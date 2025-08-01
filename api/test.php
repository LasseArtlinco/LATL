<?php
// api/test.php - Simple diagnostic endpoint
// Save this file in your api/ directory

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up headers for JSON response
header('Content-Type: application/json');

try {
    // Include necessary files
    require_once '../config.php';
    require_once '../db.php';
    
    // Test database connection
    $db = Database::getInstance();
    
    // Test basic query
    $tables = $db->select("SHOW TABLES");
    
    // Return successful response with diagnostic info
    echo json_encode([
        'status' => 'success',
        'message' => 'API diagnostics successful',
        'php_version' => PHP_VERSION,
        'database' => [
            'connected' => true,
            'tables' => $tables
        ],
        'config' => [
            'debug_mode' => DEBUG_MODE,
            'base_url' => BASE_URL
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response with diagnostic info
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ]);
}
