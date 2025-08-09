<?php
// includes/band-renderer.php - Forbedret bånd-renderer med SEO og responsive billeder
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/image_handler.php';

/**
 * Renderer et bånd baseret på type og indhold
 * 
 * @param array $band Bånddata fra databasen
 */
function render_band($band) {
    $type = $band['band_type'];
    $content = is_array($band['band_content']) ? $band['band_content'] : json_decode($band['band_content'], true);
    $height = $band['band_height'];
    
    echo "<div class='band band-{$type} band-height-{$height}'>";
    
    switch ($type) {
        case 'slideshow':
            render_slideshow_band($content);
            break;
        case 'product':
            render_product_band($content);
            break;
        case 'product_cards':
            render_product_cards_band($content);
            break;
        case 'product_full':
            render_product_full_band($content);
            break;
        case 'html':
            render_html_band($content);
            break;
        case 'related_products':
            render_related_products_band($content);
            break;
        case 'link':
            render_link_band($content);
            break;
        default:
            echo "<div class='container'><p>Ukendt båndtype: {$type}</p></div>";
    }
    
    echo "</div>"; // .band
}

/**
 * Renderer et slideshow-bånd
 * 
 * @param array $content Båndindhold
 */
function render_slideshow_band($content) {
    $slides = $content['slides'] ?? [];
    $autoplay = $content['autoplay'] ?? false;
    $interval = $content['interval'] ?? 5000;
    $title = $content['title'] ?? '';
    $description = $content['description'] ?? '';
    
    // Debug info
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<!-- DEBUG: Slideshow content: " . json_encode($content) . " -->";
        echo "<!-- DEBUG: Number of slides found: " . count($slides) . " -->";
    }
    
    // Slideshow ID for ARIA og kontroller
    $slideshowId = 'slideshow-' . uniqid();
    
    echo "<div class='slideshow' id='{$slideshowId}' data-autoplay='" . ($autoplay ? 'true' : 'false') . "' data-interval='{$interval}' role='region' aria-roledescription='carousel' aria-label='{$title}'>";
    
    // SEO: Struktureret data med JSON-LD
    if (!empty($content['seo_schema'])) {
        echo "<script type='application/ld+json'>";
        echo $content['seo_schema'];
        echo "</script>";
    } else if (count($slides) > 0) {
        // Auto-generér struktureret data hvis det ikke er angivet
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => $title,
            'description' => $description,
            'image' => []
        ];
        
        foreach ($slides as $slide) {
            if (!empty($slide['image'])) {
                $schema['image'][] = [
                    '@type' => 'ImageObject',
                    'name' => $slide['title'] ?? '',
                    'description' => $slide['seo_description'] ?? ($slide['subtitle'] ?? ''),
                    'contentUrl' => get_full_url(format_image_path($slide['image'] ?? ''))
                ];
            }
        }
        
        echo "<script type='application/ld+json'>";
        echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "</script>";
    }
    
    if ($title) {
        echo "<h2 class='slideshow-title'>{$title}</h2>";
    }
    
    if ($description) {
        echo "<div class='slideshow-description'>{$description}</div>";
    }
    
    echo "<div class='slides'>";
    
    foreach ($slides as $index => $slide) {
        $active = $index === 0 ? 'active' : '';
        $image = $slide['image'] ?? '';
        $title = htmlspecialchars($slide['title'] ?? '');
        $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
        $alt = htmlspecialchars($slide['alt'] ?? $title);
        $link = htmlspecialchars($slide['link'] ?? '');
        
        echo "<div class='slide {$active}' role='group' aria-roledescription='slide' aria-label='Slide " . ($index + 1) . "'>";
        
        if ($link) {
            echo "<a href='{$link}' aria-label='{$title}'>";
        }
        
        // Brug billedet med korrekt formatering
        if (!empty($image)) {
            $imagePath = format_image_path($image);
            
            // Prøv forskellige størrelser som fallback
            $imageHTML = generate_responsive_image_tag($imagePath, $alt, 'slide-image', ($index === 0 ? 'eager' : 'lazy'));
            echo $imageHTML;
        } else {
            echo "<div class='slide-image-placeholder'>Intet billede</div>";
        }
        
        echo "<div class='slide-content'>";
        
        if ($title) {
            echo "<h3 class='slide-title'>{$title}</h3>";
        }
        
        if ($subtitle) {
            echo "<p class='slide-subtitle'>{$subtitle}</p>";
        }
        
        echo "</div>"; // .slide-content
        
        if ($link) {
            echo "</a>";
        }
        
        echo "</div>"; // .slide
    }
    
    echo "</div>"; // .slides
    
    // Navigation
    if (count($slides) > 1) {
        echo "<div class='slideshow-nav'>";
        echo "<button class='prev' aria-label='Forrige slide'><span class='sr-only'>Forrige</span>❮</button>";
        echo "<div class='indicators' role='tablist'>";
        
        foreach ($slides as $index => $slide) {
            $active = $index === 0 ? 'active' : '';
            $label = htmlspecialchars($slide['title'] ?? 'Slide ' . ($index + 1));
            echo "<button class='indicator {$active}' data-slide='{$index}' role='tab' aria-label='{$label}' aria-selected='" . ($active ? 'true' : 'false') . "'></button>";
        }
        
        echo "</div>"; // .indicators
        echo "<button class='next' aria-label='Næste slide'><span class='sr-only'>Næste</span>❯</button>";
        echo "</div>"; // .slideshow-nav
    }
    
    echo "</div>"; // .slideshow
}

