<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\BaseRepository;

/**
 * Repository for log entry entities
 * 
 * Handles CRUD operations and specific queries for log entries
 */
class LogEntryRepository extends BaseRepository
{
    /**
     * Get the table name
     */
    protected function getTableName(): string
    {
        return $this->wpdb->prefix . 'ml_clientgallerie_log_entries';
    }

    /**
     * Get validation rules for log entry data
     */
    protected function getValidationRules(): array
    {
        return [
            'level' => 'required|string|max:20',
            'message' => 'required|string',
            'context' => 'string',
            'channel' => 'string|max:100',
            'user_id' => 'numeric',
            'client_id' => 'numeric',
            'ip_address' => 'string|max:45',
            'user_agent' => 'string|max:500',
            'request_uri' => 'string|max:500',
            'session_id' => 'string|max:100',
            'correlation_id' => 'string|max:100',
            'stack_trace' => 'string',
            'extra_data' => 'string'
        ];
    }

    /**
     * Find log entries by level
     */
    public function findByLevel(string $level, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE level = %s";
        $params = [$level];

        if ($startDate) {
            $sql .= " AND created_at >= %s";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND created_at <= %s";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find log entries by channel
     */
    public function findByChannel(string $channel, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $level = $options['level'] ?? null;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE channel = %s";
        $params = [$channel];

        if ($level) {
            $sql .= " AND level = %s";
            $params[] = $level;
        }

        if ($startDate) {
            $sql .= " AND created_at >= %s";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND created_at <= %s";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find log entries by correlation ID
     */
    public function findByCorrelationId(string $correlationId): array
    {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE correlation_id = %s 
                ORDER BY created_at ASC";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $correlationId),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find log entries by user ID
     */
    public function findByUserId(int $userId, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $level = $options['level'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE user_id = %d";
        $params = [$userId];

        if ($level) {
            $sql .= " AND level = %s";
            $params[] = $level;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find log entries by client ID
     */
    public function findByClientId(int $clientId, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $level = $options['level'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} WHERE client_id = %d";
        $params = [$clientId];

        if ($level) {
            $sql .= " AND level = %s";
            $params[] = $level;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Find error and critical log entries
     */
    public function findErrors(array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE level IN ('error', 'critical', 'alert', 'emergency')";
        $params = [];

        if ($startDate) {
            $sql .= " AND created_at >= %s";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND created_at <= %s";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Search log entries by message content
     */
    public function search(string $term, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $level = $options['level'] ?? null;
        $channel = $options['channel'] ?? null;

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE (message LIKE %s OR context LIKE %s OR stack_trace LIKE %s)";

        $searchTerm = '%' . $this->wpdb->esc_like($term) . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm];

        if ($level) {
            $sql .= " AND level = %s";
            $params[] = $level;
        }

        if ($channel) {
            $sql .= " AND channel = %s";
            $params[] = $channel;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get log statistics
     */
    public function getStatistics(array $options = []): array
    {
        $startDate = $options['start_date'] ?? date('Y-m-d H:i:s', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? current_time('mysql');

        $sql = "SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN level = 'debug' THEN 1 END) as debug_count,
                    COUNT(CASE WHEN level = 'info' THEN 1 END) as info_count,
                    COUNT(CASE WHEN level = 'notice' THEN 1 END) as notice_count,
                    COUNT(CASE WHEN level = 'warning' THEN 1 END) as warning_count,
                    COUNT(CASE WHEN level = 'error' THEN 1 END) as error_count,
                    COUNT(CASE WHEN level = 'critical' THEN 1 END) as critical_count,
                    COUNT(CASE WHEN level = 'alert' THEN 1 END) as alert_count,
                    COUNT(CASE WHEN level = 'emergency' THEN 1 END) as emergency_count,
                    COUNT(DISTINCT channel) as unique_channels,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT client_id) as unique_clients,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM {$this->getTableName()}
                WHERE created_at >= %s AND created_at <= %s";

        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $startDate, $endDate),
            ARRAY_A
        ) ?: [];

        // Get channel distribution
        $channelSql = "SELECT channel, COUNT(*) as count 
                      FROM {$this->getTableName()} 
                      WHERE created_at >= %s AND created_at <= %s
                        AND channel IS NOT NULL 
                      GROUP BY channel 
                      ORDER BY count DESC 
                      LIMIT 10";

        $channels = $this->wpdb->get_results(
            $this->wpdb->prepare($channelSql, $startDate, $endDate),
            ARRAY_A
        ) ?: [];

        // Get hourly distribution for last 24 hours
        $hourlySql = "SELECT 
                        HOUR(created_at) as hour,
                        COUNT(*) as count
                      FROM {$this->getTableName()}
                      WHERE created_at >= %s
                      GROUP BY HOUR(created_at)
                      ORDER BY hour ASC";

        $last24Hours = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $hourlyStats = $this->wpdb->get_results(
            $this->wpdb->prepare($hourlySql, $last24Hours),
            ARRAY_A
        ) ?: [];

        $stats['channel_distribution'] = $channels;
        $stats['hourly_distribution'] = $hourlyStats;
        $stats['period'] = ['start' => $startDate, 'end' => $endDate];

        return $stats;
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(array $options = []): array
    {
        $limit = $options['limit'] ?? 50;
        $excludeLevels = $options['exclude_levels'] ?? ['debug'];

        $sql = "SELECT * FROM {$this->getTableName()}";
        $params = [];

        if (!empty($excludeLevels)) {
            $placeholders = implode(',', array_fill(0, count($excludeLevels), '%s'));
            $sql .= " WHERE level NOT IN ({$placeholders})";
            $params = $excludeLevels;
        }

        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Clean old log entries
     */
    public function cleanOldEntries(int $daysToKeep = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        $sql = "DELETE FROM {$this->getTableName()} WHERE created_at < %s";
        $result = $this->wpdb->query($this->wpdb->prepare($sql, $cutoffDate));

        return $result !== false ? $result : 0;
    }

    /**
     * Archive old log entries
     */
    public function archiveOldEntries(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        $archiveTable = $this->getTableName() . '_archive';

        // First, create archive table if it doesn't exist
        $createArchiveTableSql = "CREATE TABLE IF NOT EXISTS {$archiveTable} LIKE {$this->getTableName()}";
        $this->wpdb->query($createArchiveTableSql);

        // Move old entries to archive
        $moveSql = "INSERT INTO {$archiveTable} 
                   SELECT * FROM {$this->getTableName()} 
                   WHERE created_at < %s";
        
        $moveResult = $this->wpdb->query($this->wpdb->prepare($moveSql, $cutoffDate));

        if ($moveResult !== false && $moveResult > 0) {
            // Delete original entries after successful move
            $deleteSql = "DELETE FROM {$this->getTableName()} WHERE created_at < %s";
            $this->wpdb->query($this->wpdb->prepare($deleteSql, $cutoffDate));
        }

        return $moveResult !== false ? $moveResult : 0;
    }

    /**
     * Get error patterns for analysis
     */
    public function getErrorPatterns(array $options = []): array
    {
        $days = $options['days'] ?? 7;
        $limit = $options['limit'] ?? 20;

        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $sql = "SELECT 
                    message,
                    level,
                    channel,
                    COUNT(*) as occurrence_count,
                    MAX(created_at) as last_occurrence,
                    MIN(created_at) as first_occurrence
                FROM {$this->getTableName()}
                WHERE created_at >= %s 
                    AND level IN ('error', 'critical', 'alert', 'emergency')
                GROUP BY message, level, channel
                ORDER BY occurrence_count DESC, last_occurrence DESC
                LIMIT %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $startDate, $limit),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get performance metrics from logs
     */
    public function getPerformanceMetrics(array $options = []): array
    {
        $days = $options['days'] ?? 1;
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Look for performance-related log entries
        $sql = "SELECT 
                    AVG(CASE WHEN context LIKE '%execution_time%' THEN 
                        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(context, 'execution_time\":', -1), ',', 1) AS DECIMAL(10,3))
                    END) as avg_execution_time,
                    MAX(CASE WHEN context LIKE '%execution_time%' THEN 
                        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(context, 'execution_time\":', -1), ',', 1) AS DECIMAL(10,3))
                    END) as max_execution_time,
                    COUNT(CASE WHEN message LIKE '%slow%' OR message LIKE '%performance%' THEN 1 END) as slow_operations,
                    COUNT(CASE WHEN level = 'warning' AND message LIKE '%memory%' THEN 1 END) as memory_warnings
                FROM {$this->getTableName()}
                WHERE created_at >= %s";

        $metrics = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $startDate),
            ARRAY_A
        ) ?: [];

        return $metrics;
    }

    /**
     * Export logs for external analysis
     */
    public function exportLogs(array $options = []): array
    {
        $startDate = $options['start_date'] ?? date('Y-m-d H:i:s', strtotime('-7 days'));
        $endDate = $options['end_date'] ?? current_time('mysql');
        $levels = $options['levels'] ?? null;
        $channels = $options['channels'] ?? null;
        $format = $options['format'] ?? 'array';

        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE created_at >= %s AND created_at <= %s";
        $params = [$startDate, $endDate];

        if ($levels && is_array($levels)) {
            $placeholders = implode(',', array_fill(0, count($levels), '%s'));
            $sql .= " AND level IN ({$placeholders})";
            $params = array_merge($params, $levels);
        }

        if ($channels && is_array($channels)) {
            $placeholders = implode(',', array_fill(0, count($channels), '%s'));
            $sql .= " AND channel IN ({$placeholders})";
            $params = array_merge($params, $channels);
        }

        $sql .= " ORDER BY created_at ASC";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $params),
            ARRAY_A
        ) ?: [];

        if ($format === 'json') {
            return wp_json_encode($results, JSON_PRETTY_PRINT);
        }

        if ($format === 'csv') {
            if (empty($results)) {
                return '';
            }

            $csv = '';
            // Headers
            $csv .= implode(',', array_keys($results[0])) . "\n";
            
            // Data
            foreach ($results as $row) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value ?? '') . '"';
                }, $row)) . "\n";
            }

            return $csv;
        }

        return $results;
    }

    /**
     * Get unique channels
     */
    public function getChannels(): array
    {
        $sql = "SELECT DISTINCT channel FROM {$this->getTableName()} 
                WHERE channel IS NOT NULL 
                ORDER BY channel ASC";

        $results = $this->wpdb->get_results($sql, ARRAY_A) ?: [];

        return array_column($results, 'channel');
    }

    /**
     * Get unique log levels
     */
    public function getLevels(): array
    {
        $sql = "SELECT DISTINCT level FROM {$this->getTableName()} 
                ORDER BY 
                    CASE level
                        WHEN 'emergency' THEN 1
                        WHEN 'alert' THEN 2
                        WHEN 'critical' THEN 3
                        WHEN 'error' THEN 4
                        WHEN 'warning' THEN 5
                        WHEN 'notice' THEN 6
                        WHEN 'info' THEN 7
                        WHEN 'debug' THEN 8
                        ELSE 9
                    END";

        $results = $this->wpdb->get_results($sql, ARRAY_A) ?: [];

        return array_column($results, 'level');
    }
}
