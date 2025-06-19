#!/bin/bash

# Code-Strukturbaum Analyzer
# Führt alle Dependency-Analysen aus und generiert Reports

clear

echo "🔍 ==============================================="
echo "   CODE-STRUKTURBAUM & DEPENDENCY-ANALYSE"
echo "==============================================="
echo ""

# Erstelle Ausgabe-Verzeichnis
mkdir -p docs/analysis

echo "📊 Starte Code-Analyse..."

# 1. PHP Dependency-Analyse
echo "🔎 Analysiere PHP-Abhängigkeiten..."
if command -v php >/dev/null 2>&1; then
    php scripts/analyze-dependencies.php > docs/analysis/dependencies.json
    echo "✅ PHP-Abhängigkeiten analysiert"
else
    echo "⚠️ PHP nicht verfügbar - überspringe PHP-Analyse"
fi

# 2. JavaScript Dependency-Analyse  
echo "🔎 Analysiere JavaScript-Abhängigkeiten..."
if [ -f "package.json" ]; then
    npm list --json > docs/analysis/js-dependencies.json 2>/dev/null
    echo "✅ JavaScript-Abhängigkeiten analysiert"
else
    echo "⚠️ package.json nicht gefunden - überspringe JS-Analyse"
fi

# 3. Datei-Struktur-Analyse
echo "🔎 Analysiere Datei-Struktur..."
find . -name "*.php" -not -path "./vendor/*" -not -path "./node_modules/*" | while read file; do
    lines=$(wc -l < "$file")
    if [ $lines -gt 200 ]; then
        echo "⚠️ WARNUNG: $file hat $lines Zeilen (>200)" >> docs/analysis/large-files.txt
    fi
done

# 4. Test-Coverage-Analyse
echo "🔎 Prüfe Test-Coverage..."
find . -name "*Test.php" -not -path "./vendor/*" | wc -l > docs/analysis/test-count.txt
find . -name "*.php" -not -path "./vendor/*" -not -path "./tests/*" -not -name "*Test.php" | wc -l > docs/analysis/source-count.txt

test_files=$(cat docs/analysis/test-count.txt)
source_files=$(cat docs/analysis/source-count.txt)

if [ $source_files -gt 0 ]; then
    coverage=$(echo "scale=2; $test_files / $source_files * 100" | bc -l 2>/dev/null || echo "0")
    echo "Test-Coverage: ${coverage}% ($test_files Tests für $source_files Dateien)" > docs/analysis/coverage.txt
fi

# 5. Doppelten Code finden
echo "🔎 Suche nach doppeltem Code..."
if command -v fdupes >/dev/null 2>&1; then
    fdupes -r . --exclude=vendor --exclude=node_modules > docs/analysis/duplicate-files.txt
    echo "✅ Duplikat-Analyse abgeschlossen"
else
    echo "⚠️ fdupes nicht installiert - überspringe Duplikat-Analyse"
fi

# 6. Generiere zusammenfassenden Report
echo "📝 Generiere Struktur-Report..."
cat > docs/analysis/structure-report.md << EOF
# Code-Strukturbaum Report
*Generiert am: $(date)*

## 📊 Übersicht

### Datei-Statistiken
- **Quell-Dateien:** $(cat docs/analysis/source-count.txt 2>/dev/null || echo "N/A")
- **Test-Dateien:** $(cat docs/analysis/test-count.txt 2>/dev/null || echo "N/A") 
- **Test-Coverage:** $(cat docs/analysis/coverage.txt 2>/dev/null || echo "N/A")

### Code-Qualität
$([ -f docs/analysis/large-files.txt ] && echo "⚠️ **Große Dateien gefunden:**" && cat docs/analysis/large-files.txt || echo "✅ Alle Dateien unter 200 Zeilen")

### Abhängigkeiten
- **PHP-Dependencies:** $([ -f docs/analysis/dependencies.json ] && echo "✅ Analysiert" || echo "❌ Nicht verfügbar")
- **JS-Dependencies:** $([ -f docs/analysis/js-dependencies.json ] && echo "✅ Analysiert" || echo "❌ Nicht verfügbar")

### Code-Duplikation
$([ -s docs/analysis/duplicate-files.txt ] && echo "⚠️ **Duplikate gefunden:**" && head -10 docs/analysis/duplicate-files.txt || echo "✅ Keine Duplikate gefunden")

## 🎯 Empfehlungen

$([ -f docs/analysis/large-files.txt ] && echo "1. **Große Dateien aufteilen:** Dateien über 200 Zeilen in kleinere Einheiten unterteilen")
$([ $test_files -lt $source_files ] && echo "2. **Test-Coverage erhöhen:** Mehr Unit-Tests für bessere Abdeckung")
$([ -s docs/analysis/duplicate-files.txt ] && echo "3. **Duplikate entfernen:** Code-Doppelungen bereinigen")

## 📁 Datei-Struktur
\`\`\`
$(tree -I 'vendor|node_modules|.git' -L 3 2>/dev/null || find . -type d -not -path "./vendor*" -not -path "./node_modules*" -not -path "./.git*" | head -20)
\`\`\`
EOF

echo ""
echo "✅ ANALYSE ABGESCHLOSSEN!"
echo ""
echo "📋 Generierte Reports:"
echo "   📊 docs/analysis/structure-report.md    # Haupt-Report"
echo "   🔗 docs/analysis/dependencies.json      # PHP-Abhängigkeiten"
echo "   📦 docs/analysis/js-dependencies.json   # JS-Abhängigkeiten"
echo "   📏 docs/analysis/large-files.txt        # Große Dateien"
echo "   🧪 docs/analysis/coverage.txt           # Test-Coverage"
echo "   📋 docs/analysis/duplicate-files.txt    # Code-Duplikate"
echo ""
echo "🔄 Führe dieses Script regelmäßig aus um die Struktur zu überwachen!"
echo "==============================================="
