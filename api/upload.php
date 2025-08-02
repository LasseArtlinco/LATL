<?php
// api/upload.php - Forbedret API til fil-uploads
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image-handler.php';

// API til fil-uploads
header('Content-Type: application/json');

// Starter session hvis den ikke allerede er startet
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

// Få upload-type fra parameter (slideshow, product, etc.)
$upload_type = $_POST['type'] ?? 'general';

// Bestem undermappe baseret på type
$subdir = '';
switch ($upload_type) {
    case 'slideshow':
        $subdir = 'slides';
        break;
    case 'product':
        $subdir = 'products';
        break;
    case 'logo':
        $subdir = 'logos';
        break;
    default:
        $subdir = 'general';
}

// Få SEO-metadata fra POST
$alt_text = $_POST['alt_text'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

try {
    // Upload og optimer billedet
    $file = $_FILES['file'];
    $image_data = upload_image($file, $subdir);
    
    // Tilføj metadata
    $image_data['seo'] = [
        'alt_text' => $alt_text,
        'title' => $title,
        'description' => $description
    ];
    
    // Generer responsiv HTML-kode til billedet
    $responsive_html = responsive_image(
        $image_data, 
        $alt_text, 
        'uploaded-image',
        [
            'title' => $title,
            'data-description' => $description
        ]
    );
    
    // Returner succesrespons
    echo json_encode([
        'success' => true,
        'image' => $image_data,
        'filename' => basename($image_data['original']['path']),
        'path' => $image_data['original']['path'],
        'webp_path' => $image_data['optimized']['original_webp']['path'] ?? '',
        'responsive_sizes' => array_keys($image_data['optimized']),
        'responsive_html' => $responsive_html
    ]);
} catch (Exception $e) {
    // Returner fejlbesked
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
