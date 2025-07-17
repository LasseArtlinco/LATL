<?php
// api/index.php - Hovedindgang til API'et
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
    
    switch ($endpoint) {
        case 'products':
        case 'products/':
            require_once 'products.php';
            $controller = new ProductsController($db);
            handleRequest($controller, $method, $data);
            break;
            
        case (preg_match('/^products\/(\d+)/', $endpoint, $matches) ? true : false):
            require_once 'products.php';
            $controller = new ProductsController($db);
            $id = $matches[1];
            handleRequestWithId($controller, $method, $id, $data);
            break;
            
        case 'orders':
        case 'orders/':
            require_once 'orders.php';
            $controller = new OrdersController($db);
            handleRequest($controller, $method, $data);
            break;
            
        case (preg_match('/^orders\/(\d+)/', $endpoint, $matches) ? true : false):
            require_once 'orders.php';
            $controller = new OrdersController($db);
            $id = $matches[1];
            handleRequestWithId($controller, $method, $id, $data);
            break;
            
        case 'layout':
        case 'layout/':
            require_once 'layout.php';
            $controller = new LayoutController($db);
            handleRequest($controller, $method, $data);
            break;
            
        case (preg_match('/^layout\/([a-zA-Z0-9_-]+)/', $endpoint, $matches) ? true : false):
            require_once 'layout.php';
            $controller = new LayoutController($db);
            $pageId = $matches[1];
            handleRequestWithId($controller, $method, $pageId, $data);
            break;
            
        default:
            // Ukendt endpoint
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
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