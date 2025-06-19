<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\ClientRepository;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\LogEntryRepository;

/**
 * Repository Manager
 * 
 * Central access point for all repositories
 */
class RepositoryManager
{
    private static ?RepositoryManager $instance = null;

    private ?ClientRepository $clientRepository = null;
    private ?LogEntryRepository $logEntryRepository = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): RepositoryManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton
     */
    private function __construct()
    {
        // Prevent external instantiation
    }

    /**
     * Get client repository
     */
    public function clients(): ClientRepository
    {
        if ($this->clientRepository === null) {
            $this->clientRepository = new ClientRepository();
        }

        return $this->clientRepository;
    }

    /**
     * Get log entry repository
     */
    public function logs(): LogEntryRepository
    {
        if ($this->logEntryRepository === null) {
            $this->logEntryRepository = new LogEntryRepository();
        }

        return $this->logEntryRepository;
    }

    /**
     * Get all repositories
     */
    public function getAllRepositories(): array
    {
        return [
            'clients' => $this->clients(),
            'logs' => $this->logs()
        ];
    }

    /**
     * Test database connection through repositories
     */
    public function testConnections(): array
    {
        $results = [];

        foreach ($this->getAllRepositories() as $name => $repository) {
            try {
                $repository->validateTableExists();
                $results[$name] = ['status' => 'ok', 'message' => 'Connection successful'];
            } catch (\Exception $e) {
                $results[$name] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'repositories' => [],
            'statistics' => [],
            'issues' => []
        ];

        // Test each repository
        foreach ($this->getAllRepositories() as $name => $repository) {
            try {
                $repository->validateTableExists();
                $stats = method_exists($repository, 'getStatistics') 
                    ? $repository->getStatistics() 
                    : ['count' => $repository->count()];

                $health['repositories'][$name] = [
                    'status' => 'healthy',
                    'table_exists' => true,
                    'statistics' => $stats
                ];

                $health['statistics'][$name] = $stats;

            } catch (\Exception $e) {
                $health['repositories'][$name] = [
                    'status' => 'error',
                    'table_exists' => false,
                    'error' => $e->getMessage()
                ];

                $health['issues'][] = "Repository {$name}: " . $e->getMessage();
                $health['overall_status'] = 'degraded';
            }
        }

        // Check for specific issues
        if (count($health['issues']) > 0) {
            $health['overall_status'] = count($health['issues']) >= count($this->getAllRepositories()) / 2 
                ? 'critical' 
                : 'degraded';
        }

        return $health;
    }

    /**
     * Perform basic maintenance on all repositories
     */
    public function performMaintenance(): array
    {
        $results = [];

        foreach ($this->getAllRepositories() as $name => $repository) {
            try {
                if (method_exists($repository, 'performMaintenance')) {
                    $repository->performMaintenance();
                    $results[$name] = ['status' => 'completed', 'message' => 'Maintenance completed'];
                } else {
                    $results[$name] = ['status' => 'skipped', 'message' => 'No maintenance required'];
                }
            } catch (\Exception $e) {
                $results[$name] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get aggregated statistics across all repositories
     */
    public function getAggregatedStatistics(): array
    {
        $aggregated = [
            'totals' => [],
            'repository_stats' => [],
            'generated_at' => current_time('mysql')
        ];

        foreach ($this->getAllRepositories() as $name => $repository) {
            try {
                if (method_exists($repository, 'getStatistics')) {
                    $stats = $repository->getStatistics();
                    $aggregated['repository_stats'][$name] = $stats;

                    // Aggregate common metrics
                    foreach ($stats as $key => $value) {
                        if (is_numeric($value) && strpos($key, 'total_') === 0) {
                            $totalKey = str_replace('total_', '', $key);
                            if (!isset($aggregated['totals'][$totalKey])) {
                                $aggregated['totals'][$totalKey] = 0;
                            }
                            $aggregated['totals'][$totalKey] += (int)$value;
                        }
                    }
                }
            } catch (\Exception $e) {
                $aggregated['repository_stats'][$name] = ['error' => $e->getMessage()];
            }
        }

        return $aggregated;
    }

    /**
     * Clear all repository caches (if implemented)
     */
    public function clearCaches(): array
    {
        $results = [];

        foreach ($this->getAllRepositories() as $name => $repository) {
            try {
                if (method_exists($repository, 'clearCache')) {
                    $repository->clearCache();
                    $results[$name] = ['status' => 'cleared', 'message' => 'Cache cleared'];
                } else {
                    $results[$name] = ['status' => 'skipped', 'message' => 'No cache to clear'];
                }
            } catch (\Exception $e) {
                $results[$name] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Validate data integrity across repositories
     */
    public function validateDataIntegrity(): array
    {
        $issues = [];

        try {
            // Grundlegende Integritätsprüfungen können hier hinzugefügt werden
            // Aktuell nur Clients und Logs verfügbar

        } catch (\Exception $e) {
            $issues[] = "Error during integrity validation: " . $e->getMessage();
        }

        return [
            'status' => empty($issues) ? 'clean' : 'issues_found',
            'issues' => $issues,
            'issue_count' => count($issues),
            'checked_at' => current_time('mysql')
        ];
    }

    /**
     * Reset singleton instance (mainly for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
