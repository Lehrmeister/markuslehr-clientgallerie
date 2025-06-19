<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Logging;

/**
 * Logger Registry für globalen Zugriff auf den Logger
 * Ermöglicht es allen Klassen, den Logger zu verwenden
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Logging
 * @author Markus Lehr
 * @since 1.0.0
 */
class LoggerRegistry 
{
    private static ?Logger $logger = null;
    
    public static function setLogger(Logger $logger): void 
    {
        self::$logger = $logger;
    }
    
    public static function getLogger(): ?Logger 
    {
        return self::$logger;
    }
    
    public static function hasLogger(): bool 
    {
        return self::$logger !== null;
    }
}

/**
 * Global function für einfachen Logger-Zugriff
 */
function mlcg_log(): ?Logger 
{
    return LoggerRegistry::getLogger();
}

/**
 * Helper functions für direktes Logging
 */
function mlcg_debug(string $message, array $context = []): void 
{
    if ($logger = LoggerRegistry::getLogger()) {
        $logger->debug($message, $context);
    }
}

function mlcg_info(string $message, array $context = []): void 
{
    if ($logger = LoggerRegistry::getLogger()) {
        $logger->info($message, $context);
    }
}

function mlcg_warning(string $message, array $context = []): void 
{
    if ($logger = LoggerRegistry::getLogger()) {
        $logger->warning($message, $context);
    }
}

function mlcg_error(string $message, array $context = []): void 
{
    if ($logger = LoggerRegistry::getLogger()) {
        $logger->error($message, $context);
    }
}

/**
 * Automatisches Function-Logging (Decorator Pattern)
 */
function mlcg_log_function(string $functionName, callable $function, array $args = []) 
{
    $logger = LoggerRegistry::getLogger();
    
    if (!$logger) {
        return call_user_func_array($function, $args);
    }
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    try {
        $result = call_user_func_array($function, $args);
        
        $logger->logFunction($functionName, $args, json_encode($result));
        
        return $result;
        
    } catch (\Exception $e) {
        $logger->error("Function $functionName failed", [
            'function' => $functionName,
            'args' => $args,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    } finally {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $logger->debug("Function performance: $functionName", [
            'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'memory_used' => ($endMemory - $startMemory) . ' bytes',
            'peak_memory' => memory_get_peak_usage(true) . ' bytes'
        ]);
    }
}
