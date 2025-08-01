<?php
// api/index.php - Opret eller opdater denne fil
header('Content-Type: application/json');

// Inkluder nødvendige filer
require_once '../config.php';
require_once '../db.php';

// Aktiver CORS til udvikling
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Håndter preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug logging
if (defined('DEBUG') && DEBUG) {
    error_log("API Request: " . $_SERVER['REQUEST_URI']);
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
    
    // Log POST data
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
    }
}

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/';
$path = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
$pathParts = explode('/', $path);

// Initialize database
$db = Database::getInstance();

// Handle different endpoints
switch ($pathParts[0]) {
    case 'layout':
        require_once 'layout.php';
        $controller = new LayoutController($db);
        
        // Resten af din switch case for layout...
        break;
        
    case 'bands':
        require_once 'bands.php';
        $controller = new BandsController($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($pathParts[1])) {
                $pageId = $pathParts[1];
                
                if (isset($pathParts[2])) {
                    // Get specific band
                    $bandId = $pathParts[2];
                    $result = $controller->getBand($pageId, $bandId);
                } else {
                    // Get all bands for page
                    $result = $controller->getBands($pageId);
                }
                
                echo json_encode($result);
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Create new band
            $pageId = $pathParts[1];
            
            // Check if form data or JSON
            if (!empty($_POST['band_data'])) {
                $bandData = json_decode($_POST['band_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Log the error for debugging
                    error_log("JSON decode error: " . json_last_error_msg());
                    error_log("Raw POST data: " . $_POST['band_data']);
                    
                    // Return error response
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid JSON in band_data: ' . json_last_error_msg()
                    ]);
                    exit;
                }
            } else {
                // Try to get data from request body
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $bandData = json_decode($rawInput, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Log the error for debugging
                        error_log("JSON decode error from raw input: " . json_last_error_msg());
                        error_log("Raw input: " . $rawInput);
                        
                        // Return error response
                        http_response_code(400);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Invalid JSON in request body: ' . json_last_error_msg()
                        ]);
                        exit;
                    }
                } else {
                    // No data found
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No band data received'
                    ]);
                    exit;
                }
            }
            
            $result = $controller->createBand($pageId, $bandData);
            echo json_encode($result);
        } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // Update band
            $pageId = $pathParts[1];
            $bandId = $pathParts[2];
            
            // Same handling as for POST
            if (!empty($_POST['band_data'])) {
                $bandData = json_decode($_POST['band_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    error_log("Raw POST data: " . $_POST['band_data']);
                    
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid JSON in band_data: ' . json_last_error_msg()
                    ]);
                    exit;
                }
            } else {
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $bandData = json_decode($rawInput, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("JSON decode error from raw input: " . json_last_error_msg());
                        error_log("Raw input: " . $rawInput);
                        
                        http_response_code(400);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Invalid JSON in request body: ' . json_last_error_msg()
                        ]);
                        exit;
                    }
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No band data received'
                    ]);
                    exit;
                }
            }
            
            $result = $controller->updateBand($pageId, $bandId, $bandData);
            echo json_encode($result);
        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Delete band
            $pageId = $pathParts[1];
            $bandId = $pathParts[2];
            $result = $controller->deleteBand($pageId, $bandId);
            echo json_encode($result);
        } else {
            // Unsupported method
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown endpoint: ' . $pathParts[0]
        ]);
}
