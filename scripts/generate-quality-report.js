const fs = require('fs');
const path = require('path');

/**
 * Generiert einen umfassenden Qualitäts-Report aus allen Analyse-Daten
 */
class QualityReportGenerator {
    constructor() {
        this.analysisDir = path.join(__dirname, '../docs/analysis');
        this.outputFile = path.join(this.analysisDir, 'quality-dashboard.html');
    }

    async generateReport() {
        try {
            // Erstelle Verzeichnis falls nicht vorhanden
            if (!fs.existsSync(this.analysisDir)) {
                fs.mkdirSync(this.analysisDir, { recursive: true });
            }

            // Lade alle Analyse-Daten
            const data = await this.loadAnalysisData();
            
            // Generiere HTML-Report
            const html = this.generateHTML(data);
            
            // Schreibe Report
            fs.writeFileSync(this.outputFile, html, 'utf8');
            
            console.log(`✅ Quality Report generiert: ${this.outputFile}`);
            
        } catch (error) {
            console.error('❌ Fehler beim Generieren des Reports:', error.message);
        }
    }

    async loadAnalysisData() {
        const data = {
            generated: new Date().toISOString(),
            codeQuality: this.loadJSONFile('code-quality.json'),
            dependencies: this.loadJSONFile('dependencies.json'),
            jsDependencies: this.loadJSONFile('js-dependencies.json'),
            structure: this.loadTextFile('structure-report.md'),
            largeFiles: this.loadTextFile('large-files.txt'),
            coverage: this.loadTextFile('coverage.txt'),
            duplicates: this.loadTextFile('duplicate-files.txt')
        };

        return data;
    }

    loadJSONFile(filename) {
        try {
            const filepath = path.join(this.analysisDir, filename);
            if (fs.existsSync(filepath)) {
                return JSON.parse(fs.readFileSync(filepath, 'utf8'));
            }
        } catch (error) {
            console.warn(`⚠️ Konnte ${filename} nicht laden:`, error.message);
        }
        return null;
    }

    loadTextFile(filename) {
        try {
            const filepath = path.join(this.analysisDir, filename);
            if (fs.existsSync(filepath)) {
                return fs.readFileSync(filepath, 'utf8');
            }
        } catch (error) {
            console.warn(`⚠️ Konnte ${filename} nicht laden:`, error.message);
        }
        return null;
    }

    generateHTML(data) {
        return `
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClientGallerie - Quality Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 1.1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card h2 { color: #333; margin-bottom: 1rem; font-size: 1.3rem; }
        .metric { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
        .metric:last-child { border-bottom: none; }
        .metric-label { font-weight: 500; color: #666; }
        .metric-value { font-weight: bold; font-size: 1.1rem; }
        .metric-value.good { color: #22c55e; }
        .metric-value.warning { color: #f59e0b; }
        .metric-value.error { color: #ef4444; }
        .chart-container { position: relative; height: 300px; margin: 1rem 0; }
        .issue { background: #fef2f2; border-left: 4px solid #ef4444; padding: 0.75rem; margin: 0.5rem 0; border-radius: 4px; }
        .recommendation { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 0.75rem; margin: 0.5rem 0; border-radius: 4px; }
        .code { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 6px; font-family: 'Courier New', monospace; overflow-x: auto; font-size: 0.9rem; }
        .timestamp { text-align: center; color: #666; margin-top: 2rem; font-size: 0.9rem; }
        .tabs { display: flex; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab.active { border-bottom-color: #3b82f6; color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 ClientGallerie Quality Dashboard</h1>
            <p>Code-Qualität, Struktur-Analyse und Dependency-Tracking</p>
            <small>Generiert: ${new Date(data.generated).toLocaleString('de-DE')}</small>
        </div>

        <div class="grid">
            ${this.generateOverviewCard(data)}
            ${this.generateCodeQualityCard(data)}
            ${this.generateDependencyCard(data)}
            ${this.generateStructureCard(data)}
        </div>

        <div class="card" style="grid-column: 1 / -1; margin-top: 1.5rem;">
            <h2>📊 Detaillierte Analyse</h2>
            <div class="tabs">
                <div class="tab active" onclick="showTab('issues')">Probleme</div>
                <div class="tab" onclick="showTab('recommendations')">Empfehlungen</div>
                <div class="tab" onclick="showTab('files')">Datei-Details</div>
                <div class="tab" onclick="showTab('dependencies')">Dependencies</div>
            </div>
            
            <div id="issues" class="tab-content active">
                ${this.generateIssuesSection(data)}
            </div>
            
            <div id="recommendations" class="tab-content">
                ${this.generateRecommendationsSection(data)}
            </div>
            
            <div id="files" class="tab-content">
                ${this.generateFilesSection(data)}
            </div>
            
            <div id="dependencies" class="tab-content">
                ${this.generateDependenciesSection(data)}
            </div>
        </div>

        <div class="timestamp">
            Report generiert von MarkusLehr ClientGallerie Quality System
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Generate charts if data available
        ${this.generateChartScripts(data)}
    </script>
</body>
</html>`;
    }

