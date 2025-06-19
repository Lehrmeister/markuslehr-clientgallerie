#!/usr/bin/env php
<?php
/**
 * Dead Code & Orphaned Test Detector
 * Findet verwaizte Dateien und nicht mehr verwendete Code-Teile
 * 
 * @author MarkusLehr ClientGallerie
 * @version 1.0.0
 */

class DeadCodeDetector {
    private array $sourceFiles = [];
    private array $testFiles = [];
    private array $usageMap = [];
    private array $deadCode = [];
    
    public function analyze(string $directory = '.'): array {
        $this->scanDirectory($directory);
        $this->buildUsageMap();
        $this->findDeadCode();
        $this->findOrphanedTests();
        
        return [
            'analysis_date' => date('Y-m-d H:i:s'),
            'total_files' => count($this->sourceFiles) + count($this->testFiles),
            'source_files' => count($this->sourceFiles),
            'test_files' => count($this->testFiles),
            'dead_classes' => $this->deadCode['classes'] ?? [],
            'dead_methods' => $this->deadCode['methods'] ?? [],
            'orphaned_tests' => $this->deadCode['orphaned_tests'] ?? [],
            'unused_files' => $this->deadCode['unused_files'] ?? [],
            'recommendations' => $this->generateRecommendations()
        ];
    }
    
    private function scanDirectory(string $directory): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $filePath = $file->getPathname();
                
