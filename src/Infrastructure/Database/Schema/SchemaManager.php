<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Schema Manager für erweiterbares Datenbank-Management
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class SchemaManager 
{
    private array $schemas = [];
    private array $dependencies = [];
    
    public function __construct() 
    {
        $this->registerSchemas();
        $this->buildDependencyGraph();
    }
    
    /**
     * Registriert alle Schema-Klassen
     */
    private function registerSchemas(): void 
    {
        $this->schemas = [
            'galleries' => new GallerySchema(),
            // Weitere Schemas werden nach Implementierung hinzugefügt
            // 'clients' => new ClientSchema(),
            // 'images' => new ImageSchema(),
            // 'ratings' => new RatingSchema(),
            // 'log_entries' => new LogEntrySchema(),
        ];
    }
    
    /**
     * Registriert ein Schema dynamisch
     */
    public function registerSchema(BaseSchema $schema): void
    {
        global $wpdb;
        $tableName = $schema->getTableName();
        $schemaKey = str_replace([$wpdb->prefix, 'ml_clientgallerie_'], '', $tableName);
        $this->schemas[$schemaKey] = $schema;
    }
    
    /**
     * Definiert Abhängigkeiten zwischen Schemas
     */
    private function buildDependencyGraph(): void 
    {
        $this->dependencies = [
            'clients' => [],
            'galleries' => ['clients'], // Galleries depend on clients
            'images' => ['galleries'], // Images depend on galleries
            'ratings' => ['images'], // Ratings depend on images
            'log_entries' => [],
        ];
    }
    
    /**
     * Installiert alle Schemas in der richtigen Reihenfolge
     */
    public function installAll(): array 
    {
        $logger = LoggerRegistry::getLogger();
        $logger?->info('Starting database schema installation');
        
        $installOrder = $this->getInstallationOrder();
        $success = true;
        $errors = [];
        $installed = [];
        
        foreach ($installOrder as $schemaName) {
            $result = $this->installSchema($schemaName);
            if ($result) {
                $installed[] = $schemaName;
            } else {
                $success = false;
                $errors[] = "Failed to install schema: $schemaName";
                break;
            }
        }
        
        if ($success) {
            $this->updateSchemaVersion();
            $logger?->info('Database schema installation completed successfully');
        } else {
            $logger?->error('Database schema installation failed');
        }
        
        return [
            'success' => $success,
            'installed' => $installed,
            'errors' => $errors
        ];
    }
    
    /**
     * Installiert ein einzelnes Schema
     */
    public function installSchema(string $schemaName): bool 
    {
        if (!isset($this->schemas[$schemaName])) {
            return false;
        }
        
        $schema = $this->schemas[$schemaName];
        $logger = LoggerRegistry::getLogger();
        
        try {
            $logger?->info("Installing schema: $schemaName");
            $result = $schema->create();
            
            if ($result) {
                $logger?->info("Schema installed successfully: $schemaName");
            } else {
                $logger?->error("Failed to install schema: $schemaName");
            }
            
            return $result;
        } catch (\Exception $e) {
            $logger?->error("Exception during schema installation: $schemaName", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Deinstalliert alle Schemas in umgekehrter Reihenfolge
     */
    public function uninstallAll(): bool 
    {
        $logger = LoggerRegistry::getLogger();
        $logger?->info('Starting database schema uninstallation');
        
        $uninstallOrder = array_reverse($this->getInstallationOrder());
        $success = true;
        
        foreach ($uninstallOrder as $schemaName) {
            if (!$this->uninstallSchema($schemaName)) {
                $success = false;
                // Continue with other schemas even if one fails
            }
        }
        
        if ($success) {
            $this->removeSchemaVersion();
            $logger?->info('Database schema uninstallation completed successfully');
        } else {
            $logger?->warning('Database schema uninstallation completed with errors');
        }
        
        return $success;
    }
    
    /**
     * Deinstalliert ein einzelnes Schema
     */
    public function uninstallSchema(string $schemaName): bool 
    {
        if (!isset($this->schemas[$schemaName])) {
            return false;
        }
        
        $schema = $this->schemas[$schemaName];
        $logger = LoggerRegistry::getLogger();
        
        try {
            $logger?->info("Uninstalling schema: $schemaName");
            $result = $schema->drop();
            
            if ($result) {
                $logger?->info("Schema uninstalled successfully: $schemaName");
            } else {
                $logger?->error("Failed to uninstall schema: $schemaName");
            }
            
            return $result;
        } catch (\Exception $e) {
            $logger?->error("Exception during schema uninstallation: $schemaName", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Validiert alle Schemas
     */
    public function validateAll(): array 
    {
        $allIssues = [];
        
        foreach ($this->schemas as $schemaName => $schema) {
            $issues = $schema->validate();
            if (!empty($issues)) {
                $allIssues[$schemaName] = $issues;
            }
        }
        
        return $allIssues;
    }
    
    /**
     * Prüft ob alle Schemas installiert sind
     */
    public function areAllSchemasInstalled(): bool 
    {
        foreach ($this->schemas as $schema) {
            if (!$schema->exists()) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Gibt Informationen über alle Schemas zurück
     */
    public function getSchemaInfo(): array 
    {
        $info = [];
        
        foreach ($this->schemas as $schemaName => $schema) {
            $info[$schemaName] = [
                'exists' => $schema->exists(),
                'table_name' => $schema->getTableName(),
                'dependencies' => $this->dependencies[$schemaName] ?? [],
                'validation_issues' => $schema->validate()
            ];
        }
        
        return $info;
    }
    
    /**
     * Ermittelt die richtige Installationsreihenfolge basierend auf Abhängigkeiten
     */
    private function getInstallationOrder(): array 
    {
        $order = [];
        $visited = [];
        $visiting = [];
        
        foreach (array_keys($this->schemas) as $schema) {
            $this->topologicalSort($schema, $visited, $visiting, $order);
        }
        
        return array_reverse($order);
    }
    
    /**
     * Topologische Sortierung für Abhängigkeiten
     */
    private function topologicalSort(string $schema, array &$visited, array &$visiting, array &$order): void 
    {
        if (isset($visiting[$schema])) {
            throw new \RuntimeException("Circular dependency detected involving schema: $schema");
        }
        
        if (isset($visited[$schema])) {
            return;
        }
        
        $visiting[$schema] = true;
        
        foreach ($this->dependencies[$schema] ?? [] as $dependency) {
            $this->topologicalSort($dependency, $visited, $visiting, $order);
        }
        
        unset($visiting[$schema]);
        $visited[$schema] = true;
        $order[] = $schema;
    }
    
    /**
     * Speichert die aktuelle Schema-Version
     */
    private function updateSchemaVersion(): void 
    {
        update_option('mlcg_schema_version', '1.0.0');
        update_option('mlcg_schema_installed_at', current_time('mysql'));
    }
    
    /**
     * Entfernt die Schema-Version
     */
    private function removeSchemaVersion(): void 
    {
        delete_option('mlcg_schema_version');
        delete_option('mlcg_schema_installed_at');
    }
    
    /**
     * Gibt die aktuelle Schema-Version zurück
     */
    public function getCurrentSchemaVersion(): ?string 
    {
        return get_option('mlcg_schema_version');
    }
    
    /**
     * Fügt ein neues Schema zur Laufzeit hinzu (für Erweiterungen)
     */
    public function addSchema(string $name, BaseSchema $schema, array $dependencies = []): void 
    {
        $this->schemas[$name] = $schema;
        $this->dependencies[$name] = $dependencies;
        
        $logger = LoggerRegistry::getLogger();
        $logger?->info("Schema registered: $name", ['dependencies' => $dependencies]);
    }
    
    /**
     * Entfernt ein Schema
     */
    public function removeSchema(string $name): bool 
    {
        if (!isset($this->schemas[$name])) {
            return false;
        }
        
        // Prüfe ob andere Schemas von diesem abhängen
        foreach ($this->dependencies as $schema => $deps) {
            if (in_array($name, $deps)) {
                $logger = LoggerRegistry::getLogger();
                $logger?->error("Cannot remove schema $name: dependency of $schema");
                return false;
            }
        }
        
        unset($this->schemas[$name]);
        unset($this->dependencies[$name]);
        
        return true;
    }
    
    /**
     * Erstellt alle Schemas neu (Warnung: Daten gehen verloren!)
     */
    public function recreateAll(): array 
    {
        $logger = LoggerRegistry::getLogger();
        $results = [];
        
        $logger?->warning('Starting schema recreation - all data will be lost!');
        
        // Erst alle uninstallieren
        foreach (array_reverse($this->getInstallationOrder()) as $schemaName) {
            try {
                $this->uninstallSchema($schemaName);
                $results[$schemaName]['uninstall'] = 'success';
            } catch (\Exception $e) {
                $results[$schemaName]['uninstall'] = 'failed: ' . $e->getMessage();
                $logger?->error("Failed to uninstall schema: $schemaName", ['error' => $e->getMessage()]);
            }
        }
        
        // Dann alle neu installieren
        foreach ($this->getInstallationOrder() as $schemaName) {
            try {
                $success = $this->installSchema($schemaName);
                $results[$schemaName]['install'] = $success ? 'success' : 'failed';
            } catch (\Exception $e) {
                $results[$schemaName]['install'] = 'failed: ' . $e->getMessage();
                $logger?->error("Failed to install schema: $schemaName", ['error' => $e->getMessage()]);
            }
        }
        
        $logger?->info('Schema recreation completed', ['results' => $results]);
        
        return $results;
    }
    
    /**
     * Gibt alle verfügbaren Schemas zurück
     */
    public function getAvailableSchemas(): array 
    {
        return array_keys($this->schemas);
    }
    
    /**
     * Gibt ein spezifisches Schema zurück
     */
    public function getSchema(string $name): ?BaseSchema 
    {
        return $this->schemas[$name] ?? null;
    }
    
    /**
     * Gibt die Liste der registrierten Schemas zurück
     */
    public function getRegisteredSchemas(): array
    {
        return array_keys($this->schemas);
    }
}
