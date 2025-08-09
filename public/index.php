<?php
// Inkludér nødvendige filer
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
$seo_title = htmlspecialchars($layout['title'] ?? 'LATL.dk - Læder og Laserskæring');
$seo_description = htmlspecialchars($layout['meta_description'] ?? 'Håndlavede lædervarer og laserskæring i høj kvalitet fra LATL.dk.');
$canonical_url = SITE_URL . ($_SERVER['REQUEST_URI'] != '/' ? $_SERVER['REQUEST_URI'] : '');
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
    <meta property="og:image" content="<?= SITE_URL ?>/images/og-image.jpg">
    
    <!-- Struktureret data for hjemmeside -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "LATL.dk - Læder og Laserskæring",
        "url": "<?= SITE_URL ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= SITE_URL ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <!-- Preconnect til eksterne ressourcer -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Allerta+Stencil&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Global styling -->
    <style>
        :root {
            /* Farvepalette fra databasen */
            <?php if (!empty($global_styles['color_palette'])): ?>
            <?php foreach ($global_styles['color_palette'] as $key => $value): ?>
            --<?= $key ?>-color: <?= $value ?>;
            <?php endforeach; ?>
            <?php else: ?>
            /* Fallback farver */
            --primary-color: #042940;
            --secondary-color: #005C53;
            --background-color: #D6D58E;
            --text-color: #042940;
            --accent-color: #9FC131;
            --bright-color: #DBF227;
            <?php endif; ?>
        }
        
        /* Reset og basis styling */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Open Sans', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--background-color);
            overflow-x: hidden;
        }
        
        /* Skrifttyper fra database */
        <?php if (!empty($global_styles['font_config'])): ?>
        <?php foreach ($global_styles['font_config'] as $element => $config): ?>
        .<?= $element ?> {
            font-family: <?= $config['font-family'] ?>;
            font-weight: <?= $config['font-weight'] ?>;
        }
        <?php endforeach; ?>
        <?php endif; ?>
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Allerta Stencil', sans-serif;
            color: var(--primary-color);
            margin-top: 0;
            line-height: 1.2;
        }
        
        /* Container og layout */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header */
        .site-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .site-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Allerta Stencil', sans-serif;
            font-size: 1.8rem;
            color: var(--bright-color);
            text-decoration: none;
            transition: transform 0.3s;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        /* Navigation */
        nav {
            display: flex;
            align-items: center;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1.5rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            position: relative;
        }
        
        nav a:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--bright-color);
            transition: width 0.3s;
        }
        
        nav a:hover {
            color: var(--bright-color);
        }
        
        nav a:hover:after {
            width: 100%;
        }
        
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .cart {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--bright-color);
            color: var(--primary-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Bånd system */
        .band {
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .band-height-1 { min-height: 25vh; }
        .band-height-2 { min-height: 50vh; }
        .band-height-3 { min-height: 75vh; }
        .band-height-4 { min-height: 100vh; }
        
        /* SLIDESHOW FIXES */
        .band-slideshow {
            background-color: var(--primary-color);
            overflow: hidden;
            position: relative;
        }
        
        .slideshow {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .slides {
            display: flex !important;
            transition: transform 0.5s ease-in-out;
            height: 100%;
            width: 100%;
            position: relative;
        }
        
        .slide {
            flex: 0 0 100%;
            min-width: 100%;
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .slide-image, 
        .slide img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .slide-image-placeholder,
        .placeholder-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            color: #999;
            font-style: italic;
        }
        
        .slide-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(to top, rgba(4, 41, 64, 0.9), transparent);
            color: white;
            text-align: center;
            z-index: 2;
        }
        
        .slide-title {
            font-family: 'Allerta Stencil', sans-serif;
            font-size: clamp(1.5rem, 4vw, 3rem);
            color: var(--bright-color);
            margin-bottom: 0.5rem;
        }
        
        .slide-subtitle {
            font-size: clamp(1rem, 2vw, 1.5rem);
            opacity: 0.95;
        }
        
        /* Navigation pile - RETTET POSITIONERING */
        .slideshow-nav {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            z-index: 10;
            pointer-events: none;
        }
        
        .slideshow-nav .prev,
        .slideshow-nav .next {
            pointer-events: all;
            background: rgba(4, 41, 64, 0.8);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s;
            position: relative;
            top: auto;
            bottom: auto;
            transform: none;
        }
        
        .slideshow-nav .prev:hover,
        .slideshow-nav .next:hover {
            background: var(--accent-color);
            transform: scale(1.1);
        }
        
        .slideshow-nav .indicators {
            position: absolute;
            bottom: -100px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.75rem;
            pointer-events: all;
        }
        
        .indicator {
            width: 12px !important;
            height: 12px !important;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5) !important;
            border: 2px solid white !important;
            cursor: pointer;
            transition: all 0.3s;
            padding: 0;
        }
        
        .indicator.active {
            background: var(--bright-color) !important;
            transform: scale(1.3);
        }
        
        /* PRODUCT BAND FIXES */
        .band-product {
            display: flex;
            align-items: center;
            padding: 3rem 0;
            min-height: inherit;
        }
        
        .product-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            width: 100%;
        }
        
        .product-link {
            display: contents;
            text-decoration: none;
            color: inherit;
        }
        
        .product-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .product-image:hover {
            transform: translateY(-5px);
        }
        
        .product-image-file,
        .product-image img {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: contain;
        }
        
        .product-image-placeholder {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            color: #999;
            border-radius: 10px;
        }
        
        .product-content {
            padding: 0 2rem;
        }
        
        .product-title {
            font-family: 'Allerta Stencil', sans-serif;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .product-subtitle {
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .product-cta {
            margin-top: 1.5rem;
        }
        
        .button {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--accent-color), var(--bright-color));
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(159, 193, 49, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(159, 193, 49, 0.4);
        }
        
        /* Footer */
        .site-footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            font-family: 'Allerta Stencil', sans-serif;
            color: var(--bright-color);
            margin-bottom: 1rem;
        }
        
        .footer-section p,
        .footer-section a {
            color: white;
            text-decoration: none;
            line-height: 1.8;
        }
        
        .footer-section a:hover {
            color: var(--bright-color);
            text-decoration: underline;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        /* Cookie Banner */
        #cookieBanner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(4, 41, 64, 0.95);
            color: white;
            padding: 1.5rem;
            z-index: 9999;
            backdrop-filter: blur(10px);
            transform: translateY(100%);
            transition: transform 0.3s;
        }
        
        #cookieBanner.show {
            transform: translateY(0);
        }
        
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }
        
        .cookie-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .cookie-button {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        #acceptCookies {
            background: var(--bright-color);
            color: var(--primary-color);
        }
        
        #declineCookies {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        /* Tilgængelighed */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color);
            color: white;
            padding: 8px;
            z-index: 100;
            text-decoration: none;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Responsivt design */
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
                background-color: var(--primary-color);
                flex-direction: column;
                justify-content: center;
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
            }
            
            nav.active {
                transform: translateX(0);
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
                gap: 2rem;
            }
            
            nav a {
                font-size: 1.2rem;
            }
            
            .product-inner {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .product-title {
                font-size: 1.8rem;
            }
            
            .slideshow-nav {
                padding: 0 1rem;
            }
            
            .slideshow-nav .prev,
            .slideshow-nav .next {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .band-height-1 { min-height: 40vh; }
            .band-height-2 { min-height: 60vh; }
            .band-height-3 { min-height: 80vh; }
            
            .cookie-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Globale CSS-regler fra database */
        <?= $global_styles['global_styles']['css'] ?? '' ?>
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
                <a href="/kurv" class="cart">
                    <i class="fas fa-shopping-cart"></i> Kurv 
                    <span class="cart-count" style="display: none;">0</span>
                </a>
            </nav>
        </div>
    </header>

    <!-- Bånd-indhold -->
    <main id="main-content" tabindex="-1">
        <?php if (!empty($bands)): ?>
            <?php foreach ($bands as $band): ?>
                <?php render_band($band); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="band band-html band-height-2">
                <div class="container">
                    <h1>Velkommen til LATL.dk</h1>
                    <p>Vi arbejder på at forbedre vores hjemmeside. Kom tilbage snart for at se vores håndlavede læderprodukter og laserskæringstjenester.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>LATL.dk</h3>
                    <p>Håndlavede lædervarer og laserskæring i høj kvalitet</p>
                    <p>Horsens Banegård</p>
                </div>
                <div class="footer-section">
                    <h3>Links</h3>
                    <p><a href="/shop">Shop</a></p>
                    <p><a href="/konfigurator">Konfigurator</a></p>
                    <p><a href="/om-os">Om os</a></p>
                    <p><a href="/kontakt">Kontakt</a></p>
                </div>
                <div class="footer-section">
                    <h3>Information</h3>
                    <p><a href="/handelsbetingelser">Handelsbetingelser</a></p>
                    <p><a href="/privatlivspolitik">Privatlivspolitik</a></p>
                    <p><a href="/levering">Levering</a></p>
                    <p><a href="/returnering">Returnering</a></p>
                </div>
                <div class="footer-section">
                    <h3>Kontakt</h3>
                    <p>Email: info@latl.dk</p>
                    <p>Telefon: +45 XX XX XX XX</p>
                    <p>CVR: XXXXXXXX</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LATL.dk - Alle rettigheder forbeholdes</p>
                <p><a href="#" id="cookieSettings" style="color: var(--bright-color);">Cookie-indstillinger 🍪</a></p>
            </div>
        </div>
    </footer>
    
    <!-- Cookie Banner -->
    <div id="cookieBanner">
        <div class="cookie-content">
            <div>
                <strong>Vi bruger cookies 🍪</strong>
                <p>Vi bruger cookies til at forbedre din oplevelse. Ingen personlige data gemmes.</p>
            </div>
            <div class="cookie-buttons">
                <button class="cookie-button" id="declineCookies">Kun nødvendige</button>
                <button class="cookie-button" id="acceptCookies">Accepter alle</button>
            </div>
        </div>
    </div>
    
    <!-- Inkluder main.js -->
    <script src="/public/js/main.js"></script>
    
    <!-- Inline scripts for image error handling -->
    <script>
    // Håndter billede fejl
    document.addEventListener('DOMContentLoaded', function() {
        var images = document.querySelectorAll('img');
        images.forEach(function(img) {
            img.addEventListener('error', function() {
                if (window.handleImageError) {
                    window.handleImageError(this);
                }
            });
        });
    });
    </script>
</body>
</html>