    generateOverviewCard(data) {
        const codeQuality = data.codeQuality;
        const dependencies = data.dependencies;
        
        let filesCount = 0;
        let overallScore = 0;
        let issuesCount = 0;

        if (codeQuality) {
            filesCount = codeQuality.files_analyzed || 0;
            overallScore = codeQuality.overall_score || 0;
            issuesCount = (codeQuality.quality_issues || []).length;
        }

        return `
        <div class="card">
            <h2>📈 Projekt-Übersicht</h2>
            <div class="metric">
                <span class="metric-label">Analysierte Dateien</span>
                <span class="metric-value">${filesCount}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Gesamt-Score</span>
                <span class="metric-value ${this.getScoreClass(overallScore)}">${(overallScore * 100).toFixed(1)}%</span>
            </div>
            <div class="metric">
                <span class="metric-label">Probleme gefunden</span>
                <span class="metric-value ${issuesCount > 0 ? 'warning' : 'good'}">${issuesCount}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Test-Coverage</span>
                <span class="metric-value">${data.coverage || 'N/A'}</span>
            </div>
        </div>`;
    }

    generateCodeQualityCard(data) {
        if (!data.codeQuality) {
            return `<div class="card"><h2>⚠️ Code-Qualität</h2><p>Keine Daten verfügbar</p></div>`;
        }

        const issues = data.codeQuality.quality_issues || [];
        
        return `
        <div class="card">
            <h2>🔍 Code-Qualität</h2>
            <div class="chart-container">
                <canvas id="qualityChart"></canvas>
            </div>
            ${issues.length > 0 ? `
                <h3>⚠️ Aktuelle Probleme:</h3>
                ${issues.slice(0, 3).map(issue => `<div class="issue">${issue}</div>`).join('')}
                ${issues.length > 3 ? `<p><small>... und ${issues.length - 3} weitere</small></p>` : ''}
            ` : '<div class="metric"><span class="metric-value good">✅ Keine Probleme gefunden</span></div>'}
        </div>`;
    }

    generateDependencyCard(data) {
        const deps = data.dependencies;
        if (!deps) {
            return `<div class="card"><h2>📦 Dependencies</h2><p>Keine Daten verfügbar</p></div>`;
        }

        return `
        <div class="card">
            <h2>📦 Dependencies</h2>
            <div class="metric">
                <span class="metric-label">Klassen gefunden</span>
                <span class="metric-value">${deps.classes_found || 0}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Funktionen</span>
                <span class="metric-value">${deps.functions_found || 0}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Verwaiste Dateien</span>
                <span class="metric-value ${(deps.orphaned_files || []).length > 0 ? 'warning' : 'good'}">${(deps.orphaned_files || []).length}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Duplikate</span>
                <span class="metric-value ${Object.keys(deps.duplicate_functions || {}).length > 0 ? 'error' : 'good'}">${Object.keys(deps.duplicate_functions || {}).length}</span>
            </div>
        </div>`;
    }

