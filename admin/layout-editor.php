<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check om brugeren er logget ind
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Hent den anmodede side
$page_id = isset($_GET['page']) ? $_GET['page'] : 'forside';

// Hent sideoplysninger
$layout = get_page_layout($page_id);

// Hent bånd for denne side
$bands = get_page_bands($page_id);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Editor - <?= htmlspecialchars($layout['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background-color: #333;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            margin: 0;
        }
        
        .admin-logout {
            color: white;
            text-decoration: none;
        }
        
        .admin-content {
            margin-top: 2rem;
        }
        
        .admin-nav {
            margin-bottom: 30px;
        }
        
        .admin-nav a {
            display: inline-block;
            margin-right: 15px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        
        .band-list {
            margin-top: 20px;
        }
        
        .band-item {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .band-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .band-title {
            margin: 0;
        }
        
        .band-actions a {
            margin-left: 10px;
            text-decoration: none;
            color: #333;
        }
        
        .band-content {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .add-band {
            margin-top: 30px;
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #333;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #555;
        }
        
        .band-preview {
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        
        .preview-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .json-content {
            font-family: monospace;
            white-space: pre-wrap;
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
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
                <a href="index.php">Sider</a>
                <a href="layout-editor.php">Bånd-editor</a>
            </div>
            
            <h2>Layout Editor - <?= htmlspecialchars($layout['title']) ?></h2>
            
            <div class="band-list">
                <?php if (empty($bands)): ?>
                    <p>Ingen bånd fundet for denne side. Tilføj et bånd nedenfor.</p>
                <?php else: ?>
                    <?php foreach ($bands as $band): ?>
                        <div class="band-item">
                            <div class="band-header">
                                <h3 class="band-title"><?= htmlspecialchars($band['band_type']) ?> (Højde: <?= htmlspecialchars($band['band_height']) ?>)</h3>
                                <div class="band-actions">
                                    <a href="layout-editor.php?page=<?= urlencode($page_id) ?>&edit=<?= $band['id'] ?>">Rediger</a>
                                    <a href="layout-editor.php?page=<?= urlencode($page_id) ?>&delete=<?= $band['id'] ?>" onclick="return confirm('Er du sikker på, at du vil slette dette bånd?')">Slet</a>
                                </div>
                            </div>
                            
                            <div class="band-content">
                                <div class="preview-label">Indhold:</div>
                                <div class="json-content"><?= htmlspecialchars(json_encode($band['band_content'], JSON_PRETTY_PRINT)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="add-band">
                <h3>Tilføj nyt bånd</h3>
                <form action="layout-editor.php" method="post">
                    <input type="hidden" name="page_id" value="<?= htmlspecialchars($page_id) ?>">
                    
                    <div class="form-group">
                        <label for="band_type">Båndtype:</label>
                        <select name="band_type" id="band_type" required>
                            <option value="">Vælg type</option>
                            <option value="slideshow">Slideshow</option>
                            <option value="product">Produkt</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="band_height">Båndhøjde (1-4):</label>
                        <input type="number" name="band_height" id="band_height" min="1" max="4" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="band_order">Rækkefølge:</label>
                        <input type="number" name="band_order" id="band_order" min="1" value="<?= count($bands) + 1 ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="band_content">Bånd indhold (JSON):</label>
                        <textarea name="band_content" id="band_content" rows="10" required></textarea>
                    </div>
                    
                    <button type="submit" name="add_band">Tilføj bånd</button>
                </form>
                
                <div style="margin-top: 20px;">
                    <h4>JSON-skabeloner:</h4>
                    
                    <div class="form-group">
                        <label>Slideshow:</label>
                        <div class="json-content">
{
    "slides": [
        {
            "image": "slide1.jpg",
            "title": "Slide titel",
            "subtitle": "Undertitel",
            "link": "/link-sti"
        }
    ],
    "autoplay": true,
    "interval": 5000
}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Produkt:</label>
                        <div class="json-content">
{
    "image": "produkt.png",
    "background_color": "#D6D58E",
    "title": "Produkttitel",
    "subtitle": "Produktbeskrivelse",
    "link": "/produkt-link"
}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
