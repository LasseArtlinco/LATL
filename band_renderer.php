<?php
/**
 * LATL Bånd Rendering
 * 
 * Denne fil indeholder funktioner til at rendere bånd på frontend.
 * Gem denne fil som 'band_renderer.php' i dit projektrodmappe.
 */

/**
 * Klasse til at rendere bånd på frontend
 */
class BandRenderer {
    private $globalStyles;
    
    /**
     * Konstruktør - indlæser globale stile
     */
    public function __construct() {
        // Indlæs globale stile fra databasen
        global $db;
        $layout = $db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", ['global']);
        
        if ($layout && isset($layout['layout_data'])) {
            $this->globalStyles = json_decode($layout['layout_data'], true);
        } else {
            // Standard stile hvis ingen findes
            $this->globalStyles = [
                'color_palette' => [
                    'primary' => '#042940',
                    'secondary' => '#005C53',
                    'accent' => '#9FC131',
                    'bright' => '#DBF227',
                    'background' => '#D6D58E',
                    'text' => '#042940'
                ]
            ];
        }
    }
    
    /**
     * Render alle bånd for en side
     * 
     * @param string $pageId Sidens ID
     * @return string HTML for alle bånd
     */
    public function renderPageBands($pageId) {
        global $db;
        
        $output = '';
        
        // Hent layoutet for siden
        $layout = $db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", [$pageId]);
        
        if (!$layout || !isset($layout['layout_data'])) {
            return $output;
        }
        
        // Parse layout data
        $layoutData = json_decode($layout['layout_data'], true);
        
        // Hent bånd fra layoutet
        $bands = isset($layoutData['bands']) ? $layoutData['bands'] : [];
        
        // Sorter bånd efter rækkefølge
        usort($bands, function($a, $b) {
            return $a['band_order'] - $b['band_order'];
        });
        
        // Render hvert bånd
        foreach ($bands as $band) {
            $output .= $this->renderBand($band);
        }
        
        return $output;
    }
    
