<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\BaseRepository;

/**
 * Repository for client entities
 * 
 * Handles CRUD operations and specific queries for clients
 */
class ClientRepository extends BaseRepository
{
    /**
     * Get the table name
     */
    protected function getTableName(): string
    {
        return $this->wpdb->prefix . 'ml_clientgallerie_clients';
    }

    /**
     * Get validation rules for client data
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'string|max:255',
            'phone' => 'string|max:50',
            'address' => 'string',
            'city' => 'string|max:100',
            'postal_code' => 'string|max:20',
            'country' => 'string|max:100',
            'website' => 'url|max:255',
            'notes' => 'string',
            'is_active' => 'boolean',
            'contact_preferences' => 'string',
            'timezone' => 'string|max:50',
            'language' => 'string|max:10',
            'avatar_url' => 'url|max:500',
            'metadata' => 'string'
        ];
    }

    /**
     * Find client by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE email = %s LIMIT 1";
        
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $email),
            ARRAY_A
        );

        return $result ?: null;
    }

    /**
     * Search clients by name, email, or company
     */
    public function search(string $term, array $options = []): array
    {
        $limit = $options['limit'] ?? 50;
        $activeOnly = $options['active_only'] ?? true;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE (name LIKE %s 
                   OR email LIKE %s 
                   OR company LIKE %s 
                   OR phone LIKE %s
                   OR city LIKE %s)";

        $searchTerm = '%' . $this->wpdb->esc_like($term) . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY name ASC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find clients by company
     */
    public function findByCompany(string $company, array $options = []): array
    {
        $activeOnly = $options['active_only'] ?? true;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE company = %s";
        $params = [$company];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY name ASC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find clients by country
     */
    public function findByCountry(string $country, array $options = []): array
    {
        $activeOnly = $options['active_only'] ?? true;
        $limit = $options['limit'] ?? 100;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE country = %s";
        $params = [$country];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY name ASC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get clients with galleries
     */
    public function findWithGalleries(array $options = []): array
    {
        $activeOnly = $options['active_only'] ?? true;
        $limit = $options['limit'] ?? 100;

        $galleriesTable = $this->wpdb->prefix . 'ml_clientgallerie_galleries';

        $sql = "SELECT DISTINCT c.* FROM {$this->getTableName()} c
                INNER JOIN {$galleriesTable} g ON c.id = g.client_id";

        $params = [];

        if ($activeOnly) {
            $sql .= " WHERE c.is_active = 1 AND g.is_active = 1";
        }

        $sql .= " ORDER BY c.name ASC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get clients without galleries
     */
    public function findWithoutGalleries(array $options = []): array
    {
        $activeOnly = $options['active_only'] ?? true;
        $limit = $options['limit'] ?? 100;

        $galleriesTable = $this->wpdb->prefix . 'ml_clientgallerie_galleries';

        $sql = "SELECT c.* FROM {$this->getTableName()} c
                LEFT JOIN {$galleriesTable} g ON c.id = g.client_id
                WHERE g.id IS NULL";

        $params = [];

        if ($activeOnly) {
            $sql .= " AND c.is_active = 1";
        }

        $sql .= " ORDER BY c.name ASC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Check if email is unique (excluding specific client)
     */
    public function isEmailUnique(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE email = %s";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        return (int)$count === 0;
    }

    /**
     * Get client statistics
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_clients,
                    COUNT(CASE WHEN company IS NOT NULL AND company != '' THEN 1 END) as clients_with_company,
                    COUNT(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 END) as clients_with_phone,
                    COUNT(CASE WHEN website IS NOT NULL AND website != '' THEN 1 END) as clients_with_website,
                    COUNT(DISTINCT country) as unique_countries,
                    COUNT(DISTINCT company) as unique_companies
                FROM {$this->getTableName()}";

        $stats = $this->wpdb->get_row($sql, ARRAY_A) ?: [];

        // Get country distribution
        $countrySql = "SELECT country, COUNT(*) as count 
                      FROM {$this->getTableName()} 
                      WHERE country IS NOT NULL AND country != '' 
                        AND is_active = 1
                      GROUP BY country 
                      ORDER BY count DESC 
                      LIMIT 10";

        $countries = $this->wpdb->get_results($countrySql, ARRAY_A) ?: [];

        // Get company distribution  
        $companySql = "SELECT company, COUNT(*) as count 
                      FROM {$this->getTableName()} 
                      WHERE company IS NOT NULL AND company != '' 
                        AND is_active = 1
                      GROUP BY company 
                      ORDER BY count DESC 
                      LIMIT 10";

        $companies = $this->wpdb->get_results($companySql, ARRAY_A) ?: [];

        $stats['country_distribution'] = $countries;
        $stats['company_distribution'] = $companies;

        return $stats;
    }

    /**
     * Get clients registered in date range
     */
    public function findByDateRange(string $startDate, string $endDate, array $options = []): array
    {
        $activeOnly = $options['active_only'] ?? true;
        $limit = $options['limit'] ?? 100;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE created_at >= %s AND created_at <= %s";
        $params = [$startDate, $endDate];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Update client activity status
     */
    public function updateActivityStatus(int $clientId, bool $isActive): bool
    {
        $result = $this->wpdb->update(
            $this->getTableName(),
            [
                'is_active' => $isActive ? 1 : 0,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $clientId],
            ['%d', '%s'],
            ['%d']
        );

        if ($result === false) {
            $this->logError('updateActivityStatus', $this->wpdb->last_error, [
                'client_id' => $clientId,
                'is_active' => $isActive
            ]);
            return false;
        }

        return true;
    }

    /**
     * Bulk update contact preferences
     */
    public function bulkUpdateContactPreferences(array $clientIds, array $preferences): bool
    {
        if (empty($clientIds) || empty($preferences)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($clientIds), '%d'));
        $preferencesJson = wp_json_encode($preferences);

        $sql = "UPDATE {$this->getTableName()} 
                SET contact_preferences = %s, updated_at = %s
                WHERE id IN ({$placeholders})";

        $params = array_merge(
            [$preferencesJson, current_time('mysql')],
            $clientIds
        );

        $result = $this->wpdb->query($this->wpdb->prepare($sql, $params));

        if ($result === false) {
            $this->logError('bulkUpdateContactPreferences', $this->wpdb->last_error, [
                'client_ids' => $clientIds,
                'preferences' => $preferences
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get clients who haven't been updated recently
     */
    public function findStaleClients(int $daysSinceUpdate = 365): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysSinceUpdate} days"));

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE updated_at < %s AND is_active = 1
                ORDER BY updated_at ASC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $cutoffDate),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Export client data for GDPR compliance
     */
    public function exportClientData(int $clientId): ?array
    {
        $client = $this->findById($clientId);
        if (!$client) {
            return null;
        }

        // Get related galleries
        $galleriesTable = $this->wpdb->prefix . 'ml_clientgallerie_galleries';
        $gallerysSql = "SELECT * FROM {$galleriesTable} WHERE client_id = %d";
        $galleries = $this->wpdb->get_results(
            $this->wpdb->prepare($gallerysSql, $clientId),
            ARRAY_A
        ) ?: [];

        // Get related ratings
        $ratingsTable = $this->wpdb->prefix . 'ml_clientgallerie_ratings';
        $ratingsSql = "SELECT * FROM {$ratingsTable} WHERE client_id = %d";
        $ratings = $this->wpdb->get_results(
            $this->wpdb->prepare($ratingsSql, $clientId),
            ARRAY_A
        ) ?: [];

        return [
            'client' => $client,
            'galleries' => $galleries,
            'ratings' => $ratings,
            'export_date' => current_time('mysql'),
            'export_format_version' => '1.0'
        ];
    }

    /**
     * Anonymize client data for GDPR compliance
     */
    public function anonymizeClient(int $clientId): bool
    {
        $anonymizedData = [
            'name' => 'Anonymized User',
            'email' => 'anonymized' . $clientId . '@example.com',
            'company' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'postal_code' => null,
            'country' => null,
            'website' => null,
            'notes' => 'User data anonymized on ' . current_time('mysql'),
            'contact_preferences' => null,
            'avatar_url' => null,
            'metadata' => wp_json_encode(['anonymized' => true, 'date' => current_time('mysql')]),
            'updated_at' => current_time('mysql')
        ];

        $result = $this->wpdb->update(
            $this->getTableName(),
            $anonymizedData,
            ['id' => $clientId],
            array_fill(0, count($anonymizedData), '%s'),
            ['%d']
        );

        if ($result === false) {
            $this->logError('anonymizeClient', $this->wpdb->last_error, [
                'client_id' => $clientId
            ]);
            return false;
        }

        return true;
    }
}
