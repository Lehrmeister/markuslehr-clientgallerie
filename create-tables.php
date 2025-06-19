<?php
/**
 * Manual table creation script
 */

// Change to WordPress directory
chdir('/Applications/XAMPP/xamppfiles/htdocs/wordpress');

// Include WordPress
require_once('wp-config.php');

// Include plugin autoloader
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/vendor/autoload.php');

echo "Creating database tables manually...\n";

try {
    // Create SchemaManager and install tables
    $schemaManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\SchemaManager();
    
    echo "Installing tables...\n";
    $result = $schemaManager->installAll();
    
    if ($result) {
        echo "✓ Tables created successfully!\n";
    } else {
        echo "✗ Failed to create tables\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
