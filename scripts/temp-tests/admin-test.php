<?php
// Minimal WordPress-Admin-Simulation zum Testen des Plugin-Menüs
require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-config.php';
require_once '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-admin/admin.php';

// Force Admin-Kontext
define('WP_ADMIN', true);
wp_set_current_user(1); // Admin-User

// Plugin-spezifische Funktionen testen
do_action('plugins_loaded');
do_action('admin_menu');

global $menu, $submenu;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Admin - Plugin Menu Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; }
        .admin-menu { background: #23282d; color: #fff; padding: 20px; margin-bottom: 20px; }
        .menu-item { padding: 10px; border-bottom: 1px solid #333; }
        .submenu-item { padding: 5px 20px; color: #ccc; }
        .content { padding: 20px; background: #fff; border: 1px solid #ccd0d4; }
    </style>
</head>
<body>
    <h1>WordPress Admin - Plugin Menu Test</h1>
    
    <div class="admin-menu">
        <h2>WordPress Admin-Menü</h2>
        
        <?php if (!empty($menu)): ?>
            <?php foreach ($menu as $item): ?>
                <?php if (isset($item[2]) && strpos($item[2], 'mlcg') !== false): ?>
                    <div class="menu-item">
                        <strong><?php echo esc_html($item[0]); ?></strong> (<?php echo esc_html($item[2]); ?>)
                        
                        <?php if (!empty($submenu[$item[2]])): ?>
                            <?php foreach ($submenu[$item[2]] as $subitem): ?>
                                <div class="submenu-item">
                                    → <?php echo esc_html($subitem[0]); ?> (<?php echo esc_html($subitem[2]); ?>)
                                    
                                    <?php if ($subitem[2] === 'mlcg-logs'): ?>
                                        <a href="?page=mlcg-logs" style="color: #0073aa; margin-left: 10px;">[Öffnen]</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Keine Menü-Einträge gefunden.</p>
        <?php endif; ?>
    </div>
    
    <div class="content">
        <?php if (isset($_GET['page']) && $_GET['page'] === 'mlcg-logs'): ?>
            <h2>System Logs</h2>
            <?php
            // AdminController-Instanz erstellen und Log-Seite rendern
            try {
                $adminController = new \MarkusLehr\ClientGallerie\Application\Controller\AdminController();
                $adminController->initialize();
                $adminController->logsPage();
            } catch (Exception $e) {
                echo '<p style="color: red;">Fehler beim Laden der Log-Seite: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
        <?php else: ?>
            <h2>WordPress Plugin: MarkusLehr ClientGallerie</h2>
            <p><strong>Status:</strong> Aktiv</p>
            <p><strong>Version:</strong> 1.0.0</p>
            <p><strong>Admin-Menüs registriert:</strong> 
                <?php echo !empty($submenu['mlcg-galleries']) ? 'Ja (' . count($submenu['mlcg-galleries']) . ' Untermenüs)' : 'Nein'; ?>
            </p>
            
            <?php if (!empty($submenu['mlcg-galleries'])): ?>
                <h3>Verfügbare Menüs:</h3>
                <ul>
                    <?php foreach ($submenu['mlcg-galleries'] as $subitem): ?>
                        <li>
                            <?php if ($subitem[2] === 'mlcg-logs'): ?>
                                <a href="?page=mlcg-logs"><?php echo esc_html($subitem[0]); ?></a>
                            <?php else: ?>
                                <?php echo esc_html($subitem[0]); ?> (<?php echo esc_html($subitem[2]); ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <h3>Plugin-Info:</h3>
            <ul>
                <li><strong>Plugin-Datei:</strong> <?php echo esc_html(plugin_basename(__FILE__)); ?></li>
                <li><strong>Aktueller User:</strong> <?php echo esc_html(wp_get_current_user()->display_name); ?> (ID: <?php echo get_current_user_id(); ?>)</li>
                <li><strong>User kann Plugin verwalten:</strong> <?php echo current_user_can('manage_options') ? 'Ja' : 'Nein'; ?></li>
                <li><strong>WP_DEBUG:</strong> <?php echo WP_DEBUG ? 'Aktiviert' : 'Deaktiviert'; ?></li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
