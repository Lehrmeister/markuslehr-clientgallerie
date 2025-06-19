<?php
/**
 * Test Admin Integration
 * 
 * Quick test to verify admin page works
 */

// WordPress Bootstrap
require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php';
require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/vendor/autoload.php';

echo "=== Testing Admin Integration ===\n\n";

// Test 1: Service Container
echo "1. Testing Service Container...\n";
try {
    $container = new \MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer();
    $container->register();
    echo "   ✓ Service Container created\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Admin Page Creation 
echo "2. Testing Admin Page Creation...\n";
try {
    $adminPage = new \MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage();
    echo "   ✓ Admin Page created successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Admin Controller
echo "3. Testing Admin Controller...\n";
try {
    $controller = $container->get(\MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController::class);
    echo "   ✓ Admin Controller: " . get_class($controller) . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ADMIN INTEGRATION TEST PASSED ===\n";
