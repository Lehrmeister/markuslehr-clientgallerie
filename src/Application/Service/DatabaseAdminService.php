<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Service;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\SchemaManager;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager;

/**
 * Database Administration Service
 * 
 * Provides high-level database management functionality for admin interface
 */
class DatabaseAdminService
{
    private RepositoryManager $repositoryManager;
    private SchemaManager $schemaManager;
    private MigrationManager $migrationManager;

    public function __construct()
    {
        $this->repositoryManager = RepositoryManager::getInstance();
        $this->schemaManager = new SchemaManager();
        $this->migrationManager = new MigrationManager();

        // Register schemas and migrations
        $this->registerSchemas();
        $this->registerMigrations();
    }

    /**
     * Get comprehensive system status
     */
    public function getSystemStatus(): array
    {
        return [
            'repository_health' => $this->repositoryManager->getSystemHealth(),
            'migration_status' => $this->migrationManager->getMigrationStatus(),
            'schema_status' => $this->getSchemaStatus(),
            'statistics' => $this->repositoryManager->getAggregatedStatistics(),
            'integrity_check' => $this->repositoryManager->validateDataIntegrity()
        ];
    }

    /**
     * Get client management data
     */
    public function getClientData(array $options = []): array
    {
        $clients = $this->repositoryManager->clients();
        
        return [
            'clients' => $clients->findAll($options),
            'statistics' => $clients->getStatistics()
        ];
    }

    /**
     * Get log management data
     */
    public function getLogData(array $options = []): array
    {
        $logs = $this->repositoryManager->logs();
        
        return [
            'logs' => $logs->findAll($options),
            'statistics' => $logs->getStatistics(),
            'recent_activity' => $logs->getRecentActivity(['limit' => 100]),
            'errors' => $logs->findErrors(['limit' => 50]),
            'channels' => $logs->getChannels(),
            'levels' => $logs->getLevels()
        ];
    }

    /**
     * Search across all entities
     */
    public function searchAllEntities(string $term, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        
        return [
            'clients' => $this->repositoryManager->clients()->search($term, ['limit' => $limit]),
            'logs' => $this->repositoryManager->logs()->search($term, ['limit' => $limit]),
            'search_term' => $term
        ];
    }

    /**
     * Perform system maintenance
     */
    public function performMaintenance(): array
    {
        $results = [];

        // Repository maintenance
        $results['repository_maintenance'] = $this->repositoryManager->performMaintenance();

        // Clean old logs
        $oldLogsDeleted = $this->repositoryManager->logs()->cleanOldEntries(30);
        $results['old_logs_deleted'] = $oldLogsDeleted;

        // Archive very old logs
        $archivedLogs = $this->repositoryManager->logs()->archiveOldEntries(90);
        $results['logs_archived'] = $archivedLogs;

        // Clean old ratings
        $oldRatingsDeleted = $this->repositoryManager->ratings()->deleteOldRatings(365);
        $results['old_ratings_deleted'] = $oldRatingsDeleted;

        // Clear caches
        $results['cache_clearing'] = $this->repositoryManager->clearCaches();

        return $results;
    }

    /**
     * Run database migrations
     */
    public function runMigrations(): array
    {
        return $this->migrationManager->runPendingMigrations();
    }

    /**
     * Get migration information
     */
    public function getMigrationInfo(): array
    {
        return [
            'status' => $this->migrationManager->getMigrationStatus(),
            'history' => $this->migrationManager->getMigrationHistory(),
            'pending' => array_map(function($migration) {
                return [
                    'version' => $migration->getVersion(),
                    'description' => $migration->getDescription(),
                    'warnings' => $migration->getWarnings(),
                    'can_rollback' => $migration->canRollback()
                ];
            }, $this->migrationManager->getPendingMigrations())
        ];
    }

    /**
     * Rollback a migration
     */
    public function rollbackMigration(string $version): array
    {
        return $this->migrationManager->rollbackMigration($version);
    }

    /**
     * Validate data integrity
     */
    public function validateDataIntegrity(): array
    {
        return $this->repositoryManager->validateDataIntegrity();
    }

    /**
     * Export system data
     */
    public function exportSystemData(array $options = []): array
    {
        $format = $options['format'] ?? 'json';
        $includeTypes = $options['include'] ?? ['clients'];

        $data = [
            'export_info' => [
                'generated_at' => current_time('mysql'),
                'plugin_version' => MLCG_VERSION ?? '1.0.0',
                'format' => $format
            ]
        ];

        foreach ($includeTypes as $type) {
            switch ($type) {
                case 'clients':
                    $data['clients'] = $this->repositoryManager->clients()->findAll();
                    break;
            }
        }

        if ($format === 'json') {
            return [
                'success' => true,
                'data' => wp_json_encode($data, JSON_PRETTY_PRINT),
                'mime_type' => 'application/json',
                'filename' => 'mlcg_export_' . date('Y-m-d_H-i-s') . '.json'
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'format' => $format
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        $stats = $this->repositoryManager->getAggregatedStatistics();
        $health = $this->repositoryManager->getSystemHealth();

        return [
            'totals' => $stats['totals'] ?? [],
            'health_status' => $health['overall_status'],
            'repository_stats' => $stats['repository_stats'] ?? [],
            'recent_activity' => $this->repositoryManager->logs()->getRecentActivity(['limit' => 10]),
            'system_info' => [
                'plugin_version' => MLCG_VERSION,
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version'),
                'database_version' => $this->getDatabaseVersion()
            ]
        ];
    }

    /**
     * Register all schemas
     */
    private function registerSchemas(): void
    {
        $this->schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\ClientSchema());
        $this->schemaManager->registerSchema(new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\LogEntrySchema());
    }

    /**
     * Register all migrations
     */
    private function registerMigrations(): void
    {
        $this->migrationManager->registerMigration(
            new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\Migration_001_AddSocialMediaToClients()
        );
    }

    /**
     * Get schema status
     */
    private function getSchemaStatus(): array
    {
        return [
            'registered_schemas' => $this->schemaManager->getRegisteredSchemas(),
            'validation_results' => $this->schemaManager->validateAll()
        ];
    }

    /**
     * Get database version
     */
    private function getDatabaseVersion(): string
    {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()") ?: 'Unknown';
    }
}
