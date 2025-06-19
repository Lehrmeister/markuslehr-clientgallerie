# MarkusLehr ClientGallerie - Moderne Entwicklungsrichtlinien & Code-Architektur

## 🎯 Executive Summary

Ihre Plugin-Spezifikation ist sehr ambitioniert und marktreif konzipiert. Hier sind die modernen Entwicklungsansätze und Code-Strukturierungsempfehlungen für eine erfolgreiche, skalierbare und wartbare Implementierung.

---

## 🏗️ MODERNE ARCHITEKTUR-PRINZIPIEN

### 1. Domain-Driven Design (DDD)
```
/src/
├── Domain/               # Business Logic (Plugin-spezifisch)
│   ├── Gallery/
│   │   ├── Entity/      # Gallery, Image, Client Entities
│   │   ├── Repository/  # Data Access Contracts
│   │   ├── Service/     # Business Logic Services
│   │   └── ValueObject/ # Rating, Color, PickStatus
│   ├── Client/
│   └── Security/
├── Infrastructure/       # WordPress-spezifische Implementierung
│   ├── Database/        # WordPress wpdb Repositories
│   ├── Storage/         # File System, Image Processing
│   ├── Http/           # AJAX Handlers, REST API
│   └── Queue/          # Background Tasks
└── Application/         # Use Cases & Koordination
    ├── Command/         # CQRS Commands
    ├── Query/          # CQRS Queries
    └── Handler/        # Command/Query Handlers
```

### 2. CQRS (Command Query Responsibility Segregation)
```php
// Commands (Schreiboperationen)
interface CreateGalleryCommand {
    public function execute(CreateGalleryRequest $request): Gallery;
}

// Queries (Leseoperationen)
interface GetGalleryQuery {
    public function execute(GetGalleryRequest $request): GalleryView;
}

// Event-Driven Architecture
interface DomainEvent {
    public function occurredOn(): DateTimeImmutable;
    public function aggregateId(): string;
}
```

### 3. Hexagonal Architecture (Ports & Adapters)
```php
// Ports (Contracts)
interface ImageStoragePort {
    public function store(Image $image): StorageResult;
    public function retrieve(ImageId $id): Image;
}

// Adapters (Implementierungen)
class WordPressImageStorageAdapter implements ImageStoragePort {
    // WordPress-spezifische Implementierung
}

class CloudinaryImageStorageAdapter implements ImageStoragePort {
    // Cloud-Storage Alternative
}
```

---

## 🔧 MODERNE ENTWICKLUNGSTECHNIKEN

### 0. Sinnvolle Datei-Aufteilung & Code-Strukturbaum Prinzipien

**Problem:** Große Dateien, Code-Duplikation, verwaiste Test-Dateien führen zu Wartungsproblemen.

**Lösung:** Verantwortlichkeits-basierte Aufteilung + Live-Dependency-Tracking

```php
// SINNVOLLE DATEI-AUFTEILUNG (Single Responsibility Principle)
- Eine Klasse = Eine Datei = Eine Verantwortlichkeit
- Entities: 50-150 Zeilen (reine Datenstrukturen)
- Services: 100-300 Zeilen (komplexe Business Logic erlaubt)
- Controllers: 80-200 Zeilen (HTTP-Handling)
- Repositories: 150-400 Zeilen (Datenbank-Operationen können komplex sein)
- Value Objects: 30-100 Zeilen (einfache, unveränderliche Objekte)
- Tests: 200-500 Zeilen (ausführliche Tests sind wichtig!)

```php
// SINNVOLLE DATEI-AUFTEILUNG NACH VERANTWORTLICHKEIT (Single Responsibility Principle)

// ❌ SCHLECHT: Starre Zeilenlimits
class GalleryService {  // 500 Zeilen - "zu groß", aber logisch zusammenhängend
    public function createGallery() { /* komplexe Logik */ }
    public function validateGallery() { /* gehört dazu */ }
    public function persistGallery() { /* gehört dazu */ }
}

// ✅ GUT: Verantwortlichkeits-basierte Aufteilung
class GalleryCreationService {     // Eine klare Verantwortlichkeit
    public function createGallery() { /* 150 Zeilen komplexe Logik */ }
    public function validateCreationRequest() { /* gehört zur Creation */ }
}

class GalleryPersistenceService {  // Andere Verantwortlichkeit  
    public function saveGallery() { /* 120 Zeilen Persistierung */ }
    public function updateGalleryMetadata() { /* gehört zur Persistierung */ }
}

// INTELLIGENTE METRIKEN STATT ZEILENZÄHLUNG:
// - Zyklomatische Komplexität < 10 pro Methode
// - Kopplungsgrad < 7 eingehende Abhängigkeiten  
// - Kohäsionsgrad > 0.7 (LCOM-Metrik)
// - Anzahl öffentlicher Methoden < 15 pro Klasse
// - Methodenlänge < 20 Anweisungen (ohne Leerzeilen/Kommentare)

// AUFTEILUNG NACH FACHLICHKEIT, NICHT NACH ZEILEN
❌ Schlecht: GalleryService in 3 Dateien aufteilen nur wegen Zeilenzahl
✅ Gut: GalleryCreationService.php wenn inhaltlich abgrenzbar
✅ Gut: GalleryUpdateService.php wenn andere fachliche Logik
✅ Gut: GalleryValidationService.php wenn komplexe Validierungsregeln

