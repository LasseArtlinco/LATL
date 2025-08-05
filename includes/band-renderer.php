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
    
    echo "<div class='slideshow' id='{$slideshowId}' data-autoplay='{$autoplay}' data-interval='{$interval}' role='region' aria-roledescription='carousel' aria-label='{$title}' style='border: 1px solid red;'>";
    
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
                    'contentUrl' => get_full_url('/' . format_image_path($slide['image'] ?? ''))
                ];
            }
        }
        
        echo "<script type='application/ld+json'>";
        echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "</script>";
    }
    
    echo "<div class='container'>";
    
    if ($title) {
        echo "<h2 class='slideshow-title'>{$title}</h2>";
    }
    
    if ($description) {
        echo "<div class='slideshow-description'>{$description}</div>";
    }
    
    echo "<div class='slides' style='border: 1px solid blue; display: flex; overflow: hidden;'>";
    
    // Tilføj synlige debug-data
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #ffeeee; padding: 10px; margin: 10px 0; border: 2px solid #ffaaaa; font-size: 14px;'>";
        echo "<h4>Slideshow Debug Data:</h4>";
        echo "Slideshow ID: {$slideshowId}<br>";
        echo "Autoplay: " . ($autoplay ? 'Yes' : 'No') . "<br>";
        echo "Interval: {$interval}ms<br>";
        echo "Slides Found: " . count($slides) . "<br>";
        echo "</div>";
    }
    
    foreach ($slides as $index => $slide) {
        $active = $index === 0 ? 'active' : '';
        $image = $slide['image'] ?? '';
        $title = htmlspecialchars($slide['title'] ?? '');
        $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
        $alt = htmlspecialchars($slide['alt'] ?? $title);
        $link = htmlspecialchars($slide['link'] ?? '');
        
        // Debug-information om denne slide
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<!-- DEBUG: Slide " . ($index + 1) . " billede: {$image} -->";
            echo "<div style='background: #eeffee; padding: 5px; margin: 5px; border: 1px dashed green; font-size: 12px;'>";
            echo "<strong>Slide Debug:</strong><br>";
            echo "Title: " . htmlspecialchars($title) . "<br>";
            echo "Subtitle: " . htmlspecialchars($subtitle) . "<br>";
            echo "Image: " . htmlspecialchars($image) . "<br>";
            echo "</div>";
        }
        
        echo "<div class='slide {$active}' role='group' aria-roledescription='slide' aria-label='Slide " . ($index + 1) . "' style='flex: 0 0 100%; min-width: 100%;'>";
        
        if ($link) {
            echo "<a href='{$link}' aria-label='{$title}'>";
        }
        
        // Brug billedet direkte som det er gemt i databasen
        if (!empty($image)) {
            $imagePath = format_image_path($image);
            
            // Debug info om billedsti
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                echo "<!-- DEBUG: Original image path: {$image} -->";
                echo "<!-- DEBUG: Formatted image path: {$imagePath} -->";
                // Test direct URL access
                echo "<div style='background-color: lightyellow; padding: 5px; margin: 5px; font-size: 12px;'>";
                echo "Testing image path: <a href='{$imagePath}' target='_blank'>{$imagePath}</a>";
                echo "</div>";
            }
            
            echo "<img src='{$imagePath}' alt='{$alt}' class='slide-image' loading='" . ($index === 0 ? 'eager' : 'lazy') . "' style='width: 100%; height: 100%; object-fit: cover;'>";
        } else {
            // Vis fejlbesked hvis der ikke er et billede
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
        echo "<button class='prev' aria-label='Forrige slide'><span class='sr-only'>Forrige</span><i class='fas fa-chevron-left' aria-hidden='true'></i></button>";
        echo "<div class='indicators' role='tablist'>";
        
        foreach ($slides as $index => $slide) {
            $active = $index === 0 ? 'active' : '';
            $label = htmlspecialchars($slide['title'] ?? 'Slide ' . ($index + 1));
            echo "<button class='indicator {$active}' data-slide='{$index}' role='tab' aria-label='{$label}' aria-selected='" . ($active ? 'true' : 'false') . "'></button>";
        }
        
        echo "</div>"; // .indicators
        echo "<button class='next' aria-label='Næste slide'><span class='sr-only'>Næste</span><i class='fas fa-chevron-right' aria-hidden='true'></i></button>";
        echo "</div>"; // .slideshow-nav
    }
    
    echo "</div>"; // .container
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
    
    // Debug info
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<!-- DEBUG: Product image: {$image} -->";
    }
    
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
        $schema['image'] = get_full_url('/' . format_image_path($image));
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
    
    // Brug billedet direkte som det er gemt i databasen
    if (!empty($image)) {
        $imagePath = format_image_path($image);
        
        // Debug info om billedsti
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<!-- DEBUG: Original product image path: {$image} -->";
            echo "<!-- DEBUG: Formatted product image path: {$imagePath} -->";
        }
        
        echo "<img src='{$imagePath}' alt='{$alt}' class='product-image-file' loading='lazy'>";
    } else {
        // Vis fejlbesked hvis der ikke er et billede
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
 * Hjælpefunktion til at få fuld URL med domæne
 */
function get_full_url($path) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    return $protocol . $domain . $path;
}

/**
 * Formaterer billedsti til brug i HTML
 * Fjerner 'public/' fra starten hvis det findes og sikrer korrekt formatering
 */
function format_image_path($path) {
    // Fjern 'public/' fra starten hvis det findes
    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7);
    }
    
    // Sørg for at stien begynder med en slash
    if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }
    
    // Debug-udskrift
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Original image path: $path");
        error_log("After formatting: $path");
    }
    
    return $path;
}
?>
