<?php
require_once __DIR__ . '/config.php';

function render_band($band) {
    $type = $band['band_type'];
    $content = $band['band_content'];
    $height = $band['band_height'];
    
    echo "<div class='band band-{$type} band-height-{$height}'>";
    echo "<div class='container'>";
    
    switch ($type) {
        case 'slideshow':
            render_slideshow_band($content);
            break;
        case 'product':
            render_product_band($content);
            break;
        default:
            echo "<p>Ukendt båndtype: {$type}</p>";
    }
    
    echo "</div>"; // .container
    echo "</div>"; // .band
}

function render_slideshow_band($content) {
    $slides = $content['slides'] ?? [];
    $autoplay = $content['autoplay'] ?? false;
    $interval = $content['interval'] ?? 5000;
    
    echo "<div class='slideshow' data-autoplay='{$autoplay}' data-interval='{$interval}'>";
    echo "<div class='slides'>";
    
    foreach ($slides as $index => $slide) {
        $active = $index === 0 ? 'active' : '';
        $image = htmlspecialchars($slide['image']);
        $title = htmlspecialchars($slide['title']);
        $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
        $link = htmlspecialchars($slide['link'] ?? '');
        
        echo "<div class='slide {$active}'>";
        
        if ($link) {
            echo "<a href='{$link}'>";
        }
        
        echo "<img src='/uploads/{$image}' alt='{$title}'>";
        
        echo "<div class='slide-content'>";
        echo "<h2>{$title}</h2>";
        
        if ($subtitle) {
            echo "<p>{$subtitle}</p>";
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
        echo "<button class='prev'>Forrige</button>";
        echo "<div class='indicators'>";
        
        foreach ($slides as $index => $slide) {
            $active = $index === 0 ? 'active' : '';
            echo "<button class='indicator {$active}' data-slide='{$index}'></button>";
        }
        
        echo "</div>"; // .indicators
        echo "<button class='next'>Næste</button>";
        echo "</div>"; // .slideshow-nav
    }
    
    echo "</div>"; // .slideshow
}

function render_product_band($content) {
    $image = htmlspecialchars($content['image'] ?? '');
    $bgColor = htmlspecialchars($content['background_color'] ?? '#ffffff');
    $title = htmlspecialchars($content['title'] ?? '');
    $subtitle = htmlspecialchars($content['subtitle'] ?? '');
    $link = htmlspecialchars($content['link'] ?? '');
    
    echo "<div class='product-band' style='background-color: {$bgColor};'>";
    
    if ($link) {
        echo "<a href='{$link}' class='product-link'>";
    }
    
    echo "<div class='product-image'>";
    echo "<img src='/uploads/{$image}' alt='{$title}'>";
    echo "</div>"; // .product-image
    
    echo "<div class='product-content'>";
    echo "<h2>{$title}</h2>";
    
    if ($subtitle) {
        echo "<p>{$subtitle}</p>";
    }
    
    if ($link) {
        echo "<div class='product-cta'>";
        echo "<span class='button'>Se mere</span>";
        echo "</div>"; // .product-cta
    }
    
    echo "</div>"; // .product-content
    
    if ($link) {
        echo "</a>"; // .product-link
    }
    
    echo "</div>"; // .product-band
}
