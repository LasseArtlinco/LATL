<?php
// ========================================
// debug-images.php - Test billedstier
// ========================================
// Gem denne fil som /debug-images.php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/band-renderer.php';

// Set debug mode
define('DEBUG_MODE', true);

echo "<h1>LATL.dk - Debug Billedstier</h1>";

// Test billedstier
$bands = get_page_bands('forside');

echo "<h2>Bånd Data fra Database</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";

foreach ($bands as $band) {
    $content = is_array($band['band_content']) ? $band['band_content'] : json_decode($band['band_content'], true);
    
    echo "\n<strong>Bånd Type: " . $band['band_type'] . "</strong>\n";
    echo "Bånd ID: " . $band['id'] . "\n";
    
    if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
        echo "Antal slides: " . count($content['slides']) . "\n\n";
        
        foreach ($content['slides'] as $index => $slide) {
            echo "  Slide " . ($index + 1) . ":\n";
            echo "    Titel: " . ($slide['title'] ?? 'Ingen titel') . "\n";
            echo "    Original sti: " . ($slide['image'] ?? 'Ingen billede') . "\n";
            echo "    Formateret sti: " . format_image_path($slide['image'] ?? '') . "\n";
            echo "\n";
        }
    } elseif ($band['band_type'] === 'product' && isset($content['image'])) {
        echo "Produkt titel: " . ($content['title'] ?? 'Ingen titel') . "\n";
        echo "Original sti: " . $content['image'] . "\n";
        echo "Formateret sti: " . format_image_path($content['image']) . "\n";
        echo "\n";
    }
}

echo "</pre>";

// Test om billeder faktisk eksisterer på serveren
echo "<h2>Fil Eksistens Check</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";

$document_root = $_SERVER['DOCUMENT_ROOT'];
echo "Document Root: " . $document_root . "\n";
echo "Uploads Dir: " . UPLOADS_DIR . "\n\n";

foreach ($bands as $band) {
    $content = is_array($band['band_content']) ? $band['band_content'] : json_decode($band['band_content'], true);
    
    if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
        foreach ($content['slides'] as $index => $slide) {
            if (!empty($slide['image'])) {
                $formatted_path = format_image_path($slide['image']);
                $full_path = $document_root . $formatted_path;
                
                echo "Slide " . ($index + 1) . ":\n";
                echo "  Formateret sti: " . $formatted_path . "\n";
                echo "  Fuld sti: " . $full_path . "\n";
                echo "  Eksisterer: ";
                
                if (file_exists($full_path)) {
                    echo "<span style='color: green;'>✓ JA</span>\n";
                    echo "  Størrelse: " . number_format(filesize($full_path) / 1024, 2) . " KB\n";
                    list($width, $height) = getimagesize($full_path);
                    echo "  Dimensioner: {$width}x{$height} px\n";
                } else {
                    echo "<span style='color: red;'>✗ NEJ</span>\n";
                    
                    // Prøv alternative stier
                    $alternatives = [
                        $document_root . '/public' . $formatted_path,
                        $document_root . '/public/uploads/' . basename($formatted_path),
                        UPLOADS_DIR . '/' . basename($formatted_path)
                    ];
                    
                    foreach ($alternatives as $alt_path) {
                        if (file_exists($alt_path)) {
                            echo "  <span style='color: orange;'>→ Fundet på: " . $alt_path . "</span>\n";
                            break;
                        }
                    }
                }
                echo "\n";
            }
        }
    } elseif ($band['band_type'] === 'product' && !empty($content['image'])) {
        $formatted_path = format_image_path($content['image']);
        $full_path = $document_root . $formatted_path;
        
        echo "Produkt: " . ($content['title'] ?? 'Uden titel') . "\n";
        echo "  Formateret sti: " . $formatted_path . "\n";
        echo "  Fuld sti: " . $full_path . "\n";
        echo "  Eksisterer: ";
        
        if (file_exists($full_path)) {
            echo "<span style='color: green;'>✓ JA</span>\n";
        } else {
            echo "<span style='color: red;'>✗ NEJ</span>\n";
        }
        echo "\n";
    }
}

echo "</pre>";

// Vis uploads mappe struktur
echo "<h2>Uploads Mappe Struktur</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";

