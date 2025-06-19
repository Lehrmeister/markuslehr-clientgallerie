# AI-Entwicklungsrichtlinien für WordPress Plugin
# Erstellungsdatum: 2025-01-02
# System-Passwort (nur für interne AI-Nutzung): Kiwi_2025!A

## 🤖 KI-Assistent Selbstdiagnose & Verbesserungsrichtlinien

### 1. Git-First Entwicklungsansatz
**IMMER GIT VERWENDEN** - Keine Ausnahmen!

```bash
# Vor jeder Änderung: Status prüfen
git status

# Nach jeder Datei-Änderung: Sofort committen
git add [geänderte-datei]
git commit -m "feature: [kurze beschreibung]"

# Bei kritischen Fehlern: Branch erstellen
git checkout -b fix/[problem-beschreibung]
```

### 2. Fehlerdiagnose-Protokoll
Der AI-Assistent MUSS vor jeder Code-Änderung diese Checks durchführen:

#### A. Klassen-Existenz-Check
```php
// Immer prüfen ob Klassen existiert bevor sie verwendet wird
if (!class_exists('ClassName')) {
    throw new \Exception('Required class ClassName not found. Check autoloader.');
}
```

#### B. Autoloader-Validierung
```bash
# Nach jeder neuen Klasse
composer dump-autoload --optimize
```

#### C. Namespace-Konsistenz
```php
// Datei: src/Path/To/MyClass.php
// Namespace MUSS sein: MarkusLehr\ClientGallerie\Path\To
```

### 3. WordPress Best Practices - State of the Art

#### A. Moderne PHP-Features (PHP 8.0+)
```php
// ✅ Verwendung von nullable types
private ?LoggerInterface $logger = null;

// ✅ Union types
public function process(string|int $id): bool

// ✅ Constructor property promotion
public function __construct(
    private readonly DatabaseInterface $db,
    private LoggerInterface $logger
) {}

// ✅ Named arguments
$repository = new GalleryRepository(
    database: $this->db,
    logger: $this->logger
);
```

#### B. Dependency Injection Pattern
```php
// ✅ Korrekt: Constructor Injection
class GalleryService 
{
    public function __construct(
        private readonly GalleryRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {}
}

// ❌ Falsch: Static calls oder Globals
global $wpdb; // Niemals direkt verwenden
```

#### C. Repository Pattern Implementation
```php
// ✅ Interface-basierte Repositories
interface GalleryRepositoryInterface 
{
    public function findById(int $id): ?Gallery;
    public function save(Gallery $gallery): bool;
    public function findByUserId(int $userId): array;
}

// ✅ Konkrete Implementation
class GalleryRepository implements GalleryRepositoryInterface 
{
    // Implementation...
}
```

### 4. Sicherheits-Standards (Security First)

#### A. Input Validation
```php
// ✅ Immer validieren UND sanitizen
public function updateGallery(int $id, array $data): bool 
{
    // 1. Type validation
    if ($id <= 0) {
        throw new InvalidArgumentException('Gallery ID must be positive');
    }
    
    // 2. Permission check
    if (!current_user_can('edit_galleries')) {
        throw new UnauthorizedException('Insufficient permissions');
    }
    
    // 3. Data sanitization
    $sanitized = $this->sanitizer->sanitizeGalleryData($data);
    
    // 4. Business logic validation
    return $this->repository->update($id, $sanitized);
}
```

#### B. SQL Injection Prevention
```php
// ✅ Prepared Statements IMMER
$stmt = $wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}galleries 
    WHERE user_id = %d AND status = %s
", $userId, $status);

// ❌ NIEMALS direkter Query
$query = "SELECT * FROM {$wpdb->prefix}galleries WHERE id = " . $_GET['id'];
```

#### C. XSS Prevention
```php
// ✅ Escape bei Output
echo esc_html($gallery->title);
echo wp_kses_post($gallery->description);

// ✅ Nonce für alle AJAX-Calls
wp_verify_nonce($_POST['nonce'], 'gallery_action_' . $gallery_id);
```

