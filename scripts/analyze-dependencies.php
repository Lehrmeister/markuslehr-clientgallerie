<?php
/**
 * PHP Dependency Analyzer
 * Analysiert alle PHP-Dateien und erstellt eine Abhängigkeitskarte
 */

declare(strict_types=1);

class DependencyAnalyzer 
{
    private array $dependencies = [];
    private array $classes = [];
    private array $functions = [];
    
    public function analyze(string $directory = '.'): array 
    {
        $files = $this->findPhpFiles($directory);
        
        foreach ($files as $file) {
            $this->analyzeFile($file);
        }
        
        return [
            'analysis_date' => date('Y-m-d H:i:s'),
            'files_analyzed' => count($files),
            'classes_found' => count($this->classes),
            'functions_found' => count($this->functions),
            'dependencies' => $this->dependencies,
            'classes' => $this->classes,
            'functions' => $this->functions,
            'usage_map' => $this->generateUsageMap(),
            'orphaned_files' => $this->findOrphanedFiles(),
            'duplicate_functions' => $this->findDuplicateFunctions()
        ];
    }
    
    private function findPhpFiles(string $directory): array 
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                !$this->isExcluded($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function isExcluded(string $path): bool 
    {
        $excludes = ['vendor/', 'node_modules/', '.git/'];
        
        foreach ($excludes as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function analyzeFile(string $file): void 
    {
        $content = file_get_contents($file);
        if ($content === false) return;
        
        $lines = substr_count($content, "\n") + 1;
        
        // Klassen finden
        preg_match_all('/class\s+(\w+)/', $content, $classMatches);
        foreach ($classMatches[1] as $className) {
            $this->classes[$className] = [
                'file' => $file,
                'lines' => $lines,
                'is_test' => $this->isTestFile($file),
                'namespace' => $this->extractNamespace($content)
            ];
        }
        
        // Funktionen finden
        preg_match_all('/function\s+(\w+)\s*\(/', $content, $functionMatches);
        foreach ($functionMatches[1] as $functionName) {
            if (!isset($this->functions[$functionName])) {
                $this->functions[$functionName] = [];
            }
            $this->functions[$functionName][] = [
                'file' => $file,
                'lines' => $lines,
                'is_test' => $this->isTestFile($file)
            ];
        }
        
        // Dependencies finden
        preg_match_all('/use\s+([^;]+);/', $content, $useMatches);
        preg_match_all('/new\s+(\w+)\s*\(/', $content, $newMatches);
        preg_match_all('/(\w+)::\w+/', $content, $staticMatches);
        
        $deps = array_merge(
            $useMatches[1] ?? [],
            $newMatches[1] ?? [],
            $staticMatches[1] ?? []
        );
        
        $this->dependencies[$file] = array_unique($deps);
    }
    
    private function isTestFile(string $file): bool 
    {
        return strpos($file, 'Test.php') !== false || 
               strpos($file, '/tests/') !== false ||
               strpos($file, 'Mock') !== false;
    }
    
    private function extractNamespace(string $content): ?string 
    {
        preg_match('/namespace\s+([^;]+);/', $content, $matches);
        return $matches[1] ?? null;
    }
    
    private function generateUsageMap(): array 
    {
        $usageMap = [];
        
        foreach ($this->classes as $className => $classInfo) {
            $usageMap[$className] = [
                'defined_in' => $classInfo['file'],
                'used_in' => [],
                'is_test' => $classInfo['is_test']
            ];
            
            // Finde wo diese Klasse verwendet wird
            foreach ($this->dependencies as $file => $deps) {
                if (in_array($className, $deps) || 
                    in_array($classInfo['namespace'] . '\\' . $className, $deps)) {
                    $usageMap[$className]['used_in'][] = $file;
                }
            }
        }
        
        return $usageMap;
    }
    
    private function findOrphanedFiles(): array 
    {
        $orphaned = [];
        
        foreach ($this->classes as $className => $classInfo) {
            $usageCount = 0;
            
            // Zähle Verwendungen (außer in Test-Dateien)
            foreach ($this->dependencies as $file => $deps) {
                if (!$this->isTestFile($file) && 
                    (in_array($className, $deps) || 
                     in_array($classInfo['namespace'] . '\\' . $className, $deps))) {
                    $usageCount++;
                }
            }
            
            if ($usageCount === 0 && !$classInfo['is_test']) {
                $orphaned[] = [
                    'class' => $className,
                    'file' => $classInfo['file'],
                    'reason' => 'Keine Verwendung gefunden'
                ];
            }
        }
        
        return $orphaned;
    }
    
    private function findDuplicateFunctions(): array 
    {
        $duplicates = [];
        
        foreach ($this->functions as $functionName => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[$functionName] = $occurrences;
            }
        }
        
        return $duplicates;
    }
}

// Script ausführen wenn direkt aufgerufen
if (isset($argv[0]) && basename($argv[0]) === 'analyze-dependencies.php') {
    $analyzer = new DependencyAnalyzer();
    $result = $analyzer->analyze('.');
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
