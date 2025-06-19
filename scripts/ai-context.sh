#!/bin/bash

# MarkusLehr ClientGallerie - AI Context Loader
# Verwendung: ./scripts/ai-context.sh

clear

echo "🚀 ==============================================="
echo "   MARKUSLEHR CLIENTGALLERIE - AI CONTEXT"
echo "==============================================="
echo ""

# Prüfe ob Dateien existieren
if [ ! -f "project-overview.md" ]; then
    echo "❌ project-overview.md nicht gefunden!"
    exit 1
fi

if [ ! -f "specs.md" ]; then
    echo "❌ specs.md nicht gefunden!"
    exit 1
fi

if [ ! -f "entwicklungsrichtlinien.md" ]; then
    echo "❌ entwicklungsrichtlinien.md nicht gefunden!"
    exit 1
fi

echo "✅ Alle Kontext-Dateien verfügbar:"
echo "   📋 project-overview.md ($(wc -l < project-overview.md) Zeilen)"
echo "   📄 specs.md ($(wc -l < specs.md) Zeilen)"
echo "   🏗️ entwicklungsrichtlinien.md ($(wc -l < entwicklungsrichtlinien.md) Zeilen)"
echo ""

echo "🎯 TOP 20 PRIORITÄTEN:"
echo "   1. Objektorientierte Architektur (SOLID Principles)"
echo "   2. Schlanke Bootstrap-Datei (clientgallerie.php ~100 Zeilen)"
echo "   3. Domain-Driven Design + CQRS Pattern"
echo "   4. WordPress 6.5+ Kompatibilität"
echo "   5. ThemeForest Marketplace Ready"
echo "   6. Picdrop.com-inspirierte UI"
echo "   7. Revolutionary Modal-Admin (Frontend-Backend)"
echo "  8. Responsive Design (Mobile First)"
echo "   9. Rating System (Sterne + Farben + Pick/Reject)"
echo "   10. A/B Compare Mode für Bildvergleich"
echo ""

echo "🔧 ENTWICKLUNGS-STACK:"
echo "   • Backend: PHP 8.0+, WordPress 6.5+"
echo "   • Frontend: Vanilla JavaScript (ES6+), SCSS"
echo "   • Testing: PHPUnit, Playwright, WP-CLI"
echo "   • Build: Webpack 5, Composer, NPM"
echo "   • CI/CD: GitHub Actions, Automated Testing"
echo ""

echo "📂 PROJEKT-STRUKTUR:"
echo "   src/Domain/           # Business Logic"
echo "   src/Application/      # Use Cases (CQRS)"
echo "   src/Infrastructure/   # WordPress Integration"
echo "   src/Presentation/     # Controllers & Views"
echo "   assets/src/           # Frontend Source"
echo "   tests/                # Alle Tests"
echo "   templates/            # Frontend Templates"
echo ""

echo "🤖 AI-ASSISTANT BEREIT!"
echo ""
echo "Nutze in VS Code:"
echo "   • !context + Tab     → Vollständiger Kontext"
echo "   • !task + Tab        → Schneller Prompt"
echo "   • !test + Tab        → Testing-Template"
echo "   • !arch + Tab        → Architektur-Pattern"
echo ""
echo "Oder kopiere diesen Standard-Prompt:"
echo ""
echo "📋 STANDARD-PROMPT:"
echo "-------------------"
cat << 'EOF'
@workspace Kontext: MarkusLehr ClientGallerie WordPress Plugin

🎯 Beachte diese 3 Hauptdateien:
- project-overview.md (Navigation & Top 20 Prioritäten)
- specs.md (Vollständige Spezifikation - 817 Zeilen)
- entwicklungsrichtlinien.md (Technische Architektur)

🏗️ Kernprinzipien:
- Objektorientierte Architektur (SOLID)
- Schlanke Bootstrap-Datei (~100 Zeilen)
- Domain-Driven Design + CQRS
- WordPress 6.5+ + ThemeForest ready
- Picdrop.com-inspirierte UI
- Testing: WP-CLI + Browser + Unit

🚀 Aufgabe: [HIER IHRE KONKRETE AUFGABE EINFÜGEN]
EOF
echo "-------------------"
echo ""

# Optional: VS Code öffnen mit allen Dateien
read -p "📝 Sollen alle Kontext-Dateien in VS Code geöffnet werden? (y/n): " open_vscode

if [ "$open_vscode" = "y" ] || [ "$open_vscode" = "Y" ]; then
    echo "🔄 Öffne VS Code mit Kontext-Dateien..."
    code project-overview.md specs.md entwicklungsrichtlinien.md
    echo "✅ VS Code geöffnet!"
fi

echo ""
echo "🎉 READY FOR AI-POWERED DEVELOPMENT! 🚀"
echo "==============================================="
