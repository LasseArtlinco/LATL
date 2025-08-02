<?php
// includes/image_handler.php - Håndtering af billeder og optimering

require_once __DIR__ . '/config.php';

/**
 * Klasse til håndtering af billeder - upload, optimering og konvertering
 */
class ImageHandler {
    private $targetDir;
    private $allowedTypes;
    private $maxSize;
    private $imageSizes;
    
    /**
     * Konstruktør - Sætter standardindstillinger
     * 
     * @param string $targetDir Mappe til uploadede billeder
     * @param array $allowedTypes Tilladte MIME-typer
     * @param int $maxSize Maksimal filstørrelse i bytes (default: 10MB)
     */
    public function __construct($targetDir = null, $allowedTypes = null, $maxSize = null) {
        $this->targetDir = $targetDir ?: UPLOADS_DIR;
        $this->allowedTypes = $allowedTypes ?: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $this->maxSize = $maxSize ?: 10 * 1024 * 1024; // 10MB default
        
        // Standard responsive billedstørrelser
        $this->imageSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'small' => ['width' => 300, 'height' => null, 'crop' => false],
            'medium' => ['width' => 600, 'height' => null, 'crop' => false],
            'large' => ['width' => 1200, 'height' => null, 'crop' => false],
            'hero' => ['width' => 1920, 'height' => null, 'crop' => false]
        ];
        
