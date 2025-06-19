<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GallerySlug;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;
use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * WordPress database implementation of GalleryRepositoryInterface
 * 
 * Infrastructure layer - handles data persistence using WordPress wpdb
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Repository
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryRepository implements GalleryRepositoryInterface
{
    private \wpdb $wpdb;
    private string $tableName;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . 'ml_clientgallerie_galleries';
    }

    /**
     * Get the base table name without prefix
     */
    public function getBaseTableName(): string
    {
        return 'ml_clientgallerie_galleries';
    }

    /**
     * Get the table name
     */
    public function getTableName(): string
    {
        return $this->wpdb->prefix . 'ml_clientgallerie_galleries';
    }

    /**
     * Save a gallery (create or update)
     */
    public function save(Gallery $gallery): Gallery
    {
        $logger = LoggerRegistry::getLogger();
        
        try {
            $data = $this->entityToArray($gallery);
            
            if ($gallery->getId() === 0) {
                // Create new gallery
                $result = $this->wpdb->insert(
                    $this->tableName,
                    $data,
                    $this->getFormatArray($data)
                );
                
                if ($result === false) {
                    throw new \RuntimeException('Failed to create gallery: ' . $this->wpdb->last_error);
                }
                
                $insertId = $this->wpdb->insert_id;
                $logger?->info('Gallery created', ['gallery_id' => $insertId, 'name' => $gallery->getName()]);
                
                // Return new gallery with ID
                $createdGallery = $this->findById($insertId);
                if (!$createdGallery) {
                    throw new \RuntimeException('Failed to retrieve created gallery');
                }
                return $createdGallery;
            } else {
                // Update existing gallery
                $result = $this->wpdb->update(
                    $this->tableName,
                    $data,
                    ['id' => $gallery->getId()],
                    $this->getFormatArray($data),
                    ['%d']
                );
                
                if ($result === false) {
                    throw new \RuntimeException('Failed to update gallery: ' . $this->wpdb->last_error);
                }
                
                $logger?->info('Gallery updated', ['gallery_id' => $gallery->getId(), 'name' => $gallery->getName()]);
                return $gallery;
            }
        } catch (\Exception $e) {
            $logger?->error('Failed to save gallery', [
                'gallery_id' => $gallery->getId(),
                'name' => $gallery->getName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Find gallery by ID
     */
    public function findById(int $id): ?Gallery
    {
        return $this->findGalleryById($id);
    }

    /**
     * Find gallery by ID (interface method)
     */
    public function findGalleryById(int $id): ?Gallery
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return $this->arrayToEntity($result);
    }

    /**
     * Find gallery by slug
     */
    public function findBySlug(GallerySlug $slug): ?Gallery
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} WHERE slug = %s",
                $slug->getValue()
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return $this->arrayToEntity($result);
    }

    /**
     * Find all galleries
     */
    public function findAll(): array
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->tableName} ORDER BY created_at DESC",
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([$this, 'arrayToEntity'], $results);
    }

    /**
     * Find galleries by status
     */
    public function findByStatus(GalleryStatus $status): array
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} WHERE status = %s ORDER BY created_at DESC",
                $status->getValue()
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([$this, 'arrayToEntity'], $results);
    }

    /**
     * Find galleries by client ID
     */
    public function findByClientId(int $clientId, array $options = []): array
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} WHERE client_id = %d ORDER BY created_at DESC",
                $clientId
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([$this, 'arrayToEntity'], $results);
    }

    /**
     * Delete gallery by ID
     */
    public function deleteById(int $id): bool
    {
        $logger = LoggerRegistry::getLogger();
        
        $result = $this->wpdb->delete(
            $this->tableName,
            ['id' => $id],
            ['%d']
        );

        $success = $result !== false;
        
        if ($success) {
            $logger?->info('Gallery deleted', ['gallery_id' => $id]);
        } else {
            $logger?->error('Failed to delete gallery', ['gallery_id' => $id, 'error' => $this->wpdb->last_error]);
        }

        return $success;
    }

    /**
     * Delete a gallery
     */
    public function deleteGallery(Gallery $gallery): bool
    {
        return $this->deleteById($gallery->getId());
    }

    /**
     * Delete gallery entity 
     */
    public function delete(Gallery $gallery): bool
    {
        return $this->deleteById($gallery->getId());
    }

    /**
     * Check if gallery exists by slug
     */
    public function existsBySlug(GallerySlug $slug): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE slug = %s",
                $slug->getValue()
            )
        );

        return (int) $count > 0;
    }

    /**
     * Check if gallery exists by ID
     */
    public function existsById(int $id): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE id = %d",
                $id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Count total galleries
     */
    public function count(): int
    {
        $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tableName}");
        return (int) $count;
    }

    /**
     * Count galleries by status
     */
    public function countByStatus(GalleryStatus $status): int
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE status = %s",
                $status->getValue()
            )
        );

        return (int) $count;
    }

    /**
     * Count galleries by criteria
     */
    public function countByCriteria(array $criteria = []): int
    {
        $where = [];
        $params = [];
        
        if (isset($criteria['status'])) {
            $where[] = 'status = %s';
            $params[] = $criteria['status'];
        }
        
        if (isset($criteria['client_id'])) {
            $where[] = 'client_id = %d';
            $params[] = $criteria['client_id'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->tableName}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Get next available sort order for client
     */
    public function getNextSortOrder(int $clientId): int
    {
        $maxOrder = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT MAX(sort_order) FROM {$this->tableName} WHERE client_id = %d",
                $clientId
            )
        );

        return (int) $maxOrder + 1;
    }

    /**
     * Update gallery sort orders
     */
    public function updateSortOrders(array $orders): bool
    {
        $logger = LoggerRegistry::getLogger();
        
        foreach ($orders as $galleryId => $sortOrder) {
            $result = $this->wpdb->update(
                $this->tableName,
                ['sort_order' => (int) $sortOrder],
                ['id' => (int) $galleryId],
                ['%d'],
                ['%d']
            );
            
            if ($result === false) {
                $logger?->error('Failed to update sort order', [
                    'gallery_id' => $galleryId,
                    'sort_order' => $sortOrder,
                    'error' => $this->wpdb->last_error
                ]);
                return false;
            }
        }
        
        $logger?->info('Gallery sort orders updated', ['orders' => $orders]);
        return true;
    }

    /**
     * Get galleries statistics
     */
    public function getStatistics(array $criteria = []): array
    {
        $stats = [];
        
        // Total galleries
        $stats['total'] = $this->countByCriteria($criteria);
        
        // By status
        $stats['by_status'] = [];
        $statuses = ['draft', 'published', 'archived'];
        foreach ($statuses as $status) {
            $statusCriteria = array_merge($criteria, ['status' => $status]);
            $stats['by_status'][$status] = $this->countByCriteria($statusCriteria);
        }
        
        // Recent galleries (last 30 days)
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $recentCount = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE created_at >= %s",
                $thirtyDaysAgo
            )
        );
        $stats['recent'] = (int) $recentCount;
        
        return $stats;
    }

    /**
     * Find galleries with pagination
     */
    public function findWithPagination(int $limit = 20, int $offset = 0): array
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([$this, 'arrayToEntity'], $results);
    }

    /**
     * Find all galleries with optional filtering
     */
    public function findByCriteria(array $criteria = [], array $options = []): array
    {
        $where = [];
        $params = [];
        
        if (isset($criteria['status'])) {
            $where[] = 'status = %s';
            $params[] = $criteria['status'];
        }
        
        if (isset($criteria['client_id'])) {
            $where[] = 'client_id = %d';
            $params[] = $criteria['client_id'];
        }
        
        $sql = "SELECT * FROM {$this->tableName}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int) $options['limit'];
            if (isset($options['offset'])) {
                $sql .= " OFFSET " . (int) $options['offset'];
            }
        }
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        return array_map([$this, 'arrayToEntity'], $results);
    }

    /**
     * Convert Gallery entity to database array
     */
    private function entityToArray(Gallery $gallery): array
    {
        $data = [
            'name' => $gallery->getName(),
            'slug' => $gallery->getSlug()->getValue(),
            'description' => $gallery->getDescription(),
            'status' => $gallery->getStatus()->getValue(),
            'client_id' => $gallery->getClientId(),
            'updated_at' => $gallery->getUpdatedAt()->format('Y-m-d H:i:s')
        ];

        // Only include created_at for new galleries
        if ($gallery->getId() === 0) {
            $data['created_at'] = $gallery->getCreatedAt()->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Convert database array to Gallery entity
     */
    private function arrayToEntity(array $data): Gallery
    {
        return new Gallery(
            (int) $data['id'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['status'],
            (int) $data['client_id'],
            new \DateTimeImmutable($data['created_at']),
            new \DateTimeImmutable($data['updated_at']),
            [], // settings - default empty array
            0   // image_count - default 0
        );
    }

    /**
     * Get format array for wpdb operations
     */
    private function getFormatArray(array $data): array
    {
        $formats = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['client_id', 'image_count'], true)) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

    /**
     * Map database row to Gallery entity
     */
    public function mapToEntity(array $row): Gallery
    {
        return $this->arrayToEntity($row);
    }
}
