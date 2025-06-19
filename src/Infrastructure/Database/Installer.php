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
        
        $this->createGalleriesTable();
        $this->createImagesTable();
        $this->createClientsTable();
        $this->createRatingsTable();
        $this->createLogEntriesTable();
        
        $logger?->info('Database installation completed');
    }
    
    private function createGalleriesTable(): void 
    {
        $tableName = $this->wpdb->prefix . 'mlcg_galleries';
        
        $sql = "CREATE TABLE $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            slug varchar(255) NOT NULL,
            client_id bigint(20) unsigned DEFAULT NULL,
            status enum('draft','published','archived') DEFAULT 'draft',
            settings longtext DEFAULT NULL COMMENT 'JSON settings',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY client_id (client_id),
            KEY status (status),
            KEY created_by (created_by)
        ) $this->charset;";
        
        $this->executeSql($sql, $tableName);
    }
    
    private function createImagesTable(): void 
    {
        $tableName = $this->wpdb->prefix . 'mlcg_images';
        
        $sql = "CREATE TABLE $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            gallery_id bigint(20) unsigned NOT NULL,
            filename varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) unsigned DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            title varchar(255) DEFAULT NULL,
            description text,
            alt_text varchar(255) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON metadata',
            status enum('active','hidden','deleted') DEFAULT 'active',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            uploaded_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY gallery_id (gallery_id),
            KEY filename (filename),
            KEY status (status),
            KEY sort_order (sort_order),
            KEY uploaded_by (uploaded_by)
        ) $this->charset;";
        
        $this->executeSql($sql, $tableName);
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
    
    private function createRatingsTable(): void 
    {
        $tableName = $this->wpdb->prefix . 'mlcg_ratings';
        
        $sql = "CREATE TABLE $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            image_id bigint(20) unsigned NOT NULL,
            client_id bigint(20) unsigned NOT NULL,
            rating enum('selected','rejected','favorite') NOT NULL,
            comment text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_rating (image_id, client_id),
            KEY image_id (image_id),
            KEY client_id (client_id),
            KEY rating (rating)
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
            $this->wpdb->prefix . 'mlcg_ratings',
            $this->wpdb->prefix . 'mlcg_images', 
            $this->wpdb->prefix . 'mlcg_galleries',
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
