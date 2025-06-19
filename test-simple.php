<?php
// Quick test to check if adminMenu method exists
require_once('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php');

echo "=== Quick Admin Menu Check ===\n";

// Check if plugin is active
if (is_plugin_active('markuslehr_clientgallerie/clientgallerie.php')) {
    echo "✅ Plugin is active\n";
    
    // Get plugin instance
    $plugin = MarkusLehrClientGallerie::getInstance();
    
    // Check if method exists
    if (method_exists($plugin, 'adminMenu')) {
        echo "✅ adminMenu method exists\n";
        
        // Get method reflection
        $reflection = new ReflectionMethod($plugin, 'adminMenu');
        echo "Method is " . ($reflection->isPublic() ? 'public' : 'not public') . "\n";
        
        // Try to call it
        try {
            ob_start();
            $plugin->adminMenu();
            $output = ob_get_clean();
            echo "✅ adminMenu() executed without error\n";
            if (!empty($output)) {
                echo "Output: " . $output . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Error calling adminMenu(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ adminMenu method does not exist\n";
        
        // List available methods
        $methods = get_class_methods($plugin);
        echo "Available methods:\n";
        foreach ($methods as $method) {
            echo "  - " . $method . "\n";
        }
    }
} else {
    echo "❌ Plugin is not active\n";
}

echo "\n=== Test Complete ===\n";
