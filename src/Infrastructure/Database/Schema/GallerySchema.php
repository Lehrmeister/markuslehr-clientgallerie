<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Gallery Schema
 * 
 * Defines the database schema for galleries table
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class GallerySchema extends BaseSchema
{
    protected function getTableSuffix(): string
    {
        return 'ml_clientgallerie_galleries';
    }

    protected function getCreateTableSQL(): string
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            description text DEFAULT NULL,
            status enum('draft','published','archived') NOT NULL DEFAULT 'draft',
            client_id bigint(20) unsigned NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            settings longtext DEFAULT NULL COMMENT 'JSON gallery settings',
            image_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            UNIQUE KEY unique_slug (slug),
            KEY idx_client_id (client_id),
            KEY idx_status (status),
            KEY idx_client_status (client_id, status),
            KEY idx_created_at (created_at),
            KEY idx_sort_order (client_id, sort_order),
            
            CONSTRAINT fk_gallery_client 
                FOREIGN KEY (client_id) 
                REFERENCES {$this->wpdb->prefix}ml_clientgallerie_clients(id) 
                ON DELETE CASCADE
        ) {$this->charset}";
    }

    /**
     * Get indexes for this table
     */
    public function getIndexes(): array
    {
        return [
            'unique_slug' => [
                'type' => 'UNIQUE',
                'columns' => ['slug']
            ],
            'idx_client_id' => [
                'type' => 'INDEX',
                'columns' => ['client_id']
            ],
            'idx_status' => [
                'type' => 'INDEX', 
                'columns' => ['status']
            ],
            'idx_client_status' => [
                'type' => 'INDEX',
                'columns' => ['client_id', 'status']
            ],
            'idx_created_at' => [
                'type' => 'INDEX',
                'columns' => ['created_at']
            ],
            'idx_sort_order' => [
                'type' => 'INDEX',
                'columns' => ['client_id', 'sort_order']
            ]
        ];
    }

    /**
     * Get constraints for this table
     */
    public function getConstraints(): array
    {
        return [
            'fk_gallery_client' => [
                'type' => 'FOREIGN KEY',
                'columns' => ['client_id'],
                'references' => [
                    'table' => $this->wpdb->prefix . 'ml_clientgallerie_clients',
                    'columns' => ['id']
                ],
                'on_delete' => 'CASCADE'
            ]
        ];
    }

    /**
     * Get default data for this table
     */
    public function getDefaultData(): array
    {
        return [
            // No default galleries - they are created by users
        ];
    }

    /**
     * Validate table structure after creation
     */
    public function validateStructure(): bool
    {
        // Check if table exists
        $tableExists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->tableName
            )
        );

        if (!$tableExists) {
            return false;
        }

        // Check if required columns exist
        $requiredColumns = [
            'id', 'name', 'slug', 'description', 'status', 
            'client_id', 'sort_order', 'settings', 'image_count',
            'created_at', 'updated_at'
        ];

        $existingColumns = $this->wpdb->get_col(
            "DESCRIBE {$this->tableName}"
        );

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $existingColumns, true)) {
                return false;
            }
        }

        return true;
    }
}
