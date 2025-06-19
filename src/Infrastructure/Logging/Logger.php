<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Logging;

use DateTime;
use Exception;

/**
 * Intelligentes Logging-System für MarkusLehr ClientGallerie
 * 
 * Features:
 * - Automatische Log-Rotation für AJAX-Performance
 * - VS Code Integration mit .log Dateien
 * - WordPress Backend Integration
 * - Debug-Modi für Entwicklung
 * - Performance-optimierte Log-Spooling
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Logging
 * @author Markus Lehr
 * @since 1.0.0
 */
class Logger 
{
    private const LOG_LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7
    ];
    
    private string $logDirectory;
    private string $currentLogFile;
    private array $logBuffer = [];
    private int $bufferSize = 0;
    private int $maxBufferSize = 100; // Max entries before flush
    private int $maxLogFileSize; // in bytes
    private int $logRetentionDays;
    private string $logLevel;
    private bool $debugMode;
    private bool $ajaxOptimization = true;
    
    public function __construct() 
    {
        $this->logDirectory = MLCG_PLUGIN_DIR . 'logs';
        $this->maxLogFileSize = (int) get_option('mlcg_max_log_file_size', 10) * 1024 * 1024; // MB to bytes
        $this->logRetentionDays = (int) get_option('mlcg_log_retention_days', 30);
        $this->logLevel = get_option('mlcg_log_level', 'info');
        $this->debugMode = (bool) get_option('mlcg_enable_debug', false);
        $this->maxBufferSize = (int) get_option('mlcg_ajax_log_limit', 100);
        
        $this->ensureLogDirectory();
        $this->initializeLogFile();
        $this->registerShutdownHandler();
        $this->scheduleCleanup();
    }
    
    /**
     * Emergency: System ist unbrauchbar
     */
    public function emergency(string $message, array $context = []): void 
    {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Alert: Sofortige Handlung erforderlich
     */
    public function alert(string $message, array $context = []): void 
    {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Critical: Kritische Bedingungen
     */
    public function critical(string $message, array $context = []): void 
    {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Error: Laufzeitfehler, die keine sofortige Handlung erfordern
     */
    public function error(string $message, array $context = []): void 
    {
        $this->log('error', $message, $context);
    }
    
    /**
     * Warning: Außergewöhnliche Ereignisse, aber kein Fehler
     */
    public function warning(string $message, array $context = []): void 
    {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Notice: Normale aber bedeutsame Ereignisse
     */
    public function notice(string $message, array $context = []): void 
    {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Info: Interessante Ereignisse
     */
    public function info(string $message, array $context = []): void 
    {
        $this->log('info', $message, $context);
    }
    
    /**
     * Debug: Detaillierte Debug-Informationen
     */
    public function debug(string $message, array $context = []): void 
    {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Automatisches Function-Logging für neue Funktionen
     */
    public function logFunction(string $functionName, array $args = [], ?string $result = null): void 
    {
        if (!$this->debugMode) {
            return;
        }
        
        $context = [
            'function' => $functionName,
            'args' => $this->sanitizeArgs($args),
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];
        
        if ($result !== null) {
            $context['result'] = substr($result, 0, 500); // Limit result size
        }
        
        $this->debug("Function executed: $functionName", $context);
        
        // Auto-cleanup: Remove old function logs
        $this->cleanupFunctionLogs($functionName);
    }
    
    /**
     * Performance-optimiertes AJAX-Logging
     */
    public function logAjax(string $action, array $data = [], ?string $response = null): void 
    {
        $context = [
            'ajax_action' => $action,
            'request_data' => $this->sanitizeForAjax($data),
            'response_size' => $response ? strlen($response) : 0,
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];
        
        if ($response && strlen($response) < 1000) {
            $context['response'] = $response;
        }
        
        $this->info("AJAX Request: $action", $context);
    }
    
    /**
     * Haupt-Logging-Methode
     */
    private function log(string $level, string $message, array $context = []): void 
    {
        // Prüfe Log-Level
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logEntry = $this->createLogEntry($level, $message, $context);
        
        // Buffer für Performance (besonders bei AJAX)
        if ($this->ajaxOptimization && $this->isAjaxRequest()) {
            $this->addToBuffer($logEntry);
        } else {
            $this->writeToFile($logEntry);
        }
        
        // Kritische Logs sofort an WordPress weiterleiten
        if (in_array($level, ['emergency', 'alert', 'critical', 'error'])) {
            $this->logToWordPress($level, $message, $context);
        }
    }
    
    private function shouldLog(string $level): bool 
    {
        $currentLevelValue = self::LOG_LEVELS[$this->logLevel] ?? 6;
        $messageLevelValue = self::LOG_LEVELS[$level] ?? 7;
        
        return $messageLevelValue <= $currentLevelValue;
    }
    
    private function createLogEntry(string $level, string $message, array $context): array 
    {
        return [
            'timestamp' => (new DateTime())->format('Y-m-d H:i:s.u'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'request_id' => $this->getRequestId(),
            'user_id' => get_current_user_id(),
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'memory' => memory_get_usage(true),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI'
        ];
    }
    
    private function addToBuffer(array $logEntry): void 
    {
        $this->logBuffer[] = $logEntry;
        $this->bufferSize++;
        
        // Buffer flush bei Größe oder kritischen Logs
        if ($this->bufferSize >= $this->maxBufferSize || 
            in_array($logEntry['level'], ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'])) {
            $this->flushBuffer();
        }
    }
    
    private function flushBuffer(): void 
    {
        if (empty($this->logBuffer)) {
            return;
        }
        
        foreach ($this->logBuffer as $entry) {
            $this->writeToFile($entry);
        }
        
        $this->logBuffer = [];
        $this->bufferSize = 0;
    }
    
    private function writeToFile(array $logEntry): void 
    {
        try {
            $formattedEntry = $this->formatLogEntry($logEntry);
            
            // Prüfe Dateigröße vor dem Schreiben
            if (file_exists($this->currentLogFile) && 
                filesize($this->currentLogFile) > $this->maxLogFileSize) {
                $this->rotateLogFile();
            }
            
            @file_put_contents(
                $this->currentLogFile, 
                $formattedEntry . PHP_EOL, 
                FILE_APPEND | LOCK_EX
            );
            
        } catch (Exception $e) {
            // Fallback: WordPress error log
            @error_log("MLCG Logger Error: " . $e->getMessage());
        }
    }
    
    private function formatLogEntry(array $entry): string 
    {
        $contextStr = !empty($entry['context']) ? 
            json_encode($entry['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}';
        
        return sprintf(
            '[%s] %s: %s | User: %d | IP: %s | Memory: %s | Context: %s',
            $entry['timestamp'],
            $entry['level'],
            $entry['message'],
            $entry['user_id'],
            $entry['ip'],
            $this->formatBytes($entry['memory']),
            $contextStr
        );
    }
    
    private function rotateLogFile(): void 
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $rotatedFile = $this->logDirectory . "/mlcg-{$timestamp}.log";
            
            // Prüfe ob aktuelle Log-Datei existiert und beschreibbar ist
            if (!file_exists($this->currentLogFile)) {
                $this->initializeLogFile();
                return;
            }
            
            // Versuche Dateiberechtigungen zu setzen
            @chmod($this->currentLogFile, 0664);
            @chmod($this->logDirectory, 0775);
            
            // Kopiere statt rename (sicherer bei Berechtigung-Problemen)
            if (@copy($this->currentLogFile, $rotatedFile)) {
                // Leere die aktuelle Datei anstatt sie zu löschen
                @file_put_contents($this->currentLogFile, '');
                
                $this->info('Log file rotated', [
                    'old_file' => basename($rotatedFile),
                    'new_file' => basename($this->currentLogFile),
                    'method' => 'copy'
                ]);
            } else {
                // Fallback: Einfach die aktuelle Datei leeren
                @file_put_contents($this->currentLogFile, '');
                
                // Stille Warnung - keine Exception werfen
                error_log('MLCG: Log rotation failed, current log cleared instead');
            }
            
        } catch (\Exception $e) {
            // Fehler abfangen ohne die Seite zu unterbrechen
            error_log('MLCG Logger rotation error: ' . $e->getMessage());
            
            // Sicherstellen, dass Log-Datei verfügbar ist
            $this->initializeLogFile();
        }
    }
    
    private function ensureLogDirectory(): void 
    {
        if (!is_dir($this->logDirectory)) {
            if (!wp_mkdir_p($this->logDirectory)) {
                // Fallback: WordPress uploads directory
                $uploadsDir = wp_upload_dir();
                $this->logDirectory = $uploadsDir['basedir'] . '/mlcg-logs';
                wp_mkdir_p($this->logDirectory);
            }
        }
        
        // .htaccess für Sicherheit
        $htaccessFile = $this->logDirectory . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "Deny from all\n";
            @file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    private function initializeLogFile(): void 
    {
        $this->currentLogFile = $this->logDirectory . '/mlcg-current.log';
        
        if (!file_exists($this->currentLogFile)) {
            // Versuche Log-Datei zu erstellen
            if (@touch($this->currentLogFile)) {
                @chmod($this->currentLogFile, 0664);
            } else {
                // Fallback: Verwende WordPress uploads-Verzeichnis
                $uploadsDir = wp_upload_dir();
                $fallbackDir = $uploadsDir['basedir'] . '/mlcg-logs';
                
                if (@wp_mkdir_p($fallbackDir)) {
                    $this->logDirectory = $fallbackDir;
                    $this->currentLogFile = $fallbackDir . '/mlcg-current.log';
                    @touch($this->currentLogFile);
                    @chmod($this->currentLogFile, 0664);
                }
            }
        } else {
            // Stelle sicher, dass die Datei beschreibbar ist
            @chmod($this->currentLogFile, 0664);
        }
    }
    
    private function registerShutdownHandler(): void 
    {
        register_shutdown_function(function() {
            $this->flushBuffer();
        });
    }
    
    private function scheduleCleanup(): void 
    {
        if (!wp_next_scheduled('mlcg_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'mlcg_cleanup_logs');
        }
        
        add_action('mlcg_cleanup_logs', [$this, 'cleanupOldLogs']);
    }
    
    public function cleanupOldLogs(): void 
    {
        $files = glob($this->logDirectory . '/mlcg-*.log');
        $cutoffTime = time() - ($this->logRetentionDays * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info('Old log file deleted', ['file' => basename($file)]);
            }
        }
    }
    
    private function cleanupFunctionLogs(string $functionName): void 
    {
        // Implementierung für das Entfernen alter Function-Logs
        // Wird über separate Cleanup-Routine abgewickelt
    }
    
    private function logToWordPress(string $level, string $message, array $context): void 
    {
        $wpMessage = sprintf(
            '[MLCG %s] %s | Context: %s',
            strtoupper($level),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );
        
        error_log($wpMessage);
    }
    
    private function sanitizeArgs(array $args): array 
    {
        $sanitized = [];
        foreach ($args as $key => $value) {
            if (is_string($value) && strlen($value) > 200) {
                $sanitized[$key] = substr($value, 0, 200) . '...';
            } elseif (is_array($value)) {
                $sanitized[$key] = '[Array with ' . count($value) . ' items]';
            } elseif (is_object($value)) {
                $sanitized[$key] = '[Object: ' . get_class($value) . ']';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    private function sanitizeForAjax(array $data): array 
    {
        // Entferne sensible Daten für AJAX-Logs
        $sensitiveKeys = ['password', 'token', 'key', 'secret', 'auth'];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (isset($data[$sensitiveKey])) {
                $data[$sensitiveKey] = '[HIDDEN]';
            }
        }
        
        return $data;
    }
    
    private function getRequestId(): string 
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = substr(md5(uniqid('', true)), 0, 8);
        }
        
        return $requestId;
    }
    
    private function getClientIp(): string 
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        
        return 'unknown';
    }
    
    private function isAjaxRequest(): bool 
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
    
    private function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
    
    /**
     * Für VS Code Integration: Hole neueste Logs
     */
    public function getRecentLogs(int $limit = 100): array 
    {
        if (!file_exists($this->currentLogFile)) {
            return [];
        }
        
        $lines = file($this->currentLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($lines, -$limit);
    }
    
    /**
     * Für WordPress Backend: Hole Logs als strukturierte Daten
     */
    public function getLogsForAdmin(string $level = '', int $limit = 50): array 
    {
        // Implementierung für Admin-Interface
        return [];
    }
}
