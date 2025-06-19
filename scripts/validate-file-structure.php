#!/usr/bin/env php
<?php
/**
 * File Structure Validator
 * Validiert die Projekt-Struktur nach definierten Regeln und Naming Conventions
 * 
 * @author MarkusLehr ClientGallerie
 * @version 1.0.0
 */

class FileStructureValidator {
    private array $rules = [];
    private array $violations = [];
    private array $namingConventions = [];
    
    public function __construct() {
        $this->setupRules();
        $this->setupNamingConventions();
    }
    
    public function validate(string $directory = '.'): array {
        $this->scanDirectory($directory);
        $this->validateDirectoryStructure($directory);
        $this->validateFilePlacements($directory);
        
        return [
            'analysis_date' => date('Y-m-d H:i:s'),
            'directory' => $directory,
            'violations' => $this->violations,
            'recommendations' => $this->generateRecommendations(),
            'score' => $this->calculateScore()
        ];
    }
    
    private function setupRules(): void {
        $this->rules = [
            'max_depth' => 5, // Maximale Verschachtelungstiefe
            'max_files_per_directory' => 20, // Maximale Dateien pro Verzeichnis
            'require_readme' => true, // README.md in wichtigen Verzeichnissen
            'require_tests' => true, // Test-Dateien für Source-Code
            'single_responsibility_files' => true, // Eine Klasse pro Datei
            'consistent_naming' => true, // Konsistente Naming Conventions
        ];
    }
    
    private function setupNamingConventions(): void {
        $this->namingConventions = [
            'classes' => [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*$/', // PascalCase
                'suffixes' => ['Service', 'Repository', 'Controller', 'Entity', 'ValueObject', 'Factory', 'Builder']
            ],
            'interfaces' => [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*Interface$/', // PascalCase + Interface
                'prefix' => '',
                'suffix' => 'Interface'
            ],
            'traits' => [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*Trait$/', // PascalCase + Trait
                'suffix' => 'Trait'
            ],
            'tests' => [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*Test$/', // PascalCase + Test
                'suffix' => 'Test'
            ],
            'directories' => [
                'pattern' => '/^[a-z][a-z0-9-]*$/', // kebab-case
                'exceptions' => ['.vscode', 'docs', 'scripts', 'tests', 'src', 'vendor']
            ],
            'files' => [
                'php' => '/^[A-Z][a-zA-Z0-9]*\.php$/', // PascalCase.php
                'config' => '/^[a-z][a-z0-9-]*\.(php|json|yaml|yml)$/', // kebab-case
                'docs' => '/^[A-Z][A-Z0-9-]*\.md$/' // UPPERCASE-KEBAB.md
            ]
        ];
    }
    