// INTELLIGENTE DATEI-NAMEN (Verantwortlichkeit erkennbar)
Produktiv:     CreateGalleryService.php (spezifische Use Cases)
Repository:    WordPressGalleryRepository.php (Technologie + Domain)
Controller:    AdminGalleryApiController.php (Kontext + Domain + Typ)
Test:          CreateGalleryServiceTest.php (exakt passend zum Service)
Integration:   WordPressGalleryRepositoryIntegrationTest.php
Mock:          InMemoryGalleryRepository.php (Zweck + Domain)
Browser:       gallery-creation.browser.test.js (Feature + Typ)
```

### Intelligente Code-Qualitäts-Metriken
```php
// Statt stumpfer Zeilenzählung - sinnvolle Metriken:
class CodeQualityAnalyzer {
    public function analyzeFile(string $file): QualityReport {
        return new QualityReport([
            // Komplexitäts-Metriken
            'cyclomatic_complexity' => $this->calculateComplexity($file), // Max: 10
            'number_of_methods' => $this->countMethods($file),           // Max: 15
            'number_of_dependencies' => $this->countDependencies($file), // Max: 8
            'nesting_depth' => $this->calculateNestingDepth($file),     // Max: 4
            
            // Verantwortlichkeits-Metriken  
            'single_responsibility_score' => $this->checkSRP($file),    // 0-100%
            'cohesion_score' => $this->calculateCohesion($file),        // 0-100%
            'coupling_score' => $this->calculateCoupling($file),        // 0-100%
            
            // Pragmatische Metriken
            'lines_of_code' => $this->countLines($file),                // Info only
            'lines_per_method_avg' => $this->avgMethodLength($file),    // Max: 20
            'comment_ratio' => $this->calculateCommentRatio($file),     // Min: 10%
        ]);
    }
}
```

### Code-Strukturbaum Generator
```php
// scripts/analyze-structure.php - Täglich ausgeführt
class CodeStructureAnalyzer {
    public function generateDependencyMap(): array {
        return [
            'productive_files' => $this->scanProductiveCode(),
            'test_files' => $this->scanTestCode(),
            'dependencies' => $this->analyzeDependencies(),
            'duplicates' => $this->findDuplicateCode(),
            'unused' => $this->findUnusedCode(),
            'test_coverage' => $this->checkTestCoverage()
        ];
    }
    
    public function validateStructure(): ValidationResult {
        // Prüft ob alle produktiven Klassen Tests haben
        // Findet doppelten Code zwischen Test/Produktiv
        // Identifiziert verwaiste Dateien
    }
}

