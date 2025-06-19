<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Http\Controller;

use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\UpdateGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\DeleteGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\PublishGalleryHandler;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\GalleryRepository;

/**
 * Gallery Admin Controller
 * 
 * Handles WordPress admin requests for gallery management
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Http\Controller
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryAdminController
{
    private GalleryRepository $galleryRepository;
    private CreateGalleryHandler $createHandler;
    private UpdateGalleryHandler $updateHandler;
    private DeleteGalleryHandler $deleteHandler;
    private PublishGalleryHandler $publishHandler;

    public function __construct()
    {
        $this->galleryRepository = new GalleryRepository();
        $this->createHandler = new CreateGalleryHandler($this->galleryRepository);
        $this->updateHandler = new UpdateGalleryHandler($this->galleryRepository);
        $this->deleteHandler = new DeleteGalleryHandler($this->galleryRepository);
        $this->publishHandler = new PublishGalleryHandler($this->galleryRepository);
    }

    /**
     * Initialize WordPress hooks
     */
    public function init(): void
    {
        add_action('wp_ajax_mlcg_create_gallery', [$this, 'ajaxCreateGallery']);
        add_action('wp_ajax_mlcg_update_gallery', [$this, 'ajaxUpdateGallery']);
        add_action('wp_ajax_mlcg_delete_gallery', [$this, 'ajaxDeleteGallery']);
        add_action('wp_ajax_mlcg_publish_gallery', [$this, 'ajaxPublishGallery']);
        add_action('wp_ajax_mlcg_get_galleries', [$this, 'ajaxGetGalleries']);
    }

    /**
     * AJAX: Create new gallery
     */
    public function ajaxCreateGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                throw new \Exception('Invalid nonce');
            }

            // Check capabilities
            if (!current_user_can('manage_options')) {
                throw new \Exception('Insufficient permissions');
            }

            $name = sanitize_text_field($_POST['name'] ?? '');
            $slug = sanitize_title($_POST['slug'] ?? $name);
            $clientId = (int) ($_POST['client_id'] ?? 0);
            $description = sanitize_textarea_field($_POST['description'] ?? '');

            $command = new CreateGalleryCommand(
                name: $name,
                slug: $slug,
                clientId: $clientId,
                description: $description
            );

            $gallery = $this->createHandler->handle($command);

            wp_send_json_success([
                'message' => 'Gallery created successfully',
                'gallery' => $gallery->toArray()
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Update existing gallery
     */
    public function ajaxUpdateGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                throw new \Exception('Invalid nonce');
            }

            // Check capabilities
            if (!current_user_can('manage_options')) {
                throw new \Exception('Insufficient permissions');
            }

            $id = (int) ($_POST['id'] ?? 0);
            $name = !empty($_POST['name']) ? sanitize_text_field($_POST['name']) : null;
            $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : null;
            $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : null;

            $command = new UpdateGalleryCommand(
                id: $id,
                name: $name,
                slug: $slug,
                description: $description
            );

            $gallery = $this->updateHandler->handle($command);

            wp_send_json_success([
                'message' => 'Gallery updated successfully',
                'gallery' => $gallery->toArray()
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Delete gallery
     */
    public function ajaxDeleteGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                throw new \Exception('Invalid nonce');
            }

            // Check capabilities
            if (!current_user_can('manage_options')) {
                throw new \Exception('Insufficient permissions');
            }

            $id = (int) ($_POST['id'] ?? 0);

            $command = new DeleteGalleryCommand($id);
            $this->deleteHandler->handle($command);

            wp_send_json_success([
                'message' => 'Gallery deleted successfully'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Publish/unpublish gallery
     */
    public function ajaxPublishGallery(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                throw new \Exception('Invalid nonce');
            }

            // Check capabilities
            if (!current_user_can('manage_options')) {
                throw new \Exception('Insufficient permissions');
            }

            $id = (int) ($_POST['id'] ?? 0);
            $status = sanitize_text_field($_POST['status'] ?? '');

            $command = new PublishGalleryCommand(
                id: $id,
                status: new GalleryStatus($status)
            );

            $gallery = $this->publishHandler->handle($command);

            wp_send_json_success([
                'message' => 'Gallery status updated successfully',
                'gallery' => $gallery->toArray()
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Get all galleries
     */
    public function ajaxGetGalleries(): void
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_gallery_action')) {
                throw new \Exception('Invalid nonce');
            }

            // Check capabilities
            if (!current_user_can('manage_options')) {
                throw new \Exception('Insufficient permissions');
            }

            $galleries = $this->galleryRepository->findAll();
            $galleriesArray = array_map(fn($gallery) => $gallery->toArray(), $galleries);

            wp_send_json_success([
                'galleries' => $galleriesArray
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
