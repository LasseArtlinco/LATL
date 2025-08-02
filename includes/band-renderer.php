<?php
// includes/band-renderer.php - Rendering af forskellige båndtyper
require_once __DIR__ . '/config.php';

// Sikrer at upload-mappe konstanter er defineret
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../public/uploads');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://new.leatherandthelikes.dk');
}

/**
 * Renderer et bånd baseret på type
 * 
 * @param array $band Bånd-data fra databasen
 */
function render_band($band) {
    $type = $band['band_type'];
    $content = $band['band_content'];
    $height = $band['band_height'];
    $band_id = $band['id'];
    
    echo "<section id='band-{$band_id}' class='band band-{$type} band-height-{$height}'>";
    
    switch ($type) {
        case 'slideshow':
            render_slideshow_band($content, $band_id);
            break;
        case 'product':
            render_product_band($content, $band_id);
            break;
        case 'html':
            render_html_band($content, $band_id);
            break;
        case 'link':
            render_link_band($content, $band_id);
            break;
        default:
            echo "<div class='content-wrapper'><p>Ukendt båndtype: {$type}</p></div>";
    }
    
    echo "</section>"; // .band
}

/**
 * Renderer et slideshow-bånd med responsivt design
 * 
 * @param array $content Båndets indhold
 * @param int $band_id Båndets ID
 */
function render_slideshow_band($content, $band_id) {
    $slides = $content['slides'] ?? [];
    $autoplay = $content['autoplay'] ?? false;
    $interval = $content['interval'] ?? 5000;
    
    // Struktureret data for slideshow (JSON-LD)
    $slideshow_data = [
        '@context' => 'https://schema.org',
        '@type' => 'ImageGallery',
        'name' => $content['title'] ?? 'Billedgalleri',
        'description' => $content['description'] ?? 'Vores billedgalleri',
        'image' => []
    ];
    
    echo "<div class='slideshow' id='slideshow-{$band_id}' data-autoplay='{$autoplay}' data-interval='{$interval}'>";
    echo "<div class='slides'>";
    
    foreach ($slides as $index => $slide) {
        $active = $index === 0 ? 'active' : '';
        $image = htmlspecialchars($slide['image']);
        $title = htmlspecialchars($slide['title']);
        $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
        $link = htmlspecialchars($slide['link'] ?? '');
        $image_alt = htmlspecialchars($slide['alt'] ?? $title);
        
        // Tilføj til struktureret data
        $slideshow_data['image'][] = [
            '@type' => 'ImageObject',
            'contentUrl' => BASE_URL . '/uploads/' . $image,
            'name' => $title,
            'description' => $subtitle,
            'caption' => $subtitle
        ];
        
        echo "<div class='slide {$active}'>";
        
        if ($link) {
            echo "<a href='{$link}' aria-label='{$title}'>";
        }
        
        // Sikrer at vi har et billede og det eksisterer
        if (!empty($image)) {
            $image_path = UPLOAD_PATH . '/' . $image;
            
            if (file_exists($image_path)) {
                echo "<img src='/uploads/{$image}' alt='{$image_alt}' class='slide-image' loading='" . ($index === 0 ? 'eager' : 'lazy') . "'>";
            } else {
                echo "<div class='placeholder-image'>Billede ikke fundet: {$image}</div>";
            }
        } else {
            // Vis et pladsholderbillede hvis billedet ikke findes
            echo "<div class='placeholder-image'>Intet billede</div>";
        }
        
        echo "<div class='slide-content'>";
        echo "<div class='content-wrapper'>";
        echo "<h2>{$title}</h2>";
        
        if ($subtitle) {
            echo "<p>{$subtitle}</p>";
        }
        echo "</div>"; // .content-wrapper
        echo "</div>"; // .slide-content
        
        if ($link) {
            echo "</a>";
        }
        
        echo "</div>"; // .slide
    }
    
    echo "</div>"; // .slides
    
    // Navigation - kun hvis der er flere slides
    if (count($slides) > 1) {
        echo "<div class='slideshow-nav'>";
        echo "<div class='content-wrapper'>";
        echo "<div class='slideshow-controls'>";
        echo "<button class='prev' aria-label='Forrige slide'>&#10094;</button>";
        
        echo "<div class='indicators'>";
        foreach ($slides as $index => $slide) {
            $active = $index === 0 ? 'active' : '';
            echo "<button class='indicator {$active}' data-slide='{$index}' aria-label='Gå til slide " . ($index + 1) . "'></button>";
        }
        echo "</div>"; // .indicators
        
        echo "<button class='next' aria-label='Næste slide'>&#10095;</button>";
        echo "</div>"; // .slideshow-controls
        echo "</div>"; // .content-wrapper
        echo "</div>"; // .slideshow-nav
    }
    
    echo "</div>"; // .slideshow
    
    // Output struktureret data
    echo "<script type='application/ld+json'>" . json_encode($slideshow_data) . "</script>";
}

/**
 * Renderer et produkt-bånd med responsivt design
 * 
 * @param array $content Båndets indhold
 * @param int $band_id Båndets ID
 */
