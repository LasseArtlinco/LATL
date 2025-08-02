<?php
// session-debug.php - Placer denne fil i rodmappen for at debugge session-problemer

// Start session hvis ikke allerede startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sæt en test-værdi
if (!isset($_SESSION['test_time'])) {
    $_SESSION['test_time'] = date('Y-m-d H:i:s');
}

// Vis session-information
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Session Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        pre {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .warning {
            color: orange;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="info-box">
        <h2>Session Status</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Værdi</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><code><?= session_id() ?: 'Ingen session' ?></code></td>
                <td><?= session_id() ? '<span class="success">✓ OK</span>' : '<span class="error">✗ Problem</span>' ?></td>
            </tr>
            <tr>
                <td>Session Status</td>
                <td>
                    <?php
                    $status = session_status();
                    switch($status) {
                        case PHP_SESSION_DISABLED:
                            echo 'DISABLED';
                            break;
                        case PHP_SESSION_NONE:
                            echo 'NONE';
                            break;
                        case PHP_SESSION_ACTIVE:
                            echo 'ACTIVE';
                            break;
                    }
                    ?>
                </td>
                <td><?= $status === PHP_SESSION_ACTIVE ? '<span class="success">✓ OK</span>' : '<span class="error">✗ Problem</span>' ?></td>
            </tr>
            <tr>
                <td>Session Save Path</td>
                <td><code><?= session_save_path() ?: ini_get('session.save_path') ?: 'Standard' ?></code></td>
                <td>
                    <?php
                    $save_path = session_save_path() ?: ini_get('session.save_path');
                    if ($save_path && is_writable($save_path)) {
                        echo '<span class="success">✓ Skrivbar</span>';
                    } elseif (!$save_path) {
                        echo '<span class="warning">⚠ Bruger standard</span>';
                    } else {
                        echo '<span class="error">✗ Ikke skrivbar</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Session Cookie Name</td>
                <td><code><?= session_name() ?></code></td>
                <td><span class="success">✓ OK</span></td>
            </tr>
            <tr>
                <td>Session Cookie Domain</td>
                <td><code><?= ini_get('session.cookie_domain') ?: '(ikke sat)' ?></code></td>
                <td><?= ini_get('session.cookie_domain') ? '<span class="success">✓ OK</span>' : '<span class="warning">⚠ Standard</span>' ?></td>
            </tr>
            <tr>
                <td>Session Cookie Path</td>
                <td><code><?= ini_get('session.cookie_path') ?></code></td>
                <td><span class="success">✓ OK</span></td>
            </tr>
            <tr>
                <td>Session Cookie Secure</td>
                <td><code><?= ini_get('session.cookie_secure') ? 'Ja' : 'Nej' ?></code></td>
                <td><?= (ini_get('session.cookie_secure') && $_SERVER['HTTPS']) || !$_SERVER['HTTPS'] ? '<span class="success">✓ OK</span>' : '<span class="warning">⚠ Check HTTPS</span>' ?></td>
            </tr>
            <tr>
                <td>Session Cookie HttpOnly</td>
                <td><code><?= ini_get('session.cookie_httponly') ? 'Ja' : 'Nej' ?></code></td>
                <td><?= ini_get('session.cookie_httponly') ? '<span class="success">✓ OK</span>' : '<span class="warning">⚠ Anbefales</span>' ?></td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <h2>Session Data</h2>
        <pre><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
    </div>
    
    <div class="info-box">
        <h2>Cookies</h2>
        <pre><?= htmlspecialchars(print_r($_COOKIE, true)) ?></pre>
    </div>
    
    <div class="info-box">
        <h2>Server Information</h2>
        <table>
            <tr>
                <td>Server Software</td>
                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Ukendt' ?></td>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><?= phpversion() ?></td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><code><?= $_SERVER['DOCUMENT_ROOT'] ?></code></td>
            </tr>
            <tr>
                <td>Script Path</td>
                <td><code><?= __FILE__ ?></code></td>
            </tr>
            <tr>
                <td>Request URI</td>
                <td><code><?= $_SERVER['REQUEST_URI'] ?></code></td>
            </tr>
            <tr>
                <td>HTTP Host</td>
                <td><code><?= $_SERVER['HTTP_HOST'] ?></code></td>
            </tr>
            <tr>
                <td>HTTPS</td>
                <td><?= !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'Ja' : 'Nej' ?></td>
            </tr>
        </table>
    </div>
    
    <div class="info-box">
        <h2>Test Session Funktionalitet</h2>
        <p>Test-tidspunkt gemt i session: <strong><?= $_SESSION['test_time'] ?? 'Ingen værdi' ?></strong></p>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="action" value="set_admin">Sæt Admin Session</button>
        </form>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="action" value="clear_admin">Ryd Admin Session</button>
        </form>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="action" value="destroy">Ødelæg Hele Session</button>
        </form>
        
        <?php
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'set_admin':
                    $_SESSION['admin_authenticated'] = true;
                    echo '<p class="success">Admin session sat!</p>';
                    break;
                case 'clear_admin':
                    unset($_SESSION['admin_authenticated']);
                    echo '<p class="warning">Admin session ryddet!</p>';
                    break;
                case 'destroy':
                    session_destroy();
                    echo '<p class="error">Session ødelagt! Genindlæs siden.</p>';
                    break;
            }
        }
        ?>
        
        <p style="margin-top: 20px;">
            <a href="admin/">Gå til Admin</a> | 
            <a href="session-debug.php">Genindlæs denne side</a>
        </p>
    </div>
    
    <div class="info-box">
        <h2>Mulige Løsninger</h2>
        <?php if (ini_get('session.cookie_domain') && strpos($_SERVER['HTTP_HOST'], ini_get('session.cookie_domain')) === false): ?>
            <p class="error"><strong>Problem:</strong> Cookie domain matcher ikke current host!</p>
            <p>Tilføj dette til din config.php:</p>
            <pre>ini_set('session.cookie_domain', '');</pre>
        <?php endif; ?>
        
        <?php if (!session_id()): ?>
            <p class="error"><strong>Problem:</strong> Session kunne ikke startes!</p>
            <p>Check PHP error logs og session.save_path permissions.</p>
        <?php endif; ?>
        
        <?php if (empty($_COOKIE)): ?>
            <p class="error"><strong>Problem:</strong> Ingen cookies modtaget!</p>
            <p>Check om cookies er aktiveret i browseren.</p>
        <?php endif; ?>
    </div>
</body>
</html>
