<?php
// Vis PHP-fejl
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vis filstier for fejlfinding
echo "Nuværende filsti: " . __DIR__ . "<br>";
echo "Includes sti: " . __DIR__ . "/includes<br>";

// Inkluder konfigurationsfilen direkte fra denne placering
include_once __DIR__ . '/includes/config.php';

// Vis konfigurationsværdier for at bekræfte de er korrekte
echo "<h3>Database konfiguration:</h3>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (DB_PASS ? "[Adgangskode indstillet]" : "[Ingen adgangskode]") . "<br>";

// Test database forbindelse manuelt
echo "<h3>Database forbindelsestest:</h3>";
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Database forbindelse OK!";
} catch (PDOException $e) {
    echo "Database fejl: " . $e->getMessage();
}
?>
