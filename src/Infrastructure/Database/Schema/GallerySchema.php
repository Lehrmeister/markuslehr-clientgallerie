<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Schema für Galerie-Tabelle mit erweiterten Features
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class GallerySchema extends BaseSchema 
{
    protected function getTableSuffix(): string 
    {
        return 'mlcg_galleries';
    }
    
    protected function getCreateTableSQL(): string 
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            slug varchar(255) NOT NULL,
            client_id bigint(20) unsigned DEFAULT NULL,
            photographer_id bigint(20) unsigned DEFAULT NULL,
            status enum('draft','published','archived','client_review') DEFAULT 'draft',
            visibility enum('public','private','password_protected') DEFAULT 'private',
            password varchar(255) DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            download_enabled tinyint(1) DEFAULT 0,
            watermark_enabled tinyint(1) DEFAULT 1,
            settings longtext DEFAULT NULL COMMENT 'JSON settings for gallery behavior',
            seo_title varchar(255) DEFAULT NULL,
            seo_description text DEFAULT NULL,
            social_image_id bigint(20) unsigned DEFAULT NULL,
            template varchar(100) DEFAULT 'default',
            sort_order int(11) DEFAULT 0,
            view_count bigint(20) unsigned DEFAULT 0,
            download_count bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY client_id (client_id),
            KEY photographer_id (photographer_id),
            KEY status (status),
            KEY visibility (visibility),
            KEY expires_at (expires_at),
            KEY created_by (created_by),
            KEY updated_by (updated_by),
            KEY sort_order (sort_order),
            KEY published_at (published_at),
            FULLTEXT KEY search_content (title, description)
        ) {$this->charset};";
    }
    
    protected function afterCreate(): void 
    {
        // Trigger für automatische Slug-Generierung
        $this->createSlugTrigger();
        
        // Views für häufige Queries
        $this->createPublishedGalleriesView();
        $this->createClientAccessView();
    }
    
    private function createSlugTrigger(): void 
    {
        // MySQL Trigger für automatische Slug-Generierung falls leer
        $sql = "
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_slug_trigger 
        BEFORE INSERT ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.slug = '' OR NEW.slug IS NULL THEN
                SET NEW.slug = LOWER(REPLACE(REPLACE(NEW.title, ' ', '-'), '--', '-'));
            END IF;
        END;";
        
        $this->wpdb->query($sql);
    }
    
    private function createPublishedGalleriesView(): void 
    {
        $viewName = $this->tableName . '_published';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, title, description, slug, client_id, photographer_id,
            expires_at, download_enabled, view_count, download_count,
            created_at, published_at, created_by
        FROM {$this->tableName}
        WHERE status = 'published' 
        AND (expires_at IS NULL OR expires_at > NOW())
        AND visibility IN ('public', 'password_protected');";
        
        $this->wpdb->query($sql);
    }
    
    private function createClientAccessView(): void 
    {
        $viewName = $this->tableName . '_client_access';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            g.id, g.title, g.slug, g.client_id, g.status, g.visibility,
            g.expires_at, g.download_enabled, g.created_at,
            c.name as client_name, c.email as client_email,
            c.access_key, c.access_expires
        FROM {$this->tableName} g
        LEFT JOIN {$this->wpdb->prefix}mlcg_clients c ON g.client_id = c.id
        WHERE g.status IN ('published', 'client_review')
        AND (g.expires_at IS NULL OR g.expires_at > NOW())
        AND (c.access_expires IS NULL OR c.access_expires > NOW())
        AND c.status = 'active';";
        
        $this->wpdb->query($sql);
    }
    
    public function validate(): array 
    {
        $issues = parent::validate();
        
        if ($this->exists()) {
            // Prüfe kritische Constraints
            $slugDuplicates = $this->wpdb->get_var(
                "SELECT COUNT(*) - COUNT(DISTINCT slug) FROM {$this->tableName}"
            );
            
            if ($slugDuplicates > 0) {
                $issues[] = "Duplicate slugs found in galleries table";
            }
            
            // Prüfe verwaiste Galerien
            $orphanedGalleries = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName} g
                LEFT JOIN {$this->wpdb->prefix}mlcg_clients c ON g.client_id = c.id
                WHERE g.client_id IS NOT NULL AND c.id IS NULL
            ");
            
            if ($orphanedGalleries > 0) {
                $issues[] = "$orphanedGalleries galleries with invalid client_id found";
            }
        }
        
        return $issues;
    }
}