                if (strpos($filePath, 'Test.php') !== false || 
                    strpos($filePath, '/tests/') !== false) {
                    $this->testFiles[] = $filePath;
                } else {
                    $this->sourceFiles[] = $filePath;
                }
            }
        }
    }
    
    private function buildUsageMap(): void {
        // Analysiere alle Source-Dateien nach Klassen und Methoden
        foreach ($this->sourceFiles as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            
            $this->extractDefinitions($file, $tokens);
            $this->extractUsages($file, $tokens);
        }
        
        // Analysiere Test-Dateien nach Referenzen
        foreach ($this->testFiles as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);
            
            $this->extractUsages($file, $tokens);
        }
    }
    
    private function extractDefinitions(string $file, array $tokens): void {
        $namespace = '';
        $inClass = false;
        $currentClass = '';
        
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $namespace = $this->extractNamespace($tokens, $i);
                        break;
                        
                    case T_CLASS:
                    case T_INTERFACE:
                    case T_TRAIT:
                        $className = $this->extractClassName($tokens, $i);
                        $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
                        
                        $this->usageMap['definitions']['classes'][$fullClassName] = [
                            'file' => $file,
                            'type' => $token[0] === T_CLASS ? 'class' : 
                                     ($token[0] === T_INTERFACE ? 'interface' : 'trait'),
                            'used_by' => []
                        ];
                        
                        $currentClass = $fullClassName;
                        $inClass = true;
                        break;
                        
                    case T_FUNCTION:
                        if ($inClass) {
                            $methodName = $this->extractMethodName($tokens, $i);
                            $fullMethodName = $currentClass . '::' . $methodName;
                            
                            $this->usageMap['definitions']['methods'][$fullMethodName] = [
                                'file' => $file,
                                'class' => $currentClass,
                                'method' => $methodName,
                                'used_by' => []
                            ];
                        }
                        break;
                }
            } elseif ($token === '}' && $inClass) {
                // Vereinfachte Klassen-Ende-Erkennung
                $inClass = false;
                $currentClass = '';
            }
        }
    }
    
    private function extractUsages(string $file, array $tokens): void {
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_NEW:
                        $className = $this->extractUsedClassName($tokens, $i);
                        if ($className) {
                            $this->recordUsage('classes', $className, $file);
                        }
                        break;
                        
                    case T_DOUBLE_COLON: // ::
                        $className = $this->extractStaticUsage($tokens, $i);
                        if ($className) {
                            $this->recordUsage('classes', $className, $file);
                        }
                        break;
                        
                    case T_INSTANCEOF:
                        $className = $this->extractInstanceofUsage($tokens, $i);
                        if ($className) {
                            $this->recordUsage('classes', $className, $file);
                        }
                        break;
                        
                    case T_USE:
                        $className = $this->extractUseStatement($tokens, $i);
                        if ($className) {
                            $this->recordUsage('classes', $className, $file);
                        }
                        break;
                }
            }
        }
    }
    
    private function findDeadCode(): void {
        $this->deadCode['classes'] = [];
        $this->deadCode['methods'] = [];
        $this->deadCode['unused_files'] = [];
        
        // Finde unbenutzte Klassen
        foreach ($this->usageMap['definitions']['classes'] ?? [] as $className => $info) {
            if (empty($info['used_by'])) {
                $this->deadCode['classes'][] = [
                    'class' => $className,
                    'file' => $info['file'],
                    'type' => $info['type']
                ];
            }
        }
        
        // Finde unbenutzte Methoden
        foreach ($this->usageMap['definitions']['methods'] ?? [] as $methodName => $info) {
            if (empty($info['used_by']) && !$this->isSpecialMethod($info['method'])) {
                $this->deadCode['methods'][] = [
                    'method' => $methodName,
                    'file' => $info['file'],
                    'class' => $info['class']
                ];
            }
        }
        
        // Finde Dateien ohne Referenzen
        foreach ($this->sourceFiles as $file) {
            if (!$this->fileHasReferences($file)) {
                $this->deadCode['unused_files'][] = $file;
            }
        }
    }
    
    private function findOrphanedTests(): void {
        $this->deadCode['orphaned_tests'] = [];
        
        foreach ($this->testFiles as $testFile) {
            $testName = basename($testFile, '.php');
            $sourceFile = str_replace('Test', '', $testName) . '.php';
            
            // Suche nach der entsprechenden Source-Datei
            $found = false;
            foreach ($this->sourceFiles as $source) {
                if (strpos($source, $sourceFile) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $this->deadCode['orphaned_tests'][] = [
                    'test_file' => $testFile,
                    'expected_source' => $sourceFile,
                    'reason' => 'Source file not found'
                ];
            }
        }
    }
    
    private function recordUsage(string $type, string $name, string $usedBy): void {
        if (isset($this->usageMap['definitions'][$type][$name])) {
            $this->usageMap['definitions'][$type][$name]['used_by'][] = $usedBy;
        }
    }
    
    private function isSpecialMethod(string $methodName): bool {
        $specialMethods = [
            '__construct', '__destruct', '__get', '__set', '__isset', '__unset',
            '__call', '__callStatic', '__toString', '__invoke', '__clone',
            '__sleep', '__wakeup', '__serialize', '__unserialize',
            'activate', 'deactivate', 'uninstall' // WordPress Plugin Hooks
        ];
        
        return in_array($methodName, $specialMethods) || 
               str_starts_with($methodName, 'wp_') || // WordPress Hooks
               str_starts_with($methodName, 'ajax_') || // AJAX Handlers
               str_starts_with($methodName, 'admin_'); // Admin Handlers
    }
    
    private function fileHasReferences(string $file): bool {
        $hasDefinitions = false;
        
        // Prüfe, ob die Datei definierte Klassen/Methoden hat, die verwendet werden
        foreach ($this->usageMap['definitions']['classes'] ?? [] as $className => $info) {
            if ($info['file'] === $file && !empty($info['used_by'])) {
                $hasDefinitions = true;
                break;
            }
        }
        
        return $hasDefinitions;
    }
    
    private function generateRecommendations(): array {
        $recommendations = [];
        
        if (!empty($this->deadCode['classes'])) {
            $count = count($this->deadCode['classes']);
            $recommendations[] = "🗑️ $count unbenutzte Klassen gefunden. Prüfen Sie, ob diese entfernt werden können.";
        }
        
        if (!empty($this->deadCode['methods'])) {
            $count = count($this->deadCode['methods']);
            $recommendations[] = "⚠️ $count unbenutzte Methoden gefunden. Diese könnten refactored oder entfernt werden.";
        }
        
        if (!empty($this->deadCode['orphaned_tests'])) {
            $count = count($this->deadCode['orphaned_tests']);
            $recommendations[] = "🧪 $count verwaiste Test-Dateien gefunden. Source-Code möglicherweise gelöscht oder umbenannt.";
        }
        
        if (!empty($this->deadCode['unused_files'])) {
            $count = count($this->deadCode['unused_files']);
            $recommendations[] = "📁 $count unbenutzte Dateien gefunden. Diese könnten archiviert oder entfernt werden.";
        }
        
        if (empty($this->deadCode['classes']) && 
            empty($this->deadCode['methods']) && 
            empty($this->deadCode['orphaned_tests']) &&
            empty($this->deadCode['unused_files'])) {
            $recommendations[] = "✅ Keine Dead Code oder verwaisten Tests gefunden. Codebase ist sauber!";
        }
        
        return $recommendations;
    }
    
    // Helper-Methoden für Token-Parsing
    private function extractNamespace(array $tokens, int $index): string {
        $namespace = '';
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $namespace .= $tokens[$i][1];
            } elseif ($tokens[$i] === '\\') {
                $namespace .= '\\';
            } elseif ($tokens[$i] === ';') {
                break;
            }
        }
        return $namespace;
    }
    
    private function extractClassName(array $tokens, int $index): string {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }
        return '';
    }
    
    private function extractMethodName(array $tokens, int $index): string {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }
        return '';
    }
    
    private function extractUsedClassName(array $tokens, int $index): ?string {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            } elseif ($tokens[$i] === '(') {
                break;
            }
        }
        return null;
    }
    
    private function extractStaticUsage(array $tokens, int $index): ?string {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }
        return null;
    }
    
    private function extractInstanceofUsage(array $tokens, int $index): ?string {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }
        return null;
    }
    
    private function extractUseStatement(array $tokens, int $index): ?string {
        $className = '';
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $className .= $tokens[$i][1];
            } elseif ($tokens[$i] === '\\') {
                $className .= '\\';
            } elseif ($tokens[$i] === ';' || $tokens[$i] === ',') {
                break;
            }
        }
        return !empty($className) ? $className : null;
    }
}