// Automatische Ausführung via GitHub Actions
// Bei jedem Commit: Struktur validieren
// Täglich: Dependency-Map aktualisieren  
// Wöchentlich: Dead-Code-Report erstellen
```

```php
<?php
/**
 * Plugin Name: MarkusLehr ClientGallerie
 * Plugin URI: https://markuslehr.com/clientgallerie
 * Description: Professional WordPress Gallery Plugin with modern UI
 * Version: 1.0.0
 * Author: Markus Lehr
 * License: GPL v2 or later
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 8.0
 * Text Domain: clientgallerie
 * Domain Path: /languages
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLIENTGALLERIE_VERSION', '1.0.0');
define('CLIENTGALLERIE_PLUGIN_FILE', __FILE__);
define('CLIENTGALLERIE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLIENTGALLERIE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLIENTGALLERIE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Composer Autoloader
require_once CLIENTGALLERIE_PLUGIN_DIR . 'vendor/autoload.php';

// Plugin Bootstrap (nur 1 Klasse in der Hauptdatei!)
final class ClientGalleriePlugin 
{
    private static ?self $instance = null;
    private bool $initialized = false;
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() 
    {
        // Prevent multiple instantiation
    }
    
    public function init(): void 
    {
        if ($this->initialized) {
            return;
        }
        
        // System requirements check
        if (!$this->checkRequirements()) {
            return;
        }
        
        // Initialize plugin
        $this->registerHooks();
        $this->loadTextDomain();
        
        // Bootstrap main application
        \MarkusLehr\ClientGallerie\Application\PluginBootstrap::init();
        
        $this->initialized = true;
    }
    
    private function checkRequirements(): bool 
    {
        // PHP version check
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', [$this, 'phpVersionNotice']);
            return false;
        }
        
        // WordPress version check
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            add_action('admin_notices', [$this, 'wpVersionNotice']);
            return false;
        }
        
        return true;
    }
    
    private function registerHooks(): void 
    {
        register_activation_hook(CLIENTGALLERIE_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(CLIENTGALLERIE_PLUGIN_FILE, [$this, 'deactivate']);
        register_uninstall_hook(CLIENTGALLERIE_PLUGIN_FILE, [__CLASS__, 'uninstall']);
    }
    
    public function activate(): void 
    {
        \MarkusLehr\ClientGallerie\Infrastructure\Database\DatabaseInstaller::install();
        flush_rewrite_rules();
    }
    
    public function deactivate(): void 
    {
        flush_rewrite_rules();
    }
    
    public static function uninstall(): void 
    {
        \MarkusLehr\ClientGallerie\Infrastructure\Database\DatabaseInstaller::uninstall();
    }
    
    private function loadTextDomain(): void 
    {
        load_plugin_textdomain(
            'clientgallerie',
            false,
            dirname(CLIENTGALLERIE_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function phpVersionNotice(): void 
    {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('ClientGallerie requires PHP 8.0 or higher.', 'clientgallerie');
        echo '</p></div>';
    }
    
    public function wpVersionNotice(): void 
    {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('ClientGallerie requires WordPress 6.0 or higher.', 'clientgallerie');
        echo '</p></div>';
    }
}

// Initialize plugin (WordPress best practice)
add_action('plugins_loaded', [ClientGalleriePlugin::getInstance(), 'init']);
```

### Objektorientierte Design-Prinzipien

#### 1. SOLID Principles
```php
// S - Single Responsibility Principle
class ImageUploadHandler {
    // Nur für Image-Upload zuständig
    public function handle(UploadRequest $request): UploadResult { }
}

class ThumbnailGenerator {
    // Nur für Thumbnail-Generierung zuständig
    public function generate(Image $image): array { }
}

// O - Open/Closed Principle
abstract class ImageProcessor {
    abstract public function process(Image $image): ProcessedImage;
}

class GdImageProcessor extends ImageProcessor {
    public function process(Image $image): ProcessedImage {
        // GD-spezifische Implementierung
    }
}

class ImagickImageProcessor extends ImageProcessor {
    public function process(Image $image): ProcessedImage {
        // ImageMagick-spezifische Implementierung
    }
}

// L - Liskov Substitution Principle
interface StorageInterface {
    public function store(string $path, $data): bool;
    public function retrieve(string $path): ?string;
}

class LocalStorage implements StorageInterface { }
class CloudStorage implements StorageInterface { }
// Beide können austauschbar verwendet werden

// I - Interface Segregation Principle
interface ReadableRepository {
    public function findById(int $id): ?Entity;
    public function findAll(): array;
}

interface WritableRepository {
    public function save(Entity $entity): bool;
    public function delete(int $id): bool;
}

// Client braucht nur das Interface, das es wirklich nutzt
class GalleryReader {
    public function __construct(private ReadableRepository $repo) {}
}

// D - Dependency Inversion Principle
class GalleryService {
    public function __construct(
        private GalleryRepositoryInterface $repository,  // Abstraktion
        private ImageProcessorInterface $processor,      // Abstraktion
        private EventDispatcherInterface $eventDispatcher // Abstraktion
    ) {}
}
```

#### 2. Design Patterns für WordPress

```php
// Singleton Pattern (sparsam verwenden!)
class PluginContainer {
    private static ?self $instance = null;
    private array $services = [];
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Prevent direct instantiation
    }
}

// Factory Pattern
class RepositoryFactory {
    public function createGalleryRepository(): GalleryRepositoryInterface {
        return new WordPressGalleryRepository();
    }
    
    public function createImageRepository(): ImageRepositoryInterface {
        return new WordPressImageRepository();
    }
}

// Observer Pattern (WordPress Hooks)
class EventManager {
    public function subscribe(string $event, callable $callback): void {
        add_action("clientgallerie_{$event}", $callback);
    }
    
    public function publish(string $event, ...$args): void {
        do_action("clientgallerie_{$event}", ...$args);
    }
}

// Strategy Pattern
interface UploadStrategy {
    public function upload(array $file): UploadResult;
}

class LocalUploadStrategy implements UploadStrategy {
    public function upload(array $file): UploadResult {
        // Lokaler Upload
    }
}

class CloudUploadStrategy implements UploadStrategy {
    public function upload(array $file): UploadResult {
        // Cloud Upload
    }
}

class ImageUploader {
    public function __construct(private UploadStrategy $strategy) {}
    
    public function setStrategy(UploadStrategy $strategy): void {
        $this->strategy = $strategy;
    }
    
    public function upload(array $file): UploadResult {
        return $this->strategy->upload($file);
    }
}

// Command Pattern
interface Command {
    public function execute(): mixed;
}

class CreateGalleryCommand implements Command {
    public function __construct(
        private string $name,
        private string $description,
        private GalleryRepositoryInterface $repository
    ) {}
    
    public function execute(): Gallery {
        $gallery = new Gallery($this->name, $this->description);
        $this->repository->save($gallery);
        return $gallery;
    }
}
```

#### 3. Value Objects & Entities (Domain-Driven Design)

```php
// Value Objects (unveränderlich, vergleichbar)
final class GalleryId {
    private function __construct(private string $value) {
        if (empty($value)) {
            throw new InvalidArgumentException('Gallery ID cannot be empty');
        }
    }
    
    public static function fromString(string $value): self {
        return new self($value);
    }
    
    public static function generate(): self {
        return new self(wp_generate_uuid4());
    }
    
    public function value(): string {
        return $this->value;
    }
    
    public function equals(GalleryId $other): bool {
        return $this->value === $other->value;
    }
}

final class EmailAddress {
    private function __construct(private string $value) {
        if (!is_email($value)) {
            throw new InvalidArgumentException('Invalid email address');
        }
    }
    
    public static function fromString(string $email): self {
        return new self($email);
    }
    
    public function value(): string {
        return $this->value;
    }
}

// Entities (identität, veränderbar)
class Gallery {
    private GalleryId $id;
    private string $name;
    private string $description;
    private GalleryStatus $status;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;
    
    public function __construct(
        GalleryId $id,
        string $name,
        string $description
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->status = GalleryStatus::DRAFT;
        $this->createdAt = new DateTimeImmutable();
    }
    
    public function publish(): void {
        $this->status = GalleryStatus::PUBLISHED;
        $this->updatedAt = new DateTimeImmutable();
    }
    
    public function rename(string $newName): void {
        if (empty($newName)) {
            throw new InvalidArgumentException('Name cannot be empty');
        }
        
        $this->name = $newName;
        $this->updatedAt = new DateTimeImmutable();
    }
    
    // Getters...
    public function id(): GalleryId { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): GalleryStatus { return $this->status; }
}

// Enums für Type Safety
enum GalleryStatus: string {
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    
    public function isPublic(): bool {
        return $this === self::PUBLISHED;
    }
}
```

#### 4. Service Layer Pattern

```php
// Application Services (Use Cases)
class CreateGalleryService {
    public function __construct(
        private GalleryRepositoryInterface $galleryRepository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}
    
    public function execute(CreateGalleryRequest $request): CreateGalleryResponse {
        try {
            // Business Logic
            $galleryId = GalleryId::generate();
            $gallery = new Gallery(
                $galleryId,
                $request->name(),
                $request->description()
            );
            
            // Persistence
            $this->galleryRepository->save($gallery);
            
            // Events
            $this->eventDispatcher->dispatch(
                new GalleryCreatedEvent($galleryId, $request->userId())
            );
            
            // Logging
            $this->logger->info('Gallery created', [
                'gallery_id' => $galleryId->value(),
                'user_id' => $request->userId()
            ]);
            
            return CreateGalleryResponse::success($gallery);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to create gallery', [
                'error' => $e->getMessage(),
                'user_id' => $request->userId()
            ]);
            
            return CreateGalleryResponse::failure($e->getMessage());
        }
    }
}

// Domain Services (Domain Logic)
class GalleryPermissionService {
    public function canUserAccessGallery(UserId $userId, Gallery $gallery): bool {
        // Business rules für Zugriffskontrolle
        if ($gallery->status() === GalleryStatus::PUBLISHED) {
            return true;
        }
        
        if ($gallery->ownerId()->equals($userId)) {
            return true;
        }
        
        return $this->hasClientAccess($userId, $gallery);
    }
    
    private function hasClientAccess(UserId $userId, Gallery $gallery): bool {
        // Komplexe Geschäftslogik...
    }
}
```

### Plugin-Architektur ohne große Hauptdatei

```php
// src/Application/PluginBootstrap.php
namespace MarkusLehr\ClientGallerie\Application;

class PluginBootstrap {
    private static bool $initialized = false;
    
    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        
        // Container aufbauen
        $container = self::buildContainer();
        
        // Service Provider registrieren
        self::registerServiceProviders($container);
        
        // WordPress Hooks registrieren
        self::registerWordPressHooks($container);
        
        self::$initialized = true;
    }
    
    private static function buildContainer(): ContainerInterface {
        $builder = new ContainerBuilder();
        
        // Service-Definitionen laden
        $builder->addDefinitions(require CLIENTGALLERIE_PLUGIN_DIR . 'config/services.php');
        
        return $builder->build();
    }
    
    private static function registerServiceProviders(ContainerInterface $container): void {
        $providers = [
            DatabaseServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            ApiServiceProvider::class,
        ];
        
        foreach ($providers as $providerClass) {
            $provider = $container->get($providerClass);
            $provider->register();
        }
    }
    
    private static function registerWordPressHooks(ContainerInterface $container): void {
        $hookRegistrar = $container->get(WordPressHookRegistrar::class);
        $hookRegistrar->registerAll();
    }
}

// Service Provider Pattern
abstract class ServiceProvider {
    protected ContainerInterface $container;
    
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    
    abstract public function register(): void;
}

class AdminServiceProvider extends ServiceProvider {
    public function register(): void {
        // Admin-spezifische WordPress Hooks
        add_action('admin_menu', [$this->container->get(AdminMenuManager::class), 'register']);
        add_action('admin_enqueue_scripts', [$this->container->get(AdminAssetManager::class), 'enqueue']);
        
        // AJAX Handlers
        $ajaxHandler = $this->container->get(AdminAjaxHandler::class);
        add_action('wp_ajax_clientgallery_upload', [$ajaxHandler, 'handleUpload']);
        add_action('wp_ajax_clientgallery_create_gallery', [$ajaxHandler, 'handleCreateGallery']);
    }
}
```

### Fazit: Objektorientierte Vorteile

1. **Wartbarkeit**: Kleine, fokussierte Klassen sind einfacher zu verstehen
2. **Testbarkeit**: Jede Klasse kann isoliert getestet werden
3. **Erweiterbarkeit**: Neue Features ohne Änderung bestehender Code
4. **Wiederverwendbarkeit**: Services können in verschiedenen Kontexten genutzt werden
5. **Team-Entwicklung**: Mehrere Entwickler können parallel arbeiten
6. **Debugging**: Klare Verantwortlichkeiten vereinfachen Fehlersuche

---

## 🔧 MODERNE ENTWICKLUNGSTECHNIKEN

### 1. Container & Dependency Injection

**Empfehlung: PHP-DI Container**
```php
// Container Configuration
use DI\ContainerBuilder;
use DI\Container;

class PluginContainer {
    private static ?Container $container = null;
    
    public static function getInstance(): Container {
        if (self::$container === null) {
            $builder = new ContainerBuilder();
            $builder->addDefinitions([
                GalleryRepositoryInterface::class => DI\autowire(WordPressGalleryRepository::class),
                ImageProcessorInterface::class => DI\autowire(GdImageProcessor::class),
                SecurityServiceInterface::class => DI\autowire(WordPressSecurityService::class),
            ]);
            self::$container = $builder->build();
        }
        return self::$container;
    }
}

// Usage
$galleryService = PluginContainer::getInstance()->get(GalleryService::class);
```

### 2. Event-Driven Architecture

**WordPress Hooks + Custom Events**
```php
// Custom Event System
class EventDispatcher {
    private array $listeners = [];
    
    public function subscribe(string $event, callable $listener): void {
        $this->listeners[$event][] = $listener;
    }
    
    public function dispatch(DomainEvent $event): void {
        $eventName = get_class($event);
        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            $listener($event);
        }
    }
}

// Domain Events
class ImageUploadedEvent implements DomainEvent {
    public function __construct(
        private ImageId $imageId,
        private GalleryId $galleryId,
        private DateTimeImmutable $occurredOn
    ) {}
}

// Event Listeners
class ImageUploadedListener {
    public function __invoke(ImageUploadedEvent $event): void {
        // Thumbnails generieren
        // E-Mail-Benachrichtigungen
        // Cache invalidieren
    }
}
```

### 3. Type Safety & Static Analysis

**PHPStan Level 8 + Psalm**
```php
// Strict Types überall
declare(strict_types=1);

// Value Objects für Type Safety
final class GalleryId {
    public function __construct(private string $value) {
        if (empty($value)) {
            throw new InvalidArgumentException('Gallery ID cannot be empty');
        }
    }
    
    public function value(): string {
        return $this->value;
    }
    
    public function equals(GalleryId $other): bool {
        return $this->value === $other->value;
    }
}

// Enums für bessere Type Safety (PHP 8.1+)
enum ImageStatus: string {
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
```

### 4. Asynchrone Verarbeitung

**Action Scheduler für WordPress**
```php
// Background Jobs
class ThumbnailGenerationJob {
    public function __construct(
        private ImageProcessor $processor,
        private ImageRepository $repository
    ) {}
    
    public function handle(array $args): void {
        $imageId = new ImageId($args['image_id']);
        $image = $this->repository->findById($imageId);
        
        $this->processor->generateThumbnails($image);
        
        // Event dispatchen
        EventDispatcher::dispatch(new ThumbnailsGeneratedEvent($imageId));
    }
}

// Job scheduling
as_schedule_single_action(
    time() + 60,
    'clientgallery_generate_thumbnails',
    ['image_id' => $imageId->value()]
);
```

---

## 📁 EMPFOHLENE CODE-STRUKTUR

### Hauptverzeichnis-Layout
```
markuslehr_clientgallerie/
├── clientgallerie.php              # Main Plugin File
├── composer.json                   # Dependencies
├── package.json                    # Frontend Dependencies  
├── webpack.config.js               # Build Configuration
├── phpunit.xml                     # Testing Configuration
├── phpstan.neon                    # Static Analysis
├── .editorconfig                   # Code Style
├── README.md                       # Documentation
├── CHANGELOG.md                    # Version History
├── 
├── src/                           # Source Code (PSR-4)
│   ├── Domain/                    # Business Logic
│   ├── Infrastructure/            # WordPress Integration
│   ├── Application/               # Use Cases
│   └── Presentation/              # Controllers & Views
│
├── assets/                        # Frontend Assets
│   ├── src/                       # Source Files
│   │   ├── js/                    # JavaScript (ES6+)
│   │   ├── scss/                  # SCSS Styles
│   │   └── images/                # Images & Icons
│   └── dist/                      # Built Assets
│
├── templates/                     # Frontend Templates
│   ├── gallery/                   # Gallery Views
│   ├── admin/                     # Admin Views
│   └── partials/                  # Reusable Components
│
├── tests/                         # Testing
│   ├── Unit/                      # Unit Tests
│   ├── Integration/               # Integration Tests
│   ├── Acceptance/                # E2E Tests
│   └── Fixtures/                  # Test Data
│
├── languages/                     # Translations
├── docs/                          # Documentation
└── tools/                         # Development Tools
    ├── scripts/                   # Build Scripts
    └── docker/                    # Docker Setup
```

### Domain Layer Struktur
```
src/Domain/
├── Gallery/
│   ├── Entity/
│   │   ├── Gallery.php
│   │   ├── Image.php
│   │   └── ImageCollection.php
│   ├── ValueObject/
│   │   ├── GalleryId.php
│   │   ├── ImageId.php
│   │   ├── Rating.php
│   │   ├── ColorLabel.php
│   │   └── PickStatus.php
│   ├── Repository/
│   │   ├── GalleryRepositoryInterface.php
│   │   └── ImageRepositoryInterface.php
│   ├── Service/
│   │   ├── GalleryService.php
│   │   ├── ImageProcessor.php
│   │   └── ThumbnailGenerator.php
│   └── Event/
│       ├── GalleryCreated.php
│       ├── ImageUploaded.php
│       └── ImageRated.php
│
├── Client/
│   ├── Entity/
│   │   └── Client.php
│   ├── ValueObject/
│   │   ├── ClientId.php
│   │   ├── AccessToken.php
│   │   └── EmailAddress.php
│   └── Service/
│       ├── ClientService.php
│       └── NotificationService.php
│
└── Security/
    ├── Service/
    │   ├── AuthenticationService.php
    │   ├── AuthorizationService.php
    │   └── TokenService.php
    └── ValueObject/
        ├── SessionToken.php
        └── Permission.php
```

### Infrastructure Layer Struktur
```
src/Infrastructure/
├── Database/
│   ├── Schema/                    # Database Migrations
│   │   ├── CreateGalleriesTable.php
│   │   ├── CreateImagesTable.php
│   │   └── CreateClientsTable.php
│   ├── Repository/                # WordPress Implementations
│   │   ├── WordPressGalleryRepository.php
│   │   ├── WordPressImageRepository.php
│   │   └── WordPressClientRepository.php
│   └── QueryBuilder/              # Query Abstractions
│       └── WordPressQueryBuilder.php
│
├── Storage/
│   ├── ImageStorage.php           # File System Operations
│   ├── ThumbnailStorage.php       # Thumbnail Management
│   └── SecureFileServer.php       # Protected File Serving
│
├── Http/
│   ├── Controller/                # AJAX Controllers
│   │   ├── GalleryController.php
│   │   ├── ImageController.php
│   │   └── ClientController.php
│   ├── Middleware/                # Request Middleware
│   │   ├── AuthenticationMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── CsrfMiddleware.php
│   └── Response/                  # Response Objects
│       ├── JsonResponse.php
│       └── FileResponse.php
│
├── Queue/
│   ├── ActionSchedulerQueue.php   # WordPress Action Scheduler
│   └── Job/                       # Background Jobs
│       ├── ThumbnailGenerationJob.php
│       ├── EmailNotificationJob.php
│       └── StatisticsUpdateJob.php
│
└── Cache/
    ├── WordPressCache.php         # WordPress Transients
    └── ImageCache.php             # Image Caching
```

---

## 🎨 FRONTEND-ARCHITEKTUR

### Modern JavaScript (ES6+ Module System)
```javascript
// assets/src/js/modules/
├── core/
│   ├── EventBus.js                # Central Event System
│   ├── StateManager.js            # Global State Management
│   ├── ApiClient.js               # AJAX Wrapper
│   └── Logger.js                  # Debugging & Analytics
│
├── components/                    # UI Components
│   ├── Gallery/
│   │   ├── ImageGrid.js
│   │   ├── Lightbox.js
│   │   ├── FilterBar.js
│   │   └── SortControls.js
│   ├── Rating/
│   │   ├── StarRating.js
│   │   ├── ColorLabels.js
│   │   └── PickReject.js
│   ├── Modal/
│   │   ├── BaseModal.js
│   │   ├── AdminModal.js
│   │   ├── UploadModal.js
│   │   └── SettingsModal.js
│   └── Controls/
│       ├── KeyboardShortcuts.js
│       ├── TouchGestures.js
│       └── HoverEffects.js
│
├── services/                      # Business Logic
│   ├── GalleryService.js
│   ├── ImageService.js
│   ├── AuthService.js
│   └── DownloadService.js
│
├── utils/                         # Utilities
│   ├── debounce.js
│   ├── throttle.js
│   ├── imageUtils.js
│   └── urlUtils.js
│
└── app.js                         # Main Application Entry
```

### CSS Architecture (SCSS + BEM)
```scss
// assets/src/scss/
├── abstracts/
│   ├── _variables.scss            # Color Scheme, Spacing
│   ├── _functions.scss            # SCSS Functions
│   ├── _mixins.scss              # Reusable Mixins
│   └── _placeholders.scss        # Extend Patterns
│
├── base/
│   ├── _reset.scss               # CSS Reset
│   ├── _typography.scss          # Font Definitions
│   └── _utilities.scss           # Utility Classes
│
├── components/                   # BEM Components
│   ├── _gallery.scss
│   ├── _lightbox.scss
│   ├── _rating.scss
│   ├── _modal.scss
│   └── _buttons.scss
│
├── layout/
│   ├── _grid.scss                # CSS Grid Layout
│   ├── _header.scss
│   └── _sidebar.scss
│
├── pages/
│   ├── _gallery-view.scss
│   └── _admin-dashboard.scss
│
├── themes/
│   ├── _light-theme.scss
│   └── _dark-theme.scss
│
└── main.scss                     # Main Import File
```

---

## 🔧 ENTWICKLUNGSTOOLS & WORKFLOW

### 1. Build-System (Webpack 5)
```javascript
// webpack.config.js
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
    entry: {
        frontend: './assets/src/js/app.js',
        admin: './assets/src/js/admin.js'
    },
    output: {
        path: path.resolve(__dirname, 'assets/dist'),
        filename: 'js/[name].[contenthash].js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                    'sass-loader'
                ]
            }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/[name].[contenthash].css'
        })
    ],
    optimization: {
        minimizer: [new TerserPlugin()],
        splitChunks: {
            chunks: 'all'
        }
    }
};
```

### 2. Testing-Framework
```php
// composer.json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "mockery/mockery": "^1.4",
        "phpstan/phpstan": "^1.0",
        "psalm/psalm": "^5.0",
        "squizlabs/php_codesniffer": "^3.0",
        "wp-cli/wp-cli-bundle": "^2.0"
    },
    "scripts": {
        "test": "phpunit",
        "analyze": "phpstan analyze",
        "psalm": "psalm",
        "cs": "phpcs",
        "cbf": "phpcbf"
    }
}
```

### 3. CI/CD Pipeline (.github/workflows/test.yml)
```yaml
name: Tests & Quality

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.0, 8.1, 8.2]
        wordpress: [6.0, 6.1, 6.2]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install Dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run Tests
        run: composer test
        
      - name: Static Analysis
        run: composer analyze
        
      - name: Code Style Check
        run: composer cs
```

---

## 🚀 BESTE ENTWICKLUNGSPRAKTIKEN

### 1. Test-Driven Development (TDD)
```php
// Red-Green-Refactor Cycle
class GalleryServiceTest extends TestCase {
    /** @test */
    public function it_creates_gallery_with_valid_data(): void {
        // Arrange
        $command = new CreateGalleryCommand(
            name: 'Test Gallery',
            description: 'Test Description'
        );
        
        // Act
        $gallery = $this->galleryService->createGallery($command);
        
        // Assert
        $this->assertInstanceOf(Gallery::class, $gallery);
        $this->assertEquals('Test Gallery', $gallery->name());
    }
}
```

### 2. Security-First Development
```php
// Input Validation & Sanitization
class SecureInputValidator {
    public function validateImageUpload(array $file): ValidationResult {
        $errors = [];
        
        // MIME Type Check
        if (!in_array($file['type'], self::ALLOWED_MIME_TYPES)) {
            $errors[] = 'Invalid file type';
        }
        
        // File Size Check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File too large';
        }
        
