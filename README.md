# MarkusLehr ClientGallerie

> **Professional WordPress Gallery Plugin with Picdrop-inspired UI and intelligent logging**

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🎯 Project Status

**CLEAN, MINIMAL, ERROR-FREE BASE** ✅  
This repository contains a completely cleaned and stabilized plugin foundation, ready for controlled Gallery development.

### ✅ What's Working
- **No PHP Fatal Errors** - Plugin activates and runs without warnings
- **Database System** - Tables created and functional (`wp_ml_clientgallerie_clients`, `wp_ml_clientgallerie_log_entries`)
- **Repository System** - Full CRUD operations with safety checks
- **Logging System** - Comprehensive logging infrastructure
- **Modern Architecture** - Clean separation of concerns with DDD principles

### 🚧 What's Removed
- All Gallery/Frontend components (intentionally removed for clean restart)
- Image processing systems
- Rating systems  
- Test and debug files
- Overcomplicated legacy code

## Systempasswort
**Systempasswort:** `Kiwi_2025!A`

## 🏗️ Architecture

```
markuslehr_clientgallerie/
├── clientgallerie.php              # Main plugin file (minimal, stable)
├── src/
│   ├── Application/
│   │   ├── Controller/             # Admin controllers
│   │   └── Service/                # Application services
│   ├── Infrastructure/
│   │   ├── Database/
│   │   │   ├── Repository/         # Data access layer
│   │   │   ├── Schema/             # Database schema management
│   │   │   └── Migration/          # Database migrations
│   │   ├── Logging/                # Comprehensive logging system
│   │   └── Container/              # Dependency injection
│   └── Domain/                     # (Ready for Gallery domain logic)
├── composer.json                   # Dependencies and autoloading
└── entwicklungsrichtlinien.md     # Development guidelines
```

## 🚀 Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Lehrmeister/markuslehr-clientgallerie.git
   cd markuslehr-clientgallerie
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Activate in WordPress:**
   - Copy to `wp-content/plugins/markuslehr_clientgallerie/`
   - Activate via WordPress admin or WP-CLI:
     ```bash
     wp plugin activate markuslehr_clientgallerie
     ```

## 📊 Database Tables

The plugin creates two main tables:

- **`wp_ml_clientgallerie_clients`** - Client management with extended permissions
- **`wp_ml_clientgallerie_log_entries`** - Comprehensive activity logging

## 🔧 Development

### Requirements
- **WordPress:** 6.0+
- **PHP:** 8.0+
- **MySQL:** 5.7+ or MariaDB 10.3+

### Git Workflow
```bash
# Work on features
git checkout -b feature/gallery-system
git commit -m "feat: Add gallery management"
git push origin feature/gallery-system

# Merge to main
git checkout main
git merge feature/gallery-system
```

## 📋 Roadmap

### Phase 1: Gallery Foundation ⏳
- [ ] Gallery entity and repository
- [ ] Image upload and processing
- [ ] Basic gallery management

### Phase 2: Client Interface 🔮
- [ ] Client authentication system
- [ ] Gallery viewing interface
- [ ] Download permissions

### Phase 3: Advanced Features 🌟
- [ ] Rating and feedback system
- [ ] Notifications
- [ ] Advanced permissions

## 🐛 Known Issues

None! This is a clean, stable base ready for development.

## 📝 Changelog

### v1.0.0 - Clean Base (2025-06-19)
- ✅ **FIXED:** All PHP Fatal Errors resolved
- ✅ **CLEANED:** Removed all Gallery/Image/Rating legacy systems  
- ✅ **STABILIZED:** Repository system with safety checks
- ✅ **CREATED:** Database tables with consistent naming
- ✅ **IMPLEMENTED:** Comprehensive logging system

### Previous Versions
- Complete cleanup and restart from overcomplicated legacy codebase

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2+ License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Markus Lehr**
- GitHub: [@Lehrmeister](https://github.com/Lehrmeister)

---

> **Ready for Gallery Development!** 🎨  
> This clean foundation provides the perfect starting point for building a professional, Picdrop-inspired gallery system with modern WordPress development practices.

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
