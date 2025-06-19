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
        $serviceContainer = new \MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer();
        $serviceContainer->register();
        
        // Initialize repository manager
        $repositoryManager = \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager::getInstance();
        
        // Perform health check on repositories
        $healthStatus = $repositoryManager->getSystemHealth();
        if ($healthStatus['overall_status'] !== 'healthy') {
            $this->logger?->warning('Repository system health issues detected', $healthStatus);
        }
        
        $this->logger?->debug('Dependencies loaded', [
            'services_registered' => $serviceContainer->getRegisteredServices(),
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
        add_action('admin_menu', [$this, 'adminMenu']); // Direct admin menu hook
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
            // Initialize gallery admin page
            $galleryAdminPage = new \MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage();
            $galleryAdminPage->init();
            
            $this->logger->debug('Admin init completed');
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
        echo '<div class="wrap">';
        echo '<h1>Client Gallery - All Galleries</h1>';
        echo '<div id="mlcg-admin-app">';
        echo '<p><strong>Admin interface is working!</strong></p>';
        echo '<p>This will be replaced with the full gallery management interface.</p>';
        echo '</div>';
        echo '</div>';
    }

    public function renderNewGalleryPage(): void
    {
        echo '<div class="wrap">';
        echo '<h1>Add New Gallery</h1>';
        echo '<div id="mlcg-new-gallery-app">';
        echo '<p><strong>New gallery page is working!</strong></p>';
        echo '<p>This will be replaced with the gallery creation form.</p>';
        echo '</div>';
        echo '</div>';
    }
}

// Initialize the plugin
MarkusLehrClientGallerie::getInstance();
