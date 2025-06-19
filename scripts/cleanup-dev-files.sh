#!/bin/bash

# Cleanup-Skript für temporäre Entwicklungsdateien
# ================================================

echo "🧹 MarkusLehr ClientGallerie - Development Cleanup"
echo "================================================="

# Zielverzeichnisse
PLUGIN_ROOT="/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie"
TEMP_DIR="$PLUGIN_ROOT/scripts/temp-tests"
DOCS_DIR="$PLUGIN_ROOT/docs"

cd "$PLUGIN_ROOT"

echo ""
echo "📁 Aktuelle Verzeichnisstruktur analysieren..."

# 1. Temporäre Test-Dateien identifizieren
echo ""
echo "🔍 Temporäre Test-Dateien im Hauptverzeichnis:"
TEMP_FILES=(
    "admin-test.php"
    "backend-test-fixed.php" 
    "demo-backend.php"
    "menu-check.php"
    "test-*.php"
    "*-test.php"
    "debug-*.php"
)

FOUND_TEMP_FILES=()
for pattern in "${TEMP_FILES[@]}"; do
    if ls $pattern 2>/dev/null; then
        FOUND_TEMP_FILES+=($pattern)
        echo "  ❌ Gefunden: $pattern"
    fi
done

# 2. Temporäre Dokumentation identifizieren
echo ""
echo "📚 Temporäre Dokumentation im Hauptverzeichnis:"
TEMP_DOCS=(
    "BACKEND-TROUBLESHOOTING.md"
    "FEHLERBEHEBUNG.md" 
    "PROBLEM-BEHOBEN.md"
    "DEBUG-*.md"
    "TEST-*.md"
)

FOUND_TEMP_DOCS=()
for pattern in "${TEMP_DOCS[@]}"; do
    if ls $pattern 2>/dev/null; then
        FOUND_TEMP_DOCS+=($pattern)
        echo "  ❌ Gefunden: $pattern"
    fi
done

# 3. Cleanup durchführen (mit Bestätigung)
if [ ${#FOUND_TEMP_FILES[@]} -gt 0 ] || [ ${#FOUND_TEMP_DOCS[@]} -gt 0 ]; then
    echo ""
    echo "🚨 Cleanup erforderlich!"
    echo ""
    read -p "Möchten Sie die temporären Dateien aufräumen? (y/N): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Verzeichnisse erstellen falls nicht vorhanden
        mkdir -p "$TEMP_DIR"
        mkdir -p "$DOCS_DIR/troubleshooting"
        
        # Test-Dateien verschieben
        if [ ${#FOUND_TEMP_FILES[@]} -gt 0 ]; then
            echo "📦 Verschiebe Test-Dateien nach scripts/temp-tests/..."
            for file in "${FOUND_TEMP_FILES[@]}"; do
                if [ -f "$file" ]; then
                    mv "$file" "$TEMP_DIR/"
                    echo "  ✅ $file → scripts/temp-tests/"
                fi
            done
        fi
        
        # Dokumentation verschieben
        if [ ${#FOUND_TEMP_DOCS[@]} -gt 0 ]; then
            echo "📚 Verschiebe Dokumentation nach docs/troubleshooting/..."
            for file in "${FOUND_TEMP_DOCS[@]}"; do
                if [ -f "$file" ]; then
                    mv "$file" "$DOCS_DIR/troubleshooting/"
                    echo "  ✅ $file → docs/troubleshooting/"
                fi
            done
        fi
        
        echo ""
        echo "✅ Cleanup abgeschlossen!"
        
    else
        echo "❌ Cleanup abgebrochen."
    fi
else
    echo ""
    echo "✅ Hauptverzeichnis ist bereits sauber!"
fi

# 4. Zeige empfohlene Struktur
echo ""
echo "📋 Empfohlene Verzeichnisstruktur:"
echo "================================="
echo "📁 Plugin-Root/"
echo "  ├── 📄 clientgallerie.php          # Haupt-Plugin-Datei"
echo "  ├── 📄 composer.json, package.json  # Dependencies"
echo "  ├── 📁 src/                        # Produktions-Code"
echo "  ├── 📁 scripts/                    # Entwicklungs-Skripte"
echo "  │   ├── 📁 temp-tests/             # Temporäre Test-Dateien"
echo "  │   └── 📄 *.sh, *.php             # Utility-Skripte"
echo "  ├── 📁 docs/                       # Dokumentation"
echo "  │   ├── 📁 troubleshooting/        # Debug-Dokumentation"
echo "  │   └── 📄 *.md                    # Hauptdokumentation"
echo "  ├── 📁 logs/                       # Log-Dateien"
echo "  └── 📁 .vscode/                    # IDE-Konfiguration"

# 5. Zeige verfügbare Scripts
echo ""
echo "🛠️  Verfügbare Entwicklungs-Scripts:"
echo "===================================="
ls -1 scripts/*.sh scripts/*.php 2>/dev/null | sed 's/scripts\//  /' || echo "  Keine Scripts gefunden"

echo ""
echo "🎯 Nächste Schritte:"
echo "  1. Verwenden Sie scripts/ für alle Entwicklungs-Tools"
echo "  2. Temporäre Dateien nach scripts/temp-tests/"  
echo "  3. Debug-Dokumentation nach docs/troubleshooting/"
echo "  4. Produktions-Code nur in src/ und Haupt-Plugin-Datei"
echo ""
echo "🚀 Für weitere Entwicklung: Nutzen Sie die AI-Integration-Scripts!"
