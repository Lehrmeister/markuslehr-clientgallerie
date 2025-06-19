# MarkusLehr ClientGallerie - Projekt-Übersicht & Navigation

*Zentrale Referenz für KI-Assistenten und Entwickler*

---

## 🎯 TOP 20 WICHTIGSTE PUNKTE (Quick Reference)

### 🏆 Projekt-Essentials
1. **WordPress Gallery Plugin** für professionelle Fotografen (ThemeForest-ready)
2. **Picdrop.com-inspiriertes Design** - Moderne, minimalistische UI
3. **Revolutionary Modal-Admin** - Backend-Funktionen direkt im Frontend (Passwort-geschützt)
4. **Öffentlicher Zugang per Default** - Sicherheit durch kryptische URLs
5. **Client-System als Zusatzfeature** - Nicht für Zugriffskontrolle, sondern Organisation

### 🏗️ Technische Architektur
6. **Domain-Driven Design (DDD)** - Business Logic in Domain Layer
7. **CQRS Pattern** - Trennung von Commands und Queries
8. **Hexagonal Architecture** - Ports & Adapters für Testbarkeit
9. **Event-Driven Architecture** - Lose Kopplung durch Events
10. **Schlanke, eindeutig benannte Dateien** - Klare Verantwortlichkeiten pro Datei

### 🎨 Frontend-Features
11. **Lightroom-Style Interface** - Vollständige Keyboard-Navigation
12. **Rating System** - 1-5 Sterne + Farbmarkierungen + Pick/Reject
13. **A/B Compare Mode** - Side-by-side Bildvergleich
14. **Responsive Design** - Mobile-First, Touch-optimiert
15. **Real-time Sync** - Live-Updates zwischen Clients

### 🔧 Entwicklung & Qualität
16. **Sinnvolle Datei-Aufteilung** - Eine Verantwortlichkeit pro Datei (Single Responsibility)
17. **Code-Strukturbaum & Dependency-Netz** - Live-Tracking aller Abhängigkeiten
18. **Test-Driven Development** - Unit, Integration, E2E Tests
19. **CI/CD Pipeline** - Automatisierte Qualitätskontrolle
20. **Performance-First** - Unter 2s Ladezeit, Webspace-kompatibel

---

## 📚 DATEI-NAVIGATION

### 📋 specs.md - Vollständige Projektspezifikation
**Umfang:** 817 Zeilen | **Zweck:** Komplette funktionale und technische Anforderungen

#### Hauptkapitel:
- **🎯 PROJEKTÜBERSICHT** - Ziele, Kommerzielle Ausrichtung, Kernphilosophie
- **🏗️ SYSTEMARCHITEKTUR** - Core-Komponenten, Technologie-Stack, Kompatibilität
- **🔑 ZUGRIFFSSYSTEM & SICHERHEIT** - Öffentlicher/Client-Zugang, Sicherheitsmechanismen
- **📱 FRONTEND-FEATURES** - Picdrop-Design, Lightroom-Interface, Keyboard Shortcuts
- **🛠️ ADMIN-INTERFACE** - Dual-Interface-Konzept, Frontend-Modals, WordPress-Integration
- **🔄 API-ENDPUNKTE** - REST API, AJAX Handlers
- **🖼️ BILDVERARBEITUNG** - Upload-Pipeline, Sichere Auslieferung
- **🔧 ENTWICKLUNGSRICHTLINIEN** - Code-Standards, Performance, Testing
- **🚀 DEPLOYMENT & INSTALLATION** - ThemeForest Compliance, Webspace-Optimierung
- **📋 FEATURE-ROADMAP** - 4 Entwicklungsphasen (MVP → Enterprise)
- **🎨 UI/UX SPEZIFIKATIONEN** - Design-Prinzipien
- **🔐 SICHERHEITS-CHECKLISTE** - Input-Validation, Access Control, Data Protection
- **📊 MONITORING & ANALYTICS** - Performance-Metriken, Business-Metriken, Logging
- **🧪 QUALITÄTSSICHERUNG** - Automated Testing, Manual Testing
- **📚 DOKUMENTATION** - Developer, User, Admin Documentation
- **🎯 ERFOLGSKRITERIEN** - Technische und Business-Ziele

### 🏗️ entwicklungsrichtlinien.md - Moderne Entwicklungsarchitektur
**Umfang:** ~600 Zeilen | **Zweck:** Technische Implementierung und Best Practices

#### Hauptkapitel:
- **🏗️ MODERNE ARCHITEKTUR-PRINZIPIEN** - DDD, CQRS, Hexagonal Architecture
- **🔧 MODERNE ENTWICKLUNGSTECHNIKEN** - Container & DI, Event-Driven, Type Safety, Async
- **📁 EMPFOHLENE CODE-STRUKTUR** - Verzeichnis-Layout, Domain/Infrastructure/Application
- **🎨 FRONTEND-ARCHITEKTUR** - ES6+ Module System, CSS Architecture (SCSS + BEM)
- **🔧 ENTWICKLUNGSTOOLS & WORKFLOW** - Webpack 5, Testing-Framework, CI/CD Pipeline
- **🚀 BESTE ENTWICKLUNGSPRAKTIKEN** - TDD, Security-First, Performance-Optimierung
- **🎯 QUALITÄTSSICHERUNG** - Code Quality Gates, Monitoring & Observability
- **📈 SKALIERUNGSSTRATEGIEN** - Database Sharding, Caching-Strategien
- **🚀 DEPLOYMENT-STRATEGIE** - WordPress Updates, Database Migrations
- **🔧 OBJEKTORIENTIERTE PRINZIPIEN** - SOLID, Design Patterns, Service Layer

