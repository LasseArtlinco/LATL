<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// API til fil-uploads
header('Content-Type: application/json');

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

// Definer tilladte filtyper
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Check filtype
$file = $_FILES['file'];
$file_type = $file['type'];

if (!in_array($file_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ugyldig filtype. Tilladt: JPG, PNG, GIF, WebP']);
    exit;
}

// Opret upload-mappe hvis den ikke findes
$upload_dir = UPLOAD_PATH;

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generer et unikt filnavn
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = uniqid() . '.' . $file_extension;
$upload_path = $upload_dir . '/' . $new_filename;

// Upload filen
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Konverter til WebP vil komme her senere
    
    echo json_encode([
        'success' => true,
        'filename' => $new_filename,
        'path' => '/uploads/' . $new_filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Kunne ikke uploade filen']);
}
