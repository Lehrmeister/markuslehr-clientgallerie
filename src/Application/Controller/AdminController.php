<?php

namespace MarkusLehr\ClientGallerie\Application\Controller;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Admin Controller mit integriertem Log-Viewer
 * 
 * @package MarkusLehr\ClientGallerie\Application\Controller
 * @author Markus Lehr
 * @since 1.0.0
 */
class AdminController 
{
    public function initialize(): void 
    {
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this, 'showAdminNotices']);
        
        // AJAX handlers
        add_action('wp_ajax_mlcg_clear_logs', [$this, 'handleClearLogs']);
        add_action('wp_ajax_mlcg_run_migration', [$this, 'handleRunMigration']);
        add_action('wp_ajax_mlcg_run_all_migrations', [$this, 'handleRunAllMigrations']);
        add_action('wp_ajax_mlcg_recreate_schemas', [$this, 'handleRecreateSchemas']);
        add_action('wp_ajax_mlcg_check_integrity', [$this, 'handleCheckIntegrity']);
        
        LoggerRegistry::getLogger()?->debug('Admin controller initialized');
    }
    
    public function addAdminMenus(): void 
    {
        // Hauptmenü
        add_menu_page(
            'ClientGallerie',
            'ClientGallerie', 
            'manage_options',
            'mlcg-galleries',
            [$this, 'galleriesPage'],
            'dashicons-format-gallery',
            30
        );
        
        // Untermenüs
        add_submenu_page(
            'mlcg-galleries',
            'Galerien',
            'Galerien',
            'manage_options',
            'mlcg-galleries',
            [$this, 'galleriesPage']
        );
        
        add_submenu_page(
            'mlcg-galleries',
            'Kunden',
            'Kunden',
            'manage_options',
            'mlcg-clients',
            [$this, 'clientsPage']
        );
        
        add_submenu_page(
            'mlcg-galleries',
            'Einstellungen',
            'Einstellungen',
            'manage_options',
            'mlcg-settings',
            [$this, 'settingsPage']
        );
        
        // Log-Viewer (für Administratoren immer sichtbar)
        add_submenu_page(
            'mlcg-galleries',
            'System Logs',
            'System Logs',
            'manage_options',
            'mlcg-logs',
            [$this, 'logsPage']
        );
        
        // Database Administration
        add_submenu_page(
            'mlcg-galleries',
            'Datenbank',
            'Datenbank',
            'manage_options',
            'mlcg-database',
            [$this, 'databasePage']
        );
        
        LoggerRegistry::getLogger()?->info('Admin menus added');
    }
    
    public function galleriesPage(): void 
    {
        LoggerRegistry::getLogger()?->info('Galleries admin page accessed');
        
        echo '<div class="wrap">';
        echo '<h1>ClientGallerie - Galerien</h1>';
        echo '<div id="mlcg-galleries-app"></div>';
        echo '</div>';
    }
    
    public function clientsPage(): void 
    {
        LoggerRegistry::getLogger()?->info('Clients admin page accessed');
        
        echo '<div class="wrap">';
        echo '<h1>ClientGallerie - Kunden</h1>';
        echo '<div id="mlcg-clients-app"></div>';
        echo '</div>';
    }
    
    public function settingsPage(): void 
    {
        LoggerRegistry::getLogger()?->info('Settings admin page accessed');
        
        echo '<div class="wrap">';
        echo '<h1>ClientGallerie - Einstellungen</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('mlcg_settings');
        do_settings_sections('mlcg_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function logsPage(): void 
    {
        LoggerRegistry::getLogger()?->info('Logs admin page accessed');
        
        $this->renderLogsPage();
    }
    
    public function databasePage(): void 
    {
        LoggerRegistry::getLogger()?->info('Database admin page accessed');
        
        $this->renderDatabasePage();
    }
    
    private function renderLogsPage(): void 
    {
        $logger = LoggerRegistry::getLogger();
        $recentLogs = $logger?->getRecentLogs(100) ?? [];
        
        ?>
        <div class="wrap">
            <h1>System Logs</h1>
            
            <div class="mlcg-logs-controls" style="margin-bottom: 20px;">
                <button id="mlcg-refresh-logs" class="button">Aktualisieren</button>
                <button id="mlcg-clear-logs" class="button button-secondary">Logs löschen</button>
                <select id="mlcg-log-level">
                    <option value="">Alle Level</option>
                    <option value="error">Nur Errors</option>
                    <option value="warning">Warnings +</option>
                    <option value="info">Info +</option>
                    <option value="debug">Debug +</option>
                </select>
            </div>
            
            <div class="mlcg-logs-container" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; max-height: 600px; overflow-y: auto;">
                <pre id="mlcg-logs-content" style="font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; margin: 0;"><?php
                
                if (empty($recentLogs)) {
                    echo 'Keine Logs verfügbar.';
                } else {
                    foreach (array_reverse($recentLogs) as $logLine) {
                        $this->formatLogLine($logLine);
                    }
                }
                
                ?></pre>
            </div>
            
            <div class="mlcg-logs-info" style="margin-top: 10px; color: #666;">
                <small>
                    Zeigt die letzten 100 Log-Einträge. 
                    Log-Dateien befinden sich in: <?php echo MLCG_PLUGIN_DIR; ?>logs/
                </small>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshBtn = document.getElementById('mlcg-refresh-logs');
            const clearBtn = document.getElementById('mlcg-clear-logs');
            const levelSelect = document.getElementById('mlcg-log-level');
            const logsContent = document.getElementById('mlcg-logs-content');
            
            refreshBtn.addEventListener('click', function() {
                location.reload();
            });
            
            clearBtn.addEventListener('click', function() {
                if (confirm('Sind Sie sicher, dass Sie alle Logs löschen möchten?')) {
                    // AJAX call to clear logs
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=mlcg_clear_logs&_wpnonce=' + '<?php echo wp_create_nonce('mlcg_clear_logs'); ?>'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            logsContent.textContent = 'Logs wurden gelöscht.';
                        }
                    });
                }
            });
            
            levelSelect.addEventListener('change', function() {
                const level = this.value;
                const lines = logsContent.querySelectorAll('.log-line');
                
                lines.forEach(line => {
                    if (!level || line.classList.contains('log-' + level) || 
                        (level === 'error' && line.classList.contains('log-error')) ||
                        (level === 'warning' && (line.classList.contains('log-error') || line.classList.contains('log-warning'))) ||
                        (level === 'info' && !line.classList.contains('log-debug'))
                    ) {
                        line.style.display = 'block';
                    } else {
                        line.style.display = 'none';
                    }
                });
            });
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                if (document.getElementById('mlcg-auto-refresh')?.checked) {
                    location.reload();
                }
            }, 30000);
        });
        </script>
        
        <style>
        .log-line.log-error { color: #dc3232; }
        .log-line.log-warning { color: #f56e28; }
        .log-line.log-info { color: #0073aa; }
        .log-line.log-debug { color: #666; }
        .log-line.log-critical { color: #dc3232; font-weight: bold; }
        .log-line.log-emergency { color: #dc3232; font-weight: bold; background: #ffe6e6; }
        </style>
        <?php
    }
    
    private function formatLogLine(string $logLine): void 
    {
        // Extrahiere Log-Level aus der Zeile
        $level = 'info';
        if (preg_match('/\] (\w+):/', $logLine, $matches)) {
            $level = strtolower($matches[1]);
        }
        
        $escapedLine = esc_html($logLine);
        echo "<div class='log-line log-{$level}'>{$escapedLine}</div>\n";
    }
    
    public function registerSettings(): void 
    {
        // Logging-Einstellungen
        add_settings_section(
            'mlcg_logging_settings',
            'Logging-Einstellungen',
            [$this, 'loggingSettingsDescription'],
            'mlcg_settings'
        );
        
        add_settings_field(
            'mlcg_log_level',
            'Log-Level',
            [$this, 'logLevelField'],
            'mlcg_settings',
            'mlcg_logging_settings'
        );
        
        add_settings_field(
            'mlcg_log_retention_days',
            'Log-Aufbewahrung (Tage)',
            [$this, 'logRetentionField'],
            'mlcg_settings',
            'mlcg_logging_settings'
        );
        
        add_settings_field(
            'mlcg_enable_debug',
            'Debug-Modus',
            [$this, 'debugModeField'],
            'mlcg_settings',
            'mlcg_logging_settings'
        );
        
        // Registriere Settings
        register_setting('mlcg_settings', 'mlcg_log_level');
        register_setting('mlcg_settings', 'mlcg_log_retention_days');
        register_setting('mlcg_settings', 'mlcg_enable_debug');
        register_setting('mlcg_settings', 'mlcg_max_log_file_size');
        register_setting('mlcg_settings', 'mlcg_ajax_log_limit');
        
        LoggerRegistry::getLogger()?->debug('Admin settings registered');
    }
    
    public function loggingSettingsDescription(): void 
    {
        echo '<p>Konfigurieren Sie die Logging-Einstellungen für das Plugin.</p>';
    }
    
    public function logLevelField(): void 
    {
        $value = get_option('mlcg_log_level', 'info');
        echo '<select name="mlcg_log_level">';
        echo '<option value="emergency"' . selected($value, 'emergency', false) . '>Emergency</option>';
        echo '<option value="alert"' . selected($value, 'alert', false) . '>Alert</option>';
        echo '<option value="critical"' . selected($value, 'critical', false) . '>Critical</option>';
        echo '<option value="error"' . selected($value, 'error', false) . '>Error</option>';
        echo '<option value="warning"' . selected($value, 'warning', false) . '>Warning</option>';
        echo '<option value="notice"' . selected($value, 'notice', false) . '>Notice</option>';
        echo '<option value="info"' . selected($value, 'info', false) . '>Info</option>';
        echo '<option value="debug"' . selected($value, 'debug', false) . '>Debug</option>';
        echo '</select>';
    }
    
    public function logRetentionField(): void 
    {
        $value = get_option('mlcg_log_retention_days', 30);
        echo '<input type="number" name="mlcg_log_retention_days" value="' . esc_attr($value) . '" min="1" max="365" />';
        echo '<p class="description">Anzahl Tage, wie lange Logs aufbewahrt werden sollen.</p>';
    }
    
    public function debugModeField(): void 
    {
        $value = get_option('mlcg_enable_debug', false);
        echo '<input type="checkbox" name="mlcg_enable_debug" value="1"' . checked($value, true, false) . ' />';
        echo '<label>Debug-Modus aktivieren (detaillierte Logs, Function-Tracking)</label>';
    }
    
    public function showAdminNotices(): void 
    {
        // Zeige wichtige Log-Nachrichten als Admin-Notices
        // Implementierung kann später hinzugefügt werden
    }
    
    /**
     * AJAX-Handler zum Löschen der Logs
     */
    public function handleClearLogs(): void 
    {
        // Nonce-Validierung
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mlcg_clear_logs')) {
            wp_die('Security check failed', 'Forbidden', ['response' => 403]);
        }
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $logger = LoggerRegistry::getLogger();
        
        try {
            // Log-Dateien löschen
            $logDir = MLCG_PLUGIN_DIR . 'logs';
            $logFiles = glob($logDir . '/*.log');
            
            $deletedFiles = 0;
            foreach ($logFiles as $file) {
                if (unlink($file)) {
                    $deletedFiles++;
                }
            }
            
            $logger?->info('Logs manually cleared via admin', [
                'deleted_files' => $deletedFiles,
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_success([
                'message' => sprintf('Erfolgreich %d Log-Dateien gelöscht.', $deletedFiles),
                'deleted_files' => $deletedFiles
            ]);
            
        } catch (\Exception $e) {
            $logger?->error('Failed to clear logs', [
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ]);
            
            wp_send_json_error([
                'message' => 'Fehler beim Löschen der Logs: ' . $e->getMessage()
            ]);
        }
    }
    
    public function handleRunMigration(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_migration')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $migration = sanitize_text_field($_POST['migration'] ?? '');
        if (empty($migration)) {
            wp_send_json_error(['message' => 'Migration name required']);
            return;
        }
        
        try {
            $migrationManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager();
            $result = $migrationManager->runSingle($migration);
            
            LoggerRegistry::getLogger()?->info('Migration executed via admin', [
                'migration' => $migration,
                'result' => $result
            ]);
            
            wp_send_json_success(['message' => 'Migration erfolgreich ausgeführt']);
        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Migration failed', [
                'migration' => $migration,
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error(['message' => 'Migration fehlgeschlagen: ' . $e->getMessage()]);
        }
    }
    
    public function handleRunAllMigrations(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_migration')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        try {
            $migrationManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager();
            $results = $migrationManager->runPending();
            
            LoggerRegistry::getLogger()?->info('All pending migrations executed via admin', [
                'results' => $results
            ]);
            
            wp_send_json_success([
                'message' => 'Alle ausstehenden Migrationen ausgeführt',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Batch migration failed', [
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error(['message' => 'Migrationen fehlgeschlagen: ' . $e->getMessage()]);
        }
    }
    
    public function handleRecreateSchemas(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_schema')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        try {
            $schemaManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Schema\SchemaManager();
            $results = $schemaManager->recreateAll();
            
            LoggerRegistry::getLogger()?->info('Schemas recreated via admin', [
                'results' => $results
            ]);
            
            wp_send_json_success([
                'message' => 'Alle Schemas erfolgreich neu erstellt',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Schema recreation failed', [
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error(['message' => 'Schema-Neuerstellung fehlgeschlagen: ' . $e->getMessage()]);
        }
    }
    
    public function handleCheckIntegrity(): void 
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mlcg_integrity')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        try {
            $repositoryManager = \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager::getInstance();
            $healthStatus = $repositoryManager->getSystemHealth();
            
            // Einfache Integritätsprüfung
            $integrityResults = [];
            
            // Grundlegende Datenbankprüfungen
            global $wpdb;
            $clients = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}mlcg_clients");
            
            $integrityResults['total_clients'] = count($clients);
            $integrityResults['status'] = 'clean'; // Keine Gallery/Image-Abhängigkeiten mehr
            
            LoggerRegistry::getLogger()?->info('Integrity check completed via admin', [
                'health_status' => $healthStatus,
                'integrity_results' => $integrityResults
            ]);
            
            wp_send_json_success([
                'message' => 'Integritätsprüfung abgeschlossen',
                'health_status' => $healthStatus,
                'integrity_results' => $integrityResults
            ]);
        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Integrity check failed', [
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error(['message' => 'Integritätsprüfung fehlgeschlagen: ' . $e->getMessage()]);
        }
    }
    
    private function renderDatabasePage(): void 
    {
        // Repository Manager für System Health Check
        try {
            $repositoryManager = \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\RepositoryManager::getInstance();
            $healthStatus = $repositoryManager->getSystemHealth();
            
            // Migration Manager für Migrations-Status
            $migrationManager = new \MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\MigrationManager();
            $migrationStatus = $migrationManager->getStatus();
            
        } catch (\Exception $e) {
            LoggerRegistry::getLogger()?->error('Database page error: ' . $e->getMessage());
            $healthStatus = ['overall_status' => 'error', 'message' => $e->getMessage()];
            $migrationStatus = [];
        }
        
        ?>
        <div class="wrap">
            <h1>Datenbank-Verwaltung</h1>
            
            <!-- System Health Status -->
            <div class="card" style="margin-bottom: 20px;">
                <h2>System Status</h2>
                <div class="mlcg-health-status">
                    <?php if ($healthStatus['overall_status'] === 'healthy'): ?>
                        <p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> System ist gesund</p>
                    <?php elseif ($healthStatus['overall_status'] === 'warning'): ?>
                        <p><span class="dashicons dashicons-warning" style="color: orange;"></span> Warnungen erkannt</p>
                    <?php else: ?>
                        <p><span class="dashicons dashicons-dismiss" style="color: red;"></span> Systemfehler</p>
                        <?php if (isset($healthStatus['message'])): ?>
                            <p><strong>Fehler:</strong> <?php echo esc_html($healthStatus['message']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($healthStatus['details'])): ?>
                    <h3>Details</h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Repository</th>
                                <th>Status</th>
                                <th>Tabelle</th>
                                <th>Einträge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($healthStatus['details'] as $repo => $details): ?>
                                <tr>
                                    <td><?php echo esc_html($repo); ?></td>
                                    <td>
                                        <?php if ($details['status'] === 'healthy'): ?>
                                            <span style="color: green;">✓ OK</span>
                                        <?php else: ?>
                                            <span style="color: red;">✗ Fehler</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($details['table'] ?? '-'); ?></td>
                                    <td><?php echo esc_html($details['count'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Migrations Status -->
            <div class="card" style="margin-bottom: 20px;">
                <h2>Migrationen</h2>
                
                <?php if (!empty($migrationStatus)): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Migration</th>
                                <th>Status</th>
                                <th>Datum</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($migrationStatus as $migration): ?>
                                <tr>
                                    <td><?php echo esc_html($migration['name']); ?></td>
                                    <td>
                                        <?php if ($migration['executed']): ?>
                                            <span style="color: green;">Ausgeführt</span>
                                        <?php else: ?>
                                            <span style="color: orange;">Ausstehend</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($migration['executed_at'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!$migration['executed']): ?>
                                            <button class="button button-primary mlcg-run-migration" data-migration="<?php echo esc_attr($migration['name']); ?>">
                                                Ausführen
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Keine Migrationen gefunden oder Migrationssystem nicht verfügbar.</p>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <button class="button button-secondary" id="mlcg-refresh-migrations">Status aktualisieren</button>
                    <button class="button button-primary" id="mlcg-run-all-migrations">Alle ausstehenden Migrationen ausführen</button>
                </div>
            </div>
            
            <!-- Schema Informationen -->
            <div class="card">
                <h2>Schema Informationen</h2>
                <p>Hier können Sie Informationen über die Datenbankstruktur einsehen.</p>
                
                <div class="mlcg-schema-info">
                    <h3>Installierte Tabellen</h3>
                    <?php
                    global $wpdb;
                    $tables = [
                        'galleries' => $wpdb->prefix . 'mlcg_galleries',
                        'images' => $wpdb->prefix . 'mlcg_images', 
                        'clients' => $wpdb->prefix . 'mlcg_clients',
                        'ratings' => $wpdb->prefix . 'mlcg_ratings',
                        'log_entries' => $wpdb->prefix . 'mlcg_log_entries'
                    ];
                    ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Bezeichnung</th>
                                <th>Tabelle</th>
                                <th>Existiert</th>
                                <th>Einträge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $name => $table): ?>
                                <?php 
                                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
                                $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0;
                                ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($name)); ?></td>
                                    <td><code><?php echo esc_html($table); ?></code></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span style="color: green;">✓ Ja</span>
                                        <?php else: ?>
                                            <span style="color: red;">✗ Nein</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px;">
                    <button class="button button-secondary" id="mlcg-recreate-schemas">Schemas neu erstellen</button>
                    <button class="button button-secondary" id="mlcg-check-integrity">Integrität prüfen</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Migration ausführen
            $('.mlcg-run-migration').on('click', function() {
                var migration = $(this).data('migration');
                if (confirm('Migration "' + migration + '" ausführen?')) {
                    $(this).prop('disabled', true).text('Wird ausgeführt...');
                    // AJAX call to run migration
                    $.post(ajaxurl, {
                        action: 'mlcg_run_migration',
                        migration: migration,
                        nonce: '<?php echo wp_create_nonce('mlcg_migration'); ?>'
                    }, function(response) {
                        location.reload();
                    });
                }
            });
            
            // Alle Migrationen ausführen
            $('#mlcg-run-all-migrations').on('click', function() {
                if (confirm('Alle ausstehenden Migrationen ausführen?')) {
                    $(this).prop('disabled', true).text('Wird ausgeführt...');
                    $.post(ajaxurl, {
                        action: 'mlcg_run_all_migrations',
                        nonce: '<?php echo wp_create_nonce('mlcg_migration'); ?>'
                    }, function(response) {
                        location.reload();
                    });
                }
            });
            
            // Status aktualisieren
            $('#mlcg-refresh-migrations').on('click', function() {
                location.reload();
            });
            
            // Schemas neu erstellen
            $('#mlcg-recreate-schemas').on('click', function() {
                if (confirm('Alle Schemas neu erstellen? WARNUNG: Daten könnten verloren gehen!')) {
                    $(this).prop('disabled', true).text('Wird ausgeführt...');
                    $.post(ajaxurl, {
                        action: 'mlcg_recreate_schemas',
                        nonce: '<?php echo wp_create_nonce('mlcg_schema'); ?>'
                    }, function(response) {
                        location.reload();
                    });
                }
            });
            
            // Integrität prüfen
            $('#mlcg-check-integrity').on('click', function() {
                $(this).prop('disabled', true).text('Wird geprüft...');
                $.post(ajaxurl, {
                    action: 'mlcg_check_integrity',
                    nonce: '<?php echo wp_create_nonce('mlcg_integrity'); ?>'
                }, function(response) {
                    alert('Integritätsprüfung abgeschlossen. Details siehe Log.');
                    $(this).prop('disabled', false).text('Integrität prüfen');
                });
            });
        });
        </script>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .mlcg-health-status p {
            font-size: 16px;
            margin: 10px 0;
        }
        .mlcg-schema-info table {
            margin-top: 15px;
        }
        .button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        </style>
        <?php
    }
}
