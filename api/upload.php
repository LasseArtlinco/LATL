<?php
// api/upload.php - Forbedret upload-API med WebP-konvertering og SEO-optimering
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image_handler.php';

// API til fil-uploads
header('Content-Type: application/json');

// Start session hvis den ikke allerede er startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check om brugeren er logget ind
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Ikke autoriseret']);
    exit;
}

// Check om det er en POST-request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode ikke tilladt']);
    exit;
}

// Check om der er en fil at uploade
if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ingen fil sendt']);
    exit;
}

// Få information om båndtype og mål
$band_type = $_POST['type'] ?? '';
$target_field = $_POST['target'] ?? '';

// Bestem hvilken mappe billedet skal gemmes i
$subdir = '';
if ($band_type) {
    $subdir = strtolower($band_type);
}

try {
    // Brug ImageHandler klassen til at uploade og optimere billedet
    $handler = new ImageHandler();
    $result = $handler->uploadImage($_FILES['file'], $subdir);
    
    // Tjek om det er et produktbillede og optimér for transparens
    if ($band_type === 'product' && strpos($target_field, 'product_image') !== false) {
        // Sikre at produktbilleder har transparent baggrund
        $preserveTransparency = true;
    } else {
        $preserveTransparency = false;
    }
    
    // Bestem hvilken sti der skal returneres (prioritér WebP)
    $webpPath = '';
    
    // Find den bedste WebP version for responsive billeder
    if (isset($result['optimized']['hero']['webp'])) {
        $webpPath = $result['optimized']['hero']['webp']['path'];
    } elseif (isset($result['optimized']['large']['webp'])) {
        $webpPath = $result['optimized']['large']['webp']['path'];
    } elseif (isset($result['optimized']['original_webp'])) {
        $webpPath = $result['optimized']['original_webp']['path'];
    }
    
    // Hvis ingen WebP blev genereret, brug originalen
    $path = $webpPath ? $webpPath : $result['original']['path'];
    
    // Fjern ROOT_PATH fra stien og tilføj evt. manglende '/'
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    // Returner resultat
    echo json_encode([
        'success' => true,
        'filename' => $result['original']['filename'],
        'path' => $path,
        'dimensions' => [
            'width' => $result['metadata']['width'] ?? 0,
            'height' => $result['metadata']['height'] ?? 0
        ],
        'metadata' => $result['metadata'],
        'formats' => [
            'original' => $result['original']['path'],
            'webp' => $webpPath
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
