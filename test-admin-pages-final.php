<?php
/**
 * Test admin page rendering
 */

require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php');

echo "=== Admin Page Rendering Test ===\n";

// Set basic admin context
wp_set_current_user(1);
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

try {
    // Get plugin instance and trigger admin menu registration
    $plugin = MarkusLehrClientGallerie::getInstance();
    
    // Test ServiceContainer access
    $reflection = new ReflectionClass($plugin);
    $serviceContainerProperty = $reflection->getProperty('serviceContainer');
    $serviceContainerProperty->setAccessible(true);
    $serviceContainer = $serviceContainerProperty->getValue($plugin);
    
    if ($serviceContainer) {
        echo "✅ ServiceContainer is available\n";
        
        // Test GalleryAdminPage instantiation
        $galleryAdminPage = new \MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage($serviceContainer);
        echo "✅ GalleryAdminPage instantiated successfully\n";
        
        // Test that the methods exist
        if (method_exists($galleryAdminPage, 'renderPage')) {
            echo "✅ renderPage method exists\n";
        } else {
            echo "❌ renderPage method missing\n";
        }
        
        if (method_exists($galleryAdminPage, 'renderNewGalleryPage')) {
            echo "✅ renderNewGalleryPage method exists\n";
        } else {
            echo "❌ renderNewGalleryPage method missing\n";
        }
        
        if (method_exists($galleryAdminPage, 'renderClientsPage')) {
            echo "✅ renderClientsPage method exists\n";
        } else {
            echo "❌ renderClientsPage method missing\n";
        }
        
        // Test that we can call addAdminMenu without errors
        ob_start();
        $galleryAdminPage->addAdminMenu();
        $output = ob_get_clean();
        echo "✅ addAdminMenu executed without error\n";
        
    } else {
        echo "❌ ServiceContainer is not available\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Error in: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
