<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Http;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * AJAX Handler mit intelligentem Logging
 * Optimiert für Performance bei häufigen AJAX-Requests
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Http
 * @author Markus Lehr
 * @since 1.0.0
 */
class AjaxHandler 
{
    private array $handlers = [];
    
    public function __construct() 
    {
        $this->registerHandlers();
    }
    
    public function handle(): void 
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        if (!str_starts_with($action, 'mlcg_')) {
            return;
        }
        
        $handler = str_replace('mlcg_', '', $action);
        
        $logger = LoggerRegistry::getLogger();
        $logger?->logAjax($action, $_POST);
        
        if (!isset($this->handlers[$handler])) {
            $logger?->warning("Unknown AJAX handler: $handler");
            wp_send_json_error(['message' => 'Unknown action']);
            return;
        }
        
        try {
            // Verify nonce for security
            if (!$this->verifyNonce($action)) {
                $logger?->warning("Invalid nonce for AJAX action: $action", [
                    'user_id' => get_current_user_id(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $result = call_user_func($this->handlers[$handler]);
            
            $executionTime = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            $logger?->info("AJAX action completed: $action", [
                'execution_time' => round($executionTime * 1000, 2) . 'ms',
                'memory_used' => $memoryUsed . ' bytes',
                'result_type' => gettype($result)
            ]);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            $logger?->error("AJAX action failed: $action", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'post_data' => $_POST
            ]);
            
            wp_send_json_error([
                'message' => 'An error occurred',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }
    
    private function registerHandlers(): void 
    {
        $this->handlers = [
            'get_galleries' => [$this, 'handleGetGalleries'],
            'upload_image' => [$this, 'handleUploadImage'],
            'rate_image' => [$this, 'handleRateImage'],
            'get_images' => [$this, 'handleGetImages'],
            'delete_image' => [$this, 'handleDeleteImage'],
            'update_gallery' => [$this, 'handleUpdateGallery'],
            'get_client_access' => [$this, 'handleGetClientAccess'],
            'get_logs' => [$this, 'handleGetLogs'], // Für Admin-Interface
            'clear_logs' => [$this, 'handleClearLogs']
        ];
        
        LoggerRegistry::getLogger()?->debug('AJAX handlers registered', [
            'handlers' => array_keys($this->handlers)
        ]);
    }
    
    private function verifyNonce(string $action): bool 
    {
        $nonce = $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? '';
        return wp_verify_nonce($nonce, $action);
    }
    
    // Handler-Methoden
    public function handleGetGalleries(): array 
    {
        // Implementierung für Gallery-Abruf
        return ['galleries' => []];
    }
    
    public function handleUploadImage(): array 
    {
        $logger = LoggerRegistry::getLogger();
        
        if (!isset($_FILES['image'])) {
            throw new \InvalidArgumentException('No image file provided');
        }
        
        $file = $_FILES['image'];
        $galleryId = (int) ($_POST['gallery_id'] ?? 0);
        
        $logger?->info('Image upload started', [
            'gallery_id' => $galleryId,
            'filename' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ]);
        
        // Upload-Logik hier implementieren
        
        return ['image_id' => 123, 'message' => 'Image uploaded successfully'];
    }
    
    public function handleRateImage(): array 
    {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $rating = $_POST['rating'] ?? '';
        $clientId = (int) ($_POST['client_id'] ?? 0);
        
        LoggerRegistry::getLogger()?->info('Image rated', [
            'image_id' => $imageId,
            'rating' => $rating,
            'client_id' => $clientId
        ]);
        
        return ['success' => true];
    }
    
    public function handleGetImages(): array 
    {
        $galleryId = (int) ($_GET['gallery_id'] ?? 0);
        $clientId = (int) ($_GET['client_id'] ?? 0);
        
        // Performance-Log für häufige Requests
        LoggerRegistry::getLogger()?->debug('Images requested', [
            'gallery_id' => $galleryId,
            'client_id' => $clientId
        ]);
        
        return ['images' => []];
    }
    
    public function handleDeleteImage(): array 
    {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        
        LoggerRegistry::getLogger()?->warning('Image deletion requested', [
            'image_id' => $imageId,
            'user_id' => get_current_user_id()
        ]);
        
        return ['success' => true];
    }
    
    public function handleUpdateGallery(): array 
    {
        $galleryId = (int) ($_POST['gallery_id'] ?? 0);
        $data = $_POST['data'] ?? [];
        
        LoggerRegistry::getLogger()?->info('Gallery update requested', [
            'gallery_id' => $galleryId,
            'fields_updated' => array_keys($data)
        ]);
        
        return ['success' => true];
    }
    
    public function handleGetClientAccess(): array 
    {
        $accessKey = $_POST['access_key'] ?? '';
        
        LoggerRegistry::getLogger()?->info('Client access requested', [
            'access_key' => substr($accessKey, 0, 8) . '...',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        return ['client' => []];
    }
    
    public function handleGetLogs(): array 
    {
        // Nur für Admins
        if (!current_user_can('manage_options')) {
            throw new \Exception('Insufficient permissions');
        }
        
        $level = $_GET['level'] ?? '';
        $limit = min((int) ($_GET['limit'] ?? 50), 200); // Max 200 für Performance
        
        $logger = LoggerRegistry::getLogger();
        $logs = $logger?->getRecentLogs($limit) ?? [];
        
        return [
            'logs' => $logs,
            'total' => count($logs)
        ];
    }
    
    public function handleClearLogs(): array 
    {
        // Nur für Admins
        if (!current_user_can('manage_options')) {
            throw new \Exception('Insufficient permissions');
        }
        
        LoggerRegistry::getLogger()?->info('Log cleanup requested by admin', [
            'user_id' => get_current_user_id()
        ]);
        
        // Log-Cleanup implementieren
        
        return ['success' => true];
    }
}
