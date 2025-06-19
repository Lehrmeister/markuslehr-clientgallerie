<?php
/**
 * Test Admin UI Functionality
 */

// WordPress Bootstrap
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php');

echo "=== Testing Admin UI Functionality ===\n";

try {
    // Test if we can access the admin page directly
    $url = 'http://localhost/wordpress/wp-admin/admin.php?page=markuslehr-clientgallerie';
    $context = stream_context_create([
        'http' => [
            'timeout' => 30
        ]
    ]);
    
    $content = file_get_contents($url, false, $context);
    
    if ($content === false) {
        echo "❌ Cannot access admin page\n";
        exit(1);
    }
    
    // Check for specific elements
    $hasGalleryList = strpos($content, 'mlcg-galleries-list') !== false;
    $hasEditButtons = strpos($content, 'edit-gallery') !== false;
    $hasPublishButtons = strpos($content, 'publish-gallery') !== false;
    $hasDeleteButtons = strpos($content, 'delete-gallery') !== false;
    $hasJavaScript = strpos($content, 'mlcg_toggle_gallery_status') !== false;
    
    echo "✓ Admin page accessible\n";
    echo "Gallery List: " . ($hasGalleryList ? "✓" : "❌") . "\n";
    echo "Edit Buttons: " . ($hasEditButtons ? "✓" : "❌") . "\n";
    echo "Publish Buttons: " . ($hasPublishButtons ? "✓" : "❌") . "\n";
    echo "Delete Buttons: " . ($hasDeleteButtons ? "✓" : "❌") . "\n";
    echo "JavaScript: " . ($hasJavaScript ? "✓" : "❌") . "\n";
    
    if ($hasGalleryList && $hasEditButtons && $hasDeleteButtons && $hasJavaScript) {
        echo "\n✅ Admin UI functionality implemented successfully!\n";
    } else {
        echo "\n⚠️  Some admin UI features may be missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing admin UI: " . $e->getMessage() . "\n";
    exit(1);
}
