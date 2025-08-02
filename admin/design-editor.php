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
    file_put_contents(ROOT_PATH . '/public/css/style.css', $css);
    
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
            
            <?php if (isset($_GET['error
