<?php
// api/index.php
header('Content-Type: application/json');

// Include required files
require_once '../config.php';
require_once '../db.php';

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/';
$path = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
$pathParts = explode('/', $path);

// Initialize database
$db = Database::getInstance();

// Debug logging
if (defined('DEBUG') && DEBUG) {
    error_log("API Request: " . $path);
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
}

// Handle different endpoints
switch ($pathParts[0]) {
    case 'layout':
        require_once 'layout.php';
        $controller = new LayoutController($db);
        
        if (isset($pathParts[1])) {
            $pageId = $pathParts[1];
            
            if ($pageId === 'global' && isset($pathParts[2]) && $pathParts[2] === 'styles') {
                // Return global styles
                echo json_encode([
                    'status' => 'success',
                    'data' => getGlobalStyles()
                ]);
            } else {
                // Return specific page layout
                $result = $controller->getById($pageId);
                
                // VIGTIG ÆNDRING: Hvis layout findes, udpak bands fra layout_data
                if ($result['status'] === 'success' && isset($result['data']['layout_data'])) {
                    $layoutData = $result['data']['layout_data'];
                    
                    // Hvis layoutData er en JSON-streng, afkod den
                    if (is_string($layoutData)) {
                        $layoutData = json_decode($layoutData, true);
                    }
                    
                    // Udpak bands og tilføj dem til response
                    $result['data']['bands'] = isset($layoutData['bands']) ? $layoutData['bands'] : [];
                }
                
                echo json_encode($result);
            }
        } else {
            // Return all layouts
            $result = $controller->getAll();
            echo json_encode($result);
        }
        break;
        
    // Resten af din switch case for andre endpoints...
}

// Helper function til at få globale styles
function getGlobalStyles() {
    global $db;
    
    $globalLayout = $db->selectOne("SELECT layout_data FROM layout_config WHERE page_id = ?", ['global']);
    
    if ($globalLayout && isset($globalLayout['layout_data'])) {
        $layoutData = $globalLayout['layout_data'];
        
        // Hvis layoutData er en JSON-streng, afkod den
        if (is_string($layoutData)) {
            $layoutData = json_decode($layoutData, true);
        }
        
        return $layoutData;
    }
    
    // Default styles hvis ingen findes
    return [
        'color_palette' => [
            'primary' => '#042940',
            'secondary' => '#005C53',
            'accent' => '#9FC131',
            'bright' => '#DBF227',
            'background' => '#D6D58E',
            'text' => '#042940'
        ],
        'font_config' => [
            'heading' => [
                'font-family' => "'Allerta Stencil', sans-serif",
                'font-weight' => '400'
            ],
            'body' => [
                'font-family' => "'Open Sans', sans-serif",
                'font-weight' => '400'
            ]
        ]
    ];
}
