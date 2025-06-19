<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Migration;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\BaseMigration;

/**
 * Migration Manager
 * 
 * Handles database migrations and versioning
 */
class MigrationManager
{
    private \wpdb $wpdb;
    private string $migrationTable;
    private array $migrations = [];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->migrationTable = $wpdb->prefix . 'ml_clientgallerie_migrations';
        $this->initializeMigrationTable();
    }

    /**
     * Register a migration
     */
    public function registerMigration(BaseMigration $migration): void
    {
        $this->migrations[$migration->getVersion()] = $migration;
    }

    /**
     * Register multiple migrations
     */
    public function registerMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            if ($migration instanceof BaseMigration) {
                $this->registerMigration($migration);
            }
        }
    }

    /**
     * Run pending migrations
     */
    public function runPendingMigrations(): array
    {
        $results = [];
        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            return ['status' => 'success', 'message' => 'No pending migrations'];
        }

        foreach ($pendingMigrations as $migration) {
            $result = $this->runMigration($migration);
            $results[] = $result;

            if (!$result['success']) {
                // Stop on first failure
                $results[] = [
                    'version' => 'STOPPED',
                    'success' => false,
                    'message' => 'Migration stopped due to previous failure'
                ];
                break;
            }
        }

        return [
            'status' => empty(array_filter($results, fn($r) => !$r['success'])) ? 'success' : 'error',
            'migrations' => $results
        ];
    }

    /**
     * Run a specific migration
     */
    public function runMigration(BaseMigration $migration): array
    {
        $version = $migration->getVersion();

        try {
            // Check if already applied
            if ($this->isMigrationApplied($version)) {
                return [
                    'version' => $version,
                    'success' => true,
                    'message' => 'Migration already applied',
                    'skipped' => true
                ];
            }

            // Validate prerequisites
            $validation = $migration->validatePrerequisites();
            if (!$validation['valid']) {
                return [
                    'version' => $version,
                    'success' => false,
                    'message' => 'Prerequisites not met: ' . implode(', ', $validation['messages'])
                ];
            }

            // Record migration start
            $this->recordMigrationStart($migration);

            // Run the migration
            $startTime = microtime(true);
            $success = $migration->up();
            $executionTime = microtime(true) - $startTime;

            if ($success) {
                $this->recordMigrationSuccess($migration, $executionTime);
                return [
                    'version' => $version,
                    'success' => true,
                    'message' => 'Migration completed successfully',
                    'execution_time' => $executionTime
                ];
            } else {
                $this->recordMigrationFailure($migration, 'Migration up() method returned false');
                return [
                    'version' => $version,
                    'success' => false,
                    'message' => 'Migration failed',
                    'execution_time' => $executionTime
                ];
            }

        } catch (\Exception $e) {
            $this->recordMigrationFailure($migration, $e->getMessage());
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Migration exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rollback a migration
     */
    public function rollbackMigration(string $version): array
    {
        if (!isset($this->migrations[$version])) {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Migration not found'
            ];
        }

        $migration = $this->migrations[$version];

        if (!$migration->canRollback()) {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Migration cannot be rolled back'
            ];
        }

        try {
            if (!$this->isMigrationApplied($version)) {
                return [
                    'version' => $version,
                    'success' => true,
                    'message' => 'Migration not applied, nothing to rollback',
                    'skipped' => true
                ];
            }

            $startTime = microtime(true);
            $success = $migration->down();
            $executionTime = microtime(true) - $startTime;

            if ($success) {
                $this->removeMigrationRecord($version);
                return [
                    'version' => $version,
                    'success' => true,
                    'message' => 'Migration rolled back successfully',
                    'execution_time' => $executionTime
                ];
            } else {
                return [
                    'version' => $version,
                    'success' => false,
                    'message' => 'Migration rollback failed',
                    'execution_time' => $executionTime
                ];
            }

        } catch (\Exception $e) {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Rollback exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get pending migrations
     */
    public function getPendingMigrations(): array
    {
        $appliedVersions = $this->getAppliedVersions();
        $pendingMigrations = [];

        foreach ($this->migrations as $version => $migration) {
            if (!in_array($version, $appliedVersions)) {
                $pendingMigrations[] = $migration;
            }
        }

        // Sort by version
        usort($pendingMigrations, function($a, $b) {
            return version_compare($a->getVersion(), $b->getVersion());
        });

        return $pendingMigrations;
    }

    /**
     * Get applied migration versions
     */
    public function getAppliedVersions(): array
    {
        $sql = "SELECT version FROM {$this->migrationTable} WHERE status = 'completed' ORDER BY version ASC";
        $results = $this->wpdb->get_col($sql);
        return $results ?: [];
    }

    /**
     * Get migration status
     */
    public function getMigrationStatus(): array
    {
        $allVersions = array_keys($this->migrations);
        $appliedVersions = $this->getAppliedVersions();
        $pendingVersions = array_diff($allVersions, $appliedVersions);

        // Sort versions
        sort($allVersions, SORT_VERSION);
        sort($appliedVersions, SORT_VERSION);
        sort($pendingVersions, SORT_VERSION);

        return [
            'all_migrations' => count($allVersions),
            'applied_migrations' => count($appliedVersions),
            'pending_migrations' => count($pendingVersions),
            'applied_versions' => $appliedVersions,
            'pending_versions' => $pendingVersions,
            'current_version' => !empty($appliedVersions) ? end($appliedVersions) : null,
            'latest_version' => !empty($allVersions) ? end($allVersions) : null
        ];
    }

    /**
     * Get migration history
     */
    public function getMigrationHistory(): array
    {
        $sql = "SELECT * FROM {$this->migrationTable} ORDER BY executed_at DESC";
        return $this->wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Check if migration is applied
     */
    public function isMigrationApplied(string $version): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->migrationTable} WHERE version = %s AND status = 'completed'";
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $version));
        return (int)$count > 0;
    }

    /**
     * Reset all migrations (dangerous!)
     */
    public function resetMigrations(): bool
    {
        $sql = "DELETE FROM {$this->migrationTable}";
        return $this->wpdb->query($sql) !== false;
    }

    /**
     * Mark migration as applied without running it
     */
    public function markAsApplied(string $version, string $reason = 'Manually marked'): bool
    {
        if (!isset($this->migrations[$version])) {
            return false;
        }

        $migration = $this->migrations[$version];
        
        return $this->wpdb->insert(
            $this->migrationTable,
            [
                'version' => $version,
                'description' => $migration->getDescription(),
                'status' => 'completed',
                'executed_at' => current_time('mysql'),
                'execution_time' => 0,
                'notes' => $reason
            ],
            ['%s', '%s', '%s', '%s', '%f', '%s']
        ) !== false;
    }

    /**
     * Get detailed migration info
     */
    public function getMigrationInfo(string $version): ?array
    {
        if (!isset($this->migrations[$version])) {
            return null;
        }

        $migration = $this->migrations[$version];
        $isApplied = $this->isMigrationApplied($version);

        $info = [
            'version' => $version,
            'description' => $migration->getDescription(),
            'can_rollback' => $migration->canRollback(),
            'warnings' => $migration->getWarnings(),
            'is_applied' => $isApplied,
            'prerequisites' => $migration->validatePrerequisites()
        ];

        if ($isApplied) {
            $sql = "SELECT * FROM {$this->migrationTable} WHERE version = %s AND status = 'completed' LIMIT 1";
            $record = $this->wpdb->get_row($this->wpdb->prepare($sql, $version), ARRAY_A);
            if ($record) {
                $info['executed_at'] = $record['executed_at'];
                $info['execution_time'] = $record['execution_time'];
                $info['notes'] = $record['notes'];
            }
        }

        return $info;
    }

    /**
     * Initialize migration table
     */
    private function initializeMigrationTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(50) NOT NULL,
            description TEXT,
            status ENUM('running', 'completed', 'failed') NOT NULL,
            executed_at DATETIME NOT NULL,
            execution_time DECIMAL(10,4) DEFAULT 0,
            error_message TEXT NULL,
            notes TEXT NULL,
            INDEX idx_version (version),
            INDEX idx_status (status),
            INDEX idx_executed_at (executed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->wpdb->query($sql);
    }

    /**
     * Record migration start
     */
    private function recordMigrationStart(BaseMigration $migration): void
    {
        $this->wpdb->insert(
            $this->migrationTable,
            [
                'version' => $migration->getVersion(),
                'description' => $migration->getDescription(),
                'status' => 'running',
                'executed_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    /**
     * Record migration success
     */
    private function recordMigrationSuccess(BaseMigration $migration, float $executionTime): void
    {
        $this->wpdb->update(
            $this->migrationTable,
            [
                'status' => 'completed',
                'execution_time' => $executionTime
            ],
            [
                'version' => $migration->getVersion(),
                'status' => 'running'
            ],
            ['%s', '%f'],
            ['%s', '%s']
        );
    }

    /**
     * Record migration failure
     */
    private function recordMigrationFailure(BaseMigration $migration, string $errorMessage): void
    {
        $this->wpdb->update(
            $this->migrationTable,
            [
                'status' => 'failed',
                'error_message' => $errorMessage
            ],
            [
                'version' => $migration->getVersion(),
                'status' => 'running'
            ],
            ['%s', '%s'],
            ['%s', '%s']
        );
    }

    /**
     * Run a single migration by name/version
     */
    public function runSingle(string $version): bool
    {
        if (!isset($this->migrations[$version])) {
            throw new \Exception("Migration not found: $version");
        }
        
        $migration = $this->migrations[$version];
        
        if ($this->isMigrationExecuted($migration->getVersion())) {
            throw new \Exception("Migration already executed: $version");
        }
        
        return $this->executeMigration($migration);
    }
    
    /**
     * Run all pending migrations (alias for runPendingMigrations)
     */
    public function runPending(): array
    {
        return $this->runPendingMigrations();
    }
    
    /**
     * Get migration status for admin interface
     */
    public function getStatus(): array
    {
        $status = [];
        
        foreach ($this->migrations as $version => $migration) {
            $executed = $this->isMigrationExecuted($version);
            $executedAt = null;
            
            if ($executed) {
                $record = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT executed_at FROM {$this->migrationTable} WHERE version = %s AND status = 'completed'",
                        $version
                    )
                );
                $executedAt = $record ? $record->executed_at : null;
            }
            
            $status[] = [
                'name' => $migration->getName(),
                'version' => $version,
                'description' => $migration->getDescription(),
                'executed' => $executed,
                'executed_at' => $executedAt
            ];
        }
        
        // Sort by version
        usort($status, function($a, $b) {
            return version_compare($a['version'], $b['version']);
        });
        
        return $status;
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord(string $version): void
    {
        $this->wpdb->delete(
            $this->migrationTable,
            ['version' => $version],
            ['%s']
        );
    }
}
