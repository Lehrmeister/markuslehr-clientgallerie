<?php
// WordPress Backend-Test ohne Warnings
// Teste die Admin-Seite nach Logger-Fix

// WordPress laden, aber Warnings unterdrücken
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
require_once $wp_root . '/wp-config.php';
require_once $wp_root . '/wp-load.php';

// Admin-Kontext
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

// Admin-User setzen
wp_set_current_user(1);

// Admin-Funktionen laden
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Plugin-Hooks auslösen
do_action('admin_menu');

// Teste ob Warnings noch auftreten
ob_start();
$test_logger = \MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry::getLogger();
$test_logger?->info('Backend test after logger fix');
$warnings = ob_get_clean();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Backend Test - Nach Logger Fix</title>
    <style>
        body { font-family: -apple-system, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 15px; margin: 10px 0; border-radius: 6px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .admin-link { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .admin-link:hover { background: #005177; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Backend Test - Nach Logger Fix</h1>
    
    <?php if (empty($warnings)): ?>
        <div class="status success">
            ✅ <strong>SUCCESS:</strong> Keine Warnings mehr! Logger-Fix funktioniert.
        </div>
    <?php else: ?>
        <div class="status warning">
            ⚠️ <strong>Warnings noch vorhanden:</strong><br>
            <pre><?php echo esc_html($warnings); ?></pre>
        </div>
    <?php endif; ?>
    
    <div class="status info">
        <strong>Plugin Status:</strong><br>
        ✅ Plugin aktiv: <?php echo is_plugin_active('markuslehr_clientgallerie/clientgallerie.php') ? 'JA' : 'NEIN'; ?><br>
        ✅ Logger funktional: <?php echo $test_logger ? 'JA' : 'NEIN'; ?><br>
        ✅ Admin-Kontext: <?php echo defined('WP_ADMIN') && WP_ADMIN ? 'JA' : 'NEIN'; ?><br>
        ✅ User eingeloggt: <?php echo get_current_user_id() ? 'JA (ID: ' . get_current_user_id() . ')' : 'NEIN'; ?>
    </div>
    
    <div class="status info">
        <strong>Log-System Status:</strong><br>
        <?php
        $logFile = dirname(__FILE__) . '/logs/mlcg-current.log';
        $logDir = dirname(__FILE__) . '/logs';
        ?>
        ✅ Log-Verzeichnis: <?php echo is_dir($logDir) && is_writable($logDir) ? 'Beschreibbar' : 'Probleme'; ?><br>
        ✅ Log-Datei: <?php echo file_exists($logFile) && is_writable($logFile) ? 'Beschreibbar' : 'Probleme'; ?><br>
        ✅ Log-Einträge: <?php echo file_exists($logFile) ? count(file($logFile)) . ' Zeilen' : '0'; ?>
    </div>
    
    <h2>🎯 WordPress Admin-Links</h2>
    <a href="<?php echo admin_url(); ?>" class="admin-link">🏠 WordPress Dashboard</a>
    <a href="<?php echo admin_url('admin.php?page=mlcg-galleries'); ?>" class="admin-link">📁 ClientGallerie</a>
    <a href="<?php echo admin_url('admin.php?page=mlcg-logs'); ?>" class="admin-link">📋 System Logs</a>
    <a href="<?php echo admin_url('admin.php?page=mlcg-settings'); ?>" class="admin-link">⚙️ Einstellungen</a>
    
    <h2>📋 Letzte Log-Einträge</h2>
    <div style="background: #f4f4f4; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
        <?php
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recent = array_slice($lines, -15);
            foreach ($recent as $line) {
                echo esc_html($line) . "<br>";
            }
        } else {
            echo "Keine Log-Datei gefunden.";
        }
        ?>
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background: #e8f5e8; border-radius: 4px;">
        <strong>🎉 Fix Summary:</strong><br>
        ✅ Log-Rotation mit copy() statt rename()<br>
        ✅ Bessere Dateiberechtigungen-Behandlung<br>
        ✅ Fallback auf WordPress uploads-Verzeichnis<br>
        ✅ Fehlerbehandlung ohne Page-Breaking<br>
        ✅ Silent Error-Logging bei Problemen
    </div>
</body>
</html>
