<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/band-renderer.php';

// Hent den anmodede side (default: forside)
$page_id = isset($_GET['page']) ? $_GET['page'] : 'forside';

// Hent global styling
$global_styles = get_global_styles();

// Hent sidens layout og bånd
$layout = get_page_layout($page_id);
$bands = get_page_bands($page_id);

// SEO-relaterede data
$seo_title = htmlspecialchars($layout['title'] ?? 'LATL.dk');
$seo_description = htmlspecialchars($layout['meta_description'] ?? '');
$canonical_url = BASE_URL . ($_SERVER['REQUEST_URI'] != '/' ? $_SERVER['REQUEST_URI'] : '');
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $seo_title ?></title>
    <meta name="description" content="<?= $seo_description ?>">
    
    <!-- Kanonisk URL og sociale metatags -->
    <link rel="canonical" href="<?= $canonical_url ?>">
    <meta property="og:title" content="<?= $seo_title ?>">
    <meta property="og:description" content="<?= $seo_description ?>">
    <meta property="og:url" content="<?= $canonical_url ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="LATL.dk">
    
    <!-- Struktureret data for hjemmeside -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "LATL.dk - Læder og Laserskæring",
        "url": "<?= BASE_URL ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= BASE_URL ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allerta+Stencil&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Global styling -->
    <style>
        :root {
            /* Farvepalette fra databasen */
            <?php foreach ($global_styles['color_palette'] as $key => $value): ?>
            --<?= $key ?>-color: <?= $value ?>;
            <?php endforeach; ?>
        }
        
        /* Basis styling */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Open Sans', sans-serif;
            color: var(--text-color, #042940);
            line-height: 1.6;
        }
        
        /* Skrifttyper */
        <?php foreach ($global_styles['font_config'] as $element => $config): ?>
        .<?= $element ?> {
            font-family: <?= $config['font-family'] ?>;
            font-weight: <?= $config['font-weight'] ?>;
        }
        <?php endforeach; ?>
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Allerta Stencil', sans-serif;
            color: var(--primary-color, #042940);
            margin-top: 0;
        }
        
        /* Globale CSS-regler */
        <?= $global_styles['global_styles']['css'] ?? '' ?>
        
        /* Layout styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            width: 100%;
        }
        
        .site-header {
            background-color: var(--primary-color, #042940);
            color: white;
            padding: 1rem 0;
            position: relative;
            z-index: 100;
        }
        
        .site-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Allerta Stencil', sans-serif;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        nav {
            display: flex;
            align-items: center;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        nav li {
            margin-left: 1.5rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        nav a:hover {
            opacity: 0.8;
        }
        
        .cart {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-color, #9FC131);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .site-footer {
            background-color: var(--secondary-color, #005C53);
            color: white;
            padding: 2rem 0;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 1rem;
            padding-right: 2rem;
        }
        
        .footer-section h3 {
            color: white;
            margin-bottom: 1rem;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-section li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .footer-section a:hover {
            opacity: 0.8;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            text-align: center;
        }
        
        /* Bånd styling */
        .band {
            padding: 2rem 0;
        }
        
        .band-height-1 {
            min-height: 25vh;
        }
        
        .band-height-2 {
            min-height: 50vh;
        }
        
        .band-height-3 {
            min-height: 75vh;
        }
        
        .band-height-4 {
            min-height: 100vh;
        }
        
        /* Slideshow styling */
        .slideshow {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: 400px;
        }
        
        .slides {
            display: flex;
            transition: transform 0.5s ease-in-out;
            height: 100%;
        }
        
        .slide {
            flex: 0 0 100%;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
        }
        
        .slide:hover img {
            transform: scale(1.05);
        }
        
        .slide-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            color: white;
        }
        
        .slide-content h2 {
            color: white;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .slide-content p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .slideshow-nav {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            z-index: 10;
        }
        
        .slideshow-controls {
            display: flex;
            align-items: center;
        }
        
        .prev, .next {
            background: rgba(255, 255, 255, 0.3);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 1.2rem;
            transition: background 0.3s ease;
            margin: 0 5px;
        }
        
        .prev:hover, .next:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .indicators {
            display: flex;
            justify-content: center;
            margin: 0 auto;
        }
        
        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .indicator.active {
            background: white;
            transform: scale(1.2);
        }
        
        /* Product band styling */
        .product-band {
            display: flex;
            align-items: center;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-band:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .product-link {
            display: flex;
            width: 100%;
            text-decoration: none;
            color: inherit;
        }
        
        .product-image {
            flex: 0 0 40%;
            padding-right: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-image img {
            max-width: 100%;
            height: auto;
            transition: transform 0.3s ease;
        }
        
        .product-band:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-content {
            flex: 0 0 60%;
        }
        
        .product-content h2 {
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .product-cta {
            margin-top: 1.5rem;
        }
        
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color, #042940);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .button:hover {
            background-color: var(--secondary-color, #005C53);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Tilgængelighed */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color, #042940);
            color: white;
            padding: 8px;
            z-index: 100;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Responsivt design */
        @media (max-width: 992px) {
            .slideshow {
                height: 350px;
            }
            
            .slide-content h2 {
                font-size: 1.8rem;
            }
            
            .product-content h2 {
                font-size: 1.6rem;
            }
        }
        
        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: block;
            }
            
            nav {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: var(--primary-color, #042940);
                flex-direction: column;
                justify-content: center;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            
            nav.active {
                transform: translateX(0);
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
            }
            
            nav li {
                margin: 1rem 0;
            }
            
            .product-link {
                flex-direction: column;
            }
            
            .product-image, .product-content {
                flex: 0 0 100%;
                padding-right: 0;
            }
            
            .product-image {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .slideshow {
                height: 300px;
            }
            
            .slideshow-nav {
                padding: 0 1rem;
            }
            
            .prev, .next {
                width: 30px;
                height: 30px;
            }
            
            .slide-content {
                padding: 1rem;
            }
            
            .slide-content h2 {
                font-size: 1.5rem;
            }
            
            .slide-content p {
                font-size: 1rem;
            }
            
            .footer-content {
                flex-direction: column;
            }
            
            .footer-section {
                padding-right: 0;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .slideshow {
                height: 250px;
            }
            
            .band {
                padding: 1.5rem 0;
            }
            
            .product-band {
                padding: 1.5rem;
            }
            
            .band-height-1 {
                min-height: auto;
            }
            
            .band-height-2 {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Skip link for accessibility -->
    <a href="#main-content" class="skip-link">Gå til indhold</a>
    
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="/" class="logo">LATL.dk</a>
            <button class="mobile-nav-toggle" aria-label="Åbn menu" aria-expanded="false">☰</button>
            <nav>
                <ul>
                    <li><a href="/shop">Shop</a></li>
                    <li><a href="/konfigurator">Konfigurator</a></li>
                    <li><a href="/om-os">Om os</a></li>
                    <li><a href="/kontakt">Kontakt</a></li>
                </ul>
                <a href="/kurv" class="cart">Kurv <span class="cart-count">0</span></a>
            </nav>
        </div>
    </header>

    <!-- Bånd-indhold -->
    <main id="main-content" tabindex="-1">
        <?php foreach ($bands as $band): ?>
            <?php render_band($band); ?>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>LATL.dk</h3>
                    <p>Håndlavede lædervarer og laserskæring i høj kvalitet, designet og produceret i Danmark.</p>
                </div>
                <div class="footer-section">
                    <h3>Kontakt</h3>
                    <p>Email: kontakt@latl.dk</p>
                    <p>Telefon: +45 12 34 56 78</p>
                    <p>Adresse: Eksempelvej 123, 2750 Ballerup</p>
                </div>
                <div class="footer-section">
                    <h3>Links</h3>
                    <ul>
                        <li><a href="/shop">Shop</a></li>
                        <li><a href="/konfigurator">Konfigurator</a></li>
                        <li><a href="/om-os">Om os</a></li>
                        <li><a href="/kontakt">Kontakt</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Information</h3>
                    <ul>
                        <li><a href="/leveringsbetingelser">Levering</a></li>
                        <li><a href="/handelsbetingelser">Handelsbetingelser</a></li>
                        <li><a href="/privatlivspolitik">Privatlivspolitik</a></li>
                        <li><a href="/cookies">Cookies</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LATL.dk. Alle rettigheder forbeholdes.</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="/js/main.js"></script>
</body>
</html>