        // Real MIME Type Check (not just extension)
        $realMimeType = mime_content_type($file['tmp_name']);
        if ($realMimeType !== $file['type']) {
            $errors[] = 'File type mismatch';
        }
        
        return new ValidationResult($errors);
    }
}

// CSRF Protection
class CsrfMiddleware {
    public function handle(Request $request): void {
        if (!wp_verify_nonce($request->nonce(), 'clientgallery_action')) {
            throw new SecurityException('Invalid CSRF token');
        }
    }
}
```

### 3. Performance-Optimierung
```php
// Database Query Optimization
class OptimizedGalleryRepository {
    public function findGalleriesWithImageCount(): array {
        global $wpdb;
        
        // Single optimized query instead of N+1
        $sql = "
            SELECT g.*, COUNT(i.id) as image_count 
            FROM {$wpdb->prefix}clientgallery_galleries g
            LEFT JOIN {$wpdb->prefix}clientgallery_images i ON g.id = i.gallery_id
            WHERE g.status = 'published'
            GROUP BY g.id
            ORDER BY g.created_at DESC
        ";
        
        return $wpdb->get_results($sql);
    }
}

// Image Lazy Loading & Progressive Enhancement
class ImageOptimizer {
    public function generateResponsiveImageSet(Image $image): array {
        return [
            'webp' => [
                '400w' => $this->generateWebP($image, 400),
                '800w' => $this->generateWebP($image, 800),
                '1200w' => $this->generateWebP($image, 1200),
            ],
            'jpg' => [
                '400w' => $this->generateJpg($image, 400, 85),
                '800w' => $this->generateJpg($image, 800, 85),
                '1200w' => $this->generateJpg($image, 1200, 85),
            ]
        ];
    }
}
```

---

## 🎯 QUALITÄTSSICHERUNG

### Code Quality Gates
```bash
# Pre-commit Hooks
#!/bin/sh
# .git/hooks/pre-commit