/**
 * Renderer et produkt-bånd
 * 
 * @param array $content Båndindhold
 */
function render_product_band($content) {
    $image = $content['image'] ?? '';
    $bgColor = htmlspecialchars($content['background_color'] ?? '#ffffff');
    $title = htmlspecialchars($content['title'] ?? '');
    $subtitle = htmlspecialchars($content['subtitle'] ?? '');
    $link = htmlspecialchars($content['link'] ?? '');
    $alt = htmlspecialchars($content['alt'] ?? $title);
    $buttonText = htmlspecialchars($content['button_text'] ?? 'Se mere');
    
    // Product ID for ARIA
    $productId = 'product-' . uniqid();
    
    echo "<div id='{$productId}' class='product-band' style='background-color: {$bgColor};'>";
    
    // SEO: Struktureret data med JSON-LD
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $content['seo_title'] ?? $title,
        'description' => $content['seo_description'] ?? $subtitle,
    ];
    
    if (!empty($image)) {
        $schema['image'] = get_full_url(format_image_path($image));
    }
    
    echo "<script type='application/ld+json'>";
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "</script>";
    
    echo "<div class='container'>";
    echo "<div class='product-inner'>";
    
    if ($link) {
        echo "<a href='{$link}' class='product-link' aria-label='{$title}'>";
    }
    
    echo "<div class='product-image'>";
    
    if (!empty($image)) {
        $imagePath = format_image_path($image);
        $imageHTML = generate_responsive_image_tag($imagePath, $alt, 'product-image-file', 'lazy');
        echo $imageHTML;
    } else {
        echo "<div class='product-image-placeholder'>Intet billede</div>";
    }
    
    echo "</div>"; // .product-image
    
    echo "<div class='product-content'>";
    
    if ($title) {
        echo "<h3 class='product-title'>{$title}</h3>";
    }
    
    if ($subtitle) {
        echo "<p class='product-subtitle'>{$subtitle}</p>";
    }
    
    if ($link) {
        echo "<div class='product-cta'>";
        echo "<span class='button'>{$buttonText}</span>";
        echo "</div>"; // .product-cta
    }
    
    echo "</div>"; // .product-content
    
    if ($link) {
        echo "</a>"; // .product-link
    }
    
    echo "</div>"; // .product-inner
    echo "</div>"; // .container
    echo "</div>"; // .product-band
}

/**
 * Renderer produkt kort bånd
 */
function render_product_cards_band($content) {
    $products = $content['products'] ?? [];
    $title = htmlspecialchars($content['title'] ?? '');
    
    echo "<div class='product-cards-band'>";
    echo "<div class='container'>";
    
    if ($title) {
        echo "<h2 class='band-title'>{$title}</h2>";
    }
    
    echo "<div class='product-grid'>";
    
    foreach ($products as $product) {
        $productTitle = htmlspecialchars($product['title'] ?? '');
        $productPrice = htmlspecialchars($product['price'] ?? '');
        $productImage = $product['image'] ?? '';
        $productLink = htmlspecialchars($product['link'] ?? '');
        
        echo "<div class='product-card'>";
        
        if ($productLink) {
            echo "<a href='{$productLink}' class='product-card-link'>";
        }
        
        if ($productImage) {
            $imagePath = format_image_path($productImage);
            echo generate_responsive_image_tag($imagePath, $productTitle, 'product-card-image', 'lazy');
        }
        
        echo "<div class='product-card-content'>";
        echo "<h3 class='product-card-title'>{$productTitle}</h3>";
        
        if ($productPrice) {
            echo "<p class='product-card-price'>{$productPrice} kr</p>";
        }
        
        echo "</div>";
        
        if ($productLink) {
            echo "</a>";
        }
        
        echo "</div>";
    }
    
    echo "</div>"; // .product-grid
    echo "</div>"; // .container
    echo "</div>"; // .product-cards-band
}

/**
 * Renderer HTML bånd
 */
function render_html_band($content) {
    $html = $content['html'] ?? '';
    $customClass = htmlspecialchars($content['custom_class'] ?? '');
    
    echo "<div class='html-band {$customClass}'>";
    echo "<div class='container'>";
    echo $html; // HTML indhold - ikke escaped da det er custom HTML
    echo "</div>";
    echo "</div>";
}

