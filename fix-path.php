<?php
// fix-paths.php - Placeres i roden
// Automatisk fix af billedstier i databasen

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Fix Billedstier</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #042940; border-bottom: 3px solid #9FC131; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success-box { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error-box { background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 5px; }
        button:hover { background: #45a049; }
        button.danger { background: #f44336; }
        button.danger:hover { background: #da190b; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #042940; color: white; }
    </style>
</head>
<body>
<div class="container">

<h1>🔧 Fix Billedstier i Database</h1>

<?php
$db = get_db_connection();
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Hvis der er en POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['create_backup'])) {
        // Opret backup
        $backup_table = "layout_bands_backup_" . date('Ymd_His');
        try {
            $db->exec("CREATE TABLE $backup_table AS SELECT * FROM layout_bands");
            echo "<div class='success-box'>✅ Backup oprettet: $backup_table</div>";
        } catch (Exception $e) {
            echo "<div class='error-box'>❌ Backup fejlede: " . $e->getMessage() . "</div>";
        }
    }
    
    if (isset($_POST['fix_paths'])) {
        echo "<div class='info-box'><h3>Kører fixes...</h3>";
        
        // Hent alle bånd
        $stmt = $db->query("SELECT * FROM layout_bands WHERE band_type IN ('slideshow', 'product')");
        $bands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fixed_count = 0;
        
        foreach ($bands as $band) {
            $content = json_decode($band['band_content'], true);
            $updated = false;
            
            // Process slideshow
            if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
                foreach ($content['slides'] as &$slide) {
                    if (!empty($slide['image'])) {
                        $original = $slide['image'];
                        $fixed = fix_image_path($original, $document_root);
                        
                        if ($original !== $fixed) {
                            echo "Slideshow ID " . $band['id'] . ": '$original' → '$fixed'<br>";
                            $slide['image'] = $fixed;
                            $updated = true;
                        }
                    }
                }
            }
            
            // Process product
            if ($band['band_type'] === 'product' && !empty($content['image'])) {
                $original = $content['image'];
                $fixed = fix_image_path($original, $document_root);
                
                if ($original !== $fixed) {
                    echo "Product ID " . $band['id'] . ": '$original' → '$fixed'<br>";
                    $content['image'] = $fixed;
                    $updated = true;
                }
            }
            
            // Update database if changed
            if ($updated) {
                $json = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $stmt = $db->prepare("UPDATE layout_bands SET band_content = ? WHERE id = ?");
                $stmt->execute([$json, $band['id']]);
                $fixed_count++;
            }
        }
        
        echo "</div>";
        
        if ($fixed_count > 0) {
            echo "<div class='success-box'>✅ Rettet $fixed_count bånd!</div>";
        } else {
            echo "<div class='info-box'>ℹ️ Ingen ændringer var nødvendige.</div>";
        }
    }
    
    if (isset($_POST['restore_backup'])) {
        $backup_table = $_POST['backup_table'];
        try {
            // Verificer at backup tabel findes
            $stmt = $db->query("SHOW TABLES LIKE '$backup_table'");
            if ($stmt->rowCount() > 0) {
                // Slet nuværende data og kopier fra backup
                $db->exec("TRUNCATE TABLE layout_bands");
                $db->exec("INSERT INTO layout_bands SELECT * FROM $backup_table");
                echo "<div class='success-box'>✅ Gendannet fra backup: $backup_table</div>";
            } else {
                echo "<div class='error-box'>❌ Backup tabel findes ikke: $backup_table</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error-box'>❌ Gendannelse fejlede: " . $e->getMessage() . "</div>";
        }
    }
}

// Funktion til at fixe en billedsti
function fix_image_path($path, $document_root) {
    // Fjern eventuelle dobbelte slashes
    $path = preg_replace('#/+#', '/', $path);
    
    // Fjern leading slash
    $path = ltrim($path, '/');
    
    // Fjern public/ prefix hvis den findes
    if (strpos($path, 'public/uploads/') === 0) {
        $path = substr($path, 7); // Fjern 'public/'
    }
    
    // Check om filen faktisk findes
    $possible_locations = [
        $document_root . '/' . $path,
        $document_root . '/public/' . $path,
    ];
    
    foreach ($possible_locations as $location) {
        if (file_exists($location)) {
            // Fundet! Returner den korrekte relative sti
            if (strpos($location, '/public/uploads/') !== false) {
                // Filen er i public/uploads, så stien skal være uploads/...
                return $path;
            }
        }
    }
    
    // Hvis ikke fundet, returner uploads/... format alligevel
    if (strpos($path, 'uploads/') !== 0) {
        $path = 'uploads/' . $path;
    }
    
    return $path;
}

