<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Schema für erweiterte Log-Einträge mit strukturierten Daten
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class LogEntrySchema extends BaseSchema 
{
    protected function getTableSuffix(): string 
    {
        return 'mlcg_log_entries';
    }
    
    protected function getCreateTableSQL(): string 
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level enum('emergency','alert','critical','error','warning','notice','info','debug') NOT NULL,
            channel varchar(100) DEFAULT 'application' COMMENT 'Log channel/category',
            message text NOT NULL,
            context longtext DEFAULT NULL COMMENT 'JSON context data',
            extra longtext DEFAULT NULL COMMENT 'JSON extra metadata',
            request_id varchar(32) DEFAULT NULL,
            session_id varchar(64) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            client_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            referer varchar(500) DEFAULT NULL,
            url varchar(500) DEFAULT NULL,
            method varchar(10) DEFAULT NULL,
            response_code int(4) DEFAULT NULL,
            response_time_ms int(11) DEFAULT NULL,
            memory_usage bigint(20) unsigned DEFAULT NULL,
            cpu_usage decimal(5,2) DEFAULT NULL,
            trace longtext DEFAULT NULL COMMENT 'Stack trace for errors',
            tags varchar(500) DEFAULT NULL COMMENT 'Comma-separated tags',
            environment varchar(50) DEFAULT 'production',
            application_version varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL COMMENT 'Auto-cleanup date',
            INDEX level (level),
            INDEX channel (channel),
            INDEX request_id (request_id),
            INDEX session_id (session_id),
            INDEX user_id (user_id),
            INDEX client_id (client_id),
            INDEX created_at (created_at),
            INDEX expires_at (expires_at),
            INDEX environment (environment),
            INDEX response_code (response_code),
            FULLTEXT KEY search_content (message, tags),
            PRIMARY KEY (id)
        ) {$this->charset};";
    }
    
    protected function afterCreate(): void 
    {
        // Views für verschiedene Log-Level
        $this->createErrorLogsView();
        $this->createPerformanceLogsView();
        $this->createSecurityLogsView();
        $this->createUserActivityView();
        
        // Auto-Cleanup Event
        $this->createCleanupEvent();
    }
    
    private function createErrorLogsView(): void 
    {
        $viewName = $this->tableName . '_errors';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, level, channel, message, context, request_id,
            user_id, client_id, ip_address, url, method,
            response_code, trace, created_at
        FROM {$this->tableName}
        WHERE level IN ('emergency', 'alert', 'critical', 'error')
        ORDER BY created_at DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createPerformanceLogsView(): void 
    {
        $viewName = $this->tableName . '_performance';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, message, url, method, response_code,
            response_time_ms, memory_usage, cpu_usage,
            created_at
        FROM {$this->tableName}
        WHERE channel = 'performance' 
        OR response_time_ms > 1000
        OR memory_usage > 50000000
        ORDER BY 
            CASE 
                WHEN response_time_ms > 5000 THEN 1
                WHEN memory_usage > 100000000 THEN 2
                ELSE 3
            END,
            created_at DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createSecurityLogsView(): void 
    {
        $viewName = $this->tableName . '_security';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, level, message, user_id, client_id, ip_address,
            user_agent, url, method, response_code, created_at
        FROM {$this->tableName}
        WHERE channel = 'security'
        OR level IN ('alert', 'critical')
        OR response_code IN (401, 403, 429)
        OR message LIKE '%login%'
        OR message LIKE '%access%'
        OR message LIKE '%security%'
        ORDER BY 
            CASE level
                WHEN 'critical' THEN 1
                WHEN 'alert' THEN 2
                WHEN 'error' THEN 3
                ELSE 4
            END,
            created_at DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createUserActivityView(): void 
    {
        $viewName = $this->tableName . '_user_activity';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            DATE(created_at) as activity_date,
            HOUR(created_at) as activity_hour,
            COUNT(*) as total_requests,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT client_id) as unique_clients,
            COUNT(DISTINCT ip_address) as unique_ips,
            AVG(response_time_ms) as avg_response_time,
            COUNT(CASE WHEN level = 'error' THEN 1 END) as error_count
        FROM {$this->tableName}
        WHERE channel = 'application'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at), HOUR(created_at)
        ORDER BY activity_date DESC, activity_hour DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createCleanupEvent(): void 
    {
        // MySQL Event für automatische Log-Bereinigung
        $sql = "
        CREATE EVENT IF NOT EXISTS {$this->tableName}_cleanup
        ON SCHEDULE EVERY 1 DAY
        STARTS TIMESTAMP(CURDATE(), '02:00:00')
        DO
        BEGIN
            -- Lösche abgelaufene Logs
            DELETE FROM {$this->tableName}
            WHERE expires_at IS NOT NULL AND expires_at < NOW();
            
            -- Lösche alte Debug-Logs (7 Tage)
            DELETE FROM {$this->tableName}
            WHERE level = 'debug' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
            
            -- Lösche alte Info-Logs (30 Tage)
            DELETE FROM {$this->tableName}
            WHERE level = 'info' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
            
            -- Lösche alte Warning-Logs (90 Tage)
            DELETE FROM {$this->tableName}
            WHERE level = 'warning' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
            
            -- Behalte Error-Logs für 1 Jahr
            DELETE FROM {$this->tableName}
            WHERE level IN ('error', 'critical', 'alert', 'emergency') 
            AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
        END;";
        
        $this->wpdb->query($sql);
    }
    
    public function validate(): array 
    {
        $issues = parent::validate();
        
        if ($this->exists()) {
            // Prüfe Log-Größe
            $totalLogs = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tableName}");
            if ($totalLogs > 1000000) {
                $issues[] = "Large number of log entries ($totalLogs) - consider cleanup";
            }
            
            // Prüfe alte Logs ohne Cleanup-Datum
            $oldLogs = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                AND level IN ('debug', 'info')
            ");
            
            if ($oldLogs > 0) {
                $issues[] = "$oldLogs old debug/info logs found without cleanup date";
            }
            
            // Prüfe fehlerhafte JSON-Daten
            $invalidJson = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE (context IS NOT NULL AND context != '' AND JSON_VALID(context) = 0)
                OR (extra IS NOT NULL AND extra != '' AND JSON_VALID(extra) = 0)
            ");
            
            if ($invalidJson > 0) {
                $issues[] = "$invalidJson log entries with invalid JSON data found";
            }
            
            // Prüfe hohe Error-Rate
            $recentErrors = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE level IN ('error', 'critical', 'alert', 'emergency')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            
            if ($recentErrors > 100) {
                $issues[] = "High error rate detected: $recentErrors errors in the last hour";
            }
        }
        
        return $issues;
    }
}
