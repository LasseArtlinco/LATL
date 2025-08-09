<?php
// ========================================
// public/debug-images.php - Test billedstier
// ========================================
// Denne fil skal ligge i /public/ mappen

// Inkluder filer fra parent directory
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/band-renderer.php';

// Set debug mode
define('DEBUG_MODE', true);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL.dk - Debug Billedstier</title>
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
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .warning {
            color: orange;
            font-weight: bold;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .error-box {
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #f44336;
        }
        .success-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #042940;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>

<h1>🔍 LATL.dk - Debug Billedstier</h1>

<div class="info-box">
    <strong>ℹ️ Debug Information</strong><br>
    Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
    Current File: <?php echo __FILE__; ?><br>
    PHP Version: <?php echo phpversion(); ?><br>
    Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
</div>

<?php
try {
    // Test database connection
    $db = get_db_connection();
    echo '<div class="success-box">✅ Database forbindelse OK</div>';
    
    // Hent bånd data
    $bands = get_page_bands('forside');
    
    if (empty($bands)) {
        echo '<div class="error-box">⚠️ Ingen bånd fundet for forsiden!</div>';
    } else {
        echo '<div class="success-box">✅ Fundet ' . count($bands) . ' bånd</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error-box">❌ Database fejl: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $bands = [];
}
?>

<h2>📊 Bånd Data fra Database</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Højde</th>
            <th>Billeder</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bands as $band): ?>
        <?php 
            $content = is_array($band['band_content']) ? $band['band_content'] : json_decode($band['band_content'], true);
            $images = [];
            $status = '✅ OK';
            
            if ($band['band_type'] === 'slideshow' && isset($content['slides'])) {
                foreach ($content['slides'] as $slide) {
                    if (!empty($slide['image'])) {
                        $images[] = $slide['image'];
                    }
                }
            } elseif ($band['band_type'] === 'product' && !empty($content['image'])) {
                $images[] = $content['image'];
            }
            
            if (empty($images)) {
                $status = '⚠️ Ingen billeder';
            }
        ?>
        <tr>
            <td><?php echo $band['id']; ?></td>
            <td><?php echo htmlspecialchars($band['band_type']); ?></td>
            <td><?php echo $band['band_height']; ?></td>
            <td><?php echo count($images); ?></td>
            <td><?php echo $status; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>🖼️ Detaljeret Billede Analyse</h2>
<?php
$document_root = $_SERVER['DOCUMENT_ROOT'];
$all_images_found = true;

foreach ($bands as $band):
    $content = is_array($band['band_content']) ? $band['band_content'] : json_decode($band['band_content'], true);
    
    if ($band['band_type'] === 'slideshow' && isset($content['slides'])):
?>
    <h3>Slideshow (ID: <?php echo $band['id']; ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>Slide</th>
                <th>Titel</th>
                <th>Original Sti</th>
                <th>Formateret Sti</th>
                <th>Eksisterer?</th>
                <th>Størrelse</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($content['slides'] as $index => $slide):
                if (!empty($slide['image'])):
                    $original = $slide['image'];
                    $formatted = format_image_path($original);
                    $full_path = $document_root . $formatted;
                    $exists = file_exists($full_path);
                    
                    if (!$exists) {
                        $all_images_found = false;
                        // Prøv alternative stier
                        $alternatives = [
                            $document_root . '/public' . $formatted,
                            $document_root . '/' . $original,
                            $document_root . '/public/' . $original
                        ];
                        
                        foreach ($alternatives as $alt) {
                            if (file_exists($alt)) {
                                $full_path = $alt;
                                $exists = true;
                                $formatted = str_replace($document_root, '', $alt);
                                break;
                            }
                        }
                    }
            ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($slide['title'] ?? 'Ingen'); ?></td>
                <td><small><?php echo htmlspecialchars($original); ?></small></td>
                <td><small><?php echo htmlspecialchars($formatted); ?></small></td>
                <td>
                    <?php if ($exists): ?>
                        <span class="success">✅ Ja</span>
                    <?php else: ?>
                        <span class="error">❌ Nej</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    if ($exists) {
                        echo number_format(filesize($full_path) / 1024, 2) . ' KB';
                        list($w, $h) = @getimagesize($full_path);
                        if ($w && $h) echo "<br><small>{$w}x{$h}px</small>";
                    }
                    ?>
                </td>
            </tr>
            <?php 
                endif;
            endforeach; 
            ?>
        </tbody>
    </table>
<?php
    elseif ($band['band_type'] === 'product' && !empty($content['image'])):
        $original = $content['image'];
        $formatted = format_image_path($original);
        $full_path = $document_root . $formatted;
        $exists = file_exists($full_path);
        
        if (!$exists) {
            $all_images_found = false;
        }
?>
    <h3>Product: <?php echo htmlspecialchars($content['title'] ?? 'Uden titel'); ?> (ID: <?php echo $band['id']; ?>)</h3>
    <table>
        <tr>
            <th>Original Sti</th>
            <td><small><?php echo htmlspecialchars($original); ?></small></td>
        </tr>
        <tr>
            <th>Formateret Sti</th>
            <td><small><?php echo htmlspecialchars($formatted); ?></small></td>
        </tr>
        <tr>
            <th>Eksisterer?</th>
            <td>
                <?php if ($exists): ?>
                    <span class="success">✅ Ja</span>
                <?php else: ?>
                    <span class="error">❌ Nej</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($exists): ?>
        <tr>
            <th>Størrelse</th>
            <td><?php echo number_format(filesize($full_path) / 1024, 2); ?> KB</td>
        </tr>
        <?php endif; ?>
    </table>
<?php
    endif;
endforeach;
?>

<?php if (!$all_images_found): ?>
<div class="error-box">
    <h3>⚠️ Nogle billeder kunne ikke findes!</h3>
    <p>Dette kan skyldes forkerte stier i databasen. Brug fix-knappen nedenfor for at rette det.</p>
</div>
<?php else: ?>
<div class="success-box">
    <h3>✅ Alle billeder blev fundet!</h3>
</div>
<?php endif; ?>

<h2>📁 Uploads Mappe Struktur</h2>
<pre>
<?php
function scan_dir_tree($dir, $prefix = '') {
    if (!is_dir($dir)) {
        echo "❌ Mappen findes ikke: $dir\n";
        return;
    }
    
    $items = scandir($dir);
    $dirs = [];
    $files = [];
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $dirs[] = $item;
        } else {
            $files[] = $item;
        }
    }
    
    // Vis mapper først
    foreach ($dirs as $i => $dirname) {
        $isLast = ($i === count($dirs) - 1 && empty($files));
        echo $prefix . ($isLast ? '└── ' : '├── ') . "📁 <strong>$dirname/</strong>\n";
        $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
        scan_dir_tree($dir . '/' . $dirname, $newPrefix);
    }
    
    // Vis filer
    foreach ($files as $i => $filename) {
        $isLast = ($i === count($files) - 1);
        $size = number_format(filesize($dir . '/' . $filename) / 1024, 1);
        echo $prefix . ($isLast ? '└── ' : '├── ') . "📄 $filename ({$size} KB)\n";
    }
}

