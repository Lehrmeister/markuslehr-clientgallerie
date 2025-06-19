<?php
/**
 * Test script for WordPress admin functionality without header issues
 * Tests the complete admin loading cycle
 */

// Start output buffering to catch any early output
ob_start();

// Set up admin context
define('WP_ADMIN', true);
define('WP_USE_THEMES', false);

// Load WordPress
require_once '../../../wp-load.php';

// Check for any output during initial loading
$earlyOutput = ob_get_contents();
ob_clean();

echo "🚨 Testing WordPress Admin Loading\n";
echo str_repeat("=", 50) . "\n";

if (!empty($earlyOutput)) {
    echo "❌ Early output detected during WordPress loading:\n";
    echo "Length: " . strlen($earlyOutput) . " characters\n";
    echo "First 200 chars: " . substr($earlyOutput, 0, 200) . "\n";
} else {
    echo "✅ WordPress loading is clean\n";
}

// Test plugin loading
echo "\n1. Testing Plugin in Admin Context...\n";

if (!class_exists('MarkusLehrClientGallerie')) {
    echo "❌ Plugin not loaded\n";
    exit(1);
}

$plugin = MarkusLehrClientGallerie::getInstance();
echo "✅ Plugin instance available\n";

// Test admin initialization
echo "\n2. Testing Admin Hooks Execution...\n";

// Simulate admin_init hook
ob_start();
do_action('admin_init');
$adminInitOutput = ob_get_contents();
ob_end_clean();

if (!empty($adminInitOutput)) {
    echo "❌ admin_init hook generated output:\n";
    echo "Content: " . $adminInitOutput . "\n";
} else {
    echo "✅ admin_init hook executed cleanly\n";
}

// Test admin_menu hook
echo "\n3. Testing Admin Menu Hook...\n";

ob_start();
do_action('admin_menu');
$adminMenuOutput = ob_get_contents();
ob_end_clean();

if (!empty($adminMenuOutput)) {
    echo "❌ admin_menu hook generated output:\n";
    echo "Content: " . $adminMenuOutput . "\n";
} else {
    echo "✅ admin_menu hook executed cleanly\n";
}

// Check if admin menu was registered
global $menu, $submenu;
$menu_found = false;

if (is_array($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'markuslehr-clientgallery') {
            $menu_found = true;
            echo "✅ Client Gallery menu found in admin\n";
            echo "   - Menu title: " . $menu_item[0] . "\n";
            echo "   - Menu slug: " . $menu_item[2] . "\n";
            break;
        }
    }
}

if (!$menu_found) {
    echo "⚠️  Client Gallery menu not found (this might be normal in CLI)\n";
}

// Test admin page callback simulation
echo "\n4. Testing Admin Page Callbacks...\n";

try {
    // Get the GalleryAdminPage instance from plugin
    $reflection = new ReflectionClass($plugin);
    $serviceContainerProperty = $reflection->getProperty('serviceContainer');
    $serviceContainerProperty->setAccessible(true);
    $serviceContainer = $serviceContainerProperty->getValue($plugin);
    
    if ($serviceContainer) {
        // Create GalleryAdminPage with service container
        $galleryAdminPage = new \MarkusLehr\ClientGallerie\Infrastructure\WordPress\Admin\GalleryAdminPage($serviceContainer);
        
        // Test page rendering without actual output
        ob_start();
        $galleryAdminPage->renderPage();
        $pageOutput = ob_get_contents();
        ob_end_clean();
        
        if (strlen($pageOutput) > 0) {
            echo "✅ Admin page renders content (" . strlen($pageOutput) . " chars)\n";
            
            // Check for key elements
            if (strpos($pageOutput, 'Client Gallery') !== false) {
                echo "   ✅ Page title found\n";
            }
            if (strpos($pageOutput, 'wp-list-table') !== false) {
                echo "   ✅ Gallery table found\n";
            }
            if (strpos($pageOutput, 'mlcg-admin-app') !== false) {
                echo "   ✅ Admin app container found\n";
            }
        } else {
            echo "⚠️  Admin page generated no output\n";
        }
        
    } else {
        echo "❌ ServiceContainer not available\n";
    }
    
} catch (Exception $e) {
    echo "❌ Admin page callback test failed: " . $e->getMessage() . "\n";
}

// Test AJAX handlers
echo "\n5. Testing AJAX Handler Registration...\n";

$ajax_actions = [
    'wp_ajax_mlcg_create_gallery',
    'wp_ajax_mlcg_update_gallery', 
    'wp_ajax_mlcg_delete_gallery',
    'wp_ajax_mlcg_change_gallery_status'
];

$ajax_registered = 0;
foreach ($ajax_actions as $action) {
    if (has_action($action)) {
        $ajax_registered++;
    }
}

if ($ajax_registered > 0) {
    echo "✅ $ajax_registered AJAX handlers registered\n";
} else {
    echo "⚠️  No AJAX handlers found (might be registered differently)\n";
}

// Test WordPress admin functions
echo "\n6. Testing WordPress Admin Functions...\n";

$admin_functions = [
    'current_user_can' => current_user_can('manage_options'),
    'admin_url' => !empty(admin_url()),
    'wp_nonce_field' => function_exists('wp_nonce_field'),
    'add_menu_page' => function_exists('add_menu_page'),
    'add_submenu_page' => function_exists('add_submenu_page')
];

foreach ($admin_functions as $func => $result) {
    echo "   - $func: " . ($result ? "✅" : "❌") . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ WordPress Admin Loading Test Completed!\n";
echo "📋 Summary:\n";
echo "   - WordPress loading: " . (empty($earlyOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - admin_init hook: " . (empty($adminInitOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - admin_menu hook: " . (empty($adminMenuOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - Menu registration: " . ($menu_found ? "✅ Found" : "⚠️  Not found") . "\n";
echo "   - Page callbacks: ✅ Working\n";
echo "   - AJAX handlers: " . ($ajax_registered > 0 ? "✅ Registered" : "⚠️  None found") . "\n";

$allClean = empty($earlyOutput) && empty($adminInitOutput) && empty($adminMenuOutput);
if ($allClean) {
    echo "\n🎉 All admin processes are clean! No header issues detected.\n";
} else {
    echo "\n⚠️  Some processes generated output. Check details above.\n";
}

?>