/**
 * Renderer relaterede produkter bånd
 */
function render_related_products_band($content) {
    // Dette ville typisk hente produkter fra databasen baseret på kategori eller tags
    $title = htmlspecialchars($content['title'] ?? 'Relaterede produkter');
    $productIds = $content['product_ids'] ?? [];
    
    echo "<div class='related-products-band'>";
    echo "<div class='container'>";
    echo "<h2 class='band-title'>{$title}</h2>";
    
    // Her ville du hente produkter fra databasen
    // For nu bare vis placeholder
    echo "<div class='product-grid'>";
    echo "<p>Relaterede produkter kommer snart...</p>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
}

/**
 * Renderer link bånd
 */
function render_link_band($content) {
    $links = $content['links'] ?? [];
    $title = htmlspecialchars($content['title'] ?? '');
    
    echo "<div class='link-band'>";
    echo "<div class='container'>";
    
    if ($title) {
        echo "<h2 class='band-title'>{$title}</h2>";
    }
    
    echo "<div class='link-grid'>";
    
    foreach ($links as $link) {
        $linkTitle = htmlspecialchars($link['title'] ?? '');
        $linkUrl = htmlspecialchars($link['url'] ?? '');
        $linkDescription = htmlspecialchars($link['description'] ?? '');
        $linkIcon = htmlspecialchars($link['icon'] ?? '');
        
        echo "<a href='{$linkUrl}' class='link-item'>";
        
        if ($linkIcon) {
            echo "<i class='{$linkIcon} link-icon'></i>";
        }
        
        echo "<div class='link-content'>";
        echo "<h3 class='link-title'>{$linkTitle}</h3>";
        
        if ($linkDescription) {
            echo "<p class='link-description'>{$linkDescription}</p>";
        }
        
        echo "</div>";
        echo "</a>";
    }
    
    echo "</div>"; // .link-grid
    echo "</div>"; // .container
    echo "</div>"; // .link-band
}

/**
 * Hjælpefunktion til at få fuld URL med domæne
 */
function get_full_url($path) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'] ?? 'new.leatherandthelikes.dk';
    return $protocol . $domain . $path;
}

/**
 * Formaterer billedsti til brug i HTML
 * Håndterer alle varianter af billedstier korrekt
 */
function format_image_path($path) {
    if (empty($path)) {
        return '/placeholder-image.png';
    }
    
    $original_path = $path;
    
    // Hvis stien allerede starter med /uploads, returner den direkte
    if (strpos($path, '/uploads/') === 0) {
        return $path;
    }
    
    // Hvis stien starter med uploads/ (uden slash), tilføj slash
    if (strpos($path, 'uploads/') === 0) {
        return '/' . $path;
    }
    
    // Hvis stien starter med public/uploads, fjern public/ og tilføj slash
    if (strpos($path, 'public/uploads/') === 0) {
        return '/' . substr($path, 7); // Fjerner 'public/' og tilføjer '/'
    }
    
    // Debug-udskrift
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("format_image_path - Original: $original_path, Formatted: $path");
    }
    
    // Standard fallback - antag det er en relativ sti
    return '/' . ltrim($path, '/');
}

/**
 * Genererer responsive image tag med fallback
 */
function generate_responsive_image_tag($imagePath, $alt = '', $class = '', $loading = 'lazy') {
    // Prøv forskellige størrelser som fallback
    $sizes = ['large', 'medium', 'small'];
    $basePath = $imagePath;
    
    // Fjern eksisterende størrelse fra stien hvis den findes
    foreach ($sizes as $size) {
        $basePath = str_replace("/{$size}/", '/', $basePath);
    }
    
    // Generer srcset
    $srcset = [];
    foreach ($sizes as $size) {
        $sizePath = str_replace('/uploads/', "/uploads/{$size}/", $basePath);
        $srcset[] = $sizePath . ' ' . get_size_width($size) . 'w';
    }
    
    // Brug large som default src
    $defaultSrc = str_replace('/uploads/', '/uploads/large/', $basePath);
    
    $html = '<img src="' . htmlspecialchars($defaultSrc) . '"';
    $html .= ' srcset="' . htmlspecialchars(implode(', ', $srcset)) . '"';
    $html .= ' sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"';
    $html .= ' alt="' . htmlspecialchars($alt) . '"';
    
    if ($class) {
        $html .= ' class="' . htmlspecialchars($class) . '"';
    }
    
    $html .= ' loading="' . $loading . '"';
    $html .= ' onerror="this.onerror=null; this.src=\'/placeholder-image.png\';"';
    $html .= '>';
    
    return $html;
}

/**
 * Hjælpefunktion til at få bredde for størrelse
 */
function get_size_width($size) {
    $widths = [
        'small' => 300,
        'medium' => 600,
        'large' => 1200,
        'hero' => 1920
    ];
    
    return $widths[$size] ?? 800;
}
?>
