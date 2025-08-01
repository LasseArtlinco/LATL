<?php
// api_debug.php - Simpel debug-script til at teste API-endpoints

// Aktiver fejlrapportering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Indlæs nødvendige filer
require_once 'config.php';
require_once 'db.php';

// Opret en funktion til at teste databaseforbindelsen
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $tables = $db->select("SHOW TABLES");
        return [
            'status' => 'success',
            'message' => 'Database connection successful',
            'tables' => $tables
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

// Opret en funktion til at teste global_styles
function testGlobalStyles() {
    try {
        require_once 'api/global_styles.php';
        $db = Database::getInstance();
        $controller = new GlobalStylesController($db);
        $result = $controller->getStyles();
        return $result;
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error testing global_styles: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Opret en funktion til at teste layout
function testLayout($pageId = 'forside') {
    try {
        require_once 'api/layout.php';
        $db = Database::getInstance();
        $controller = new LayoutController($db);
        $result = $controller->getById($pageId);
        return $result;
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error testing layout: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Opret en funktion til at teste bands
function testBands($pageId = 'forside') {
    try {
        require_once 'api/bands.php';
        $db = Database::getInstance();
        $controller = new BandsController($db);
        $result = $controller->getBands($pageId);
        return $result;
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error testing bands: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Funktion til at vise resultater som HTML
function displayResults($title, $result) {
    echo "<h3>$title</h3>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "<hr>";
}

// HTML-hoved
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL API Debug</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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
            max-height: 500px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        hr {
            margin: 30px 0;
            border: 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <h1>LATL API Debug</h1>
    
    <h2>1. PHP Information</h2>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    <p>Extensions:</p>
    <ul>
        <li>PDO: <?php echo extension_loaded('pdo') ? '<span class="success">Loaded</span>' : '<span class="error">Not loaded</span>'; ?></li>
        <li>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '<span class="success">Loaded</span>' : '<span class="error">Not loaded</span>'; ?></li>
        <li>JSON: <?php echo extension_loaded('json') ? '<span class="success">Loaded</span>' : '<span class="error">Not loaded</span>'; ?></li>
        <li>GD: <?php echo extension_loaded('gd') ? '<span class="success">Loaded</span>' : '<span class="error">Not loaded</span>'; ?></li>
    </ul>
    
    <h2>2. Configuration</h2>
    <p>DB_HOST: <?php echo defined('DB_HOST') ? DB_HOST : 'Not defined'; ?></p>
    <p>DB_NAME: <?php echo defined('DB_NAME') ? DB_NAME : 'Not defined'; ?></p>
    <p>DB_USER: <?php echo defined('DB_USER') ? '******' : 'Not defined'; ?></p>
    <p>BASE_URL: <?php echo defined('BASE_URL') ? BASE_URL : 'Not defined'; ?></p>
    <p>ROOT_PATH: <?php echo defined('ROOT_PATH') ? ROOT_PATH : 'Not defined'; ?></p>
    
    <h2>3. Database Connection Test</h2>
    <?php displayResults('Database Connection', testDatabaseConnection()); ?>
    
    <h2>4. API Tests</h2>
    <?php 
    displayResults('Global Styles', testGlobalStyles());
    displayResults('Layout Forside', testLayout('forside'));
    displayResults('Bands Forside', testBands('forside')); 
    ?>
    
    <h2>5. Table Structure</h2>
    <?php
    try {
        $db = Database::getInstance();
        $tables = $db->select("SHOW TABLES");
        
        foreach ($tables as $tableRow) {
            $tableName = array_values($tableRow)[0];
            echo "<h3>Table: $tableName</h3>";
            
            $columns = $db->select("DESCRIBE $tableName");
            echo "<pre>" . json_encode($columns, JSON_PRETTY_PRINT) . "</pre>";
            
            $count = $db->selectOne("SELECT COUNT(*) as count FROM $tableName");
            echo "<p>Row count: " . $count['count'] . "</p>";
            
            if ($count['count'] > 0 && $count['count'] < 10) {
                $rows = $db->select("SELECT * FROM $tableName LIMIT 3");
                echo "<p>Sample data:</p>";
                echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT) . "</pre>";
            }
            
            echo "<hr>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error retrieving table structure: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>
