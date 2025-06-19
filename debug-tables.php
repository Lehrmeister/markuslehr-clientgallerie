<?php
/**
 * Debug table creation script
 */

// Change to WordPress directory
chdir('/Applications/XAMPP/xamppfiles/htdocs/wordpress');

// Include WordPress
require_once('wp-config.php');

// Include plugin autoloader
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/vendor/autoload.php');

echo "Debug table creation...\n";

try {
    global $wpdb;
    echo "WordPress table prefix: " . $wpdb->prefix . "\n";
    
    // Test ClientSchema
    echo "\n=== Testing ClientSchema ===\n";
    $clientSchema = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\ClientSchema();
    
    // Use reflection to access protected properties
    $reflection = new ReflectionClass($clientSchema);
    $tableNameProperty = $reflection->getProperty('tableName');
    $tableNameProperty->setAccessible(true);
    $tableName = $tableNameProperty->getValue($clientSchema);
    
    echo "Expected table name: " . $tableName . "\n";
    
    // Try to create table
    echo "Creating table...\n";
    $result = $clientSchema->create();
    echo "Create result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($wpdb->last_error) {
        echo "Database error: " . $wpdb->last_error . "\n";
    }
    
    // Check if table exists
    $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'");
    echo "Table exists check: " . ($tableExists ? 'YES' : 'NO') . "\n";
    
    echo "\n=== Testing LogEntrySchema ===\n";
    $logSchema = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\LogEntrySchema();
    
    $tableNameProperty = $reflection->getProperty('tableName');
    $tableNameProperty->setAccessible(true);
    $logTableName = $tableNameProperty->getValue($logSchema);
    
    echo "Expected table name: " . $logTableName . "\n";
    
    // Try to create table
    echo "Creating table...\n";
    $result = $logSchema->create();
    echo "Create result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($wpdb->last_error) {
        echo "Database error: " . $wpdb->last_error . "\n";
    }
    
    // Check if table exists
    $logTableExists = $wpdb->get_var("SHOW TABLES LIKE '$logTableName'");
    echo "Table exists check: " . ($logTableExists ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
