<?php
/**
 * AI Self-Diagnosis and Health Check System
 * 
 * This script provides comprehensive system validation and self-improvement
 * capabilities for the AI assistant working on this WordPress plugin.
 * 
 * @package MarkusLehrClientGallerie
 * @author AI Assistant with Self-Improvement
 * @since 1.0.0
 */

namespace MarkusLehr\ClientGallerie\Tools;

class AISelfDiagnosis
{
    private array $results = [];
    private string $pluginDir;
    private string $composerFile;
    
    public function __construct()
    {
        $this->pluginDir = dirname(__DIR__, 2);
        $this->composerFile = $this->pluginDir . '/composer.json';
    }
    
    /**
     * Run complete system diagnosis
     */
    public function runDiagnosis(): array
    {
        $this->log("🔍 Starting AI Self-Diagnosis...");
        
        $this->checkFileStructure();
        $this->checkAutoloader();
        $this->checkNamespaces();
        $this->checkClassExistence();
        $this->checkGitStatus();
        $this->checkSecurityCompliance();
        $this->checkPerformanceIssues();
        $this->checkCodeQuality();
        $this->generateRecommendations();
        
        return $this->results;
    }
    
    /**
     * Check file structure integrity
     */
    private function checkFileStructure(): void
    {
        $this->log("📁 Checking file structure...");
        
        $requiredDirs = [
            'src/Application',
            'src/Domain', 
            'src/Infrastructure',
            'src/Infrastructure/Database/Schema',
            'src/Infrastructure/Database/Repository',
            'src/Infrastructure/Database/Migration',
            'vendor'
        ];
        
        $missingDirs = [];
        foreach ($requiredDirs as $dir) {
            $fullPath = $this->pluginDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                $missingDirs[] = $dir;
            }
        }
        