// CLI Ausführung
if (isset($argv[1])) {
    $detector = new DeadCodeDetector();
    $result = $detector->analyze($argv[1]);
    
    echo "🔍 Dead Code Detection Report\n";
    echo "=============================\n\n";
    echo "📊 Zusammenfassung:\n";
    echo "  • Gesamt-Dateien: " . $result['total_files'] . "\n";
    echo "  • Source-Dateien: " . $result['source_files'] . "\n";
    echo "  • Test-Dateien: " . $result['test_files'] . "\n\n";
    
    if (!empty($result['dead_classes'])) {
        echo "🗑️ Unbenutzte Klassen (" . count($result['dead_classes']) . "):\n";
        foreach ($result['dead_classes'] as $class) {
            echo "  • {$class['class']} ({$class['type']}) in {$class['file']}\n";
        }
        echo "\n";
    }
    
    if (!empty($result['dead_methods'])) {
        echo "⚠️ Unbenutzte Methoden (" . count($result['dead_methods']) . "):\n";
        foreach ($result['dead_methods'] as $method) {
            echo "  • {$method['method']} in {$method['file']}\n";
        }
        echo "\n";
    }
    
    if (!empty($result['orphaned_tests'])) {
        echo "🧪 Verwaiste Tests (" . count($result['orphaned_tests']) . "):\n";
        foreach ($result['orphaned_tests'] as $test) {
            echo "  • {$test['test_file']} - {$test['reason']}\n";
        }
        echo "\n";
    }
    
    if (!empty($result['unused_files'])) {
        echo "📁 Unbenutzte Dateien (" . count($result['unused_files']) . "):\n";
        foreach ($result['unused_files'] as $file) {
            echo "  • $file\n";
        }
        echo "\n";
    }
    
    echo "💡 Empfehlungen:\n";
    foreach ($result['recommendations'] as $recommendation) {
        echo "  $recommendation\n";
    }
    
} else {
    echo "Usage: php find-dead-code.php <directory>\n";
    echo "Example: php find-dead-code.php ./src\n";
    exit(1);
}
