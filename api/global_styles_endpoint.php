<?php
// api/global_styles_endpoint.php - Direkte endpoint for global stilarter
// Dette er en standalone-løsning, der omgår den komplekse API-routing

// Aktiver fejlvisning i udvikling (fjern i produktion)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file we can access
ini_set('error_log', dirname(__DIR__) . '/api_errors.log');
error_log('Global styles endpoint accessed: ' . date('Y-m-d H:i:s'));

// Inkluder nødvendige filer
require_once '../config.php';
require_once '../db.php';

// Sæt korrekte HTTP-headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization");

// Håndter OPTIONS præflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Global Styles Controller
 */
class SimpleGlobalStylesController {
    private $db;
    
    /**
     * Konstruktør
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Hent globale stilarter
     */
    public function getStyles() {
        // Standard stilarter - altid returneres hvis intet andet findes
        $defaultStyles = [
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
                ],
                'price' => [
                    'font-family' => "'Allerta Stencil', sans-serif",
                    'font-weight' => '400'
                ],
                'button' => [
                    'font-family' => "'Open Sans', sans-serif",
                    'font-weight' => '600'
                ]
            ],
            'global_styles' => [
                'css' => "/* Global styling */\nbody {\n  line-height: 1.6;\n}\n\n.container {\n  max-width: 1200px;\n  margin: 0 auto;\n  padding: 0 15px;\n}\n\n.button {\n  transition: all 0.3s ease;\n}\n\n.button:hover {\n  transform: translateY(-2px);\n  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\n}"
            ]
        ];
        
        try {
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            
            if (empty($tables)) {
                // Hvis tabellen ikke findes, opret den
                $this->createLayoutConfigTable();
                
                // Opret global styles post med standardværdier
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'layout_data' => json_encode($defaultStyles)
                ]);
                
                return ['status' => 'success', 'data' => $defaultStyles];
            }
            
            // Hent global styles
            $globalStyles = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = 'global'");
            
            if (!$globalStyles) {
                // Hvis ingen global styles findes, opret standardstilarter
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'layout_data' => json_encode($defaultStyles)
                ]);
                
                return ['status' => 'success', 'data' => $defaultStyles];
            }
            
            // Konverter layout_data fra JSON
            if (isset($globalStyles['layout_data']) && !empty($globalStyles['layout_data'])) {
                $layoutData = json_decode($globalStyles['layout_data'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($layoutData)) {
                    // Sikre at alle påkrævede felter er til stede
                    if (!isset($layoutData['color_palette'])) {
                        $layoutData['color_palette'] = $defaultStyles['color_palette'];
                    }
                    
                    if (!isset($layoutData['font_config'])) {
                        $layoutData['font_config'] = $defaultStyles['font_config'];
                    }
                    
                    if (!isset($layoutData['global_styles'])) {
                        $layoutData['global_styles'] = $defaultStyles['global_styles'];
                    }
                    
                    return ['status' => 'success', 'data' => $layoutData];
                }
            }
            
            // Fald tilbage til standardstilarter hvis noget gik galt
            return ['status' => 'success', 'data' => $defaultStyles];
            
        } catch (Exception $e) {
            error_log('Error in getStyles: ' . $e->getMessage());
            // Returner standard stilarter ved fejl
            return ['status' => 'success', 'data' => $defaultStyles];
        }
    }
    
    /**
     * Opdater globale stilarter
     */
    public function updateStyles($data) {
        try {
            if (!is_array($data)) {
                return ['status' => 'error', 'message' => 'Invalid data format, array expected'];
            }
            
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            
            if (empty($tables)) {
                // Hvis tabellen ikke findes, opret den
                $this->createLayoutConfigTable();
            }
            
            // Konverter data til JSON
            $jsonData = json_encode($data);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['status' => 'error', 'message' => 'JSON encoding error: ' . json_last_error_msg()];
            }
            
            // Tjek om global styles post eksisterer
            $globalStyles = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = 'global'");
            
            if (!$globalStyles) {
                // Hvis posten ikke findes, opret den
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'layout_data' => $jsonData
                ]);
            } else {
                // Hvis posten findes, opdater den
                $this->db->update('layout_config', [
                    'layout_data' => $jsonData
                ], 'page_id = ?', ['global']);
            }
            
            return ['status' => 'success', 'message' => 'Global styles updated successfully', 'data' => $data];
            
        } catch (Exception $e) {
            error_log('Error in updateStyles: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to update global styles: ' . $e->getMessage()];
        }
    }
    
    /**
     * Opret layout_config tabellen
     */
    private function createLayoutConfigTable() {
        $query = "
            CREATE TABLE IF NOT EXISTS layout_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id VARCHAR(255) NOT NULL UNIQUE,
                layout_data JSON,
                global_styles JSON,
                font_config JSON,
                color_palette JSON,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $this->db->query($query);
    }
}

// Kør endpoint-logik
try {
    // Initialiser database
    $db = Database::getInstance();
    $controller = new SimpleGlobalStylesController($db);
    
    // Håndter HTTP-metoder
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Hent stilarter
        $result = $controller->getStyles();
        echo json_encode($result);
    } 
    else if ($method === 'PUT') {
        // Hent request body
        $inputData = file_get_contents('php://input');
        $data = json_decode($inputData, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Invalid JSON: ' . json_last_error_msg()
            ]);
            exit;
        }
        
        // Opdater stilarter
        $result = $controller->updateStyles($data);
        echo json_encode($result);
    } 
    else {
        // Metode ikke tilladt
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} 
catch (Exception $e) {
    // Log fejl og returner fejlbesked
    error_log('API error in global_styles_endpoint: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error', 
        'details' => $e->getMessage()
    ]);
}
