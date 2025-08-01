<?php
// api/bands.php - Band controller for LATL.dk

/**
 * Controller til håndtering af bånd (bands) på websitet
 */
class BandsController {
    private $db;
    private $imageHandler;
    
    public function __construct($db) {
        $this->db = $db;
        $this->imageHandler = new ImageHandler();
    }
    
    /**
     * Opret et nyt bånd til en side
     */
    public function createBand($pageId, $bandData) {
        try {
            // Valider input data
            if (!isset($bandData['band_type']) || !isset($bandData['band_height']) || !isset($bandData['band_order'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Band type, height and order are required'];
            }
            
            // Hent den eksisterende side
            $layoutController = new LayoutController($this->db);
            $result = $layoutController->getById($pageId);
            
            if ($result['status'] !== 'success') {
                return $result; // Returnerer fejl hvis siden ikke findes
            }
            
            $layout = $result['data'];
            $bands = isset($layout['bands']) ? $layout['bands'] : [];
            
            // Opret nyt bånd
            $newBand = [
                'band_id' => uniqid('band_'), // Generer unikt ID
                'band_type' => $bandData['band_type'],
                'band_height' => $bandData['band_height'],
                'band_order' => $bandData['band_order'],
                'band_content' => isset($bandData['band_content']) ? $bandData['band_content'] : []
            ];
            
            // Behandl billedupload hvis det findes i anmodningen
            if (isset($_FILES['band_image'])) {
                $imagePath = $this->imageHandler->handleBandImageUpload($_FILES['band_image'], $newBand['band_type']);
                if ($imagePath) {
                    $newBand['band_content']['image'] = $imagePath;
                }
            }
            
            // Håndter specifikke båndtyper
            switch ($newBand['band_type']) {
                case 'slideshow':
                    // Initialiser slides array hvis det ikke findes
                    if (!isset($newBand['band_content']['slides'])) {
                        $newBand['band_content']['slides'] = [];
                    }
                    break;
                    
                case 'product':
                    // Sæt standard baggrundsfarve hvis ikke angivet
                    if (!isset($newBand['band_content']['background_color'])) {
                        $newBand['band_content']['background_color'] = '#ffffff';
                    }
                    break;
            }
            
            // Tilføj det nye bånd til array'et
            $bands[] = $newBand;
            
            // Opdater layout
            $layout['bands'] = $bands;
            
            // Gem layout
            $updateResult = $layoutController->update($pageId, ['bands' => $bands]);
            
            if ($updateResult['status'] === 'success') {
                return ['status' => 'success', 'data' => $newBand];
            } else {
                return $updateResult;
            }
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opdater et eksisterende bånd
     */
    public function updateBand($pageId, $bandId, $bandData) {
        try {
            // Hent den eksisterende side
            $layoutController = new LayoutController($this->db);
            $result = $layoutController->getById($pageId);
            
            if ($result['status'] !== 'success') {
                return $result; // Returnerer fejl hvis siden ikke findes
            }
            
            $layout = $result['data'];
            
            if (!isset($layout['bands'])) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No bands found for this page'];
            }
            
            $bands = $layout['bands'];
            $bandIndex = null;
            
            // Find det bånd, der skal opdateres
            foreach ($bands as $index => $band) {
                if ($band['band_id'] === $bandId) {
                    $bandIndex = $index;
                    break;
                }
            }
            
            if ($bandIndex === null) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Opdater båndets egenskaber
            if (isset($bandData['band_type'])) {
                $bands[$bandIndex]['band_type'] = $bandData['band_type'];
            }
            
            if (isset($bandData['band_height'])) {
                $bands[$bandIndex]['band_height'] = $bandData['band_height'];
            }
            
            if (isset($bandData['band_order'])) {
                $bands[$bandIndex]['band_order'] = $bandData['band_order'];
            }
            
            if (isset($bandData['band_content'])) {
                // Bevar eksisterende indhold, der ikke er specificeret i den nye data
                if (!is_array($bands[$bandIndex]['band_content'])) {
                    $bands[$bandIndex]['band_content'] = [];
                }
                
                foreach ($bandData['band_content'] as $key => $value) {
                    $bands[$bandIndex]['band_content'][$key] = $value;
                }
            }
            
            // Behandl billedupload hvis det findes i anmodningen
            if (isset($_FILES['band_image'])) {
                $imagePath = $this->imageHandler->handleBandImageUpload($_FILES['band_image'], $bands[$bandIndex]['band_type']);
                if ($imagePath) {
                    $bands[$bandIndex]['band_content']['image'] = $imagePath;
                }
            }
            
            // For slideshow type, håndter slide uploads
            if ($bands[$bandIndex]['band_type'] === 'slideshow' && isset($_FILES['slides'])) {
                $this->handleSlideUploads($bands[$bandIndex], $_FILES['slides']);
            }
            
            // Opdater layout
            $layout['bands'] = $bands;
            
            // Gem layout
            $updateResult = $layoutController->update($pageId, ['bands' => $bands]);
            
            if ($updateResult['status'] === 'success') {
                return ['status' => 'success', 'data' => $bands[$bandIndex]];
            } else {
                return $updateResult;
            }
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Slet et bånd fra en side
     */
    public function deleteBand($pageId, $bandId) {
        try {
            // Hent den eksisterende side
            $layoutController = new LayoutController($this->db);
            $result = $layoutController->getById($pageId);
            
            if ($result['status'] !== 'success') {
                return $result; // Returnerer fejl hvis siden ikke findes
            }
            
            $layout = $result['data'];
            
            if (!isset($layout['bands'])) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No bands found for this page'];
            }
            
            $bands = $layout['bands'];
            $newBands = [];
            $found = false;
            
            // Filtrer det bånd, der skal slettes
            foreach ($bands as $band) {
                if ($band['band_id'] !== $bandId) {
                    $newBands[] = $band;
                } else {
                    $found = true;
                }
            }
            
            if (!$found) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Opdater layout
            $layout['bands'] = $newBands;
            
            // Gem layout
            $updateResult = $layoutController->update($pageId, ['bands' => $newBands]);
            
            if ($updateResult['status'] === 'success') {
                return ['status' => 'success', 'message' => 'Band deleted successfully'];
            } else {
                return $updateResult;
            }
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent alle bånd for en side
     */
    public function getBands($pageId) {
        try {
            // Hent den eksisterende side
            $layoutController = new LayoutController($this->db);
            $result = $layoutController->getById($pageId);
            
            if ($result['status'] !== 'success') {
                return $result; // Returnerer fejl hvis siden ikke findes
            }
            
            $layout = $result['data'];
            $bands = isset($layout['bands']) ? $layout['bands'] : [];
            
            // Sorter båndene efter rækkefølge
            usort($bands, function($a, $b) {
                return $a['band_order'] - $b['band_order'];
            });
            
            return ['status' => 'success', 'data' => $bands];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent et specifikt bånd
     */
    public function getBand($pageId, $bandId) {
        try {
            // Hent den eksisterende side
            $layoutController = new LayoutController($this->db);
            $result = $layoutController->getById($pageId);
            
            if ($result['status'] !== 'success') {
                return $result; // Returnerer fejl hvis siden ikke findes
            }
            
            $layout = $result['data'];
            
            if (!isset($layout['bands'])) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No bands found for this page'];
            }
            
            $bands = $layout['bands'];
            
            // Find det ønskede bånd
            foreach ($bands as $band) {
                if ($band['band_id'] === $bandId) {
                    return ['status' => 'success', 'data' => $band];
                }
            }
            
            http_response_code(404);
            return ['status' => 'error', 'message' => 'Band not found'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hjælpefunktion til at håndtere uploads af slides til slideshow
     */
    private function handleSlideUploads(&$band, $slideFiles) {
        // Initialiser slides array hvis det ikke findes
        if (!isset($band['band_content']['slides'])) {
            $band['band_content']['slides'] = [];
        }
        
        // Loop gennem slide uploads
        foreach ($slideFiles as $index => $file) {
            $imagePath = $this->imageHandler->handleSlideImageUpload($file);
            
            if ($imagePath) {
                // Find eksisterende slide hvis det findes
                $slideExists = false;
                
                foreach ($band['band_content']['slides'] as &$slide) {
                    if ($slide['position'] == $index) {
                        $slide['image'] = $imagePath;
                        $slideExists = true;
                        break;
                    }
                }
                
                // Hvis slide ikke findes, opret et nyt
                if (!$slideExists) {
                    $band['band_content']['slides'][] = [
                        'position' => $index,
                        'image' => $imagePath,
                        'title' => '',
                        'subtitle' => '',
                        'link' => ''
                    ];
                }
            }
        }
        
        // Sorter slides efter position
        usort($band['band_content']['slides'], function($a, $b) {
            return $a['position'] - $b['position'];
        });
    }
}

/**
 * Klasse til håndtering af billedupload og -behandling
 */
class ImageHandler {
    /**
     * Håndter upload af et billede til et bånd
     */
    public function handleBandImageUpload($file, $bandType) {
        // Opret upload mappe hvis den ikke eksisterer
        $uploadDir = IMAGES_DIR . '/bands/' . $bandType . '/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Valider filtype
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Generer et unikt filnavn
        $fileName = uniqid('band_') . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        // Upload filen
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Konverter til WebP og generer responsive versioner
            $webpPath = $this->convertToWebP($filePath);
            $this->generateResponsiveImages($webpPath);
            
            // Returner stien til den uploadede fil (relativ til BASE_URL)
            return str_replace(ROOT_PATH, BASE_URL, $webpPath);
        }
        
        return false;
    }
    
    /**
     * Håndter upload af et slide billede til et slideshow
     */
    public function handleSlideImageUpload($file) {
        // Opret upload mappe hvis den ikke eksisterer
        $uploadDir = IMAGES_DIR . '/bands/slideshow/slides/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Valider filtype
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Generer et unikt filnavn
        $fileName = uniqid('slide_') . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        // Upload filen
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Konverter til WebP og generer responsive versioner
            $webpPath = $this->convertToWebP($filePath);
            $this->generateResponsiveImages($webpPath);
            
            // Returner stien til den uploadede fil (relativ til BASE_URL)
            return str_replace(ROOT_PATH, BASE_URL, $webpPath);
        }
        
        return false;
    }
    
    /**
     * Konverter et billede til WebP format
     */
    private function convertToWebP($filePath) {
        $info = pathinfo($filePath);
        $webpPath = $info['dirname'] . '/' . $info['filename'] . '.webp';
        
        $image = null;
        
        // Indlæs billede baseret på filtype
        switch (strtolower($info['extension'])) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'webp':
                // Allerede i WebP format
                return $filePath;
            default:
                return $filePath;
        }
        
        // Konverter til WebP
        imagewebp($image, $webpPath, 90);
        imagedestroy($image);
        
        // Hvis konverteringen lykkedes, slet den originale fil
        if (file_exists($webpPath)) {
            unlink($filePath);
            return $webpPath;
        }
        
        return $filePath;
    }
    
    /**
     * Generer responsive versioner af et billede
     */
    private function generateResponsiveImages($filePath) {
        $info = pathinfo($filePath);
        $sizes = [
            'small' => 640,   // Mobil
            'medium' => 1024, // Tablet
            'large' => 1920   // Desktop
        ];
        
        $image = imagecreatefromwebp($filePath);
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
        
        foreach ($sizes as $size => $maxWidth) {
            // Spring over hvis billedet allerede er mindre end den ønskede størrelse
            if ($origWidth <= $maxWidth) {
                continue;
            }
            
            // Beregn ny højde for at bevare aspect ratio
            $newHeight = floor($origHeight * ($maxWidth / $origWidth));
            
            // Opret det skalerede billede
            $newImage = imagecreatetruecolor($maxWidth, $newHeight);
            
            // Bevar transparens hvis det er en PNG
            if ($info['extension'] === 'png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $maxWidth, $newHeight, $transparent);
            }
            
            // Skaler billedet
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, $origHeight);
            
            // Gem det skalerede billede
            $newFilePath = $info['dirname'] . '/' . $info['filename'] . '_' . $size . '.webp';
            imagewebp($newImage, $newFilePath, 90);
            imagedestroy($newImage);
        }
        
        imagedestroy($image);
    }
}

/**
 * Frontend Band Renderer - Håndterer rendering af forskellige båndtyper
 */
class BandRenderer {
    private $globalStyles;
    
    public function __construct() {
        // Indlæs globale stile
        $db = Database::getInstance();
        $globalStylesController = new GlobalStylesController($db);
        $result = $globalStylesController->getStyles();
        
        if ($result['status'] === 'success') {
            $this->globalStyles = $result['data'];
        } else {
            $this->globalStyles = [
                'color_palette' => [
                    'primary' => '#042940',
                    'secondary' => '#005C53',
                    'accent' => '#9FC131',
                    'bright' => '#DBF227',
                    'background' => '#D6D58E',
                    'text' => '#042940'
                ],
                'font_config' => [
                    'heading' => [
                        'font-family' => "'Allerta Stencil', sans-serif",
                        'font-weight' => '400'
                    ],
                    'body' => [
                        'font-family' => "'Open Sans', sans-serif",
                        'font-weight' => '400'
                    ]
                ]
            ];
        }
    }
    
    /**
     * Render et bånd baseret på type og indhold
     */
    public function renderBand($band) {
        if (!isset($band['band_type'])) {
            return '<!-- Invalid band: missing type -->';
        }
        
        $height = isset($band['band_height']) ? $band['band_height'] : '1';
        $content = isset($band['band_content']) ? $band['band_content'] : [];
        
        // Start band container
        $output = '<div class="band band-' . htmlspecialchars($band['band_type']) . ' band-height-' . $height . '" data-band-id="' . htmlspecialchars($band['band_id']) . '">';
        
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
     * Render et slideshow bånd
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
            $output .= '<div class="slide" data-slide-index="' . $index . '">';
            
            // Hvis der er et link, wrap slide i et anker tag
            if (!empty($slide['link'])) {
                $output .= '<a href="' . htmlspecialchars($slide['link']) . '" class="slide-link">';
            }
            
            // Generer responsive billede med srcset for forskellige skærmstørrelser
            $imagePath = $slide['image'];
            $imageInfo = pathinfo($imagePath);
            $smallImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_small.webp';
            $mediumImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_medium.webp';
            $largeImage = $imageInfo['dirname'] . '/' . $imageInfo['filename'] . '_large.webp';
            
            // Generer alt-tekst for SEO
            $altText = !empty($slide['title']) ? $slide['title'] : 'Slideshow billede ' . ($index + 1);
            
            // Opbyg srcset for responsive billeder
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
                $output .= '<button class="slideshow-dot" data-slide="' . $index . '" aria-label="Gå til slide ' . ($index + 1) . '"></button>';
            }
            $output .= '</div>'; // .slideshow-dots
            
            $output .= '</div>'; // .slideshow-controls
        }
        
        $output .= '</div>'; // .slideshow-container
        
        // Tilføj JavaScript for slideshow funktionalitet
        $output .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                initSlideshow("' . $content['band_id'] . '");
            });
        </script>';
        
        return $output;
    }
    
    /**
     * Render et konfigurerbart produkt bånd
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
        
        // Start container med baggrund
        $output = '<div class="product-container" style="background-color: ' . htmlspecialchars($bgColor) . ';">';
        
        // Hvis der er et link, wrap indholdet i et anker tag
        if (!empty($link)) {
            $output .= '<a href="' . htmlspecialchars($link) . '" class="product-link">';
        }
        
        // Produktbillede (PNG med gennemsigtig baggrund)
        $output .= '<div class="product-image-container">';
        $output .= '<img src="' . htmlspecialchars($image) . '" 
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
        
        return $output;
    }
    
    /**
     * Generer JavaScript til slideshow funktionalitet
     */
    public function getSlideshowJS() {
        return '<script>
            function initSlideshow(bandId) {
                const container = document.querySelector(`.band[data-band-id="${bandId}"] .slideshow-container`);
                if (!container) return;
                
                const slides = container.querySelectorAll(".slide");
                const dots = container.querySelectorAll(".slideshow-dot");
                const prevBtn = container.querySelector(".prev-slide");
                const nextBtn = container.querySelector(".next-slide");
                
                let currentSlide = 0;
                let interval;
                
                // Funktion til at vise et bestemt slide
                function showSlide(n) {
                    // Nulstil interval ved manuel navigation
                    if (interval) {
                        clearInterval(interval);
                    }
                    
                    // Wrap-around hvis n er uden for grænserne
                    if (n >= slides.length) {
                        currentSlide = 0;
                    } else if (n < 0) {
                        currentSlide = slides.length - 1;
                    } else {
                        currentSlide = n;
                    }
                    
                    // Skjul alle slides
                    slides.forEach(slide => {
                        slide.style.display = "none";
                    });
                    
                    // Fjern aktiv klasse fra alle prikker
                    dots.forEach(dot => {
                        dot.classList.remove("active");
                    });
                    
                    // Vis det aktuelle slide
                    slides[currentSlide].style.display = "block";
                    
                    // Marker den aktuelle prik som aktiv
                    if (dots.length > 0) {
                        dots[currentSlide].classList.add("active");
                    }
                    
                    // Genstart interval
                    startAutoSlide();
                }
                
                // Funktion til automatisk slideshow
                function startAutoSlide() {
                    if (interval) {
                        clearInterval(interval);
                    }
                    
                    // Skift slide hvert 5. sekund
                    interval = setInterval(() => {
                        showSlide(currentSlide + 1);
                    }, 5000);
                }
                
                // Tilføj event listeners til knapper
                if (prevBtn) {
                    prevBtn.addEventListener("click", () => {
                        showSlide(currentSlide - 1);
                    });
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener("click", () => {
                        showSlide(currentSlide + 1);
                    });
                }
                
                // Tilføj event listeners til prikker
                dots.forEach((dot, index) => {
                    dot.addEventListener("click", () => {
                        showSlide(index);
                    });
                });
                
                // Vis første slide og start automatisk skift
                showSlide(0);
            }
        </script>';
    }
}
