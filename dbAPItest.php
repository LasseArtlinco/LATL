<?php
// dbAPItest.php - Test database connection and API functionality
header('Content-Type: text/plain; charset=utf-8');

echo "=======================================================\n";
echo "LATL.dk Database and API Diagnostic Test\n";
echo "=======================================================\n";
echo "Test run at: " . date('Y-m-d H:i:s') . "\n\n";

// Load configuration
if (file_exists('config.php')) {
    echo "[✓] Found config.php file\n";
    require_once 'config.php';
    
    echo "Database Configuration:\n";
    echo "- Host: " . DB_HOST . "\n";
    echo "- Database: " . DB_NAME . "\n";
    echo "- User: " . DB_USER . "\n";
    echo "- Charset: " . DB_CHARSET . "\n";
    echo "- Base URL: " . BASE_URL . "\n";
    echo "- Debug Mode: " . (DEBUG_MODE ? 'Enabled' : 'Disabled') . "\n";
} else {
    echo "[✗] config.php file not found. Please create one from config.php.example\n";
    exit;
}

// Test database connection
echo "\n=======================================================\n";
echo "Testing Database Connection\n";
echo "=======================================================\n";

try {
    require_once 'db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "[✓] Successfully connected to database\n";
    
    // List all tables in the database
    echo "\nDatabase Tables:\n";
    $tables = $db->select("SHOW TABLES");
    
    if (empty($tables)) {
        echo "No tables found in the database. You may need to run db_setup.php\n";
    } else {
        echo "Found " . count($tables) . " tables:\n";
        foreach ($tables as $tableRow) {
            $tableName = reset($tableRow);
            echo "- $tableName\n";
            
            // Show table structure
            $columns = $db->select("DESCRIBE `$tableName`");
            echo "  Columns:\n";
            foreach ($columns as $column) {
                echo "    {$column['Field']} ({$column['Type']})" . 
                     ($column['Key'] == 'PRI' ? ' PRIMARY KEY' : '') . 
                     ($column['Null'] == 'NO' ? ' NOT NULL' : '') . "\n";
            }
            
            // Count rows in table
            $count = $db->selectOne("SELECT COUNT(*) as count FROM `$tableName`");
            echo "  Total rows: {$count['count']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "[✗] Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials in config.php\n";
}

// Test API functionality
echo "\n=======================================================\n";
echo "Testing API Endpoints\n";
echo "=======================================================\n";

function testApiEndpoint($endpoint) {
    $url = BASE_URL . '/api/' . $endpoint;
    echo "Testing: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "[✗] Request failed: $error\n";
    } else {
        echo "HTTP Status: $httpCode\n";
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "[✓] Endpoint accessible\n";
            
            // Try to decode JSON response
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "Response is valid JSON\n";
                echo "Status: " . ($data['status'] ?? 'Not available') . "\n";
                
                if (isset($data['data']) && is_array($data['data'])) {
                    echo "Data items: " . count($data['data']) . "\n";
                } elseif (isset($data['message'])) {
                    echo "Message: " . $data['message'] . "\n";
                }
            } else {
                echo "[✗] Response is not valid JSON: " . json_last_error_msg() . "\n";
                // Print the first 200 characters of response
                echo "Response preview: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
            }
        } else {
            echo "[✗] Endpoint returned error code: $httpCode\n";
            echo "Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
        }
    }
    
    echo "\n";
}

// Test API routing
$apiEndpoints = [
    'products',
    'orders',
    'layout',
    'layout/forside'
];

foreach ($apiEndpoints as $endpoint) {
    testApiEndpoint($endpoint);
}

// Check .htaccess file configuration
echo "\n=======================================================\n";
echo "Checking .htaccess Configuration\n";
echo "=======================================================\n";

if (file_exists('.htaccess')) {
    echo "[✓] Found .htaccess file\n";
    $htaccess = file_get_contents('.htaccess');
    echo "Content preview:\n" . substr($htaccess, 0, 500) . (strlen($htaccess) > 500 ? "..." : "") . "\n";
} else {
    echo "[✗] .htaccess file not found in root directory\n";
}

if (file_exists('api/.htaccess')) {
    echo "[✓] Found api/.htaccess file\n";
    $apiHtaccess = file_get_contents('api/.htaccess');
    echo "Content preview:\n" . substr($apiHtaccess, 0, 500) . (strlen($apiHtaccess) > 500 ? "..." : "") . "\n";
} else {
    echo "[✗] api/.htaccess file not found\n";
}

// Check server PHP settings
echo "\n=======================================================\n";
echo "Server PHP Information\n";
echo "=======================================================\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled') . "\n";
echo "JSON: " . (extension_loaded('json') ? 'Enabled' : 'Disabled') . "\n";
echo "cURL: " . (extension_loaded('curl') ? 'Enabled' : 'Disabled') . "\n";
echo "File Uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";

echo "\n=======================================================\n";
echo "Test Completed\n";
echo "=======================================================\n";
?>
