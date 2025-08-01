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

// Hent sider fra databasen
$pages = [];
if ($is_authenticated) {
    $conn = get_db_connection();
    $stmt = $conn->query("SELECT page_id, title FROM layout_config WHERE page_id != 'global'");
    $pages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL Admin</title>
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
        
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
        }
        
        input[type="password"] {
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
        
        .page-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .page-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .page-title {
            margin-top: 0;
        }
        
        .page-actions {
            margin-top: 15px;
        }
        
        .page-actions a {
            display: inline-block;
            margin-right: 10px;
            text-decoration: none;
            color: #333;
        }
        
        .add-page {
            margin-top: 20px;
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
    </style>
</head>
<body>
    <?php if ($is_authenticated): ?>
        <header class="admin-header">
            <h1 class="admin-title">LATL Admin</h1>
            <a href="?logout=1" class="admin-logout">Log ud</a>
        </header>
        
        <div class="container">
            <div class="admin-content">
                <div class="admin-nav">
                    <a href="index.php">Sider</a>
                    <a href="layout-editor.php">Bånd-editor</a>
                </div>
                
                <h2>Sider</h2>
                
                <div class="page-list">
                    <?php foreach ($pages as $page): ?>
                        <div class="page-card">
                            <h3 class="page-title"><?= htmlspecialchars($page['title']) ?></h3>
                            <p>ID: <?= htmlspecialchars($page['page_id']) ?></p>
                            
                            <div class="page-actions">
                                <a href="layout-editor.php?page=<?= urlencode($page['page_id']) ?>">Rediger layout</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="add-page">
                    <h3>Tilføj ny side</h3>
                    <form action="index.php" method="post">
                        <div class="form-group">
                            <label for="page_id">Side ID:</label>
                            <input type="text" name="page_id" id="page_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Titel:</label>
                            <input type="text" name="title" id="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="meta_description">Meta beskrivelse:</label>
                            <textarea name="meta_description" id="meta_description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="add_page">Tilføj side</button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="login-form">
                <h2>Log ind</h2>
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button type="submit">Log ind</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
