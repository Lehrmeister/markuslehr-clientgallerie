<?php
/**
 * Intelligenter Code-Qualitäts-Analyzer
 * Bewertet Dateien nach Komplexität, nicht nur Zeilenzahl
 */

declare(strict_types=1);

class SmartCodeAnalyzer 
{
    private array $qualityThresholds = [
        'cyclomatic_complexity_max' => 10,
        'methods_per_class_max' => 15,
        'dependencies_max' => 7,       // Reduziert von 8
        'nesting_depth_max' => 4,
        'lines_per_method_max' => 20,
        'comment_ratio_min' => 0.10, // 10%
        'cohesion_min' => 0.70, // 70%
        'coupling_max' => 0.30, // 30%
        'responsibility_score_min' => 60, // Neue Metrik: Single Responsibility
        'code_to_total_lines_min' => 0.60, // 60% sollten produktiver Code sein
    ];
    
    public function analyzeDirectory(string $directory = '.'): array 
    {
        $files = $this->findPhpFiles($directory);
        $results = [];
        
        foreach ($files as $file) {
            $results[$file] = $this->analyzeFile($file);
        }
        
        return [
            'analysis_date' => date('Y-m-d H:i:s'),
            'files_analyzed' => count($files),
            'overall_score' => $this->calculateOverallScore($results),
            'quality_issues' => $this->findQualityIssues($results),
            'recommendations' => $this->generateRecommendations($results),
            'file_details' => $results
        ];
    }
    
    public function analyzeFile(string $file): array 
    {
        $content = file_get_contents($file);
        if ($content === false) return [];
        
        $tokens = token_get_all($content);
        
        return [
            'file' => $file,
            'file_type' => $this->determineFileType($file),
            'lines_of_code' => $this->countLines($content),
            'cyclomatic_complexity' => $this->calculateComplexity($tokens),
            'number_of_methods' => $this->countMethods($tokens),
            'number_of_classes' => $this->countClasses($tokens),
            'number_of_dependencies' => $this->countDependencies($tokens),
            'nesting_depth' => $this->calculateNestingDepth($tokens),
            'lines_per_method_avg' => $this->calculateAvgMethodLength($tokens, $content),
            'comment_ratio' => $this->calculateCommentRatio($tokens),
            // NEUE VERANTWORTLICHKEITS-METRIKEN
            'responsibility_score' => $this->analyzeResponsibility($tokens, $content),
            'cohesion_score' => $this->calculateCohesion($tokens),
            'coupling_score' => $this->calculateCoupling($tokens),
            'code_to_total_ratio' => $this->calculateCodeRatio($content),
            'domain_concepts' => $this->extractDomainConcepts($content),
            'method_prefixes' => $this->extractMethodPrefixes($tokens),
            'quality_score' => 0, // Wird später berechnet
            'issues' => [],
            'recommendations' => []
        ];
    }
    
    private function determineFileType(string $file): string 
    {
        if (strpos($file, 'Test.php') !== false) return 'test';
        if (strpos($file, 'Mock') !== false) return 'mock';
        if (strpos($file, 'Controller') !== false) return 'controller';
        if (strpos($file, 'Repository') !== false) return 'repository';
        if (strpos($file, 'Service') !== false) return 'service';
        if (strpos($file, 'Entity') !== false) return 'entity';
        if (strpos($file, 'ValueObject') !== false) return 'value_object';
        return 'other';
    }
    
