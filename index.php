<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL.dk - Leather and the Likes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .api-links {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .api-links h2 {
            margin-top: 0;
        }
        .api-links ul {
            list-style-type: none;
            padding-left: 0;
        }
        .api-links li {
            margin-bottom: 8px;
        }
        .api-links a {
            color: #0066cc;
            text-decoration: none;
        }
        .api-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>LATL.dk API Test</h1>
    
    <div class="api-links">
        <h2>API Links</h2>
        <ul>
            <li><a href="api/products">GET /api/products</a> - Hent alle produkter</li>
            <li><a href="api/orders">GET /api/orders</a> - Hent alle ordrer</li>
            <li><a href="api/layout">GET /api/layout</a> - Hent alle layouts</li>
        </ul>
    </div>
    
    <h2>Velkommen til LATL.dk API</h2>
    <p>
        Dette er et midlertidigt testmiljø for LATL.dk API. Når frontend er klar, vil denne side blive erstattet med den faktiske webshop.
    </p>
    
    <h3>Kom godt i gang</h3>
    <ol>
        <li>Kør <a href="db_setup.php">db_setup.php</a> for at opsætte databasen</li>
        <li>Test API endpoints ved at klikke på linkene ovenfor</li>
        <li>Implementer frontend-delen for at interagere med API'et</li>
    </ol>
</body>
</html>