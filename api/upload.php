<?php
// api/upload.php - Upload handler for billeder og filer
require_once '../config.php';
require_once '../db.php';

// Tillad CORS og indstil headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Håndter OPTIONS præflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Tjek for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Få upload type (produkt billede, kvittering, osv.)
$uploadType = $_GET['type'] ?? 'product';

try {
    // Tjek om filen er uploadet
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fil upload fejl: ' . ($_FILES['file']['error'] ?? 'Ingen fil uploadet'));
    }
    
    // Valider filtype
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $fileMimeType = mime_content_type($_FILES['file']['tmp_name']);
    
    if (!in_array($fileMimeType, $allowedTypes)) {
        throw new Exception('Ugyldig filtype. Tilladt: JPEG, PNG, WebP');
    }
    
    // Bestem destinations mappe baseret på type
    $uploadDir = UPLOADS_DIR;
    switch ($uploadType) {
        case 'product':
            $uploadDir = PRODUCTS_DIR;
            break;
        // Tilføj flere typer efter behov
    }
    
    // Opret mappestruktur hvis den ikke eksisterer
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Kunne ikke oprette upload mappe');
        }
    }
    
    // Generer unikt filnavn
    $fileInfo = pathinfo($_FILES['file']['name']);
    $fileName = md5(uniqid() . time()) . '.' . $fileInfo['extension'];
    $filePath = $uploadDir . '/' . $fileName;
    
    // Flyt fil til destinations mappe
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        throw new Exception('Kunne ikke gemme filen');
    }
    
    // Forbered respons data
    $relativePath = str_replace(ROOT_PATH, '', $filePath);
    
    $response = [
        'status' => 'success',
        'file' => [
            'name' => $fileName,
            'path' => $relativePath,
            'url' => BASE_URL . $relativePath
        ]
    ];
    
    // Send respons
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}