    private function scanDirectory(string $directory): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $this->validateFile($file);
        }
    }
    
    private function validateFile(SplFileInfo $file): void {
        $path = $file->getPathname();
        $name = $file->getFilename();
        $extension = $file->getExtension();
        
        // Validiere Dateinamen
        if ($file->isFile()) {
            $this->validateFileName($path, $name, $extension);
            
            if ($extension === 'php') {
                $this->validatePhpFile($path);
            }
        } else {
            $this->validateDirectoryName($path, $name);
        }
    }
    
    private function validateFileName(string $path, string $name, string $extension): void {
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        
        switch ($extension) {
            case 'php':
                if (!preg_match($this->namingConventions['files']['php'], $name) && 
                    !str_ends_with($baseName, 'Test') &&
                    !in_array($name, ['index.php', 'functions.php', 'config.php'])) {
                    $this->addViolation('naming', 
                        "PHP-Datei '$name' folgt nicht der PascalCase-Konvention", 
                        $path, 
                        'high'
                    );
                }
                break;
                
            case 'md':
                if (!preg_match($this->namingConventions['files']['docs'], $name) &&
                    !in_array($name, ['README.md', 'CHANGELOG.md', 'LICENSE.md'])) {
                    $this->addViolation('naming',
                        "Dokumentations-Datei '$name' sollte UPPERCASE-KEBAB-CASE verwenden",
                        $path,
                        'low'
                    );
                }
                break;
                
            case 'json':
            case 'yaml':
            case 'yml':
                if (!preg_match($this->namingConventions['files']['config'], $name)) {
                    $this->addViolation('naming',
                        "Konfigurations-Datei '$name' sollte kebab-case verwenden",
                        $path,
                        'medium'
                    );
                }
                break;
        }
    }
    
    private function validateDirectoryName(string $path, string $name): void {
        if (!preg_match($this->namingConventions['directories']['pattern'], $name) &&
            !in_array($name, $this->namingConventions['directories']['exceptions'])) {
            $this->addViolation('naming',
                "Verzeichnis '$name' sollte kebab-case verwenden",
                $path,
                'medium'
            );
        }
    }
    
    private function validatePhpFile(string $path): void {
        $content = file_get_contents($path);
        $tokens = token_get_all($content);
        
        $this->validateSingleResponsibility($path, $tokens);
        $this->validateClassNaming($path, $tokens);
        $this->validateFileClassMatching($path, $tokens);
    }
    
    private function validateSingleResponsibility(string $path, array $tokens): void {
        $classCount = 0;
        $interfaceCount = 0;
        $traitCount = 0;
        
        foreach ($tokens as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_CLASS:
                        $classCount++;
                        break;
                    case T_INTERFACE:
                        $interfaceCount++;
                        break;
                    case T_TRAIT:
                        $traitCount++;
                        break;
                }
            }
        }
        
        $totalTypes = $classCount + $interfaceCount + $traitCount;
        
        if ($totalTypes > 1) {
            $this->addViolation('structure',
                "Datei enthält $totalTypes Typen. Eine Klasse/Interface/Trait pro Datei bevorzugen.",
                $path,
                'high'
            );
        }
        
        if ($totalTypes === 0 && !str_contains($path, 'functions.php') && 
            !str_contains($path, 'config.php') && !str_contains($path, 'index.php')) {
            $this->addViolation('structure',
                "PHP-Datei ohne Klassen/Interfaces/Traits gefunden",
                $path,
                'medium'
            );
        }
    }
    
    private function validateClassNaming(string $path, array $tokens): void {
        $inClass = false;
        $inInterface = false;
        $inTrait = false;
        
        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_CLASS:
                        $inClass = true;
                        break;
                    case T_INTERFACE:
                        $inInterface = true;
                        break;
                    case T_TRAIT:
                        $inTrait = true;
                        break;
                    case T_STRING:
                        if ($inClass) {
                            $this->validateClassName($path, $token[1]);
                            $inClass = false;
                        } elseif ($inInterface) {
                            $this->validateInterfaceName($path, $token[1]);
                            $inInterface = false;
                        } elseif ($inTrait) {
                            $this->validateTraitName($path, $token[1]);
                            $inTrait = false;
                        }
                        break;
                }
            }
        }
    }
    
    private function validateClassName(string $path, string $className): void {
        if (!preg_match($this->namingConventions['classes']['pattern'], $className)) {
            $this->addViolation('naming',
                "Klasse '$className' folgt nicht der PascalCase-Konvention",
                $path,
                'high'
            );
        }
        
        // Prüfe, ob der Klassenname die Verantwortlichkeit beschreibt
        $hasDescriptiveSuffix = false;
        foreach ($this->namingConventions['classes']['suffixes'] as $suffix) {
            if (str_ends_with($className, $suffix)) {
                $hasDescriptiveSuffix = true;
                break;
            }
        }
        
        if (!$hasDescriptiveSuffix && !str_contains($path, 'Entity')) {
            $this->addViolation('naming',
                "Klassenname '$className' sollte die Verantwortlichkeit beschreiben (z.B. Service, Repository, Controller)",
                $path,
                'medium'
            );
        }
    }
    
    private function validateInterfaceName(string $path, string $interfaceName): void {
        if (!preg_match($this->namingConventions['interfaces']['pattern'], $interfaceName)) {
            $this->addViolation('naming',
                "Interface '$interfaceName' sollte mit 'Interface' enden",
                $path,
                'high'
            );
        }
    }
    
    private function validateTraitName(string $path, string $traitName): void {
        if (!preg_match($this->namingConventions['traits']['pattern'], $traitName)) {
            $this->addViolation('naming',
                "Trait '$traitName' sollte mit 'Trait' enden",
                $path,
                'high'
            );
        }
    }
    
    private function validateFileClassMatching(string $path, array $tokens): void {
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        $className = $this->extractMainClassName($tokens);
        
        if ($className && $fileName !== $className) {
            $this->addViolation('structure',
                "Dateiname '$fileName.php' stimmt nicht mit Klassenname '$className' überein",
                $path,
                'high'
            );
        }
    }
    
    private function extractMainClassName(array $tokens): ?string {
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_CLASS) {
                // Suche den nächsten String-Token (Klassenname)
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        return $tokens[$j][1];
                    }
                }
            }
        }
        return null;
    }
    
    private function validateDirectoryStructure(string $directory): void {
        $depth = substr_count($directory, DIRECTORY_SEPARATOR);
        if ($depth > $this->rules['max_depth']) {
            $this->addViolation('structure',
                "Verzeichnis-Verschachtelung zu tief: $depth Ebenen (max: {$this->rules['max_depth']})",
                $directory,
                'medium'
            );
        }
        
        $files = glob($directory . '/*');
        if (count($files) > $this->rules['max_files_per_directory']) {
            $this->addViolation('structure',
                "Zu viele Dateien in Verzeichnis: " . count($files) . " (max: {$this->rules['max_files_per_directory']})",
                $directory,
                'medium'
            );
        }
    }
    
    private function validateFilePlacements(string $directory): void {
        // Prüfe, ob wichtige Dateien an den richtigen Orten sind
        $this->checkForRequiredFiles($directory);
        $this->checkTestCoverage($directory);
    }
    
    private function checkForRequiredFiles(string $directory): void {
        $requiredFiles = ['README.md', 'composer.json', '.gitignore'];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($directory . '/' . $file)) {
                $this->addViolation('structure',
                    "Erforderliche Datei fehlt: $file",
                    $directory,
                    $file === 'README.md' ? 'high' : 'medium'
                );
            }
        }
    }
    
    private function checkTestCoverage(string $directory): void {
        $srcFiles = glob($directory . '/src/**/*.php', GLOB_BRACE);
        $testFiles = glob($directory . '/tests/**/*Test.php', GLOB_BRACE);
        
        if (count($srcFiles) > 0 && count($testFiles) === 0) {
            $this->addViolation('testing',
                "Keine Test-Dateien gefunden für " . count($srcFiles) . " Source-Dateien",
                $directory,
                'high'
            );
        }
        
        $testCoverage = count($srcFiles) > 0 ? (count($testFiles) / count($srcFiles)) * 100 : 0;
        if ($testCoverage < 50) {
            $this->addViolation('testing',
                "Niedrige Test-Abdeckung: " . round($testCoverage, 1) . "%",
                $directory,
                'medium'
            );
        }
    }
    
    private function addViolation(string $category, string $message, string $path, string $severity): void {
        $this->violations[] = [
            'category' => $category,
            'message' => $message,
            'path' => $path,
            'severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function generateRecommendations(): array {
        $recommendations = [];
        $categories = array_count_values(array_column($this->violations, 'category'));
        $severities = array_count_values(array_column($this->violations, 'severity'));
        
        if (isset($categories['naming'])) {
            $recommendations[] = "📝 {$categories['naming']} Naming-Convention-Verstöße gefunden. Konsistente Benennung verbessert Lesbarkeit.";
        }
        
        if (isset($categories['structure'])) {
            $recommendations[] = "🏗️ {$categories['structure']} Struktur-Probleme gefunden. Überprüfen Sie Single Responsibility und Datei-Organisation.";
        }
        
        if (isset($categories['testing'])) {
            $recommendations[] = "🧪 {$categories['testing']} Test-bezogene Probleme gefunden. Erhöhen Sie die Test-Abdeckung.";
        }
        
        if (isset($severities['high'])) {
            $recommendations[] = "🔴 {$severities['high']} hochprioritäre Probleme erfordern sofortige Aufmerksamkeit.";
        }
        
        if (empty($this->violations)) {
            $recommendations[] = "✅ Datei-Struktur folgt allen definierten Regeln und Konventionen!";
        }
        
        return $recommendations;
    }
    
    private function calculateScore(): float {
        if (empty($this->violations)) {
            return 100.0;
        }
        
        $totalPenalty = 0;
        foreach ($this->violations as $violation) {
            switch ($violation['severity']) {
                case 'high':
                    $totalPenalty += 10;
                    break;
                case 'medium':
                    $totalPenalty += 5;
                    break;
                case 'low':
                    $totalPenalty += 2;
                    break;
            }
        }
        
        return max(0, 100 - $totalPenalty);
    }
}

