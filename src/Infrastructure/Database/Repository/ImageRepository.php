<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\BaseRepository;

/**
 * Repository for image entities
 * 
 * Handles CRUD operations and specific queries for images
 */
class ImageRepository extends BaseRepository
{
    /**
     * Get the table name
     */
    protected function getTableName(): string
    {
        return $this->wpdb->prefix . 'ml_clientgallerie_images';
    }

    /**
     * Get validation rules for image data
     */
    protected function getValidationRules(): array
    {
        return [
            'gallery_id' => 'required|numeric',
            'filename' => 'required|string|max:255',
            'original_filename' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'file_size' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'file_type' => 'required|string|max:20',
            'title' => 'string|max:255',
            'description' => 'string',
            'alt_text' => 'string|max:255',
            'sort_order' => 'numeric|min:0',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'exif_data' => 'string',
            'metadata' => 'string'
        ];
    }

    /**
     * Find images by gallery ID
     */
    public function findByGalleryId(int $galleryId, array $options = []): array
    {
        $orderBy = $options['order_by'] ?? 'sort_order';
        $order = strtoupper($options['order'] ?? 'ASC');
        $limit = $options['limit'] ?? null;
        $activeOnly = $options['active_only'] ?? true;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE gallery_id = %d";
        $params = [$galleryId];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY {$orderBy} {$order}";

        if ($limit) {
            $sql .= " LIMIT %d";
            $params[] = $limit;
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find featured images
     */
    public function findFeatured(array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $orderBy = $options['order_by'] ?? 'created_at';
        $order = strtoupper($options['order'] ?? 'DESC');

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE is_featured = 1 AND is_active = 1
                ORDER BY {$orderBy} {$order}
                LIMIT %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $limit),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Search images by filename or metadata
     */
    public function search(string $term, array $options = []): array
    {
        $limit = $options['limit'] ?? 50;
        $galleryId = $options['gallery_id'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE (filename LIKE %s 
                   OR original_filename LIKE %s 
                   OR title LIKE %s 
                   OR description LIKE %s
                   OR alt_text LIKE %s)
                AND is_active = 1";

        $searchTerm = '%' . $this->wpdb->esc_like($term) . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];

        if ($galleryId) {
            $sql .= " AND gallery_id = %d";
            $params[] = $galleryId;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get images by file type
     */
    public function findByFileType(string $fileType, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $galleryId = $options['gallery_id'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE file_type = %s AND is_active = 1";
        $params = [$fileType];

        if ($galleryId) {
            $sql .= " AND gallery_id = %d";
            $params[] = $galleryId;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Update sort order for multiple images
     */
    public function updateSortOrder(array $imageOrders): bool
    {
        if (empty($imageOrders)) {
            return true;
        }

        $this->wpdb->query('START TRANSACTION');

        try {
            foreach ($imageOrders as $imageId => $sortOrder) {
                $result = $this->wpdb->update(
                    $this->getTableName(),
                    ['sort_order' => (int)$sortOrder, 'updated_at' => current_time('mysql')],
                    ['id' => (int)$imageId],
                    ['%d', '%s'],
                    ['%d']
                );

                if ($result === false) {
                    throw new \Exception("Failed to update sort order for image {$imageId}");
                }
            }

            $this->wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->logError('updateSortOrder', $e->getMessage(), ['orders' => $imageOrders]);
            return false;
        }
    }

    /**
     * Set featured image for gallery (unsets others)
     */
    public function setFeatured(int $imageId, int $galleryId): bool
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            // Unset all featured images in this gallery
            $this->wpdb->update(
                $this->getTableName(),
                ['is_featured' => 0, 'updated_at' => current_time('mysql')],
                ['gallery_id' => $galleryId],
                ['%d', '%s'],
                ['%d']
            );

            // Set the new featured image
            $result = $this->wpdb->update(
                $this->getTableName(),
                ['is_featured' => 1, 'updated_at' => current_time('mysql')],
                ['id' => $imageId, 'gallery_id' => $galleryId],
                ['%d', '%s'],
                ['%d', '%d']
            );

            if ($result === false) {
                throw new \Exception("Failed to set featured image {$imageId}");
            }

            $this->wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->logError('setFeatured', $e->getMessage(), [
                'image_id' => $imageId,
                'gallery_id' => $galleryId
            ]);
            return false;
        }
    }

    /**
     * Get image statistics
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_images,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_images,
                    COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_images,
                    AVG(file_size) as avg_file_size,
                    SUM(file_size) as total_file_size,
                    COUNT(DISTINCT file_type) as unique_file_types,
                    COUNT(DISTINCT gallery_id) as galleries_with_images
                FROM {$this->getTableName()}";

        $stats = $this->wpdb->get_row($sql, ARRAY_A) ?: [];

        // Get file type distribution
        $fileTypesSql = "SELECT file_type, COUNT(*) as count 
                        FROM {$this->getTableName()} 
                        WHERE is_active = 1 
                        GROUP BY file_type 
                        ORDER BY count DESC";

        $fileTypes = $this->wpdb->get_results($fileTypesSql, ARRAY_A) ?: [];

        $stats['file_type_distribution'] = $fileTypes;

        return $stats;
    }

    /**
     * Bulk update metadata
     */
    public function bulkUpdateMetadata(array $imageIds, array $metadata): bool
    {
        if (empty($imageIds) || empty($metadata)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($imageIds), '%d'));
        $setParts = [];
        $params = [];

        foreach ($metadata as $field => $value) {
            if (in_array($field, ['title', 'description', 'alt_text', 'metadata', 'exif_data'])) {
                $setParts[] = "{$field} = %s";
                $params[] = $value;
            }
        }

        if (empty($setParts)) {
            return true;
        }

        $setParts[] = "updated_at = %s";
        $params[] = current_time('mysql');
        $params = array_merge($params, $imageIds);

        $sql = "UPDATE {$this->getTableName()} 
                SET " . implode(', ', $setParts) . "
                WHERE id IN ({$placeholders})";

        $result = $this->wpdb->query($this->wpdb->prepare($sql, $params));

        if ($result === false) {
            $this->logError('bulkUpdateMetadata', $this->wpdb->last_error, [
                'image_ids' => $imageIds,
                'metadata' => $metadata
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get images requiring optimization (large file sizes)
     */
    public function findLargeImages(int $maxSizeBytes = 5242880): array // 5MB default
    {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE file_size > %d AND is_active = 1
                ORDER BY file_size DESC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $maxSizeBytes),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get duplicate images (same file size and dimensions)
     */
    public function findDuplicates(): array
    {
        $sql = "SELECT file_size, width, height, COUNT(*) as count, 
                       GROUP_CONCAT(id) as image_ids,
                       GROUP_CONCAT(filename) as filenames
                FROM {$this->getTableName()} 
                WHERE is_active = 1
                GROUP BY file_size, width, height
                HAVING count > 1
                ORDER BY count DESC";

        return $this->wpdb->get_results($sql, ARRAY_A) ?: [];
    }
}
