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
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($layout['title'] ?? 'LATL.dk') ?></title>
    <meta name="description" content="<?= htmlspecialchars($layout['meta_description'] ?? '') ?>">
    
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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Open Sans', sans-serif;
            color: var(--text-color);
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
            color: var(--primary-color);
        }
        
        /* Globale CSS-regler */
        <?= $global_styles['global_styles']['css'] ?? '' ?>
        
        /* Layout styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .site-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 0;
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
        }
        
        .cart {
            color: white;
            text-decoration: none;
        }
        
        .site-footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 2rem 0;
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
        }
        
        .slides {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        
        .slide {
            flex: 0 0 100%;
            position: relative;
        }
        
        .slide img {
            width: 100%;
            height: auto;
        }
        
        .slide-content {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .slideshow-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            padding: 0 1rem;
        }
        
        .indicators {
            display: flex;
        }
        
        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.5);
            margin: 0 5px;
            border: none;
            cursor: pointer;
        }
        
        .indicator.active {
            background-color: white;
        }
        
        /* Product band styling */
        .product-band {
            display: flex;
            align-items: center;
            padding: 2rem;
            border-radius: 8px;
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
        }
        
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        
        .product-content {
            flex: 0 0 60%;
        }
        
        .product-cta {
            margin-top: 1.5rem;
        }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Responsivt design */
        @media (max-width: 768px) {
            .product-link {
                flex-direction: column;
            }
            
            .product-image, .product-content {
                flex: 0 0 100%;
                padding-right: 0;
            }
            
            .product-image {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="/" class="logo">LATL.dk</a>
            <nav>
                <ul>
                    <li><a href="/shop">Shop</a></li>
                    <li><a href="/about">Om os</a></li>
                    <li><a href="/contact">Kontakt</a></li>
                </ul>
            </nav>
            <a href="/cart" class="cart">Kurv (0)</a>
        </div>
    </header>

    <!-- Bånd-indhold -->
    <main>
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
                    <p>Håndlavede lædervarer og laserskæring i høj kvalitet.</p>
                </div>
                <div class="footer-section">
                    <h3>Kontakt</h3>
                    <p>Email: kontakt@latl.dk</p>
                    <p>Telefon: +45 12 34 56 78</p>
                </div>
                <div class="footer-section">
                    <h3>Links</h3>
                    <ul>
                        <li><a href="/shop">Shop</a></li>
                        <li><a href="/about">Om os</a></li>
                        <li><a href="/contact">Kontakt</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LATL.dk. Alle rettigheder forbeholdes.</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Enkel slideshow-funktionalitet
        document.addEventListener('DOMContentLoaded', function() {
            const slideshows = document.querySelectorAll('.slideshow');
            
            slideshows.forEach(function(slideshow) {
                const slides = slideshow.querySelector('.slides');
                const slideElements = slideshow.querySelectorAll('.slide');
                const indicators = slideshow.querySelectorAll('.indicator');
                const prevBtn = slideshow.querySelector('.prev');
                const nextBtn = slideshow.querySelector('.next');
                
                let currentSlide = 0;
                const slideCount = slideElements.length;
                
                // Hvis der kun er ét slide, gør vi ingenting
                if (slideCount <= 1) return;
                
                function goToSlide(index) {
                    slides.style.transform = `translateX(-${index * 100}%)`;
                    
                    // Opdater active class
                    slideElements.forEach((slide, i) => {
                        slide.classList.toggle('active', i === index);
                    });
                    
                    indicators.forEach((indicator, i) => {
                        indicator.classList.toggle('active', i === index);
                    });
                    
                    currentSlide = index;
                }
                
                function nextSlide() {
                    goToSlide((currentSlide + 1) % slideCount);
                }
                
                function prevSlide() {
                    goToSlide((currentSlide - 1 + slideCount) % slideCount);
                }
                
                // Event listeners
                if (prevBtn) prevBtn.addEventListener('click', prevSlide);
                if (nextBtn) nextBtn.addEventListener('click', nextSlide);
                
                indicators.forEach((indicator, i) => {
                    indicator.addEventListener('click', () => goToSlide(i));
                });
                
                // Autoplay
                const autoplay = slideshow.dataset.autoplay === 'true';
                const interval = parseInt(slideshow.dataset.interval) || 5000;
                
                let autoplayTimer;
                
                if (autoplay) {
                    autoplayTimer = setInterval(nextSlide, interval);
                    
                    // Pause autoplay on hover
                    slideshow.addEventListener('mouseenter', () => {
                        clearInterval(autoplayTimer);
                    });
                    
                    slideshow.addEventListener('mouseleave', () => {
                        autoplayTimer = setInterval(nextSlide, interval);
                    });
                }
            });
        });
    </script>
</body>
</html>
