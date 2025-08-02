<?php
/**
 * LATL Diagnostics Test Script
 * 
 * Dette script tester forskellige aspekter af LATL-systemet for at identificere fejl.
 * Upload denne fil til rodmappen på dit websted og kør den ved at besøge:
 * https://new.leatherandthelikes.dk/latl_test.php
 */

// Aktivér fejlrapportering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering for at undgå header-problemer
ob_start();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL Systemdiagnostik</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #042940;
            border-bottom: 2px solid #9FC131;
            padding-bottom: 10px;
        }
        h2 {
            color: #005C53;
            margin-top: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .warning {
            color: #FFC107;
            font-weight: bold;
        }
        .error {
            color: #F44336;
            font-weight: bold;
        }
        .info {
            color: #2196F3;
            font-weight: bold;
        }
        .test-result {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        .test-result.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #333;
        }
        .test-result.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #333;
        }
        .test-result.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: #333;
        }
        .test-result.info {
            background-color: rgba(33, 150, 243, 0.1);
            color: #333;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .code-block {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LATL Systemdiagnostik</h1>
        <p>Dette værktøj tester forskellige aspekter af dit LATL-system for at identificere eventuelle problemer.</p>

        <h2>1. PHP Information</h2>
        <?php
        // Test PHP-version
        $php_version = phpversion();
        $required_version = '7.4.0';
        
        echo '<div class="test-result ' . (version_compare($php_version, $required_version, '>=') ? 'success' : 'error') . '">';
        echo 'PHP Version: <span class="' . (version_compare($php_version, $required_version, '>=') ? 'success' : 'error') . '">' . $php_version . '</span>';
        
        if (version_compare($php_version, $required_version, '<')) {
            echo ' <span class="error">(Anbefalet: ' . $required_version . ' eller højere)</span>';
        }
        echo '</div>';
        
        // Test PHP extensions
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'gd', 'mbstring', 'fileinfo'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        
        echo '<div class="test-result ' . (empty($missing_extensions) ? 'success' : 'error') . '">';
        echo 'PHP Extensions: ';
        
        if (empty($missing_extensions)) {
            echo '<span class="success">Alle nødvendige extensions er installeret</span>';
        } else {
            echo '<span class="error">Manglende extensions: ' . implode(', ', $missing_extensions) . '</span>';
        }
        echo '</div>';
        
        // Check for GD support and info
        if (extension_loaded('gd')) {
            $gd_info = gd_info();
            echo '<div class="test-result success">';
            echo 'GD Version: <span class="success">' . $gd_info['GD Version'] . '</span><br>';
            echo 'FreeType Support: <span class="' . ($gd_info['FreeType Support'] ? 'success' : 'warning') . '">' . ($gd_info['FreeType Support'] ? 'Ja' : 'Nej') . '</span><br>';
            echo 'JPEG Support: <span class="' . ($gd_info['JPEG Support'] ? 'success' : 'warning') . '">' . ($gd_info['JPEG Support'] ? 'Ja' : 'Nej') . '</span><br>';
            echo 'PNG Support: <span class="' . ($gd_info['PNG Support'] ? 'success' : 'warning') . '">' . ($gd_info['PNG Support'] ? 'Ja' : 'Nej') . '</span><br>';
            echo 'WebP Support: <span class="' . (isset($gd_info['WebP Support']) && $gd_info['WebP Support'] ? 'success' : 'warning') . '">' . (isset($gd_info['WebP Support']) && $gd_info['WebP Support'] ? 'Ja' : 'Nej') . '</span>';
            echo '</div>';
        }
        ?>

        <h2>2. Filstruktur-kontrol</h2>
        <?php
        // Definer de forventede filer og mapper
        $expected_files = [
            'admin/band_editor.php',
            'admin/design-editor.php',
            'admin/index.php',
            'admin/layout-editor.php',
            'api/bands.php',
            'api/upload.php',
            'includes/band-renderer.php',
            'includes/config.php',
            'includes/db.php',
            'includes/image_handler.php',
            'public/js/main.js'
        ];
        
        $missing_files = [];
        $inaccessible_files = [];
        
        foreach ($expected_files as $file) {
            $file_path = dirname(__FILE__) . '/' . $file;
            
            if (!file_exists($file_path)) {
                $missing_files[] = $file;
            } elseif (!is_readable($file_path)) {
                $inaccessible_files[] = $file;
            }
        }
        
        echo '<div class="test-result ' . (empty($missing_files) && empty($inaccessible_files) ? 'success' : 'error') . '">';
        echo 'Filstruktur: ';
        
        if (empty($missing_files) && empty($inaccessible_files)) {
            echo '<span class="success">Alle nødvendige filer blev fundet og er tilgængelige</span>';
        } else {
            if (!empty($missing_files)) {
                echo '<span class="error">Manglende filer: ' . implode(', ', $missing_files) . '</span><br>';
            }
            
            if (!empty($inaccessible_files)) {
                echo '<span class="error">Ikke-læsbare filer: ' . implode(', ', $inaccessible_files) . '</span>';
            }
        }
        echo '</div>';
        
        // Check filrettigheder
        $writable_dirs = [
            'public/uploads',
            'public/uploads/slideshow',
            'public/uploads/product'
        ];
        
        $non_writable_dirs = [];
        
        foreach ($writable_dirs as $dir) {
            $dir_path = dirname(__FILE__) . '/' . $dir;
            
            if (!file_exists($dir_path)) {
                // Prøv at oprette mappen
                if (!@mkdir($dir_path, 0755, true)) {
                    $non_writable_dirs[] = $dir . ' (kan ikke oprettes)';
                }
            } elseif (!is_writable($dir_path)) {
                $non_writable_dirs[] = $dir;
            }
        }
        
        echo '<div class="test-result ' . (empty($non_writable_dirs) ? 'success' : 'error') . '">';
        echo 'Upload-mapper: ';
        
        if (empty($non_writable_dirs)) {
            echo '<span class="success">Alle upload-mapper er skrivbare</span>';
        } else {
            echo '<span class="error">Ikke-skrivbare mapper: ' . implode(', ', $non_writable_dirs) . '</span>';
        }
        echo '</div>';
        ?>

        <h2>3. Database-forbindelse</h2>
        <?php
        // Test database-forbindelse
        $db_config_exists = false;
        $db_config_readable = false;
        $db_connection = false;
        $db_error = '';
        
        // Check om config.php eksisterer
        $config_file = dirname(__FILE__) . '/includes/config.php';
        if (file_exists($config_file)) {
            $db_config_exists = true;
            
            if (is_readable($config_file)) {
                $db_config_readable = true;
                
                // Prøv at indlæse konfiguration og oprette forbindelse
                try {
                    require_once $config_file;
                    
                    // Tjek om nødvendige konstanter er defineret
                    $required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
                    $missing_constants = [];
                    
                    foreach ($required_constants as $const) {
                        if (!defined($const)) {
                            $missing_constants[] = $const;
                        }
                    }
                    
                    if (empty($missing_constants)) {
                        // Prøv at oprette forbindelse
                        try {
                            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                            $options = [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES => false,
                            ];
                            
                            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                            $db_connection = true;
                            
                            // Test at læse fra databasen
                            $stmt = $pdo->query("SELECT COUNT(*) FROM layout_bands");
                            $band_count = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM layout_config");
                            $config_count = $stmt->fetchColumn();
                        } catch (PDOException $e) {
                            $db_error = $e->getMessage();
                        }
                    } else {
                        $db_error = 'Manglende konstanter i config.php: ' . implode(', ', $missing_constants);
                    }
                } catch (Exception $e) {
                    $db_error = $e->getMessage();
                }
            } else {
                $db_error = 'Config-filen er ikke læsbar';
            }
        } else {
            $db_error = 'Config-filen blev ikke fundet';
        }
        
        echo '<div class="test-result ' . ($db_connection ? 'success' : 'error') . '">';
        echo 'Database-forbindelse: ';
        
        if ($db_connection) {
            echo '<span class="success">Forbundet til databasen</span><br>';
            echo 'Antal bånd i databasen: ' . $band_count . '<br>';
            echo 'Antal konfigurationer i databasen: ' . $config_count;
        } else {
            echo '<span class="error">Kunne ikke oprette forbindelse til databasen</span><br>';
            echo 'Fejl: ' . $db_error;
        }
        echo '</div>';
        ?>

        <h2>4. Klasse-kontrol</h2>
        <?php
        // Test om nøgleklasser er tilgængelige
        $classes_to_check = [
            'Database' => 'includes/db.php',
            'ImageHandler' => 'includes/image_handler.php'
        ];
        
        $missing_classes = [];
        $class_errors = [];
        
        foreach ($classes_to_check as $class => $file) {
            $file_path = dirname(__FILE__) . '/' . $file;
            
            if (file_exists($file_path) && is_readable($file_path)) {
                try {
                    require_once $file_path;
                    
                    if (!class_exists($class)) {
                        $missing_classes[] = $class;
                    }
                } catch (Exception $e) {
                    $class_errors[$class] = $e->getMessage();
                }
            } else {
                $class_errors[$class] = 'Fil ikke fundet eller ikke læsbar: ' . $file;
            }
        }
        
        echo '<div class="test-result ' . (empty($missing_classes) && empty($class_errors) ? 'success' : 'error') . '">';
        echo 'Klasse-kontrol: ';
        
        if (empty($missing_classes) && empty($class_errors)) {
            echo '<span class="success">Alle nødvendige klasser er tilgængelige</span>';
        } else {
            if (!empty($missing_classes)) {
                echo '<span class="error">Manglende klasser: ' . implode(', ', $missing_classes) . '</span><br>';
            }
            
            if (!empty($class_errors)) {
                echo '<span class="error">Klasse-fejl:</span><br>';
                foreach ($class_errors as $class => $error) {
                    echo '<span class="error">' . $class . ': ' . $error . '</span><br>';
                }
            }
        }
        echo '</div>';
        
        // Test Database-klassen specifikt hvis den findes
        if (class_exists('Database')) {
            try {
                $db = Database::getInstance();
                $test_query = $db->selectOne("SELECT 1 AS test");
                
                echo '<div class="test-result success">';
                echo 'Database-klasse: <span class="success">Fungerer korrekt</span>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="test-result error">';
                echo 'Database-klasse: <span class="error">Fejl: ' . $e->getMessage() . '</span>';
                echo '</div>';
            }
        }
        
        // Test ImageHandler-klassen specifikt hvis den findes
        if (class_exists('ImageHandler')) {
            try {
                $ih = new ImageHandler();
                
                echo '<div class="test-result success">';
                echo 'ImageHandler-klasse: <span class="success">Kan instantieres</span>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="test-result error">';
                echo 'ImageHandler-klasse: <span class="error">Fejl: ' . $e->getMessage() . '</span>';
                echo '</div>';
            }
        }
        ?>

        <h2>5. Fejlfinding af band_editor.php</h2>
        <?php
        // Tjek band_editor.php
        $band_editor_file = dirname(__FILE__) . '/admin/band_editor.php';
        $band_editor_content = '';
        
        if (file_exists($band_editor_file) && is_readable($band_editor_file)) {
            $band_editor_content = file_get_contents($band_editor_file);
            
            echo '<div class="test-result info">';
            echo 'band_editor.php: <span class="success">Filen findes og er læsbar</span><br>';
            echo 'Filstørrelse: ' . filesize($band_editor_file) . ' bytes<br>';
            
            // Tjek for almindelige fejl
            $errors = [];
            
            if (strpos($band_editor_content, '<?php') === false) {
                $errors[] = 'Manglende <?php tag';
            }
            
            if (preg_match('/require.*config\.php/', $band_editor_content) === 0) {
                $errors[] = 'Manglende require af config.php';
            }
            
            if (preg_match('/require.*db\.php/', $band_editor_content) === 0) {
                $errors[] = 'Manglende require af db.php';
            }
            
            if (strpos($band_editor_content, 'session_start') === false) {
                $errors[] = 'Manglende session_start()';
            }
            
            if (empty($errors)) {
                echo '<span class="success">Ingen almindelige syntaksfejl fundet</span>';
            } else {
                echo '<span class="error">Potentielle problemer:</span><br>';
                foreach ($errors as $error) {
                    echo '- ' . $error . '<br>';
                }
            }
            
            echo '</div>';
            
            // Vis de første 200 tegn af filen
            echo '<div class="code-block">';
            echo htmlspecialchars(substr($band_editor_content, 0, 200)) . '...';
            echo '</div>';
        } else {
            echo '<div class="test-result error">';
            echo 'band_editor.php: <span class="error">Filen blev ikke fundet eller er ikke læsbar</span>';
            echo '</div>';
        }
        ?>

        <h2>6. Fejlfinding af design-editor.php</h2>
        <?php
        // Tjek design-editor.php
        $design_editor_file = dirname(__FILE__) . '/admin/design-editor.php';
        $design_editor_content = '';
        
        if (file_exists($design_editor_file) && is_readable($design_editor_file)) {
            $design_editor_content = file_get_contents($design_editor_file);
            
            echo '<div class="test-result info">';
            echo 'design-editor.php: <span class="success">Filen findes og er læsbar</span><br>';
            echo 'Filstørrelse: ' . filesize($design_editor_file) . ' bytes<br>';
            
            // Tjek for almindelige fejl
            $errors = [];
            
            if (strpos($design_editor_content, '<?php') === false) {
                $errors[] = 'Manglende <?php tag';
            }
            
            if (preg_match('/require.*config\.php/', $design_editor_content) === 0) {
                $errors[] = 'Manglende require af config.php';
            }
            
            if (preg_match('/require.*db\.php/', $design_editor_content) === 0) {
                $errors[] = 'Manglende require af db.php';
            }
            
            if (strpos($design_editor_content, 'session_start') === false) {
                $errors[] = 'Manglende session_start()';
            }
            
            if (empty($errors)) {
                echo '<span class="success">Ingen almindelige syntaksfejl fundet</span>';
            } else {
                echo '<span class="error">Potentielle problemer:</span><br>';
                foreach ($errors as $error) {
                    echo '- ' . $error . '<br>';
                }
            }
            
            echo '</div>';
            
            // Vis de første 200 tegn af filen
            echo '<div class="code-block">';
            echo htmlspecialchars(substr($design_editor_content, 0, 200)) . '...';
            echo '</div>';
        } else {
            echo '<div class="test-result error">';
            echo 'design-editor.php: <span class="error">Filen blev ikke fundet eller er ikke læsbar</span>';
            echo '</div>';
        }
        ?>

        <h2>7. PHP Lint-check</h2>
        <?php
        // Kør PHP lint på de vigtigste filer
        $files_to_lint = [
            'admin/band_editor.php',
            'admin/design-editor.php',
            'includes/db.php',
            'includes/image_handler.php',
            'api/upload.php'
        ];
        
        $lint_errors = [];
        
        foreach ($files_to_lint as $file) {
            $file_path = dirname(__FILE__) . '/' . $file;
            
            if (file_exists($file_path) && is_readable($file_path)) {
                $output = [];
                $return_var = 0;
                
                exec('php -l ' . escapeshellarg($file_path) . ' 2>&1', $output, $return_var);
                
                if ($return_var !== 0) {
                    $lint_errors[$file] = implode("\n", $output);
                }
            } else {
                $lint_errors[$file] = 'Filen blev ikke fundet eller er ikke læsbar';
            }
        }
        
        echo '<div class="test-result ' . (empty($lint_errors) ? 'success' : 'error') . '">';
        echo 'PHP Lint-check: ';
        
        if (empty($lint_errors)) {
            echo '<span class="success">Alle filer er syntaktisk korrekte</span>';
        } else {
            echo '<span class="error">Syntaksfejl fundet:</span><br>';
            
            foreach ($lint_errors as $file => $error) {
                echo '<strong>' . $file . ':</strong><br>';
                echo '<pre>' . htmlspecialchars($error) . '</pre>';
            }
        }
        echo '</div>';
        ?>

        <h2>8. Anbefalinger og løsningsforslag</h2>
        <div class="test-result info">
            <?php
            $issues_found = false;
            
            // Oplist fundne problemer og giv løsningsforslag
            echo '<h3>Problemer og løsninger:</h3>';
            echo '<ul>';
            
            if (!empty($missing_extensions)) {
                $issues_found = true;
                echo '<li><span class="error">Manglende PHP-udvidelser:</span> ' . implode(', ', $missing_extensions) . '<br>';
                echo '<strong>Løsning:</strong> Installer de manglende PHP-udvidelser via din hosting-kontrollpanel eller kontakt din host.</li>';
            }
            
            if (!empty($missing_files)) {
                $issues_found = true;
                echo '<li><span class="error">Manglende filer:</span> ' . implode(', ', $missing_files) . '<br>';
                echo '<strong>Løsning:</strong> Upload de manglende filer til serveren.</li>';
            }
            
            if (!empty($inaccessible_files)) {
                $issues_found = true;
                echo '<li><span class="error">Ikke-læsbare filer:</span> ' . implode(', ', $inaccessible_files) . '<br>';
                echo '<strong>Løsning:</strong> Ændr filrettighederne til 644 for PHP-filer og 755 for mapper.</li>';
            }
            
            if (!empty($non_writable_dirs)) {
                $issues_found = true;
                echo '<li><span class="error">Ikke-skrivbare mapper:</span> ' . implode(', ', $non_writable_dirs) . '<br>';
                echo '<strong>Løsning:</strong> Ændr mapperettighederne til 755 eller 775.</li>';
            }
            
            if ($db_config_exists && $db_config_readable && !$db_connection) {
                $issues_found = true;
                echo '<li><span class="error">Database-forbindelsesfejl:</span> ' . $db_error . '<br>';
                echo '<strong>Løsning:</strong> Kontroller dine databaseindstillinger i config.php.</li>';
            }
            
            if (!empty($missing_classes) || !empty($class_errors)) {
                $issues_found = true;
                echo '<li><span class="error">Problemer med klasser:</span><br>';
                
                if (!empty($missing_classes)) {
                    echo '- Manglende klasser: ' . implode(', ', $missing_classes) . '<br>';
                }
                
                if (!empty($class_errors)) {
                    echo '- Fejl ved indlæsning af klasser:<br>';
                    foreach ($class_errors as $class => $error) {
                        echo '&nbsp;&nbsp;' . $class . ': ' . $error . '<br>';
                    }
                }
                
                echo '<strong>Løsning:</strong> Kontroller at filerne er korrekt uploadet og ikke er blevet beskadiget.</li>';
            }
            
            if (!empty($lint_errors)) {
                $issues_found = true;
                echo '<li><span class="error">PHP syntaksfejl:</span><br>';
                
                foreach ($lint_errors as $file => $error) {
                    echo '- ' . $file . ': ' . str_replace("\n", '<br>', htmlspecialchars($error)) . '<br>';
                }
                
                echo '<strong>Løsning:</strong> Ret syntaksfejlene i de pågældende filer.</li>';
            }
            
            if (!$issues_found) {
                echo '<li><span class="success">Ingen kritiske problemer blev fundet!</span><br>';
                echo 'Hvis du stadig oplever fejl, kan det skyldes problemer med:</li>';
                echo '<li>JavaScript-fejl i browseren (tjek browser-konsollen)</li>';
                echo '<li>Server-konfiguration (f.eks. PHP-indstillinger)</li>';
                echo '<li>Inkompatible filversioner</li>';
            }
            
            echo '</ul>';
            
            // Simpel implementering
            echo '<h3>Simpel implementering:</h3>';
            echo '<p>Her er en meget enkel version af band_editor.php, som du kan prøve at uploade og teste:</p>';
            
            $simple_band_editor = '<?php
// Aktivér fejlvisning
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);

