<?php
// Vis PHP-fejl
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database konfiguration
$db_host = 'localhost';
$db_name = 'latl_db';
$db_user = 'din_bruger';
$db_pass = 'dit_password';

// Test databaseforbindelse
try {
    $conn = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database forbindelse OK!";
} catch (PDOException $e) {
    echo "Database fejl: " . $e->getMessage();
}
?>
