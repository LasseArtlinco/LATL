<?php
// debug-simple.php - Placeres i RODEN (samme sted som test.php)
// UDEN band-renderer.php for at undgå fejl

// Vis fejl
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug Simple</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #042940; color: white; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
</style>";
echo "</head><body>\n";

echo "<h1>🔍 LATL.dk - Debug (Simple Version)</h1>\n";

// Test 1: Include config
echo "<h2>Test 1: Config</h2>";
$config_path = __DIR__ . '/includes/config.php';
if (file_exists($config_path)) {
    echo "<p>✅ config.php findes</p>";
    require_once $config_path;
    
    if (defined('ROOT_PATH')) {
        echo "<p>ROOT_PATH: " . ROOT_PATH . "</p>";
    }
    if (defined('UPLOADS_DIR')) {
        echo "<p>UPLOADS_DIR: " . UPLOADS_DIR . "</p>";
    }
} else {
    echo "<p class='error'>❌ config.php findes ikke!</p>";
    die("Kan ikke fortsætte uden config.php");
}

// Test 2: Include db.php
echo "<h2>Test 2: Database</h2>";
$db_path = __DIR__ . '/includes/db.php';
if (file_exists($db_path)) {
    echo "<p>✅ db.php findes</p>";
    require_once $db_path;
    
    try {
        $db = get_db_connection();
        echo "<p class='success'>✅ Database forbindelse OK</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database fejl: " . $e->getMessage() . "</p>";
        die();
    }
} else {
    echo "<p class='error'>❌ db.php findes ikke!</p>";
    die();
}

// Test 3: Hent data DIREKTE fra database (uden band-renderer)
echo "<h2>Test 3: Bånd Data (Direkte fra DB)</h2>";

try {
    // Hent bånd direkte med SQL
    $sql = "SELECT * FROM layout_bands WHERE page_id = 'forside' ORDER BY band_order";
    $stmt = $db->query($sql);
    $bands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Fundet " . count($bands) . " bånd</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Type</th><th>Content (første 200 tegn)</th><th>Billeder</th></tr>";
    
    foreach ($bands as $band) {
        $content = json_decode($band['band_content'], true);
        $images = [];
        
        // Find billeder
        if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
            foreach ($content['slides'] as $slide) {
                if (!empty($slide['image'])) {
                    $images[] = $slide['image'];
                }
            }
        } elseif ($band['band_type'] === 'product' && !empty($content['image'])) {
            $images[] = $content['image'];
        }
        
        echo "<tr>";
        echo "<td>" . $band['id'] . "</td>";
        echo "<td>" . $band['band_type'] . "</td>";
        echo "<td><small>" . substr($band['band_content'], 0, 200) . "...</small></td>";
        echo "<td>" . count($images) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vis billeder i detaljer
    echo "<h2>Test 4: Billede Analyse</h2>";
    
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    echo "<p>Document root: $document_root</p>";
    
    foreach ($bands as $band) {
        $content = json_decode($band['band_content'], true);
        
        if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
            echo "<h3>Slideshow (ID: " . $band['id'] . ")</h3>";
            echo "<table>";
            echo "<tr><th>Slide</th><th>Image Path</th><th>Eksisterer?</th></tr>";
            
            foreach ($content['slides'] as $index => $slide) {
                if (!empty($slide['image'])) {
                    $image_path = $slide['image'];
                    
                    // Prøv forskellige stier
                    $paths_to_try = [
                        $document_root . '/' . $image_path,
                        $document_root . '/public/' . $image_path,
                        $document_root . '/' . ltrim($image_path, '/'),
                        $document_root . '/public/' . ltrim($image_path, '/')
                    ];
                    
                    $found = false;
                    $found_path = '';
                    
                    foreach ($paths_to_try as $try_path) {
                        if (file_exists($try_path)) {
                            $found = true;
                            $found_path = $try_path;
                            break;
                        }
                    }
                    
                    echo "<tr>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td><small>" . htmlspecialchars($image_path) . "</small></td>";
                    if ($found) {
                        echo "<td class='success'>✅ Ja<br><small>" . str_replace($document_root, '', $found_path) . "</small></td>";
                    } else {
                        echo "<td class='error'>❌ Nej</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
        
        if ($band['band_type'] === 'product' && !empty($content['image'])) {
            echo "<h3>Product: " . ($content['title'] ?? 'Uden titel') . " (ID: " . $band['id'] . ")</h3>";
            
            $image_path = $content['image'];
            $paths_to_try = [
                $document_root . '/' . $image_path,
                $document_root . '/public/' . $image_path,
                $document_root . '/' . ltrim($image_path, '/'),
                $document_root . '/public/' . ltrim($image_path, '/')
            ];
            
            $found = false;
            $found_path = '';
            
            foreach ($paths_to_try as $try_path) {
                if (file_exists($try_path)) {
                    $found = true;
                    $found_path = $try_path;
                    break;
                }
            }
            
            echo "<p>Image: " . htmlspecialchars($image_path) . "</p>";
            if ($found) {
                echo "<p class='success'>✅ Fundet: " . str_replace($document_root, '', $found_path) . "</p>";
            } else {
                echo "<p class='error'>❌ Ikke fundet</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Fejl: " . $e->getMessage() . "</p>";
}

// Test 5: Vis uploads mappe struktur
echo "<h2>Test 5: Uploads Mappe</h2>";

$paths_to_check = [
    '/uploads',
    '/public/uploads'
];

foreach ($paths_to_check as $path) {
    $full_path = $document_root . $path;
    echo "<h3>Tjekker: $path</h3>";
    
    if (is_dir($full_path)) {
        echo "<p class='success'>✅ Mappen findes</p>";
        echo "<pre>";
        
        // Vis struktur
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full_path),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $count = 0;
        foreach ($iterator as $file) {
            if ($count++ > 50) {
                echo "... (mere end 50 filer, stoppet)\n";
                break;
            }
            
            if ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
                $depth = $iterator->getDepth();
                echo str_repeat('  ', $depth) . '📁 ' . $file->getFilename() . "/\n";
            } elseif ($file->isFile()) {
                $depth = $iterator->getDepth();
                echo str_repeat('  ', $depth) . '📄 ' . $file->getFilename() . ' (' . number_format($file->getSize()/1024, 1) . ' KB)' . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Mappen findes ikke</p>";
    }
}

// Fix forslag
echo "<h2>🔧 Fix Database Stier</h2>";
echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
echo "<p>Hvis billeder ikke findes, kan du køre disse SQL kommandoer:</p>";
echo "<pre style='background: white; padding: 10px;'>";
echo "-- Backup først!\n";
echo "CREATE TABLE IF NOT EXISTS layout_bands_backup_" . date('Ymd') . " AS SELECT * FROM layout_bands;\n\n";
echo "-- Fix stier\n";
echo "UPDATE layout_bands\n";
echo "SET band_content = REPLACE(band_content, '\"image\": \"public/uploads/', '\"image\": \"uploads/')\n";
echo "WHERE band_type IN ('slideshow', 'product');\n\n";
echo "UPDATE layout_bands\n";
echo "SET band_content = REPLACE(band_content, '\"image\": \"/uploads/', '\"image\": \"uploads/')\n";
echo "WHERE band_type IN ('slideshow', 'product');\n";
echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
