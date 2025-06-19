<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Database Installer für Plugin-Tabellen
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database
 * @author Markus Lehr
 * @since 1.0.0
 */
class Installer 
{
    private \wpdb $wpdb;
    private string $charset;
    
    public function __construct() 
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset = $wpdb->get_charset_collate();
    }
    
    public function install(): void 
    {
        $logger = LoggerRegistry::getLogger();
        $logger?->info('Database installation started');
        
        $this->createClientsTable();
        $this->createLogEntriesTable();
        
        $logger?->info('Database installation completed');
    }
    
    private function createClientsTable(): void 
    {
        $tableName = $this->wpdb->prefix . 'mlcg_clients';
        
        $sql = "CREATE TABLE $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            company varchar(255) DEFAULT NULL,
            access_key varchar(64) NOT NULL,
            access_expires datetime DEFAULT NULL,
            permissions longtext DEFAULT NULL COMMENT 'JSON permissions',
            settings longtext DEFAULT NULL COMMENT 'JSON settings',
            status enum('active','inactive','blocked') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY access_key (access_key),
            KEY status (status),
            KEY created_by (created_by)
        ) $this->charset;";
        
        $this->executeSql($sql, $tableName);
    }
    
    private function createLogEntriesTable(): void 
    {
        $tableName = $this->wpdb->prefix . 'mlcg_log_entries';
        
        $sql = "CREATE TABLE $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level enum('emergency','alert','critical','error','warning','notice','info','debug') NOT NULL,
            message text NOT NULL,
            context longtext DEFAULT NULL COMMENT 'JSON context',
            request_id varchar(32) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            url varchar(500) DEFAULT NULL,
            method varchar(10) DEFAULT NULL,
            memory_usage bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX level (level),
            INDEX request_id (request_id),
            INDEX user_id (user_id),
            INDEX created_at (created_at),
            PRIMARY KEY (id)
        ) $this->charset;";
        
        $this->executeSql($sql, $tableName);
    }
    
    private function executeSql(string $sql, string $tableName): void 
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $result = dbDelta($sql);
        
        $logger = LoggerRegistry::getLogger();
        
        if ($this->wpdb->last_error) {
            $logger?->error("Failed to create table: $tableName", [
                'error' => $this->wpdb->last_error,
                'sql' => $sql
            ]);
        } else {
            $logger?->info("Table created/updated: $tableName", [
                'result' => $result
            ]);
        }
    }
    
    public function uninstall(): void 
    {
        $logger = LoggerRegistry::getLogger();
        $logger?->info('Database uninstallation started');
        
        $tables = [
            $this->wpdb->prefix . 'mlcg_clients',
            $this->wpdb->prefix . 'mlcg_log_entries'
        ];
        
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            $this->wpdb->query($sql);
            
            $logger?->info("Table dropped: $table");
        }
        
        $logger?->info('Database uninstallation completed');
    }
}