---

## 🗂️ DETAILLIERTES INHALTSVERZEICHNIS

### 📋 specs.md - Funktionale Spezifikation

```
Zeilen 1-50:     🎯 PROJEKTÜBERSICHT
  - Ziel: Kommerzielles WordPress Gallery Plugin
  - ThemeForest Ready, Webspace-Kompatibel
  - Picdrop.com-inspiriertes Design

Zeilen 51-100:   🏗️ SYSTEMARCHITEKTUR  
  - 7 Core-Komponenten (Database → JavaScript Frontend)
  - Technologie-Stack: PHP 8.0+, WordPress 6.5+, Vanilla JS
  - Kompatibilität: Multi-Site, Standard-Hosting

Zeilen 101-150:  🔑 ZUGRIFFSSYSTEM & SICHERHEIT
  - Öffentlicher Zugang per Default (kryptische URLs)
  - Client-System für erweiterte Features
  - Session-basierte Authentifizierung

Zeilen 151-300:  📱 FRONTEND-FEATURES
  - Picdrop.com-inspiriertes Design
  - Lightroom-Style Interface + Modal-System
  - Keyboard Shortcuts (1-5 Rating, P/X Pick/Reject, etc.)
  - Responsive Design (Mobile → Ultra-wide)
  - JavaScript-Architektur (Event-Driven)

Zeilen 301-400:  🛠️ ADMIN-INTERFACE
  - Dual-Interface-Konzept (Backend + Frontend-Modals)
  - Revolutionary Frontend-Admin-Features
  - WordPress-Integration (Admin-Menüs, Sub-Menüs)

Zeilen 401-450:  🖼️ BILDVERARBEITUNG
  - Upload-Pipeline (Validation → Processing → Storage)
  - Sichere Bildauslieferung über PHP-Proxy
  - Thumbnail-Generierung (150x150, 300x300, 800x600)

Zeilen 451-550:  🔧 ENTWICKLUNGSRICHTLINIEN
  - WordPress Coding Standards, PSR-4 Autoloading
  - Performance-Optimierung (Database, Frontend)
  - Testing-Strategie (Unit, Integration, UAT)

Zeilen 551-650:  🚀 DEPLOYMENT & INSTALLATION
  - ThemeForest Compliance (Code-Qualität, Dokumentation)
  - Webspace-Kompatibilität (Shared-Hosting)
  - Installation-Workflow (Ein-Klick-Setup)

Zeilen 651-750:  📋 FEATURE-ROADMAP & UI/UX
  - 4 Entwicklungsphasen (Core → Enterprise)
  - Design-Prinzipien von Picdrop.com

Zeilen 751-817:  🔐 QUALITÄT & MONITORING
  - Sicherheits-Checkliste, Performance-Metriken
  - Testing-Verfahren, Dokumentations-Standards
  - Kommerzielle Vollständigkeit für ThemeForest
```

### 🏗️ entwicklungsrichtlinien.md - Technische Architektur

```
Zeilen 1-100:    🏗️ MODERNE ARCHITEKTUR-PRINZIPIEN
  - Domain-Driven Design (DDD) Structure
  - CQRS (Command Query Responsibility Segregation)
  - Hexagonal Architecture (Ports & Adapters)

Zeilen 101-200:  🔧 OBJEKTORIENTIERTE PRINZIPIEN
  - Problem: Große clientgallerie.php (1000+ Zeilen)
  - Lösung: Sinnvolle, eindeutig benannte Dateien mit klarer Verantwortlichkeit
  - SOLID Principles mit konkreten Beispielen
  - Design Patterns (Factory, Strategy, Observer, Command)

Zeilen 201-300:  📁 EMPFOHLENE CODE-STRUKTUR
  - Hauptverzeichnis-Layout (src/, assets/, templates/, tests/)
  - Domain Layer (Entity, ValueObject, Repository, Service)
  - Infrastructure Layer (Database, Storage, HTTP, Queue)

Zeilen 301-400:  🎨 FRONTEND-ARCHITEKTUR
  - Modern JavaScript (ES6+ Module System)
  - CSS Architecture (SCSS + BEM)
  - Component-basierte Struktur

Zeilen 401-500:  🔧 ENTWICKLUNGSTOOLS & WORKFLOW
  - Build-System (Webpack 5)
  - Testing-Framework (PHPUnit, Composer)
  - CI/CD Pipeline (GitHub Actions)

Zeilen 501-600:  🚀 BESTE PRAKTIKEN & SKALIERUNG
  - Test-Driven Development (TDD)
  - Security-First Development
  - Performance-Optimierung
  - Database Sharding, Caching-Strategien
```

