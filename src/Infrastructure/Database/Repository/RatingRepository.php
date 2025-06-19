<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\BaseRepository;

/**
 * Repository for rating entities
 * 
 * Handles CRUD operations and specific queries for ratings
 */
class RatingRepository extends BaseRepository
{
    /**
     * Get the table name
     */
    protected function getTableName(): string
    {
        return $this->wpdb->prefix . 'ml_clientgallerie_ratings';
    }

    /**
     * Get validation rules for rating data
     */
    protected function getValidationRules(): array
    {
        return [
            'client_id' => 'required|numeric',
            'gallery_id' => 'numeric',
            'image_id' => 'numeric',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'string',
            'rating_type' => 'required|string|max:50',
            'is_public' => 'boolean',
            'is_moderated' => 'boolean',
            'moderator_notes' => 'string',
            'client_ip' => 'string|max:45',
            'user_agent' => 'string|max:500',
            'metadata' => 'string'
        ];
    }

    /**
     * Find ratings by gallery ID
     */
    public function findByGalleryId(int $galleryId, array $options = []): array
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;
        $limit = $options['limit'] ?? 100;
        $orderBy = $options['order_by'] ?? 'created_at';
        $order = strtoupper($options['order'] ?? 'DESC');

        $sql = "SELECT * FROM {$this->getTableName()} WHERE gallery_id = %d";
        $params = [$galleryId];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        $sql .= " ORDER BY {$orderBy} {$order} LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find ratings by image ID
     */
    public function findByImageId(int $imageId, array $options = []): array
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;
        $limit = $options['limit'] ?? 50;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE image_id = %d";
        $params = [$imageId];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find ratings by client ID
     */
    public function findByClientId(int $clientId, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $ratingType = $options['rating_type'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE client_id = %d";
        $params = [$clientId];

        if ($ratingType) {
            $sql .= " AND rating_type = %s";
            $params[] = $ratingType;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get average rating for gallery
     */
    public function getGalleryAverageRating(int $galleryId, array $options = []): float
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;
        $ratingType = $options['rating_type'] ?? null;

        $sql = "SELECT AVG(rating) FROM {$this->getTableName()} WHERE gallery_id = %d";
        $params = [$galleryId];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        if ($ratingType) {
            $sql .= " AND rating_type = %s";
            $params[] = $ratingType;
        }

        $average = $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        return $average ? (float)$average : 0.0;
    }

    /**
     * Get average rating for image
     */
    public function getImageAverageRating(int $imageId, array $options = []): float
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;
        $ratingType = $options['rating_type'] ?? null;

        $sql = "SELECT AVG(rating) FROM {$this->getTableName()} WHERE image_id = %d";
        $params = [$imageId];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        if ($ratingType) {
            $sql .= " AND rating_type = %s";
            $params[] = $ratingType;
        }

        $average = $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        return $average ? (float)$average : 0.0;
    }

    /**
     * Get rating distribution for gallery
     */
    public function getGalleryRatingDistribution(int $galleryId, array $options = []): array
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;

        $sql = "SELECT rating, COUNT(*) as count 
                FROM {$this->getTableName()} 
                WHERE gallery_id = %d";
        $params = [$galleryId];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        $sql .= " GROUP BY rating ORDER BY rating ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];

        // Initialize all rating values (1-5) with count 0
        $distribution = [
            1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0
        ];

        foreach ($results as $result) {
            $distribution[(int)$result['rating']] = (int)$result['count'];
        }

        return $distribution;
    }

    /**
     * Find ratings requiring moderation
     */
    public function findUnmoderated(array $options = []): array
    {
        $limit = $options['limit'] ?? 50;
        $orderBy = $options['order_by'] ?? 'created_at';
        $order = strtoupper($options['order'] ?? 'ASC');

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE is_moderated = 0
                ORDER BY {$orderBy} {$order} 
                LIMIT %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $limit),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Moderate multiple ratings
     */
    public function moderateRatings(array $ratingIds, bool $approve, string $moderatorNotes = ''): bool
    {
        if (empty($ratingIds)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($ratingIds), '%d'));

        $sql = "UPDATE {$this->getTableName()} 
                SET is_moderated = 1, 
                    is_public = %d,
                    moderator_notes = %s,
                    updated_at = %s
                WHERE id IN ({$placeholders})";

        $params = array_merge(
            [$approve ? 1 : 0, $moderatorNotes, current_time('mysql')],
            $ratingIds
        );

        $result = $this->wpdb->query($this->wpdb->prepare($sql, $params));