require_once __DIR__ . \'/../includes/config.php\';
require_once __DIR__ . \'/../includes/db.php\';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION[\'admin_authenticated\']) || $_SESSION[\'admin_authenticated\'] !== true) {
    header(\'Location: index.php\');
    exit;
}

// Hent bånd-data
$page_id = isset($_GET[\'page\']) ? $_GET[\'page\'] : \'forside\';
$edit_id = isset($_GET[\'edit\']) ? (int)$_GET[\'edit\'] : null;
$band_data = null;

if ($edit_id) {
    $db = Database::getInstance();
    $band = $db->selectOne("SELECT * FROM layout_bands WHERE id = ?", [$edit_id]);
    
    if ($band) {
        $band_data = $band;
        $band_data[\'band_content\'] = json_decode($band_data[\'band_content\'], true);
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simpel Bånd-editor</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #042940; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simpel Bånd-editor</h1>
        
        <?php if ($band_data): ?>
        <form method="post" action="band_editor.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="page_id" value="<?= htmlspecialchars($page_id) ?>">
            <input type="hidden" name="band_id" value="<?= $band_data[\'id\'] ?>">
            <input type="hidden" name="band_type" value="<?= htmlspecialchars($band_data[\'band_type\']) ?>">
            
            <div class="form-group">
                <label>Bånd-type:</label>
                <strong><?= htmlspecialchars($band_data[\'band_type\']) ?></strong>
            </div>
            
            <div class="form-group">
                <label for="band_height">Højde (1-4):</label>
                <input type="number" id="band_height" name="band_height" min="1" max="4" value="<?= $band_data[\'band_height\'] ?>">
            </div>
            
            <div class="form-group">
                <label for="band_content">Indhold (JSON):</label>
                <textarea id="band_content" name="band_content" rows="10"><?= htmlspecialchars(json_encode($band_data[\'band_content\'], JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit">Gem ændringer</button>
                <a href="layout-editor.php?page=<?= urlencode($page_id) ?>">Annuller</a>
            </div>
        </form>
        <?php else: ?>
        <p>Intet bånd fundet med ID <?= $edit_id ?>.</p>
        <a href="layout-editor.php?page=<?= urlencode($page_id) ?>">Tilbage til layout-editor</a>
        <?php endif; ?>
    </div>
</body>
</html>';
            
            echo '<div class="code-block">';
            echo htmlspecialchars($simple_band_editor);
            echo '</div>';
            
            echo '<p>Du kan kopiere denne kode og uploade den som <code>admin/simple_band_editor.php</code>, og derefter tilgå den via:<br>';
            echo '<code>https://new.leatherandthelikes.dk/admin/simple_band_editor.php?page=forside&edit=1</code></p>';
            ?>
        </div>

        <h2>9. Upload-test</h2>
        <?php
        // Test om filupload fungerer
        $upload_dir = dirname(__FILE__) . '/public/uploads/test';
        $upload_successful = false;
        $upload_error = '';
        
        // Forsøg at oprette testmappe
        if (!file_exists($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, true)) {
                $upload_error = 'Kunne ikke oprette test-mappe';
            }
        }
        
        if (empty($upload_error)) {
            // Forsøg at oprette testfil
            $test_file = $upload_dir . '/test.txt';
            $content = 'Dette er en testfil oprettet ' . date('Y-m-d H:i:s');
            
            if (file_put_contents($test_file, $content) === false) {
                $upload_error = 'Kunne ikke skrive til testfil';
            } else {
                $upload_successful = true;
                
                // Ryd op efter os selv
                @unlink($test_file);
                @rmdir($upload_dir);
            }
        }
        
        echo '<div class="test-result ' . ($upload_successful ? 'success' : 'error') . '">';
        echo 'Upload-test: ';
        
        if ($upload_successful) {
            echo '<span class="success">Filupload fungerer korrekt</span>';
        } else {
            echo '<span class="error">Filupload fejlede: ' . $upload_error . '</span>';
        }
        echo '</div>';
        ?>

        <h2>10. Webserver-information</h2>
        <?php
        echo '<div class="test-result info">';
        echo 'Server software: ' . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Ukendt') . '<br>';
        echo 'Document root: ' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Ukendt') . '<br>';
        echo 'Script filename: ' . htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'Ukendt') . '<br>';
        echo 'Server name: ' . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Ukendt') . '<br>';
        echo 'Script path: ' . dirname(__FILE__);
        echo '</div>';
        ?>
    </div>
</body>
</html>
<?php
// End output buffering and send output
ob_end_flush();
?>
