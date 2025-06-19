# Code-Strukturbaum & Dependency-Management

## 🎯 ZIEL: Vermeidung von Code-Duplikation und verwaisten Dateien

### Das Problem das wir lösen:
- ✅ **Alte Test-Code wird produktiv genutzt** → Beim Löschen von Tests crasht das System
- ✅ **Code-Doppelungen** zwischen Test- und Produktiv-Code
- ✅ **Verwaiste Dateien** die niemand mehr nutzt aber nicht gelöscht werden
- ✅ **Unclear Dependencies** - Welcher Code hängt von welchem ab?

### Die Lösung:
- 🔍 **Live-Dependency-Tracking** - Automatische Analyse aller Abhängigkeiten
- 📊 **Code-Usage-Reports** - Welcher Code wird wo verwendet?
- 🧹 **Dead-Code-Detection** - Automatisches Finden von ungenutztem Code
- 📋 **Test-Prod-Separation** - Klare Trennung und Validierung

---

## 🔧 TOOLS & SCRIPTS

### Automatische Analyse
```bash
# Vollständige Struktur-Analyse
bash scripts/analyze-structure.sh

# Nur PHP-Dependencies
php scripts/analyze-dependencies.php

# VS Code Task
Ctrl+Shift+P → "Analyze Code Structure"
```

### Generierte Reports
```
docs/analysis/
├── structure-report.md      # Haupt-Report mit allen Erkenntnissen
├── dependencies.json        # PHP-Abhängigkeitskarte
├── js-dependencies.json     # JavaScript-Dependencies
├── large-files.txt          # Dateien über 200 Zeilen
├── coverage.txt             # Test-Coverage-Statistik
└── duplicate-files.txt      # Code-Duplikate
```

---

## 📏 DATEI-GRÖSSENBESCHRÄNKUNGEN

### Maximale Zeilenzahlen:
- **Produktive Klassen:** 200 Zeilen
- **Test-Dateien:** 300 Zeilen
- **Controller:** 150 Zeilen
- **Repositories:** 250 Zeilen
- **Value Objects:** 100 Zeilen

### Automatische Überwachung:
```bash
# Findet alle Dateien über 200 Zeilen
find . -name "*.php" -not -path "./vendor/*" | while read file; do
    lines=$(wc -l < "$file")
    if [ $lines -gt 200 ]; then
        echo "⚠️ $file: $lines Zeilen"
    fi
done
```

---

## 🏗️ NAMING-CONVENTION

### Produktive Dateien:
```
GalleryService.php              # Business Logic Service
WordPressGalleryRepository.php  # Data Access Repository  
GalleryApiController.php        # HTTP Request Controller
ImageProcessor.php              # Domain Service
GalleryId.php                   # Value Object
```

### Test-Dateien:
```
GalleryServiceTest.php          # Unit Test
GalleryIntegrationTest.php      # WordPress Integration Test
MockGalleryRepository.php       # Test Double/Mock
gallery.browser.test.js         # Browser/E2E Test
TestGalleryFactory.php          # Test Data Factory
```

### Suffix-Bedeutungen:
- `Service.php` → Business Logic
- `Repository.php` → Data Access
- `Controller.php` → HTTP Handling
- `Processor.php` → Data Processing
- `Factory.php` → Object Creation
- `Test.php` → Unit Test
- `Mock.php` → Test Double
- `.browser.test.js` → E2E Test

---

## 🔗 DEPENDENCY-TRACKING

### Was wird getrackt:
```php
// Automatisch erkannt:
- Klassen-Dependencies (use, new, ::)
- Funktion-Aufrufe
- Interface-Implementierungen
- Trait-Usage
- File-Includes/Requires

// In Reports sichtbar:
- Welche Klasse wird wo verwendet?
- Gibt es ungenutzte Klassen?
- Wo sind Code-Doppelungen?
- Welche Tests fehlen?
```

### Usage-Map Beispiel:
```json
{
    "GalleryService": {
        "defined_in": "src/Domain/Gallery/Service/GalleryService.php",
        "used_in": [
            "src/Application/Handler/CreateGalleryHandler.php",
            "src/Infrastructure/Http/Controller/GalleryController.php"
        ],
        "is_test": false,
        "test_coverage": ["tests/Unit/Domain/Gallery/GalleryServiceTest.php"]
    }
}
```

---

## 🧹 DEAD-CODE-DETECTION

### Automatisches Finden von:
- **Ungenutzte Klassen** - Definiert aber nie verwendet
- **Verwaiste Funktionen** - Nur in einer Datei definiert, nie aufgerufen
- **Test-Code im Produktiv-System** - Test-Klassen außerhalb von /tests/
- **Doppelte Funktionen** - Gleiche Funktion in mehreren Dateien

### Sicherheitsregeln:
```php
// Niemals automatisch löschen:
- WordPress Hook-Callbacks (können dynamisch aufgerufen werden)
- Public API-Methoden (können extern verwendet werden)
- Plugin-Entry-Points (WordPress erwartet bestimmte Funktionen)
- Dateien mit @preserve-Kommentar
```

---

## 📊 CONTINUOUS MONITORING

### GitHub Actions Integration:
```yaml
# .github/workflows/structure-check.yml
name: Code Structure Check

on: [push, pull_request]

jobs:
  structure:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Analyze Structure
        run: bash scripts/analyze-structure.sh
      - name: Check File Sizes
        run: |
          large_files=$(find . -name "*.php" -not -path "./vendor/*" -exec wc -l {} + | awk '$1 > 200 {print $2 ": " $1 " lines"}')
          if [ -n "$large_files" ]; then
            echo "❌ Large files found:"
            echo "$large_files"
            exit 1
          fi
      - name: Upload Reports
        uses: actions/upload-artifact@v3
        with:
          name: structure-reports
          path: docs/analysis/
```

### Regelmäßige Ausführung:
- **Bei jedem Commit:** Dateigrößen-Check
- **Täglich:** Dependency-Analyse  
- **Wöchentlich:** Dead-Code-Report
- **Monatlich:** Vollständige Struktur-Review

---

## 🎯 VORTEILE DIESES SYSTEMS

### ✅ **Entwickler-Produktivität**
- Schnelles Finden von Code-Dependencies
- Klare Struktur-Übersicht
- Automatische Qualitätschecks

### ✅ **Code-Qualität**
- Vermeidung von Duplikationen
- Frühe Erkennung von Struktur-Problemen
- Kontinuierliche Verbesserung

### ✅ **Wartbarkeit**
- Sichere Refactorings
- Klare Test-Abdeckung
- Dokumentierte Abhängigkeiten

### ✅ **Team-Collaboration**
- Einheitliche Standards
- Transparente Code-Struktur
- Automated Reviews

**🚀 Mit diesem System vermeiden wir erfolgreich die Probleme der Vergangenheit und schaffen eine saubere, wartbare Code-Basis!**
