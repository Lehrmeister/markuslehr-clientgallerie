<?php
/**
 * Frontend Gallery Template
 * 
 * Handles public gallery display based on specs.md requirements
 */

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Frontend;

use MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer;
use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface;

/**
 * Frontend Gallery Handler
 * 
 * Renders public galleries with Picdrop-inspired design
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Frontend
 * @author Markus Lehr
 * @since 1.0.0
 */
class FrontendGalleryHandler
{
    private ServiceContainer $serviceContainer;
    private QueryBusInterface $queryBus;

    public function __construct()
    {
        $this->serviceContainer = new ServiceContainer();
        $this->serviceContainer->register();
        $this->queryBus = $this->serviceContainer->get(QueryBusInterface::class);
    }

    /**
     * Initialize frontend hooks
     */
    public function init(): void
    {
        add_action('template_redirect', [$this, 'handleGalleryRequest']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Handle gallery URL requests
     */
    public function handleGalleryRequest(): void
    {
        // Check for public gallery request
        if (isset($_GET['ClientSelect_public_gallery'])) {
            $galleryId = (int) $_GET['ClientSelect_public_gallery'];
            $this->renderPublicGallery($galleryId);
            exit;
        }

        // Check for client gallery request
        if (isset($_GET['ClientSelect_gallery'])) {
            $accessToken = sanitize_text_field($_GET['ClientSelect_gallery']);
            $this->renderClientGallery($accessToken);
            exit;
        }
    }

    /**
     * Render public gallery
     */
    private function renderPublicGallery(int $galleryId): void
    {
        try {
            $query = new GetGalleryQuery($galleryId);
            $gallery = $this->queryBus->execute($query);

            // Check if gallery is published
            if (!$gallery->getStatus()->isPublished()) {
                wp_die('Gallery not found', 'Gallery Error', ['response' => 404]);
            }

            $this->renderGalleryTemplate($gallery, 'public');

        } catch (\Exception $e) {
            wp_die('Gallery not found', 'Gallery Error', ['response' => 404]);
        }
    }

    /**
     * Render client gallery
     */
    private function renderClientGallery(string $accessToken): void
    {
        // TODO: Implement client gallery access logic
        wp_die('Client galleries not yet implemented', 'Coming Soon', ['response' => 503]);
    }

    /**
     * Render gallery template
     */
    private function renderGalleryTemplate($gallery, string $accessType): void
    {
        // Prevent WordPress from loading theme (only if not already defined)
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', false);
        }
        
        // Get WordPress header
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($gallery->getName()); ?> - Gallery</title>
            <?php $this->renderStyles(); ?>
        </head>
        <body class="mlcg-gallery-frontend">
            <div id="mlcg-gallery-app" data-gallery-id="<?php echo $gallery->getId(); ?>" data-access-type="<?php echo $accessType; ?>">
                
                <!-- Header -->
                <header class="mlcg-header">
                    <div class="mlcg-container">
                        <h1 class="mlcg-gallery-title"><?php echo esc_html($gallery->getName()); ?></h1>
                        <div class="mlcg-header-actions">
                            <!-- TODO: Add download, filter, view toggle buttons -->
                        </div>
                    </div>
                </header>

                <!-- Gallery Grid -->
                <main class="mlcg-main">
                    <div class="mlcg-container">
                        <div id="mlcg-gallery-grid" class="mlcg-grid">
                            <!-- Images will be loaded via JavaScript -->
                            <div class="mlcg-loading">
                                <p>Loading gallery...</p>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- Lightbox -->
                <div id="mlcg-lightbox" class="mlcg-lightbox" style="display: none;">
                    <!-- Lightbox content will be loaded via JavaScript -->
                </div>

            </div>

            <?php $this->renderScripts(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Render inline styles (Picdrop-inspired)
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        /* Picdrop-inspired Gallery Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body.mlcg-gallery-frontend {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .mlcg-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .mlcg-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .mlcg-gallery-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        .mlcg-main {
            padding: 40px 0;
        }

        .mlcg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .mlcg-loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mlcg-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .mlcg-container {
                padding: 0 15px;
            }
        }

        @media (max-width: 480px) {
            .mlcg-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
        }
        </style>
        <?php
    }

    /**
     * Render JavaScript
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        // Frontend Gallery JavaScript (Picdrop-inspired)
        class ClientSelectGallery {
            constructor() {
                this.galleryElement = document.getElementById('mlcg-gallery-grid');
                this.galleryId = document.getElementById('mlcg-gallery-app').dataset.galleryId;
                this.init();
            }

            init() {
                this.loadGalleryImages();
            }

            async loadGalleryImages() {
                try {
                    // TODO: Implement AJAX call to load gallery images
                    console.log('Loading gallery ID:', this.galleryId);
                    
                    // Placeholder for now
                    setTimeout(() => {
                        this.renderPlaceholderImages();
                    }, 1000);
                    
                } catch (error) {
                    console.error('Error loading gallery:', error);
                    this.showError('Failed to load gallery images.');
                }
            }

            renderPlaceholderImages() {
                const loadingElement = this.galleryElement.querySelector('.mlcg-loading');
                if (loadingElement) {
                    loadingElement.innerHTML = '<p>Gallery successfully loaded! Images will be implemented next.</p>';
                }
            }

            showError(message) {
                const loadingElement = this.galleryElement.querySelector('.mlcg-loading');
                if (loadingElement) {
                    loadingElement.innerHTML = `<p style="color: #dc3545;">${message}</p>`;
                }
            }
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            new ClientSelectGallery();
        });
        </script>
        <?php
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueueScripts(): void
    {
        // Only enqueue on gallery pages
        if (!isset($_GET['ClientSelect_public_gallery']) && !isset($_GET['ClientSelect_gallery'])) {
            return;
        }

        // Frontend scripts will be enqueued here later
    }
}
