<?php
// admin/band-editor.php - Forbedret bånd-editor med billedupload og SEO-optimering
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

// Hjælpefunktion til at få ikon for båndtype
function get_band_icon($type) {
    $icons = [
        'slideshow' => 'images',
        'product' => 'box',
        'text' => 'align-left',
        'hero' => 'star',
        'gallery' => 'th',
        'contact' => 'envelope'
    ];
    return $icons[$type] ?? 'layer-group';
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
                header('Location: band-editor.php?page=' . urlencode($page_id) . '&error=invalid_band_type');
                exit;
        }
        
        // Gem båndet
        $result = save_band($page_id, $band_type, $band_height, $band_content, $band_order, $band_id);
        
        if ($result) {
            header('Location: band-editor.php?page=' . urlencode($page_id) . '&success=band_saved');
        } else {
            header('Location: band-editor.php?page=' . urlencode($page_id) . '&error=save_failed');
        }
        exit;
    }
    
    // Slet bånd
    if ($action === 'delete' && !empty($_POST['band_id'])) {
        $band_id = (int)$_POST['band_id'];
        $page_id = $_POST['page_id'] ?? 'forside';
        
        $result = delete_band($band_id);
        
        if ($result) {
            header('Location: band-editor.php?page=' . urlencode($page_id) . '&success=band_deleted');
        } else {
            header('Location: band-editor.php?page=' . urlencode($page_id) . '&error=delete_failed');
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
    <!-- Eksternt CSS og JavaScript -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="../public/js/upload.js"></script>
    <script src="../public/js/band-editor.js" defer></script>
</head>
<body>
    <header class="admin-header">
        <h1 class="admin-title">LATL Admin</h1>
        <a href="index.php?logout=1" class="admin-logout">
            <i class="fas fa-sign-out-alt"></i> Log ud
        </a>
    </header>
    
    <div class="container">
        <div class="admin-content">
            <div class="admin-nav">
                <a href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="band-editor.php?page=forside" class="<?= $page_id === 'forside' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Forside
                </a>
                <a href="band-editor.php?page=om-os" class="<?= $page_id === 'om-os' ? 'active' : '' ?>">
                    <i class="fas fa-info-circle"></i> Om os
                </a>
                <a href="band-editor.php?page=kontakt" class="<?= $page_id === 'kontakt' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Kontakt
                </a>
                <a href="band-editor.php?page=shop" class="<?= $page_id === 'shop' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i> Shop
                </a>
                <a href="design-editor.php">
                    <i class="fas fa-paint-brush"></i> Design
                </a>
            </div>
            
            <div class="page-title">
                <h2>Bånd-editor - <?= htmlspecialchars($layout['title'] ?? ucfirst($page_id)) ?></h2>
                <a href="/<?= urlencode($page_id) ?>" target="_blank" class="page-action">
                    <i class="fas fa-external-link-alt"></i> Vis side
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
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
                    <i class="fas fa-exclamation-circle"></i>
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
                                    <a href="band-editor.php?page=<?= urlencode($page_id) ?>&edit=<?= $band['id'] ?>" class="band-action" title="Rediger">
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
                
                <form action="band-editor.php" method="post" enctype="multipart/form-data">
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
                                            <div class="upload-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-bar-fill"></div>
                                                </div>
                                            </div>
                                            
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
                            <div class="upload-progress">
                                <div class="progress-bar">
                                    <div class="progress-bar-fill"></div>
                                </div>
                            </div>
                            
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
                        
                        <a href="band-editor.php?page=<?= urlencode($page_id) ?>" class="button secondary" style="margin-left: 10px;">Annuller</a>
                    </div>
                </form>
                
                <!-- Delete form for modal submit -->
                <form id="delete_form" action="band-editor.php" method="post" style="display: none;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="page_id" value="<?= htmlspecialchars($page_id) ?>">
                    <input type="hidden" name="band_id" id="delete_band_id" value="">
                </form>
            </div>
        </div>
    </div>
    
    <!-- Variabel der sendes til JavaScript -->
    <script>
        // Overfør nødvendig data til JavaScript
        var pageId = "<?= htmlspecialchars($page_id) ?>";
    </script>
</body>
</html>