function scan_directory($dir, $indent = 0) {
    if (!is_dir($dir)) {
        echo str_repeat('  ', $indent) . "Mappen findes ikke: $dir\n";
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        echo str_repeat('  ', $indent);
        
        if (is_dir($path)) {
            echo "📁 <strong>$file/</strong>\n";
            scan_directory($path, $indent + 1);
        } else {
            $size = number_format(filesize($path) / 1024, 2);
            echo "📄 $file ({$size} KB)\n";
        }
    }
}

$uploads_path = $document_root . '/public/uploads';
if (is_dir($uploads_path)) {
    echo "Uploads mappe: $uploads_path\n\n";
    scan_directory($uploads_path);
} else {
    echo "Uploads mappen findes ikke på: $uploads_path\n";
    
    // Prøv alternative steder
    $alt_paths = [
        $document_root . '/uploads',
        UPLOADS_DIR,
        dirname(__DIR__) . '/public/uploads'
    ];
    
    foreach ($alt_paths as $alt) {
        if (is_dir($alt)) {
            echo "\nFundet uploads mappe på: $alt\n\n";
            scan_directory($alt);
            break;
        }
    }
}

echo "</pre>";

// Test format_image_path funktionen
echo "<h2>Test af format_image_path() Funktion</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";

$test_paths = [
    'uploads/slideshow/large/test.webp',
    '/uploads/slideshow/large/test.webp',
    'public/uploads/slideshow/large/test.webp',
    '/public/uploads/slideshow/large/test.webp',
    'placeholder-product.png',
    '/images/test.jpg'
];

foreach ($test_paths as $path) {
    echo "Input:  '$path'\n";
    echo "Output: '" . format_image_path($path) . "'\n\n";
}

echo "</pre>";

?>

<!-- SQL Fixes -->
<h2>Database Fixes</h2>
<div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3>Kør disse SQL kommandoer for at rette billedstier:</h3>
    <pre style="background: white; padding: 10px; border: 1px solid #ddd;">
-- Backup først!
CREATE TABLE layout_bands_backup AS SELECT * FROM layout_bands;

-- Fix 1: Fjern 'public/' prefix fra alle billedstier
UPDATE layout_bands 
SET band_content = REPLACE(
    REPLACE(
        REPLACE(
            band_content,
            '"image": "public/uploads/',
            '"image": "uploads/'
        ),
        '"image": "/public/uploads/',
        '"image": "uploads/'
    ),
    '"image": "public/',
    '"image": "'
)
WHERE band_type IN ('slideshow', 'product', 'product_cards');

-- Fix 2: Sørg for at alle uploads stier ikke har leading slash
UPDATE layout_bands 
SET band_content = REPLACE(
    band_content,
    '"image": "/uploads/',
    '"image": "uploads/'
)
WHERE band_type IN ('slideshow', 'product', 'product_cards');

-- Verificer ændringerne
SELECT id, band_type, band_content FROM layout_bands;
    </pre>
</div>

<h2>Quick Fix Script</h2>
<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3>Automatisk fix (kør dette efter backup):</h3>
    <form method="post" action="">
        <button type="submit" name="fix_paths" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            🔧 Kør Automatisk Fix
        </button>
    </form>
</div>

<?php
// Håndter automatisk fix
if (isset($_POST['fix_paths'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Kører fixes...</h3>";
    
    try {
        $db = get_db_connection();
        
        // Backup først
        $db->exec("CREATE TABLE IF NOT EXISTS layout_bands_backup AS SELECT * FROM layout_bands");
        echo "✓ Backup oprettet<br>";
        
        // Fix paths
        $queries = [
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"public/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')",
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"/public/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')",
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')"
        ];
        
        foreach ($queries as $query) {
            $affected = $db->exec($query);
            echo "✓ Opdateret $affected rækker<br>";
        }
        
        echo "<br><strong>Fixes anvendt! Genindlæs siden for at se ændringerne.</strong>";
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>Fejl: " . $e->getMessage() . "</span>";
    }
    
    echo "</div>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #042940;
        border-bottom: 3px solid #9FC131;
        padding-bottom: 10px;
    }
    h2 {
        color: #005C53;
        margin-top: 30px;
    }
    pre {
        overflow-x: auto;
    }
</style>
