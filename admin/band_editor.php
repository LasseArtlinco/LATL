<?php
// admin/band_editor.php - Forbedret bånd-editor med billedupload og SEO-optimering
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/image_handler.php';

// Starter session hvis den ikke allerede er startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check om brugeren er logget ind
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Håndterer CRUD-operationer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Opret eller opdater bånd
    if ($action === 'save') {
        $page_id = $_POST['page_id'] ?? 'forside';
        $band_id = !empty($_POST['band_id']) ? (int)$_POST['band_id'] : null;
        $band_type = $_POST['band_type'] ?? '';
        $band_height = (int)($_POST['band_height'] ?? 1);
        $band_order = (int)($_POST['band_order'] ?? 999);
        
        // Behandl båndindhold baseret på type
        $band_content = [];
        
        switch ($band_type) {
            case 'slideshow':
                // Håndter slideshow indhold
                $slides = [];
                $slide_count = (int)($_POST['slide_count'] ?? 0);
                
                for ($i = 0; $i < $slide_count; $i++) {
                    $slide = [
                        'image' => $_POST["slide_{$i}_image"] ?? '',
                        'title' => $_POST["slide_{$i}_title"] ?? '',
                        'subtitle' => $_POST["slide_{$i}_subtitle"] ?? '',
                        'link' => $_POST["slide_{$i}_link"] ?? '',
                        'alt' => $_POST["slide_{$i}_alt"] ?? '',
                        'seo_title' => $_POST["slide_{$i}_seo_title"] ?? '',
                        'seo_description' => $_POST["slide_{$i}_seo_description"] ?? ''
                    ];
                    $slides[] = $slide;
                }
                
                $band_content = [
                    'title' => $_POST['slideshow_title'] ?? '',
                    'description' => $_POST['slideshow_description'] ?? '',
                    'seo_schema' => $_POST['slideshow_seo_schema'] ?? '',
                    'slides' => $slides,
                    'autoplay' => isset($_POST['autoplay']) && $_POST['autoplay'] === 'on',
                    'interval' => (int)($_POST['interval'] ?? 5000)
                ];
                break;
                
            case 'product':
                // Håndter produkt indhold
                $band_content = [
                    'image' => $_POST['product_image'] ?? '',
                    'title' => $_POST['product_title'] ?? '',
                    'subtitle' => $_POST['product_subtitle'] ?? '',
                    'link' => $_POST['product_link'] ?? '',
                    'alt' => $_POST['product_alt'] ?? '',
                    'seo_title' => $_POST['product_seo_title'] ?? '',
                    'seo_description' => $_POST['product_seo_description'] ?? '',
                    'background_color' => $_POST['product_bg_color'] ?? '#ffffff',
                    'button_text' => $_POST['product_button_text'] ?? 'Se mere'
                ];
                break;
                
            default:
                // Ukendt båndtype
                header('Location: layout-editor.php?page=' . urlencode($page_id) . '&error=invalid_band_type');
                exit;
        }
        
        // Gem båndet
        $result = save_band($page_id, $band_type, $band_height, $band_content, $band_order, $band_id);
        
        if ($result) {
            header('Location: layout-editor.php?page=' . urlencode($page_id) . '&success=band_saved');
        } else {
            header('Location: layout-editor.php?page=' . urlencode($page_id) . '&error=save_failed');
        }
        exit;
    }
    
    // Slet bånd
    if ($action === 'delete' && !empty($_POST['band_id'])) {
        $band_id = (int)$_POST['band_id'];
        $page_id = $_POST['page_id'] ?? 'forside';
        
        $result = delete_band($band_id);
        
        if ($result) {
            header('Location: layout-editor.php?page=' . urlencode($page_id) . '&success=band_deleted');
        } else {
            header('Location: layout-editor.php?page=' . urlencode($page_id) . '&error=delete_failed');
        }
        exit;
    }
    
    // Opdater båndrækkefølge (drag-and-drop)
    if ($action === 'update_order') {
        $page_id = $_POST['page_id'] ?? 'forside';
        $band_orders = json_decode($_POST['band_orders'], true);
        
        if ($band_orders && is_array($band_orders)) {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            try {
                $conn->beginTransaction();
                
                foreach ($band_orders as $band_id => $order) {
                    $db->update(
                        'layout_bands',
                        ['band_order' => (int)$order],
                        'id = ?',
                        [(int)$band_id]
                    );
                }
                
                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        
        echo json_encode(['error' => 'Ugyldige data']);
        exit;
    }
}

// Hent den anmodede side
$page_id = isset($_GET['page']) ? $_GET['page'] : 'forside';

// Hvis en specifik bånd-redigering er anmodet, hent bånddata
$edit_band = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $band_id = (int)$_GET['edit'];
    $db = Database::getInstance();
    $band = $db->selectOne("SELECT * FROM layout_bands WHERE id = ?", [$band_id]);
    
    if ($band) {
        $edit_band = $band;
        $edit_band['band_content'] = json_decode($edit_band['band_content'], true);
    }
}

