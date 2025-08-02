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
    
    // Slideshow ID for ARIA og kontroller
    $slideshowId = 'slideshow-' . uniqid();
    
    echo "<div class='slideshow' id='{$slideshowId}' data-autoplay='{$autoplay}' data-interval='{$interval}' role='region' aria-roledescription='carousel' aria-label='{$title}'>";
    
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
            $schema['image'][] = [
                '@type' => 'ImageObject',
                'name' => $slide['title'] ?? '',
                'description' => $slide['seo_description'] ?? ($slide['subtitle'] ?? ''),
                'contentUrl' => get_full_url('/uploads/' . ($slide['image'] ?? ''))
            ];
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
    
    echo "<div class='slides'>";
    
    foreach ($slides as $index => $slide) {
        $active = $index === 0 ? 'active' : '';
        $image = htmlspecialchars($slide['image'] ?? '');
        $title = htmlspecialchars($slide['title'] ?? '');
        $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
        $alt = htmlspecialchars($slide['alt'] ?? $title);
        $link = htmlspecialchars($slide['link'] ?? '');
        
        echo "<div class='slide {$active}' role='group' aria-roledescription='slide' aria-label='Slide " . ($index + 1) . "'>";
        
        if ($link) {
            echo "<a href='{$link}' aria-label='{$title}'>";
        }
        
        // Brug responsive billeder med WebP support
        echo "<picture>";
        
        // WebP version
        echo "<source type='image/webp' srcset='";
        echo "/uploads/slideshow/large/{$image}.webp 1200w, ";
        echo "/uploads/slideshow/medium/{$image}.webp 600w, ";
        echo "/uploads/slideshow/small/{$image}.webp 300w";
        echo "' sizes='(max-width: 767px) 100vw, (max-width: 1200px) 1200px, 100vw'>";
        
        // Original format som fallback
        echo "<source srcset='";
        echo "/uploads/slideshow/large/{$image} 1200w, ";
        echo "/uploads/slideshow/medium/{$image} 600w, ";
        echo "/uploads/slideshow/small/{$image} 300w";
        echo "' sizes='(max-width: 767px) 100vw, (max-width: 1200px) 1200px, 100vw'>";
        
        // Fallback img-tag
        echo "<img src='/uploads/{$image}' alt='{$alt}' class='slide-image' loading='" . ($index === 0 ? 'eager' : 'lazy') . "'>";
        echo "</picture>";
        
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
    $image = htmlspecialchars($content['image'] ?? '');
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
        'image' => get_full_url('/uploads/' . $image)
    ];
    
    echo "<script type='application/ld+json'>";
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "</script>";
    
    echo "<div class='container'>";
    echo "<div class='product-inner'>";
    
    if ($link) {
        echo "<a href='{$link}' class='product-link' aria-label='{$title}'>";
    }
    
    echo "<div class='product-image'>";
    
    // Brug responsive billeder med WebP support for produkter
    echo "<picture>";
    
    // WebP version
    echo "<source type='image/webp' srcset='";
    echo "/uploads/product/large/{$image}.webp 1200w, ";
    echo "/uploads/product/medium/{$image}.webp 600w, ";
    echo "/uploads/product/small/{$image}.webp 300w";
    echo "' sizes='(max-width: 767px) 300px, (max-width: 1200px) 600px, 1200px'>";
    
    // Original format som fallback
    echo "<source srcset='";
    echo "/uploads/product/large/{$image} 1200w, ";
    echo "/uploads/product/medium/{$image} 600w, ";
    echo "/uploads/product/small/{$image} 300w";
    echo "' sizes='(max-width: 767px) 300px, (max-width: 1200px) 600px, 1200px'>";
    
    // Fallback img-tag
    echo "<img src='/uploads/{$image}' alt='{$alt}' class='product-image-file' loading='lazy'>";
    echo "</picture>";
    
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
?>