        if ($result === false) {
            $this->logError('moderateRatings', $this->wpdb->last_error, [
                'rating_ids' => $ratingIds,
                'approve' => $approve,
                'moderator_notes' => $moderatorNotes
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get rating statistics
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_ratings,
                    COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_ratings,
                    COUNT(CASE WHEN is_moderated = 1 THEN 1 END) as moderated_ratings,
                    AVG(rating) as average_rating,
                    COUNT(DISTINCT client_id) as unique_clients,
                    COUNT(DISTINCT gallery_id) as rated_galleries,
                    COUNT(DISTINCT image_id) as rated_images
                FROM {$this->getTableName()}";

        $stats = $this->wpdb->get_row($sql, ARRAY_A) ?: [];

        // Get rating distribution
        $distributionSql = "SELECT rating, COUNT(*) as count 
                           FROM {$this->getTableName()} 
                           WHERE is_public = 1 AND is_moderated = 1
                           GROUP BY rating 
                           ORDER BY rating ASC";

        $distribution = $this->wpdb->get_results($distributionSql, ARRAY_A) ?: [];

        // Get rating type distribution
        $typesSql = "SELECT rating_type, COUNT(*) as count 
                    FROM {$this->getTableName()} 
                    WHERE is_public = 1 AND is_moderated = 1
                    GROUP BY rating_type 
                    ORDER BY count DESC";

        $types = $this->wpdb->get_results($typesSql, ARRAY_A) ?: [];

        $stats['rating_distribution'] = $distribution;
        $stats['rating_type_distribution'] = $types;

        return $stats;
    }

    /**
     * Find top rated galleries
     */
    public function findTopRatedGalleries(array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $minRatings = $options['min_ratings'] ?? 5;
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;

        $galleriesTable = $this->wpdb->prefix . 'ml_clientgallerie_galleries';

        $sql = "SELECT 
                    r.gallery_id,
                    g.title as gallery_title,
                    AVG(r.rating) as average_rating,
                    COUNT(r.id) as rating_count
                FROM {$this->getTableName()} r
                INNER JOIN {$galleriesTable} g ON r.gallery_id = g.id
                WHERE 1=1";

        $params = [];

        if ($publicOnly) {
            $sql .= " AND r.is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND r.is_moderated = 1";
        }

        $sql .= " GROUP BY r.gallery_id, g.title
                 HAVING rating_count >= %d
                 ORDER BY average_rating DESC, rating_count DESC
                 LIMIT %d";

        $params[] = $minRatings;
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find top rated images
     */
    public function findTopRatedImages(array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $minRatings = $options['min_ratings'] ?? 3;
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;

        $imagesTable = $this->wpdb->prefix . 'ml_clientgallerie_images';

        $sql = "SELECT 
                    r.image_id,
                    i.filename,
                    i.title as image_title,
                    AVG(r.rating) as average_rating,
                    COUNT(r.id) as rating_count
                FROM {$this->getTableName()} r
                INNER JOIN {$imagesTable} i ON r.image_id = i.id
                WHERE r.image_id IS NOT NULL";

        $params = [];

        if ($publicOnly) {
            $sql .= " AND r.is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND r.is_moderated = 1";
        }

        $sql .= " GROUP BY r.image_id, i.filename, i.title
                 HAVING rating_count >= %d
                 ORDER BY average_rating DESC, rating_count DESC
                 LIMIT %d";

        $params[] = $minRatings;
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Check if client already rated an item
     */
    public function hasClientRated(int $clientId, ?int $galleryId = null, ?int $imageId = null, string $ratingType = 'general'): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} 
                WHERE client_id = %d AND rating_type = %s";
        $params = [$clientId, $ratingType];

        if ($galleryId) {
            $sql .= " AND gallery_id = %d";
            $params[] = $galleryId;
        }

        if ($imageId) {
            $sql .= " AND image_id = %d";
            $params[] = $imageId;
        }

        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        return (int)$count > 0;
    }

    /**
     * Get ratings by date range
     */
    public function findByDateRange(string $startDate, string $endDate, array $options = []): array
    {
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;
        $limit = $options['limit'] ?? 500;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE created_at >= %s AND created_at <= %s";
        $params = [$startDate, $endDate];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND is_moderated = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Delete ratings older than specified days
     */
    public function deleteOldRatings(int $daysOld = 365): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        $sql = "DELETE FROM {$this->getTableName()} 
                WHERE created_at < %s AND is_public = 0";

        $result = $this->wpdb->query($this->wpdb->prepare($sql, $cutoffDate));

        return $result !== false ? $result : 0;
    }

    /**
     * Export ratings for analysis
     */
    public function exportRatings(array $options = []): array
    {
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;
        $publicOnly = $options['public_only'] ?? true;
        $moderatedOnly = $options['moderated_only'] ?? true;

        $galleriesTable = $this->wpdb->prefix . 'ml_clientgallerie_galleries';
        $imagesTable = $this->wpdb->prefix . 'ml_clientgallerie_images';
        $clientsTable = $this->wpdb->prefix . 'ml_clientgallerie_clients';

        $sql = "SELECT 
                    r.*,
                    g.title as gallery_title,
                    i.filename as image_filename,
                    c.name as client_name,
                    c.email as client_email
                FROM {$this->getTableName()} r
                LEFT JOIN {$galleriesTable} g ON r.gallery_id = g.id
                LEFT JOIN {$imagesTable} i ON r.image_id = i.id
                LEFT JOIN {$clientsTable} c ON r.client_id = c.id
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND r.created_at >= %s";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND r.created_at <= %s";
            $params[] = $endDate;
        }

        if ($publicOnly) {
            $sql .= " AND r.is_public = 1";
        }

        if ($moderatedOnly) {
            $sql .= " AND r.is_moderated = 1";
        }

        $sql .= " ORDER BY r.created_at DESC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }
}
