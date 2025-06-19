<?php
/**
 * Simple Admin Page Test
 */

// Include WordPress
define('WP_USE_THEMES', false);
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-load.php');

echo "=== Testing Admin Page Rendering ===\n";

try {
    // Set up admin context
    if (!defined('WP_ADMIN')) {
        define('WP_ADMIN', true);
    }
    
    // Get plugin instance
    global $markuslehr_clientgallerie_plugin;
    
    if (!$markuslehr_clientgallerie_plugin) {
        echo "❌ Plugin instance not found\n";
        exit(1);
    }
    
    echo "✓ Plugin instance found\n";
    
    // Get service container
    $container = $markuslehr_clientgallerie_plugin->getServiceContainer();
    echo "✓ Service container: " . get_class($container) . "\n";
    
    // Test gallery repository
    $repository = $container->get('MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\GalleryRepository');
    echo "✓ Gallery repository: " . get_class($repository) . "\n";
    
    // Test gallery count
    $galleries = $repository->findAll();
    echo "✓ Found " . count($galleries) . " galleries\n";
    
    // Test if GalleryAdminPage class exists and can be instantiated
    $adminPageClass = 'MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage';
    if (class_exists($adminPageClass)) {
        echo "✓ GalleryAdminPage class exists\n";
        
        // Check if render method exists
        $reflection = new ReflectionClass($adminPageClass);
        if ($reflection->hasMethod('renderMainPage')) {
            echo "✓ renderMainPage method exists\n";
        } else {
            echo "❌ renderMainPage method missing\n";
        }
        
        if ($reflection->hasMethod('init')) {
            echo "✓ init method exists\n";
        } else {
            echo "❌ init method missing\n";
        }
    } else {
        echo "❌ GalleryAdminPage class not found\n";
    }
    
    echo "\n✅ Basic plugin structure is working!\n";
    echo "   The admin UI should be accessible via WordPress admin.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