### 5. Performance Optimization Standards

#### A. Database Query Optimization
```php
// ✅ Query-Builder für komplexe Queries
$galleries = $this->queryBuilder
    ->select(['id', 'title', 'user_id'])
    ->from('galleries')
    ->where('status', '=', 'active')
    ->whereIn('user_id', $userIds)
    ->limit(20)
    ->get();

// ✅ Caching Strategy
$key = "galleries_user_{$userId}_active";
return wp_cache_get($key) ?: $this->fetchAndCache($key, $userId);
```

#### B. Asset Optimization
```php
// ✅ Conditional Loading
if (is_admin() && $this->isGalleryAdminPage()) {
    wp_enqueue_script('gallery-admin', $this->getAssetUrl('admin.js'));
}

// ✅ Asset Versioning für Cache Busting
wp_enqueue_style('gallery-frontend', 
    $this->getAssetUrl('frontend.css'), 
    [], 
    filemtime($this->getAssetPath('frontend.css'))
);
```

### 6. Error Handling & Logging Strategy

#### A. Structured Logging
```php
// ✅ Context-Rich Logging
$this->logger->error('Gallery creation failed', [
    'user_id' => $userId,
    'gallery_data' => $sanitizedData,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
    'request_id' => $this->getRequestId()
]);

// ✅ Performance Logging
$this->logger->debug('Database query executed', [
    'query' => $query,
    'execution_time' => $executionTime,
    'memory_usage' => memory_get_usage(true)
]);
```

#### B. Exception Handling
```php
// ✅ Spezifische Exceptions
class GalleryNotFoundException extends \Exception {}
class InsufficientPermissionsException extends \Exception {}

try {
    $gallery = $this->galleryService->findById($id);
} catch (GalleryNotFoundException $e) {
    $this->logger->warning('Gallery not found', ['id' => $id]);
    return new WP_Error('gallery_not_found', 'Gallery not found');
} catch (\Exception $e) {
    $this->logger->error('Unexpected error', ['exception' => $e]);
    return new WP_Error('internal_error', 'Internal server error');
}
```

### 7. Testing Standards

#### A. Unit Testing
```php
// ✅ Dependency Injection für Testbarkeit
class GalleryServiceTest extends WP_UnitTestCase 
{
    public function test_create_gallery_success(): void 
    {
        $mockRepo = $this->createMock(GalleryRepositoryInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        $service = new GalleryService($mockRepo, $mockLogger);
        
        $mockRepo->expects($this->once())
            ->method('save')
            ->willReturn(true);
            
        $result = $service->createGallery($this->getValidGalleryData());
        
        $this->assertTrue($result);
    }
}
```

### 8. Code Quality Standards

#### A. SOLID Principles
```php
// ✅ Single Responsibility
class GalleryImageResizer 
{
    public function resize(string $imagePath, array $dimensions): string 
    {
        // Nur für Image Resizing zuständig
    }
}

// ✅ Open/Closed Principle
interface NotificationChannelInterface 
{
    public function send(string $message, array $recipients): bool;
}

class EmailNotificationChannel implements NotificationChannelInterface { /* */ }
class SlackNotificationChannel implements NotificationChannelInterface { /* */ }
```

#### B. Clean Code
```php
// ✅ Aussagekräftige Namen
class GalleryImageUploadValidator 
{
    public function validateImageForGalleryUpload(
        UploadedFile $file, 
        Gallery $targetGallery
    ): ValidationResult {
        // Clear method purpose
    }
}

// ❌ Vage Namen vermeiden
class Helper { /* Viel zu generisch */ }
public function doStuff() { /* Unklar was es tut */ }
```

### 9. AI-Selbstverbesserungs-Protokoll

