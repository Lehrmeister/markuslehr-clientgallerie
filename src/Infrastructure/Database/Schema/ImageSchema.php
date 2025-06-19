<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Image Schema
 * 
 * Defines the database schema for images table
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class ImageSchema extends BaseSchema
{
    protected function getTableSuffix(): string
    {
        return 'ml_clientgallerie_images';
    }

    protected function getCreateTableSQL(): string
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            gallery_id bigint(20) unsigned NOT NULL,
            filename varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            title varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            alt_text varchar(255) DEFAULT NULL,
            file_size bigint(20) unsigned NOT NULL,
            mime_type varchar(100) NOT NULL,
            width int(11) unsigned NOT NULL,
            height int(11) unsigned NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            status enum('uploaded','processing','ready','error') NOT NULL DEFAULT 'uploaded',
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            metadata longtext DEFAULT NULL COMMENT 'JSON metadata (EXIF, etc.)',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            UNIQUE KEY unique_gallery_filename (gallery_id, filename),
            KEY idx_gallery_id (gallery_id),
            KEY idx_status (status),
            KEY idx_gallery_sort (gallery_id, sort_order),
            KEY idx_featured (gallery_id, is_featured),
            KEY idx_created_at (created_at),
            
            CONSTRAINT fk_image_gallery 
                FOREIGN KEY (gallery_id) 
                REFERENCES {$this->wpdb->prefix}ml_clientgallerie_galleries(id) 
                ON DELETE CASCADE
        ) {$this->charset}";
    }

    /**
     * Get indexes for this table
     */
    public function getIndexes(): array
    {
        return [
            'unique_gallery_filename' => [
                'type' => 'UNIQUE',
                'columns' => ['gallery_id', 'filename']
            ],
            'idx_gallery_id' => [
                'type' => 'INDEX',
                'columns' => ['gallery_id']
            ],
            'idx_status' => [
                'type' => 'INDEX', 
                'columns' => ['status']
            ],
            'idx_gallery_sort' => [
                'type' => 'INDEX',
                'columns' => ['gallery_id', 'sort_order']
            ],
            'idx_featured' => [
                'type' => 'INDEX',
                'columns' => ['gallery_id', 'is_featured']
            ],
            'idx_created_at' => [
                'type' => 'INDEX',
                'columns' => ['created_at']
            ]
        ];
    }

    /**
     * Get constraints for this table
     */
    public function getConstraints(): array
    {
        return [
            'fk_image_gallery' => [
                'type' => 'FOREIGN KEY',
                'columns' => ['gallery_id'],
                'references' => [
                    'table' => $this->wpdb->prefix . 'ml_clientgallerie_galleries',
                    'columns' => ['id']
                ],
                'on_delete' => 'CASCADE'
            ]
        ];
    }

    /**
     * Get default data to insert after table creation
     */
    public function getDefaultData(): array
    {
        return [
            // No default data for images table
        ];
    }

    /**
     * Get required WordPress capabilities for this table
     */
    public function getRequiredCapabilities(): array
    {
        return [
            'upload_files',
            'delete_files',
            'edit_files'
        ];
    }

    /**
     * Validate table-specific data
     */
    public function validateData(array $data): array
    {
        $errors = [];

        // Validate required fields
        if (empty($data['gallery_id'])) {
            $errors[] = 'Gallery ID is required';
        }

        if (empty($data['filename'])) {
            $errors[] = 'Filename is required';
        }

        if (empty($data['original_filename'])) {
            $errors[] = 'Original filename is required';
        }

        if (empty($data['file_size']) || !is_numeric($data['file_size'])) {
            $errors[] = 'Valid file size is required';
        }

        if (empty($data['mime_type'])) {
            $errors[] = 'MIME type is required';
        }

        if (empty($data['width']) || !is_numeric($data['width'])) {
            $errors[] = 'Valid width is required';
        }

        if (empty($data['height']) || !is_numeric($data['height'])) {
            $errors[] = 'Valid height is required';
        }

        return $errors;
    }
}
