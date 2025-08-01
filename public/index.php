<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test databaseforbindelse
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $conn = get_db_connection();
    echo "Database forbindelse OK!";
} catch (Exception $e) {
    echo "Database fejl: " . $e->getMessage();
}
?>