    /**
     * Render et enkelt bånd
     * 
     * @param array $band Bånd data
     * @return string HTML for båndet
     */
    public function renderBand($band) {
        if (!isset($band['band_type'])) {
            return '<!-- Ugyldigt bånd: mangler type -->';
        }
        
        $height = isset($band['band_height']) ? $band['band_height'] : '1';
        $content = isset($band['band_content']) ? $band['band_content'] : [];
        
        // Start band container
        $output = '<div class="band band-' . htmlspecialchars($band['band_type']) . ' band-height-' . $height . '" data-band-id="' . htmlspecialchars($band['band_id']) . '">';
        
        // Tilføj SEO metadata hvis det findes
        if (isset($content['seo'])) {
            $output .= $this->renderSeoMetadata($content['seo']);
        }
        
        // Render bånd indhold baseret på type
        switch ($band['band_type']) {
            case 'slideshow':
                $output .= $this->renderSlideshow($content);
                break;
                
            case 'product':
                $output .= $this->renderConfigurableProduct($content);
                break;
                
            default:
                $output .= '<!-- Ukendt båndtype: ' . htmlspecialchars($band['band_type']) . ' -->';
                break;
        }
        
        // Afslut band container
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render slideshow bånd
     * 
     * @param array $content Bånd indhold
     * @return string HTML for slideshow
     */
    private function renderSlideshow($content) {
        if (!isset($content['slides']) || empty($content['slides'])) {
            return '<div class="slideshow-container empty">Ingen slides fundet</div>';
        }
        
        $slides = $content['slides'];
        
        // Slideshow container
        $output = '<div class="slideshow-container">';
        
        // Render hver slide
        foreach ($slides as $index => $slide) {
            $output .= '<div class="slide' . ($index === 0 ? ' active' : '') . '">';
            
            // Hvis der er et link, wrap slide i et anker tag
            if (!empty($slide['link'])) {
                $output .= '<a href="' . htmlspecialchars($slide['link']) . '" class="slide-link">';
            }
            
            // Billede med responsive srcset og lazy loading
            $imagePath = $slide['image'];
            $imageInfo = pathinfo($imagePath);
            $smallImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_small.webp';
            $mediumImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_medium.webp';
            $largeImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_large.webp';
            
            // Alt-tekst for SEO
            $altText = !empty($slide['alt']) ? $slide['alt'] : (!empty($slide['title']) ? $slide['title'] : 'Slideshow billede ' . ($index + 1));
            
            // Brug data-src og data-srcset for lazy loading
            $output .= '<img src="' . htmlspecialchars($imagePath) . '" 
                          srcset="' . htmlspecialchars($smallImage) . ' 640w, 
                                 ' . htmlspecialchars($mediumImage) . ' 1024w, 
                                 ' . htmlspecialchars($largeImage) . ' 1920w"
                          sizes="100vw"
                          alt="' . htmlspecialchars($altText) . '" 
                          class="slide-image"
                          loading="lazy">';
            
            // Slide indhold (titel og undertitel)
            $output .= '<div class="slide-content">';
            
            if (!empty($slide['title'])) {
                $output .= '<h2 class="slide-title">' . htmlspecialchars($slide['title']) . '</h2>';
            }
            
            if (!empty($slide['subtitle'])) {
                $output .= '<p class="slide-subtitle">' . htmlspecialchars($slide['subtitle']) . '</p>';
            }
            
            $output .= '</div>'; // .slide-content
            
            if (!empty($slide['link'])) {
                $output .= '</a>';
            }
            
            $output .= '</div>'; // .slide
        }
        
        // Tilføj navigationsknapper hvis der er flere slides
        if (count($slides) > 1) {
            $output .= '<div class="slideshow-controls">';
            $output .= '<button class="prev-slide" aria-label="Forrige slide">&#10094;</button>';
            $output .= '<button class="next-slide" aria-label="Næste slide">&#10095;</button>';
            
            // Tilføj prikker for hvert slide
            $output .= '<div class="slideshow-dots">';
            foreach ($slides as $index => $slide) {
                $output .= '<button class="slideshow-dot' . ($index === 0 ? ' active' : '') . '" data-slide="' . $index . '" aria-label="Gå til slide ' . ($index + 1) . '"></button>';
            }
            $output .= '</div>'; // .slideshow-dots
            
            $output .= '</div>'; // .slideshow-controls
        }
        
        $output .= '</div>'; // .slideshow-container
        
        // Tilføj strukturerede data for slideshow
        $output .= $this->generateSlideshowStructuredData($slides);
        
        return $output;
    }
    
    /**
     * Render konfigurerbart produkt bånd
     * 
     * @param array $content Bånd indhold
     * @return string HTML for produkt
     */
    private function renderConfigurableProduct($content) {
        // Valider at påkrævede felter findes
        if (!isset($content['image']) || !isset($content['title'])) {
            return '<div class="product-container empty">Ufuldstændigt produkt bånd</div>';
        }
        
        // Få indhold
        $image = $content['image'];
        $title = $content['title'];
        $subtitle = isset($content['subtitle']) ? $content['subtitle'] : '';
        $link = isset($content['link']) ? $content['link'] : '';
        $bgColor = isset($content['background_color']) ? $content['background_color'] : '#ffffff';
        
        // Billede information for responsive srcset
        $imageInfo = pathinfo($image);
        $smallImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_small.webp';
        $mediumImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_medium.webp';
        $largeImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_large.webp';
        
        // Start container med baggrund
        $output = '<div class="product-container" style="background-color: ' . htmlspecialchars($bgColor) . ';">';
        
        // Hvis der er et link, wrap indholdet i et anker tag
        if (!empty($link)) {
            $output .= '<a href="' . htmlspecialchars($link) . '" class="product-link">';
        }
        
        // Produktbillede (PNG med gennemsigtig baggrund)
        $output .= '<div class="product-image-container">';
        $output .= '<img src="' . htmlspecialchars($image) . '" 
                      srcset="' . htmlspecialchars($smallImage) . ' 640w, 
                             ' . htmlspecialchars($mediumImage) . ' 1024w, 
                             ' . htmlspecialchars($largeImage) . ' 1920w"
                      sizes="(max-width: 768px) 100vw, 50vw"
                      alt="' . htmlspecialchars($title) . '" 
                      class="product-image"
                      loading="lazy">';
        $output .= '</div>';
        
        // Produktinformation
        $output .= '<div class="product-info">';
        $output .= '<h2 class="product-title">' . htmlspecialchars($title) . '</h2>';
        
        if (!empty($subtitle)) {
            $output .= '<p class="product-subtitle">' . htmlspecialchars($subtitle) . '</p>';
        }
        
        $output .= '</div>'; // .product-info
        
        if (!empty($link)) {
            $output .= '</a>';
        }
        
        $output .= '</div>'; // .product-container
        
        // Tilføj strukturerede data for produkt
        $output .= $this->generateProductStructuredData($content);
        
        return $output;
    }
    
    /**
     * Generer strukturerede data for slideshow (JSON-LD)
     * 
     * @param array $slides Slides data
     * @return string JSON-LD script tag
     */
    private function generateSlideshowStructuredData($slides) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => []
        ];
        
