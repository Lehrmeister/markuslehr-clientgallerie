#!/bin/bash
# Live Log Monitor für VS Code
# Zeigt aktuelle Plugin-Logs im Terminal

LOG_DIR="/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/logs"
LOG_FILE="$LOG_DIR/mlcg-current.log"

echo "🔍 MLCG Live Log Monitor"
echo "========================"
echo "Log-Datei: $LOG_FILE"
echo ""

if [ ! -f "$LOG_FILE" ]; then
    echo "⚠️  Log-Datei existiert noch nicht. Warten auf erste Logs..."
    echo ""
fi

# Erstelle Log-Verzeichnis falls nicht vorhanden
mkdir -p "$LOG_DIR"

# Live-Monitoring mit farbiger Ausgabe
tail -f "$LOG_FILE" 2>/dev/null | while read line; do
    # Extrahiere Timestamp und Level
    if [[ $line =~ \[([^\]]+)\]\ ([A-Z]+): ]]; then
        timestamp="${BASH_REMATCH[1]}"
        level="${BASH_REMATCH[2]}"
        
        # Farbcodierung basierend auf Log-Level
        case $level in
            "EMERGENCY"|"ALERT"|"CRITICAL")
                echo -e "\033[1;31m$line\033[0m"  # Rot, Bold
                ;;
            "ERROR")
                echo -e "\033[0;31m$line\033[0m"  # Rot
                ;;
            "WARNING")
                echo -e "\033[0;33m$line\033[0m"  # Gelb
                ;;
            "NOTICE")
                echo -e "\033[0;36m$line\033[0m"  # Cyan
                ;;
            "INFO")
                echo -e "\033[0;32m$line\033[0m"  # Grün
                ;;
            "DEBUG")
                echo -e "\033[0;37m$line\033[0m"  # Grau
                ;;
            *)
                echo "$line"  # Standard
                ;;
        esac
    else
        echo "$line"
    fi
done
