<?php
/**
 * Plugin Name: MarkusLehr ClientGallerie
 * Plugin URI: https://markuslehr.com/plugins/clientgallerie
 * Description: Professional WordPress Gallery Plugin with Picdrop-inspired UI and intelligent logging
 * Version: 1.0.0
 * Author: Markus Lehr
 * Author URI: https://markuslehr.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: markuslehr-clientgallerie
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 8.0
 * Network: false
 * 
 * @package MarkusLehrClientGallerie
 * @author Markus Lehr
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MLCG_VERSION', '1.0.0');
define('MLCG_PLUGIN_FILE', __FILE__);
define('MLCG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MLCG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MLCG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once MLCG_PLUGIN_DIR . 'vendor/autoload.php';

// Bootstrap the plugin
final class MarkusLehrClientGallerie 
{
    private static ?self $instance = null;
    private ?\MarkusLehr\ClientGallerie\Infrastructure\Logging\Logger $logger = null;
    private ?\MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer $serviceContainer = null;
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() 
    {
        // Verzögere Logger-Initialisierung bis WordPress bereit ist
        add_action('plugins_loaded', [$this, 'initializeLogger'], 1);
        add_action('plugins_loaded', [$this, 'loadDependencies'], 2);
        add_action('plugins_loaded', [$this, 'initializeHooks'], 3);
    }
    
    public function initializeLogger(): void 
    {
        $this->logger = new \MarkusLehr\ClientGallerie\Infrastructure\Logging\Logger();
        
        // Register logger globally for other components
        \MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry::setLogger($this->logger);
        
        $this->logger->info('Plugin bootstrapped successfully', [
            'version' => MLCG_VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ]);
    }
    
    public function loadDependencies(): void 
    {
        // Load core services
        $this->serviceContainer = new \MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer();
        $this->serviceContainer->register();
        
        // Initialize repository manager
        $repositoryManager = \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager::getInstance();
        
        // Perform health check on repositories
        $healthStatus = $repositoryManager->getSystemHealth();
        if ($healthStatus['overall_status'] !== 'healthy') {
            $this->logger?->warning('Repository system health issues detected', $healthStatus);
        }
        
        $this->logger?->debug('Dependencies loaded', [
            'services_registered' => $this->serviceContainer->getRegisteredServices(),
            'repository_health' => $healthStatus['overall_status']
        ]);
    }
    
    public function initializeHooks(): void 
    {
        // Activation/Deactivation hooks
        register_activation_hook(MLCG_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MLCG_PLUGIN_FILE, [$this, 'deactivate']);
        
        // WordPress hooks
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('admin_menu', [$this, 'adminMenu'], 10); // Ensure admin menu is added after dependencies are loaded
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
        
        // Admin Controller für Backend - only after tables are created
        // TODO: Re-enable after successful database setup
        /*
        if (is_admin()) {
            $adminController = new \MarkusLehr\ClientGallerie\Application\Controller\AdminController();
            $adminController->initialize();
            $this->logger->debug('Admin controller initialized in hooks');
        }
        */
        
        // AJAX hooks - disabled for now
        // add_action('wp_ajax_mlcg_action', [$this, 'handleAjaxRequest']);
        // add_action('wp_ajax_nopriv_mlcg_action', [$this, 'handleAjaxRequest']);
        
        $this->logger?->debug('WordPress hooks initialized');
    }
    
    public function activate(): void 
    {
        $this->logger->info('Plugin activation started');
        
        // Initialize schema manager and install database tables
        $schemaManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\SchemaManager();
        $this->registerSchemas($schemaManager);
        
        $installResult = $schemaManager->installAll();
        if (!$installResult['success']) {
            $this->logger->error('Database installation failed', $installResult);
            wp_die('Database installation failed: ' . implode(', ', $installResult['errors']));
        }
        
        // Run pending migrations
        $migrationManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager();
        $this->registerMigrations($migrationManager);
        
        $migrationResult = $migrationManager->runPendingMigrations();
        if ($migrationResult['status'] !== 'success') {
            $this->logger->warning('Some migrations failed', $migrationResult);
        }
        
        // Create directories
        $this->createDirectories();
        
        // Set default options
        $this->setDefaultOptions();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $this->logger->info('Plugin activated successfully', [
            'database_status' => $installResult,
            'migration_status' => $migrationResult
        ]);
    }
    
    public function deactivate(): void 
    {
        $this->logger->info('Plugin deactivation started');
        
        // Clean up scheduled events
        wp_clear_scheduled_hook('mlcg_cleanup_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $this->logger->info('Plugin deactivated successfully');
    }
    
    public function init(): void 
    {
        // Load text domain
        load_plugin_textdomain(
            'markuslehr-clientgallerie',
            false,
            dirname(MLCG_PLUGIN_BASENAME) . '/languages'
        );
        
        // Initialize main components
        $this->initializeComponents();
        
        // Initialize frontend handler
        $this->initializeFrontend();
        
        $this->logger->debug('Plugin initialized for current request');
    }
    
    public function adminInit(): void 
    {
        // Admin-specific initialization
        if (is_admin()) {
            // Ensure service container is initialized
            if (!$this->serviceContainer) {
                $this->loadDependencies();
            }
            
            // Initialize gallery admin page
            $galleryAdminPage = new \MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage();
            $galleryAdminPage->init();
            
            // Initialize AJAX handlers
            $this->initAjaxHandlers();
        }
        
        $this->logger->debug('Admin initialized');
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function initAjaxHandlers(): void
    {
        try {
            // Check if service container is available
            if (!$this->serviceContainer) {
                $this->logger->error('ServiceContainer not initialized when trying to setup AJAX handlers');
                return;
            }
            
            // Get AJAX handler from service container
            $ajaxHandler = $this->serviceContainer->get(\MarkusLehr\ClientGallerie\Infrastructure\Ajax\GalleryAjaxHandler::class);
            $ajaxHandler->init();
            
            $this->logger->debug('AJAX handlers initialized');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize AJAX handlers', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function initializeFrontend(): void
    {
        // Initialize frontend gallery handler
        $frontendHandler = new \MarkusLehr\ClientGallerie\Infrastructure\Frontend\FrontendGalleryHandler();
        $frontendHandler->init();
        
        $this->logger->debug('Frontend handler initialized');
    }
    
    public function enqueueScripts(): void 
    {
        if (!is_admin()) {
            wp_enqueue_script(
                'mlcg-frontend',
                MLCG_PLUGIN_URL . 'assets/dist/js/frontend.js',
                ['jquery'],
                MLCG_VERSION,
                true
            );
            
            wp_enqueue_style(
                'mlcg-frontend',
                MLCG_PLUGIN_URL . 'assets/dist/css/frontend.css',
                [],
                MLCG_VERSION
            );
            
            $this->logger->debug('Frontend assets enqueued');
        }
    }
    
    public function adminEnqueueScripts(): void 
    {
        if (is_admin()) {
            wp_enqueue_script(
                'mlcg-admin',
                MLCG_PLUGIN_URL . 'assets/dist/js/admin.js',
                ['jquery'],
                MLCG_VERSION,
                true
            );
            
            wp_enqueue_style(
                'mlcg-admin',
                MLCG_PLUGIN_URL . 'assets/dist/css/admin.css',
                [],
                MLCG_VERSION
            );
            
            $this->logger->debug('Admin assets enqueued');
        }
    }
    
    public function handleAjaxRequest(): void 
    {
        $ajaxHandler = new \MarkusLehr\ClientGallerie\Infrastructure\Http\AjaxHandler();
        $ajaxHandler->handle();
    }
    
    private function initializeComponents(): void 
    {
        try {
            // Get repository manager
            $repositories = \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager::getInstance();
            
            // Get system statistics
            $stats = $repositories->getAggregatedStatistics();
            
            $this->logger?->debug('Core components initialized', [
                'repository_stats' => $stats['totals'] ?? []
            ]);
            
        } catch (\Exception $e) {
            $this->logger?->error('Failed to initialize components', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    private function createDirectories(): void 
    {
        $uploadsDir = wp_upload_dir();
        $directories = [
            $uploadsDir['basedir'] . '/mlcg-galleries',
            $uploadsDir['basedir'] . '/mlcg-galleries/thumbnails',
            $uploadsDir['basedir'] . '/mlcg-galleries/temp',
            MLCG_PLUGIN_DIR . 'logs'
        ];
        
        foreach ($directories as $dir) {
            if (!wp_mkdir_p($dir)) {
                $this->logger->error("Failed to create directory: $dir");
            } else {
                $this->logger->debug("Directory created: $dir");
            }
        }
    }
    
    private function setDefaultOptions(): void 
    {
        $defaults = [
            'mlcg_version' => MLCG_VERSION,
            'mlcg_log_level' => 'info',
            'mlcg_log_retention_days' => 30,
            'mlcg_max_log_file_size' => 10, // MB
            'mlcg_enable_debug' => false,
            'mlcg_ajax_log_limit' => 100 // Max log entries for AJAX requests
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
                $this->logger->debug("Default option set: $option = $value");
            }
        }
    }
    
    public function getLogger(): \MarkusLehr\ClientGallerie\Infrastructure\Logging\Logger 
    {
        return $this->logger;
    }
    
    /**
     * Register all database schemas
     */
    private function registerSchemas(\MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\SchemaManager $schemaManager): void
    {
        $schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\ClientSchema());
        $schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\GallerySchema());
        $schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\ImageSchema());
        $schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\RatingSchema());
        $schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\LogEntrySchema());
        
        $this->logger->debug('Database schemas registered', [
            'schemas' => $schemaManager->getRegisteredSchemas()
        ]);
    }
    
    /**
     * Register all database migrations
     */
    private function registerMigrations(\MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager $migrationManager): void
    {
        // Register migrations in version order
        $migrationManager->registerMigration(
            new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\Migration_001_AddSocialMediaToClients()
        );
        
        // Add more migrations here as they are created
        
        $this->logger->debug('Database migrations registered', [
            'migration_status' => $migrationManager->getMigrationStatus()
        ]);
    }
    
    public function adminMenu(): void
    {
        // Ensure service container is initialized before creating admin menu
        if (!$this->serviceContainer) {
            $this->loadDependencies();
        }
        
        // Direct admin menu registration
        add_menu_page(
            'Client Gallery',                    // Page title
            'Client Gallery',                    // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery',          // Menu slug
            [$this, 'renderAdminPage'],         // Callback
            'dashicons-format-gallery',          // Icon
            30                                   // Position
        );

        // Add submenu pages
        add_submenu_page(
            'markuslehr-clientgallery',          // Parent slug
            'All Galleries',                     // Page title
            'All Galleries',                     // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery',          // Menu slug (same as parent for first item)
            [$this, 'renderAdminPage']          // Callback
        );

        add_submenu_page(
            'markuslehr-clientgallery',          // Parent slug
            'Add New Gallery',                   // Page title
            'Add New',                          // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery-new',     // Menu slug
            [$this, 'renderNewGalleryPage']     // Callback
        );

        $this->logger?->debug('Admin menu registered directly in main plugin');
    }

    public function renderAdminPage(): void
    {
        // Get galleries using CQRS
        try {
            // Ensure ServiceContainer is initialized
            if (!$this->serviceContainer) {
                $this->loadDependencies();
            }
            
            $queryBus = $this->serviceContainer->get(\MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface::class);
            $listQuery = new \MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery();
            $galleries = $queryBus->execute($listQuery);
        } catch (\Exception $e) {
            $galleries = [];
            $error = $e->getMessage();
            $this->logger?->error('Error rendering admin page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Client Gallery - All Galleries</h1>';
        echo '<a href="' . admin_url('admin.php?page=markuslehr-clientgallery-new') . '" class="page-title-action">Add New Gallery</a>';
        echo '<hr class="wp-header-end">';
        
        // Show success message
        if (isset($_GET['message']) && $_GET['message'] === 'created') {
            echo '<div class="notice notice-success is-dismissible"><p>Gallery created successfully!</p></div>';
        }
        
        if (isset($error)) {
            echo '<div class="notice notice-error"><p>Error loading galleries: ' . esc_html($error) . '</p></div>';
        }

        echo '<div id="mlcg-admin-app">';
        
        if (empty($galleries)) {
            echo '<div class="mlcg-empty-state">';
            echo '<h2>No galleries found</h2>';
            echo '<p>Create your first gallery to get started!</p>';
            echo '<a href="' . admin_url('admin.php?page=markuslehr-clientgallery-new') . '" class="button button-primary">Create First Gallery</a>';
            echo '</div>';
        } else {
            $this->renderGalleryTable($galleries);
        }
        
        echo '</div>';
        echo '</div>';
        
        // Add admin styles
        echo '<style>
        .mlcg-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .mlcg-gallery-table {
            margin-top: 20px;
        }
        .mlcg-status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        .mlcg-status-published {
            background: #d1ecf1;
            color: #0c5460;
        }
        .mlcg-status-draft {
            background: #f8d7da;
            color: #721c24;
        }
        .mlcg-public-url {
            font-family: monospace;
            font-size: 11px;
            color: #666;
        }
        </style>';
    }

    private function renderGalleryTable($galleries): void
    {
        echo '<table class="wp-list-table widefat fixed striped mlcg-gallery-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Gallery Name</th>';
        echo '<th>Status</th>';
        echo '<th>Client ID</th>';
        echo '<th>Created</th>';
        echo '<th>Public URL</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($galleries as $gallery) {
            $statusClass = $gallery->getStatus()->getValue() === 'published' ? 'mlcg-status-published' : 'mlcg-status-draft';
            $publicUrl = home_url('/?ClientSelect_public_gallery=' . $gallery->getId());
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($gallery->getName()) . '</strong><br>';
            echo '<small>' . esc_html($gallery->getDescription()) . '</small></td>';
            echo '<td><span class="mlcg-status-badge ' . $statusClass . '">' . esc_html($gallery->getStatus()->getValue()) . '</span></td>';
            echo '<td>' . esc_html($gallery->getClientId()) . '</td>';
            echo '<td>' . esc_html($gallery->getCreatedAt()->format('Y-m-d H:i')) . '</td>';
            echo '<td><code class="mlcg-public-url">' . esc_html($publicUrl) . '</code></td>';
            echo '<td>';
            echo '<a href="' . esc_url($publicUrl) . '" class="button button-small" target="_blank">View</a> ';
            echo '<a href="#" class="button button-small" onclick="editGallery(' . $gallery->getId() . ')">Edit</a> ';
            if ($gallery->getStatus()->getValue() === 'draft') {
                echo '<a href="#" class="button button-small" onclick="publishGallery(' . $gallery->getId() . ')">Publish</a> ';
            }
            echo '<a href="#" class="button button-small button-link-delete" onclick="deleteGallery(' . $gallery->getId() . ')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Add JavaScript for actions
        echo '<script>
        function editGallery(id) {
            alert("Edit functionality will be implemented next. Gallery ID: " + id);
        }
        
        function publishGallery(id) {
            if (confirm("Publish this gallery?")) {
                // TODO: Implement AJAX call to publish gallery
                alert("Publish functionality will be implemented next. Gallery ID: " + id);
            }
        }
        
        function deleteGallery(id) {
            if (confirm("Are you sure you want to delete this gallery? This action cannot be undone.")) {
                // TODO: Implement AJAX call to delete gallery
                alert("Delete functionality will be implemented next. Gallery ID: " + id);
            }
        }
        </script>';
    }

    public function renderNewGalleryPage(): void
    {
        // Handle form submission
        if ($_POST && isset($_POST['mlcg_create_gallery'])) {
            $this->handleCreateGallery();
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Add New Gallery</h1>';
        echo '<a href="' . admin_url('admin.php?page=markuslehr-clientgallery') . '" class="page-title-action">← Back to Galleries</a>';
        echo '<hr class="wp-header-end">';
        
        echo '<div id="mlcg-new-gallery-app">';
        echo '<form method="post" action="" class="mlcg-gallery-form">';
        
        // Security nonce
        wp_nonce_field('mlcg_create_gallery', 'mlcg_nonce');
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row"><label for="gallery_name">Gallery Name *</label></th>';
        echo '<td><input type="text" id="gallery_name" name="gallery_name" class="regular-text" required /></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="gallery_slug">Gallery Slug</label></th>';
        echo '<td>';
        echo '<input type="text" id="gallery_slug" name="gallery_slug" class="regular-text" />';
        echo '<p class="description">Leave empty to auto-generate from gallery name</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="gallery_description">Description</label></th>';
        echo '<td><textarea id="gallery_description" name="gallery_description" rows="4" class="large-text"></textarea></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="client_id">Client ID</label></th>';
        echo '<td>';
        echo '<input type="number" id="client_id" name="client_id" class="small-text" value="1" min="1" />';
        echo '<p class="description">Client assignment for organization</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="gallery_status">Status</label></th>';
        echo '<td>';
        echo '<select id="gallery_status" name="gallery_status">';
        echo '<option value="draft">Draft</option>';
        echo '<option value="published">Published</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        
        echo '<p class="submit">';
        echo '<input type="submit" name="mlcg_create_gallery" class="button-primary" value="Create Gallery" />';
        echo '</p>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        // Add auto-slug generation JavaScript
        echo '<script>
        document.getElementById("gallery_name").addEventListener("input", function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, "")
                .replace(/\s+/g, "-")
                .substring(0, 50);
            document.getElementById("gallery_slug").value = slug;
        });
        </script>';
    }

    private function handleCreateGallery(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['mlcg_nonce'], 'mlcg_create_gallery')) {
            wp_die('Security check failed');
        }

        // Sanitize input
        $name = sanitize_text_field($_POST['gallery_name']);
        $slug = sanitize_title($_POST['gallery_slug']) ?: sanitize_title($name);
        $description = sanitize_textarea_field($_POST['gallery_description']);
        $clientId = (int) $_POST['client_id'];
        $status = in_array($_POST['gallery_status'], ['draft', 'published']) ? $_POST['gallery_status'] : 'draft';

        try {
            // Ensure ServiceContainer is initialized
            if (!$this->serviceContainer) {
                $this->loadDependencies();
            }
            
            // Use CQRS to create gallery
            $commandBus = $this->serviceContainer->get(\MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface::class);
            
            $createCommand = new \MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand(
                $name,
                $slug,
                $clientId,
                $description
            );
            
            $gallery = $commandBus->execute($createCommand);
            
            // If status is published, publish it
            if ($status === 'published') {
                $publishCommand = new \MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand(
                    $gallery->getId(),
                    new \MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus('published')
                );
                $commandBus->execute($publishCommand);
            }
            
            // Redirect to gallery list with success message
            $redirectUrl = add_query_arg([
                'page' => 'markuslehr-clientgallery',
                'message' => 'created'
            ], admin_url('admin.php'));
            
            wp_redirect($redirectUrl);
            exit;
            
        } catch (\Exception $e) {
            echo '<div class="notice notice-error"><p>Error creating gallery: ' . esc_html($e->getMessage()) . '</p></div>';
            $this->logger?->error('Error creating gallery', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

// Initialize the plugin
MarkusLehrClientGallerie::getInstance();
