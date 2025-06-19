<?php
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php');

global $wpdb;

// Check current table structure
$table_name = $wpdb->prefix . 'ml_clientgallerie_galleries';

echo "=== Current table structure for $table_name ===\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");

if ($wpdb->last_error) {
    echo "Error: " . $wpdb->last_error . "\n";
    exit(1);
}

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

echo "\n=== Checking if slug column exists ===\n";
$slug_exists = false;
foreach ($columns as $column) {
    if ($column->Field === 'slug' || $column->Field === 'gallery_slug') {
        $slug_exists = true;
        echo "Found slug column: " . $column->Field . "\n";
        break;
    }
}

if (!$slug_exists) {
    echo "No slug column found. Need to add it.\n";
    
    // Add the slug column
    echo "\n=== Adding slug column ===\n";
    $alter_sql = "ALTER TABLE $table_name ADD COLUMN slug VARCHAR(255) NOT NULL UNIQUE AFTER name";
    
    $result = $wpdb->query($alter_sql);
    
    if ($wpdb->last_error) {
        echo "Error adding slug column: " . $wpdb->last_error . "\n";
    } else {
        echo "Successfully added slug column.\n";
        
        // Update existing records with slugs based on their names
        echo "\n=== Updating existing records with slugs ===\n";
        $galleries = $wpdb->get_results("SELECT id, name FROM $table_name WHERE slug = ''");
        
        foreach ($galleries as $gallery) {
            $slug = sanitize_title($gallery->name);
            $update_result = $wpdb->update(
                $table_name,
                ['slug' => $slug],
                ['id' => $gallery->id]
            );
            
            if ($update_result !== false) {
                echo "Updated gallery ID {$gallery->id} with slug: $slug\n";
            } else {
                echo "Error updating gallery ID {$gallery->id}: " . $wpdb->last_error . "\n";
            }
        }
    }
    
    echo "\n=== Updated table structure ===\n";
    $updated_columns = $wpdb->get_results("DESCRIBE $table_name");
    foreach ($updated_columns as $column) {
        echo sprintf("%-20s %-15s %-5s %-10s %-10s %s\n", 
            $column->Field, 
            $column->Type, 
            $column->Null, 
            $column->Key, 
            $column->Default, 
            $column->Extra
        );
    }
} else {
    echo "Slug column already exists.\n";
}

echo "\nDone.\n";
