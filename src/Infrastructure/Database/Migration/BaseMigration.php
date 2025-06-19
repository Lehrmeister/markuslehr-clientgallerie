<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Migration;

/**
 * Abstract base class for database migrations
 */
abstract class BaseMigration
{
    protected \wpdb $wpdb;
    protected string $version;
    protected string $description;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get migration version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get migration description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Get migration name (human readable)
     */
    public function getName(): string
    {
        return $this->description ?: 'Migration ' . $this->version;
    }

    /**
     * Execute the migration (up direction)
     */
    abstract public function up(): bool;

    /**
     * Rollback the migration (down direction)
     */
    abstract public function down(): bool;

    /**
     * Check if this migration can be rolled back
     */
    public function canRollback(): bool
    {
        return true;
    }

    /**
     * Get any warnings for this migration
     */
    public function getWarnings(): array
    {
        return [];
    }

    /**
     * Validate prerequisites for this migration
     */
    public function validatePrerequisites(): array
    {
        return ['valid' => true, 'messages' => []];
    }

    /**
     * Log migration activity
     */
    protected function logMigration(string $action, string $message, array $context = []): void
    {
        $logData = [
            'version' => $this->version,
            'action' => $action,
            'message' => $message,
            'context' => wp_json_encode($context),
            'timestamp' => current_time('mysql')
        ];

        // Use WordPress logging or custom logger
        if (function_exists('error_log')) {
            error_log(sprintf(
                '[Migration %s] %s: %s',
                $this->version,
                $action,
                $message
            ));
        }
    }

    /**
     * Execute SQL with error handling
     */
    protected function executeSql(string $sql, array $params = []): bool
    {
        try {
            if (!empty($params)) {
                $result = $this->wpdb->query($this->wpdb->prepare($sql, $params));
            } else {
                $result = $this->wpdb->query($sql);
            }

            if ($result === false) {
                $this->logMigration('error', "SQL execution failed: " . $this->wpdb->last_error, ['sql' => $sql]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logMigration('error', "SQL execution exception: " . $e->getMessage(), ['sql' => $sql]);
            return false;
        }
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $tableName): bool
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tableName
        ));

