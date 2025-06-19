# MarkusLehr ClientGallerie - Verzeichnis-Struktur

## 📁 OPTIMIERTE PROJEKT-ORGANISATION

### Root-Level (Hauptdokumentation)
```
markuslehr_clientgallerie/
├── 📋 project-overview.md          # Navigation & Quick Reference
├── 📄 specs.md                     # Vollständige Spezifikation (817 Zeilen)
├── 🏗️ entwicklungsrichtlinien.md   # Technische Architektur
├── 🚀 clientgallerie.php          # Sinnvolle, eindeutig benannte Bootstrap-Datei
├── 📦 composer.json                # PHP Dependencies
├── 📦 package.json                 # Frontend Dependencies
└── 📖 README.md                    # Projekt-Übersicht
```

### VS Code Integration (`.vscode/`)
```
.vscode/
├── ⚙️ settings.json               # VS Code Einstellungen (Copilot optimiert)
├── 🔧 tasks.json                  # VS Code Tasks (AI Context, WP-CLI Tests)
├── ✂️ snippets.code-snippets      # AI-Context Snippets (!context, !task, !test)
├── 🤖 AI-INTEGRATION.md           # AI-Assistant Anleitung
└── 📜 ai-context.sh               # Context-Loader Script
```

### Zukünftige Entwicklungsstruktur
```
src/                               # Quellcode (PSR-4)
├── Domain/                        # Business Logic
├── Application/                   # Use Cases (CQRS)
├── Infrastructure/                # WordPress Integration
└── Presentation/                  # Controllers & Views

assets/                            # Frontend Assets
├── src/                          # Source Files (SCSS, JS)
└── dist/                         # Built Assets

tests/                            # Testing
├── Unit/                         # PHPUnit Unit Tests
├── Integration/                  # WordPress Integration Tests
├── Browser/                      # Playwright E2E Tests
└── Fixtures/                     # Test Data

templates/                        # Frontend Templates
├── gallery/                      # Gallery Views
├── admin/                        # Admin Views
└── partials/                     # Reusable Components
```

## 🎯 WARUM DIESE STRUKTUR OPTIMAL IST

### ✅ **Root-Level für Hauptdokumentation**
- **Sofort sichtbar** für neue Entwickler
- **GitHub-kompatibel** (README.md wird automatisch angezeigt)
- **AI-Assistant freundlich** (Dateien direkt auffindbar)

### ✅ **VS Code spezifische Dateien in `.vscode/`**
- **Standard-Konvention** für VS Code Projekte
- **Team-Sync** über Git (alle haben gleiche Settings)
- **Kein Root-Clutter** - technische Dateien versteckt

### ✅ **Zukünftige Skalierbarkeit**
- **PSR-4 Structure** für professionelle PHP-Entwicklung
- **Separation of Concerns** - jeder Bereich hat seinen Platz
- **Testing-First** - komplette Test-Organisation vorbereitet

## 🚀 NÄCHSTE SCHRITTE

1. **AI-Context testen:**
   ```bash
   # Neue Ausführung:
   .vscode/ai-context.sh
   
   # Oder über VS Code Task:
   Ctrl+Shift+P → Tasks → "🚀 Load AI Context"
   ```

2. **Snippets verwenden:**
   ```
   !context + Tab    → Vollständiger AI-Kontext
   !task + Tab       → Schneller AI-Prompt
   ```

3. **Entwicklung starten:**
   - Bootstrap-Datei erstellen (`clientgallerie.php`)
   - Composer Setup (`composer.json`)
   - Domain Layer implementieren

**✨ Perfekt organisiert für professionelle Plugin-Entwicklung! 🚀**
