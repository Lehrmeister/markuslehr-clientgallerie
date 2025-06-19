<?php
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php');

echo "=== Activating MarkusLehr ClientGallerie Plugin ===\n";

// Manually trigger plugin activation
$plugin_file = 'markuslehr_clientgallerie/clientgallerie.php';

// Check if plugin is already active
if (is_plugin_active($plugin_file)) {
    echo "Plugin is already active. Deactivating first...\n";
    deactivate_plugins($plugin_file);
}

echo "Activating plugin...\n";
$result = activate_plugin($plugin_file);

if (is_wp_error($result)) {
    echo "Error activating plugin: " . $result->get_error_message() . "\n";
    exit(1);
} else {
    echo "Plugin activated successfully!\n";
}

// Now check if the table was created
global $wpdb;
$table_name = $wpdb->prefix . 'ml_clientgallerie_galleries';

echo "\n=== Checking table structure ===\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");

if ($wpdb->last_error) {
    echo "Error: " . $wpdb->last_error . "\n";
} else {
    echo "Table structure for $table_name:\n";
    foreach ($columns as $column) {
        echo sprintf("%-20s %-15s %-5s %-10s %-10s %s\n", 
            $column->Field, 
            $column->Type, 
            $column->Null, 
            $column->Key, 
            $column->Default, 
            $column->Extra
        );
    }
    
    echo "\n=== Slug column found! ===\n";
    foreach ($columns as $column) {
        if ($column->Field === 'slug') {
            echo "Slug column: {$column->Field} ({$column->Type}) - {$column->Key}\n";
            break;
        }
    }
}

echo "\nDone.\n";