        return $result === $tableName;
    }

    /**
     * Check if column exists in table
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$tableName}` LIKE %s",
            $columnName
        ));

        return !empty($result);
    }

    /**
     * Check if index exists on table
     */
    protected function indexExists(string $tableName, string $indexName): bool
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s",
            $indexName
        ));

        return !empty($result);
    }

    /**
     * Add column to table
     */
    protected function addColumn(string $tableName, string $columnName, string $columnDefinition): bool
    {
        if ($this->columnExists($tableName, $columnName)) {
            $this->logMigration('info', "Column {$columnName} already exists in {$tableName}");
            return true;
        }

        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnDefinition}";
        return $this->executeSql($sql);
    }

    /**
     * Drop column from table
     */
    protected function dropColumn(string $tableName, string $columnName): bool
    {
        if (!$this->columnExists($tableName, $columnName)) {
            $this->logMigration('info', "Column {$columnName} does not exist in {$tableName}");
            return true;
        }

        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";
        return $this->executeSql($sql);
    }

    /**
     * Modify column in table
     */
    protected function modifyColumn(string $tableName, string $columnName, string $newDefinition): bool
    {
        if (!$this->columnExists($tableName, $columnName)) {
            $this->logMigration('error', "Column {$columnName} does not exist in {$tableName}");
            return false;
        }

        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$newDefinition}";
        return $this->executeSql($sql);
    }

    /**
     * Add index to table
     */
    protected function addIndex(string $tableName, string $indexName, array $columns, string $type = 'INDEX'): bool
    {
        if ($this->indexExists($tableName, $indexName)) {
            $this->logMigration('info', "Index {$indexName} already exists on {$tableName}");
            return true;
        }

        $columnList = '`' . implode('`, `', $columns) . '`';
        $sql = "ALTER TABLE `{$tableName}` ADD {$type} `{$indexName}` ({$columnList})";
        return $this->executeSql($sql);
    }

    /**
     * Drop index from table
     */
    protected function dropIndex(string $tableName, string $indexName): bool
    {
        if (!$this->indexExists($tableName, $indexName)) {
            $this->logMigration('info', "Index {$indexName} does not exist on {$tableName}");
            return true;
        }

        $sql = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`";
        return $this->executeSql($sql);
    }

    /**
     * Create new table
     */
    protected function createTable(string $tableName, string $tableDefinition): bool
    {
        if ($this->tableExists($tableName)) {
            $this->logMigration('info', "Table {$tableName} already exists");
            return true;
        }

        $sql = "CREATE TABLE `{$tableName}` ({$tableDefinition})";
        return $this->executeSql($sql);
    }

    /**
     * Drop table
     */
    protected function dropTable(string $tableName): bool
    {
        if (!$this->tableExists($tableName)) {
            $this->logMigration('info', "Table {$tableName} does not exist");
            return true;
        }

        $sql = "DROP TABLE `{$tableName}`";
        return $this->executeSql($sql);
    }

    /**
     * Rename table
     */
    protected function renameTable(string $oldName, string $newName): bool
    {
        if (!$this->tableExists($oldName)) {
            $this->logMigration('error', "Table {$oldName} does not exist");
            return false;
        }

        if ($this->tableExists($newName)) {
            $this->logMigration('error', "Table {$newName} already exists");
            return false;
        }

        $sql = "RENAME TABLE `{$oldName}` TO `{$newName}`";
        return $this->executeSql($sql);
    }

    /**
     * Copy data between tables
     */
    protected function copyData(string $sourceTable, string $targetTable, array $columnMapping = []): bool
    {
        if (!$this->tableExists($sourceTable)) {
            $this->logMigration('error', "Source table {$sourceTable} does not exist");
            return false;
        }

        if (!$this->tableExists($targetTable)) {
            $this->logMigration('error', "Target table {$targetTable} does not exist");
            return false;
        }

        if (empty($columnMapping)) {
            // Copy all columns with same names
            $sql = "INSERT INTO `{$targetTable}` SELECT * FROM `{$sourceTable}`";
        } else {
            // Copy specific columns with mapping
            $sourceColumns = implode('`, `', array_keys($columnMapping));
            $targetColumns = implode('`, `', array_values($columnMapping));
            $sql = "INSERT INTO `{$targetTable}` (`{$targetColumns}`) SELECT `{$sourceColumns}` FROM `{$sourceTable}`";
        }

        return $this->executeSql($sql);
    }

    /**
     * Update data in table
     */
    protected function updateData(string $tableName, array $setData, array $whereConditions = []): bool
    {
        if (!$this->tableExists($tableName)) {
            $this->logMigration('error', "Table {$tableName} does not exist");
            return false;
        }

        $setParts = [];
        $params = [];

        foreach ($setData as $column => $value) {
            $setParts[] = "`{$column}` = %s";
            $params[] = $value;
        }

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts);

        if (!empty($whereConditions)) {
            $whereParts = [];
            foreach ($whereConditions as $column => $value) {
                $whereParts[] = "`{$column}` = %s";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        return $this->executeSql($sql, $params);
    }

    /**
     * Get table row count
     */
    protected function getRowCount(string $tableName): int
    {
        if (!$this->tableExists($tableName)) {
            return 0;
        }

        $result = $this->wpdb->get_var("SELECT COUNT(*) FROM `{$tableName}`");
        return (int)$result;
    }

    /**
     * Backup table data before migration
     */
    protected function backupTable(string $tableName): bool
    {
        if (!$this->tableExists($tableName)) {
            return true;
        }

        $backupTableName = $tableName . '_backup_' . date('Ymd_His');
        $sql = "CREATE TABLE `{$backupTableName}` LIKE `{$tableName}`";
        
        if (!$this->executeSql($sql)) {
            return false;
        }

        $sql = "INSERT INTO `{$backupTableName}` SELECT * FROM `{$tableName}`";
        return $this->executeSql($sql);
    }
}
