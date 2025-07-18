<?php
// api/layout.php - Udvidet Layout controller
class LayoutController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll() {
        try {
            $result = $this->db->select("SELECT * FROM layout_config ORDER BY page_id");
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function getById($pageId) {
        try {
            $layout = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            // Konverter JSON data til arrays
            if ($layout['layout_data']) {
                $layout['layout_data'] = json_decode($layout['layout_data'], true);
            }
            if ($layout['global_styles']) {
                $layout['global_styles'] = json_decode($layout['global_styles'], true);
            }
            if ($layout['font_config']) {
                $layout['font_config'] = json_decode($layout['font_config'], true);
            }
            if ($layout['color_palette']) {
                $layout['color_palette'] = json_decode($layout['color_palette'], true);
            }
            
            // Hent bånd for dette layout
            $bands = $this->getBandsForLayout($layout['id']);
            $layout['bands'] = $bands;
            
            return ['status' => 'success', 'data' => $layout];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function create($data) {
        try {
            // Valider input data
            if (!isset($data['page_id'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Page ID is required'];
            }
            
            // Tjek om layout allerede eksisterer
            $existingLayout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$data['page_id']]);
            if ($existingLayout) {
                http_response_code(409);
                return ['status' => 'error', 'message' => 'Layout for this page already exists'];
            }
            
            // Konverter JSON data
            $layoutData = isset($data['layout_data']) ? 
                (is_array($data['layout_data']) ? json_encode($data['layout_data']) : $data['layout_data']) : null;
            
            $globalStyles = isset($data['global_styles']) ? 
                (is_array($data['global_styles']) ? json_encode($data['global_styles']) : $data['global_styles']) : null;
            
            $fontConfig = isset($data['font_config']) ? 
                (is_array($data['font_config']) ? json_encode($data['font_config']) : $data['font_config']) : null;
            
            $colorPalette = isset($data['color_palette']) ? 
                (is_array($data['color_palette']) ? json_encode($data['color_palette']) : $data['color_palette']) : null;
            
            // Opret layout
            $layoutId = $this->db->insert('layout_config', [
                'page_id' => $data['page_id'],
                'layout_data' => $layoutData,
                'global_styles' => $globalStyles,
                'font_config' => $fontConfig,
                'color_palette' => $colorPalette
            ]);
            
            // Håndter bånd, hvis de er inkluderet
            if (isset($data['bands']) && is_array($data['bands'])) {
                $this->saveBands($layoutId, $data['bands']);
            }
            
            // Hent det nye layout
            return $this->getById($data['page_id']);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function update($pageId, $data) {
        try {
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $updateData = [];
            
            // Opdater layout data hvis den er sat
            if (isset($data['layout_data'])) {
                $updateData['layout_data'] = is_array($data['layout_data']) ? 
                    json_encode($data['layout_data']) : $data['layout_data'];
            }
            
            // Opdater global styles hvis de er sat
            if (isset($data['global_styles'])) {
                $updateData['global_styles'] = is_array($data['global_styles']) ? 
                    json_encode($data['global_styles']) : $data['global_styles'];
            }
            
            // Opdater font config hvis den er sat
            if (isset($data['font_config'])) {
                $updateData['font_config'] = is_array($data['font_config']) ? 
                    json_encode($data['font_config']) : $data['font_config'];
            }
            
            // Opdater color palette hvis den er sat
            if (isset($data['color_palette'])) {
                $updateData['color_palette'] = is_array($data['color_palette']) ? 
                    json_encode($data['color_palette']) : $data['color_palette'];
            }
            
            // Opdater layout data i databasen
            if (!empty($updateData)) {
                $this->db->update('layout_config', $updateData, 'id = ?', [$layout['id']]);
            }
            
            // Håndter bands, hvis de er inkluderet
            if (isset($data['bands']) && is_array($data['bands'])) {
                // Slet eksisterende bands først
                $this->db->delete('layout_bands', 'layout_id = ?', [$layout['id']]);
                
                // Gem de nye bands
                $this->saveBands($layout['id'], $data['bands']);
            }
            
            // Hent det opdaterede layout
            return $this->getById($pageId);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function delete($pageId) {
        try {
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            // Slet layout (kaskade-delete vil også slette tilknyttede bands)
            $this->db->delete('layout_config', 'page_id = ?', [$pageId]);
            
            return ['status' => 'success', 'message' => 'Layout deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    // Metode til at gemme bands
    private function saveBands($layoutId, $bands) {
        $order = 1;
        foreach ($bands as $band) {
            // Valider at de nødvendige felter er til stede
            if (!isset($band['band_type'])) {
                continue; // Spring over bands uden type
            }
            
            $bandContent = isset($band['band_content']) ? 
                (is_array($band['band_content']) ? json_encode($band['band_content']) : $band['band_content']) : null;
            
            $bandHeight = isset($band['band_height']) ? intval($band['band_height']) : 1;
            // Sikre at band_height er mellem 1 og 4
            $bandHeight = max(1, min(4, $bandHeight));
            
            $this->db->insert('layout_bands', [
                'layout_id' => $layoutId,
                'band_type' => $band['band_type'],
                'band_height' => $bandHeight,
                'band_content' => $bandContent,
                'band_order' => $order++
            ]);
        }
    }
    
    // Metode til at hente bands for et layout
    private function getBandsForLayout($layoutId) {
        $bands = $this->db->select("SELECT * FROM layout_bands WHERE layout_id = ? ORDER BY band_order", [$layoutId]);
        
        // Konverter band_content til arrays
        foreach ($bands as &$band) {
            if ($band['band_content']) {
                $band['band_content'] = json_decode($band['band_content'], true);
            }
        }
        
        return $bands;
    }
    
    // Metode til at hente globale stilarter
    public function getGlobalStyles() {
        try {
            $styles = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = 'global'");
            
            if (!$styles) {
                // Opret default global styles, hvis de ikke eksisterer
                $defaultStyles = [
                    'colors' => [
                        'primary' => '#333333',
                        'secondary' => '#666666',
                        'accent' => '#ff4500',
                        'background' => '#ffffff',
                        'text' => '#333333',
                        'link' => '#0066cc'
                    ],
                    'fonts' => [
                        'heading' => 'Arial, sans-serif',
                        'body' => 'Arial, sans-serif',
                        'price' => 'Arial, sans-serif',
                        'button' => 'Arial, sans-serif'
                    ],
                    'spacing' => [
                        'base' => '8px',
                        'small' => '4px',
                        'medium' => '16px',
                        'large' => '32px'
                    ]
                ];
                
                $globalStylesJson = json_encode($defaultStyles);
                
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'global_styles' => $globalStylesJson
                ]);
                
                return ['status' => 'success', 'data' => $defaultStyles];
            }
            
            // Parse JSON data
            $data = [
                'colors' => json_decode($styles['color_palette'] ?? '{}', true),
                'fonts' => json_decode($styles['font_config'] ?? '{}', true),
                'global' => json_decode($styles['global_styles'] ?? '{}', true)
            ];
            
            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    // Metode til at opdatere globale stilarter
    public function updateGlobalStyles($data) {
        try {
            $styles = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = 'global'");
            
            $updateData = [];
            
            if (isset($data['colors'])) {
                $updateData['color_palette'] = is_array($data['colors']) ? 
                    json_encode($data['colors']) : $data['colors'];
            }
            
            if (isset($data['fonts'])) {
                $updateData['font_config'] = is_array($data['fonts']) ? 
                    json_encode($data['fonts']) : $data['fonts'];
            }
            
            if (isset($data['global'])) {
                $updateData['global_styles'] = is_array($data['global']) ? 
                    json_encode($data['global']) : $data['global'];
            }
            
            if (!$styles) {
                // Opret global styles, hvis de ikke eksisterer
                $updateData['page_id'] = 'global';
                $this->db->insert('layout_config', $updateData);
            } else {
                // Opdater eksisterende global styles
                $this->db->update('layout_config', $updateData, 'id = ?', [$styles['id']]);
            }
            
            return $this->getGlobalStyles();
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
