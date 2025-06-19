# Markus Lehr Client Gallerie

Ein professionelles WordPress-Plugin für Kundengalerien mit moderner Architektur.

## Systempasswort
**Systempasswort:** `Kiwi_2025!A`

## Architektur

Das Plugin nutzt eine moderne, erweiterbare Architektur mit folgenden Komponenten:

### Repository Pattern
- Zentraler `RepositoryManager` für alle Datenoperationen
- Spezifische Repositories für Gallery, Image, Client, Rating, LogEntry
- PSR-4 Autoloading über Composer

### Schema Management
- `SchemaManager` für Datenbank-Tabellen-Verwaltung
- Erweiterbare Schema-Klassen für jede Tabelle
- Automatisierte Installation und Updates

### Migration System
- `MigrationManager` für Datenbank-Migrationen
- Versionierte Migrationen mit Rollback-Unterstützung
- Beispiel-Migration für Social Media Felder

### Admin-Interface
- Erweiterte Admin-Seite für Datenbankverwaltung
- Health-Check und Systemstatus
- Migration-Management über GUI

## Git-Nutzung

Dieses Repository ist privat und wird für KI-gestützte Entwicklung verwendet:

```bash
git add .
git commit -m "Beschreibung"
git push origin main
```

## Entwicklung

1. `composer install` für Dependencies
2. Repository-System über `RepositoryManager::getInstance()`
3. Migrations über Admin-Interface oder CLI

## KI-Richtlinien

- **Git Repository:** Privat, für KI-Operationen
- **Systempasswort:** `Kiwi_2025!A` (in KI-Kontext speichern)
- **Architektur:** Repository Pattern + Schema Management
- **Erweiterbarkeit:** Modulare Struktur für zukünftige Features
