<?php
// api/index.php
header('Content-Type: application/json');

// Inkluder nødvendige filer
require_once '../config.php';
require_once '../db.php';

// Aktiver CORS til udvikling
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/';
$path = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
$pathParts = explode('/', $path);

// Initialiser database
$db = Database::getInstance();

// Debug logging
if (defined('DEBUG') && DEBUG) {
    error_log("API Request: " . $path);
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
}

// Håndter forskellige endpoints
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
                
                // KRITISK FIX: Hvis layout findes, udpak bands fra layout_data og læg dem i data.bands 
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
        
    case 'bands':
        require_once 'bands.php';
        $controller = new BandsController($db);
        
        // Resten af din switch case for bands...
        break;
    
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown endpoint: ' . $pathParts[0]
        ]);
}

// Hjælpefunktion til at få globale styles
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
