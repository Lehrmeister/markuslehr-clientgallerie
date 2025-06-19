<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Ajax;

use MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface;
use MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface;
use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UnpublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery;
use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * AJAX Handler for Gallery Admin Operations
 * 
 * Handles all AJAX requests from the admin interface
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Ajax
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryAjaxHandler
{
    private CommandBusInterface $commandBus;
    private QueryBusInterface $queryBus;

    public function __construct(CommandBusInterface $commandBus, QueryBusInterface $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * Initialize AJAX hooks
     */
    public function init(): void
    {
        // Admin AJAX hooks
        add_action('wp_ajax_mlcg_create_gallery', [$this, 'handleCreateGallery']);
        add_action('wp_ajax_mlcg_get_gallery', [$this, 'handleGetGallery']);
        add_action('wp_ajax_mlcg_update_gallery', [$this, 'handleUpdateGallery']);
        add_action('wp_ajax_mlcg_delete_gallery', [$this, 'handleDeleteGallery']);
        add_action('wp_ajax_mlcg_change_gallery_status', [$this, 'handleChangeGalleryStatus']);
        add_action('wp_ajax_mlcg_list_galleries', [$this, 'handleListGalleries']);
        
        LoggerRegistry::getLogger()?->debug('Gallery AJAX handlers registered');
    }

    /**
     * Handle gallery creation
     */
    public function handleCreateGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            // Validate input
            $name = sanitize_text_field($_POST['name'] ?? '');
            $slug = sanitize_title($_POST['slug'] ?? '');
            $clientId = absint($_POST['client_id'] ?? 0);
            $description = sanitize_textarea_field($_POST['description'] ?? '');

            if (empty($name)) {
                wp_send_json_error(['message' => 'Gallery name is required']);
                return;
            }

            if ($clientId <= 0) {
                wp_send_json_error(['message' => 'Valid client selection is required']);
                return;
            }

            // Generate slug if empty
            if (empty($slug)) {
                $slug = sanitize_title($name);
            }

            // Create command
            $command = new CreateGalleryCommand(
                $name,
                $slug,
                $clientId,
                $description
            );

            // Execute command
            $gallery = $this->commandBus->dispatch($command);

            LoggerRegistry::getLogger()?->info('Gallery created via AJAX', [
                'gallery_id' => $gallery->getId(),
                'name' => $gallery->getName(),
                'user_id' => get_current_user_id()
            ]);

            wp_send_json_success([
                'message' => 'Gallery created successfully',
                'gallery' => [
                    'id' => $gallery->getId(),
                    'name' => $gallery->getName(),
                    'slug' => $gallery->getSlug()->getValue(),
                    'status' => $gallery->getStatus()->getValue(),
                    'client_id' => $gallery->getClientId(),
                    'description' => $gallery->getDescription()
                ]
            ]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Gallery creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle get gallery
     */
    public function handleGetGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            $galleryId = absint($_POST['gallery_id'] ?? 0);
            if ($galleryId <= 0) {
                wp_send_json_error(['message' => 'Invalid gallery ID']);
                return;
            }

            // Create query
            $query = new GetGalleryQuery($galleryId);
            $gallery = $this->queryBus->execute($query);

            if (!$gallery) {
                wp_send_json_error(['message' => 'Gallery not found']);
                return;
            }

            wp_send_json_success([
                'gallery' => [
                    'id' => $gallery->getId(),
                    'name' => $gallery->getName(),
                    'slug' => $gallery->getSlug()->getValue(),
                    'status' => $gallery->getStatus()->getValue(),
                    'client_id' => $gallery->getClientId(),
                    'description' => $gallery->getDescription(),
                    'created_at' => $gallery->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Get gallery failed', [
                'error' => $e->getMessage(),
                'gallery_id' => $_POST['gallery_id'] ?? null
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle gallery update
     */
    public function handleUpdateGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            // Validate input
            $galleryId = absint($_POST['id'] ?? 0);
            $name = sanitize_text_field($_POST['name'] ?? '');
            $slug = sanitize_title($_POST['slug'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            $status = sanitize_text_field($_POST['status'] ?? '');

            if ($galleryId <= 0) {
                wp_send_json_error(['message' => 'Invalid gallery ID']);
                return;
            }

            if (empty($name)) {
                wp_send_json_error(['message' => 'Gallery name is required']);
                return;
            }

            // Generate slug if empty
            if (empty($slug)) {
                $slug = sanitize_title($name);
            }

            // Create command
            $command = new UpdateGalleryCommand(
                $galleryId,
                $name,
                $slug,
                $description
            );

            // Execute command
            $gallery = $this->commandBus->dispatch($command);

            // Handle status change if provided
            if (!empty($status) && in_array($status, ['draft', 'published'])) {
                if ($status === 'published' && $gallery->isDraft()) {
                    $publishCommand = new PublishGalleryCommand($galleryId);
                    $this->commandBus->dispatch($publishCommand);
                } elseif ($status === 'draft' && $gallery->isPublished()) {
                    $unpublishCommand = new UnpublishGalleryCommand($galleryId);
                    $this->commandBus->dispatch($unpublishCommand);
                }
            }

            LoggerRegistry::getLogger()?->info('Gallery updated via AJAX', [
                'gallery_id' => $gallery->getId(),
                'name' => $gallery->getName(),
                'user_id' => get_current_user_id()
            ]);

            wp_send_json_success([
                'message' => 'Gallery updated successfully',
                'gallery' => [
                    'id' => $gallery->getId(),
                    'name' => $gallery->getName(),
                    'slug' => $gallery->getSlug()->getValue(),
                    'status' => $gallery->getStatus()->getValue(),
                    'description' => $gallery->getDescription()
                ]
            ]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Gallery update failed', [
                'error' => $e->getMessage(),
                'gallery_id' => $_POST['id'] ?? null
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle gallery deletion
     */
    public function handleDeleteGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            $galleryId = absint($_POST['gallery_id'] ?? 0);
            if ($galleryId <= 0) {
                wp_send_json_error(['message' => 'Invalid gallery ID']);
                return;
            }

            // Create command
            $command = new DeleteGalleryCommand($galleryId);
            $this->commandBus->dispatch($command);

            LoggerRegistry::getLogger()?->info('Gallery deleted via AJAX', [
                'gallery_id' => $galleryId,
                'user_id' => get_current_user_id()
            ]);

            wp_send_json_success([
                'message' => 'Gallery deleted successfully'
            ]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Gallery deletion failed', [
                'error' => $e->getMessage(),
                'gallery_id' => $_POST['gallery_id'] ?? null
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle gallery status change
     */
    public function handleChangeGalleryStatus(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            $galleryId = absint($_POST['gallery_id'] ?? 0);
            $status = sanitize_text_field($_POST['status'] ?? '');

            if ($galleryId <= 0) {
                wp_send_json_error(['message' => 'Invalid gallery ID']);
                return;
            }

            if (!in_array($status, ['draft', 'publish'])) {
                wp_send_json_error(['message' => 'Invalid status']);
                return;
            }

            // Create appropriate command
            if ($status === 'publish') {
                $command = new PublishGalleryCommand($galleryId);
                $message = 'Gallery published successfully';
            } else {
                $command = new UnpublishGalleryCommand($galleryId);
                $message = 'Gallery unpublished successfully';
            }

            $this->commandBus->dispatch($command);

            LoggerRegistry::getLogger()?->info('Gallery status changed via AJAX', [
                'gallery_id' => $galleryId,
                'status' => $status,
                'user_id' => get_current_user_id()
            ]);

            wp_send_json_success(['message' => $message]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Gallery status change failed', [
                'error' => $e->getMessage(),
                'gallery_id' => $_POST['gallery_id'] ?? null,
                'status' => $_POST['status'] ?? null
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle list galleries
     */
    public function handleListGalleries(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            // Get filter parameters
            $status = sanitize_text_field($_POST['status'] ?? '');
            $clientId = absint($_POST['client_id'] ?? 0);
            $search = sanitize_text_field($_POST['search'] ?? '');
            $limit = absint($_POST['limit'] ?? 25);
            $offset = absint($_POST['offset'] ?? 0);

            // Create query
            $query = new ListGalleriesQuery($status, $clientId, $search, $limit, $offset);
            $galleries = $this->queryBus->execute($query);

            $galleryData = [];
            foreach ($galleries as $gallery) {
                $galleryData[] = [
                    'id' => $gallery->getId(),
                    'name' => $gallery->getName(),
                    'slug' => $gallery->getSlug()->getValue(),
                    'status' => $gallery->getStatus()->getValue(),
                    'client_id' => $gallery->getClientId(),
                    'description' => $gallery->getDescription(),
                    'image_count' => $gallery->getImageCount(),
                    'created_at' => $gallery->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            wp_send_json_success([
                'galleries' => $galleryData,
                'total' => count($galleryData)
            ]);

        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('List galleries failed', [
                'error' => $e->getMessage()
            ]);

            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
