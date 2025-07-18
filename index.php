<?php
// standalone_index.php - Simpel statisk version af forsiden til test
// Placer denne fil i roden af din webserver

// Eksempel layout data
$mockData = [
    "page_id" => "forside",
    "layout_data" => [
        "title" => "LATL.dk - Læder og Laserskæring",
        "meta_description" => "Håndlavede lædervarer og laserskæring i høj kvalitet fra LATL.dk."
    ],
    "bands" => [
        [
            "band_type" => "slideshow",
            "band_height" => 3,
            "band_order" => 1,
            "band_content" => [
                "full_width" => true,
                "slides" => [
                    [
                        "image" => "https://images.unsplash.com/photo-1605030753481-64d14b4d4251?q=80&w=1974&auto=format&fit=crop",
                        "title" => "Håndlavede lædervarer",
                        "description" => "Unikke og holdbare produkter lavet med omhu og kærlighed til håndværket.",
                        "link" => "/shop",
                        "link_text" => "Se vores udvalg"
                    ],
                    [
                        "image" => "https://images.unsplash.com/photo-1551717551-7d5f490d201f?q=80&w=2070&auto=format&fit=crop",
                        "title" => "Laserskæring i højeste kvalitet",
                        "description" => "Præcision og detaljer, der gør hvert produkt unikt og personligt.",
                        "link" => "/custom",
                        "link_text" => "Design dit eget produkt"
                    ],
                    [
                        "image" => "https://images.unsplash.com/photo-1505287892375-fca331c9d0cd?q=80&w=2071&auto=format&fit=crop",
                        "title" => "Bæredygtigt håndværk",
                        "description" => "Vi bruger kun de bedste materialer og bæredygtige produktionsmetoder.",
                        "link" => "/about",
                        "link_text" => "Læs mere om os"
                    ]
                ]
            ]
        ],
        [
            "band_type" => "html",
            "band_height" => 1,
            "band_order" => 2,
            "band_content" => [
                "background_class" => "bg-primary",
                "align" => "center",
                "padding" => "3rem 1rem",
                "html" => "<h2 style='color: white; font-size: 2.2rem; margin-bottom: 1.5rem;'>Velkommen til LATL.dk</h2><p style='color: white; max-width: 800px; margin: 0 auto; font-size: 1.1rem;'>Vi specialiserer os i håndlavede lædervarer og laserskæring af højeste kvalitet. Hvert produkt er skabt med omhu, præcision og kærlighed til håndværket.</p>"
            ]
        ],
        [
            "band_type" => "product",
            "band_height" => 2,
            "band_order" => 3,
            "band_content" => [
                "title" => "Udvalgte produkter",
                "description" => "Se vores mest populære håndlavede lædervarer",
                "show_more_link" => "/shop",
                "show_more_text" => "Se alle produkter",
                "products" => [
                    [
                        "title" => "Læder Pung",
                        "description" => "Håndlavet pung i førsteklasses læder. Holdbar og elegant med plads til kort og sedler.",
                        "price" => 599,
                        "image" => "https://images.unsplash.com/photo-1627123424574-724758594e93?q=80&w=1974&auto=format&fit=crop"
                    ],
                    [
                        "title" => "Nøglering med monogram",
                        "description" => "Personlig nøglering i læder med dit valgte monogram laserskåret i materialet.",
                        "price" => 249,
                        "image" => "https://images.unsplash.com/photo-1626871550973-99d0644f8b12?q=80&w=1974&auto=format&fit=crop"
                    ],
                    [
                        "title" => "Læder Armbånd",
                        "description" => "Stilrent armbånd i blødt læder med håndlavede detaljer og justerbar størrelse.",
                        "price" => 349,
                        "image" => "https://images.unsplash.com/photo-1600592736018-a531a7fb82bc?q=80&w=1974&auto=format&fit=crop"
                    ]
                ]
            ]
        ],
        [
            "band_type" => "link",
            "band_height" => 1,
            "band_order" => 4,
            "band_content" => [
                "background_class" => "bg-accent",
                "title" => "Design din egen læderprodukt",
                "description" => "Skab et unikt og personligt produkt med vores online konfigurator",
                "link" => "/konfigurator",
                "link_text" => "Start din design nu",
                "link_style" => "button",
                "button_color" => "#042940",
                "button_text_color" => "#ffffff"
            ]
        ],
        [
            "band_type" => "html",
            "band_height" => 2,
            "band_order" => 5,
            "band_content" => [
                "title" => "Vores proces",
                "max_width" => "1000",
                "html" => "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;'><div style='text-align: center;'><div style='background-color: var(--primary-color); color: white; width: 80px; height: 80px; display: flex; justify-content: center; align-items: center; border-radius: 50%; margin: 0 auto 1rem auto; font-size: 2rem; font-weight: bold;'>1</div><h3>Design</h3><p>Vi starter med et unikt design, der passer til dine behov og ønsker. For konfigurerbare produkter kan du selv stå for designet.</p></div><div style='text-align: center;'><div style='background-color: var(--primary-color); color: white; width: 80px; height: 80px; display: flex; justify-content: center; align-items: center; border-radius: 50%; margin: 0 auto 1rem auto; font-size: 2rem; font-weight: bold;'>2</div><h3>Laserskæring</h3><p>Med præcision og omhu laserskærer vi materialet efter designet, så alle detaljer kommer til deres ret.</p></div><div style='text-align: center;'><div style='background-color: var(--primary-color); color: white; width: 80px; height: 80px; display: flex; justify-content: center; align-items: center; border-radius: 50%; margin: 0 auto 1rem auto; font-size: 2rem; font-weight: bold;'>3</div><h3>Håndarbejde</h3><p>Hver produkt samles og færdiggøres i hånden for at sikre den højeste kvalitet og finish.</p></div><div style='text-align: center;'><div style='background-color: var(--primary-color); color: white; width: 80px; height: 80px; display: flex; justify-content: center; align-items: center; border-radius: 50%; margin: 0 auto 1rem auto; font-size: 2rem; font-weight: bold;'>4</div><h3>Levering</h3><p>Dit færdige produkt pakkes omhyggeligt ind og sendes direkte til din dør med hurtig levering.</p></div></div>"
            ]
        ]
    ],
    "global_styles" => [
        "css" => "/* Globale stilarter */\nbody { line-height: 1.6; }\n.container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }"
    ],
    "font_config" => [
        "heading" => [
            "font-family" => "'Allerta Stencil', sans-serif",
            "font-weight" => "400"
        ],
        "body" => [
            "font-family" => "'Open Sans', sans-serif",
            "font-weight" => "400"
        ],
        "price" => [
            "font-family" => "'Allerta Stencil', sans-serif",
            "font-weight" => "400"
        ],
        "button" => [
            "font-family" => "'Open Sans', sans-serif",
            "font-weight" => "600"
        ]
    ],
    "color_palette" => [
        "primary" => "#042940",
        "secondary" => "#005C53",
        "accent" => "#9FC131",
        "bright" => "#DBF227",
        "background" => "#D6D58E",
        "text" => "#042940"
    ]
];
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL.dk - Læder og Laserskæring</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allerta+Stencil&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style id="globalStyles">
        /* Global stilarter */
        :root {
            --primary-color: <?php echo $mockData['color_palette']['primary']; ?>;
            --secondary-color: <?php echo $mockData['color_palette']['secondary']; ?>;
            --accent-color: <?php echo $mockData['color_palette']['accent']; ?>;
            --bright-color: <?php echo $mockData['color_palette']['bright']; ?>;
            --background-color: <?php echo $mockData['color_palette']['background']; ?>;
            --text-color: <?php echo $mockData['color_palette']['text']; ?>;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: <?php echo $mockData['font_config']['body']['font-family']; ?>;
            font-weight: <?php echo $mockData['font_config']['body']['font-weight']; ?>;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: <?php echo $mockData['font_config']['heading']['font-family']; ?>;
            font-weight: <?php echo $mockData['font_config']['heading']['font-weight']; ?>;
        }
        
        .latl-header {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
        }
        
        .latl-logo {
            font-family: 'Allerta Stencil', sans-serif;
            font-size: 1.5rem;
            text-decoration: none;
            font-weight: bold;
            color: white;
        }
        
        .latl-nav {
            display: flex;
            gap: 1rem;
        }
        
        .latl-nav a {
            text-decoration: none;
            padding: 0.5rem;
            color: white;
        }
        
        .latl-footer {
            padding: 2rem 1rem;
            margin-top: 2rem;
            background-color: var(--secondary-color);
            color: white;
        }
        
        .latl-footer a {
            color: var(--bright-color);
        }
        
        .band {
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .band-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .band.full-width .band-content {
            max-width: 100%;
            padding: 0;
        }
        
        /* Båndtyper */
        .band-slideshow {
            position: relative;
            overflow: hidden;
        }
        
        .slideshow-container {
            display: flex;
            transition: transform 0.5s ease-in-out;
            width: 100%;
            height: 100%;
        }
        
        .slideshow-slide {
            flex: 0 0 100%;
            height: 100%;
            background-position: center;
            background-size: cover;
            position: relative;
        }
        
        .slide-content {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            max-width: 1200px;
            width: 100%;
            padding: 0 2rem;
            box-sizing: border-box;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        }
        
        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0) 50%);
        }
        
        .slideshow-nav {
            position: absolute;
            bottom: 1rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .slideshow-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
        }
        
        .slideshow-dot.active {
            background-color: white;
        }
        
        .band-product {
            padding: 4rem 0;
        }
        
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: white;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .product-image-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-title {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }
        
        .product-price {
            font-family: <?php echo $mockData['font_config']['price']['font-family']; ?>;
            font-weight: <?php echo $mockData['font_config']['price']['font-weight']; ?>;
            font-size: 1.4rem;
            margin: 1rem 0;
            color: var(--primary-color);
        }
        
        .product-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--accent-color);
            color: var(--primary-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: <?php echo $mockData['font_config']['button']['font-family']; ?>;
            font-weight: <?php echo $mockData['font_config']['button']['font-weight']; ?>;
            transition: background-color 0.3s, transform 0.2s;
            text-align: center;
            text-decoration: none;
            width: 100%;
        }
        
        .product-button:hover {
            background-color: var(--bright-color);
            transform: translateY(-2px);
        }
        
        .band-html {
            padding: 4rem 0;
        }
        
        .band-html.bg-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .band-html.bg-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .band-html.bg-accent {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .band-html.bg-bright {
            background-color: var(--bright-color);
            color: var(--primary-color);
        }
        
        .band-link {
            padding: 4rem 0;
            text-align: center;
        }
        
        .band-link.bg-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .band-link.bg-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .band-link.bg-accent {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .band-link.bg-bright {
            background-color: var(--bright-color);
            color: var(--primary-color);
        }
        
        .band-link h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .band-link p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
        }
        
        .band-link a {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .band-link a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        
        .cookie-banner button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }
            
            .latl-header {
                flex-direction: column;
                padding: 1rem 0;
            }
            
            .latl-nav {
                margin-top: 1rem;
            }
        }
        
        /* Tilføj eventuelle ekstra stilarter her */
        <?php echo $mockData['global_styles']['css'] ?? ''; ?>
    </style>
