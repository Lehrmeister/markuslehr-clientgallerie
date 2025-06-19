<?php
/**
 * Quick test for admin menu visibility
 */

// WordPress Bootstrap for admin context
define('WP_USE_THEMES', false);
require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php';

// Set admin context
define('WP_ADMIN', true);
$_SERVER['REQUEST_URI'] = '/wp-admin/';

// Load admin
require_once ABSPATH . 'wp-admin/admin.php';

echo "=== Testing Admin Menu Registration ===\n\n";

// Create an admin user context
wp_set_current_user(1); // Assume user ID 1 is admin

// Check if we can see the menu
if (current_user_can('manage_options')) {
    echo "✓ User has manage_options capability\n";
} else {
    echo "✗ User does not have manage_options capability\n";
}

// Force admin_menu action
do_action('admin_menu');

global $menu, $submenu;

echo "\nLooking for Client Gallery menu...\n";

if (is_array($menu)) {
    foreach ($menu as $position => $item) {
        if (isset($item[0]) && (stripos($item[0], 'Client') !== false || stripos($item[0], 'Gallery') !== false)) {
            echo "Found menu: {$item[0]} (position: {$position})\n";
            echo "Slug: {$item[2]}\n";
            echo "Icon: {$item[6]}\n";
        }
    }
} else {
    echo "Menu array is not available\n";
}

echo "\nDone.\n";