// Vis nuværende status
echo "<h2>📊 Nuværende Status</h2>";

$stmt = $db->query("SELECT * FROM layout_bands WHERE band_type IN ('slideshow', 'product')");
$bands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$problems = [];

echo "<table>";
echo "<tr><th>ID</th><th>Type</th><th>Billede</th><th>Status</th><th>Problem</th></tr>";

foreach ($bands as $band) {
    $content = json_decode($band['band_content'], true);
    
    if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
        foreach ($content['slides'] as $index => $slide) {
            if (!empty($slide['image'])) {
                $status = check_image_exists($slide['image'], $document_root);
                echo "<tr>";
                echo "<td>" . $band['id'] . "</td>";
                echo "<td>slideshow #" . ($index + 1) . "</td>";
                echo "<td><small>" . htmlspecialchars($slide['image']) . "</small></td>";
                echo "<td>" . ($status['exists'] ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
                echo "<td>" . ($status['problem'] ?? '-') . "</td>";
                echo "</tr>";
                
                if (!$status['exists']) {
                    $problems[] = $band['id'];
                }
            }
        }
    }
    
    if ($band['band_type'] === 'product' && !empty($content['image'])) {
        $status = check_image_exists($content['image'], $document_root);
        echo "<tr>";
        echo "<td>" . $band['id'] . "</td>";
        echo "<td>product</td>";
        echo "<td><small>" . htmlspecialchars($content['image']) . "</small></td>";
        echo "<td>" . ($status['exists'] ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        echo "<td>" . ($status['problem'] ?? '-') . "</td>";
        echo "</tr>";
        
        if (!$status['exists']) {
            $problems[] = $band['id'];
        }
    }
}
echo "</table>";

function check_image_exists($path, $document_root) {
    $checks = [
        $document_root . '/' . $path,
        $document_root . '/' . ltrim($path, '/'),
        $document_root . '/public/' . $path,
        $document_root . '/public/' . ltrim($path, '/')
    ];
    
    foreach ($checks as $check) {
        if (file_exists($check)) {
            return ['exists' => true, 'found_at' => $check];
        }
    }
    
    // Check hvis filen findes med andet navn
    $basename = basename($path);
    $search_dirs = [
        $document_root . '/uploads',
        $document_root . '/public/uploads'
    ];
    
    foreach ($search_dirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->getFilename() === $basename) {
                    return [
                        'exists' => false, 
                        'problem' => 'Fil findes, men på forkert sti: ' . str_replace($document_root, '', $file->getPathname())
                    ];
                }
            }
        }
    }
    
    return ['exists' => false, 'problem' => 'Fil findes ikke'];
}

// Vis backups
echo "<h2>💾 Backups</h2>";
$stmt = $db->query("SHOW TABLES LIKE 'layout_bands_backup_%'");
$backups = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($backups)) {
    echo "<table>";
    echo "<tr><th>Backup Navn</th><th>Handling</th></tr>";
    foreach ($backups as $backup) {
        echo "<tr>";
        echo "<td>$backup</td>";
        echo "<td>";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='backup_table' value='$backup'>";
        echo "<button type='submit' name='restore_backup' class='danger' onclick='return confirm(\"Sikker på at du vil gendanne fra denne backup?\")'>Gendan</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ingen backups fundet.</p>";
}

// Vis handlinger
echo "<h2>🎯 Handlinger</h2>";

if (!empty($problems)) {
    echo "<div class='error-box'>";
    echo "<p><strong>⚠️ Der er " . count($problems) . " bånd med problemer!</strong></p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='create_backup'>💾 Opret Backup</button>";
    echo "<button type='submit' name='fix_paths' class='success'>🔧 Fix Billedstier</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div class='success-box'>";
    echo "<p><strong>✅ Alle billeder er OK!</strong></p>";
    echo "</div>";
}
?>

<div class="info-box">
    <h3>📝 Manuel SQL</h3>
    <p>Hvis automatisk fix ikke virker, kan du køre dette SQL direkte:</p>
    <pre>
-- Fix product bykort specifikt
UPDATE layout_bands 
SET band_content = JSON_SET(
    band_content,
    '$.image',
    'uploads/product/large/console-mapbox-com-studio-styles-anotherjensen-clbo39x7h001514rum2fhawnj-edit-test-1754214961.webp'
)
WHERE id = 3 AND band_type = 'product';
    </pre>
</div>

</div>
</body>
</html>
