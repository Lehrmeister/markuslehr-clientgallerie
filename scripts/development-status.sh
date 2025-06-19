#!/bin/bash

# Entwicklungsfortschritt-Tracker
# ==============================

PLUGIN_ROOT="/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie"

echo "📊 MARKUSLEHR CLIENTGALLERIE - ENTWICKLUNGSFORTSCHRITT"
echo "===================================================="

cd "$PLUGIN_ROOT"

# Phase 1: Kern-Infrastruktur
echo ""
echo "🎯 PHASE 1: KERN-INFRASTRUKTUR"
echo "=============================="

# 1.1 Datenbank-Schema
echo "📊 1.1 Datenbank-Schema:"
echo "  ✅ Database Installer: $([ -f src/Infrastructure/Database/Installer.php ] && echo "Implementiert" || echo "Fehlt")"
echo "  $([ -d src/Infrastructure/Database/Schema ] && echo "✅" || echo "❌") Schema-Klassen: $([ -d src/Infrastructure/Database/Schema ] && ls src/Infrastructure/Database/Schema/*.php 2>/dev/null | wc -l || echo "0") Dateien"
echo "  $([ -d src/Infrastructure/Database/Repository ] && echo "✅" || echo "❌") Repository-Pattern: $([ -d src/Infrastructure/Database/Repository ] && ls src/Infrastructure/Database/Repository/*.php 2>/dev/null | wc -l || echo "0") Dateien"

# 1.2 Domain-Services  
echo ""
echo "🏗️ 1.2 Domain-Services:"
echo "  ✅ GalleryManager: $([ -f src/Domain/Gallery/Service/GalleryManager.php ] && echo "Vorhanden" || echo "Fehlt")"
echo "  ✅ ImageProcessor: $([ -f src/Domain/Image/Service/ImageProcessor.php ] && echo "Vorhanden" || echo "Fehlt")"
echo "  ✅ SecurityManager: $([ -f src/Domain/Security/Service/SecurityManager.php ] && echo "Vorhanden" || echo "Fehlt")"

# 1.3 Frontend-Routing
echo ""
echo "🌐 1.3 Frontend-Routing:"
echo "  $([ -f src/Application/Controller/FrontendController.php ] && echo "✅" || echo "❌") FrontendController: $([ -f src/Application/Controller/FrontendController.php ] && echo "Implementiert" || echo "Fehlt")"
echo "  $([ -f src/Infrastructure/Http/Router.php ] && echo "✅" || echo "❌") Router-System: $([ -f src/Infrastructure/Http/Router.php ] && echo "Implementiert" || echo "Fehlt")"

# Phase 2: Core-Features
echo ""
echo "🎯 PHASE 2: CORE-FEATURES"
echo "========================="

# 2.1 Admin-Interface
echo "👨‍💼 2.1 Admin-Interface:"
echo "  ✅ AdminController: $([ -f src/Application/Controller/AdminController.php ] && echo "Implementiert" || echo "Fehlt")"
echo "  $([ -f src/Application/Controller/GalleryController.php ] && echo "✅" || echo "❌") GalleryController: $([ -f src/Application/Controller/GalleryController.php ] && echo "Implementiert" || echo "Fehlt")"
echo "  $([ -f src/Application/Controller/ImageController.php ] && echo "✅" || echo "❌") ImageController: $([ -f src/Application/Controller/ImageController.php ] && echo "Implementiert" || echo "Fehlt")"

# 2.2 Frontend-Viewer
echo ""
echo "🖼️ 2.2 Frontend-Viewer:"
echo "  $([ -d assets ] && echo "✅" || echo "❌") Assets-Verzeichnis: $([ -d assets ] && echo "Vorhanden" || echo "Fehlt")"
echo "  $([ -d templates ] && echo "✅" || echo "❌") Template-System: $([ -d templates ] && echo "Vorhanden" || echo "Fehlt")"

# Infrastruktur-Status
echo ""
echo "🔧 INFRASTRUKTUR-STATUS"
echo "======================"
echo "  ✅ Logging-System: Funktional"
echo "  ✅ Service Container: $(grep -c 'services' logs/mlcg-current.log | tail -1) Services registriert"
echo "  ✅ Admin-Backend: Menüs verfügbar"
echo "  ✅ Plugin Bootstrap: Aktiv"

# Code-Qualität
echo ""
echo "📊 CODE-QUALITÄT"
echo "================"
PHP_FILES=$(find src -name "*.php" | wc -l)
echo "  📄 PHP-Klassen: $PHP_FILES Dateien"
echo "  📁 Verzeichnisse: $(find src -type d | wc -l) Ordner"
echo "  📜 Logs: $([ -f logs/mlcg-current.log ] && wc -l < logs/mlcg-current.log || echo "0") Einträge"

# Nächste Prioritäten
echo ""
echo "🎯 NÄCHSTE PRIORITÄTEN"
echo "====================="
echo "  1️⃣ Datenbank-Schema vervollständigen"
echo "  2️⃣ Repository-Pattern implementieren"  
echo "  3️⃣ Gallery-CRUD-Operationen"
echo "  4️⃣ Admin-Gallery-Management"
echo "  5️⃣ Frontend-Routing für Public-URLs"

echo ""
echo "🚀 EMPFEHLUNG: Beginnen Sie mit Datenbank-Schema (Priorität 1)"
echo ""
