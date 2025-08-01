<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// API til håndtering af bånd
header('Content-Type: application/json');

// Check om brugeren er logget ind
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Ikke autoriseret']);
    exit;
}

// Få HTTP-metode
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Hent bånd
        if (isset($_GET['id'])) {
            // Hent specifikt bånd
            $band_id = (int) $_GET['id'];
            
            $conn = get_db_connection();
            $stmt = $conn->prepare("SELECT * FROM layout_bands WHERE id = ?");
            $stmt->execute([$band_id]);
            
            $band = $stmt->fetch();
            
            if ($band) {
                $band['band_content'] = json_decode($band['band_content'], true);
                echo json_encode(['success' => true, 'band' => $band]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Bånd ikke fundet']);
            }
        } elseif (isset($_GET['page'])) {
            // Hent alle bånd for en side
            $page_id = $_GET['page'];
            
            $bands = get_page_bands($page_id);
            echo json_encode(['success' => true, 'bands' => $bands]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Manglende parameter']);
        }
        break;
        
    case 'POST':
        // Opret nyt bånd
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            // Prøv at få data fra form
            $data = $_POST;
        }
        
        if (empty($data['page_id']) || empty($data['band_type']) || !isset($data['band_height']) || empty($data['band_content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Manglende påkrævede felter']);
            exit;
        }
        
        $page_id = $data['page_id'];
        $band_type = $data['band_type'];
        $band_height = (int) $data['band_height'];
        $band_content = is_array($data['band_content']) ? $data['band_content'] : json_decode($data['band_content'], true);
        $band_order = isset($data['band_order']) ? (int) $data['band_order'] : 999;
        
        if (!$band_content) {
            http_response_code(400);
            echo json_encode(['error' => 'Ugyldigt JSON i band_content']);
            exit;
        }
        
        $band_id = save_band($page_id, $band_type, $band_height, $band_content, $band_order);
        
        echo json_encode(['success' => true, 'band_id' => $band_id]);
        break;
        
    case 'PUT':
        // Opdater eksisterende bånd
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Manglende bånd ID']);
            exit;
        }
        
        $band_id = (int) $data['id'];
        
        // Hent eksisterende bånd
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM layout_bands WHERE id = ?");
        $stmt->execute([$band_id]);
        
        $band = $stmt->fetch();
        
        if (!$band) {
            http_response_code(404);
            echo json_encode(['error' => 'Bånd ikke fundet']);
            exit;
        }
        
        // Opdater felter
        $page_id = $data['page_id'] ?? $band['page_id'];
        $band_type = $data['band_type'] ?? $band['band_type'];
        $band_height = isset($data['band_height']) ? (int) $data['band_height'] : $band['band_height'];
        $band_content = isset($data['band_content']) ? (is_array($data['band_content']) ? $data['band_content'] : json_decode($data['band_content'], true)) : json_decode($band['band_content'], true);
        $band_order = isset($data['band_order']) ? (int) $data['band_order'] : $band['band_order'];
        
        save_band($page_id, $band_type, $band_height, $band_content, $band_order, $band_id);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'DELETE':
        // Slet bånd
        if (isset($_GET['id'])) {
            $band_id = (int) $_GET['id'];
            
            $result = delete_band($band_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Bånd ikke fundet']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Manglende ID parameter']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Metode ikke tilladt']);
}