        foreach ($slides as $index => $slide) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1
            ];
            
            if (isset($slide['link'])) {
                // Hvis linket er relativt, konverter til absolut URL
                $url = $slide['link'];
                if (strpos($url, 'http') !== 0) {
                    $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
                }
                $item['url'] = $url;
            }
            
            if (isset($slide['title'])) {
                $item['name'] = $slide['title'];
            }
            
            if (isset($slide['image'])) {
                // Hvis billedet er relativt, konverter til absolut URL
                $imageUrl = $slide['image'];
                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = rtrim(BASE_URL, '/') . '/' . ltrim($imageUrl, '/');
                }
                $item['image'] = $imageUrl;
            }
            
            $data['itemListElement'][] = $item;
        }
        
        return '<script type="application/ld+json">' . json_encode($data) . '</script>';
    }
    
    /**
     * Generer strukturerede data for produkt (JSON-LD)
     * 
     * @param array $content Produkt data
     * @return string JSON-LD script tag
     */
    private function generateProductStructuredData($content) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $content['title']
        ];
        
        if (isset($content['subtitle'])) {
            $data['description'] = $content['subtitle'];
        }
        
        if (isset($content['image'])) {
            // Hvis billedet er relativt, konverter til absolut URL
            $imageUrl = $content['image'];
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = rtrim(BASE_URL, '/') . '/' . ltrim($imageUrl, '/');
            }
            $data['image'] = $imageUrl;
        }
        
        return '<script type="application/ld+json">' . json_encode($data) . '</script>';
    }
    
    /**
     * Render SEO metadata
     * 
     * @param array $seo SEO data
     * @return string HTML kommentar med SEO info
     */
    private function renderSeoMetadata($seo) {
        $output = '<!-- SEO Metadata:';
        
        if (isset($seo['title'])) {
            $output .= ' title="' . htmlspecialchars($seo['title']) . '"';
        }
        
        if (isset($seo['description'])) {
            $output .= ' description="' . htmlspecialchars($seo['description']) . '"';
        }
        
        if (isset($seo['keywords'])) {
            $output .= ' keywords="' . htmlspecialchars($seo['keywords']) . '"';
        }
        
        $output .= ' -->';
        
        return $output;
    }
}

/**
 * Funktion til at rendere bånd for en side
 * 
 * @param string $pageId ID på den side, der skal vises bånd for
 * @return string HTML for alle bånd på siden
 */
function renderPageBands($pageId) {
    $bandRenderer = new BandRenderer();
    return $bandRenderer->renderPageBands($pageId);
}