// Find uploads mappen
$possible_paths = [
    $document_root . '/uploads',
    $document_root . '/public/uploads',
    __DIR__ . '/uploads',
    dirname(__DIR__) . '/public/uploads'
];

$uploads_path = null;
foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        $uploads_path = $path;
        break;
    }
}

if ($uploads_path) {
    echo "📁 Uploads mappe: $uploads_path\n\n";
    scan_dir_tree($uploads_path);
} else {
    echo "❌ Kunne ikke finde uploads mappen!\n";
    echo "Prøvede følgende steder:\n";
    foreach ($possible_paths as $path) {
        echo "  - $path\n";
    }
}
?>
</pre>

<h2>🔧 Database Fixes</h2>

<div class="info-box">
    <h3>SQL Kommandoer til at rette billedstier:</h3>
    <pre style="background: white;">
-- Backup først!
CREATE TABLE IF NOT EXISTS layout_bands_backup_<?php echo date('Ymd'); ?> AS SELECT * FROM layout_bands;

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

<?php if (!$all_images_found): ?>
<div class="error-box">
    <h3>🔧 Automatisk Fix</h3>
    <form method="post" action="">
        <p>Dette vil automatisk rette billedstier i databasen.</p>
        <button type="submit" name="fix_paths" onclick="return confirm('Er du sikker? Dette vil ændre databasen!');">
            🔧 Kør Automatisk Fix
        </button>
    </form>
