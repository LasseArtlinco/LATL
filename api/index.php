<?php
// api/index.php - Centralized API Router
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', dirname(__DIR__) . '/api_errors.log');

require_once '../config.php';
require_once '../db.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse URL to find endpoint
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/'; // Adjust based on your server setup
$endpoint = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));

// Log access for debugging
error_log("API request: " . $_SERVER['REQUEST_METHOD'] . " " . $endpoint);

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get request body for POST, PUT requests
$data = null;
if ($method == 'POST' || $method == 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
}

// Upload handling for multipart/form-data
$isMultipartFormData = isset($_SERVER['CONTENT_TYPE']) && 
                        strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

// Routing logic
try {
    // Database connection
    $db = Database::getInstance();
    
    // Global endpoint routing
    if (strpos($endpoint, 'global_styles') === 0) {
        // Handle global styles API
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
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
    }
    else if (strpos($endpoint, 'products') === 0) {
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
        
        // Extract page_id and band_id from URL
        if (preg_match('/^bands\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/', $endpoint, $matches)) {
            // Endpoint format: bands/{page_id}/{band_id}
            $pageId = $matches[1];
            $bandId = $matches[2];
            
            $controller = new BandsController($db);
            
            switch ($method) {
                case 'GET':
                    $result = $controller->getBand($pageId, $bandId);
                    echo json_encode($result);
                    break;
                case 'PUT':
                    $result = $controller->updateBand($pageId, $bandId, $data);
                    echo json_encode($result);
                    break;
                case 'DELETE':
                    $result = $controller->deleteBand($pageId, $bandId);
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                    break;
            }
        } 
        else if (preg_match('/^bands\/([a-zA-Z0-9_-]+)/', $endpoint, $matches)) {
            // Endpoint format: bands/{page_id}
            $pageId = $matches[1];
            
            $controller = new BandsController($db);
            
            switch ($method) {
                case 'GET':
                    $result = $controller->getBands($pageId);
                    echo json_encode($result);
                    break;
                case 'POST':
                    // Handle file uploads if present
                    if ($isMultipartFormData) {
                        $bandData = isset($_POST['band_data']) ? json_decode($_POST['band_data'], true) : [];
                        $result = $controller->createBand($pageId, $bandData);
                        
                        // Handle image uploads
                        if (isset($_FILES['product_image']) || isset($_FILES['slide_images'])) {
                            // Process product image
                            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                                $controller->handleProductImageUpload($_FILES['product_image'], $pageId, $result['data']['band_id']);
                            }
                            
                            // Process slide images
                            if (isset($_FILES['slide_images'])) {
                                foreach ($_FILES['slide_images']['name'] as $index => $name) {
                                    if ($_FILES['slide_images']['error'][$index] === UPLOAD_ERR_OK) {
                                        $slideImage = [
                                            'name' => $_FILES['slide_images']['name'][$index],
                                            'type' => $_FILES['slide_images']['type'][$index],
                                            'tmp_name' => $_FILES['slide_images']['tmp_name'][$index],
                                            'error' => $_FILES['slide_images']['error'][$index],
                                            'size' => $_FILES['slide_images']['size'][$index]
                                        ];
                                        $controller->handleSlideImageUpload($slideImage, $pageId, $result['data']['band_id'], $index);
                                    }
                                }
                            }
                        }
                    } else {
                        $result = $controller->createBand($pageId, $data);
                    }
                    
                    echo json_encode($result);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
                    break;
            }
        }
        else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Invalid bands endpoint']);
        }
    }
    else if (strpos($endpoint, 'upload') === 0) {
        // Handle file uploads
        require_once 'upload.php';
        $uploadController = new UploadController($db);
        
        if ($method !== 'POST' || !$isMultipartFormData) {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Only POST with multipart/form-data is allowed for uploads']);
            exit;
        }
        
        $result = $uploadController->handleUpload($_FILES);
        echo json_encode($result);
    }
    else if ($endpoint === '' || $endpoint === '/') {
        // API root - return available endpoints
        echo json_encode([
            'status' => 'success',
            'message' => 'LATL API v1.0',
            'endpoints' => [
                'global_styles' => 'GET, PUT',
                'layout' => 'GET, POST',
                'layout/{page_id}' => 'GET, PUT, DELETE',
                'bands/{page_id}' => 'GET, POST',
                'bands/{page_id}/{band_id}' => 'GET, PUT, DELETE',
                'products' => 'GET, POST',
                'products/{id}' => 'GET, PUT, DELETE',
                'orders' => 'GET, POST',
                'orders/{id}' => 'GET, PUT, DELETE',
                'upload' => 'POST (multipart/form-data)'
            ]
        ]);
    }
    else {
        // Unknown endpoint
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Endpoint not found: ' . $endpoint]);
    }
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    if (DEBUG_MODE) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred']);
    }
}

// Helper functions for handling requests
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
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            break;
    }
}
