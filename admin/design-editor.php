<?php
// admin/design-editor.php - Editor til global styling, farveskema og typografi
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Starter session hvis den ikke allerede er startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check om brugeren er logget ind
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Håndter opdatering af design
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_design') {
        // Indsaml farveskema
        $colorPalette = [
            'text' => $_POST['color_text'] ?? '#042940',
            'accent' => $_POST['color_accent'] ?? '#9FC131',
            'bright' => $_POST['color_bright'] ?? '#DBF227',
            'primary' => $_POST['color_primary'] ?? '#042940',
            'secondary' => $_POST['color_secondary'] ?? '#005C53',
            'background' => $_POST['color_background'] ?? '#D6D58E'
        ];
        
        // Indsaml skrifttyper
        $fontConfig = [
            'body' => [
                'font-family' => $_POST['font_body'] ?? 'Open Sans, sans-serif',
                'font-weight' => $_POST['font_body_weight'] ?? '400'
            ],
            'heading' => [
                'font-family' => $_POST['font_heading'] ?? 'Allerta Stencil, sans-serif',
                'font-weight' => $_POST['font_heading_weight'] ?? '400'
            ],
            'button' => [
                'font-family' => $_POST['font_button'] ?? 'Open Sans, sans-serif',
                'font-weight' => $_POST['font_button_weight'] ?? '600'
            ],
            'price' => [
                'font-family' => $_POST['font_price'] ?? 'Allerta Stencil, sans-serif',
                'font-weight' => $_POST['font_price_weight'] ?? '400'
            ]
        ];
        
        // Indsaml globale CSS regler
        $globalStyles = [
            'css' => $_POST['global_css'] ?? ''
        ];
        
        // Opdater databasen
        $db = Database::getInstance();
        $db->update(
            'layout_config',
            [
                'color_palette' => json_encode($colorPalette),
                'font_config' => json_encode($fontConfig),
                'global_styles' => json_encode($globalStyles)
            ],
            'page_id = ?',
            ['global']
        );
        
        // Generer og gem CSS fil
        generate_css($colorPalette, $fontConfig, $globalStyles);
        
        // Redirect med succes-besked
        header('Location: design-editor.php?success=design_saved');
        exit;
    }
}

// Hent aktuelle design-indstillinger
$globalStyles = get_global_styles();
$colorPalette = $globalStyles['color_palette'];
$fontConfig = $globalStyles['font_config'];
$css = $globalStyles['global_styles']['css'] ?? '';

