<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Admin;

use MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface;
use MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface;
use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery;
use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;

/**
 * Gallery Admin Controller
 * 
 * WordPress admin interface for gallery management
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Admin
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryAdminController
{
    private CommandBusInterface $commandBus;
    private QueryBusInterface $queryBus;

    public function __construct(
        CommandBusInterface $commandBus,
        QueryBusInterface $queryBus
    ) {
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * Initialize admin hooks
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'handleAdminActions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu(): void
    {
        add_menu_page(
            'Client Galleries',
            'Galleries',
            'manage_options',
            'client-galleries',
            [$this, 'renderGalleriesPage'],
            'dashicons-format-gallery',
            30
        );
    }

    /**
     * Handle admin actions
     */
    public function handleAdminActions(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'gallery_admin_action')) {
            if ($action) {
                wp_die('Security check failed');
            }
            return;
        }

        try {
            switch ($action) {
                case 'create_gallery':
                    $this->handleCreateGallery();
                    break;
                case 'delete_gallery':
                    $this->handleDeleteGallery();
                    break;
                case 'update_gallery':
                    $this->handleUpdateGallery();
                    break;
                case 'publish_gallery':
                    $this->handlePublishGallery();
                    break;
            }
        } catch (\Exception $e) {
            add_settings_error('gallery_admin', 'gallery_error', $e->getMessage());
        }
    }

    /**
     * Handle gallery creation
     */
    private function handleCreateGallery(): void
    {
        $name = sanitize_text_field($_POST['gallery_name'] ?? '');
        $slug = sanitize_title($_POST['gallery_slug'] ?? $name);
        $clientId = (int) ($_POST['client_id'] ?? 1);
        $description = sanitize_textarea_field($_POST['gallery_description'] ?? '');

        if (empty($name)) {
            throw new \InvalidArgumentException('Gallery name is required');
        }

        $command = new CreateGalleryCommand($name, $slug, $clientId, $description);
        $this->commandBus->execute($command);

        add_settings_error('gallery_admin', 'gallery_created', 'Gallery created successfully!', 'updated');
    }

    /**
     * Handle gallery deletion
     */
    private function handleDeleteGallery(): void
    {
        $id = (int) ($_POST['gallery_id'] ?? $_GET['gallery_id'] ?? 0);
        
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid gallery ID');
        }

        $command = new DeleteGalleryCommand($id);
        $this->commandBus->execute($command);

        add_settings_error('gallery_admin', 'gallery_deleted', 'Gallery deleted successfully!', 'updated');
    }

    /**
     * Handle gallery update
     */
    private function handleUpdateGallery(): void
    {
        $id = (int) ($_POST['gallery_id'] ?? 0);
        $name = sanitize_text_field($_POST['gallery_name'] ?? '');
        $slug = sanitize_title($_POST['gallery_slug'] ?? '');
        $description = sanitize_textarea_field($_POST['gallery_description'] ?? '');

        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid gallery ID');
        }

        $command = new UpdateGalleryCommand($id, $name ?: null, $slug ?: null, $description ?: null);
        $this->commandBus->execute($command);

        add_settings_error('gallery_admin', 'gallery_updated', 'Gallery updated successfully!', 'updated');
    }

    /**
     * Handle gallery publish/unpublish
     */
    private function handlePublishGallery(): void
    {
        $id = (int) ($_POST['gallery_id'] ?? $_GET['gallery_id'] ?? 0);
        $status = sanitize_text_field($_POST['gallery_status'] ?? $_GET['gallery_status'] ?? 'published');

        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid gallery ID');
        }

        $galleryStatus = GalleryStatus::fromString($status);
        $command = new PublishGalleryCommand($id, $galleryStatus);
        $this->commandBus->execute($command);

        add_settings_error('gallery_admin', 'gallery_published', 'Gallery status updated successfully!', 'updated');
    }

    /**
     * Render galleries admin page
     */
    public function renderGalleriesPage(): void
    {
        $galleries = $this->queryBus->execute(new ListGalleriesQuery());
        
        ?>
        <div class="wrap">
            <h1>Client Galleries</h1>
            
            <?php settings_errors('gallery_admin'); ?>
            
            <!-- Create Gallery Form -->
            <div class="card">
                <h2>Create New Gallery</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('gallery_admin_action'); ?>
                    <input type="hidden" name="action" value="create_gallery">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Gallery Name</th>
                            <td><input type="text" name="gallery_name" required class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Slug</th>
                            <td><input type="text" name="gallery_slug" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Client ID</th>
                            <td><input type="number" name="client_id" value="1" min="1" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td><textarea name="gallery_description" class="large-text" rows="3"></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Create Gallery" />
                    </p>
                </form>
            </div>
            
            <!-- Galleries List -->
            <div class="card">
                <h2>Existing Galleries</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Client ID</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($galleries as $gallery): ?>
                        <tr>
                            <td><?php echo esc_html($gallery->getId()); ?></td>
                            <td><?php echo esc_html($gallery->getName()); ?></td>
                            <td><?php echo esc_html($gallery->getSlug()); ?></td>
                            <td><?php echo esc_html($gallery->getStatus()->toString()); ?></td>
                            <td><?php echo esc_html($gallery->getClientId()); ?></td>
                            <td><?php echo esc_html($gallery->getCreatedAt()->format('Y-m-d H:i')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg([
                                    'action' => 'publish_gallery',
                                    'gallery_id' => $gallery->getId(),
                                    'gallery_status' => $gallery->getStatus()->toString() === 'published' ? 'draft' : 'published',
                                    '_wpnonce' => wp_create_nonce('gallery_admin_action')
                                ])); ?>" class="button">
                                    <?php echo $gallery->getStatus()->toString() === 'published' ? 'Unpublish' : 'Publish'; ?>
                                </a>
                                
                                <a href="<?php echo esc_url(add_query_arg([
                                    'action' => 'delete_gallery',
                                    'gallery_id' => $gallery->getId(),
                                    '_wpnonce' => wp_create_nonce('gallery_admin_action')
                                ])); ?>" class="button button-secondary" 
                                   onclick="return confirm('Are you sure you want to delete this gallery?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueueScripts($hook): void
    {
        if ($hook !== 'toplevel_page_client-galleries') {
            return;
        }
        
        wp_enqueue_script('gallery-admin', plugin_dir_url(__FILE__) . '../../../assets/js/admin.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('gallery-admin', plugin_dir_url(__FILE__) . '../../../assets/css/admin.css', [], '1.0.0');
    }
}
