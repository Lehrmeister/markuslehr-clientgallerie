<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin;

use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\ClientRepository;
use MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer;
use MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController as CQRSGalleryAdminController;

/**
 * Gallery Admin Page
 * 
 * Handles WordPress admin interface for gallery management
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryAdminPage
{
    private GalleryRepositoryInterface $galleryRepository;
    private ClientRepository $clientRepository;
    private CQRSGalleryAdminController $cqrsController;
    private ServiceContainer $serviceContainer;

    public function __construct()
    {
        // Initialize service container first
        $this->serviceContainer = new ServiceContainer();
        $this->serviceContainer->register();
        
        // Get dependencies from service container
        $this->galleryRepository = $this->serviceContainer->get(GalleryRepositoryInterface::class);
        $this->clientRepository = new ClientRepository();
        $this->cqrsController = $this->serviceContainer->get(CQRSGalleryAdminController::class);
    }

    /**
     * Initialize WordPress hooks
     */
    public function init(): void
    {
        // Add debug logging
        error_log('MLCG: GalleryAdminPage::init() called');
        
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        
        // Initialize CQRS controller
        $this->cqrsController->init();
        
        error_log('MLCG: Admin hooks registered');
    }

    /**
     * Add admin menu page
     */
    public function addAdminMenu(): void
    {
        error_log('MLCG: addAdminMenu() called');
        
        // Add main menu page
        $hookSuffix = add_menu_page(
            'Client Gallery',                    // Page title
            'Client Gallery',                    // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery',          // Menu slug
            [$this, 'renderPage'],              // Callback
            'dashicons-format-gallery',          // Icon
            30                                   // Position
        );
        
        error_log('MLCG: Main menu added with hook suffix: ' . $hookSuffix);

        // Add submenu pages
        add_submenu_page(
            'markuslehr-clientgallery',          // Parent slug
            'All Galleries',                     // Page title
            'All Galleries',                     // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery',          // Menu slug (same as parent for first item)
            [$this, 'renderPage']               // Callback
        );

        add_submenu_page(
            'markuslehr-clientgallery',          // Parent slug
            'Add New Gallery',                   // Page title
            'Add New',                          // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery-new',     // Menu slug
            [$this, 'renderNewGalleryPage']     // Callback
        );

        add_submenu_page(
            'markuslehr-clientgallery',          // Parent slug
            'Clients',                          // Page title
            'Clients',                          // Menu title
            'manage_options',                    // Capability
            'markuslehr-clientgallery-clients', // Menu slug
            [$this, 'renderClientsPage']        // Callback
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueueScripts(string $hook): void
    {
        // Check if we're on one of our admin pages
        if (strpos($hook, 'markuslehr-clientgallery') === false) {
            return;
        }

        // Enqueue WordPress media scripts for image uploads
        wp_enqueue_media();
        
        // Enqueue our custom admin scripts
        $script_url = plugin_dir_url(dirname(dirname(dirname(__DIR__)))) . 'assets/js/gallery-admin.js';
        $style_url = plugin_dir_url(dirname(dirname(dirname(__DIR__)))) . 'assets/css/gallery-admin.css';
        
        wp_enqueue_script(
            'mlcg-gallery-admin',
            $script_url,
            ['jquery', 'wp-util'],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'mlcg-gallery-admin',
            $style_url,
            [],
            '1.0.0'
        );

        // Localize script with AJAX data
        wp_localize_script('mlcg-gallery-admin', 'mlcgGallery', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mlcg_gallery_action'),
            'strings' => [
                'confirmDelete' => 'Are you sure you want to delete this gallery?',
                'createSuccess' => 'Gallery created successfully',
                'updateSuccess' => 'Gallery updated successfully',
                'deleteSuccess' => 'Gallery deleted successfully',
                'error' => 'An error occurred',
            ]
        ]);
    }

    /**
     * Render admin page
     */
    public function renderPage(): void
    {
        // Get all galleries and clients for the interface
        $galleries = $this->galleryRepository->findAll();
        $clients = $this->clientRepository->findAll();
        
        ?>
        <div class="wrap">
            <h1>Gallery Management</h1>
            
            <!-- Gallery Creation Form -->
            <div class="mlcg-gallery-form-container">
                <h2>Create New Gallery</h2>
                <form id="mlcg-create-gallery-form" class="mlcg-gallery-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gallery-name">Gallery Name *</label>
                            </th>
                            <td>
                                <input type="text" id="gallery-name" name="name" class="regular-text" required>
                                <p class="description">Enter a unique name for the gallery</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gallery-slug">Slug</label>
                            </th>
                            <td>
                                <input type="text" id="gallery-slug" name="slug" class="regular-text">
                                <p class="description">URL-friendly version (auto-generated if empty)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gallery-client">Client *</label>
                            </th>
                            <td>
                                <select id="gallery-client" name="client_id" required>
                                    <option value="">Select a client...</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo esc_attr($client->getId()); ?>">
                                            <?php echo esc_html($client->getName()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="gallery-description">Description</label>
                            </th>
                            <td>
                                <textarea id="gallery-description" name="description" rows="3" class="large-text"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Create Gallery">
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>

            <!-- Galleries List -->
            <div class="mlcg-galleries-list">
                <h2>Existing Galleries</h2>
                
                <?php if (empty($galleries)): ?>
                    <p>No galleries found. Create your first gallery above.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 20%">Name</th>
                                <th scope="col" style="width: 15%">Slug</th>
                                <th scope="col" style="width: 15%">Client</th>
                                <th scope="col" style="width: 10%">Status</th>
                                <th scope="col" style="width: 10%">Images</th>
                                <th scope="col" style="width: 15%">Created</th>
                                <th scope="col" style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($galleries as $gallery): ?>
                                <?php
                                // Get client name
                                $client = $this->clientRepository->findById($gallery->getClientId());
                                $clientName = $client ? $client->getName() : 'Unknown';
                                ?>
                                <tr data-gallery-id="<?php echo esc_attr($gallery->getId()); ?>">
                                    <td>
                                        <strong><?php echo esc_html($gallery->getName()); ?></strong>
                                        <?php if ($gallery->getDescription()): ?>
                                            <br><small><?php echo esc_html(wp_trim_words($gallery->getDescription(), 10)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($gallery->getSlug()->getValue()); ?></td>
                                    <td><?php echo esc_html($clientName); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($gallery->getStatus()->getValue()); ?>">
                                            <?php echo esc_html(ucfirst($gallery->getStatus()->getValue())); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($gallery->getImageCount()); ?></td>
                                    <td><?php echo esc_html($gallery->getCreatedAt()->format('Y-m-d H:i')); ?></td>
                                    <td>
                                        <div class="gallery-actions">
                                            <button type="button" class="button button-small edit-gallery" 
                                                    data-gallery-id="<?php echo esc_attr($gallery->getId()); ?>">
                                                Edit
                                            </button>
                                            
                                            <?php if ($gallery->isDraft()): ?>
                                                <button type="button" class="button button-small publish-gallery" 
                                                        data-gallery-id="<?php echo esc_attr($gallery->getId()); ?>"
                                                        data-action="publish">
                                                    Publish
                                                </button>
                                            <?php elseif ($gallery->isPublished()): ?>
                                                <button type="button" class="button button-small unpublish-gallery" 
                                                        data-gallery-id="<?php echo esc_attr($gallery->getId()); ?>"
                                                        data-action="draft">
                                                    Unpublish
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="button button-small button-link-delete delete-gallery" 
                                                    data-gallery-id="<?php echo esc_attr($gallery->getId()); ?>">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Gallery Modal (will be populated via JavaScript) -->
        <div id="mlcg-edit-gallery-modal" style="display: none;">
            <div class="mlcg-modal-content">
                <h2>Edit Gallery</h2>
                <form id="mlcg-edit-gallery-form">
                    <input type="hidden" id="edit-gallery-id" name="id">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="edit-gallery-name">Gallery Name</label>
                            </th>
                            <td>
                                <input type="text" id="edit-gallery-name" name="name" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="edit-gallery-slug">Slug</label>
                            </th>
                            <td>
                                <input type="text" id="edit-gallery-slug" name="slug" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="edit-gallery-description">Description</label>
                            </th>
                            <td>
                                <textarea id="edit-gallery-description" name="description" rows="3" class="large-text"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Update Gallery">
                        <button type="button" class="button" id="cancel-edit">Cancel</button>
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Edit Gallery
            $('.edit-gallery').on('click', function() {
                const galleryId = $(this).data('gallery-id');
                // TODO: Open edit modal with gallery data
                console.log('Edit gallery:', galleryId);
                alert('Edit functionality will be implemented soon!');
            });
            
            // Publish/Unpublish Gallery
            $('.publish-gallery, .unpublish-gallery').on('click', function() {
                const galleryId = $(this).data('gallery-id');
                const action = $(this).data('action');
                const $button = $(this);
                
                if (!confirm('Are you sure you want to ' + action + ' this gallery?')) {
                    return;
                }
                
                $button.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlcg_toggle_gallery_status',
                        gallery_id: galleryId,
                        status: action,
                        nonce: '<?php echo wp_create_nonce('mlcg_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload to show updated status
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Delete Gallery
            $('.delete-gallery').on('click', function() {
                const galleryId = $(this).data('gallery-id');
                const $button = $(this);
                
                if (!confirm('Are you sure you want to delete this gallery? This action cannot be undone.')) {
                    return;
                }
                
                $button.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlcg_delete_gallery',
                        gallery_id: galleryId,
                        nonce: '<?php echo wp_create_nonce('mlcg_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-gallery-id="' + galleryId + '"]').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        
        <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: #f0f0f1;
            color: #646970;
        }
        
        .status-published {
            background: #d1e7dd;
            color: #0a3622;
        }
        
        .gallery-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .gallery-actions .button {
            margin: 0;
        }
        
        .button-link-delete {
            color: #d63638 !important;
        }
        
        .button-link-delete:hover {
            color: #ffffff !important;
            background: #d63638 !important;
        }
        </style>
        <?php
    }

    /**
     * Render new gallery page
     */
    public function renderNewGalleryPage(): void
    {
        echo '<div class="wrap">';
        echo '<h1>Add New Gallery</h1>';
        echo '<div id="mlcg-new-gallery-page">';
        echo '<p>Create a new gallery for your clients...</p>';
        // TODO: Implement new gallery form
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render clients page
     */
    public function renderClientsPage(): void
    {
        echo '<div class="wrap">';
        echo '<h1>Clients</h1>';
        echo '<div id="mlcg-clients-page">';
        echo '<p>Manage your clients...</p>';
        // TODO: Implement clients management
        echo '</div>';
        echo '</div>';
    }
}