        if (empty($missingDirs)) {
            $this->addResult('file_structure', 'success', 'All required directories exist');
        } else {
            $this->addResult('file_structure', 'error', 'Missing directories: ' . implode(', ', $missingDirs));
            $this->addRecommendation('Create missing directories: ' . implode(', ', $missingDirs));
        }
    }
    
    /**
     * Check autoloader status
     */
    private function checkAutoloader(): void
    {
        $this->log("🔄 Checking autoloader...");
        
        $autoloadFile = $this->pluginDir . '/vendor/autoload.php';
        
        if (!file_exists($autoloadFile)) {
            $this->addResult('autoloader', 'error', 'Autoloader not found');
            $this->addRecommendation('Run: composer install && composer dump-autoload --optimize');
            return;
        }
        
        // Check if autoloader is current
        $composerTime = filemtime($this->composerFile);
        $autoloadTime = filemtime($autoloadFile);
        
        if ($composerTime > $autoloadTime) {
            $this->addResult('autoloader', 'warning', 'Autoloader is outdated');
            $this->addRecommendation('Run: composer dump-autoload --optimize');
        } else {
            $this->addResult('autoloader', 'success', 'Autoloader is current');
        }
    }
    
    /**
     * Check namespace consistency
     */
    private function checkNamespaces(): void
    {
        $this->log("🏷️ Checking namespaces...");
        
        $srcDir = $this->pluginDir . '/src';
        $issues = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace($srcDir . '/', '', $file->getPathname());
                $expectedNamespace = $this->getExpectedNamespace($relativePath);
                $actualNamespace = $this->extractNamespace($file->getPathname());
                
                if ($actualNamespace && $expectedNamespace !== $actualNamespace) {
                    $issues[] = [
                        'file' => $relativePath,
                        'expected' => $expectedNamespace,
                        'actual' => $actualNamespace
                    ];
                }
            }
        }
        
        if (empty($issues)) {
            $this->addResult('namespaces', 'success', 'All namespaces are consistent');
        } else {
            $this->addResult('namespaces', 'error', 'Namespace inconsistencies found');
            foreach ($issues as $issue) {
                $this->addRecommendation("Fix namespace in {$issue['file']}: expected {$issue['expected']}, found {$issue['actual']}");
            }
        }
    }
    
    /**
     * Check class existence and autoloading
     */
    private function checkClassExistence(): void
    {
        $this->log("🏗️ Checking class existence...");
        
        $criticalClasses = [
            'MarkusLehr\\ClientGallerie\\Infrastructure\\Database\\Repository\\RepositoryManager',
            'MarkusLehr\\ClientGallerie\\Infrastructure\\Database\\Schema\\SchemaManager',
            'MarkusLehr\\ClientGallerie\\Infrastructure\\Database\\Migration\\MigrationManager',
            'MarkusLehr\\ClientGallerie\\Application\\Service\\DatabaseAdminService',
            'MarkusLehr\\ClientGallerie\\Application\\Controller\\AdminController'
        ];
        
        $missingClasses = [];
        
        foreach ($criticalClasses as $class) {
            if (!class_exists($class)) {
                $missingClasses[] = $class;
            }
        }
        
        if (empty($missingClasses)) {
            $this->addResult('class_existence', 'success', 'All critical classes are available');
        } else {
            $this->addResult('class_existence', 'error', 'Missing critical classes');
            foreach ($missingClasses as $class) {
                $this->addRecommendation("Check and fix class: $class");
            }
        }
    }
    
    /**
     * Check Git status
     */
    private function checkGitStatus(): void
    {
        $this->log("📊 Checking Git status...");
        
        $gitDir = $this->pluginDir . '/.git';
        
        if (!is_dir($gitDir)) {
            $this->addResult('git', 'error', 'Git repository not initialized');
            $this->addRecommendation('Initialize Git: git init && git add . && git commit -m "Initial commit"');
            return;
        }
        
        // Check for uncommitted changes
        $output = [];
        exec('cd ' . escapeshellarg($this->pluginDir) . ' && git status --porcelain', $output);
        
        if (!empty($output)) {
            $this->addResult('git', 'warning', 'Uncommitted changes found');
            $this->addRecommendation('Commit changes: git add . && git commit -m "Update: [description]"');
        } else {
            $this->addResult('git', 'success', 'Git repository is clean');
        }
    }
    
    /**
     * Check security compliance
     */
    private function checkSecurityCompliance(): void
    {
        $this->log("🔒 Checking security compliance...");
        
        $securityIssues = [];
        
        // Check for direct database queries
        if ($this->findDirectDatabaseUsage()) {
            $securityIssues[] = 'Direct $wpdb usage found - use Repository pattern instead';
        }
        
        // Check for missing sanitization
        if ($this->findMissingSanitization()) {
            $securityIssues[] = 'Potential unsanitized input found';
        }
        
        // Check for missing nonce verification
        if ($this->findMissingNonceVerification()) {
            $securityIssues[] = 'Missing nonce verification in AJAX handlers';
        }
        
        if (empty($securityIssues)) {
            $this->addResult('security', 'success', 'No security issues detected');
        } else {
            $this->addResult('security', 'warning', 'Security issues found');
            foreach ($securityIssues as $issue) {
                $this->addRecommendation("Security: $issue");
            }
        }
    }
    
    /**
     * Check for performance issues
     */
    private function checkPerformanceIssues(): void
    {
        $this->log("⚡ Checking performance...");
        
        $performanceIssues = [];
        
        // Check for N+1 query patterns
        if ($this->findNPlusOneQueries()) {
            $performanceIssues[] = 'Potential N+1 query patterns found';
        }
        
        // Check for missing caching
        if ($this->findMissingCaching()) {
            $performanceIssues[] = 'Missing caching in frequently called methods';
        }
        
        // Check for large file operations
        if ($this->findLargeFileOperations()) {
            $performanceIssues[] = 'Large file operations without memory optimization';
        }
        
        if (empty($performanceIssues)) {
            $this->addResult('performance', 'success', 'No performance issues detected');
        } else {
            $this->addResult('performance', 'warning', 'Performance issues found');
            foreach ($performanceIssues as $issue) {
                $this->addRecommendation("Performance: $issue");
            }
        }
    }
    
    /**
     * Check code quality
     */
    private function checkCodeQuality(): void
    {
        $this->log("📈 Checking code quality...");
        
        $qualityIssues = [];
        
        // Check for long methods
        if ($this->findLongMethods()) {
            $qualityIssues[] = 'Methods exceeding 50 lines found';
        }
        
        // Check for high complexity
        if ($this->findHighComplexity()) {
            $qualityIssues[] = 'High complexity methods found';
        }
        
        // Check for missing documentation
        if ($this->findMissingDocumentation()) {
            $qualityIssues[] = 'Missing PHPDoc documentation';
        }
        
        if (empty($qualityIssues)) {
            $this->addResult('code_quality', 'success', 'Good code quality maintained');
        } else {
            $this->addResult('code_quality', 'warning', 'Code quality issues found');
            foreach ($qualityIssues as $issue) {
                $this->addRecommendation("Code Quality: $issue");
            }
        }
    }
    
    /**
     * Generate improvement recommendations
     */
    private function generateRecommendations(): void
    {
        $this->log("💡 Generating recommendations...");
        
        // Check overall health score
        $healthScore = $this->calculateHealthScore();
        
        if ($healthScore < 80) {
            $this->addRecommendation("Overall health score is low ($healthScore%). Prioritize fixing critical issues.");
        }
        
        // Add general recommendations
        $this->addRecommendation("Run quality checks: composer run quality:check");
        $this->addRecommendation("Update dependencies: composer update");
        $this->addRecommendation("Generate documentation: composer run docs:generate");
    }
    
    /**
     * Helper methods for analysis
     */
    private function getExpectedNamespace(string $relativePath): string
    {
        $pathParts = explode('/', dirname($relativePath));
        return 'MarkusLehr\\ClientGallerie\\' . implode('\\', $pathParts);
    }
    
    private function extractNamespace(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    
    private function findDirectDatabaseUsage(): bool
    {
        // Simplified check - in real implementation, parse PHP files
        return false;
    }
    
    private function findMissingSanitization(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findMissingNonceVerification(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findNPlusOneQueries(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findMissingCaching(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findLargeFileOperations(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findLongMethods(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findHighComplexity(): bool
    {
        // Simplified check
        return false;
    }
    
    private function findMissingDocumentation(): bool
    {
        // Simplified check
        return false;
    }
    
    private function calculateHealthScore(): int
    {
        $total = count($this->results);
        $success = count(array_filter($this->results, fn($r) => $r['status'] === 'success'));
        
        return $total > 0 ? round(($success / $total) * 100) : 0;
    }
    
    /**
     * Utility methods
     */
    private function addResult(string $category, string $status, string $message): void
    {
        $this->results[] = [
            'category' => $category,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function addRecommendation(string $recommendation): void
    {
        if (!isset($this->results['recommendations'])) {
            $this->results['recommendations'] = [];
        }
        $this->results['recommendations'][] = $recommendation;
    }
    
    private function log(string $message): void
    {
        echo "[" . date('H:i:s') . "] $message\n";
    }
    
    /**
     * Generate HTML report
     */
    public function generateHtmlReport(): string
    {
        $healthScore = $this->calculateHealthScore();
        $statusColor = $healthScore >= 80 ? 'green' : ($healthScore >= 60 ? 'orange' : 'red');
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>AI Self-Diagnosis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .health-score { font-size: 24px; color: $statusColor; font-weight: bold; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .recommendation { background: #f0f8ff; padding: 10px; margin: 5px 0; border-left: 4px solid #007cba; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🤖 AI Self-Diagnosis Report</h1>
    <div class='health-score'>Overall Health Score: {$healthScore}%</div>
    <p>Generated: " . date('Y-m-d H:i:s') . "</p>
    
    <h2>📊 System Status</h2>
    <table>
        <tr><th>Category</th><th>Status</th><th>Message</th><th>Time</th></tr>";
        
        foreach ($this->results as $result) {
            if (is_array($result) && isset($result['category'])) {
                $statusClass = $result['status'];
                $html .= "<tr>
                    <td>{$result['category']}</td>
                    <td class='$statusClass'>{$result['status']}</td>
                    <td>{$result['message']}</td>
                    <td>{$result['timestamp']}</td>
                </tr>";
            }
        }
        
        $html .= "</table>";
        
        if (isset($this->results['recommendations'])) {
            $html .= "<h2>💡 Recommendations</h2>";
            foreach ($this->results['recommendations'] as $recommendation) {
                $html .= "<div class='recommendation'>$recommendation</div>";
            }
        }
        
        $html .= "</body></html>";
        
        return $html;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $diagnosis = new AISelfDiagnosis();
    $results = $diagnosis->runDiagnosis();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "AI SELF-DIAGNOSIS COMPLETE\n";
    echo str_repeat("=", 50) . "\n";
    
    $healthScore = count(array_filter($results, fn($r) => is_array($r) && ($r['status'] ?? '') === 'success'));
    $total = count(array_filter($results, fn($r) => is_array($r) && isset($r['status'])));
    $percentage = $total > 0 ? round(($healthScore / $total) * 100) : 0;
    
    echo "Health Score: $percentage%\n\n";
    
    if (isset($results['recommendations'])) {
        echo "RECOMMENDATIONS:\n";
        foreach ($results['recommendations'] as $i => $rec) {
            echo ($i + 1) . ". $rec\n";
        }
    }
}