echo "Running code quality checks..."

# PHPStan
./vendor/bin/phpstan analyze --level=8
if [ $? -ne 0 ]; then
    echo "PHPStan failed. Fix errors before committing."
    exit 1
fi

# PHP CodeSniffer
./vendor/bin/phpcs
if [ $? -ne 0 ]; then
    echo "Code style violations found. Run composer cbf to fix."
    exit 1
fi

# Unit Tests
./vendor/bin/phpunit
if [ $? -ne 0 ]; then
    echo "Tests failed. Fix tests before committing."
    exit 1
fi

# JavaScript Linting
npm run lint
if [ $? -ne 0 ]; then
    echo "JavaScript linting failed."
    exit 1
fi

echo "All quality checks passed!"
```

### Monitoring & Observability
```php
// Application Performance Monitoring
class PerformanceMonitor {
    public function measureExecutionTime(callable $operation, string $operationName): mixed {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            $result = $operation();
            
            $this->logMetrics($operationName, [
                'execution_time' => microtime(true) - $startTime,
                'memory_usage' => memory_get_usage() - $startMemory,
                'status' => 'success'
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->logMetrics($operationName, [
                'execution_time' => microtime(true) - $startTime,
                'memory_usage' => memory_get_usage() - $startMemory,
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

---

## 📈 SKALIERUNGSSTRATEGIEN

### 1. Database Sharding Vorbereitung
```php
// Tenant-aware Repositories
interface TenantAwareRepository {
    public function setTenant(TenantId $tenantId): void;
}

class ShardedGalleryRepository implements TenantAwareRepository {
    private TenantId $currentTenant;
    
    public function setTenant(TenantId $tenantId): void {
        $this->currentTenant = $tenantId;
    }
    
    protected function getTableName(): string {
        // Dynamische Tabellennamen basierend auf Tenant
        return $this->wpdb->prefix . 'clientgallery_galleries_' . $this->currentTenant->shard();
    }
}
```

### 2. Caching-Strategien
```php
// Multi-Layer Caching
class CacheManager {
    private array $layers = [];
    
    public function addLayer(CacheLayer $layer): void {
        $this->layers[] = $layer;
    }
    
    public function get(string $key): mixed {
        foreach ($this->layers as $layer) {
            if ($layer->has($key)) {
                $value = $layer->get($key);
                
                // Populate higher layers
                $this->populateHigherLayers($key, $value, $layer);
                
                return $value;
            }
        }
        
        return null;
    }
}

// Implementation
$cacheManager = new CacheManager();
$cacheManager->addLayer(new MemoryCache());      // L1: Memory
$cacheManager->addLayer(new RedisCache());       // L2: Redis
$cacheManager->addLayer(new DatabaseCache());    // L3: Database
```

---

## 🚀 DEPLOYMENT-STRATEGIE

### 1. WordPress-native Updates
```php
// Automatic Updates via WordPress Update API
class PluginUpdater {
    public function __construct(
        private string $pluginFile,
        private string $version,
        private string $updateServer
    ) {}
    
    public function init(): void {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
    }
    
    public function checkForUpdate($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $remoteVersion = $this->getRemoteVersion();
        if (version_compare($this->version, $remoteVersion, '<')) {
            $transient->response[$this->pluginFile] = (object) [
                'slug' => dirname($this->pluginFile),
                'new_version' => $remoteVersion,
                'url' => $this->updateServer,
                'package' => $this->getDownloadUrl()
            ];
        }
        
        return $transient;
    }
}
```

### 2. Database Migrations
```php
// Versioned Database Migrations
abstract class Migration {
    abstract public function up(): void;
    abstract public function down(): void;
    abstract public function version(): string;
}

class CreateGalleriesTableMigration extends Migration {
    public function up(): void {
        global $wpdb;
        
        $sql = "CREATE TABLE {$wpdb->prefix}clientgallery_galleries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            settings JSON,
            status VARCHAR(20) DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_uuid (uuid),
            INDEX idx_slug (slug),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    public function version(): string {
        return '1.0.0';
    }
}
```

---

## 🎯 FAZIT & EMPFEHLUNGEN

### ✅ Prioritäten für den Start

1. **Architektur-Setup (Woche 1-2)**
   - PSR-4 Autoloading einrichten
   - Dependency Injection Container implementieren
   - Event System aufbauen
   - Testing-Framework konfigurieren

2. **Core Domain Implementation (Woche 3-6)**
   - Gallery, Image, Client Entities
   - Repository Interfaces
   - Basic CRUD Operations
   - Database Migrations

3. **Frontend Foundation (Woche 7-10)**
   - Build-System (Webpack)
   - Component-Architektur
   - API Client
   - Basic UI Components

4. **Integration & Polish (Woche 11-12)**
   - WordPress Integration
   - Admin Interface
   - Security Implementation
   - Performance Optimierung

### 🔥 Moderne Technologien Integration

**Zusätzliche Empfehlungen:**
- **Vite** statt Webpack für schnellere Builds
- **Alpine.js** für leichtgewichtige Interaktivität
- **Tailwind CSS** für konsistentes Design-System
- **Headless UI** für accessibility-optimierte Components
- **Workbox** für Progressive Web App Features

### 📚 Weiterführende Ressourcen

- **Clean Architecture in PHP** - Robert Martin
- **Domain-Driven Design** - Eric Evans
- **WordPress Plugin Boilerplate** - DevinVinson
- **Modern JavaScript Patterns** - Addy Osmani
- **Web Performance Optimization** - Google Developers

---

**Diese Architektur ermöglicht es Ihnen, ein professionelles, wartbares und skalierbares WordPress Plugin zu entwickeln, das den höchsten Qualitätsstandards entspricht und für den kommerziellen Verkauf auf Theme Forest optimiert ist.**

---

## 🧪 TESTING-STRATEGIEN & TOOLS

#### WP-CLI Integration für Testing
```bash
# WP-CLI Setup für Plugin-Testing
wp plugin activate markuslehr-clientgallerie
wp plugin deactivate markuslehr-clientgallerie

# Database Testing
wp db reset --yes
wp core install --url=localhost/wordpress --title="Test Site" --admin_user=admin --admin_password=admin --admin_email=test@test.com

# Plugin-spezifische WP-CLI Commands
wp clientgallery create-test-data
wp clientgallery generate-galleries --count=10
wp clientgallery upload-test-images --gallery-id=1
wp clientgallery run-performance-test
wp clientgallery clear-cache

# Testing-Environment Reset
wp clientgallery reset-test-environment
```

#### Browser-Testing Integration
```javascript
// Automatisierte Browser-Tests mit Playwright
// tests/browser/gallery.spec.js
import { test, expect } from '@playwright/test';

test.describe('Gallery Frontend', () => {
    test.beforeEach(async ({ page }) => {
        // WordPress Test-Environment setup
        await page.goto('http://localhost/wordpress/wp-admin');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin');
        await page.click('#wp-submit');
    });

    test('should display gallery grid', async ({ page }) => {
        await page.goto('http://localhost/wordpress/?ClientSelect_public_gallery=test-gallery-123');
        await expect(page.locator('.clientgallery-grid')).toBeVisible();
        await expect(page.locator('.clientgallery-image')).toHaveCount(10);
    });

    test('should open lightbox on image click', async ({ page }) => {
        await page.goto('http://localhost/wordpress/?ClientSelect_public_gallery=test-gallery-123');
        await page.click('.clientgallery-image:first-child');
        await expect(page.locator('.clientgallery-lightbox')).toBeVisible();
    });

    test('keyboard shortcuts work', async ({ page }) => {
        await page.goto('http://localhost/wordpress/?ClientSelect_public_gallery=test-gallery-123');
        await page.click('.clientgallery-image:first-child');
        
        // Test rating with keyboard
        await page.keyboard.press('5');
        await expect(page.locator('.rating-stars.rating-5')).toBeVisible();
        
        // Test navigation
        await page.keyboard.press('ArrowRight');
        await expect(page.locator('.lightbox-image:nth-child(2)')).toBeVisible();
    });
});

// Performance Testing
test.describe('Performance', () => {
    test('gallery loads within 2 seconds', async ({ page }) => {
        const startTime = Date.now();
        await page.goto('http://localhost/wordpress/?ClientSelect_public_gallery=test-gallery-123');
        await page.waitForSelector('.clientgallery-grid');
        const loadTime = Date.now() - startTime;
        
        expect(loadTime).toBeLessThan(2000);
    });
});
```

#### Test-Verzeichnis-Struktur
```
tests/
├── Unit/                           # PHPUnit Unit Tests
│   ├── Domain/
│   │   ├── Gallery/
│   │   │   ├── GalleryTest.php
│   │   │   ├── ImageTest.php
│   │   │   └── GalleryServiceTest.php
│   │   └── Client/
│   │       └── ClientServiceTest.php
│   ├── Infrastructure/
│   │   ├── Database/
│   │   │   └── RepositoryTest.php
│   │   └── Storage/
│   │       └── ImageStorageTest.php
│   └── Application/
│       └── UseCase/
│           └── CreateGalleryTest.php
│
├── Integration/                    # WordPress Integration Tests
│   ├── AdminIntegrationTest.php
│   ├── FrontendIntegrationTest.php
│   ├── DatabaseIntegrationTest.php
│   └── ApiIntegrationTest.php
│
├── Browser/                        # Browser/E2E Tests
│   ├── specs/
│   │   ├── gallery.spec.js
│   │   ├── admin.spec.js
│   │   ├── upload.spec.js
│   │   └── performance.spec.js
│   ├── utils/
│   │   ├── test-helpers.js
│   │   └── wp-setup.js
│   └── fixtures/
│       ├── test-images/
│       └── test-data.json
│
├── Performance/                    # Performance Tests
│   ├── LoadTest.php
│   ├── DatabasePerformanceTest.php
│   └── ImageProcessingTest.php
│
├── Security/                       # Security Tests
│   ├── AuthenticationTest.php
│   ├── AuthorizationTest.php
│   └── InputValidationTest.php
│
├── Fixtures/                       # Test Data
│   ├── images/                     # Test Images (verschiedene Formate/Größen)
│   │   ├── large-image.jpg
│   │   ├── medium-image.png
│   │   ├── small-image.gif
│   │   └── invalid-file.txt
│   ├── galleries.json              # Test Gallery Data
│   ├── clients.json                # Test Client Data
│   └── database-seeds/
│       ├── galleries.sql
│       ├── images.sql
│       └── clients.sql
│
├── WP-CLI/                         # WP-CLI Test Commands
│   ├── TestDataCommand.php
│   ├── PerformanceTestCommand.php
│   └── CleanupTestCommand.php
│
└── config/                         # Test Configuration
    ├── phpunit.xml
    ├── playwright.config.js
    ├── test-wp-config.php
    └── bootstrap.php
```

#### WP-CLI Custom Commands für Testing
```php
// tests/WP-CLI/TestDataCommand.php
namespace MarkusLehr\ClientGallerie\Tests\WpCli;

use WP_CLI;
use WP_CLI_Command;

class TestDataCommand extends WP_CLI_Command {
    
    /**
     * Creates test galleries with sample images
     * 
     * ## OPTIONS
     * 
     * [--count=<number>]
     * : Number of galleries to create
     * 
     * [--images=<number>]
     * : Number of images per gallery
     * 
     * ## EXAMPLES
     * 
     *     wp clientgallery create-test-data --count=5 --images=20
     */
    public function create_test_data($args, $assoc_args) {
        $count = $assoc_args['count'] ?? 3;
        $images_per_gallery = $assoc_args['images'] ?? 10;
        
        WP_CLI::log("Creating {$count} test galleries with {$images_per_gallery} images each...");
        
        for ($i = 1; $i <= $count; $i++) {
            $this->createTestGallery($i, $images_per_gallery);
        }
        
        WP_CLI::success("Created {$count} test galleries successfully!");
    }
    
    /**
     * Runs performance tests
     */
    public function run_performance_test($args, $assoc_args) {
        WP_CLI::log("Running performance tests...");
        
        // Gallery Load Performance
        $start = microtime(true);
        $galleries = $this->loadAllGalleries();
        $load_time = microtime(true) - $start;
        
        WP_CLI::log("Gallery load time: " . round($load_time * 1000, 2) . "ms");
        
        if ($load_time > 0.5) {
            WP_CLI::warning("Gallery load time exceeds 500ms threshold!");
        } else {
            WP_CLI::success("Gallery load performance: OK");
        }
        
        // Image Processing Performance
        $this->testImageProcessingPerformance();
        
        // Database Query Performance
        $this->testDatabasePerformance();
    }
    
    /**
     * Resets test environment
     */
    public function reset_test_environment() {
        WP_CLI::log("Resetting test environment...");
        
        // Clear test galleries
        $this->clearTestGalleries();
        
        // Clear test images
        $this->clearTestImages();
        
        // Clear test clients
        $this->clearTestClients();
        
        // Clear cache
        wp_cache_flush();
        
        WP_CLI::success("Test environment reset complete!");
    }
    
    private function createTestGallery($index, $image_count) {
        // Implementierung...
    }
}

// WP-CLI Command Registration
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('clientgallery', TestDataCommand::class);
}
```

#### Automated Testing Pipeline
```yaml
# .github/workflows/test-complete.yml
name: Complete Test Suite

on: [push, pull_request]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.0, 8.1, 8.2]
        wordpress: [6.0, 6.1, 6.2]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install Dependencies
        run: composer install
        
      - name: Setup WordPress Test Environment
        run: |
          bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost ${{ matrix.wordpress }}
          
      - name: Run Unit Tests
        run: ./vendor/bin/phpunit
        
      - name: Run Integration Tests
        run: ./vendor/bin/phpunit --configuration phpunit-integration.xml

  browser-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          
      - name: Install Dependencies
        run: npm install
        
      - name: Setup WordPress with Docker
        run: |
          docker-compose up -d
          sleep 30
          
      - name: Install WordPress via WP-CLI
        run: |
          docker-compose exec wordpress wp core install --url=localhost --title="Test Site" --admin_user=admin --admin_password=admin --admin_email=test@test.com
          docker-compose exec wordpress wp plugin activate markuslehr-clientgallerie
          
      - name: Create Test Data
        run: |
          docker-compose exec wordpress wp clientgallery create-test-data --count=3 --images=5
          
      - name: Run Browser Tests
        run: npm run test:browser
        
      - name: Run Performance Tests
        run: |
          docker-compose exec wordpress wp clientgallery run-performance-test
          npm run test:performance

  security-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Security Scan
        run: |
          ./vendor/bin/psalm --security-analysis
          npm audit
          
      - name: WordPress Security Scan
        run: |
          wp plugin install wp-cli/checksum-command --activate
          wp core verify-checksums
```

#### Test Configuration Files
```javascript
// playwright.config.js
module.exports = {
    testDir: './tests/browser',
    timeout: 30000,
    expect: {
        timeout: 5000
    },
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL: 'http://localhost/wordpress',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
        {
            name: 'Mobile Chrome',
            use: { ...devices['Pixel 5'] },
        },
        {
            name: 'Mobile Safari',
            use: { ...devices['iPhone 12'] },
        },
    ],
    webServer: {
        command: 'docker-compose up',
        port: 80,
        reuseExistingServer: !process.env.CI,
    },
};
```