// CLI Ausführung
if (isset($argv[1])) {
    $validator = new FileStructureValidator();
    $result = $validator->validate($argv[1]);
    
    echo "🏗️ File Structure Validation Report\n";
    echo "===================================\n\n";
    echo "📍 Verzeichnis: " . $result['directory'] . "\n";
    echo "📊 Struktur-Score: " . round($result['score'], 1) . "/100\n\n";
    
    if (!empty($result['violations'])) {
        echo "⚠️ Verstöße (" . count($result['violations']) . "):\n";
        
        foreach (['high', 'medium', 'low'] as $severity) {
            $severityViolations = array_filter($result['violations'], 
                fn($v) => $v['severity'] === $severity);
            
            if (!empty($severityViolations)) {
                $icon = $severity === 'high' ? '🔴' : ($severity === 'medium' ? '🟡' : '🟢');
                echo "\n$icon " . strtoupper($severity) . " Priorität:\n";
                
                foreach ($severityViolations as $violation) {
                    echo "  • [{$violation['category']}] {$violation['message']}\n";
                    echo "    📁 {$violation['path']}\n";
                }
            }
        }
        echo "\n";
    }
    
    echo "💡 Empfehlungen:\n";
    foreach ($result['recommendations'] as $recommendation) {
        echo "  $recommendation\n";
    }
    
} else {
    echo "Usage: php validate-file-structure.php <directory>\n";
    echo "Example: php validate-file-structure.php .\n";
    exit(1);
}