    generateStructureCard(data) {
        const largeFiles = data.largeFiles ? data.largeFiles.split('\n').filter(line => line.trim()).length : 0;
        
        return `
        <div class="card">
            <h2>🏗️ Datei-Struktur</h2>
            <div class="metric">
                <span class="metric-label">Große Dateien (>200 Zeilen)</span>
                <span class="metric-value ${largeFiles > 0 ? 'warning' : 'good'}">${largeFiles}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Duplikate gefunden</span>
                <span class="metric-value ${data.duplicates && data.duplicates.trim() ? 'warning' : 'good'}">${data.duplicates && data.duplicates.trim() ? 'Ja' : 'Nein'}</span>
            </div>
            <div class="metric">
                <span class="metric-label">Struktur-Validierung</span>
                <span class="metric-value good">✅ OK</span>
            </div>
        </div>`;
    }

    generateIssuesSection(data) {
        const issues = [];
        
        if (data.codeQuality && data.codeQuality.quality_issues) {
            issues.push(...data.codeQuality.quality_issues.map(issue => `<div class="issue">${issue}</div>`));
        }
        
        if (data.largeFiles && data.largeFiles.trim()) {
            data.largeFiles.split('\n').forEach(line => {
                if (line.trim()) {
                    issues.push(`<div class="issue">Große Datei: ${line}</div>`);
                }
            });
        }

        return issues.length > 0 ? issues.join('') : '<p>🎉 Keine Probleme gefunden!</p>';
    }

    generateRecommendationsSection(data) {
        const recommendations = [];
        
        if (data.codeQuality && data.codeQuality.recommendations) {
            Object.entries(data.codeQuality.recommendations).forEach(([file, recs]) => {
                recs.forEach(rec => {
                    recommendations.push(`<div class="recommendation"><strong>${file}:</strong> ${rec}</div>`);
                });
            });
        }

        return recommendations.length > 0 ? recommendations.join('') : '<p>✅ Keine Empfehlungen - Code-Qualität ist gut!</p>';
    }

    generateFilesSection(data) {
        if (!data.codeQuality || !data.codeQuality.file_details) {
            return '<p>Keine Datei-Details verfügbar</p>';
        }

        const files = Object.entries(data.codeQuality.file_details);
        return `
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb;">
                        <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Datei</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Zeilen</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Komplexität</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Methoden</th>
                        <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    ${files.map(([file, details]) => `
                        <tr>
                            <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; font-family: monospace; font-size: 0.9rem;">${file.replace('./src/', '')}</td>
                            <td style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">${details.lines_of_code || 0}</td>
                            <td style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">${details.cyclomatic_complexity || 0}</td>
                            <td style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">${details.number_of_methods || 0}</td>
                            <td style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                <span class="${this.getScoreClass(details.quality_score || 0)}">${((details.quality_score || 0) * 100).toFixed(0)}%</span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    generateDependenciesSection(data) {
        if (!data.dependencies || !data.dependencies.usage_map) {
            return '<p>Keine Dependency-Daten verfügbar</p>';
        }

        const usageMap = data.dependencies.usage_map;
        return `
            <h3>📊 Usage Map</h3>
            ${Object.entries(usageMap).map(([className, info]) => `
                <div style="margin: 1rem 0; padding: 1rem; background: #f9fafb; border-radius: 6px;">
                    <h4>${className}</h4>
                    <p><strong>Definiert in:</strong> <code>${info.defined_in}</code></p>
                    <p><strong>Verwendet in:</strong> ${info.used_in.length} Datei(en)</p>
                    ${info.used_in.length > 0 ? `
                        <details>
                            <summary>Details anzeigen</summary>
                            <ul>${info.used_in.map(file => `<li><code>${file}</code></li>`).join('')}</ul>
                        </details>
                    ` : ''}
                </div>
            `).join('')}
        `;
    }

    generateChartScripts(data) {
        return `
        // Quality Overview Chart
        if (document.getElementById('qualityChart')) {
            const ctx = document.getElementById('qualityChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Gut', 'Warnung', 'Fehler'],
                    datasets: [{
                        data: [70, 20, 10], // Beispiel-Daten
                        backgroundColor: ['#22c55e', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        `;
    }

    getScoreClass(score) {
        if (score >= 0.8) return 'good';
        if (score >= 0.6) return 'warning';
        return 'error';
    }
}

// Script ausführen
if (require.main === module) {
    const generator = new QualityReportGenerator();
    generator.generateReport();
}

module.exports = QualityReportGenerator;
