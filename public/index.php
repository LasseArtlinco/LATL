<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Enkelt test
try {
    $conn = get_db_connection();
    echo "Database forbindelse OK fra index.php!";
    
    // Test at hente globale styles
    $styles = get_global_styles();
    echo "<pre>";
    print_r($styles);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Fejl: " . $e->getMessage();
}
?>