</div>
<?php endif; ?>

<?php
// Håndter automatisk fix
if (isset($_POST['fix_paths'])) {
    echo "<div class='info-box'>";
    echo "<h3>Kører fixes...</h3>";
    
    try {
        $db = get_db_connection();
        
        // Backup først
        $backup_table = "layout_bands_backup_" . date('Ymd_His');
        $db->exec("CREATE TABLE IF NOT EXISTS $backup_table AS SELECT * FROM layout_bands");
        echo "✅ Backup oprettet: $backup_table<br>";
        
        // Fix paths
        $queries = [
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"public/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')",
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"/public/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')",
            "UPDATE layout_bands SET band_content = REPLACE(band_content, '\"image\": \"/uploads/', '\"image\": \"uploads/') WHERE band_type IN ('slideshow', 'product')"
        ];
        
        $total_affected = 0;
        foreach ($queries as $i => $query) {
            $affected = $db->exec($query);
            echo "✅ Query " . ($i + 1) . ": Opdateret $affected rækker<br>";
            $total_affected += $affected;
        }
        
        if ($total_affected > 0) {
            echo "<br><strong class='success'>✅ Fixes anvendt! $total_affected rækker blev opdateret.</strong><br>";
            echo "<a href='javascript:window.location.reload()'>🔄 Genindlæs siden for at se ændringerne</a>";
        } else {
            echo "<br><strong class='warning'>⚠️ Ingen ændringer var nødvendige.</strong>";
        }
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ Fejl: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    
    echo "</div>";
}
?>

<h2>🧪 Test format_image_path() Funktion</h2>
<table>
    <thead>
        <tr>
            <th>Input</th>
            <th>Output</th>
            <th>Korrekt?</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $test_cases = [
            'uploads/slideshow/large/test.webp' => '/uploads/slideshow/large/test.webp',
            '/uploads/slideshow/large/test.webp' => '/uploads/slideshow/large/test.webp',
            'public/uploads/slideshow/large/test.webp' => '/uploads/slideshow/large/test.webp',
            '/public/uploads/slideshow/large/test.webp' => '/uploads/slideshow/large/test.webp',
            'placeholder-product.png' => '/placeholder-product.png',
        ];
        
        foreach ($test_cases as $input => $expected) {
            $output = format_image_path($input);
            $correct = ($output === $expected);
        ?>
        <tr>
            <td><code><?php echo htmlspecialchars($input); ?></code></td>
            <td><code><?php echo htmlspecialchars($output); ?></code></td>
            <td>
                <?php if ($correct): ?>
                    <span class="success">✅ Korrekt</span>
                <?php else: ?>
                    <span class="error">❌ Forkert (forventet: <?php echo htmlspecialchars($expected); ?>)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<div class="success-box">
    <h3>📝 Næste skridt:</h3>
    <ol>
        <li>Hvis der er røde ❌ markeringer ovenfor, kør "Automatisk Fix"</li>
        <li>Tjek at alle billeder nu vises korrekt</li>
        <li>Test upload af et nyt billede</li>
        <li>Slet denne debug-fil når alt virker!</li>
    </ol>
</div>

</body>
</html>
