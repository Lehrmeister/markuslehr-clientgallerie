<?php
/**
 * Plugin Name: MarkusLehr ClientGallerie
 * Plugin URI: https://markus-lehr.de/plugins/clientgallerie
 * Description: Professional WordPress Gallery Plugin - Clean Base Infrastructure
 * Version: 1.0.0
 * Author: Markus Lehr
 * Author URI: https://markus-lehr.de/
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

/**
 * Main Plugin Class - Clean Base Infrastructure Only
 */
class MarkusLehrClientGallerie 
{
    private static ?self $instance = null;
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() 
    {
        $this->initializeHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initializeHooks(): void 
    {
        try {
            // Plugin lifecycle hooks
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);
            
            // Admin controller for backend management
            if (is_admin()) {
                $adminController = new \MarkusLehr\ClientGallerie\Application\Controller\AdminController();
                $adminController->initialize();
            }
            
        } catch (\Exception $e) {
            error_log('MLCG Failed to initialize hooks: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate(): void 
    {
        try {
            // Create basic upload directories
            $upload_dir = wp_upload_dir();
            $plugin_upload_dir = $upload_dir['basedir'] . '/client-galleries';
            
            if (!is_dir($plugin_upload_dir)) {
                wp_mkdir_p($plugin_upload_dir);
            }
            
        } catch (\Exception $e) {
            error_log('MLCG Plugin activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate(): void 
    {
        try {
            // Cleanup if needed
        } catch (\Exception $e) {
            error_log('MLCG deactivation error: ' . $e->getMessage());
        }
    }
}

// Initialize the plugin
MarkusLehrClientGallerie::getInstance();