        // Opret målmappe, hvis den ikke findes
        $this->ensureDirectoryExists($this->targetDir);
    }
    
    /**
     * Upload af billede med validering
     * 
     * @param array $file $_FILES array-element
     * @param string $subdir Undermappe til upload (valgfri)
     * @return array Information om det uploadede billede
     * @throws Exception Ved fejl i upload-processen
     */
    public function uploadImage($file, $subdir = '') {
        // Validér filen
        $this->validateFile($file);
        
        // Opret undermappe hvis angivet
        $targetDir = $this->targetDir;
        if ($subdir) {
            $targetDir .= '/' . trim($subdir, '/');
            $this->ensureDirectoryExists($targetDir);
        }
        
        // Generer filnavn og -sti
        $filename = $this->generateFilename($file);
        $targetPath = $targetDir . '/' . $filename;
        
        // Upload filen
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Kunne ikke uploade filen. Kontroller rettigheder på upload-mappen.');
        }
        
        // Optimer og konvertér billedet
        $optimizedImages = $this->optimizeImage($targetPath);
        
        // Tilføj metadata til billedet (for SEO)
        $metadata = $this->extractMetadata($targetPath);
        
        return [
            'original' => [
                'filename' => $filename,
                'path' => str_replace(ROOT_PATH, '', $targetPath),
                'full_path' => $targetPath,
                'size' => filesize($targetPath),
                'mime' => mime_content_type($targetPath)
            ],
            'optimized' => $optimizedImages,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Validering af uploadet fil
     * 
     * @param array $file $_FILES array-element
     * @throws Exception Ved fejl i valideringen
     */
    private function validateFile($file) {
        // Check om fil blev uploadet korrekt
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Ugyldig filparameter.');
        }
        
        // Check upload-status
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Filen er for stor. Maksimal størrelse er ' . $this->formatSize($this->maxSize) . '.');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('Filen blev kun delvist uploadet.');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Ingen fil blev uploadet.');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Manglende midlertidig mappe på serveren.');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Kunne ikke skrive filen til disk.');
            case UPLOAD_ERR_EXTENSION:
                throw new Exception('En PHP-udvidelse stoppede filuploaden.');
            default:
                throw new Exception('Ukendt upload-fejl.');
        }
        
        // Check filstørrelse
        if ($file['size'] > $this->maxSize) {
            throw new Exception('Filen er for stor. Maksimal størrelse er ' . $this->formatSize($this->maxSize) . '.');
        }
        
        // Validér filtypen
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $this->allowedTypes)) {
            throw new Exception('Ugyldig filtype. Tilladt: JPG, PNG, GIF, WebP.');
        }
    }
    
    /**
     * Generer unikt filnavn baseret på original filnavn
     * 
     * @param array $file $_FILES array-element
     * @return string Genereret filnavn
     */
    private function generateFilename($file) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $basename = pathinfo($file['name'], PATHINFO_FILENAME);
        
        // Sanitér filnavnet
        $basename = preg_replace('/[^a-z0-9æøå]/i', '-', strtolower($basename));
        $basename = preg_replace('/-+/', '-', $basename);
        $basename = trim($basename, '-');
        
        // Tilføj timestamp for at undgå duplikerede navne
        $basename .= '-' . time();
        
        return $basename . '.' . strtolower($extension);
    }
    
    /**
     * Optimér billede og konvertér til WebP
     * 
     * @param string $sourcePath Sti til kildefilen
     * @return array Information om optimerede billeder
     */
    public function optimizeImage($sourcePath) {
        $results = [];
        $sourceInfo = pathinfo($sourcePath);
        $sourceExt = strtolower($sourceInfo['extension']);
        
        // Load billede baseret på filtype
        $sourceImage = $this->loadImage($sourcePath, $sourceExt);
        if (!$sourceImage) {
            return $results;
        }
        
        // Få originale dimensioner
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        // Generer forskellige størrelser
        foreach ($this->imageSizes as $size => $dimensions) {
            $width = $dimensions['width'];
            $height = $dimensions['height'];
            $crop = $dimensions['crop'];
            
            // Skip hvis billedet er mindre end målstørrelsen
            if ($originalWidth <= $width && (!$height || $originalHeight <= $height)) {
                continue;
            }
            
            // Beregn nye dimensioner
            list($newWidth, $newHeight) = $this->calculateDimensions(
                $originalWidth, 
                $originalHeight, 
                $width, 
                $height, 
                $crop
            );
            
            // Opret nyt billede
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Bevar gennemsigtighed hvis PNG eller WebP
            if ($sourceExt == 'png' || $sourceExt == 'webp') {
                imagecolortransparent($resizedImage, imagecolorallocate($resizedImage, 0, 0, 0));
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
            }
            
            // Resample billedet
            imagecopyresampled(
                $resizedImage, 
                $sourceImage, 
                0, 0, 0, 0, 
                $newWidth, 
                $newHeight, 
                $originalWidth, 
                $originalHeight
            );
            
            // Generer output-filstier
            $sizeDir = $sourceInfo['dirname'] . '/' . $size;
            $this->ensureDirectoryExists($sizeDir);
            
            $originalOutput = $sizeDir . '/' . $sourceInfo['filename'] . '.' . $sourceExt;
            $webpOutput = $sizeDir . '/' . $sourceInfo['filename'] . '.webp';
            
            // Gem i original format
            $this->saveImage($resizedImage, $originalOutput, $sourceExt);
            
            // Gem som WebP
            imagewebp($resizedImage, $webpOutput, 80);
            
            // Tilføj til resultater
            $results[$size] = [
                'original' => [
                    'path' => str_replace(ROOT_PATH, '', $originalOutput),
                    'full_path' => $originalOutput,
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'size' => filesize($originalOutput),
                    'mime' => mime_content_type($originalOutput)
                ],
                'webp' => [
                    'path' => str_replace(ROOT_PATH, '', $webpOutput),
                    'full_path' => $webpOutput,
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'size' => filesize($webpOutput),
                    'mime' => 'image/webp'
                ]
            ];
            
            // Frigør hukommelse
            imagedestroy($resizedImage);
        }
        
        // Konvertér originalen til WebP
        $webpOutput = $sourceInfo['dirname'] . '/' . $sourceInfo['filename'] . '.webp';
        imagewebp($sourceImage, $webpOutput, 90);
        
        $results['original_webp'] = [
            'path' => str_replace(ROOT_PATH, '', $webpOutput),
            'full_path' => $webpOutput,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'size' => filesize($webpOutput),
            'mime' => 'image/webp'
        ];
        
        // Frigør hukommelse
        imagedestroy($sourceImage);
        
        return $results;
    }
    
    /**
     * Indlæs billede baseret på filtype
     * 
     * @param string $path Sti til billedfil
     * @param string $extension Filendelse
     * @return resource|false GD billede ressource eller false ved fejl
     */
    private function loadImage($path, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($path);
            case 'png':
                return imagecreatefrompng($path);
            case 'gif':
                return imagecreatefromgif($path);
            case 'webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Gem billede i det angivne format
     * 
     * @param resource $image GD billede ressource
     * @param string $path Sti til output-fil
     * @param string $extension Filendelse
     * @return bool True ved succes, false ved fejl
     */
    private function saveImage($image, $path, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, 85);
            case 'png':
                return imagepng($image, $path, 8);
            case 'gif':
                return imagegif($image, $path);
            case 'webp':
                return imagewebp($image, $path, 80);
            default:
                return false;
        }
    }
    
    /**
     * Beregn nye dimensioner for et billede
     * 
     * @param int $origWidth Original bredde
     * @param int $origHeight Original højde
     * @param int $targetWidth Målbredde
     * @param int $targetHeight Målhøjde (kan være null)
     * @param bool $crop Om billedet skal beskæres
     * @return array [bredde, højde]
     */
    private function calculateDimensions($origWidth, $origHeight, $targetWidth, $targetHeight, $crop = false) {
        if ($crop) {
            // Hvis både bredde og højde er angivet, og vi skal beskære
            $targetHeight = $targetHeight ?: $targetWidth;
            
            $ratio = max($targetWidth / $origWidth, $targetHeight / $origHeight);
            $newWidth = $targetWidth;
            $newHeight = $targetHeight;
        } else {
            // Bevar aspektforhold
            if (!$targetHeight) {
                $ratio = $targetWidth / $origWidth;
                $newWidth = $targetWidth;
                $newHeight = $origHeight * $ratio;
            } elseif (!$targetWidth) {
                $ratio = $targetHeight / $origHeight;
                $newHeight = $targetHeight;
                $newWidth = $origWidth * $ratio;
            } else {
                // Hvis både bredde og højde er angivet, tilpas til den mindste
                $ratioWidth = $targetWidth / $origWidth;
                $ratioHeight = $targetHeight / $origHeight;
                $ratio = min($ratioWidth, $ratioHeight);
                
                $newWidth = $origWidth * $ratio;
                $newHeight = $origHeight * $ratio;
            }
        }
        
        return [round($newWidth), round($newHeight)];
    }
    
    /**
     * Udtræk metadata fra billedet for SEO
     * 
     * @param string $path Sti til billedfil
     * @return array Metadata
     */
    private function extractMetadata($path) {
        $metadata = [];
        
        // Basale filoplysninger
        $metadata['filename'] = basename($path);
        $metadata['mime'] = mime_content_type($path);
        $metadata['size'] = filesize($path);
        
        // Få billedstørrelse
        list($width, $height) = getimagesize($path);
        $metadata['width'] = $width;
        $metadata['height'] = $height;
        
        // Udtræk EXIF-data hvis det er en JPEG
        if ($metadata['mime'] == 'image/jpeg' && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($path);
                if ($exif) {
                    // Filtrer og rens EXIF-data
                    $relevantExif = [];
                    
                    if (!empty($exif['DateTimeOriginal'])) {
                        $relevantExif['captured'] = $exif['DateTimeOriginal'];
                    }
                    
                    if (!empty($exif['Model'])) {
                        $relevantExif['camera'] = $exif['Make'] . ' ' . $exif['Model'];
                    }
                    
                    if (!empty($exif['ImageDescription'])) {
                        $relevantExif['description'] = $exif['ImageDescription'];
                    }
                    
                    if (!empty($exif['Copyright'])) {
                        $relevantExif['copyright'] = $exif['Copyright'];
                    }
                    
                    $metadata['exif'] = $relevantExif;
                }
            } catch (Exception $e) {
                // Ignorer fejl ved læsning af EXIF
            }
        }
        
        return $metadata;
    }
    
    /**
     * Sikr at en mappe eksisterer og kan skrives til
     * 
     * @param string $directory Mappesti
     * @throws Exception Hvis mappen ikke kan oprettes
     */
    private function ensureDirectoryExists($directory) {
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Kunne ikke oprette mappen: $directory");
            }
        }
        
        if (!is_writable($directory)) {
            throw new Exception("Mappen er ikke skrivbar: $directory");
        }
    }
    
    /**
     * Formater filstørrelse til læsbar tekst
     * 
     * @param int $bytes Størrelse i bytes
     * @return string Formateret størrelse
     */
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Generer HTML for et responsivt billede med srcset
     * 
     * @param array $imageData Billeddata fra uploadImage() eller optimizeImage()
     * @param string $alt Alt-tekst til billedet
     * @param string $class CSS-klasse(r)
     * @param array $attributes Ekstra HTML-attributter
     * @return string HTML for responsivt billede
     */
    public function generateResponsiveImage($imageData, $alt = '', $class = '', $attributes = []) {
        if (empty($imageData) || empty($imageData['optimized'])) {
            return '';
        }
        
        $sizes = [];
        $srcset = [];
        $webpSrcset = [];
        
        // Generer srcset for forskellige størrelser
        foreach ($imageData['optimized'] as $size => $data) {
            if ($size === 'original_webp') continue;
            
            $srcset[] = BASE_URL . $data['original']['path'] . ' ' . $data['original']['width'] . 'w';
            $webpSrcset[] = BASE_URL . $data['webp']['path'] . ' ' . $data['webp']['width'] . 'w';
            
            // Tilføj til sizes attribute baseret på skærmbredde
            switch ($size) {
                case 'thumbnail':
                    $sizes[] = '(max-width: 320px) 150px';
                    break;
                case 'small':
                    $sizes[] = '(max-width: 640px) 300px';
                    break;
                case 'medium':
                    $sizes[] = '(max-width: 1024px) 600px';
                    break;
                case 'large':
                    $sizes[] = '(min-width: 1025px) 1200px';
                    break;
            }
        }
        
        // Fald tilbage til original hvis ingen optimerede versioner
        if (empty($srcset)) {
            $src = BASE_URL . $imageData['original']['path'];
            $width = $imageData['original']['width'];
            $height = $imageData['original']['height'];
        } else {
            $src = BASE_URL . $imageData['original']['path'];
            $width = $imageData['metadata']['width'] ?? '';
            $height = $imageData['metadata']['height'] ?? '';
        }
        
        // Tilføj ekstra attributter
        $attrStr = '';
        foreach ($attributes as $key => $value) {
            $attrStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        // Byg HTML-output
        $html = '<picture>';
        
        // WebP-kilder
        if (!empty($webpSrcset)) {
            $html .= '<source type="image/webp" srcset="' . implode(', ', $webpSrcset) . '"';
            if (!empty($sizes)) {
                $html .= ' sizes="' . implode(', ', $sizes) . '"';
            }
            $html .= '>';
        } elseif (isset($imageData['optimized']['original_webp'])) {
            $html .= '<source type="image/webp" srcset="' . BASE_URL . $imageData['optimized']['original_webp']['path'] . '">';
        }
        
        // Original format kilder
        if (!empty($srcset)) {
            $html .= '<source srcset="' . implode(', ', $srcset) . '"';
            if (!empty($sizes)) {
                $html .= ' sizes="' . implode(', ', $sizes) . '"';
            }
            $html .= '>';
        }
        
        // Fallback img-tag
        $html .= '<img src="' . $src . '" alt="' . htmlspecialchars($alt) . '"';
        
        if ($class) {
            $html .= ' class="' . htmlspecialchars($class) . '"';
        }
        
        if ($width && $height) {
            $html .= ' width="' . $width . '" height="' . $height . '"';
        }
        
        $html .= $attrStr . '>';
        $html .= '</picture>';
        
        return $html;
    }
}

/**
 * Hjælpefunktion til at uploade et billede
 * 
 * @param array $file $_FILES array-element
 * @param string $subdir Undermappe (valgfri)
 * @return array Billeddata
 */
function upload_image($file, $subdir = '') {
    $handler = new ImageHandler();
    return $handler->uploadImage($file, $subdir);
}

/**
 * Hjælpefunktion til at generere HTML for et responsivt billede
 * 
 * @param array $imageData Billeddata
 * @param string $alt Alt-tekst
 * @param string $class CSS-klasse(r)
 * @param array $attributes Ekstra attributter
 * @return string HTML
 */
function responsive_image($imageData, $alt = '', $class = '', $attributes = []) {
    $handler = new ImageHandler();
    return $handler->generateResponsiveImage($imageData, $alt, $class, $attributes);
}

/**
 * Konverterer et eksisterende billede til WebP
 * 
 * @param string $path Sti til billedet
 * @return array Optimerede billeddata
 */
function optimize_image($path) {
    $handler = new ImageHandler();
    return $handler->optimizeImage($path);
}
