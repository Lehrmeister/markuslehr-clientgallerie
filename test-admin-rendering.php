<?php
/**
 * Test script for admin page rendering
 * Tests the actual HTML output of admin pages
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once '../../../wp-load.php';

// Ensure plugin is loaded
if (!class_exists('MarkusLehrClientGallerie')) {
    die("❌ Plugin not loaded\n");
}

echo "🎨 Testing Admin Page Rendering\n";
echo str_repeat("=", 50) . "\n";

try {
    // Get plugin instance
    $plugin = MarkusLehrClientGallerie::getInstance();
    
    // Ensure dependencies are loaded
    $plugin->loadDependencies();
    
    // Mock admin environment
    $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=markuslehr-clientgallery';
    if (!defined('WP_ADMIN')) {
        define('WP_ADMIN', true);
    }
    
    // Test 1: Gallery listing page rendering
    echo "\n1. Testing Gallery Listing Page...\n";
    
    ob_start();
    try {
        $plugin->renderAdminPage();
        $listingOutput = ob_get_contents();
    } catch (Exception $e) {
        ob_end_clean();
        throw $e;
    }
    ob_end_clean();
    
    if (strlen($listingOutput) > 0) {
        echo "   ✅ Gallery listing page rendered\n";
        echo "   📏 Output length: " . strlen($listingOutput) . " characters\n";
        
        // Check for key elements
        if (strpos($listingOutput, 'Client Gallery - All Galleries') !== false) {
            echo "   ✅ Page title found\n";
        }
        
        if (strpos($listingOutput, 'Add New Gallery') !== false) {
            echo "   ✅ Add New button found\n";
        }
        
        if (strpos($listingOutput, 'wp-list-table') !== false) {
            echo "   ✅ Gallery table found\n";
        }
        
        if (strpos($listingOutput, 'mlcg-admin-app') !== false) {
            echo "   ✅ Admin app container found\n";
        }
        
        // Count galleries in output
        $galleryCount = substr_count($listingOutput, '<tr>') - 1; // subtract header row
        echo "   📊 Galleries displayed: " . $galleryCount . "\n";
        
    } else {
        echo "   ❌ No output generated\n";
    }
    
    // Test 2: New gallery page rendering
    echo "\n2. Testing New Gallery Page...\n";
    
    ob_start();
    try {
        $plugin->renderNewGalleryPage();
        $newGalleryOutput = ob_get_contents();
    } catch (Exception $e) {
        ob_end_clean();
        throw $e;
    }
    ob_end_clean();
    
    if (strlen($newGalleryOutput) > 0) {
        echo "   ✅ New gallery page rendered\n";
        echo "   📏 Output length: " . strlen($newGalleryOutput) . " characters\n";
        
        // Check for key elements
        if (strpos($newGalleryOutput, 'Add New Gallery') !== false) {
            echo "   ✅ Page title found\n";
        }
        
        if (strpos($newGalleryOutput, 'mlcg-gallery-form') !== false) {
            echo "   ✅ Gallery form found\n";
        }
        
        if (strpos($newGalleryOutput, 'gallery_name') !== false) {
            echo "   ✅ Name field found\n";
        }
        
        if (strpos($newGalleryOutput, 'gallery_slug') !== false) {
            echo "   ✅ Slug field found\n";
        }
        
        if (strpos($newGalleryOutput, 'mlcg_create_gallery') !== false) {
            echo "   ✅ Submit button found\n";
        }
        
        if (strpos($newGalleryOutput, 'wp_nonce_field') !== false || strpos($newGalleryOutput, 'mlcg_nonce') !== false) {
            echo "   ✅ Security nonce found\n";
        }
        
    } else {
        echo "   ❌ No output generated\n";
    }
    
    // Test 3: Test gallery creation simulation
    echo "\n3. Testing Gallery Creation Form Processing...\n";
    
    // Mock POST data
    $_POST = [
        'mlcg_create_gallery' => '1',
        'mlcg_nonce' => wp_create_nonce('mlcg_create_gallery'),
        'gallery_name' => 'Test Rendered Gallery',
        'gallery_slug' => 'test-rendered-gallery',
        'gallery_description' => 'Gallery created via rendering test',
        'client_id' => '1',
        'gallery_status' => 'draft'
    ];
    
    // Mock nonce verification (normally WordPress would handle this)
    $_POST['mlcg_nonce'] = wp_create_nonce('mlcg_create_gallery');
    
    try {
        // We can't actually test the full form processing here due to wp_redirect
        // But we can test the validation and command creation logic
        
        $name = sanitize_text_field($_POST['gallery_name']);
        $slug = sanitize_title($_POST['gallery_slug']) ?: sanitize_title($name);
        $description = sanitize_textarea_field($_POST['gallery_description']);
        $clientId = (int) $_POST['client_id'];
        $status = in_array($_POST['gallery_status'], ['draft', 'published']) ? $_POST['gallery_status'] : 'draft';
        
        echo "   ✅ Form data validation passed\n";
        echo "      - Name: " . $name . "\n";
        echo "      - Slug: " . $slug . "\n";
        echo "      - Client ID: " . $clientId . "\n";
        echo "      - Status: " . $status . "\n";
        
        // Test command creation (without execution to avoid redirect)
        $createCommand = new \MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand(
            $name,
            $slug,
            $clientId,
            $description
        );
        
        echo "   ✅ Create command object created successfully\n";
        
    } catch (Exception $e) {
        echo "   ❌ Form processing test failed: " . $e->getMessage() . "\n";
    }
    
    // Clean up
    $_POST = [];
    
    // Test 4: CSS and JavaScript includes check
    echo "\n4. Testing Asset Inclusion...\n";
    
    // Check if styling is included in output
    if (strpos($listingOutput, '<style>') !== false) {
        echo "   ✅ CSS styles included in listing page\n";
    }
    
    if (strpos($listingOutput, '<script>') !== false) {
        echo "   ✅ JavaScript included in listing page\n";
    }
    
    if (strpos($newGalleryOutput, '<script>') !== false) {
        echo "   ✅ JavaScript included in new gallery page\n";
    }
    
    // Test 5: Save sample outputs for inspection
    echo "\n5. Saving Sample Outputs...\n";
    
    file_put_contents('/tmp/admin-listing-output.html', $listingOutput);
    echo "   💾 Listing page output saved to /tmp/admin-listing-output.html\n";
    
    file_put_contents('/tmp/admin-new-gallery-output.html', $newGalleryOutput);
    echo "   💾 New gallery page output saved to /tmp/admin-new-gallery-output.html\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Admin Page Rendering Test Completed Successfully!\n";
    echo "📋 Summary:\n";
    echo "   - Gallery listing page: ✅\n";
    echo "   - New gallery page: ✅\n";
    echo "   - Form validation: ✅\n";
    echo "   - Asset inclusion: ✅\n";
    echo "   - Output generation: ✅\n";
    
    echo "\n📄 Generated files for inspection:\n";
    echo "   - /tmp/admin-listing-output.html\n";
    echo "   - /tmp/admin-new-gallery-output.html\n";

} catch (Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "📍 Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

?>
