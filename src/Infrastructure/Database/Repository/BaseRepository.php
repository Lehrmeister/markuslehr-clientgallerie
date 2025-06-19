<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Abstract Base Repository mit gemeinsamen Funktionen
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Repository
 * @author Markus Lehr
 * @since 1.0.0
 */
abstract class BaseRepository implements RepositoryInterface 
{
    protected \wpdb $wpdb;
    protected string $tableName;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $casts = [];
    
    public function __construct() 
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . $this->getTableSuffix();
    }
    
    /**
     * Gibt den Tabellen-Suffix zurück
     */
    abstract protected function getTableSuffix(): string;
    
    /**
     * Findet eine Entität anhand der ID
     */
    public function findById(int $id): ?array 
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = %d",
            $id
        );
        
        $result = $this->wpdb->get_row($sql, ARRAY_A);
        
        if ($result) {
            return $this->castValues($result);
        }
        
        return null;
    }
    
    /**
     * Findet alle Entitäten
     */
    public function findAll(array $options = []): array 
    {
        $sql = "SELECT * FROM {$this->tableName}";
        
        // WHERE-Klausel
        if (!empty($options['where'])) {
            $sql .= " WHERE " . $options['where'];
        }
        
        // ORDER BY
        if (!empty($options['order_by'])) {
            $sql .= " ORDER BY " . $options['order_by'];
        } else {
            $sql .= " ORDER BY {$this->primaryKey} DESC";
        }
        
        // LIMIT
        if (!empty($options['limit'])) {
            $limit = (int) $options['limit'];
            $offset = (int) ($options['offset'] ?? 0);
            $sql .= " LIMIT $offset, $limit";
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        if ($results) {
            return array_map([$this, 'castValues'], $results);
        }
        
        return [];
    }
    
    /**
     * Findet Entitäten anhand von Kriterien
     */
    public function findBy(array $criteria, array $options = []): array 
    {
        $whereClauses = [];
        $values = [];
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '%s'));
                $whereClauses[] = "$field IN ($placeholders)";
                $values = array_merge($values, $value);
            } else {
                $whereClauses[] = "$field = %s";
                $values[] = $value;
            }
        }
        
        if (empty($whereClauses)) {
            return $this->findAll($options);
        }
        
        $whereClause = implode(' AND ', $whereClauses);
        $options['where'] = $this->wpdb->prepare($whereClause, $values);
        
        return $this->findAll($options);
    }
    
    /**
     * Findet eine Entität anhand von Kriterien
     */
    public function findOneBy(array $criteria): ?array 
    {
        $options = ['limit' => 1];
        $results = $this->findBy($criteria, $options);
        
        return $results[0] ?? null;
    }
    
    /**
     * Erstellt eine neue Entität
     */
    public function create(array $data): int 
    {
        $filteredData = $this->filterFillable($data);
        $filteredData = $this->prepareForDatabase($filteredData);
        
        // Zeitstempel hinzufügen
        if (array_key_exists('created_at', $this->getCasts())) {
            $filteredData['created_at'] = current_time('mysql');
        }
        if (array_key_exists('updated_at', $this->getCasts())) {
            $filteredData['updated_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->insert($this->tableName, $filteredData);
        
        if ($result === false) {
            $logger = LoggerRegistry::getLogger();
            $logger?->error("Failed to create record in {$this->tableName}", [
                'data' => $filteredData,
                'error' => $this->wpdb->last_error
            ]);
            return 0;
        }
        
        $insertId = $this->wpdb->insert_id;
        
        $logger = LoggerRegistry::getLogger();
        $logger?->info("Created record in {$this->tableName}", [
            'id' => $insertId,
            'data' => $filteredData
        ]);
        
        return $insertId;
    }
    
    /**
     * Aktualisiert eine Entität
     */
    public function update(int $id, array $data): bool 
    {
        $filteredData = $this->filterFillable($data);
        $filteredData = $this->prepareForDatabase($filteredData);
        
        // Zeitstempel aktualisieren
        if (array_key_exists('updated_at', $this->getCasts())) {
            $filteredData['updated_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->update(
            $this->tableName,
            $filteredData,
            [$this->primaryKey => $id]
        );
        
        if ($result === false) {
            $logger = LoggerRegistry::getLogger();
            $logger?->error("Failed to update record in {$this->tableName}", [
                'id' => $id,
                'data' => $filteredData,
                'error' => $this->wpdb->last_error
            ]);
            return false;
        }
        
        $logger = LoggerRegistry::getLogger();
        $logger?->info("Updated record in {$this->tableName}", [
            'id' => $id,
            'data' => $filteredData,
            'affected_rows' => $result
        ]);
        
        return true;
    }
    
    /**
     * Löscht eine Entität
     */
    public function delete(int $id): bool 
    {
        $result = $this->wpdb->delete(
            $this->tableName,
            [$this->primaryKey => $id]
        );
        
        if ($result === false) {
            $logger = LoggerRegistry::getLogger();
            $logger?->error("Failed to delete record from {$this->tableName}", [
                'id' => $id,
                'error' => $this->wpdb->last_error
            ]);
            return false;
        }
        
        $logger = LoggerRegistry::getLogger();
        $logger?->info("Deleted record from {$this->tableName}", [
            'id' => $id,
            'affected_rows' => $result
        ]);
        
        return $result > 0;
    }
    
    /**
     * Zählt Entitäten anhand von Kriterien
     */
    public function count(array $criteria = []): int 
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableName}";
        
        if (!empty($criteria)) {
            $whereClauses = [];
            $values = [];
            
            foreach ($criteria as $field => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $whereClauses[] = "$field IN ($placeholders)";
                    $values = array_merge($values, $value);
                } else {
                    $whereClauses[] = "$field = %s";
                    $values[] = $value;
                }
            }
            
            if (!empty($whereClauses)) {
                $whereClause = implode(' AND ', $whereClauses);
                $sql .= " WHERE " . $this->wpdb->prepare($whereClause, $values);
            }
        }
        
        return (int) $this->wpdb->get_var($sql);
    }
    
    /**
     * Prüft ob eine Entität existiert
     */
    public function exists(int $id): bool 
    {
        $sql = $this->wpdb->prepare(
            "SELECT 1 FROM {$this->tableName} WHERE {$this->primaryKey} = %d LIMIT 1",
            $id
        );
        
        return (bool) $this->wpdb->get_var($sql);
    }
    
    /**
     * Filtert nur erlaubte Felder
     */
    protected function filterFillable(array $data): array 
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Bereitet Daten für die Datenbank vor
     */
    protected function prepareForDatabase(array $data): array 
    {
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $data[$key] = json_encode($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Castet Werte basierend auf Definition
     */
    protected function castValues(array $data): array 
    {
        foreach ($this->getCasts() as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            
            $value = $data[$field];
            
            switch ($type) {
                case 'int':
                case 'integer':
                    $data[$field] = (int) $value;
                    break;
                    
                case 'float':
                case 'double':
                    $data[$field] = (float) $value;
                    break;
                    
                case 'bool':
                case 'boolean':
                    $data[$field] = (bool) $value;
                    break;
                    
                case 'array':
                case 'json':
                    $data[$field] = $value ? json_decode($value, true) : [];
                    break;
                    
                case 'object':
                    $data[$field] = $value ? json_decode($value) : null;
                    break;
                    
                case 'datetime':
                    $data[$field] = $value ? new \DateTime($value) : null;
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Gibt Cast-Definitionen zurück
     */
    protected function getCasts(): array 
    {
        return $this->casts;
    }
    
    /**
     * Gibt den Tabellennamen zurück
     */
    public function getTableName(): string 
    {
        return $this->tableName;
    }
    
    /**
     * Transaktions-Support
     */
    public function transaction(callable $callback) 
    {
        $this->wpdb->query('START TRANSACTION');
        
        try {
            $result = $callback($this);
            $this->wpdb->query('COMMIT');
            return $result;
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }
    
    /**
     * Raw SQL Query mit Logging
     */
    protected function query(string $sql, array $params = []): array 
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        if ($this->wpdb->last_error) {
            $logger = LoggerRegistry::getLogger();
            $logger?->error("Database query error", [
                'sql' => $sql,
                'error' => $this->wpdb->last_error
            ]);
        }
        
        return $results ?: [];
    }
    
    /**
     * Validates that the table exists
     */
    public function validateTableExists(): bool
    {
        $sql = $this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->tableName
        );
        
        $result = $this->wpdb->get_var($sql);
        return !empty($result);
    }
}
