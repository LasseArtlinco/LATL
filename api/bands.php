<?php
// api/bands.php - Bands controller

class BandsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all bands for a specific page
     */
    public function getBands($pageId) {
        try {
            // Check if the page exists in the layout_config table
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Page not found'];
            }
            
            // Get bands for this page
            $bands = $this->db->select(
                "SELECT * FROM layout_bands WHERE layout_id = ? ORDER BY band_order",
                [$layout['id']]
            );
            
            // Process band content (JSON to array)
            foreach ($bands as &$band) {
                if (isset($band['band_content']) && !empty($band['band_content'])) {
                    $band['band_content'] = json_decode($band['band_content'], true);
                }
            }
            
            return ['status' => 'success', 'data' => $bands];
        } catch (Exception $e) {
            error_log('BandsController getBands error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get a specific band
     */
    public function getBand($pageId, $bandId) {
        try {
            // Get the layout ID
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Page not found'];
            }
            
            // Get the specific band
            $band = $this->db->selectOne(
                "SELECT * FROM layout_bands WHERE layout_id = ? AND band_id = ?",
                [$layout['id'], $bandId]
            );
            
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Process band content (JSON to array)
            if (isset($band['band_content']) && !empty($band['band_content'])) {
                $band['band_content'] = json_decode($band['band_content'], true);
            }
            
            return ['status' => 'success', 'data' => $band];
        } catch (Exception $e) {
            error_log('BandsController getBand error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create a new band
     */
    public function createBand($pageId, $bandData) {
        try {
            // Validate input data
            if (!isset($bandData['band_type']) || !isset($bandData['band_content'])) {
                http_response_code(400);
                return [
                    'status' => 'error', 
                    'message' => 'Band type and content are required'
                ];
            }
            
            // Get the layout ID
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Page not found'];
            }
            
            // Generate a unique band ID if not provided
            if (!isset($bandData['band_id'])) {
                $bandData['band_id'] = 'band_' . time() . '_' . rand(1000, 9999);
            }
            
            // Get the highest band order and add 1 for the new band
            $highestOrder = $this->db->selectOne(
                "SELECT MAX(band_order) as max_order FROM layout_bands WHERE layout_id = ?",
                [$layout['id']]
            );
            
            $newOrder = (isset($highestOrder['max_order'])) ? $highestOrder['max_order'] + 1 : 1;
            
            // Convert band_content to JSON if it's an array
            $bandContent = is_array($bandData['band_content']) 
                ? json_encode($bandData['band_content']) 
                : $bandData['band_content'];
            
            // Insert the new band
            $this->db->insert('layout_bands', [
                'layout_id' => $layout['id'],
                'band_type' => $bandData['band_type'],
                'band_height' => isset($bandData['band_height']) ? $bandData['band_height'] : 1,
                'band_content' => $bandContent,
                'band_order' => isset($bandData['band_order']) ? $bandData['band_order'] : $newOrder,
                'band_id' => $bandData['band_id']
            ]);
            
            // Get the newly created band
            return $this->getBand($pageId, $bandData['band_id']);
        } catch (Exception $e) {
            error_log('BandsController createBand error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update an existing band
     */
    public function updateBand($pageId, $bandId, $bandData) {
        try {
            // Get the layout ID
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Page not found'];
            }
            
            // Check if band exists
            $band = $this->db->selectOne(
                "SELECT id FROM layout_bands WHERE layout_id = ? AND band_id = ?",
                [$layout['id'], $bandId]
            );
            
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Prepare update data
            $updateData = [];
            
            if (isset($bandData['band_type'])) {
                $updateData['band_type'] = $bandData['band_type'];
            }
            
            if (isset($bandData['band_height'])) {
                $updateData['band_height'] = $bandData['band_height'];
            }
            
            if (isset($bandData['band_order'])) {
                $updateData['band_order'] = $bandData['band_order'];
            }
            
            if (isset($bandData['band_content'])) {
                $updateData['band_content'] = is_array($bandData['band_content']) 
                    ? json_encode($bandData['band_content']) 
                    : $bandData['band_content'];
            }
            
            if (!empty($updateData)) {
                // Update the band
                $this->db->update(
                    'layout_bands',
                    $updateData,
                    'layout_id = ? AND band_id = ?',
                    [$layout['id'], $bandId]
                );
            }
            
            // Get the updated band
            return $this->getBand($pageId, $bandId);
        } catch (Exception $e) {
            error_log('BandsController updateBand error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete a band
     */
    public function deleteBand($pageId, $bandId) {
        try {
            // Get the layout ID
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Page not found'];
            }
            
            // Check if band exists
            $band = $this->db->selectOne(
                "SELECT id, band_order FROM layout_bands WHERE layout_id = ? AND band_id = ?",
                [$layout['id'], $bandId]
            );
            
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Delete the band
            $this->db->delete(
                'layout_bands',
                'layout_id = ? AND band_id = ?',
                [$layout['id'], $bandId]
            );
            
            // Re-order remaining bands
            $this->reorderBands($layout['id'], $band['band_order']);
            
            return ['status' => 'success', 'message' => 'Band deleted successfully'];
        } catch (Exception $e) {
            error_log('BandsController deleteBand error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Re-order bands after deletion
     */
    private function reorderBands($layoutId, $deletedOrder) {
        try {
            // Get all bands with higher order than the deleted one
            $bands = $this->db->select(
                "SELECT id, band_order FROM layout_bands WHERE layout_id = ? AND band_order > ? ORDER BY band_order",
                [$layoutId, $deletedOrder]
            );
            
            // Decrement the order of each band
            foreach ($bands as $band) {
                $this->db->update(
                    'layout_bands',
                    ['band_order' => $band['band_order'] - 1],
                    'id = ?',
                    [$band['id']]
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log('BandsController reorderBands error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle product image upload
     */
    public function handleProductImageUpload($file, $pageId, $bandId) {
        try {
            require_once 'image_handler.php';
            $imageHandler = new ImageHandler();
            
            // Define upload path
            $uploadDir = ROOT_PATH . '/uploads/images/bands/product/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate a unique filename
            $filename = $pageId . '_' . $bandId . '_' . time() . '_' . uniqid() . '.jpg';
            $uploadPath = $uploadDir . $filename;
            
            // Process and save the image
            $result = $imageHandler->processAndSaveImage($file, $uploadPath);
            
            if (!$result) {
                throw new Exception('Failed to process and save product image');
            }
            
            // Get the band data
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            $band = $this->db->selectOne(
                "SELECT * FROM layout_bands WHERE layout_id = ? AND band_id = ?",
                [$layout['id'], $bandId]
            );
            
            if (!$band) {
                throw new Exception('Band not found');
            }
            
            // Update band content with the image URL
            $bandContent = json_decode($band['band_content'], true);
            
            // Update the image URL in band content based on band type
            if ($band['band_type'] === 'product') {
                $bandContent['image'] = BASE_URL . '/uploads/images/bands/product/' . $filename;
            } else {
                // Generic handling for other band types
                $bandContent['product_image'] = BASE_URL . '/uploads/images/bands/product/' . $filename;
            }
            
            // Update the band content
            $this->db->update(
                'layout_bands',
                ['band_content' => json_encode($bandContent)],
                'id = ?',
                [$band['id']]
            );
            
            return true;
        } catch (Exception $e) {
            error_log('BandsController handleProductImageUpload error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle slide image upload for slideshow bands
     */
    public function handleSlideImageUpload($file, $pageId, $bandId, $slideIndex) {
        try {
            require_once 'image_handler.php';
            $imageHandler = new ImageHandler();
            
            // Define upload path
            $uploadDir = ROOT_PATH . '/uploads/images/bands/slideshow/slides/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate a unique filename
            $filename = $pageId . '_' . $bandId . '_slide_' . $slideIndex . '_' . time() . '_' . uniqid() . '.jpg';
            $uploadPath = $uploadDir . $filename;
            
            // Process and save the image
            $result = $imageHandler->processAndSaveImage($file, $uploadPath);
            
            if (!$result) {
                throw new Exception('Failed to process and save slide image');
            }
            
            // Get the band data
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            $band = $this->db->selectOne(
                "SELECT * FROM layout_bands WHERE layout_id = ? AND band_id = ?",
                [$layout['id'], $bandId]
            );
            
            if (!$band) {
                throw new Exception('Band not found');
            }
            
            // Update band content with the image URL
            $bandContent = json_decode($band['band_content'], true);
            
            // Update the image URL in band content based on band type
            if ($band['band_type'] === 'slideshow' && isset($bandContent['slides'][$slideIndex])) {
                $bandContent['slides'][$slideIndex]['image'] = BASE_URL . '/uploads/images/bands/slideshow/slides/' . $filename;
            } else {
                // Generic handling for other band types or missing slides
                if (!isset($bandContent['slides'])) {
                    $bandContent['slides'] = [];
                }
                
                if (!isset($bandContent['slides'][$slideIndex])) {
                    $bandContent['slides'][$slideIndex] = [];
                }
                
                $bandContent['slides'][$slideIndex]['image'] = BASE_URL . '/uploads/images/bands/slideshow/slides/' . $filename;
            }
            
            // Update the band content
            $this->db->update(
                'layout_bands',
                ['band_content' => json_encode($bandContent)],
                'id = ?',
                [$band['id']]
            );
            
            return true;
        } catch (Exception $e) {
            error_log('BandsController handleSlideImageUpload error: ' . $e->getMessage());
            return false;
        }
    }
}
