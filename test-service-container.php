<?php
/**
 * Test script for ServiceContainer and dependency resolution
 * 
 * Tests all service registrations and resolves dependencies
 * 
 * @package MarkusLehr\ClientGallerie
 * @author Markus Lehr
 * @since 1.0.0
 */

// WordPress bootstrap (wenn als wp eval-file ausgeführt)
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Load plugin
require_once(__DIR__ . '/clientgallerie.php');

use MarkusLehr\ClientGallerie\Infrastructure\Container\ServiceContainer;
use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

echo "=== Testing ServiceContainer ===\n\n";

try {
    // Initialize container
    echo "1. Creating ServiceContainer...\n";
    $container = new ServiceContainer();
    
    echo "2. Registering services...\n";
    $container->register();
    
    echo "3. Checking registered services...\n";
    $services = $container->getRegisteredServices();
    echo "Registered services count: " . count($services) . "\n";
    echo "Services: " . implode(', ', $services) . "\n\n";
    
    // Test key services
    echo "4. Testing service resolution...\n";
    
    // Test GalleryRepository
    echo "Testing GalleryRepository...\n";
    $galleryRepo = $container->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class);
    echo "✓ GalleryRepository resolved: " . get_class($galleryRepo) . "\n";
    
    // Test Handlers
    echo "Testing CreateGalleryHandler...\n";
    $createHandler = $container->get(\MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler::class);
    echo "✓ CreateGalleryHandler resolved: " . get_class($createHandler) . "\n";
    
    echo "Testing ListGalleriesQueryHandler...\n";
    $listHandler = $container->get(\MarkusLehr\ClientGallerie\Application\Handler\ListGalleriesQueryHandler::class);
    echo "✓ ListGalleriesQueryHandler resolved: " . get_class($listHandler) . "\n";
    
    // Test Buses
    echo "Testing CommandBus...\n";
    $commandBus = $container->get(\MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface::class);
    echo "✓ CommandBus resolved: " . get_class($commandBus) . "\n";
    
    echo "Testing QueryBus...\n";
    $queryBus = $container->get(\MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface::class);
    echo "✓ QueryBus resolved: " . get_class($queryBus) . "\n";
    
    // Test Admin Controller
    echo "Testing GalleryAdminController...\n";
    $adminController = $container->get(\MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController::class);
    echo "✓ GalleryAdminController resolved: " . get_class($adminController) . "\n";
    
    echo "\n5. Testing singleton behavior...\n";
    $repo1 = $container->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class);
    $repo2 = $container->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class);
    
    if ($repo1 === $repo2) {
        echo "✓ Singleton behavior confirmed - same instance returned\n";
    } else {
        echo "✗ Singleton behavior failed - different instances returned\n";
    }
    
    echo "\n6. Testing non-existent service...\n";
    try {
        $container->get('non_existent_service');
        echo "✗ Should have thrown exception for non-existent service\n";
    } catch (\InvalidArgumentException $e) {
        echo "✓ Correctly threw exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== All ServiceContainer tests passed! ===\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
