<?php
// debug.php - Diagnosticeringsværktøj for PHP og API
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Titel og styling
echo '<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL PHP Diagnostics</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            margin-top: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 400px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        .info {
            color: #17a2b8;
        }
        section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .test-api {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        .test-api:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>LATL PHP & API Diagnostics</h1>
    <p>Dette værktøj hjælper med at diagnosticere problemer med PHP og API\'et.</p>';

// PHP-information
echo '<section>
    <h2>1. PHP Information</h2>
    <table>
        <tr>
            <th>Information</th>
            <th>Værdi</th>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td>' . phpversion() . '</td>
        </tr>
        <tr>
            <td>Server Software</td>
            <td>' . $_SERVER['SERVER_SOFTWARE'] . '</td>
        </tr>
        <tr>
            <td>Document Root</td>
            <td>' . $_SERVER['DOCUMENT_ROOT'] . '</td>
        </tr>
        <tr>
            <td>Request URI</td>
            <td>' . $_SERVER['REQUEST_URI'] . '</td>
        </tr>
        <tr>
            <td>PDO MySQL</td>
            <td>' . (extension_loaded('pdo_mysql') ? '<span class="success">Aktiveret</span>' : '<span class="error">Ikke aktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>JSON</td>
            <td>' . (extension_loaded('json') ? '<span class="success">Aktiveret</span>' : '<span class="error">Ikke aktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>cURL</td>
            <td>' . (extension_loaded('curl') ? '<span class="success">Aktiveret</span>' : '<span class="error">Ikke aktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>GD (for billedbehandling)</td>
            <td>' . (extension_loaded('gd') ? '<span class="success">Aktiveret</span>' : '<span class="error">Ikke aktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>File Uploads</td>
            <td>' . (ini_get('file_uploads') ? '<span class="success">Aktiveret</span>' : '<span class="error">Ikke aktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>Upload Max Filesize</td>
            <td>' . ini_get('upload_max_filesize') . '</td>
        </tr>
        <tr>
            <td>Post Max Size</td>
            <td>' . ini_get('post_max_size') . '</td>
        </tr>
        <tr>
            <td>Memory Limit</td>
            <td>' . ini_get('memory_limit') . '</td>
        </tr>
        <tr>
            <td>Max Execution Time</td>
            <td>' . ini_get('max_execution_time') . ' sekunder</td>
        </tr>
        <tr>
            <td>Display Errors</td>
            <td>' . (ini_get('display_errors') ? '<span class="success">Aktiveret</span>' : '<span class="warning">Deaktiveret</span>') . '</td>
        </tr>
        <tr>
            <td>Error Reporting</td>
            <td>' . ini_get('error_reporting') . '</td>
        </tr>
    </table>
</section>';

// Konfigurationskontrol
echo '<section>
    <h2>2. Konfigurationskontrol</h2>';

// Tjek om config.php eksisterer og kan indlæses
$configExists = file_exists('config.php');
echo '<p>config.php: ' . ($configExists ? '<span class="success">Fundet</span>' : '<span class="error">Ikke fundet</span>') . '</p>';

if ($configExists) {
    try {
        require_once 'config.php';
        echo '<p>Indlæsning af config.php: <span class="success">Succesfuld</span></p>';
        
        // Vis konfigurationsvariabler
        echo '<table>
            <tr>
                <th>Konfiguration</th>
                <th>Værdi</th>
            </tr>';
        
        // Database-konfiguration
        echo '<tr>
            <td>DB_HOST</td>
            <td>' . (defined('DB_HOST') ? DB_HOST : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        echo '<tr>
            <td>DB_NAME</td>
            <td>' . (defined('DB_NAME') ? DB_NAME : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        echo '<tr>
            <td>DB_USER</td>
            <td>' . (defined('DB_USER') ? DB_USER : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        echo '<tr>
            <td>DB_CHARSET</td>
            <td>' . (defined('DB_CHARSET') ? DB_CHARSET : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        
        // Andre konfigurationsvariabler
        echo '<tr>
            <td>BASE_URL</td>
            <td>' . (defined('BASE_URL') ? BASE_URL : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        echo '<tr>
            <td>ROOT_PATH</td>
            <td>' . (defined('ROOT_PATH') ? ROOT_PATH : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        echo '<tr>
            <td>DEBUG_MODE</td>
            <td>' . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'Aktiveret' : 'Deaktiveret') : '<span class="error">Ikke defineret</span>') . '</td>
        </tr>';
        
        echo '</table>';
    } catch (Exception $e) {
        echo '<p class="error">Fejl ved indlæsning af config.php: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="error">config.php blev ikke fundet. Kontroller, at filen eksisterer i rodmappen.</p>';
}

echo '</section>';

// Database-forbindelsestest
echo '<section>
    <h2>3. Database-forbindelsestest</h2>';

$dbExists = file_exists('db.php');
echo '<p>db.php: ' . ($dbExists ? '<span class="success">Fundet</span>' : '<span class="error">Ikke fundet</span>') . '</p>';

if ($dbExists && $configExists) {
    try {
        require_once 'db.php';
        $db = Database::getInstance();
        echo '<p>Database-forbindelse: <span class="success">Succesfuld</span></p>';
        
        // Vis tabeller
        $tables = $db->select("SHOW TABLES");
        
        if (!empty($tables)) {
            echo '<p>Fandt ' . count($tables) . ' tabeller i databasen:</p>';
            echo '<ul>';
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                echo '<li>' . $tableName;
                
                // Vis antal rækker i tabellen
                $count = $db->selectOne("SELECT COUNT(*) as count FROM $tableName");
                $rowCount = $count['count'] ?? 0;
                echo ' (' . $rowCount . ' rækker)';
                
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p class="warning">Ingen tabeller fundet i databasen.</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">Fejl ved forbindelse til database: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="error">db.php og/eller config.php blev ikke fundet. Kan ikke teste databaseforbindelse.</p>';
}

echo '</section>';

// API-test
echo '<section>
    <h2>4. API Test</h2>
    <p>Klik på knapperne nedenfor for at teste forskellige API-endpoints.</p>
    
    <div>
        <h3>Global Styles API</h3>
        <button class="test-api" data-endpoint="layout/global/styles">Test Global Styles API</button>
        <div id="global-styles-result" class="api-result"></div>
    </div>
    
    <div>
        <h3>Layout API</h3>
        <button class="test-api" data-endpoint="layout">Test Layout API</button>
        <div id="layout-result" class="api-result"></div>
    </div>
    
    <div>
        <h3>Layout Forside API</h3>
        <button class="test-api" data-endpoint="layout/forside">Test Layout Forside API</button>
        <div id="layout-forside-result" class="api-result"></div>
    </div>
    
    <div>
        <h3>Bands API</h3>
        <button class="test-api" data-endpoint="bands/forside">Test Bands API</button>
        <div id="bands-result" class="api-result"></div>
    </div>
    
    <script>
        document.querySelectorAll(".test-api").forEach(button => {
            button.addEventListener("click", async () => {
                const endpoint = button.dataset.endpoint;
                const resultId = endpoint.replace(/[\//]/g, "-") + "-result";
                const resultElement = document.getElementById(button.parentElement.querySelector(".api-result").id);
                
                resultElement.innerHTML = "<p>Tester API-endpoint: " + endpoint + "...</p>";
                
                try {
                    const response = await fetch("/api/" + endpoint);
                    const status = response.status;
                    
                    let resultHtml = "<p>Status: " + status + "</p>";
                    
                    if (response.ok) {
                        try {
                            const data = await response.json();
                            resultHtml += "<p>Response JSON:</p><pre>" + JSON.stringify(data, null, 2) + "</pre>";
                        } catch (e) {
                            const text = await response.text();
                            resultHtml += "<p class=\'error\'>Fejl ved parsing af JSON:</p><pre>" + text + "</pre>";
                        }
                    } else {
                        resultHtml += "<p class=\'error\'>Endpoint fejlede med status " + status + "</p>";
                        try {
                            const text = await response.text();
                            resultHtml += "<pre>" + text + "</pre>";
                        } catch (e) {
                            resultHtml += "<p>Kunne ikke læse response body</p>";
                        }
                    }
                    
                    resultElement.innerHTML = resultHtml;
                } catch (error) {
                    resultElement.innerHTML = "<p class=\'error\'>Fejl ved test af endpoint: " + error.message + "</p>";
                }
            });
        });
    </script>
</section>';

// Server-filstruktur
echo '<section>
    <h2>5. Server-filstruktur</h2>';

// Funktioner til at vise mappestruktur
function getDirectoryStructure($path, $maxDepth = 3, $currentDepth = 0) {
    if ($currentDepth >= $maxDepth) {
        return '...';
    }
    
    $result = '';
    $items = scandir($path);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $fullPath = $path . '/' . $item;
        $isDir = is_dir($fullPath);
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $currentDepth);
        
        $result .= '<div>' . $indent . ($isDir ? '📁 ' : '📄 ') . $item;
        
        if ($isDir) {
            $result .= getDirectoryStructure($fullPath, $maxDepth, $currentDepth + 1);
        }
        
        $result .= '</div>';
    }
    
    return $result;
}

// Vis filstruktur for api-mappen
if (is_dir('api')) {
    echo '<h3>API-mappe</h3>';
    echo '<div style="font-family: monospace;">';
    echo getDirectoryStructure('api', 2);
    echo '</div>';
} else {
    echo '<p class="error">API-mappen blev ikke fundet.</p>';
}

// Vis filstruktur for admin-mappen
if (is_dir('admin')) {
    echo '<h3>Admin-mappe</h3>';
    echo '<div style="font-family: monospace;">';
    echo getDirectoryStructure('admin', 2);
    echo '</div>';
} else {
    echo '<p class="error">Admin-mappen blev ikke fundet.</p>';
}

echo '</section>';

// .htaccess-filer
echo '<section>
    <h2>6. .htaccess-filer</h2>';

// Kontroller rod .htaccess
if (file_exists('.htaccess')) {
    echo '<h3>Rod .htaccess</h3>';
    echo '<pre>' . htmlspecialchars(file_get_contents('.htaccess')) . '</pre>';
} else {
    echo '<p class="error">Rod .htaccess findes ikke.</p>';
}

// Kontroller API .htaccess
if (file_exists('api/.htaccess')) {
    echo '<h3>API .htaccess</h3>';
    echo '<pre>' . htmlspecialchars(file_get_contents('api/.htaccess')) . '</pre>';
} else {
    echo '<p class="error">API .htaccess findes ikke.</p>';
}

echo '</section>';

// Apache fejllog (vis de seneste linjer)
echo '<section>
    <h2>7. Apache-fejllog</h2>';

// Funktionen er i php på serveren
$errorLog = '/var/log/apache2/error.log';
if (file_exists($errorLog) && is_readable($errorLog)) {
    // Få de seneste 50 linjer
    $command = 'tail -n 50 ' . escapeshellarg($errorLog);
    $output = [];
    exec($command, $output);
    
    if (!empty($output)) {
        echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    } else {
        echo '<p class="info">Ingen fejl fundet i loggen, eller logfilen er tom.</p>';
    }
} else {
    echo '<p class="warning">Kan ikke læse Apache-fejlloggen. Kontroller stien eller rettigheder.</p>';
}

echo '</section>';

echo '</body>
</html>';
