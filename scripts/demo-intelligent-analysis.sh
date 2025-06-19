#!/bin/bash
# Demo-Script für die neuen Code-Analyse-Tools
# Zeigt alle Funktionen der verbesserten Architektur-Analyse

echo "🎯 DEMONSTRATION: VERANTWORTLICHKEITS-BASIERTE CODE-ANALYSE"
echo "=========================================================="
echo ""

# 1. Intelligent Code Analysis
echo "1️⃣  INTELLIGENTE CODE-ANALYSE (statt starrer Zeilenlimits)"
echo "-----------------------------------------------------------"
echo "Analysiert nach:"
echo "• Zyklomatische Komplexität < 10 pro Methode"
echo "• Kopplungsgrad < 7 Abhängigkeiten"
echo "• Kohäsionsgrad > 70%"
echo "• Single Responsibility Score"
echo ""

if [ -f "scripts/smart-code-analyzer.php" ]; then
    echo "📊 Ausführung:"
    php scripts/smart-code-analyzer.php . | head -30
    echo ""
fi

# 2. Dead Code Detection
echo "2️⃣  DEAD CODE & VERWAISTE TESTS DETECTION"
echo "-------------------------------------------"
echo "Findet:"
echo "• Unbenutzte Klassen und Methoden"
echo "• Verwaiste Test-Dateien ohne Source-Code"
echo "• Dateien ohne Referenzen"
echo ""

if [ -f "scripts/find-dead-code.php" ]; then
    echo "🗑️ Ausführung:"
    php scripts/find-dead-code.php . | head -20
    echo ""
fi

# 3. File Structure Validation
echo "3️⃣  DATEI-STRUKTUR & NAMING CONVENTIONS"
echo "----------------------------------------"
echo "Validiert:"
echo "• PascalCase für Klassen, kebab-case für Verzeichnisse"
echo "• Eine Klasse pro Datei (Single Responsibility)"
echo "• Dateiname = Klassenname"
echo "• Erforderliche Dateien (README.md, tests, etc.)"
echo ""

if [ -f "scripts/validate-file-structure.php" ]; then
    echo "🏗️ Ausführung:"
    php scripts/validate-file-structure.php . | head -25
    echo ""
fi

# 4. Verfügbare NPM Scripts
echo "4️⃣  VERFÜGBARE ANALYSE-COMMANDS"
echo "--------------------------------"
echo "📦 NPM Scripts:"
echo "• npm run check:responsibility  - Single Responsibility Analysis"
echo "• npm run check:coupling       - Kopplungsgrad prüfen"
echo "• npm run find:orphaned        - Verwaiste Tests finden"
echo "• npm run validate:structure   - Struktur-Validierung"
echo "• npm run report:full          - Vollständiger Qualitäts-Report"
echo ""

echo "🐘 Composer Scripts:" 
echo "• composer check:responsibility - PHP Single Responsibility"
echo "• composer check:coupling      - PHP Kopplungsanalyse"
echo "• composer check:cohesion      - PHP Kohäsionsanalyse"
echo "• composer quality:full        - Vollständige PHP-Qualitäts-Analyse"
echo ""

# 5. VS Code Integration
echo "5️⃣  VS CODE INTEGRATION"
echo "------------------------"
echo "🎮 VS Code Tasks verfügbar:"
echo "• 'Check Code Responsibility'  - Ctrl+Shift+P → Tasks: Run Task"
echo "• 'Find Dead Code'"
echo "• 'Validate File Structure'"
echo "• 'Full Quality Report'"
echo ""

echo "✨ KI-CONTEXT ENHANCEMENT"
echo "-------------------------"
echo "Der .vscode/ai-context.sh wird jetzt automatisch erweitert um:"
echo "• Live-Verantwortlichkeits-Analyse"
echo "• Dead-Code-Detection-Report"
echo "• Struktur-Validierungs-Summary"
echo ""

echo "📋 ZUSAMMENFASSUNG DER VERBESSERUNGEN"
echo "====================================="
echo "✅ Starre Zeilenlimits → Intelligente Verantwortlichkeits-Metriken"
echo "✅ Code-Duplikation → Live-Dependency-Tracking"
echo "✅ Verwaiste Tests → Automatische Orphan-Detection"
echo "✅ Datei-Chaos → Enforced Naming Conventions"
echo "✅ Willkürliche Aufteilung → Fachliche Kohäsion"
echo ""

echo "🚀 NÄCHSTE SCHRITTE"
echo "==================="
echo "1. Führen Sie 'npm run report:full' aus für vollständige Analyse"
echo "2. Verwenden Sie VS Code Tasks für kontinuierliche Überwachung"  
echo "3. Integrieren Sie die Scripts in Ihre CI/CD Pipeline"
echo "4. Nutzen Sie den erweiterten AI-Context für bessere KI-Assistenz"
echo ""

echo "Demo abgeschlossen! 🎉"