// Hent alle bånd for denne side
$bands = get_page_bands($page_id);

// Hent sideoplysninger
$layout = get_page_layout($page_id);

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
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bånd-editor - <?= htmlspecialchars($layout['title'] ?? 'LATL Admin') ?></title>
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
        
        .page-action {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-action:hover {
            background-color: var(--bright-color);
            color: var(--gray-800);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
        
        .band-list {
            margin-top: 20px;
        }
        
        .band-item {
            background-color: white;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid var(--gray-300);
        }
        
        .band-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .band-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-300);
            cursor: pointer;
        }
        
        .band-title {
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .band-type-icon {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .band-actions {
            display: flex;
        }
        
        .band-action {
            margin-left: 10px;
            text-decoration: none;
            color: var(--gray-600);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .band-action:hover {
            color: var(--primary-color);
            background-color: var(--gray-200);
        }
        
        .band-action.delete {
            color: var(--danger-color);
        }
        
        .band-action.delete:hover {
            background-color: rgba(244, 67, 54, 0.1);
        }
        
        .band-content {
            padding: 20px;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            display: none;
        }
        
        .band-content.active {
            display: block;
        }
        
        .band-preview {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            background-color: var(--gray-100);
        }
        
        .preview-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--gray-700);
        }
        
        .json-content {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            white-space: pre-wrap;
            background-color: var(--gray-200);
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
            color: var(--gray-800);
        }
        
        .add-band {
            margin-top: 30px;
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-300);
        }
        
        .add-band h3 {
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
        input[type="number"],
        input[type="url"],
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
        input[type="number"]:focus,
        input[type="url"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(4, 41, 64, 0.1);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
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
        
        .slide-list {
            margin-bottom: 20px;
        }
        
        .slide-item {
            background-color: var(--gray-100);
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid var(--gray-300);
            position: relative;
        }
        
        .slide-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .slide-title {
            font-weight: 500;
            margin: 0;
        }
        
        .slide-actions button {
            padding: 5px 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .slide-content {
            display: none;
        }
        
        .slide-content.active {
            display: block;
        }

        .seo-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--gray-300);
        }
        
        .seo-section h4 {
            margin-top: 0;
            color: var(--secondary-color);
        }
        
        .image-preview {
            margin-top: 10px;
            max-width: 300px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        .upload-area {
            border: 2px dashed var(--gray-400);
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(4, 41, 64, 0.02);
        }
        
        .upload-area i {
            font-size: 2rem;
            color: var(--gray-500);
            margin-bottom: 10px;
        }
        
        .upload-area p {
            margin: 0;
            color: var(--gray-600);
        }
        
        .upload-input {
            display: none;
        }
        
        .drag-handle {
            cursor: move;
            margin-right: 10px;
            color: var(--gray-500);
        }
        
        .flex-row {
            display: flex;
            gap: 15px;
        }
        
        .flex-column {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            margin-left: 5px;
        }
        
        .help-text {
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 5px;
        }

        .sortable-ghost {
            opacity: 0.5;
            background-color: var(--gray-200);
        }

        .slide-drag-handle {
            position: absolute;
            top: 15px;
            left: 0;
            color: var(--gray-500);
            cursor: move;
            padding: 0 10px;
        }

        .slide-drag-handle:hover {
            color: var(--primary-color);
        }

        .schema-editor {
            font-family: monospace;
            min-height: 150px;
        }
        
        /* Responsivt design */
        @media (max-width: 768px) {
            .flex-row {
                flex-direction: column;
            }
            
            .band-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .band-actions {
                margin-top: 10px;
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
                <a href="layout-editor.php?page=forside" class="<?= $page_id === 'forside' ? 'active' : '' ?>">Forside</a>
                <a href="layout-editor.php?page=om-os" class="<?= $page_id === 'om-os' ? 'active' : '' ?>">Om os</a>
                <a href="layout-editor.php?page=kontakt" class="<?= $page_id === 'kontakt' ? 'active' : '' ?>">Kontakt</a>
                <a href="layout-editor.php?page=shop" class="<?= $page_id === 'shop' ? 'active' : '' ?>">Shop</a>
                <a href="design-editor.php" class="<?= isset($_GET['design']) ? 'active' : '' ?>">Design</a>
            </div>
            
            <div class="page-title">
                <h2>Bånd-editor - <?= htmlspecialchars($layout['title'] ?? ucfirst($page_id)) ?></h2>
                <a href="page-settings.php?page=<?= urlencode($page_id) ?>" class="page-action">
                    <i class="fas fa-cog"></i> Side-indstillinger
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    $success = $_GET['success'];
                    switch ($success) {
                        case 'band_saved':
                            echo 'Båndet blev gemt.';
                            break;
                        case 'band_deleted':
                            echo 'Båndet blev slettet.';
                            break;
                        default:
                            echo 'Handlingen blev udført.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    $error = $_GET['error'];
                    switch ($error) {
                        case 'invalid_band_type':
                            echo 'Ugyldig båndtype.';
                            break;
                        case 'save_failed':
                            echo 'Kunne ikke gemme båndet.';
                            break;
                        case 'delete_failed':
                            echo 'Kunne ikke slette båndet.';
                            break;
                        default:
                            echo 'Der opstod en fejl.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="band-list sortable">
                <?php if (empty($bands)): ?>
                    <p>Ingen bånd fundet for denne side. Tilføj et bånd nedenfor.</p>
                <?php else: ?>
                    <?php foreach ($bands as $band): ?>
                        <div class="band-item" data-id="<?= $band['id'] ?>" data-height="<?= $band['band_height'] ?>">
                            <div class="band-header">
                                <h3 class="band-title">
                                    <i class="drag-handle fas fa-grip-vertical"></i>
                                    <i class="band-type-icon fas fa-<?= get_band_icon($band['band_type']) ?>"></i>
                                    <?= ucfirst(htmlspecialchars($band['band_type'])) ?> 
                                    <small>(Højde: <?= htmlspecialchars($band['band_height']) ?>, Rækkefølge: <?= htmlspecialchars($band['band_order']) ?>)</small>
                                </h3>
                                <div class="band-actions">
                                    <button class="band-action toggle-preview" title="Vis/skjul indhold">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="band_editor.php?page=<?= urlencode($page_id) ?>&edit=<?= $band['id'] ?>" class="band-action" title="Rediger">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="band-action delete" data-id="<?= $band['id'] ?>" title="Slet">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="band-content">
                                <div class="preview-label">Indhold:</div>
                                <div class="json-content"><?= htmlspecialchars(json_encode($band['band_content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="add-band">
                <?php if ($edit_band): ?>
                    <h3>Rediger bånd - <?= ucfirst(htmlspecialchars($edit_band['band_type'])) ?></h3>
                <?php else: ?>
                    <h3>Tilføj nyt bånd</h3>
                <?php endif; ?>
                
                <form action="band_editor.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="page_id" value="<?= htmlspecialchars($page_id) ?>">
                    <?php if ($edit_band): ?>
                        <input type="hidden" name="band_id" value="<?= $edit_band['id'] ?>">
                        <input type="hidden" name="band_type" value="<?= htmlspecialchars($edit_band['band_type']) ?>">
                    <?php endif; ?>
                    
                    <?php if (!$edit_band): ?>
                        <div class="form-group">
                            <label for="band_type">Båndtype:</label>
                            <select name="band_type" id="band_type" required>
                                <option value="">Vælg type</option>
                                <option value="slideshow">Slideshow</option>
                                <option value="product">Produkt</option>
                            </select>
                            <div class="help-text">Vælg den type bånd du vil tilføje</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-row">
                        <div class="flex-column">
                            <div class="form-group">
                                <label for="band_height">Båndhøjde (1-4):</label>
                                <input type="number" name="band_height" id="band_height" min="1" max="4" value="<?= $edit_band ? $edit_band['band_height'] : 1 ?>" required>
                                <div class="help-text">1 = lille, 2 = medium, 3 = stor, 4 = fuld højde</div>
                            </div>
                        </div>
                        
                        <div class="flex-column">
                            <div class="form-group">
                                <label for="band_order">Rækkefølge:</label>
                                <input type="number" name="band_order" id="band_order" min="1" value="<?= $edit_band ? $edit_band['band_order'] : (count($bands) + 1) ?>" required>
                                <div class="help-text">Bånd med lavere værdier vises først</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Slideshow bånd form -->
                    <div id="slideshow_form" class="band-type-form" style="display: <?= (!$edit_band || $edit_band['band_type'] === 'slideshow') ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="slideshow_title">Slideshow titel:</label>
                            <input type="text" name="slideshow_title" id="slideshow_title" value="<?= $edit_band && isset($edit_band['band_content']['title']) ? htmlspecialchars($edit_band['band_content']['title']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="slideshow_description">Beskrivelse:</label>
                            <textarea name="slideshow_description" id="slideshow_description"><?= $edit_band && isset($edit_band['band_content']['description']) ? htmlspecialchars($edit_band['band_content']['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="seo-section">
                            <h4>SEO-optimering</h4>
                            <div class="form-group">
                                <label for="slideshow_seo_schema">JSON-LD Schema:</label>
                                <textarea name="slideshow_seo_schema" id="slideshow_seo_schema" class="schema-editor"><?= $edit_band && isset($edit_band['band_content']['seo_schema']) ? htmlspecialchars($edit_band['band_content']['seo_schema']) : '' ?></textarea>
                                <div class="help-text">JSON-LD schema for struktureret data (carousel, gallery, etc.). Lad feltet være tomt for at generere automatisk.</div>
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="flex-column">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="autoplay" id="autoplay" <?= $edit_band && isset($edit_band['band_content']['autoplay']) && $edit_band['band_content']['autoplay'] ? 'checked' : '' ?>>
                                    <label for="autoplay">Autoplay</label>
                                </div>
                            </div>
                            
                            <div class="flex-column">
                                <div class="form-group">
                                    <label for="interval">Interval (ms):</label>
                                    <input type="number" name="interval" id="interval" min="1000" step="500" value="<?= $edit_band && isset($edit_band['band_content']['interval']) ? (int)$edit_band['band_content']['interval'] : 5000 ?>">
                                </div>
                            </div>
                        </div>
                        
                        <h4>Slides</h4>
                        <div id="slide_list" class="slide-list sortable-slides">
                            <?php 
                            $slides = $edit_band && isset($edit_band['band_content']['slides']) ? $edit_band['band_content']['slides'] : [];
                            foreach ($slides as $index => $slide): 
                            ?>
                                <div class="slide-item" data-index="<?= $index ?>">
                                    <i class="slide-drag-handle fas fa-grip-vertical"></i>
                                    <div class="slide-header">
                                        <h4 class="slide-title">Slide <?= $index + 1 ?></h4>
                                        <div class="slide-actions">
                                            <button type="button" class="toggle-slide">Vis/skjul</button>
                                            <button type="button" class="remove-slide">Fjern</button>
                                        </div>
                                    </div>
                                    <div class="slide-content">
                                        <div class="form-group">
                                            <label for="slide_<?= $index ?>_image">Billede:</label>
                                            <div class="upload-area" data-target="slide_<?= $index ?>_image">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p>Klik for at uploade eller træk en fil hertil</p>
                                            </div>
                                            <input type="file" class="upload-input" id="slide_<?= $index ?>_image_upload" accept="image/*">
                                            <input type="hidden" name="slide_<?= $index ?>_image" id="slide_<?= $index ?>_image" value="<?= htmlspecialchars($slide['image']) ?>">
                                            
                                            <?php if (!empty($slide['image'])): ?>
                                                <div class="image-preview">
                                                    <img src="/uploads/<?= htmlspecialchars($slide['image']) ?>" alt="Slide preview">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="slide_<?= $index ?>_alt">Alt-tekst (SEO):</label>
                                            <input type="text" name="slide_<?= $index ?>_alt" id="slide_<?= $index ?>_alt" value="<?= htmlspecialchars($slide['alt'] ?? '') ?>">
                                            <div class="help-text">Beskrivende tekst til billedet for SEO og tilgængelighed</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="slide_<?= $index ?>_title">Titel:</label>
                                            <input type="text" name="slide_<?= $index ?>_title" id="slide_<?= $index ?>_title" value="<?= htmlspecialchars($slide['title'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="slide_<?= $index ?>_subtitle">Undertitel:</label>
                                            <input type="text" name="slide_<?= $index ?>_subtitle" id="slide_<?= $index ?>_subtitle" value="<?= htmlspecialchars($slide['subtitle'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="slide_<?= $index ?>_link">Link:</label>
                                            <input type="text" name="slide_<?= $index ?>_link" id="slide_<?= $index ?>_link" value="<?= htmlspecialchars($slide['link'] ?? '') ?>">
                                        </div>

                                        <div class="seo-section">
                                            <h4>SEO-metadata</h4>
                                            <div class="form-group">
                                                <label for="slide_<?= $index ?>_seo_title">SEO Titel:</label>
                                                <input type="text" name="slide_<?= $index ?>_seo_title" id="slide_<?= $index ?>_seo_title" value="<?= htmlspecialchars($slide['seo_title'] ?? '') ?>">
                                                <div class="help-text">Titel til brug i struktureret data (JSON-LD)</div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="slide_<?= $index ?>_seo_description">SEO Beskrivelse:</label>
                                                <textarea name="slide_<?= $index ?>_seo_description" id="slide_<?= $index ?>_seo_description"><?= htmlspecialchars($slide['seo_description'] ?? '') ?></textarea>
                                                <div class="help-text">Beskrivelse til brug i struktureret data og meta-tags</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" id="add_slide" class="button secondary">
                            <i class="fas fa-plus"></i> Tilføj slide
                        </button>
                        
                        <input type="hidden" name="slide_count" id="slide_count" value="<?= count($slides) ?>">
                    </div>
                    
                    <!-- Produkt bånd form -->
                    <div id="product_form" class="band-type-form" style="display: <?= ($edit_band && $edit_band['band_type'] === 'product') ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="product_image">Produktbillede (PNG med transparent baggrund):</label>
                            <div class="upload-area" data-target="product_image">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Klik for at uploade eller træk en fil hertil</p>
                            </div>
                            <input type="file" class="upload-input" id="product_image_upload" accept="image/*">
                            <input type="hidden" name="product_image" id="product_image" value="<?= $edit_band && isset($edit_band['band_content']['image']) ? htmlspecialchars($edit_band['band_content']['image']) : '' ?>">
                            
                            <?php if ($edit_band && !empty($edit_band['band_content']['image'])): ?>
                                <div class="image-preview">
                                    <img src="/uploads/<?= htmlspecialchars($edit_band['band_content']['image']) ?>" alt="Product preview">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_alt">Alt-tekst (SEO):</label>
                            <input type="text" name="product_alt" id="product_alt" value="<?= $edit_band && isset($edit_band['band_content']['alt']) ? htmlspecialchars($edit_band['band_content']['alt']) : '' ?>">
                            <div class="help-text">Beskrivende tekst til billedet for SEO og tilgængelighed</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_title">Titel:</label>
                            <input type="text" name="product_title" id="product_title" value="<?= $edit_band && isset($edit_band['band_content']['title']) ? htmlspecialchars($edit_band['band_content']['title']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_subtitle">Undertitel:</label>
                            <input type="text" name="product_subtitle" id="product_subtitle" value="<?= $edit_band && isset($edit_band['band_content']['subtitle']) ? htmlspecialchars($edit_band['band_content']['subtitle']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_link">Link:</label>
                            <input type="text" name="product_link" id="product_link" value="<?= $edit_band && isset($edit_band['band_content']['link']) ? htmlspecialchars($edit_band['band_content']['link']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_button_text">Knaptekst:</label>
                            <input type="text" name="product_button_text" id="product_button_text" value="<?= $edit_band && isset($edit_band['band_content']['button_text']) ? htmlspecialchars($edit_band['band_content']['button_text']) : 'Se mere' ?>">
                        </div>
                        
                        <?= color_picker(
                            'product_bg_color', 
                            $edit_band && isset($edit_band['band_content']['background_color']) ? $edit_band['band_content']['background_color'] : '#D6D58E',
                            'Baggrundsfarve'
                        ) ?>

                        <div class="seo-section">
                            <h4>SEO-metadata</h4>
                            <div class="form-group">
                                <label for="product_seo_title">SEO Titel:</label>
                                <input type="text" name="product_seo_title" id="product_seo_title" value="<?= $edit_band && isset($edit_band['band_content']['seo_title']) ? htmlspecialchars($edit_band['band_content']['seo_title']) : '' ?>">
                                <div class="help-text">Titel til brug i struktureret data og meta-tags</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_seo_description">SEO Beskrivelse:</label>
                                <textarea name="product_seo_description" id="product_seo_description"><?= $edit_band && isset($edit_band['band_content']['seo_description']) ? htmlspecialchars($edit_band['band_content']['seo_description']) : '' ?></textarea>
                                <div class="help-text">Beskrivelse til brug i struktureret data og meta-tags</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="button">
                            <?= $edit_band ? 'Gem ændringer' : 'Tilføj bånd' ?>
                        </button>
                        
                        <a href="layout-editor.php?page=<?= urlencode($page_id) ?>" class="button secondary" style="margin-left: 10px;">Annuller</a>
                    </div>
                </form>
                
                <!-- Delete form for modal submit -->
                <form id="delete_form" action="band_editor.php" method="post" style="display: none;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="page_id" value="<?= htmlspecialchars($page_id) ?>">
                    <input type="hidden" name="band_id" id="delete_band_id" value="">
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Band type ændring
            const bandTypeSelect = document.getElementById('band_type');
            const bandTypeForms = document.querySelectorAll('.band-type-form');
            
            if (bandTypeSelect) {
                bandTypeSelect.addEventListener('change', function() {
                    const selectedType = this.value;
                    
                    bandTypeForms.forEach(form => {
                        form.style.display = 'none';
                    });
                    
                    if (selectedType) {
                        document.getElementById(selectedType + '_form').style.display = 'block';
                    }
                });
            }
            
            // Farveværktøj
            const colorPickers = document.querySelectorAll('.color-picker');
            colorPickers.forEach(picker => {
                picker.addEventListener('input', function() {
                    const targetId = this.dataset.target;
                    document.getElementById(targetId).value = this.value;
                });

                // Synkroniser input-felt med color-picker
                const targetId = picker.dataset.target;
                const inputField = document.getElementById(targetId);
                if (inputField) {
                    inputField.addEventListener('input', function() {
                        picker.value = this.value;
                    });
                }
            });
            
            // Slideshow håndtering
            const addSlideBtn = document.getElementById('add_slide');
            const slideList = document.getElementById('slide_list');
            const slideCountInput = document.getElementById('slide_count');
            
            if (addSlideBtn && slideList && slideCountInput) {
                let slideCount = parseInt(slideCountInput.value) || 0;
                
                addSlideBtn.addEventListener('click', function() {
                    const index = slideCount;
                    const slideHtml = `
                        <div class="slide-item" data-index="${index}">
                            <i class="slide-drag-handle fas fa-grip-vertical"></i>
                            <div class="slide-header">
                                <h4 class="slide-title">Slide ${index + 1}</h4>
                                <div class="slide-actions">
                                    <button type="button" class="toggle-slide">Vis/skjul</button>
                                    <button type="button" class="remove-slide">Fjern</button>
                                </div>
                            </div>
                            <div class="slide-content active">
                                <div class="form-group">
                                    <label for="slide_${index}_image">Billede:</label>
                                    <div class="upload-area" data-target="slide_${index}_image">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Klik for at uploade eller træk en fil hertil</p>
                                    </div>
                                    <input type="file" class="upload-input" id="slide_${index}_image_upload" accept="image/*">
                                    <input type="hidden" name="slide_${index}_image" id="slide_${index}_image" value="">
                                </div>
                                
                                <div class="form-group">
                                    <label for="slide_${index}_alt">Alt-tekst (SEO):</label>
                                    <input type="text" name="slide_${index}_alt" id="slide_${index}_alt" value="">
                                    <div class="help-text">Beskrivende tekst til billedet for SEO og tilgængelighed</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="slide_${index}_title">Titel:</label>
                                    <input type="text" name="slide_${index}_title" id="slide_${index}_title" value="">
                                </div>
                                
                                <div class="form-group">
                                    <label for="slide_${index}_subtitle">Undertitel:</label>
                                    <input type="text" name="slide_${index}_subtitle" id="slide_${index}_subtitle" value="">
                                </div>
                                
                                <div class="form-group">
                                    <label for="slide_${index}_link">Link:</label>
                                    <input type="text" name="slide_${index}_link" id="slide_${index}_link" value="">
                                </div>

                                <div class="seo-section">
                                    <h4>SEO-metadata</h4>
                                    <div class="form-group">
                                        <label for="slide_${index}_seo_title">SEO Titel:</label>
                                        <input type="text" name="slide_${index}_seo_title" id="slide_${index}_seo_title" value="">
                                        <div class="help-text">Titel til brug i struktureret data (JSON-LD)</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slide_${index}_seo_description">SEO Beskrivelse:</label>
                                        <textarea name="slide_${index}_seo_description" id="slide_${index}_seo_description"></textarea>
                                        <div class="help-text">Beskrivelse til brug i struktureret data og meta-tags</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    slideList.insertAdjacentHTML('beforeend', slideHtml);
                    slideCount++;
                    slideCountInput.value = slideCount;
                    
                    // Tilføj event listeners til den nye slide
                    initSlideEvents();
                    setupUploadAreas();
                    
                    // Initialiser sortable på den nye slide-liste
                    initSortableSlides();
                });
            }
            
            // Initialiser event listeners for slides
            function initSlideEvents() {
                document.querySelectorAll('.toggle-slide').forEach(btn => {
                    btn.removeEventListener('click', toggleSlideContent);
                    btn.addEventListener('click', toggleSlideContent);
                });
                
                document.querySelectorAll('.remove-slide').forEach(btn => {
                    btn.removeEventListener('click', removeSlide);
                    btn.addEventListener('click', removeSlide);
                });
            }
            
            // Toggle slide content
            function toggleSlideContent() {
                const content = this.closest('.slide-item').querySelector('.slide-content');
                content.classList.toggle('active');
            }
            
            // Fjern slide
            function removeSlide() {
                if (confirm('Er du sikker på, at du vil fjerne dette slide?')) {
                    const slideItem = this.closest('.slide-item');
                    slideItem.remove();
                    updateSlideIndices();
                }
            }
            
            // Opdater slide indekser
            function updateSlideIndices() {
                const slides = slideList.querySelectorAll('.slide-item');
                slideCount = slides.length;
                slideCountInput.value = slideCount;
                
                slides.forEach((slide, i) => {
                    slide.dataset.index = i;
                    slide.querySelector('.slide-title').textContent = `Slide ${i + 1}`;
                    
                    // Opdater indekser i input-navne
                    const inputs = slide.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        const name = input.name;
                        if (name) {
                            input.name = name.replace(/slide_\d+/, `slide_${i}`);
                            input.id = input.id.replace(/slide_\d+/, `slide_${i}`);
                        }
                    });
                    
                    // Opdater labels
                    const labels = slide.querySelectorAll('label');
                    labels.forEach(label => {
                        const forAttr = label.getAttribute('for');
                        if (forAttr) {
                            label.setAttribute('for', forAttr.replace(/slide_\d+/, `slide_${i}`));
                        }
                    });
                    
                    // Opdater upload area
                    const uploadArea = slide.querySelector('.upload-area');
                    if (uploadArea) {
                        uploadArea.dataset.target = `slide_${i}_image`;
                    }
                });
            }
            
            // Initialiser events
            initSlideEvents();
            
            // Visning/skjul af båndindhold
            document.querySelectorAll('.toggle-preview').forEach(btn => {
                btn.addEventListener('click', function() {
                    const content = this.closest('.band-item').querySelector('.band-content');
                    content.classList.toggle('active');
                });
            });
            
            // Sletning af bånd
            document.querySelectorAll('.band-action.delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Er du sikker på, at du vil slette dette bånd? Denne handling kan ikke fortrydes.')) {
                        const bandId = this.dataset.id;
                        document.getElementById('delete_band_id').value = bandId;
                        document.getElementById('delete_form').submit();
                    }
                });
            });
            
            // Drag-and-drop for slides
            function initSortableSlides() {
                const slideSortableList = document.querySelector('.sortable-slides');
                if (slideSortableList) {
                    // Fjern eventuelle eksisterende sortable instanser
                    const oldInstance = slideSortableList._sortable;
                    if (oldInstance) {
                        oldInstance.destroy();
                    }
                    
                    new Sortable(slideSortableList, {
                        handle: '.slide-drag-handle',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function() {
                            updateSlideIndices();
                        }
                    });
                }
            }
            
            // Initialiser sortable for slides
            initSortableSlides(); rækkefølge for bånd
            const sortableList = document.querySelector('.sortable');
            if (sortableList) {
                const sortable = new Sortable(sortableList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function() {
                        // Opdater rækkefølgen i databasen via AJAX
                        const items = sortableList.querySelectorAll('.band-item');
                        const bandOrders = {};
                        
                        items.forEach((item, index) => {
                            const bandId = item.dataset.id;
                            bandOrders[bandId] = index + 1;
                            
                            // Opdater den synlige rækkefølge
                            const title = item.querySelector('.band-title');
                            const regex = /\(Højde: \d+, Rækkefølge: \d+\)/;
                            const newText = title.textContent.replace(regex, `(Højde: ${item.dataset.height || '?'}, Rækkefølge: ${index + 1})`);
                            title.textContent = newText;
                        });
                        
                        // Send opdatering til server
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'band_editor.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.error) {
                                            alert('Fejl: ' + response.error);
                                        }
                                    } catch (e) {
                                        console.error('Fejl ved parsing af svar:', e);
                                    }
                                } else {
                                    alert('Fejl ved opdatering af rækkefølge.');
                                }
                            }
                        };
                        xhr.send('action=update_order&page_id=' + encodeURIComponent('<?= $page_id ?>') + '&band_orders=' + encodeURIComponent(JSON.stringify(bandOrders)));
                    }
                });
            }

            // Drag-and-drop
