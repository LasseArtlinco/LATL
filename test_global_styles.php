<?php
// test_global_styles.php - Direkte test af global styles API
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inkluder nødvendige filer
require_once 'config.php';
require_once 'db.php';

// Standard global styles
$defaultStyles = [
    'color_palette' => [
        'primary' => '#042940',
        'secondary' => '#005C53',
        'accent' => '#9FC131',
        'bright' => '#DBF227',
        'background' => '#D6D58E',
        'text' => '#042940'
    ],
    'font_config' => [
        'heading' => [
            'font-family' => "'Allerta Stencil', sans-serif",
            'font-weight' => '400'
        ],
        'body' => [
            'font-family' => "'Open Sans', sans-serif",
            'font-weight' => '400'
        ],
        'price' => [
            'font-family' => "'Allerta Stencil', sans-serif",
            'font-weight' => '400'
        ],
        'button' => [
            'font-family' => "'Open Sans', sans-serif",
            'font-weight' => '600'
        ]
    ],
    'global_styles' => [
        'css' => "/* Global styling */\nbody {\n  line-height: 1.6;\n}\n\n.container {\n  max-width: 1200px;\n  margin: 0 auto;\n  padding: 0 15px;\n}\n\n.button {\n  transition: all 0.3s ease;\n}\n\n.button:hover {\n  transform: translateY(-2px);\n  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\n}"
    ]
];

// Styling for output
echo '<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Styles API Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            margin-top: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 500px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Global Styles API Test</h1>';

try {
    // Test om vi kan få forbindelse til databasen
    $db = Database::getInstance();
    echo '<p class="success">✅ Database-forbindelse oprettet</p>';
    
    // Test om layout_config-tabellen eksisterer
    $tables = $db->select("SHOW TABLES LIKE 'layout_config'");
    if (empty($tables)) {
        echo '<p class="warning">⚠️ layout_config-tabellen findes ikke. Forsøger at oprette den...</p>';
        
        // Opret tabellen
        $query = "
            CREATE TABLE IF NOT EXISTS layout_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id VARCHAR(255) NOT NULL UNIQUE,
                layout_data JSON,
                global_styles JSON,
                font_config JSON,
                color_palette JSON,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $db->query($query);
        echo '<p class="success">✅ layout_config-tabellen oprettet</p>';
    } else {
        echo '<p class="success">✅ layout_config-tabellen findes</p>';
    }
    
    // Test om global-posten findes
    $global = $db->selectOne("SELECT * FROM layout_config WHERE page_id = 'global'");
    if (!$global) {
        echo '<p class="warning">⚠️ global-posten findes ikke. Forsøger at oprette den...</p>';
        
        // Opret posten
        $db->insert('layout_config', [
            'page_id' => 'global',
            'layout_data' => json_encode($defaultStyles)
        ]);
        
        echo '<p class="success">✅ global-posten oprettet</p>';
        
        // Hent den oprettede post
        $global = $db->selectOne("SELECT * FROM layout_config WHERE page_id = 'global'");
    } else {
        echo '<p class="success">✅ global-posten findes</p>';
    }
    
    // Vis indholdet af global-posten
    echo '<h2>Databaseindhold</h2>';
    echo '<pre>' . json_encode($global, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    
    // Dekoder layout_data
    if (isset($global['layout_data'])) {
        $layoutData = json_decode($global['layout_data'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<h2>Dekodet layout_data</h2>';
            echo '<pre>' . json_encode($layoutData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            
            // API-resultat
            echo '<h2>API-resultat</h2>';
            echo '<pre>' . json_encode(['status' => 'success', 'data' => $layoutData], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        } else {
            echo '<p class="error">❌ Fejl ved dekodning af layout_data: ' . json_last_error_msg() . '</p>';
        }
    } else {
        echo '<p class="warning">⚠️ layout_data mangler i global-posten</p>';
    }
    
    // Simuler direkte API-kald
    echo '<h2>Test API-kald</h2>';
    echo '<button id="testApiButton">Test API-kald</button>';
    echo '<div id="apiResult" class="result"></div>';
    
    echo '<script>
        document.getElementById("testApiButton").addEventListener("click", async () => {
            const resultElement = document.getElementById("apiResult");
            resultElement.innerHTML = "<p>Tester API...</p>";
            
            try {
                const response = await fetch("/api/layout/global/styles");
                const status = response.status;
                
                if (response.ok) {
                    try {
                        const data = await response.json();
                        resultElement.innerHTML = `
                            <p class="success">✅ API-kald vellykket (status: ${status})</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        `;
                    } catch (e) {
                        const text = await response.text();
                        resultElement.innerHTML = `
                            <p class="error">❌ Fejl ved parsing af JSON:</p>
                            <pre>${text}</pre>
                        `;
                    }
                } else {
                    resultElement.innerHTML = `
                        <p class="error">❌ API-kald fejlede med status ${status}</p>
                        <pre>${await response.text()}</pre>
                    `;
                }
            } catch (error) {
                resultElement.innerHTML = `
                    <p class="error">❌ Fejl ved API-kald: ${error.message}</p>
                `;
            }
        });
    </script>';
    
} catch (Exception $e) {
    echo '<p class="error">❌ Fejl: ' . $e->getMessage() . '</p>';
}

echo '</body>
</html>';
