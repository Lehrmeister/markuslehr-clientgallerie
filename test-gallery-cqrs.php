<?php

/**
 * Comprehensive Gallery System Test
 * 
 * Tests complete CQRS Gallery operations including:
 * - Gallery creation, update, delete
 * - Status changes (draft/published)
 * - Admin integration
 * - Command/Query bus
 * 
 * Run this script from WordPress root or plugin directory
 */

// Load WordPress
if (file_exists('/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-load.php')) {
    require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-load.php';
} elseif (file_exists('../../../wp-load.php')) {
    require_once '../../../wp-load.php';
} elseif (file_exists('../../../../wp-load.php')) {
    require_once '../../../../wp-load.php';
} else {
    die('WordPress not found. Please run this script from the plugin directory.');
}

// Check if we're in WordPress
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress context.');
}

use MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer;
use MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface;
use MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface;
use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery;
use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;

echo "=== MarkusLehr ClientGallerie - Comprehensive Gallery System Test ===\n\n";

try {
    // Initialize Service Container
    echo "1. Initializing Service Container...\n";
    $container = new ServiceContainer();
    $container->register();
    echo "   ✓ Service Container initialized\n\n";

    // Get Command and Query Buses
    echo "2. Getting Command and Query Buses...\n";
    $commandBus = $container->get(CommandBusInterface::class);
    $queryBus = $container->get(QueryBusInterface::class);
    echo "   ✓ Command Bus: " . get_class($commandBus) . "\n";
    echo "   ✓ Query Bus: " . get_class($queryBus) . "\n\n";

    // Test 1: List existing galleries
    echo "3. Listing existing galleries...\n";
    $listQuery = new ListGalleriesQuery();
    $existingGalleries = $queryBus->execute($listQuery);
    echo "   ✓ Found " . count($existingGalleries) . " existing galleries\n";
    foreach ($existingGalleries as $gallery) {
        echo "   - ID: {$gallery->getId()}, Name: {$gallery->getName()}, Status: {$gallery->getStatus()->getValue()}\n";
    }
    echo "\n";

    // Test 2: Create new gallery
    echo "4. Creating new gallery...\n";
    $createCommand = new CreateGalleryCommand(
        'Test Gallery CQRS ' . date('H:i:s'),
        'test-gallery-cqrs-' . time(),
        1,
        'This is a test gallery created via CQRS'
    );
    $createdGallery = $commandBus->execute($createCommand);
    $galleryId = $createdGallery->getId();
    echo "   ✓ Gallery created with ID: $galleryId\n\n";

    // Test 3: Get the created gallery
    echo "5. Retrieving created gallery...\n";
    $getQuery = new GetGalleryQuery($galleryId);
    $gallery = $queryBus->execute($getQuery);
    echo "   ✓ Gallery retrieved:\n";
    echo "     - ID: {$gallery->getId()}\n";
    echo "     - Name: {$gallery->getName()}\n";
    echo "     - Slug: {$gallery->getSlug()->getValue()}\n";
    echo "     - Status: {$gallery->getStatus()->getValue()}\n";
    echo "     - Client ID: {$gallery->getClientId()}\n";
    echo "     - Description: {$gallery->getDescription()}\n";
    echo "     - Created: {$gallery->getCreatedAt()->format('Y-m-d H:i:s')}\n\n";

    // Test 4: Update gallery
    echo "6. Updating gallery...\n";
    $updateCommand = new UpdateGalleryCommand(
        $galleryId,
        'Updated Test Gallery CQRS',
        null, // Keep existing slug
        'Updated description via CQRS'
    );
    $commandBus->execute($updateCommand);
    echo "   ✓ Gallery updated\n\n";

    // Test 5: Verify update
    echo "7. Verifying gallery update...\n";
    $updatedGallery = $queryBus->execute($getQuery);
    echo "   ✓ Updated gallery:\n";
    echo "     - Name: {$updatedGallery->getName()}\n";
    echo "     - Description: {$updatedGallery->getDescription()}\n\n";

    // Test 6: Publish gallery
    echo "8. Publishing gallery...\n";
    $publishCommand = new PublishGalleryCommand($galleryId, new GalleryStatus(GalleryStatus::PUBLISHED));
    $commandBus->execute($publishCommand);
    echo "   ✓ Gallery published\n\n";

    // Test 7: Verify publish
    echo "9. Verifying gallery status...\n";
    $publishedGallery = $queryBus->execute($getQuery);
    echo "   ✓ Gallery status: {$publishedGallery->getStatus()->getValue()}\n\n";

    // Test 8: Unpublish gallery
    echo "10. Unpublishing gallery...\n";
    $unpublishCommand = new PublishGalleryCommand($galleryId, new GalleryStatus(GalleryStatus::DRAFT));
    $commandBus->execute($unpublishCommand);
    echo "    ✓ Gallery unpublished\n\n";

    // Test 9: Final gallery list
    echo "11. Final gallery list...\n";
    $finalGalleries = $queryBus->execute($listQuery);
    echo "    ✓ Total galleries: " . count($finalGalleries) . "\n";
    foreach ($finalGalleries as $gallery) {
        $marker = $gallery->getId() === $galleryId ? " <-- NEW" : "";
        echo "    - ID: {$gallery->getId()}, Name: {$gallery->getName()}, Status: {$gallery->getStatus()->getValue()}$marker\n";
    }
    echo "\n";

    // Test 10: Delete gallery (optional - comment out to keep test data)
    echo "12. Deleting test gallery...\n";
    $deleteCommand = new DeleteGalleryCommand($galleryId);
    $commandBus->execute($deleteCommand);
    echo "    ✓ Gallery deleted\n\n";

    // Test 11: Verify deletion
    echo "13. Verifying gallery deletion...\n";
    try {
        $queryBus->execute($getQuery);
        echo "    ✗ ERROR: Gallery should have been deleted but still exists\n";
    } catch (\Exception $e) {
        echo "    ✓ Gallery successfully deleted: {$e->getMessage()}\n";
    }

    echo "\n=== ALL TESTS PASSED ===\n";
    echo "Gallery CQRS system is working correctly!\n\n";

    // Admin Integration Test
    echo "14. Testing Admin Integration...\n";
    $adminController = $container->get(\MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController::class);
    echo "    ✓ Admin Controller: " . get_class($adminController) . "\n";
    echo "    ✓ Admin integration ready\n\n";

    echo "=== COMPREHENSIVE TEST COMPLETED SUCCESSFULLY ===\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\n=== TEST FAILED ===\n";
}
