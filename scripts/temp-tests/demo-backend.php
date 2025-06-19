<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkusLehr ClientGallerie - System Logs</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; }
        .wrap { max-width: 1200px; }
        .mlcg-logs-controls { margin-bottom: 20px; }
        .button { padding: 8px 16px; margin-right: 10px; border: 1px solid #ccc; background: #f7f7f7; cursor: pointer; }
        .button:hover { background: #e0e0e0; }
        .mlcg-logs-container { background: #fff; border: 1px solid #ccd0d4; padding: 20px; max-height: 600px; overflow-y: auto; }
        pre { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; margin: 0; white-space: pre-wrap; }
        .log-line.log-error { color: #dc3232; }
        .log-line.log-warning { color: #f56e28; }
        .log-line.log-info { color: #0073aa; }
        .log-line.log-debug { color: #666; }
        .log-line.log-critical { color: #dc3232; font-weight: bold; }
        .nav-tab-wrapper { border-bottom: 1px solid #ccc; margin-bottom: 20px; }
        .nav-tab { padding: 10px 15px; margin-right: 5px; background: #f1f1f1; border: 1px solid #ccc; border-bottom: none; text-decoration: none; color: #333; }
        .nav-tab.nav-tab-active { background: #fff; color: #000; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>ClientGallerie</h1>
        
        <div class="nav-tab-wrapper">
            <a href="#" class="nav-tab">Galerien</a>
            <a href="#" class="nav-tab">Kunden</a>
            <a href="#" class="nav-tab">Einstellungen</a>
            <a href="#" class="nav-tab nav-tab-active">System Logs</a>
        </div>
        
        <h2>System Logs</h2>
        
        <div class="mlcg-logs-controls">
            <button class="button" onclick="refreshLogs()">Aktualisieren</button>
            <button class="button" onclick="clearLogs()">Logs löschen</button>
            <select id="mlcg-log-level" onchange="filterLogs()">
                <option value="">Alle Level</option>
                <option value="error">Nur Errors</option>
                <option value="warning">Warnings +</option>
                <option value="info">Info +</option>
                <option value="debug">Debug +</option>
            </select>
        </div>
        
        <div class="mlcg-logs-container">
            <pre id="mlcg-logs-content"><?php
            
// Live-Logs aus der aktuellen Log-Datei laden
$logFile = '/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/logs/mlcg-current.log';

if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -50); // Letzte 50 Zeilen
    
    foreach ($recentLines as $line) {
        if (empty(trim($line))) continue;
        
        // Log-Level extrahieren
        $level = 'info';
        if (preg_match('/\] (\w+):/', $line, $matches)) {
            $level = strtolower($matches[1]);
        }
        
        echo '<div class="log-line log-' . $level . '">' . htmlspecialchars($line) . '</div>' . "\n";
    }
} else {
    echo "Log-Datei nicht gefunden: $logFile";
}

?></pre>
        </div>
        
        <div class="mlcg-logs-info" style="margin-top: 10px; color: #666;">
            <small>
                Zeigt die letzten 50 Log-Einträge. 
                Log-Dateien befinden sich in: /Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie/logs/
            </small>
        </div>
    </div>
    
    <script>
        function refreshLogs() {
            location.reload();
        }
        
        function clearLogs() {
            if (confirm('Sind Sie sicher, dass Sie alle Logs löschen möchten?')) {
                alert('Log-Bereinigung würde über AJAX erfolgen (nicht in dieser Demo implementiert)');
            }
        }
        
        function filterLogs() {
            const level = document.getElementById('mlcg-log-level').value;
            const lines = document.querySelectorAll('.log-line');
            
            lines.forEach(line => {
                if (!level || 
                    line.classList.contains('log-' + level) ||
                    (level === 'error' && line.classList.contains('log-error')) ||
                    (level === 'warning' && (line.classList.contains('log-error') || line.classList.contains('log-warning'))) ||
                    (level === 'info' && !line.classList.contains('log-debug'))
                ) {
                    line.style.display = 'block';
                } else {
                    line.style.display = 'none';
                }
            });
        }
        
        // Auto-refresh alle 10 Sekunden
        setInterval(refreshLogs, 10000);
    </script>
</body>
</html>
