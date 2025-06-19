#!/bin/bash

# AI Agent: Intelligente Dateierstellung und Organisation
# ======================================================
# Dieses Skript hilft dem AI-Assistenten, Dateien korrekt zu organisieren

PLUGIN_ROOT="/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie"

# Funktion: Bestimme das richtige Verzeichnis für eine Datei
determine_file_location() {
    local filename="$1"
    local purpose="$2"  # test, debug, docs, production, etc.
    
    case "$purpose" in
        "test"|"debug")
            echo "scripts/temp-tests/"
            ;;
        "troubleshooting"|"debug-docs")
            echo "docs/troubleshooting/"
            ;;
        "documentation"|"docs")
            echo "docs/"
            ;;
        "script"|"utility")
            echo "scripts/"
            ;;
        "production"|"source")
            echo "src/"
            ;;
        *)
            # Auto-detect basierend auf Dateiname
            if [[ "$filename" =~ (test|debug|demo) ]]; then
                echo "scripts/temp-tests/"
            elif [[ "$filename" =~ \.(md|txt)$ && "$filename" =~ (TROUBLESHOOT|DEBUG|PROBLEM|FEHLER) ]]; then
                echo "docs/troubleshooting/"
            elif [[ "$filename" =~ \.(md|txt)$ ]]; then
                echo "docs/"
            elif [[ "$filename" =~ \.(sh|php)$ && "$filename" =~ (script|util|tool) ]]; then
                echo "scripts/"
            else
                echo "scripts/temp-tests/"  # Default für unklare Fälle
            fi
            ;;
    esac
}

# Funktion: Erstelle Datei im korrekten Verzeichnis
create_organized_file() {
    local filename="$1"
    local content="$2"
    local purpose="$3"
    
    local target_dir=$(determine_file_location "$filename" "$purpose")
    local full_path="$PLUGIN_ROOT/$target_dir$filename"
    
    # Erstelle Verzeichnis falls nicht vorhanden
    mkdir -p "$(dirname "$full_path")"
    
    # Erstelle Datei
    echo "$content" > "$full_path"
    
    echo "📁 Datei erstellt: $target_dir$filename"
    
    # Wenn es ein temporäres Test-File ist, füge Auto-Cleanup hinzu
    if [[ "$target_dir" == "scripts/temp-tests/" ]]; then
        echo "⏰ Hinweis: Temporäre Datei wird bei nächstem Cleanup automatisch entfernt."
    fi
}

# Funktion: Räume Hauptverzeichnis auf
cleanup_root_directory() {
    cd "$PLUGIN_ROOT"
    
    # Definiere was ins Hauptverzeichnis gehört
    ALLOWED_IN_ROOT=(
        "clientgallerie.php"
        "composer.json"
        "package.json" 
        "README.md"
        "specs.md"
        "entwicklungsrichtlinien.md"
        "project-overview.md"
        "STRUKTUR.md"
        "AI-INTEGRATION.md"
    )
    
    # Finde Dateien, die nicht ins Hauptverzeichnis gehören
    for file in *.php *.md *.html *.js *.txt; do
        if [[ -f "$file" ]]; then
            local should_move=true
            
            # Prüfe ob Datei in ALLOWED_IN_ROOT ist
            for allowed in "${ALLOWED_IN_ROOT[@]}"; do
                if [[ "$file" == "$allowed" ]]; then
                    should_move=false
                    break
                fi
            done
            
            if [[ "$should_move" == true ]]; then
                local target_dir=$(determine_file_location "$file" "auto")
                mkdir -p "$target_dir"
                mv "$file" "$target_dir"
                echo "📦 Verschoben: $file → $target_dir"
            fi
        fi
    done
}

# Funktion: Zeige AI-Agent-Anweisungen
show_ai_instructions() {
    cat << 'EOF'
🤖 AI-AGENT ANWEISUNGEN
======================

Beim Erstellen von Dateien für das MarkusLehr ClientGallerie Plugin:

📋 DATEI-ORGANISATION:
├── 🏠 Plugin-Root/           # NUR: clientgallerie.php, composer.json, README.md
├── 📁 src/                   # Produktions-Code (Klassen, Services)
├── 📁 scripts/               # Entwicklungs-Tools, Utilities
│   └── 📁 temp-tests/        # Temporäre Test-Dateien
├── 📁 docs/                  # Dokumentation
│   └── 📁 troubleshooting/   # Debug/Problem-Dokumentation
└── 📁 logs/                  # Log-Dateien

🛠️ VERWENDUNG:
- create_organized_file "filename.php" "content" "test"
- create_organized_file "DEBUG.md" "content" "troubleshooting"  
- cleanup_root_directory

🚫 NICHT ERSTELLEN:
- Keine temporären Dateien im Hauptverzeichnis
- Keine Test-URLs im Production-Pfad
- Keine Debug-Dokumentation im Root

✅ STATTDESSEN:
- scripts/temp-tests/ für alle Test-Dateien
- docs/troubleshooting/ für Debug-Dokumentation
- Verwende bestehende AI-Integration-Scripts

EOF
}

# Hauptfunktion
case "${1:-help}" in
    "cleanup")
        cleanup_root_directory
        ;;
    "create")
        create_organized_file "$2" "$3" "$4"
        ;;
    "instructions"|"help")
        show_ai_instructions
        ;;
    *)
        echo "Usage: $0 {cleanup|create|instructions}"
        echo "  cleanup                              - Räume Hauptverzeichnis auf"
        echo "  create <filename> <content> <type>   - Erstelle Datei im korrekten Verzeichnis"
        echo "  instructions                         - Zeige AI-Agent-Anweisungen"
        ;;
esac
