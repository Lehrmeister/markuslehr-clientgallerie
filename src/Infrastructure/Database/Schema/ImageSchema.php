<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Schema für Bilder-Tabelle mit erweiterten Metadaten
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class ImageSchema extends BaseSchema 
{
    protected function getTableSuffix(): string 
    {
        return 'mlcg_images';
    }
    
    protected function getCreateTableSQL(): string 
    {
        return "CREATE TABLE {$this->tableName} (
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
            caption text DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            orientation enum('landscape','portrait','square') DEFAULT NULL,
            aspect_ratio decimal(5,3) DEFAULT NULL,
            dominant_color varchar(7) DEFAULT NULL,
            has_faces tinyint(1) DEFAULT 0,
            exif_data longtext DEFAULT NULL COMMENT 'JSON EXIF metadata',
            processing_data longtext DEFAULT NULL COMMENT 'JSON processing info',
            thumbnail_path varchar(500) DEFAULT NULL,
            medium_path varchar(500) DEFAULT NULL,
            large_path varchar(500) DEFAULT NULL,
            webp_path varchar(500) DEFAULT NULL,
            status enum('active','hidden','deleted','processing','error') DEFAULT 'active',
            visibility enum('public','private','client_only') DEFAULT 'client_only',
            download_enabled tinyint(1) DEFAULT 1,
            watermark_applied tinyint(1) DEFAULT 0,
            view_count bigint(20) unsigned DEFAULT 0,
            download_count bigint(20) unsigned DEFAULT 0,
            rating_average decimal(3,2) DEFAULT NULL,
            rating_count int(11) DEFAULT 0,
            tags varchar(500) DEFAULT NULL COMMENT 'Comma-separated tags',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            uploaded_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY gallery_id (gallery_id),
            KEY filename (filename),
            KEY status (status),
            KEY visibility (visibility),
            KEY sort_order (sort_order),
            KEY uploaded_by (uploaded_by),
            KEY orientation (orientation),
            KEY has_faces (has_faces),
            KEY rating_average (rating_average),
            KEY uploaded_at (uploaded_at),
            FULLTEXT KEY search_content (title, description, caption, alt_text),
            FULLTEXT KEY tags_search (tags),
            CONSTRAINT fk_image_gallery FOREIGN KEY (gallery_id) 
                REFERENCES {$this->wpdb->prefix}mlcg_galleries(id) ON DELETE CASCADE
        ) {$this->charset};";
    }
    
    protected function afterCreate(): void 
    {
        // Trigger für automatische Aspect Ratio Berechnung
        $this->createAspectRatioTrigger();
        
        // Trigger für Orientation Detection
        $this->createOrientationTrigger();
        
        // Views für verschiedene Image-States
        $this->createActiveImagesView();
        $this->createProcessingQueueView();
    }
    
    private function createAspectRatioTrigger(): void 
    {
        $sql = "
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_aspect_ratio_trigger 
        BEFORE INSERT ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.width > 0 AND NEW.height > 0 THEN
                SET NEW.aspect_ratio = NEW.width / NEW.height;
            END IF;
        END;
        
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_aspect_ratio_update_trigger 
        BEFORE UPDATE ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.width > 0 AND NEW.height > 0 THEN
                SET NEW.aspect_ratio = NEW.width / NEW.height;
            END IF;
        END;";
        
        $this->wpdb->query($sql);
    }
    
    private function createOrientationTrigger(): void 
    {
        $sql = "
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_orientation_trigger 
        BEFORE INSERT ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.width > 0 AND NEW.height > 0 THEN
                IF NEW.width > NEW.height THEN
                    SET NEW.orientation = 'landscape';
                ELSEIF NEW.height > NEW.width THEN
                    SET NEW.orientation = 'portrait';
                ELSE
                    SET NEW.orientation = 'square';
                END IF;
            END IF;
        END;
        
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_orientation_update_trigger 
        BEFORE UPDATE ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.width > 0 AND NEW.height > 0 THEN
                IF NEW.width > NEW.height THEN
                    SET NEW.orientation = 'landscape';
                ELSEIF NEW.height > NEW.width THEN
                    SET NEW.orientation = 'portrait';
                ELSE
                    SET NEW.orientation = 'square';
                END IF;
            END IF;
        END;";
        
        $this->wpdb->query($sql);
    }
    
    private function createActiveImagesView(): void 
    {
        $viewName = $this->tableName . '_active';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            i.*, g.title as gallery_title, g.status as gallery_status
        FROM {$this->tableName} i
        JOIN {$this->wpdb->prefix}mlcg_galleries g ON i.gallery_id = g.id
        WHERE i.status = 'active' 
        AND g.status IN ('published', 'client_review')
        ORDER BY i.sort_order ASC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createProcessingQueueView(): void 
    {
        $viewName = $this->tableName . '_processing_queue';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, gallery_id, filename, original_filename, file_path,
            status, uploaded_at, uploaded_by
        FROM {$this->tableName}
        WHERE status IN ('processing', 'error')
        ORDER BY uploaded_at ASC;";
        
        $this->wpdb->query($sql);
    }
    
    public function validate(): array 
    {
        $issues = parent::validate();
        
        if ($this->exists()) {
            // Prüfe verwaiste Bilder
            $orphanedImages = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName} i
                LEFT JOIN {$this->wpdb->prefix}mlcg_galleries g ON i.gallery_id = g.id
                WHERE g.id IS NULL
            ");
            
            if ($orphanedImages > 0) {
                $issues[] = "$orphanedImages images with invalid gallery_id found";
            }
            
            // Prüfe fehlende Dateien
            $missingFiles = $this->wpdb->get_results("
                SELECT id, filename, file_path 
                FROM {$this->tableName} 
                WHERE status = 'active' 
                LIMIT 10
            ");
            
            foreach ($missingFiles as $image) {
                if (!file_exists(ABSPATH . ltrim($image->file_path, '/'))) {
                    $issues[] = "Missing file for image ID {$image->id}: {$image->filename}";
                }
            }
            
            // Prüfe ungültige Aspect Ratios
            $invalidRatios = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE (width > 0 AND height > 0) 
                AND (aspect_ratio IS NULL OR ABS(aspect_ratio - (width/height)) > 0.01)
            ");
            
            if ($invalidRatios > 0) {
                $issues[] = "$invalidRatios images with incorrect aspect ratios found";
            }
        }
        
        return $issues;
    }
}
