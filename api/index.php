<?php
// api/index.php - Hovedindgang til API'et
// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file we can access
ini_set('error_log', dirname(__DIR__) . '/api_errors.log');
error_log('API index accessed: ' . date('Y-m-d H:i:s') . ' - ' . $_SERVER['REQUEST_URI']);

require_once '../config.php';
require_once '../db.php';

// Tillad CORS og indstil headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Håndter OPTIONS præflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse URL for at finde endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/'; // Juster dette baseret på din serveropsætning
$endpoint = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));

// Log the endpoint for debugging
error_log("Parsed endpoint: " . $endpoint);

// Få HTTP metode
$method = $_SERVER['REQUEST_METHOD'];

// Få request body for POST, PUT requests
$data = null;
if ($method == 'POST' || $method == 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
}

// Routing logic
try {
    // Database-forbindelse
    $db = Database::getInstance();
    
    // Log debug info
    error_log("Endpoint routing: " . $endpoint);
    
    // Switch based on endpoint prefix
    if (strpos($endpoint, 'products') === 0) {
        // Products API
        require_once 'products.php';
        $controller = new ProductsController($db);
        
        if (preg_match('/^products\/(\d+)/', $endpoint, $matches)) {
            $id = $matches[1];
            handleRequestWithId($controller, $method, $id, $data);
        } else {
            handleRequest($controller, $method, $data);
        }
    }
    else if (strpos($endpoint, 'orders') === 0) {
        // Orders API
        require_once 'orders.php';
        $controller = new OrdersController($db);
        
        if (preg_match('/^orders\/(\d+)/', $endpoint, $matches)) {
            $id = $matches[1];
            handleRequestWithId($controller, $method, $id, $data);
        } else {
            handleRequest($controller, $method, $data);
        }
    }
    else if (strpos($endpoint, 'layout') === 0) {
        // Layout API
        require_once 'layout.php';
        $controller = new LayoutController($db);
        
        if (preg_match('/^layout\/([a-zA-Z0-9_-]+)/', $endpoint, $matches)) {
            $pageId = $matches[1];
            handleRequestWithId($controller, $method, $pageId, $data);
        } else {
            handleRequest($controller, $method, $data);
        }
    }
    else if (strpos($endpoint, 'bands') === 0) {
        // Bands API
        require_once 'bands.php';
        require_once 'bands_endpoint.php';
        
        if (preg_match('/^bands\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/', $endpoint, $matches)) {
            $pageId = $matches[1];
            $bandId = $matches[2];
            $result = handleBandsRequest($db, $method, $pageId, $bandId, $data);
            echo json_encode($result);
        } 
        else if (preg_match('/^bands\/([a-zA-Z0-9_-]+)/', $endpoint, $matches)) {
            $pageId = $matches[1];
            $result = handleBandsRequest($db, $method, $pageId, null, $data);
            echo json_encode($result);
        }
        else {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid bands endpoint']);
        }
    }
    else if ($endpoint === 'global_styles' || $endpoint === 'global_styles/') {
        // Global Styles API - redirect to dedicated endpoint
        require_once 'global_styles.php';
        $controller = new GlobalStylesController($db);
        
        if ($method === 'GET') {
            $result = $controller->getStyles();
            echo json_encode($result);
        } 
        else if ($method === 'PUT' || $method === 'POST') {
            $result = $controller->updateStyles($data);
            echo json_encode($result);
        }
        else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed for global_styles']);
        }
    }
    else {
        // Ukendt endpoint
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found: ' . $endpoint]);
    }
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    if (DEBUG_MODE) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'An internal server error occurred']);
    }
}

// Hjælpefunktioner til at håndtere requests
function handleRequest($controller, $method, $data) {
    switch ($method) {
        case 'GET':
            $result = $controller->getAll();
            echo json_encode($result);
            break;
        case 'POST':
            $result = $controller->create($data);
            echo json_encode($result);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleRequestWithId($controller, $method, $id, $data) {
    switch ($method) {
        case 'GET':
            $result = $controller->getById($id);
            echo json_encode($result);
            break;
        case 'PUT':
            $result = $controller->update($id, $data);
            echo json_encode($result);
            break;
        case 'DELETE':
            $result = $controller->delete($id);
            echo json_encode($result);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
