<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Schema für erweiterte Bewertungs- und Feedback-Tabelle
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class RatingSchema extends BaseSchema 
{
    protected function getTableSuffix(): string 
    {
        return 'mlcg_ratings';
    }
    
    protected function getCreateTableSQL(): string 
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            image_id bigint(20) unsigned NOT NULL,
            client_id bigint(20) unsigned NOT NULL,
            session_id varchar(64) DEFAULT NULL COMMENT 'Anonymous session tracking',
            rating enum('selected','rejected','favorite','maybe') NOT NULL,
            score tinyint(2) DEFAULT NULL COMMENT '1-10 score rating',
            comment text,
            feedback_category enum('technical','aesthetic','content','other') DEFAULT NULL,
            priority_level enum('low','medium','high','urgent') DEFAULT 'medium',
            feedback_tags varchar(500) DEFAULT NULL COMMENT 'Comma-separated tags',
            is_public tinyint(1) DEFAULT 0,
            is_anonymous tinyint(1) DEFAULT 0,
            client_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            device_info longtext DEFAULT NULL COMMENT 'JSON device information',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_rating (image_id, client_id),
            KEY image_id (image_id),
            KEY client_id (client_id),
            KEY session_id (session_id),
            KEY rating (rating),
            KEY score (score),
            KEY feedback_category (feedback_category),
            KEY priority_level (priority_level),
            KEY is_public (is_public),
            KEY created_at (created_at),
            KEY expires_at (expires_at),
            FULLTEXT KEY feedback_search (comment, feedback_tags),
            CONSTRAINT fk_rating_image FOREIGN KEY (image_id) 
                REFERENCES {$this->wpdb->prefix}mlcg_images(id) ON DELETE CASCADE,
            CONSTRAINT fk_rating_client FOREIGN KEY (client_id) 
                REFERENCES {$this->wpdb->prefix}mlcg_clients(id) ON DELETE CASCADE
        ) {$this->charset};";
    }
    
    protected function afterCreate(): void 
    {
        // Trigger für automatische Rating-Updates in Image-Tabelle
        $this->createRatingUpdateTrigger();
        
        // Views für Rating-Analytics
        $this->createRatingSummaryView();
        $this->createTopRatedImagesView();
        $this->createFeedbackAnalyticsView();
    }
    
    private function createRatingUpdateTrigger(): void 
    {
        $sql = "
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_update_image_ratings 
        AFTER INSERT ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            UPDATE {$this->wpdb->prefix}mlcg_images 
            SET 
                rating_count = (
                    SELECT COUNT(*) FROM {$this->tableName} 
                    WHERE image_id = NEW.image_id AND score IS NOT NULL
                ),
                rating_average = (
                    SELECT AVG(score) FROM {$this->tableName} 
                    WHERE image_id = NEW.image_id AND score IS NOT NULL
                )
            WHERE id = NEW.image_id;
        END;
        
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_update_image_ratings_on_update 
        AFTER UPDATE ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            UPDATE {$this->wpdb->prefix}mlcg_images 
            SET 
                rating_count = (
                    SELECT COUNT(*) FROM {$this->tableName} 
                    WHERE image_id = NEW.image_id AND score IS NOT NULL
                ),
                rating_average = (
                    SELECT AVG(score) FROM {$this->tableName} 
                    WHERE image_id = NEW.image_id AND score IS NOT NULL
                )
            WHERE id = NEW.image_id;
        END;
        
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_update_image_ratings_on_delete 
        AFTER DELETE ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            UPDATE {$this->wpdb->prefix}mlcg_images 
            SET 
                rating_count = (
                    SELECT COUNT(*) FROM {$this->tableName} 
                    WHERE image_id = OLD.image_id AND score IS NOT NULL
                ),
                rating_average = (
                    SELECT AVG(score) FROM {$this->tableName} 
                    WHERE image_id = OLD.image_id AND score IS NOT NULL
                )
            WHERE id = OLD.image_id;
        END;";
        
        $this->wpdb->query($sql);
    }
    
    private function createRatingSummaryView(): void 
    {
        $viewName = $this->tableName . '_summary';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            i.id as image_id,
            i.filename,
            i.gallery_id,
            g.title as gallery_title,
            COUNT(r.id) as total_ratings,
            COUNT(CASE WHEN r.rating = 'selected' THEN 1 END) as selected_count,
            COUNT(CASE WHEN r.rating = 'favorite' THEN 1 END) as favorite_count,
            COUNT(CASE WHEN r.rating = 'rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN r.rating = 'maybe' THEN 1 END) as maybe_count,
            AVG(r.score) as average_score,
            COUNT(CASE WHEN r.comment IS NOT NULL AND r.comment != '' THEN 1 END) as feedback_count
        FROM {$this->wpdb->prefix}mlcg_images i
        LEFT JOIN {$this->tableName} r ON i.id = r.image_id
        LEFT JOIN {$this->wpdb->prefix}mlcg_galleries g ON i.gallery_id = g.id
        GROUP BY i.id, i.filename, i.gallery_id, g.title;";
        
        $this->wpdb->query($sql);
    }
    
    private function createTopRatedImagesView(): void 
    {
        $viewName = $this->tableName . '_top_rated';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            r.image_id,
            i.filename,
            i.title,
            g.title as gallery_title,
            COUNT(CASE WHEN r.rating = 'favorite' THEN 1 END) as favorite_count,
            AVG(r.score) as average_score,
            COUNT(r.id) as total_ratings
        FROM {$this->tableName} r
        JOIN {$this->wpdb->prefix}mlcg_images i ON r.image_id = i.id
        JOIN {$this->wpdb->prefix}mlcg_galleries g ON i.gallery_id = g.id
        WHERE r.score IS NOT NULL
        GROUP BY r.image_id, i.filename, i.title, g.title
        HAVING total_ratings >= 3
        ORDER BY 
            favorite_count DESC,
            average_score DESC,
            total_ratings DESC
        LIMIT 100;";
        
        $this->wpdb->query($sql);
    }
    
    private function createFeedbackAnalyticsView(): void 
    {
        $viewName = $this->tableName . '_feedback_analytics';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            g.id as gallery_id,
            g.title as gallery_title,
            COUNT(r.id) as total_feedback,
            COUNT(CASE WHEN r.comment IS NOT NULL AND r.comment != '' THEN 1 END) as feedback_with_comments,
            COUNT(CASE WHEN r.priority_level = 'urgent' THEN 1 END) as urgent_feedback,
            COUNT(CASE WHEN r.priority_level = 'high' THEN 1 END) as high_priority_feedback,
            AVG(CASE WHEN r.score IS NOT NULL THEN r.score END) as average_score,
            COUNT(DISTINCT r.client_id) as unique_reviewers,
            MAX(r.created_at) as latest_feedback
        FROM {$this->wpdb->prefix}mlcg_galleries g
        LEFT JOIN {$this->wpdb->prefix}mlcg_images i ON g.id = i.gallery_id
        LEFT JOIN {$this->tableName} r ON i.id = r.image_id
        GROUP BY g.id, g.title
        HAVING total_feedback > 0
        ORDER BY latest_feedback DESC;";
        
        $this->wpdb->query($sql);
    }
    
    public function validate(): array 
    {
        $issues = parent::validate();
        
        if ($this->exists()) {
            // Prüfe verwaiste Ratings
            $orphanedRatings = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName} r
                LEFT JOIN {$this->wpdb->prefix}mlcg_images i ON r.image_id = i.id
                WHERE i.id IS NULL
            ");
            
            if ($orphanedRatings > 0) {
                $issues[] = "$orphanedRatings ratings with invalid image_id found";
            }
            
            // Prüfe doppelte Ratings
            $duplicateRatings = $this->wpdb->get_var("
                SELECT COUNT(*) - COUNT(DISTINCT CONCAT(image_id, '-', client_id))
                FROM {$this->tableName}
            ");
            
            if ($duplicateRatings > 0) {
                $issues[] = "$duplicateRatings duplicate ratings found";
            }
            
            // Prüfe ungültige Score-Werte
            $invalidScores = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE score IS NOT NULL AND (score < 1 OR score > 10)
            ");
            
            if ($invalidScores > 0) {
                $issues[] = "$invalidScores ratings with invalid score values found";
            }
        }
        
        return $issues;
    }
}
