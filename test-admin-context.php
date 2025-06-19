<?php
/**
 * Test Admin Access with proper WordPress context
 */

// Set up proper WordPress admin context
define('WP_USE_THEMES', false);
define('WP_ADMIN', true);
define('WP_NETWORK_ADMIN', false);
define('WP_USER_ADMIN', false);

// Load WordPress
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');

echo "=== Testing WordPress Admin Context ===\n";

// Set up admin user
wp_set_current_user(1);

if (!current_user_can('manage_options')) {
    echo "❌ User doesn't have admin permissions\n";
    exit(1);
}

echo "✓ Admin user authenticated\n";

// Check if our plugin created admin menus
global $menu, $submenu;

echo "Available admin menu items:\n";
foreach ($menu as $item) {
    if (is_array($item) && !empty($item[0])) {
        echo "  - " . strip_tags($item[0]) . " (slug: " . ($item[2] ?? 'none') . ")\n";
    }
}

// Check if our plugin page exists
$our_page_found = false;
foreach ($menu as $item) {
    if (is_array($item) && isset($item[2]) && $item[2] === 'markuslehr-clientgallerie') {
        $our_page_found = true;
        break;
    }
}

if ($our_page_found) {
    echo "✓ Found our admin menu item\n";
} else {
    echo "❌ Our admin menu item not found\n";
}

// Test if we can get the plugin global variable
global $markuslehr_clientgallerie_plugin;
if ($markuslehr_clientgallerie_plugin) {
    echo "✓ Plugin global variable found\n";
} else {
    echo "❌ Plugin global variable not found\n";
    
    // Try to find plugin by checking loaded plugins
    $active_plugins = get_option('active_plugins', []);
    echo "Active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "  - $plugin\n";
    }
}

echo "\n=== Test completed ===\n";
