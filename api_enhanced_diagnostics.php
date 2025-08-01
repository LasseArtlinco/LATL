<?php
// api_enhanced_diagnostics.php - Place this in your root directory
// Detailed API diagnostics tool

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Basic styling
echo '<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL API Udvidet Diagnose</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #042940; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { background: #005C53; color: white; border: none; padding: 8px 15px; border-radius: 3px; cursor: pointer; }
        button:hover { background: #042940; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .test-button { margin: 5px; }
        .response { max-height: 300px; overflow-y: auto; }
        .endpoint-tester { display: flex; margin-bottom: 10px; }
        .endpoint-tester input { flex-grow: 1; padding: 8px; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>LATL API Udvidet Diagnose</h1>
    <p>Dette værktøj hjælper med at diagnosticere problemer med PHP og API\'et.</p>';

// Include required files
try {
    echo '<div class="card">
        <h2>1. Konfigurationsfiler</h2>';
    
    if (file_exists('config.php')) {
        echo '<p class="success">✅ config.php findes</p>';
        require_once 'config.php';
        echo '<p class="success">✅ config.php indlæst korrekt</p>';
        
        // Display configuration values
        echo '<h3>Konfigurationsværdier:</h3>';
        echo '<table>
            <tr><th>Konfiguration</th><th>Værdi</th></tr>
            <tr><td>DB_HOST</td><td>' . DB_HOST . '</td></tr>
            <tr><td>DB_NAME</td><td>' . DB_NAME . '</td></tr>
            <tr><td>DB_USER</td><td>' . DB_USER . '</td></tr>
            <tr><td>DB_CHARSET</td><td>' . DB_CHARSET . '</td></tr>
            <tr><td>BASE_URL</td><td>' . BASE_URL . '</td></tr>
            <tr><td>ROOT_PATH</td><td>' . ROOT_PATH . '</td></tr>
            <tr><td>DEBUG_MODE</td><td>' . (DEBUG_MODE ? 'Aktiveret' : 'Deaktiveret') . '</td></tr>
        </table>';
    } else {
        echo '<p class="error">❌ config.php findes ikke</p>';
    }
    
    if (file_exists('db.php')) {
        echo '<p class="success">✅ db.php findes</p>';
        require_once 'db.php';
        echo '<p class="success">✅ db.php indlæst korrekt</p>';
    } else {
        echo '<p class="error">❌ db.php findes ikke</p>';
    }
    
    echo '</div>';
    
    // Test database connection
    echo '<div class="card">
        <h2>2. Database-forbindelsestest</h2>';
    
    try {
        $db = Database::getInstance();
        echo '<p class="success">✅ Database-forbindelse oprettet succesfuldt</p>';
        
        // Check tables
        $tables = $db->select("SHOW TABLES");
        echo '<p class="success">✅ Fandt ' . count($tables) . ' tabeller i databasen:</p>';
        echo '<ul>';
        foreach ($tables as $table) {
            $tableName = reset($table);
            $count = $db->selectOne("SELECT COUNT(*) as count FROM {$tableName}");
            echo '<li>' . $tableName . ' (' . $count['count'] . ' rækker)</li>';
        }
        echo '</ul>';
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Database-forbindelsesfejl: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // API File Structure
    echo '<div class="card">
        <h2>3. API-filstruktur</h2>';
    
    $apiFiles = [
        'api/index.php',
        'api/bands.php',
        'api/bands_endpoint.php',
        'api/global_styles.php',
        'api/global_styles_endpoint.php',
        'api/layout.php',
        'api/products.php',
        'api/orders.php',
        'api/upload.php',
        'api/image_handler.php',
        'api/.htaccess'
    ];
    
    echo '<table>
        <tr>
            <th>Fil</th>
            <th>Status</th>
            <th>Størrelse</th>
            <th>Sidste ændret</th>
        </tr>';
    
    foreach ($apiFiles as $file) {
        if (file_exists($file)) {
            $size = filesize($file);
            $lastModified = date("Y-m-d H:i:s", filemtime($file));
            echo "<tr>
                <td>{$file}</td>
                <td class=\"success\">Findes</td>
                <td>{$size} bytes</td>
                <td>{$lastModified}</td>
            </tr>";
        } else {
            echo "<tr>
                <td>{$file}</td>
                <td class=\"error\">Mangler</td>
                <td>-</td>
                <td>-</td>
            </tr>";
        }
    }
    
    echo '</table>';
    echo '</div>';
    
    // API Endpoint Testing
    echo '<div class="card">
        <h2>4. API-endpointtest</h2>
        <p>Test de forskellige API-endpoints ved at klikke på knapperne herunder.</p>
        
        <div id="endpoint-results"></div>
        
        <h3>Standard endpoints:</h3>
        <button class="test-button" onclick="testEndpoint(\'api/global_styles\')">Test Global Styles API</button>
        <button class="test-button" onclick="testEndpoint(\'api/layout\')">Test Layout API</button>
        <button class="test-button" onclick="testEndpoint(\'api/bands/forside\')">Test Bands Forside API</button>
        
        <h3>Brugerdefineret endpoint:</h3>
        <div class="endpoint-tester">
            <input type="text" id="custom-endpoint" placeholder="F.eks. api/layout/forside" value="api/">
            <button onclick="testEndpoint(document.getElementById(\'custom-endpoint\').value)">Test</button>
        </div>
    </div>';
    
    // API Log Viewer
    echo '<div class="card">
        <h2>5. API Fejllog</h2>';
    
    $logFile = 'api_errors.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = array_slice(explode("\n", $logContent), -50); // Get last 50 lines
        
        echo '<p class="success">✅ Fejllog findes. Viser de seneste 50 linjer:</p>';
        echo '<pre class="response">' . htmlspecialchars(implode("\n", $logLines)) . '</pre>';
        echo '<button onclick="clearLog()">Ryd log</button>';
    } else {
        echo '<p class="warning">⚠️ Ingen fejllog-fil fundet. Fejllogning kan være deaktiveret eller log-filen er tom.</p>';
    }
    
    echo '</div>';
    
    // JavaScript for API testing
    echo '<script>
    function testEndpoint(endpoint) {
        const resultsDiv = document.getElementById("endpoint-results");
        
        // Add a new result container
        const resultContainer = document.createElement("div");
        resultContainer.className = "card";
        resultContainer.innerHTML = `
            <h3>Test af: ${endpoint}</h3>
            <p>Sender anmodning...</p>
        `;
        resultsDiv.prepend(resultContainer);
        
        // Make the API request
        fetch(endpoint)
            .then(response => {
                const statusText = response.ok ? "success" : "error";
                return response.text().then(text => {
                    let formattedResponse;
                    try {
                        // Try to parse as JSON for pretty display
                        const json = JSON.parse(text);
                        formattedResponse = JSON.stringify(json, null, 2);
                    } catch (e) {
                        // Not JSON, display as is
                        formattedResponse = text;
                    }
                    
                    resultContainer.innerHTML = `
                        <h3>Test af: ${endpoint}</h3>
                        <p class="${statusText}">Status: ${response.status} ${response.statusText}</p>
                        <p>Response:</p>
                        <pre class="response">${formattedResponse}</pre>
                    `;
                });
            })
            .catch(error => {
                resultContainer.innerHTML = `
                    <h3>Test af: ${endpoint}</h3>
                    <p class="error">Fejl: ${error.message}</p>
                `;
            });
    }
    
    function clearLog() {
        fetch("clear_log.php")
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => {
                alert("Fejl ved rydning af log: " + error.message);
            });
    }
    </script>';
    
} catch (Exception $e) {
    echo '<div class="card">
        <h2 class="error">Fejl ved kørsel af diagnosticering</h2>
        <p>' . $e->getMessage() . '</p>
        <pre>' . $e->getTraceAsString() . '</pre>
    </div>';
}

echo '</body></html>';

// Create a log clearer script
$clearLogScript = '<?php
// clear_log.php
$logFile = "api_errors.log";
if (file_exists($logFile)) {
    file_put_contents($logFile, "");
    echo "Log cleared";
} else {
    echo "Log file not found";
}
';

file_put_contents('clear_log.php', $clearLogScript);
