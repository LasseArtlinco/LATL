<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Simpel admin-beskyttelse (erstat med ordentlig login senere)
$admin_password = 'admin123'; // Ændr dette til et sikkert password!

// Check for password
$is_authenticated = false;

if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
    $_SESSION['admin_authenticated'] = true;
    $is_authenticated = true;
} elseif (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    $is_authenticated = true;
}

// Logud
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Håndter tilføjelse af ny side
if ($is_authenticated && isset($_POST['add_page'])) {
    $page_id = trim($_POST['page_id']);
    $title = trim($_POST['title']);
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    if ($page_id && $title) {
        $db = Database::getInstance();
        
        try {
            // Check om side allerede eksisterer
            $existing = $db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$page_id]);
            
            if (!$existing) {
                $db->insert('layout_config', [
                    'page_id' => $page_id,
                    'title' => $title,
                    'meta_description' => $meta_description
                ]);
                
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=page_added');
                exit;
            } else {
                $error = 'En side med dette ID eksisterer allerede.';
            }
        } catch (Exception $e) {
            $error = 'Kunne ikke oprette side: ' . $e->getMessage();
        }
    } else {
        $error = 'Side ID og titel er påkrævet.';
    }
}

// Hent sider fra databasen
$pages = [];
if ($is_authenticated) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT page_id, title FROM layout_config WHERE page_id != 'global' ORDER BY page_id ASC");
    $pages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL Admin</title>
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
        
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-form h2 {
            margin-top: 0;
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
        
        input[type="password"],
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-400);
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(4, 41, 64, 0.1);
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .admin-nav {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .admin-nav a {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
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
        
        .page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .page-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid var(--gray-200);
        }
        
        .page-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .page-card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .page-id {
            color: var(--gray-600);
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .page-actions a {
            display: inline-block;
            padding: 6px 12px;
            text-decoration: none;
            color: white;
            background-color: var(--primary-color);
            border-radius: 4px;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .page-actions a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }
        
        .add-page-section {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-200);
        }
        
        .add-page-section h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.2);
            color: var(--success-color);
        }
        
        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.2);
            color: var(--danger-color);
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid var(--gray-200);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                flex-direction: column;
            }
            
            .admin-nav a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php if ($is_authenticated): ?>
        <header class="admin-header">
            <h1 class="admin-title">LATL Admin</h1>
            <a href="?logout=1" class="admin-logout">
                <i class="fas fa-sign-out-alt"></i> Log ud
            </a>
        </header>
        
        <div class="container">
            <div class="admin-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        switch ($_GET['success']) {
                            case 'page_added':
                                echo 'Siden blev oprettet.';
                                break;
                            default:
                                echo 'Handlingen blev udført.';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <nav class="admin-nav">
                    <a href="index.php" class="active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="band-editor.php?page=forside">
                        <i class="fas fa-layer-group"></i> Bånd-editor
                    </a>
                    <a href="design-editor.php">
                        <i class="fas fa-paint-brush"></i> Design & Styling
                    </a>
                </nav>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <p class="stat-number"><?= count($pages) ?></p>
                        <p class="stat-label">Sider</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-number">
                            <?php
                            $db = Database::getInstance();
                            $bandCount = $db->selectOne("SELECT COUNT(*) as count FROM layout_bands");
                            echo $bandCount['count'] ?? 0;
                            ?>
                        </p>
                        <p class="stat-label">Bånd total</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-number">2</p>
                        <p class="stat-label">Båndtyper</p>
                    </div>
                </div>
                
                <h2>Administrer sider</h2>
                
                <div class="page-grid">
                    <?php foreach ($pages as $page): ?>
                        <div class="page-card">
                            <h3><?= htmlspecialchars($page['title']) ?></h3>
                            <p class="page-id">
                                <i class="fas fa-link"></i> /<?= htmlspecialchars($page['page_id']) ?>
                            </p>
                            
                            <div class="page-actions">
                                <a href="band-editor.php?page=<?= urlencode($page['page_id']) ?>">
                                    <i class="fas fa-edit"></i> Rediger bånd
                                </a>
                                <a href="/<?= urlencode($page['page_id']) ?>" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Vis side
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="add-page-section">
                    <h3><i class="fas fa-plus-circle"></i> Tilføj ny side</h3>
                    <form action="index.php" method="post">
                        <div class="form-group">
                            <label for="page_id">Side ID (URL):</label>
                            <input type="text" name="page_id" id="page_id" required 
                                   pattern="[a-z0-9-]+" 
                                   title="Kun små bogstaver, tal og bindestreger"
                                   placeholder="f.eks. om-os">
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Sidetitel:</label>
                            <input type="text" name="title" id="title" required
                                   placeholder="f.eks. Om os">
                        </div>
                        
                        <div class="form-group">
                            <label for="meta_description">Meta beskrivelse (SEO):</label>
                            <textarea name="meta_description" id="meta_description" 
                                      placeholder="Kort beskrivelse af siden til søgemaskiner"></textarea>
                        </div>
                        
                        <button type="submit" name="add_page">
                            <i class="fas fa-plus"></i> Tilføj side
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="login-form">
                <h2><i class="fas fa-lock"></i> Log ind</h2>
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" required autofocus>
                    </div>
                    <button type="submit">
                        <i class="fas fa-sign-in-alt"></i> Log ind
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