</head>
<body>
    <header class="latl-header">
        <a href="/" class="latl-logo">LATL.dk</a>
        <nav class="latl-nav">
            <a href="/shop">Shop</a>
            <a href="/about">Om os</a>
            <a href="/contact">Kontakt</a>
            <a href="/cart">Kurv (0)</a>
        </nav>
    </header>
    
    <main id="page-content">
        <?php
        // Render hver bånd
        $bands = $mockData['bands'];
        // Sorter bånd efter rækkefølge
        usort($bands, function($a, $b) {
            return $a['band_order'] - $b['band_order'];
        });
        
        foreach ($bands as $band) {
            $bandClass = 'band band-' . $band['band_type'];
            if (isset($band['band_content']['full_width']) && $band['band_content']['full_width']) {
                $bandClass .= ' full-width';
            }
            if (isset($band['band_content']['background_class'])) {
                $bandClass .= ' ' . $band['band_content']['background_class'];
            }
            
            $bandStyle = '';
            $height = $band['band_height'] * 300; // 300px som basis højde
            if ($band['band_type'] === 'slideshow') {
                $bandStyle = 'height: ' . $height . 'px;';
            } else {
                $bandStyle = 'min-height: ' . $height . 'px;';
            }
            
            if (isset($band['band_content']['background_color'])) {
                $bandStyle .= ' background-color: ' . $band['band_content']['background_color'] . ';';
            }
            
            if (isset($band['band_content']['text_color'])) {
                $bandStyle .= ' color: ' . $band['band_content']['text_color'] . ';';
            }
            
            echo '<div class="' . $bandClass . '" style="' . $bandStyle . '">';
            
            switch ($band['band_type']) {
                case 'slideshow':
                    renderSlideshowBand($band);
                    break;
                    
                case 'product':
                case 'product_card':
                case 'product_full':
                    renderProductBand($band);
                    break;
                    
                case 'html':
                    renderHtmlBand($band);
                    break;
                    
                case 'link':
                    renderLinkBand($band);
                    break;
                    
                default:
                    echo '<div class="band-content"><p>Ukendt båndtype: ' . $band['band_type'] . '</p></div>';
            }
            
            echo '</div>';
        }
        
        // Funktioner til at rendere bånd
        function renderSlideshowBand($band) {
            $content = $band['band_content'];
            $slides = $content['slides'] ?? [];
            
            if (empty($slides)) {
                echo '<div class="band-content"><p>Ingen slides fundet.</p></div>';
                return;
            }
            
            echo '<div class="slideshow-container">';
            
            foreach ($slides as $index => $slide) {
                echo '<div class="slideshow-slide" style="background-image: url(\'' . $slide['image'] . '\');">';
                echo '<div class="slide-overlay"></div>';
                
                if (isset($slide['title']) || isset($slide['description']) || isset($slide['link'])) {
                    echo '<div class="slide-content">';
                    
                    if (isset($slide['title'])) {
                        echo '<h2 style="font-size: 2.5rem; margin-bottom: 1rem;">' . $slide['title'] . '</h2>';
                    }
                    
                    if (isset($slide['description'])) {
                        echo '<p style="font-size: 1.2rem; max-width: 800px; margin-bottom: 1.5rem;">' . $slide['description'] . '</p>';
                    }
                    
                    if (isset($slide['link'])) {
                        echo '<a href="' . $slide['link'] . '" style="display: inline-block; padding: 0.75rem 1.5rem; background-color: var(--accent-color); color: var(--primary-color); text-decoration: none; border-radius: 4px; font-weight: bold;">' . ($slide['link_text'] ?? 'Læs mere') . '</a>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            echo '</div>';
            
            echo '<div class="slideshow-nav">';
            foreach ($slides as $index => $slide) {
                $activeClass = $index === 0 ? ' active' : '';
                echo '<div class="slideshow-dot' . $activeClass . '" data-index="' . $index . '"></div>';
            }
            echo '</div>';
        }
        
        function renderProductBand($band) {
            $content = $band['band_content'];
            $products = $content['products'] ?? [];
            
            echo '<div class="band-content">';
            
            if (isset($content['title'])) {
                echo '<h2 style="text-align: center; margin-bottom: 2rem; font-size: 2.2rem;">' . $content['title'] . '</h2>';
            }
            
            if (isset($content['description'])) {
                echo '<p style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto; font-size: 1.1rem;">' . $content['description'] . '</p>';
            }
            
            if (empty($products)) {
                echo '<p style="text-align: center;">Ingen produkter fundet.</p>';
            } else {
                echo '<div class="product-container">';
                
                foreach ($products as $product) {
                    echo '<div class="product-card">';
                    
                    if (isset($product['image'])) {
                        echo '<div class="product-image-container">';
                        echo '<img src="' . $product['image'] . '" alt="' . ($product['title'] ?? 'Produkt') . '" class="product-image">';
                        echo '</div>';
                    }
                    
                    echo '<div class="product-info">';
                    
                    if (isset($product['title'])) {
                        echo '<h3 class="product-title">' . $product['title'] . '</h3>';
                    }
                    
                    if (isset($product['description'])) {
                        echo '<p>' . $product['description'] . '</p>';
                    }
                    
                    if (isset($product['price'])) {
                        echo '<div class="product-price">' . $product['price'] . ' kr.</div>';
                    }
                    
                    echo '<button class="product-button">Læg i kurv</button>';
                    
                    echo '</div>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            if (isset($content['show_more_link'])) {
                echo '<div style="text-align: center; margin-top: 3rem;">';
                echo '<a href="' . $content['show_more_link'] . '" class="product-button" style="max-width: 300px;">' . ($content['show_more_text'] ?? 'Se flere produkter') . '</a>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        function renderHtmlBand($band) {
            $content = $band['band_content'];
            $html = $content['html'] ?? '';
            
            $contentStyle = '';
            
            if (isset($content['align'])) {
                $contentStyle .= 'text-align: ' . $content['align'] . ';';
            }
            
            if (isset($content['max_width'])) {
                $contentStyle .= 'max-width: ' . $content['max_width'] . 'px;';
            }
            
            if (isset($content['padding'])) {
                $contentStyle .= 'padding: ' . $content['padding'] . ';';
            }
            
            echo '<div class="band-content" style="' . $contentStyle . '">';
            
            if (isset($content['title'])) {
                $titleAlign = $content['align'] ?? 'center';
                echo '<h2 style="text-align: ' . $titleAlign . '; margin-bottom: 2rem; font-size: 2.2rem;">' . $content['title'] . '</h2>';
            }
            
            echo '<div class="html-content">' . $html . '</div>';
            
            echo '</div>';
        }
        
        function renderLinkBand($band) {
            $content = $band['band_content'];
            
            echo '<div class="band-content">';
            
            if (isset($content['title'])) {
                echo '<h2 style="font-size: 2.5rem; margin-bottom: 1rem;">' . $content['title'] . '</h2>';
            }
            
            if (isset($content['description'])) {
                echo '<p style="font-size: 1.2rem; max-width: 800px; margin: 0 auto 2rem auto;">' . $content['description'] . '</p>';
            }
            
            if (isset($content['link'])) {
                echo '<div style="margin-top: 1rem;">';
                
                $linkStyle = '';
                $linkClass = 'button';
                
                if (isset($content['link_style'])) {
                    if ($content['link_style'] === 'button') {
                        $backgroundColor = $content['button_color'] ?? 'var(--accent-color)';
                        $textColor = $content['button_text_color'] ?? 'var(--primary-color)';
                        
                        $linkStyle = 'display: inline-block; padding: 1rem 2.5rem; font-size: 1.1rem; font-weight: bold; background-color: ' . $backgroundColor . '; color: ' . $textColor . '; border-radius: 4px; text-decoration: none;';
                    } else if ($content['link_style'] === 'text') {
                        $textColor = $content['text_color'] ?? 'var(--primary-color)';
                        $linkStyle = 'color: ' . $textColor . '; font-size: 1.1rem; font-weight: bold; text-decoration: underline;';
                    } else if ($content['link_style'] === 'arrow') {
                        $textColor = $content['text_color'] ?? 'var(--primary-color)';
                        $linkStyle = 'display: inline-flex; align-items: center; color: ' . $textColor . '; font-size: 1.1rem; font-weight: bold; text-decoration: none;';
                        
                        echo '<a href="' . $content['link'] . '" style="' . $linkStyle . '">';
                        echo $content['link_text'] ?? 'Læs mere';
                        echo ' <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
                        echo '</a>';
                        
                        echo '</div>';
                        echo '</div>';
                        return;
                    }
                }
                
                echo '<a href="' . $content['link'] . '" class="' . $linkClass . '" style="' . $linkStyle . '">' . ($content['link_text'] ?? 'Læs mere') . '</a>';
                
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
    </main>
    
    <footer class="latl-footer">
        <div class="band-content">
            <div class="footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <h3>LATL.dk</h3>
                    <p>Håndlavede lædervarer og laserskæring i høj kvalitet.</p>
                </div>
                <div>
                    <h3>Kontakt</h3>
                    <p>Email: info@latl.dk</p>
                    <p>Telefon: +45 12345678</p>
                </div>
                <div>
                    <h3>Information</h3>
                    <p><a href="/about">Om os</a></p>
                    <p><a href="/terms">Handelsbetingelser</a></p>
                    <p><a href="/privacy">Privatlivspolitik</a></p>
                </div>
            </div>
            <div style="margin-top: 2rem; text-align: center;">
                <p>&copy; 2025 LATL.dk - Alle rettigheder forbeholdes</p>
                <button id="cookieSettingsButton" style="background: none; border: none; text-decoration: underline; cursor: pointer; color: white;">Cookie-indstillinger</button>
            </div>
        </div>
    </footer>
    
    <div id="cookieBanner" class="cookie-banner" style="display: none;">
        <div>
            <p>Vi bruger cookies for at forbedre din oplevelse på vores hjemmeside.</p>
        </div>
        <div>
            <button id="acceptCookiesButton">Acceptér</button>
            <button id="rejectCookiesButton">Afvis</button>
        </div>
    </div>
    
    <script>
        // Cookie-håndtering
        document.addEventListener('DOMContentLoaded', function() {
            const cookieBanner = document.getElementById('cookieBanner');
            const acceptButton = document.getElementById('acceptCookiesButton');
            const rejectButton = document.getElementById('rejectCookiesButton');
            const settingsButton = document.getElementById('cookieSettingsButton');
            
            // Tjek om brugeren allerede har taget stilling til cookies
            const cookieConsent = localStorage.getItem('cookieConsent');
            
            if (cookieConsent === null) {
                // Hvis der ikke er taget stilling, vis banner
                cookieBanner.style.display = 'flex';
            }
            
            // Håndter accept af cookies
            acceptButton.addEventListener('click', function() {
                localStorage.setItem('cookieConsent', 'accepted');
                cookieBanner.style.display = 'none';
            });
            
            // Håndter afvisning af cookies
            rejectButton.addEventListener('click', function() {
                localStorage.setItem('cookieConsent', 'rejected');
                cookieBanner.style.display = 'none';
            });
            
            // Håndter klik på cookie-indstillinger
            settingsButton.addEventListener('click', function() {
                cookieBanner.style.display = 'flex';
            });
            
            // Slideshow funktionalitet
            document.querySelectorAll('.band-slideshow').forEach(function(slideshow) {
                const container = slideshow.querySelector('.slideshow-container');
                const dots = slideshow.querySelectorAll('.slideshow-dot');
                
                if (!container || !dots.length) return;
                
                let currentSlide = 0;
                const slideCount = dots.length;
                
                // Klik på dots
                dots.forEach(function(dot, index) {
                    dot.addEventListener('click', function() {
                        goToSlide(index);
                    });
                });
                
                // Gå til en bestemt slide
                function goToSlide(index) {
                    currentSlide = index;
                    container.style.transform = `translateX(-${index * 100}%)`;
                    
                    // Opdater aktive dots
                    dots.forEach(function(dot, idx) {
                        dot.classList.toggle('active', idx === index);
                    });
                }
                
                // Automatisk skift
                setInterval(function() {
                    currentSlide = (currentSlide + 1) % slideCount;
                    goToSlide(currentSlide);
                }, 5000);
            });
        });
    </script>
</body>
</html>