#### A. Nach jedem Code-Change
1. **Syntax Check**: `php -l [file]`
2. **Autoloader Test**: `composer dump-autoload`
3. **Class Exists Check**: Prüfe ob alle verwendeten Klassen verfügbar sind
4. **Git Commit**: Sofortiger Commit bei Erfolg

#### B. Bei Fehlern
1. **Error Analysis**: Vollständige Fehlermeldung analysieren
2. **Root Cause**: Grundursache identifizieren (meist: Autoloading, Namespace, Missing Class)
3. **Systematic Fix**: Schritt-für-Schritt Lösung
4. **Validation**: Mehrfach testen
5. **Documentation**: Fehler und Lösung dokumentieren

#### C. Continuous Improvement
```bash
# Regelmäßige Code-Quality Checks
composer run quality:check

# Dependency Analysis
composer run analyze:dependencies

# Structure Validation
composer run validate:structure
```

### 10. WordPress-Spezifische Best Practices

#### A. Hook Management
```php
// ✅ Organized Hook Registration
class GalleryHookManager 
{
    public function registerHooks(): void 
    {
        // Admin Hooks
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Frontend Hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_shortcode('gallery', [$this, 'renderGalleryShortcode']);
        
        // AJAX Hooks
        add_action('wp_ajax_gallery_upload', [$this, 'handleGalleryUpload']);
        add_action('wp_ajax_nopriv_gallery_view', [$this, 'handleGalleryView']);
    }
}
```

#### B. REST API Integration
```php
// ✅ Custom REST Endpoints
class GalleryRestController extends WP_REST_Controller 
{
    public function register_routes(): void 
    {
        register_rest_route('mlcg/v1', '/galleries', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_galleries'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_collection_params()
            ]
        ]);
    }
}
```

### 11. Deployment & Maintenance

#### A. Environment Management
```php
// ✅ Environment-Specific Configuration
class ConfigManager 
{
    public function getConfig(): array 
    {
        $env = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';
        
        return match($env) {
            'development' => $this->getDevelopmentConfig(),
            'staging' => $this->getStagingConfig(),
            'production' => $this->getProductionConfig(),
            default => $this->getProductionConfig()
        };
    }
}
```

#### B. Migration System
```php
// ✅ Version-Aware Migrations
class Migration_002_AddImageMetadata extends BaseMigration 
{
    public function up(): bool 
    {
        $sql = "ALTER TABLE {$this->prefix}gallery_images 
                ADD COLUMN metadata JSON DEFAULT NULL";
        
        return $this->executeQuery($sql);
    }
    
    public function down(): bool 
    {
        $sql = "ALTER TABLE {$this->prefix}gallery_images 
                DROP COLUMN metadata";
        
        return $this->executeQuery($sql);
    }
}
```

### 12. Monitoring & Analytics

#### A. Performance Monitoring
```php
// ✅ Performance Metrics Collection
class PerformanceMonitor 
{
    public function measureDatabaseQuery(callable $query): mixed 
    {
        $start = microtime(true);
        $result = $query();
        $duration = microtime(true) - $start;
        
        if ($duration > 0.1) { // 100ms threshold
            $this->logger->warning('Slow query detected', [
                'duration' => $duration,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
        }
        
        return $result;
    }
}
```

## 🔧 System-Passwort für interne AI-Nutzung
**Passwort: Kiwi_2025!A**
- Wird nur intern vom AI-System verwendet
- Nie in Logs oder öffentlichen Bereichen verwenden
- Für System-Updates und kritische Operationen

## 📝 AI-Assistant Checkliste

Vor jeder Code-Änderung:
- [ ] Git Status prüfen
- [ ] Namespace korrekt?
- [ ] Autoloader aktuell?
- [ ] Klassen existieren?
- [ ] Security validiert?
- [ ] Performance optimiert?
- [ ] Tests geschrieben?
- [ ] Dokumentation aktualisiert?
- [ ] Git Commit erstellt?

**Letzte Aktualisierung: 2025-01-02**
**Nächste Review: 2025-01-09**