    private function calculateComplexity(array $tokens): int 
    {
        $complexity = 1; // Base complexity
        $complexityKeywords = [T_IF, T_ELSEIF, T_ELSE, T_SWITCH, T_CASE, 
                              T_FOR, T_FOREACH, T_WHILE, T_DO, T_TRY, T_CATCH];
        
        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], $complexityKeywords)) {
                $complexity++;
            }
        }
        
        return $complexity;
    }
    
    private function countMethods(array $tokens): int 
    {
        $count = 0;
        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                $count++;
            }
        }
        return $count;
    }
    
    private function countClasses(array $tokens): int 
    {
        $count = 0;
        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                $count++;
            }
        }
        return $count;
    }
    
    private function countDependencies(array $tokens): int 
    {
        $dependencies = [];
        
        for ($i = 0; $i < count($tokens); $i++) {
            // Use statements
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_USE) {
                $j = $i + 1;
                while ($j < count($tokens) && $tokens[$j] !== ';') {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $dependencies[] = $tokens[$j][1];
                    }
                    $j++;
                }
            }
            
            // New instantiations
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NEW) {
                if (isset($tokens[$i + 2]) && is_array($tokens[$i + 2])) {
                    $dependencies[] = $tokens[$i + 2][1];
                }
            }
        }
        
        return count(array_unique($dependencies));
    }
    
    private function calculateNestingDepth(array $tokens): int 
    {
        $maxDepth = 0;
        $currentDepth = 0;
        
        foreach ($tokens as $token) {
            if ($token === '{') {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
            } elseif ($token === '}') {
                $currentDepth--;
            }
        }
        
        return $maxDepth;
    }
    
    private function calculateAvgMethodLength(array $tokens, string $content): float 
    {
        $methods = $this->countMethods($tokens);
        $lines = $this->countLines($content);
        
        return $methods > 0 ? round($lines / $methods, 1) : 0;
    }
    
    private function calculateCommentRatio(array $tokens): float 
    {
        $totalTokens = count($tokens);
        $commentTokens = 0;
        
        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $commentTokens++;
            }
        }
        
        return $totalTokens > 0 ? round($commentTokens / $totalTokens, 3) : 0;
    }
    
    // NEUE VERANTWORTLICHKEITS-ANALYSE METHODEN
    
    private function analyzeResponsibility(array $tokens, string $content): float 
    {
        $score = 100.0;
        
        // 1. Methodenpräfix-Diversität (zeigt verschiedene Verantwortlichkeiten)
        $methodPrefixes = $this->extractMethodPrefixes($tokens);
        if (count($methodPrefixes) > 3) {
            $score -= (count($methodPrefixes) - 3) * 15; // -15 pro zusätzlichen Präfix
        }
        
        // 2. Domain-Konzept-Diversität
        $domains = $this->extractDomainConcepts($content);
        if (count($domains) > 2) {
            $score -= (count($domains) - 2) * 10; // -10 pro zusätzlichem Domain-Konzept
        }
        
        // 3. Klassen-zu-Datei Verhältnis
        $classCount = $this->countClasses($tokens);
        if ($classCount > 1) {
            $score -= ($classCount - 1) * 20; // -20 pro zusätzlicher Klasse
        }
        
        return max(0, min(100, $score));
    }
    
    private function extractMethodPrefixes(array $tokens): array 
    {
        $prefixes = [];
        $prefixPatterns = ['get', 'set', 'create', 'update', 'delete', 'find', 'save', 
                          'validate', 'process', 'handle', 'render', 'format', 'parse',
                          'calculate', 'generate', 'build', 'convert', 'transform'];
        
        $inFunction = false;
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_FUNCTION) {
                $inFunction = true;
            } elseif ($inFunction && is_array($token) && $token[0] === T_STRING) {
                $methodName = strtolower($token[1]);
                foreach ($prefixPatterns as $prefix) {
                    if (str_starts_with($methodName, $prefix)) {
                        $prefixes[] = $prefix;
                        break;
                    }
                }
                $inFunction = false;
            }
        }
        
        return array_unique($prefixes);
    }
    
    private function extractDomainConcepts(string $content): array 
    {
        $concepts = ['Gallery', 'Image', 'Client', 'User', 'Rating', 'Comment', 'Tag', 
                    'Category', 'File', 'Upload', 'Download', 'Permission', 'Role',
                    'Notification', 'Email', 'Report', 'Statistics', 'Settings',
                    'Database', 'Cache', 'Session', 'Security', 'Validation'];
        
        $foundConcepts = [];
        foreach ($concepts as $concept) {
            if (stripos($content, $concept) !== false) {
                $foundConcepts[] = $concept;
            }
        }
        
        return $foundConcepts;
    }
    
    private function calculateCohesion(array $tokens): float 
    {
        // Vereinfachte Kohäsions-Berechnung
        // Hohe Kohäsion = Methoden greifen auf ähnliche Variablen/Attribute zu
        
        $methods = $this->getMethods($tokens);
        if (count($methods) <= 1) return 100.0;
        
        $sharedVariables = 0;
        $totalPairs = 0;
        
        // Vereinfachte Heuristik: Je ähnlicher die Variablennamen in Methoden, 
        // desto höher die Kohäsion
        for ($i = 0; $i < count($methods); $i++) {
            for ($j = $i + 1; $j < count($methods); $j++) {
                $similarity = $this->calculateMethodSimilarity($methods[$i], $methods[$j]);
                $sharedVariables += $similarity;
                $totalPairs++;
            }
        }
        
        return $totalPairs > 0 ? min(100, ($sharedVariables / $totalPairs) * 100) : 75.0;
    }
    
    private function calculateCoupling(array $tokens): float 
    {
        $dependencies = $this->countDependencies($tokens);
        
        // Bewertung: weniger externe Abhängigkeiten = niedrigere Kopplung
        if ($dependencies <= 3) return 20.0; // Sehr niedrige Kopplung
        if ($dependencies <= 5) return 35.0; // Niedrige Kopplung
        if ($dependencies <= 7) return 50.0; // Moderate Kopplung
        if ($dependencies <= 10) return 70.0; // Hohe Kopplung
        return 90.0; // Sehr hohe Kopplung
    }
    
    private function calculateCodeRatio(string $content): float 
    {
        $totalLines = substr_count($content, "\n") + 1;
        $codeLines = $this->countCodeLines($content);
        
        return $totalLines > 0 ? round($codeLines / $totalLines, 3) : 0;
    }
    
    private function countCodeLines(string $content): int 
    {
        $lines = explode("\n", $content);
        $codeLines = 0;
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Ignoriere leere Zeilen und reine Kommentare
            if (!empty($trimmed) && 
                !str_starts_with($trimmed, '//') && 
                !str_starts_with($trimmed, '*') &&
                !str_starts_with($trimmed, '/*') &&
                !str_starts_with($trimmed, '#') &&
                !str_starts_with($trimmed, '<?php') &&
                $trimmed !== '<?php') {
                $codeLines++;
            }
        }
        
        return $codeLines;
    }
    
    private function getMethods(array $tokens): array 
    {
        $methods = [];
        $inFunction = false;
        $braceLevel = 0;
        $currentMethod = '';
        
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_FUNCTION) {
                $inFunction = true;
                $currentMethod = '';
                $braceLevel = 0;
            } elseif ($inFunction) {
                if ($token === '{') {
                    $braceLevel++;
                } elseif ($token === '}') {
                    $braceLevel--;
                    if ($braceLevel === 0) {
                        $methods[] = $currentMethod;
                        $inFunction = false;
                    }
                } elseif (is_array($token)) {
                    $currentMethod .= $token[1];
                } else {
                    $currentMethod .= $token;
                }
            }
        }
        
        return $methods;
    }
    
    private function calculateMethodSimilarity(string $method1, string $method2): float 
    {
        // Vereinfachte Ähnlichkeitsberechnung basierend auf gemeinsamen Wörtern
        $words1 = preg_split('/\W+/', strtolower($method1));
        $words2 = preg_split('/\W+/', strtolower($method2));
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        return count($union) > 0 ? count($intersection) / count($union) : 0;
    }
    
    private function countLines(string $content): int 
    {
        return substr_count($content, "\n") + 1;
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
    
    private function calculateOverallScore(array $results): float 
    {
        if (empty($results)) return 0;
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($results as $result) {
            if (isset($result['quality_score'])) {
                $totalScore += $result['quality_score'];
                $count++;
            }
        }
        
        return $count > 0 ? round($totalScore / $count, 2) : 0;
    }
    
    private function findQualityIssues(array $results): array 
    {
        $issues = [];
        
        foreach ($results as $file => $result) {
            if ($result['cyclomatic_complexity'] > $this->qualityThresholds['cyclomatic_complexity_max']) {
                $issues[] = "Hohe Komplexität in {$file}: {$result['cyclomatic_complexity']}";
            }
            
            if ($result['number_of_methods'] > $this->qualityThresholds['methods_per_class_max']) {
                $issues[] = "Zu viele Methoden in {$file}: {$result['number_of_methods']}";
            }
            
            if ($result['nesting_depth'] > $this->qualityThresholds['nesting_depth_max']) {
                $issues[] = "Zu tiefe Verschachtelung in {$file}: {$result['nesting_depth']} Ebenen";
            }
            
            if ($result['comment_ratio'] < $this->qualityThresholds['comment_ratio_min']) {
                $ratio = round($result['comment_ratio'] * 100, 1);
                $issues[] = "Wenige Kommentare in {$file}: {$ratio}%";
            }
        }
        
        return $issues;
    }
    
    private function generateRecommendations(array $results): array 
    {
        $recommendations = [];
        
        foreach ($results as $file => $result) {
            $fileRecommendations = [];
            
            if ($result['cyclomatic_complexity'] > 10) {
                $fileRecommendations[] = "Methoden aufteilen zur Reduzierung der Komplexität";
            }
            
            if ($result['number_of_methods'] > 15) {
                $fileRecommendations[] = "Klasse aufteilen - zu viele Verantwortlichkeiten";
            }
            
            if ($result['coupling_score'] > 0.5) {
                $fileRecommendations[] = "Dependencies reduzieren - zu hohe Kopplung";
            }
            
            if ($result['cohesion_score'] < 0.7) {
                $fileRecommendations[] = "Methoden gruppieren - niedrige Kohäsion";
            }
            
            if (!empty($fileRecommendations)) {
                $recommendations[$file] = $fileRecommendations;
            }
        }
        
        return $recommendations;
    }
}

// Script ausführen wenn direkt aufgerufen
if (isset($argv[0]) && basename($argv[0]) === 'smart-code-analyzer.php') {
    $analyzer = new SmartCodeAnalyzer();
    $result = $analyzer->analyzeDirectory('.');
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
