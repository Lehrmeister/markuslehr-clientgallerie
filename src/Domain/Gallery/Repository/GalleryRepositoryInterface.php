<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Domain\Gallery\Repository;

use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GallerySlug;

/**
 * Gallery Repository Interface
 * 
 * Domain contract for gallery persistence operations.
 * Implementation will be in Infrastructure layer.
 * 
 * @package MarkusLehr\ClientGallerie\Domain\Gallery\Repository
 * @author Markus Lehr
 * @since 1.0.0
 */
interface GalleryRepositoryInterface
{
    /**
     * Save a gallery (create or update)
     * 
     * @param Gallery $gallery Gallery entity to save
     * @return Gallery Saved gallery with ID assigned
     * @throws \RuntimeException If save operation fails
     */
    public function save(Gallery $gallery): Gallery;

    /**
     * Find gallery by ID
     * 
     * @param int $id Gallery ID
     * @return Gallery|null Gallery entity or null if not found
     */
    public function findGalleryById(int $id): ?Gallery;

    /**
     * Find gallery by slug
     * 
     * @param GallerySlug $slug Gallery slug
     * @return Gallery|null Gallery entity or null if not found
     */
    public function findBySlug(GallerySlug $slug): ?Gallery;

    /**
     * Find galleries by client ID
     * 
     * @param int $clientId Client ID
     * @param array $options Query options (limit, offset, status, etc.)
     * @return Gallery[] Array of gallery entities
     */
    public function findByClientId(int $clientId, array $options = []): array;

    /**
     * Find all galleries with optional filtering
     * 
     * @param array $criteria Filter criteria (status, client_id, etc.)
     * @param array $options Query options (limit, offset, order_by, etc.)
     * @return Gallery[] Array of gallery entities
     */
    public function findByCriteria(array $criteria = [], array $options = []): array;

    /**
     * Delete gallery by ID (for infrastructure compatibility)
     *
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;

    /**
     * Delete gallery entity (for domain usage)
     *
     * @param Gallery $gallery
     * @return bool
     */
    public function deleteGallery(Gallery $gallery): bool;

    /**
     * Check if gallery exists by ID
     * 
     * @param int $id Gallery ID
     * @return bool True if exists, false otherwise
     */
    public function existsById(int $id): bool;

    /**
     * Check if gallery exists by slug
     * 
     * @param GallerySlug $slug Gallery slug
     * @return bool True if exists, false otherwise
     */
    public function existsBySlug(GallerySlug $slug): bool;

    /**
     * Count galleries by criteria
     * 
     * @param array $criteria Filter criteria
     * @return int Number of galleries matching criteria
     */
    public function countByCriteria(array $criteria = []): int;

    /**
     * Get next available sort order for client
     * 
     * @param int $clientId Client ID
     * @return int Next sort order value
     */
    public function getNextSortOrder(int $clientId): int;

    /**
     * Update gallery sort orders
     * 
     * @param array $orders Array of ['gallery_id' => sort_order]
     * @return bool True if successful
     */
    public function updateSortOrders(array $orders): bool;

    /**
     * Get galleries statistics
     * 
     * @param array $criteria Optional filter criteria
     * @return array Statistics data
     */
    public function getStatistics(array $criteria = []): array;
}
