<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Base Schema Class für erweiterbares Datenbank-Schema
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
abstract class BaseSchema 
{
    protected \wpdb $wpdb;
    protected string $charset;
    protected string $tableName;
    
    public function __construct() 
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset = $wpdb->get_charset_collate();
        $this->tableName = $this->wpdb->prefix . $this->getTableSuffix();
    }
    
    /**
     * Gibt den Tabellen-Suffix zurück (z.B. 'mlcg_galleries')
     */
    abstract protected function getTableSuffix(): string;
    
    /**
     * Gibt die SQL-Definition für die Tabelle zurück
     */
    abstract protected function getCreateTableSQL(): string;
    
    /**
     * Erstellt oder aktualisiert die Tabelle
     */
    public function create(): bool 
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $sql = $this->getCreateTableSQL();
        $result = dbDelta($sql);
        
        $logger = LoggerRegistry::getLogger();
        
        if ($this->wpdb->last_error) {
            $logger?->error("Failed to create/update table: {$this->tableName}", [
                'error' => $this->wpdb->last_error,
                'sql' => $sql
            ]);
            return false;
        }
        
        $logger?->info("Table created/updated successfully: {$this->tableName}", [
            'result' => $result
        ]);
        
        // Post-Creation Hook für zusätzliche Aktionen
        $this->afterCreate();
        
        return true;
    }
    
    /**
     * Löscht die Tabelle
     */
    public function drop(): bool 
    {
        $sql = "DROP TABLE IF EXISTS {$this->tableName}";
        $result = $this->wpdb->query($sql);
        
        $logger = LoggerRegistry::getLogger();
        if ($result !== false) {
            $logger?->info("Table dropped successfully: {$this->tableName}");
            return true;
        } else {
            $logger?->error("Failed to drop table: {$this->tableName}", [
                'error' => $this->wpdb->last_error
            ]);
            return false;
        }
    }
    
    /**
     * Prüft ob die Tabelle existiert
     */
    public function exists(): bool 
    {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->tableName)
        );
        
        return $result === $this->tableName;
    }
    
    /**
     * Gibt den Tabellennamen zurück
     */
    public function getTableName(): string 
    {
        return $this->tableName;
    }
    
    /**
     * Hook für Aktionen nach der Tabellenerstellung
     */
    protected function afterCreate(): void 
    {
        // Override in Subklassen falls nötig
    }
    
    /**
     * Gibt Spalten-Definitionen für erweiterte Features zurück
     */
    protected function getExtendedColumns(): array 
    {
        return [
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
    }
    
    /**
     * Fügt Standard-Indizes hinzu
     */
    protected function getStandardIndexes(): array 
    {
        return [
            'KEY created_at (created_at)',
            'KEY updated_at (updated_at)'
        ];
    }
    
    /**
     * Validiert Schema-Anforderungen
     */
    public function validate(): array 
    {
        $issues = [];
        
        if (!$this->exists()) {
            $issues[] = "Table {$this->tableName} does not exist";
        }
        
        // Weitere Validierungen können hier hinzugefügt werden
        
        return $issues;
    }
}
