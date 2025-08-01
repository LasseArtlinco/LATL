<?php
// api/image_handler.php - Håndtering af billedupload og -behandling

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
        // Kontroller at GD-biblioteket er tilgængeligt
        if (!extension_loaded('gd')) {
            error_log('GD extension is not available');
            return $filePath;
        }
        
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
                // Bevar alfa-kanal for PNG
                imagesavealpha($image, true);
                break;
            case 'webp':
                // Allerede i WebP format
                return $filePath;
            default:
                return $filePath;
        }
        
        if (!$image) {
            error_log('Failed to create image from file: ' . $filePath);
            return $filePath;
        }
        
        // Konverter til WebP
        if (!imagewebp($image, $webpPath, 90)) {
            error_log('Failed to save WebP image: ' . $webpPath);
            imagedestroy($image);
            return $filePath;
        }
        
        imagedestroy($image);
        
        // Hvis konverteringen lykkedes, slet den originale fil
        if (file_exists($webpPath) && filesize($webpPath) > 0) {
            if (unlink($filePath)) {
                return $webpPath;
            } else {
                error_log('Failed to delete original file: ' . $filePath);
            }
        }
        
        return $filePath;
    }
    
    /**
     * Generer responsive versioner af et billede
     */
    private function generateResponsiveImages($filePath) {
        // Kontroller at GD-biblioteket er tilgængeligt
        if (!extension_loaded('gd')) {
            error_log('GD extension is not available');
            return;
        }
        
        $info = pathinfo($filePath);
        $sizes = [
            'small' => 640,   // Mobil
            'medium' => 1024, // Tablet
            'large' => 1920   // Desktop
        ];
        
        // Tjek om filen er en WebP
        if (strtolower($info['extension']) !== 'webp') {
            error_log('File is not WebP: ' . $filePath);
            return;
        }
        
        // Indlæs originalt billede
        $image = imagecreatefromwebp($filePath);
        if (!$image) {
            error_log('Failed to create image from WebP: ' . $filePath);
            return;
        }
        
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
            if (!$newImage) {
                error_log('Failed to create new image for size: ' . $size);
                continue;
            }
            
            // Bevar transparens
            imagepalettetotruecolor($newImage);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            
            // Skaler billedet
            if (!imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, $origHeight)) {
                error_log('Failed to resize image for size: ' . $size);
                imagedestroy($newImage);
                continue;
            }
            
            // Gem det skalerede billede
            $newFilePath = $info['dirname'] . '/' . $info['filename'] . '_' . $size . '.webp';
            if (!imagewebp($newImage, $newFilePath, 90)) {
                error_log('Failed to save WebP image for size: ' . $size);
            }
            
            imagedestroy($newImage);
        }
        
        imagedestroy($image);
    }
}
