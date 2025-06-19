<?php
// WordPress-Backend-URL-Test für Plugin-Menü
// Besuche: http://localhost/wordpress/wp-content/plugins/markuslehr_clientgallerie/menu-check.php

// WordPress-Kern laden
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $wp_root . '/wp-config.php';
require_once $wp_root . '/wp-load.php';

// Admin-Kontext setzen (wichtig!)
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

// Teste mit Admin-User
$admin_user = get_user_by('login', 'MarkusLehr');
if ($admin_user) {
    wp_set_current_user($admin_user->ID);
}

// WordPress Admin-Funktionen laden
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Menu-Hooks auslösen
do_action('admin_menu');

// Hole Menu-Daten
global $menu, $submenu;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Plugin Menu Check</title>
    <style>
        body { font-family: -apple-system, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .menu-item { background: #f8f9fa; padding: 8px; margin: 5px 0; border-left: 3px solid #007cba; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 MarkusLehr ClientGallerie - Menu Check</h1>
    
    <div class="status info">
        <strong>WordPress Info:</strong><br>
        Version: <?php echo get_bloginfo('version'); ?><br>
        URL: <?php echo get_bloginfo('url'); ?><br>
        Admin URL: <?php echo admin_url(); ?>
    </div>
    
    <div class="status info">
        <strong>User Info:</strong><br>
        Current User ID: <?php echo get_current_user_id(); ?><br>
        Current User: <?php echo wp_get_current_user()->display_name; ?><br>
        Can manage options: <?php echo current_user_can('manage_options') ? '✅ JA' : '❌ NEIN'; ?><br>
        Is admin: <?php echo current_user_can('administrator') ? '✅ JA' : '❌ NEIN'; ?>
    </div>
    
    <div class="status info">
        <strong>Plugin Info:</strong><br>
        Plugin Status: <?php echo is_plugin_active('markuslehr_clientgallerie/clientgallerie.php') ? '✅ AKTIV' : '❌ INAKTIV'; ?><br>
        WP_DEBUG: <?php echo WP_DEBUG ? '✅ EIN' : '❌ AUS'; ?><br>
        WP_ADMIN: <?php echo defined('WP_ADMIN') && WP_ADMIN ? '✅ TRUE' : '❌ FALSE'; ?>
    </div>

    <h2>🎯 Hauptmenüs (nach MLCG gefiltert)</h2>
    <?php
    $found_main_menu = false;
    if (!empty($menu)) {
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && strpos($item[2], 'mlcg') !== false) {
                $found_main_menu = true;
                echo '<div class="menu-item">';
                echo '<strong>' . esc_html($item[0]) . '</strong><br>';
                echo 'Slug: ' . esc_html($item[2]) . '<br>';
                echo 'Capability: ' . esc_html($item[1]) . '<br>';
                echo 'Position: ' . $key;
                echo '</div>';
            }
        }
    }
    
    if (!$found_main_menu) {
        echo '<div class="status error">❌ Keine MLCG-Hauptmenüs gefunden!</div>';
    } else {
        echo '<div class="status success">✅ MLCG-Hauptmenü gefunden!</div>';
    }
    ?>

    <h2>📋 Untermenüs (mlcg-galleries)</h2>
    <?php
    if (!empty($submenu['mlcg-galleries'])) {
        echo '<div class="status success">✅ Untermenüs gefunden (' . count($submenu['mlcg-galleries']) . ' Einträge)</div>';
        
        foreach ($submenu['mlcg-galleries'] as $key => $item) {
            echo '<div class="menu-item">';
            echo '<strong>' . esc_html($item[0]) . '</strong><br>';
            echo 'Slug: ' . esc_html($item[2]) . '<br>';
            echo 'Capability: ' . esc_html($item[1]) . '<br>';
            
            if ($item[2] === 'mlcg-logs') {
                echo '<a href="' . admin_url('admin.php?page=mlcg-logs') . '" style="color: #007cba; font-weight: bold;">🔗 Zur Log-Seite</a>';
            }
            echo '</div>';
        }
    } else {
        echo '<div class="status error">❌ Keine Untermenüs für mlcg-galleries gefunden!</div>';
    }
    ?>
    
    <h2>🔧 Plugin Actions Test</h2>
    <div class="status info">
        Teste Plugin-Initialisierung...
        <?php
        try {
            // Manuell Plugin-Hooks auslösen
            do_action('plugins_loaded');
            echo '<br>✅ plugins_loaded ausgeführt';
            
            // AdminController direkt testen
            if (class_exists('\MarkusLehr\ClientGallerie\Application\Controller\AdminController')) {
                echo '<br>✅ AdminController-Klasse existiert';
                
                $controller = new \MarkusLehr\ClientGallerie\Application\Controller\AdminController();
                echo '<br>✅ AdminController instanziiert';
                
                $controller->initialize();
                echo '<br>✅ AdminController initialisiert';
            } else {
                echo '<br>❌ AdminController-Klasse nicht gefunden';
            }
            
        } catch (Exception $e) {
            echo '<br>❌ Fehler: ' . esc_html($e->getMessage());
        }
        ?>
    </div>
    
    <h2>📄 Letzte Log-Einträge</h2>
    <pre><?php
    $log_file = __DIR__ . '/logs/mlcg-current.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $lines = explode("\n", $logs);
        $recent = array_slice($lines, -10);
        echo esc_html(implode("\n", $recent));
    } else {
        echo "Log-Datei nicht gefunden: $log_file";
    }
    ?></pre>
    
    <div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
        <strong>🚀 Nächste Schritte:</strong><br>
        1. WordPress Admin besuchen: <a href="<?php echo admin_url(); ?>" target="_blank"><?php echo admin_url(); ?></a><br>
        2. Nach "ClientGallerie" im Menü suchen<br>
        3. Falls nicht sichtbar: Plugin deaktivieren/aktivieren<br>
        4. Log-Seite testen: <a href="<?php echo admin_url('admin.php?page=mlcg-logs'); ?>" target="_blank">System Logs</a>
    </div>
</body>
</html>