---

## 🌐 CODE-STRUKTURBAUM & DEPENDENCY-NETZ

### Ziel: Vermeidung von Code-Duplikation und verwaisten Dateien
- **Problem:** Alter Test-Code wird produktiv genutzt, Löschung verursacht Crashes
- **Lösung:** Live-Dependency-Tracking und automatische Code-Analyse

### Live-Dependency-Map
```
docs/dependency-map.json         # Automatisch generierte Abhängigkeitskarte
docs/code-usage-report.md        # Welcher Code wird wo verwendet
docs/test-prod-separation.md     # Klare Trennung Test/Produktiv
scripts/analyze-dependencies.php # Dependency-Analyzer
scripts/find-unused-code.php     # Dead-Code-Finder
scripts/validate-structure.php   # Struktur-Validator
```

### Automatische Code-Analyse
```php
// Täglich ausgeführt via Cron/GitHub Actions
- Welche Klassen/Funktionen werden wo verwendet?
- Gibt es doppelten Code zwischen Test/Produktiv?
- Welche Dateien haben keine Abhängigkeiten (potentiell löschbar)?
- Sind alle produktiven Klassen von Tests abgedeckt?
```

### Naming-Convention für eindeutige Identifikation
```
Produktiv:     GalleryService.php, ImageProcessor.php
Test:          GalleryServiceTest.php, ImageProcessorTest.php  
Mock:          MockGalleryRepository.php, MockImageStorage.php
Integration:   GalleryIntegrationTest.php
Browser:       gallery.browser.test.js
```

---

## 🎯 ENTWICKLUNGSSTRATEGIE

### Phase 1: Architektur-Setup (Woche 1-2)
```
✅ Prioritäten:
- PSR-4 Autoloading einrichten
- Dependency Injection Container implementieren  
- Event System aufbauen
- Testing-Framework konfigurieren
- Eindeutig benannte, verantwortlichkeits-basierte Dateien erstellen

📁 Dateien:
- clientgallerie.php (Bootstrap)
- composer.json (Dependencies)
- src/Application/PluginBootstrap.php
- config/services.php (DI Configuration)
```

### Phase 2: Core Domain (Woche 3-6)  
```
✅ Prioritäten:
- Gallery, Image, Client Entities
- Repository Interfaces
- Basic CRUD Operations
- Database Migrations
- Value Objects (GalleryId, ImageId, etc.)

📁 Dateien:
- src/Domain/Gallery/Entity/
- src/Domain/Gallery/Repository/
- src/Infrastructure/Database/Repository/
- src/Infrastructure/Database/Schema/
```

### Phase 3: Frontend Foundation (Woche 7-10)
```
✅ Prioritäten:
- Build-System (Webpack)
- Component-Architektur  
- API Client
- Basic UI Components
- Picdrop-inspiriertes Design

📁 Dateien:
- webpack.config.js
- assets/src/js/modules/
- assets/src/scss/components/
- templates/gallery/
```

### Phase 4: Integration & Polish (Woche 11-12)
```
✅ Prioritäten:
- WordPress Integration
- Admin Interface
- Security Implementation
- Performance Optimierung
- ThemeForest Compliance

📁 Dateien:
- src/Infrastructure/Http/Controller/
- src/Presentation/Admin/
- templates/admin/
- tests/ (Complete Coverage)
```

---

## 🔧 KI-ASSISTANT HINWEISE

### Wenn Sie Code implementieren:
1. **Verwenden Sie die verantwortlichkeits-basierte Architektur** (Datei-Aufteilung nach fachlicher Kohäsion)
2. **Befolgen Sie SOLID Principles** - Jede Klasse hat eine Verantwortung
3. **Nutzen Sie Type Safety** - declare(strict_types=1), Value Objects, Enums
4. **Implementieren Sie Tests parallel** - TDD Ansatz
5. **Beachten Sie WordPress Standards** - PSR-4, Hooks, Sanitization

### Wenn Sie Fragen haben:
1. **specs.md** für funktionale Anforderungen und Business Logic
2. **entwicklungsrichtlinien.md** für technische Implementierung
3. **project-overview.md** für schnelle Orientierung und Prioritäten

### Code-Organisation:
- **Domain Layer**: Geschäftslogik, Entities, Value Objects
- **Application Layer**: Use Cases, Commands, Queries  
- **Infrastructure Layer**: WordPress-Integration, Database, Storage
- **Presentation Layer**: Controllers, Templates, Views

---

## 🚀 READY FOR DEVELOPMENT

**Diese drei Dateien enthalten alle Informationen für eine erfolgreiche Entwicklung:**

1. **project-overview.md** ← Diese Datei (Navigation & Quick Reference)
2. **specs.md** ← Vollständige funktionale Spezifikation  
3. **entwicklungsrichtlinien.md** ← Moderne technische Architektur

**Nächster Schritt:** Implementierung der schlanken Bootstrap-Architektur beginnen! 🎯
