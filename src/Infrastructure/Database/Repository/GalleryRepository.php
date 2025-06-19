<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

/**
 * Gallery Repository mit erweiterten Galerie-spezifischen Funktionen
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Repository
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryRepository extends BaseRepository 
{
    protected array $fillable = [
        'title', 'description', 'slug', 'client_id', 'photographer_id',
        'status', 'visibility', 'password', 'expires_at', 'download_enabled',
        'watermark_enabled', 'settings', 'seo_title', 'seo_description',
        'social_image_id', 'template', 'sort_order', 'created_by', 'updated_by'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'client_id' => 'int',
        'photographer_id' => 'int',
        'download_enabled' => 'bool',
        'watermark_enabled' => 'bool',
        'settings' => 'json',
        'social_image_id' => 'int',
        'sort_order' => 'int',
        'view_count' => 'int',
        'download_count' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime'
    ];
    
    protected function getTableSuffix(): string 
    {
        return 'mlcg_galleries';
    }
    
    /**
     * Findet Galerie anhand des Slugs
     */
    public function findBySlug(string $slug): ?array 
    {
        return $this->findOneBy(['slug' => $slug]);
    }
    
    /**
     * Findet alle Galerien eines Clients
     */
    public function findByClientId(int $clientId, array $options = []): array 
    {
        return $this->findBy(['client_id' => $clientId], $options);
    }
    
    /**
     * Findet alle Galerien eines Fotografen
     */
    public function findByPhotographerId(int $photographerId, array $options = []): array 
    {
        return $this->findBy(['photographer_id' => $photographerId], $options);
    }
    
    /**
     * Findet alle veröffentlichten Galerien
     */
    public function findPublished(array $options = []): array 
    {
        $criteria = ['status' => 'published'];
        $options['where'] = "status = 'published' AND (expires_at IS NULL OR expires_at > NOW())";
        
        return $this->findAll($options);
    }
    
    /**
     * Findet alle Galerien, die bald ablaufen
     */
    public function findExpiringSoon(int $days = 7): array 
    {
        $options = [
            'where' => $this->wpdb->prepare(
                "expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL %d DAY)",
                $days
            ),
            'order_by' => 'expires_at ASC'
        ];
        
        return $this->findAll($options);
    }
    
    /**
     * Sucht in Galerien
     */
    public function search(string $query, array $options = []): array 
    {
        $escapedQuery = $this->wpdb->esc_like($query);
        
        $options['where'] = $this->wpdb->prepare(
            "MATCH(title, description) AGAINST(%s IN BOOLEAN MODE) OR title LIKE %s OR description LIKE %s",
            $query,
            "%{$escapedQuery}%",
            "%{$escapedQuery}%"
        );
        
        $options['order_by'] = $this->wpdb->prepare(
            "MATCH(title, description) AGAINST(%s IN BOOLEAN MODE) DESC, created_at DESC",
            $query
        );
        
        return $this->findAll($options);
    }
    
    /**
     * Erstellt eine neue Galerie mit Auto-Slug
     */
    public function create(array $data): int 
    {
        // Auto-generate slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
        }
        
        return parent::create($data);
    }
    
    /**
     * Generiert einen eindeutigen Slug
     */
    public function generateUniqueSlug(string $title, int $excludeId = null): string 
    {
        $baseSlug = sanitize_title($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Prüft ob ein Slug bereits existiert
     */
    public function slugExists(string $slug, int $excludeId = null): bool 
    {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tableName} WHERE slug = %s",
            $slug
        );
        
        if ($excludeId) {
            $sql .= $this->wpdb->prepare(" AND id != %d", $excludeId);
        }
        
        return (bool) $this->wpdb->get_var($sql);
    }
    
    /**
     * Aktualisiert View-Counter
     */
    public function incrementViewCount(int $id): bool 
    {
        $sql = $this->wpdb->prepare(
            "UPDATE {$this->tableName} SET view_count = view_count + 1 WHERE id = %d",
            $id
        );
        
        return (bool) $this->wpdb->query($sql);
    }
    
    /**
     * Aktualisiert Download-Counter
     */
    public function incrementDownloadCount(int $id): bool 
    {
        $sql = $this->wpdb->prepare(
            "UPDATE {$this->tableName} SET download_count = download_count + 1 WHERE id = %d",
            $id
        );
        
        return (bool) $this->wpdb->query($sql);
    }
    
    /**
     * Gibt Galerie-Statistiken zurück
     */
    public function getStatistics(int $id): array 
    {
        $sql = $this->wpdb->prepare("
            SELECT 
                g.*,
                COUNT(i.id) as image_count,
                COUNT(CASE WHEN r.rating = 'selected' THEN 1 END) as selected_count,
                COUNT(CASE WHEN r.rating = 'favorite' THEN 1 END) as favorite_count,
                COUNT(CASE WHEN r.rating = 'rejected' THEN 1 END) as rejected_count,
                COUNT(DISTINCT r.client_id) as reviewer_count,
                AVG(r.score) as average_score
            FROM {$this->tableName} g
            LEFT JOIN {$this->wpdb->prefix}mlcg_images i ON g.id = i.gallery_id AND i.status = 'active'
            LEFT JOIN {$this->wpdb->prefix}mlcg_ratings r ON i.id = r.image_id
            WHERE g.id = %d
            GROUP BY g.id
        ", $id);
        
        $result = $this->wpdb->get_row($sql, ARRAY_A);
        
        if ($result) {
            return $this->castValues($result);
        }
        
        return [];
    }
    
    /**
     * Dupliziert eine Galerie
     */
    public function duplicate(int $id, array $overrides = []): int 
    {
        $original = $this->findById($id);
        if (!$original) {
            return 0;
        }
        
        // Bereite Daten für Duplikation vor
        unset($original['id'], $original['created_at'], $original['updated_at']);
        $original['title'] = ($overrides['title'] ?? $original['title']) . ' (Copy)';
        $original['slug'] = $this->generateUniqueSlug($original['title']);
        $original['status'] = 'draft';
        
        // Überschreibungen anwenden
        $original = array_merge($original, $overrides);
        
        return $this->create($original);
    }
    
    /**
     * Archiviert eine Galerie
     */
    public function archive(int $id): bool 
    {
        return $this->update($id, ['status' => 'archived']);
    }
    
    /**
     * Veröffentlicht eine Galerie
     */
    public function publish(int $id): bool 
    {
        return $this->update($id, [
            'status' => 'published',
            'published_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Setzt Galerie auf Draft zurück
     */
    public function unpublish(int $id): bool 
    {
        return $this->update($id, [
            'status' => 'draft',
            'published_at' => null
        ]);
    }
    
    /**
     * Bulk-Operationen
     */
    public function bulkUpdateStatus(array $ids, string $status): int 
    {
        if (empty($ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $params = array_merge([$status], $ids);
        
        $sql = $this->wpdb->prepare(
            "UPDATE {$this->tableName} SET status = %s WHERE id IN ($placeholders)",
            $params
        );
        
        return (int) $this->wpdb->query($sql);
    }
    
    /**
     * Bulk-Delete mit Abhängigkeiten
     */
    public function bulkDelete(array $ids): int 
    {
        if (empty($ids)) {
            return 0;
        }
        
        return $this->transaction(function() use ($ids) {
            $deleted = 0;
            
            foreach ($ids as $id) {
                if ($this->delete($id)) {
                    $deleted++;
                }
            }
            
            return $deleted;
        });
    }
}
