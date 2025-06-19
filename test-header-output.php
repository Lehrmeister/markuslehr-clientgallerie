<?php
/**
 * Test script for header output issues
 * Tests if any output is generated before headers
 */

// Start output buffering to catch any unwanted output
ob_start();

// WordPress environment
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Get any output that was generated during WordPress loading
$preLoadOutput = ob_get_contents();
ob_clean();

echo "🚨 Testing Header Output Issues\n";
echo str_repeat("=", 50) . "\n";

if (!empty($preLoadOutput)) {
    echo "❌ Pre-load output detected:\n";
    echo "Length: " . strlen($preLoadOutput) . " characters\n";
    echo "Content (first 200 chars): " . substr($preLoadOutput, 0, 200) . "\n";
    echo str_repeat("-", 30) . "\n";
} else {
    echo "✅ No pre-load output detected\n";
}

// Test plugin loading
echo "\n1. Testing Plugin Loading...\n";

if (!class_exists('MarkusLehrClientGallerie')) {
    echo "❌ Plugin not loaded\n";
    exit(1);
}

echo "✅ Plugin class exists\n";

// Test plugin instance creation
ob_start();
$plugin = MarkusLehrClientGallerie::getInstance();
$instanceOutput = ob_get_contents();
ob_end_clean();

if (!empty($instanceOutput)) {
    echo "❌ Plugin instance creation generated output:\n";
    echo "Content: " . $instanceOutput . "\n";
} else {
    echo "✅ Plugin instance creation is clean\n";
}

// Test logger initialization
echo "\n2. Testing Logger Initialization...\n";
ob_start();
$logger = $plugin->getLogger();
$loggerOutput = ob_get_contents();
ob_end_clean();

if (!empty($loggerOutput)) {
    echo "❌ Logger initialization generated output:\n";
    echo "Content: " . $loggerOutput . "\n";
} else {
    echo "✅ Logger initialization is clean\n";
}

// Test dependency loading
echo "\n3. Testing Dependency Loading...\n";
ob_start();
$plugin->loadDependencies();
$depOutput = ob_get_contents();
ob_end_clean();

if (!empty($depOutput)) {
    echo "❌ Dependency loading generated output:\n";
    echo "Content: " . $depOutput . "\n";
} else {
    echo "✅ Dependency loading is clean\n";
}

// Test admin initialization
echo "\n4. Testing Admin Initialization...\n";
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

ob_start();
$plugin->adminInit();
$adminOutput = ob_get_contents();
ob_end_clean();

if (!empty($adminOutput)) {
    echo "❌ Admin initialization generated output:\n";
    echo "Content: " . $adminOutput . "\n";
} else {
    echo "✅ Admin initialization is clean\n";
}

// Test CQRS operations
echo "\n5. Testing CQRS Operations...\n";
ob_start();
try {
    $reflection = new ReflectionClass($plugin);
    $serviceContainerProperty = $reflection->getProperty('serviceContainer');
    $serviceContainerProperty->setAccessible(true);
    $serviceContainer = $serviceContainerProperty->getValue($plugin);
    
    if ($serviceContainer) {
        $queryBus = $serviceContainer->get(\MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface::class);
        $listQuery = new \MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery();
        $galleries = $queryBus->execute($listQuery);
        echo "Query executed successfully, found " . count($galleries) . " galleries\n";
    }
} catch (Exception $e) {
    echo "CQRS test failed: " . $e->getMessage() . "\n";
}
$cqrsOutput = ob_get_contents();
ob_end_clean();

if (!empty($cqrsOutput)) {
    echo "❌ CQRS operations generated output:\n";
    echo "Content: " . $cqrsOutput . "\n";
} else {
    echo "✅ CQRS operations are clean\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Header Output Test Completed!\n";
echo "📋 Summary:\n";
echo "   - Pre-load output: " . (empty($preLoadOutput) ? "✅ Clean" : "❌ Detected") . "\n";
echo "   - Plugin instance: " . (empty($instanceOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - Logger init: " . (empty($loggerOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - Dependencies: " . (empty($depOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - Admin init: " . (empty($adminOutput) ? "✅ Clean" : "❌ Output") . "\n";
echo "   - CQRS ops: " . (empty($cqrsOutput) ? "✅ Clean" : "❌ Output") . "\n";

$allClean = empty($preLoadOutput) && empty($instanceOutput) && empty($loggerOutput) && 
           empty($depOutput) && empty($adminOutput) && empty($cqrsOutput);

if ($allClean) {
    echo "\n🎉 All operations are clean! No header output issues detected.\n";
} else {
    echo "\n⚠️  Some operations generated output. Check details above.\n";
}

?>