function render_product_band($content, $band_id) {
    $image = htmlspecialchars($content['image'] ?? '');
    $bgColor = htmlspecialchars($content['background_color'] ?? '#ffffff');
    $title = htmlspecialchars($content['title'] ?? '');
    $subtitle = htmlspecialchars($content['subtitle'] ?? '');
    $link = htmlspecialchars($content['link'] ?? '');
    $image_alt = htmlspecialchars($content['alt'] ?? $title);
    $button_text = htmlspecialchars($content['button_text'] ?? 'Se mere');
    
    // Struktureret data for produkt (JSON-LD)
    $product_data = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $title,
        'description' => $subtitle
    ];
    
    if (!empty($image)) {
        $product_data['image'] = BASE_URL . '/uploads/' . $image;
    }
    
    if (!empty($link)) {
        $product_data['url'] = BASE_URL . $link;
    }
    
    echo "<div class='product-band' style='background-color: {$bgColor};'>";
    echo "<div class='content-wrapper'>";
    
    if ($link) {
        echo "<a href='{$link}' class='product-link' aria-label='{$title}'>";
    }
    
    echo "<div class='product-image'>";
    
    if (!empty($image)) {
        $image_path = UPLOAD_PATH . '/' . $image;
        
        if (file_exists($image_path)) {
            echo "<img src='/uploads/{$image}' alt='{$image_alt}' class='product-img'>";
        } else {
            echo "<div class='placeholder-image'>Billede ikke fundet: {$image}</div>";
        }
    } else {
        // Vis en pladsholder hvis billedet ikke findes
        echo "<div class='placeholder-image'>Intet billede</div>";
    }
    
    echo "</div>"; // .product-image
    
    echo "<div class='product-content'>";
    echo "<h2>{$title}</h2>";
    
    if ($subtitle) {
        echo "<p>{$subtitle}</p>";
    }
    
    if ($link) {
        echo "<div class='product-cta'>";
        echo "<span class='button'>{$button_text}</span>";
        echo "</div>"; // .product-cta
    }
    
    echo "</div>"; // .product-content
    
    if ($link) {
        echo "</a>"; // .product-link
    }
    
    echo "</div>"; // .content-wrapper
    echo "</div>"; // .product-band
    
    // Output struktureret data
    echo "<script type='application/ld+json'>" . json_encode($product_data) . "</script>";
}

/**
 * Renderer et HTML-bånd med fritekst-indhold
 * 
 * @param array $content Båndets indhold
 * @param int $band_id Båndets ID
 */
function render_html_band($content, $band_id) {
    $title = htmlspecialchars($content['title'] ?? '');
    $html = $content['html'] ?? '';
    $bg_color = htmlspecialchars($content['background_color'] ?? '');
    $text_color = htmlspecialchars($content['text_color'] ?? '');
    
    $style = '';
    if ($bg_color) {
        $style .= "background-color: {$bg_color};";
    }
    if ($text_color) {
        $style .= "color: {$text_color};";
    }
    
    echo "<div class='html-band' style='{$style}'>";
    echo "<div class='content-wrapper'>";
    
    if ($title) {
        echo "<h2 class='html-band-title'>{$title}</h2>";
    }
    
    // Filtrer og vis HTML-indhold
    echo "<div class='html-band-content'>";
    echo $html; // Vi stoler på admins til at skrive sikker HTML
    echo "</div>"; // .html-band-content
    
    echo "</div>"; // .content-wrapper
    echo "</div>"; // .html-band
}

/**
 * Renderer et link-bånd med CTA-knapper
 * 
 * @param array $content Båndets indhold
 * @param int $band_id Båndets ID
 */
function render_link_band($content, $band_id) {
    $title = htmlspecialchars($content['title'] ?? '');
    $subtitle = htmlspecialchars($content['subtitle'] ?? '');
    $bg_color = htmlspecialchars($content['background_color'] ?? '');
    $text_color = htmlspecialchars($content['text_color'] ?? '');
    $links = $content['links'] ?? [];
    $alignment = htmlspecialchars($content['alignment'] ?? 'center');
    
    $style = '';
    if ($bg_color) {
        $style .= "background-color: {$bg_color};";
    }
    if ($text_color) {
        $style .= "color: {$text_color};";
    }
    
    echo "<div class='link-band text-{$alignment}' style='{$style}'>";
    echo "<div class='content-wrapper'>";
    
    if ($title) {
        echo "<h2 class='link-band-title'>{$title}</h2>";
    }
    
    if ($subtitle) {
        echo "<p class='link-band-subtitle'>{$subtitle}</p>";
    }
    
    if (!empty($links)) {
        echo "<div class='link-band-buttons'>";
        
        foreach ($links as $link) {
            $url = htmlspecialchars($link['url'] ?? '#');
            $text = htmlspecialchars($link['text'] ?? 'Læs mere');
            $style = htmlspecialchars($link['style'] ?? 'primary');
            $target = isset($link['new_window']) && $link['new_window'] ? ' target="_blank" rel="noopener"' : '';
            
            echo "<a href='{$url}' class='button button-{$style}'{$target}>{$text}</a>";
        }
        
        echo "</div>"; // .link-band-buttons
    }
    
    echo "</div>"; // .content-wrapper
    echo "</div>"; // .link-band
}