// Hjælpefunktion til at generere farveværktøj
function color_picker($name, $value = '', $label = '') {
    $id = str_replace(['[', ']'], '_', $name);
    $html = '<div class="form-group">';
    if ($label) {
        $html .= '<label for="' . $id . '">' . htmlspecialchars($label) . ':</label>';
    }
    $html .= '<div class="color-picker-wrapper">';
    $html .= '<input type="text" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($value) . '" class="color-input" placeholder="#RRGGBB">';
    $html .= '<input type="color" class="color-picker" data-target="' . $id . '" value="' . htmlspecialchars($value ?: '#ffffff') . '">';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

// Hjælpefunktion til at genere og gemme CSS-fil baseret på design-indstillinger
function generate_css($colorPalette, $fontConfig, $globalStyles) {
    $css = ":root {\n";
    
    // Farver
    foreach ($colorPalette as $key => $value) {
        $css .= "    --color-" . $key . ": " . $value . ";\n";
    }
    
    // Skrifttyper
    foreach ($fontConfig as $key => $values) {
        $css .= "    --font-" . $key . ": " . $values['font-family'] . ";\n";
        $css .= "    --font-" . $key . "-weight: " . $values['font-weight'] . ";\n";
    }
    
    $css .= "}\n\n";
    
    // Grundlæggende typografi
    $css .= "body {\n";
    $css .= "    font-family: var(--font-body);\n";
    $css .= "    font-weight: var(--font-body-weight);\n";
    $css .= "    color: var(--color-text);\n";
    $css .= "    background-color: var(--color-background);\n";
    $css .= "    line-height: 1.6;\n";
    $css .= "    margin: 0;\n";
    $css .= "    padding: 0;\n";
    $css .= "}\n\n";
    
    $css .= "h1, h2, h3, h4, h5, h6 {\n";
    $css .= "    font-family: var(--font-heading);\n";
    $css .= "    font-weight: var(--font-heading-weight);\n";
    $css .= "    color: var(--color-primary);\n";
    $css .= "    margin-top: 0;\n";
    $css .= "}\n\n";
    
    $css .= "a {\n";
    $css .= "    color: var(--color-accent);\n";
    $css .= "    text-decoration: none;\n";
    $css .= "}\n\n";
    
    $css .= "a:hover {\n";
    $css .= "    color: var(--color-secondary);\n";
    $css .= "}\n\n";
    
    $css .= ".button {\n";
    $css .= "    display: inline-block;\n";
    $css .= "    font-family: var(--font-button);\n";
    $css .= "    font-weight: var(--font-button-weight);\n";
    $css .= "    background-color: var(--color-accent);\n";
    $css .= "    color: white;\n";
    $css .= "    padding: 10px 20px;\n";
    $css .= "    border-radius: 4px;\n";
    $css .= "    text-decoration: none;\n";
    $css .= "    transition: all 0.3s ease;\n";
    $css .= "}\n\n";
    
    $css .= ".button:hover {\n";
    $css .= "    background-color: var(--color-secondary);\n";
    $css .= "    transform: translateY(-2px);\n";
    $css .= "    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\n";
    $css .= "}\n\n";
    
    $css .= ".container {\n";
    $css .= "    max-width: 1200px;\n";
    $css .= "    margin: 0 auto;\n";
    $css .= "    padding: 0 15px;\n";
    $css .= "}\n\n";
    
    // Bånd-styling
    $css .= ".band {\n";
    $css .= "    width: 100%;\n";
    $css .= "    padding: 0;\n";
    $css .= "    margin: 0;\n";
    $css .= "    overflow: hidden;\n";
    $css .= "}\n\n";
    
    $css .= ".band-height-1 {\n";
    $css .= "    padding: 30px 0;\n";
    $css .= "}\n\n";
    
    $css .= ".band-height-2 {\n";
    $css .= "    padding: 60px 0;\n";
    $css .= "}\n\n";
    
    $css .= ".band-height-3 {\n";
    $css .= "    padding: 90px 0;\n";
    $css .= "}\n\n";
    
    $css .= ".band-height-4 {\n";
    $css .= "    min-height: 100vh;\n";
    $css .= "    display: flex;\n";
    $css .= "    align-items: center;\n";
    $css .= "}\n\n";
    
    // Slideshow styling
    $css .= ".slideshow {\n";
    $css .= "    position: relative;\n";
    $css .= "    width: 100%;\n";
    $css .= "    overflow: hidden;\n";
    $css .= "}\n\n";
    
    $css .= ".slides {\n";
    $css .= "    display: flex;\n";
    $css .= "    transition: transform 0.5s ease-in-out;\n";
    $css .= "}\n\n";
    
    $css .= ".slide {\n";
    $css .= "    flex: 0 0 100%;\n";
    $css .= "    position: relative;\n";
    $css .= "}\n\n";
    
    $css .= ".slide img {\n";
    $css .= "    width: 100%;\n";
    $css .= "    height: auto;\n";
    $css .= "    display: block;\n";
    $css .= "}\n\n";
    
    $css .= ".slide-content {\n";
    $css .= "    position: absolute;\n";
    $css .= "    bottom: 0;\n";
    $css .= "    left: 0;\n";
    $css .= "    right: 0;\n";
    $css .= "    padding: 20px;\n";
    $css .= "    background: rgba(0, 0, 0, 0.5);\n";
    $css .= "    color: white;\n";
    $css .= "}\n\n";
    
    $css .= ".slideshow-nav {\n";
    $css .= "    display: flex;\n";
    $css .= "    justify-content: space-between;\n";
    $css .= "    align-items: center;\n";
    $css .= "    position: absolute;\n";
    $css .= "    bottom: 10px;\n";
    $css .= "    left: 0;\n";
    $css .= "    right: 0;\n";
    $css .= "    padding: 0 20px;\n";
    $css .= "}\n\n";
    
    $css .= ".indicators {\n";
    $css .= "    display: flex;\n";
    $css .= "    justify-content: center;\n";
    $css .= "}\n\n";
    
    $css .= ".indicator {\n";
    $css .= "    width: 12px;\n";
    $css .= "    height: 12px;\n";
    $css .= "    margin: 0 5px;\n";
    $css .= "    border-radius: 50%;\n";
    $css .= "    background-color: rgba(255, 255, 255, 0.5);\n";
    $css .= "    border: none;\n";
    $css .= "    cursor: pointer;\n";
    $css .= "    transition: background-color 0.3s;\n";
    $css .= "}\n\n";
    
    $css .= ".indicator.active {\n";
    $css .= "    background-color: white;\n";
    $css .= "}\n\n";
    
    $css .= ".prev, .next {\n";
    $css .= "    background: none;\n";
    $css .= "    border: none;\n";
    $css .= "    color: white;\n";
    $css .= "    font-size: 1.5rem;\n";
    $css .= "    cursor: pointer;\n";
    $css .= "    transition: opacity 0.3s;\n";
    $css .= "}\n\n";
    
    $css .= ".prev:hover, .next:hover {\n";
    $css .= "    opacity: 0.8;\n";
    $css .= "}\n\n";
    
    // Produkt-bånd styling
    $css .= ".product-band {\n";
    $css .= "    width: 100%;\n";
    $css .= "}\n\n";
    
    $css .= ".product-inner {\n";
    $css .= "    display: flex;\n";
    $css .= "    align-items: center;\n";
    $css .= "    flex-wrap: wrap;\n";
    $css .= "}\n\n";
    
    $css .= ".product-image {\n";
    $css .= "    flex: 0 0 50%;\n";
    $css .= "    padding: 20px;\n";
    $css .= "    text-align: center;\n";
    $css .= "}\n\n";
    
    $css .= ".product-image img {\n";
    $css .= "    max-width: 100%;\n";
    $css .= "    height: auto;\n";
    $css .= "}\n\n";
    
    $css .= ".product-content {\n";
    $css .= "    flex: 0 0 50%;\n";
    $css .= "    padding: 20px;\n";
    $css .= "}\n\n";
    
    $css .= ".product-title {\n";
    $css .= "    margin-top: 0;\n";
    $css .= "    color: var(--color-primary);\n";
    $css .= "}\n\n";
    
    $css .= ".product-subtitle {\n";
    $css .= "    margin-bottom: 20px;\n";
    $css .= "}\n\n";
    
    $css .= ".product-cta {\n";
    $css .= "    margin-top: 20px;\n";
    $css .= "}\n\n";
    
    $css .= ".product-link {\n";
    $css .= "    display: flex;\n";
    $css .= "    text-decoration: none;\n";
    $css .= "    color: inherit;\n";
    $css .= "}\n\n";
    
    // Responsivt design
    $css .= "@media (max-width: 768px) {\n";
    $css .= "    .product-image,\n";
    $css .= "    .product-content {\n";
    $css .= "        flex: 0 0 100%;\n";
    $css .= "    }\n";
    $css .= "}\n\n";
    
    // Accessibility
    $css .= ".sr-only {\n";
    $css .= "    position: absolute;\n";
    $css .= "    width: 1px;\n";
    $css .= "    height: 1px;\n";
    $css .= "    padding: 0;\n";
    $css .= "    margin: -1px;\n";
    $css .= "    overflow: hidden;\n";
    $css .= "    clip: rect(0, 0, 0, 0);\n";
    $css .= "    white-space: nowrap;\n";
    $css .= "    border: 0;\n";
    $css .= "}\n\n";
    
    // Tilføj brugerdefineret CSS
    if (!empty($globalStyles['css'])) {
        $css .= "/* Brugerdefineret CSS */\n";
        $css .= $globalStyles['css'] . "\n";
    }
    
    // Gem CSS-filen
    $css_dir = ROOT_PATH . '/public/css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    
    file_put_contents($css_dir . '/style.css', $css);
    
    return true;
}

// De mest almindelige skrifttyper til dropdown
$commonFonts = [
    'Arial, sans-serif' => 'Arial',
    'Helvetica, Arial, sans-serif' => 'Helvetica',
    'Verdana, Geneva, sans-serif' => 'Verdana',
    'Georgia, serif' => 'Georgia',
    'Times New Roman, Times, serif' => 'Times New Roman',
    'Courier New, Courier, monospace' => 'Courier New',
    'Open Sans, sans-serif' => 'Open Sans',
    'Roboto, sans-serif' => 'Roboto',
    'Montserrat, sans-serif' => 'Montserrat',
    'Lato, sans-serif' => 'Lato',
    'Poppins, sans-serif' => 'Poppins',
    'Source Sans Pro, sans-serif' => 'Source Sans Pro',
    'Allerta Stencil, sans-serif' => 'Allerta Stencil',
    'Josefin Sans, sans-serif' => 'Josefin Sans',
    'Playfair Display, serif' => 'Playfair Display',
    'Merriweather, serif' => 'Merriweather'
];

// Skrifttykkelser
$fontWeights = [
    '300' => 'Light',
    '400' => 'Regular',
    '500' => 'Medium',
    '600' => 'Semi-Bold',
    '700' => 'Bold',
    '800' => 'Extra-Bold',
    '900' => 'Black'
];
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design-editor - LATL Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #042940;
            --secondary-color: #005C53;
            --accent-color: #9FC131;
            --bright-color: #DBF227;
            --light-color: #D6D58E;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --danger-color: #F44336;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: var(--gray-100);
            color: var(--gray-800);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .admin-title {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .admin-logout {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s;
        }
        
        .admin-logout:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .admin-content {
            margin-top: 2rem;
        }
        
        .admin-nav {
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .admin-nav a {
            display: inline-block;
            margin-right: 15px;
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover {
            background-color: var(--gray-200);
            color: var(--gray-900);
        }
        
        .admin-nav a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title h2 {
            margin: 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.2);
            color: var(--danger-color);
        }
        
        .design-editor {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-300);
        }
        
        .design-editor h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-300);
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-400);
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(4, 41, 64, 0.1);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
            font-family: monospace;
        }
        
        button, .button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
        
        button:hover, .button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        button.secondary, .button.secondary {
            background-color: var(--gray-500);
        }
        
        button.secondary:hover, .button.secondary:hover {
            background-color: var(--gray-600);
        }
        
        .color-picker-wrapper {
            display: flex;
            align-items: center;
        }
        
        .color-input {
            flex-grow: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .color-picker {
            width: 40px;
            height: 40px;
            padding: 0;
            border: 1px solid var(--gray-400);
            border-left: none;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            cursor: pointer;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-300);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .tab:hover {
            background-color: var(--gray-200);
        }
        
        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .color-preview {
            width: 100%;
            height: 100px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .color-label {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(255, 255, 255, 0.7);
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            color: var(--gray-800);
        }
        
        .preview-wrapper {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            background-color: var(--gray-100);
        }
        
        .preview-title {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-300);
            color: var(--primary-color);
        }
        
        .font-preview {
            margin-bottom: 15px;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .flex-row {
            display: flex;
            gap: 15px;
        }
        
        .flex-column {
            flex: 1;
        }
        
        .flex-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .help-text {
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 5px;
        }
        
        /* Responsivt design */
        @media (max-width: 768px) {
            .flex-row {
                flex-direction: column;
            }
            
            .admin-nav {
                flex-wrap: wrap;
            }
            
            .admin-nav a {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1 class="admin-title">LATL Admin</h1>
        <a href="index.php?logout=1" class="admin-logout">Log ud</a>
    </header>
    
    <div class="container">
        <div class="admin-content">
            <div class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="layout-editor.php?page=forside">Forside</a>
                <a href="layout-editor.php?page=om-os">Om os</a>
                <a href="layout-editor.php?page=kontakt">Kontakt</a>
                <a href="layout-editor.php?page=shop">Shop</a>
                <a href="design-editor.php" class="active">Design</a>
            </div>
            
            <div class="page-title">
                <h2>Global design og styling</h2>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Design-indstillinger blev gemt og CSS-fil genereret.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <div class="design-editor">
                <div class="tabs">
                    <div class="tab active" data-tab="colors">Farver</div>
                    <div class="tab" data-tab="typography">Typografi</div>
                    <div class="tab" data-tab="custom">Tilpasset CSS</div>
                    <div class="tab" data-tab="preview">Forhåndsvisning</div>
                </div>
                
                <form action="design-editor.php" method="post">
                    <input type="hidden" name="action" value="save_design">
                    
                    <!-- Tab: Farver -->
                    <div class="tab-content active" id="tab-colors">
                        <h3>Farveskema</h3>
                        
                        <div class="preview-wrapper">
                            <h4 class="preview-title">Farveprøver</h4>
                            <div class="flex-grid">
                                <div class="color-preview" id="preview-primary" style="background-color: <?= htmlspecialchars($colorPalette['primary'] ?? '#042940') ?>">
                                    <div class="color-label">Primær</div>
                                </div>
                                <div class="color-preview" id="preview-secondary" style="background-color: <?= htmlspecialchars($colorPalette['secondary'] ?? '#005C53') ?>">
                                    <div class="color-label">Sekundær</div>
                                </div>
                                <div class="color-preview" id="preview-accent" style="background-color: <?= htmlspecialchars($colorPalette['accent'] ?? '#9FC131') ?>">
                                    <div class="color-label">Accent</div>
                                </div>
                                <div class="color-preview" id="preview-bright" style="background-color: <?= htmlspecialchars($colorPalette['bright'] ?? '#DBF227') ?>">
                                    <div class="color-label">Lys accent</div>
                                </div>
                                <div class="color-preview" id="preview-background" style="background-color: <?= htmlspecialchars($colorPalette['background'] ?? '#D6D58E') ?>">
                                    <div class="color-label">Baggrund</div>
                                </div>
                                <div class="color-preview" id="preview-text" style="background-color: <?= htmlspecialchars($colorPalette['text'] ?? '#042940') ?>">
                                    <div class="color-label" style="color: white;">Tekst</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_primary',
                                    $colorPalette['primary'] ?? '#042940',
                                    'Primær farve'
                                ) ?>
                                <div class="help-text">Bruges til hovedoverskrifter og vigtige elementer</div>
                            </div>
                            
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_secondary',
                                    $colorPalette['secondary'] ?? '#005C53',
                                    'Sekundær farve'
                                ) ?>
                                <div class="help-text">Bruges til understøttende elementer</div>
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_accent',
                                    $colorPalette['accent'] ?? '#9FC131',
                                    'Accent farve'
                                ) ?>
                                <div class="help-text">Bruges til at fremhæve vigtige elementer som knapper</div>
                            </div>
                            
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_bright',
                                    $colorPalette['bright'] ?? '#DBF227',
                                    'Lys accent farve'
                                ) ?>
                                <div class="help-text">Bruges til hover-effekter og lysere accenter</div>
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_background',
                                    $colorPalette['background'] ?? '#D6D58E',
                                    'Baggrundsfarve'
                                ) ?>
                                <div class="help-text">Standard baggrundsfarve for hjemmesiden</div>
                            </div>
                            
                            <div class="flex-column">
                                <?= color_picker(
                                    'color_text',
                                    $colorPalette['text'] ?? '#042940',
                                    'Tekstfarve'
                                ) ?>
                                <div class="help-text">Standard tekstfarve</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Typografi -->
                    <div class="tab-content" id="tab-typography">
                        <h3>Skrifttyper</h3>
                        
                        <div class="preview-wrapper">
                            <h4 class="preview-title">Typografi-eksempler</h4>
                            
                            <div class="font-preview">
                                <h2 style="font-family: <?= htmlspecialchars($fontConfig['heading']['font-family'] ?? 'inherit') ?>; font-weight: <?= htmlspecialchars($fontConfig['heading']['font-weight'] ?? 'inherit') ?>;">
                                    Overskrift-eksempel
                                </h2>
                                <p style="font-family: <?= htmlspecialchars($fontConfig['body']['font-family'] ?? 'inherit') ?>; font-weight: <?= htmlspecialchars($fontConfig['body']['font-weight'] ?? 'inherit') ?>;">
                                    Dette er et eksempel på brødtekst. Den viser, hvordan den valgte skrifttype vil se ud på hjemmesiden.
                                </p>
                                <button style="font-family: <?= htmlspecialchars($fontConfig['button']['font-family'] ?? 'inherit') ?>; font-weight: <?= htmlspecialchars($fontConfig['button']['font-weight'] ?? 'inherit') ?>;">
                                    Knap-eksempel
                                </button>
                                <p>
                                    <span style="font-family: <?= htmlspecialchars($fontConfig['price']['font-family'] ?? 'inherit') ?>; font-weight: <?= htmlspecialchars($fontConfig['price']['font-weight'] ?? 'inherit') ?>; font-size: 1.5rem;">
                                        299,95 kr
                                    </span>
                                </p>
                            </div>
                            
                            <div class="help-text">
                                <p>For at benytte Google Fonts, tilføj først import-koden til "Tilpasset CSS"-fanen og vælg derefter skrifttypen her.</p>
                                <p>Eksempel på Google Fonts import:</p>
                                <code>@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');</code>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="font_heading">Overskrift-skrifttype:</label>
                            <select name="font_heading" id="font_heading">
                                <?php foreach ($commonFonts as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['heading']['font-family'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom" <?= !in_array($fontConfig['heading']['font-family'] ?? '', array_keys($commonFonts)) ? 'selected' : '' ?>>
                                    Tilpasset (skriv nedenfor)
                                </option>
                            </select>
                            <div id="custom_heading_font" style="display: <?= !in_array($fontConfig['heading']['font-family'] ?? '', array_keys($commonFonts)) ? 'block' : 'none' ?>;">
                                <input type="text" name="custom_heading_font" value="<?= !in_array($fontConfig['heading']['font-family'] ?? '', array_keys($commonFonts)) ? htmlspecialchars($fontConfig['heading']['font-family']) : '' ?>" placeholder="f.eks. 'Roboto, sans-serif'">
                            </div>
                            
                            <div class="form-group">
                                <label for="font_heading_weight">Overskrift-tykkelse:</label>
                                <select name="font_heading_weight" id="font_heading_weight">
                                    <?php foreach ($fontWeights as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['heading']['font-weight'] ?? '') === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($value) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="font_body">Brødtekst-skrifttype:</label>
                            <select name="font_body" id="font_body">
                                <?php foreach ($commonFonts as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['body']['font-family'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom" <?= !in_array($fontConfig['body']['font-family'] ?? '', array_keys($commonFonts)) ? 'selected' : '' ?>>
                                    Tilpasset (skriv nedenfor)
                                </option>
                            </select>
                            <div id="custom_body_font" style="display: <?= !in_array($fontConfig['body']['font-family'] ?? '', array_keys($commonFonts)) ? 'block' : 'none' ?>;">
                                <input type="text" name="custom_body_font" value="<?= !in_array($fontConfig['body']['font-family'] ?? '', array_keys($commonFonts)) ? htmlspecialchars($fontConfig['body']['font-family']) : '' ?>" placeholder="f.eks. 'Roboto, sans-serif'">
                            </div>
                            
                            <div class="form-group">
                                <label for="font_body_weight">Brødtekst-tykkelse:</label>
                                <select name="font_body_weight" id="font_body_weight">
                                    <?php foreach ($fontWeights as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['body']['font-weight'] ?? '') === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($value) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="font_button">Knap-skrifttype:</label>
                            <select name="font_button" id="font_button">
                                <?php foreach ($commonFonts as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['button']['font-family'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom" <?= !in_array($fontConfig['button']['font-family'] ?? '', array_keys($commonFonts)) ? 'selected' : '' ?>>
                                    Tilpasset (skriv nedenfor)
                                </option>
                            </select>
                            <div id="custom_button_font" style="display: <?= !in_array($fontConfig['button']['font-family'] ?? '', array_keys($commonFonts)) ? 'block' : 'none' ?>;">
                                <input type="text" name="custom_button_font" value="<?= !in_array($fontConfig['button']['font-family'] ?? '', array_keys($commonFonts)) ? htmlspecialchars($fontConfig['button']['font-family']) : '' ?>" placeholder="f.eks. 'Roboto, sans-serif'">
                            </div>
                            
                            <div class="form-group">
                                <label for="font_button_weight">Knap-tykkelse:</label>
                                <select name="font_button_weight" id="font_button_weight">
                                    <?php foreach ($fontWeights as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['button']['font-weight'] ?? '') === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($value) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="font_price">Pris-skrifttype:</label>
                            <select name="font_price" id="font_price">
                                <?php foreach ($commonFonts as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['price']['font-family'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom" <?= !in_array($fontConfig['price']['font-family'] ?? '', array_keys($commonFonts)) ? 'selected' : '' ?>>
                                    Tilpasset (skriv nedenfor)
                                </option>
                            </select>
                            <div id="custom_price_font" style="display: <?= !in_array($fontConfig['price']['font-family'] ?? '', array_keys($commonFonts)) ? 'block' : 'none' ?>;">
                                <input type="text" name="custom_price_font" value="<?= !in_array($fontConfig['price']['font-family'] ?? '', array_keys($commonFonts)) ? htmlspecialchars($fontConfig['price']['font-family']) : '' ?>" placeholder="f.eks. 'Roboto, sans-serif'">
                            </div>
                            
                            <div class="form-group">
                                <label for="font_price_weight">Pris-tykkelse:</label>
                                <select name="font_price_weight" id="font_price_weight">
                                    <?php foreach ($fontWeights as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= ($fontConfig['price']['font-weight'] ?? '') === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($value) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Tilpasset CSS -->
                    <div class="tab-content" id="tab-custom">
                        <h3>Tilpasset CSS</h3>
                        
                        <div class="form-group">
                            <label for="global_css">Tilføj egen CSS:</label>
                            <textarea name="global_css" id="global_css" rows="15"><?= htmlspecialchars($css) ?></textarea>
                            <div class="help-text">
                                <p>Her kan du tilføje din egen CSS-kode, som vil blive tilføjet til slutningen af den genererede stylesheet-fil.</p>
                                <p>Dette er også et godt sted at tilføje Google Fonts imports, f.eks.:</p>
                                <code>@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');</code>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Forhåndsvisning -->
                    <div class="tab-content" id="tab-preview">
                        <h3>Forhåndsvisning</h3>
                        
                        <div class="preview-wrapper">
                            <div id="live-preview" style="padding: 20px;">
                                <h1 style="font-family: var(--heading-font); color: var(--primary-color);">Overskrift niveau 1</h1>
                                <h2 style="font-family: var(--heading-font); color: var(--primary-color);">Overskrift niveau 2</h2>
                                <h3 style="font-family: var(--heading-font); color: var(--primary-color);">Overskrift niveau 3</h3>
                                
                                <p style="font-family: var(--body-font); color: var(--text-color);">
                                    Dette er et eksempel på brødtekst. Den viser, hvordan den valgte skrifttype og tekstfarve vil se ud på hjemmesiden.
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris.
                                </p>
                                
                                <p>
                                    <a href="#" style="color: var(--accent-color);">Dette er et link</a>
                                </p>
                                
                                <div style="margin: 20px 0;">
                                    <button style="background-color: var(--accent-color); color: white; border: none; padding: 10px 20px; border-radius: 4px; font-family: var(--button-font);">
                                        Knap-eksempel
                                    </button>
                                </div>
                                
                                <div style="background-color: var(--secondary-color); color: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                    <h3 style="color: white; font-family: var(--heading-font);">Indholdsblok</h3>
                                    <p>Dette er et eksempel på en indholdsblok med en anden baggrundsfarve.</p>
                                </div>
                                
                                <div style="display: flex; align-items: center; background-color: var(--background-color); padding: 20px; border-radius: 5px;">
                                    <div style="flex: 1; padding-right: 20px;">
                                        <h3 style="font-family: var(--heading-font); color: var(--primary-color);">Produkttitel</h3>
                                        <p style="font-family: var(--body-font); color: var(--text-color);">Produktbeskrivelse og information.</p>
                                        <p style="font-family: var(--price-font); font-size: 1.5rem; color: var(--primary-color);">299,95 kr</p>
                                    </div>
                                    <div style="flex: 1; text-align: center;">
                                        <div style="width: 100px; height: 100px; background-color: var(--bright-color); border-radius: 5px; margin: 0 auto;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="button">
                            Gem design-indstillinger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    
                    // Fjern active class fra alle tabs og indhold
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Tilføj active class til det valgte tab og indhold
                    this.classList.add('active');
                    document.getElementById('tab-' + tabId).classList.add('active');
                });
            });
            
            // Farveværktøj
            const colorPickers = document.querySelectorAll('.color-picker');
            colorPickers.forEach(picker => {
                picker.addEventListener('input', function() {
                    const targetId = this.dataset.target;
                    const inputField = document.getElementById(targetId);
                    inputField.value = this.value;
                    
                    // Opdater farveprøve
                    const previewId = targetId.replace('color_', 'preview-');
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.style.backgroundColor = this.value;
                    }
                    
                    // Opdater live preview
                    updateLivePreview();
                });
                
                // Synkroniser input-felt med color-picker
                const targetId = picker.dataset.target;
                const inputField = document.getElementById(targetId);
                if (inputField) {
                    inputField.addEventListener('input', function() {
                        picker.value = this.value;
                        
                        // Opdater farveprøve
                        const previewId = targetId.replace('color_', 'preview-');
                        const preview = document.getElementById(previewId);
                        if (preview) {
                            preview.style.backgroundColor = this.value;
                        }
                        
                        // Opdater live preview
                        updateLivePreview();
                    });
                }
            });
            
            // Tilpasset skrifttype toggle
            const fontSelects = {
                'font_heading': 'custom_heading_font',
                'font_body': 'custom_body_font',
                'font_button': 'custom_button_font',
                'font_price': 'custom_price_font'
            };
            
            Object.entries(fontSelects).forEach(([selectId, customId]) => {
                const select = document.getElementById(selectId);
                const customDiv = document.getElementById(customId);
                
                if (select && customDiv) {
                    select.addEventListener('change', function() {
                        if (this.value === 'custom') {
                            customDiv.style.display = 'block';
                        } else {
                            customDiv.style.display = 'none';
                        }
                        
                        // Opdater typografi-preview
                        updateTypographyPreview();
                    });
                }
            });
            
            // Opdater typografi-preview når værdier ændres
            document.querySelectorAll('#tab-typography select, #tab-typography input').forEach(el => {
                el.addEventListener('change', updateTypographyPreview);
                el.addEventListener('input', updateTypographyPreview);
            });
            
            // Live preview opdatering
            function updateLivePreview() {
                const preview = document.getElementById('live-preview');
                const styles = document.createElement('style');
                
                // Farver
                let css = ':root {\n';
                
                // Farver fra color-pickers
                css += `  --primary-color: ${document.getElementById('color_primary').value || '#042940'};\n`;
                css += `  --secondary-color: ${document.getElementById('color_secondary').value || '#005C53'};\n`;
                css += `  --accent-color: ${document.getElementById('color_accent').value || '#9FC131'};\n`;
                css += `  --bright-color: ${document.getElementById('color_bright').value || '#DBF227'};\n`;
                css += `  --background-color: ${document.getElementById('color_background').value || '#D6D58E'};\n`;
                css += `  --text-color: ${document.getElementById('color_text').value || '#042940'};\n`;
                
                // Skrifttyper
                const getFontFamily = (selectId, customId) => {
                    const select = document.getElementById(selectId);
                    if (select.value === 'custom') {
                        return document.getElementById(customId).value || 'inherit';
                    } else {
                        return select.value;
                    }
                };
                
                css += `  --heading-font: ${getFontFamily('font_heading', 'custom_heading_font')};\n`;
                css += `  --body-font: ${getFontFamily('font_body', 'custom_body_font')};\n`;
                css += `  --button-font: ${getFontFamily('font_button', 'custom_button_font')};\n`;
                css += `  --price-font: ${getFontFamily('font_price', 'custom_price_font')};\n`;
                
                css += `  --heading-weight: ${document.getElementById('font_heading_weight').value || '400'};\n`;
                css += `  --body-weight: ${document.getElementById('font_body_weight').value || '400'};\n`;
                css += `  --button-weight: ${document.getElementById('font_button_weight').value || '600'};\n`;
                css += `  --price-weight: ${document.getElementById('font_price_weight').value || '400'};\n`;
                
                css += '}\n';
                
                styles.textContent = css;
                
                // Fjern tidligere style-tag og tilføj det nye
                const oldStyle = preview.querySelector('style');
                if (oldStyle) {
                    preview.removeChild(oldStyle);
                }
                preview.prepend(styles);
            }
            
            // Opdater typografi-preview
            function updateTypographyPreview() {
                const headingPreview = document.querySelector('.font-preview h2');
                const bodyPreview = document.querySelector('.font-preview p');
                const buttonPreview = document.querySelector('.font-preview button');
                const pricePreview = document.querySelector('.font-preview span');
                
                const getFontFamily = (selectId, customId) => {
                    const select = document.getElementById(selectId);
                    if (select.value === 'custom') {
                        return document.getElementById(customId).value || 'inherit';
                    } else {
                        return select.value;
                    }
                };
                
                if (headingPreview) {
                    headingPreview.style.fontFamily = getFontFamily('font_heading', 'custom_heading_font');
                    headingPreview.style.fontWeight = document.getElementById('font_heading_weight').value || 'inherit';
                }
                
                if (bodyPreview) {
                    bodyPreview.style.fontFamily = getFontFamily('font_body', 'custom_body_font');
                    bodyPreview.style.fontWeight = document.getElementById('font_body_weight').value || 'inherit';
                }
                
                if (buttonPreview) {
                    buttonPreview.style.fontFamily = getFontFamily('font_button', 'custom_button_font');
                    buttonPreview.style.fontWeight = document.getElementById('font_button_weight').value || 'inherit';
                }
                
                if (pricePreview) {
                    pricePreview.style.fontFamily = getFontFamily('font_price', 'custom_price_font');
                    pricePreview.style.fontWeight = document.getElementById('font_price_weight').value || 'inherit';
                }
                
                // Opdater også live preview
                updateLivePreview();
            }
            
            // Initialiser previews
            updateTypographyPreview();
            updateLivePreview();
        });
    </script>
</body>
</html>
