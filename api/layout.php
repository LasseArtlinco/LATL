<?php
// api/layout.php - Layout controller - Opdateret med support for global_styles, font_config, color_palette og bands
class LayoutController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Hent alle layout konfigurationer
     */
    public function getAll() {
        try {
            $layouts = $this->db->select("SELECT * FROM layout_config ORDER BY page_id");
            
            // For hvert layout, dekoder vi JSON-felterne
            foreach ($layouts as &$layout) {
                $this->decodeJsonFields($layout);
            }
            
            return ['status' => 'success', 'data' => $layouts];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent layout konfiguration baseret på page_id
     */
    public function getById($pageId) {
        try {
            $layout = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            // Konverter JSON-felter til arrays
            $this->decodeJsonFields($layout);
            
            // Hent bands knyttet til dette layout
            $bands = $this->getBands($layout['id']);
            $layout['bands'] = $bands;
            
            return ['status' => 'success', 'data' => $layout];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opret nyt layout
     */
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
            
            // Forbered data til indsættelse - konverter arrays til JSON
            $layoutData = [
                'page_id' => $data['page_id'],
                'layout_data' => isset($data['layout_data']) ? $this->prepareJsonField($data['layout_data']) : null,
                'global_styles' => isset($data['global_styles']) ? $this->prepareJsonField($data['global_styles']) : null,
                'font_config' => isset($data['font_config']) ? $this->prepareJsonField($data['font_config']) : null,
                'color_palette' => isset($data['color_palette']) ? $this->prepareJsonField($data['color_palette']) : null
            ];
            
            // Opret layout
            $layoutId = $this->db->insert('layout_config', $layoutData);
            
            // Hvis der er bands med i data, opretter vi dem
            if (isset($data['bands']) && is_array($data['bands'])) {
                $this->createBands($layoutId, $data['bands']);
            }
            
            // Hent det nye layout
            return $this->getById($data['page_id']);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opdater eksisterende layout
     */
    public function update($pageId, $data) {
        try {
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            $updateData = [];
            
            // Opdater layout felter hvis de er inkluderet i request
            if (isset($data['layout_data'])) {
                $updateData['layout_data'] = $this->prepareJsonField($data['layout_data']);
            }
            
            if (isset($data['global_styles'])) {
                $updateData['global_styles'] = $this->prepareJsonField($data['global_styles']);
            }
            
            if (isset($data['font_config'])) {
                $updateData['font_config'] = $this->prepareJsonField($data['font_config']);
            }
            
            if (isset($data['color_palette'])) {
                $updateData['color_palette'] = $this->prepareJsonField($data['color_palette']);
            }
            
            // Udfør opdatering hvis der er data at opdatere
            if (!empty($updateData)) {
                $this->db->update('layout_config', $updateData, 'id = ?', [$layoutId]);
            }
            
            // Hvis der er bands med i data, opdaterer vi dem
            if (isset($data['bands']) && is_array($data['bands'])) {
                // Slet eksisterende bands først
                $this->db->delete('layout_bands', 'layout_id = ?', [$layoutId]);
                
                // Opret nye bands
                $this->createBands($layoutId, $data['bands']);
            }
            
            // Hent det opdaterede layout
            return $this->getById($pageId);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Slet layout
     */
    public function delete($pageId) {
        try {
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            
            // Slet bands knyttet til dette layout
            $this->db->delete('layout_bands', 'layout_id = ?', [$layoutId]);
            
            // Slet layout
            $this->db->delete('layout_config', 'id = ?', [$layoutId]);
            
            return ['status' => 'success', 'message' => 'Layout deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent globale stilarter
     */
    public function getGlobalStyles() {
        try {
            // Hent layout med global_styles, font_config og color_palette
            $globalStyles = $this->db->selectOne("
                SELECT global_styles, font_config, color_palette 
                FROM layout_config 
                WHERE page_id = 'global'
            ");
            
            if (!$globalStyles) {
                // Hvis der ikke findes en global konfiguration, returnerer vi tom data
                return [
                    'status' => 'success',
                    'data' => [
                        'global_styles' => null,
                        'font_config' => null,
                        'color_palette' => null
                    ]
                ];
            }
            
            // Konverter JSON-felter til arrays
            $this->decodeJsonFields($globalStyles);
            
            return ['status' => 'success', 'data' => $globalStyles];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opdater globale stilarter
     */
    public function updateGlobalStyles($data) {
        try {
            // Tjek om global layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = 'global'");
            
            if (!$layout) {
                // Hvis der ikke findes en global konfiguration, opretter vi en
                $layoutData = [
                    'page_id' => 'global',
                    'layout_data' => null,
                    'global_styles' => isset($data['global_styles']) ? $this->prepareJsonField($data['global_styles']) : null,
                    'font_config' => isset($data['font_config']) ? $this->prepareJsonField($data['font_config']) : null,
                    'color_palette' => isset($data['color_palette']) ? $this->prepareJsonField($data['color_palette']) : null
                ];
                
                $this->db->insert('layout_config', $layoutData);
            } else {
                // Opdater eksisterende global konfiguration
                $updateData = [];
                
                if (isset($data['global_styles'])) {
                    $updateData['global_styles'] = $this->prepareJsonField($data['global_styles']);
                }
                
                if (isset($data['font_config'])) {
                    $updateData['font_config'] = $this->prepareJsonField($data['font_config']);
                }
                
                if (isset($data['color_palette'])) {
                    $updateData['color_palette'] = $this->prepareJsonField($data['color_palette']);
                }
                
                if (!empty($updateData)) {
                    $this->db->update('layout_config', $updateData, 'id = ?', [$layout['id']]);
                }
            }
            
            // Hent de opdaterede globale stilarter
            return $this->getGlobalStyles();
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent bands for et specifikt layout
     */
    public function getBands($layoutId) {
        try {
            $bands = $this->db->select("
                SELECT * FROM layout_bands 
                WHERE layout_id = ? 
                ORDER BY band_order
            ", [$layoutId]);
            
            // Konverter band_content fra JSON til array for hvert band
            foreach ($bands as &$band) {
                if (isset($band['band_content']) && $band['band_content']) {
                    $band['band_content'] = json_decode($band['band_content'], true);
                }
            }
            
            return $bands;
        } catch (Exception $e) {
            throw new Exception("Error fetching bands: " . $e->getMessage());
        }
    }
    
    /**
     * Opret bands for et layout
     */
    private function createBands($layoutId, $bands) {
        foreach ($bands as $band) {
            // Validering af nødvendige felter
            if (!isset($band['band_type']) || !isset($band['band_order'])) {
                continue; // Spring over dette band hvis nødvendige felter mangler
            }
            
            $bandData = [
                'layout_id' => $layoutId,
                'band_type' => $band['band_type'],
                'band_height' => isset($band['band_height']) ? $band['band_height'] : 1,
                'band_content' => isset($band['band_content']) ? $this->prepareJsonField($band['band_content']) : null,
                'band_order' => $band['band_order']
            ];
            
            $this->db->insert('layout_bands', $bandData);
        }
    }
    
    /**
     * Opdater et specifikt band
     */
    public function updateBand($bandId, $data) {
        try {
            // Tjek om bandet eksisterer
            $band = $this->db->selectOne("SELECT id FROM layout_bands WHERE id = ?", [$bandId]);
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            $updateData = [];
            
            if (isset($data['band_type'])) {
                $updateData['band_type'] = $data['band_type'];
            }
            
            if (isset($data['band_height'])) {
                $updateData['band_height'] = $data['band_height'];
            }
            
            if (isset($data['band_content'])) {
                $updateData['band_content'] = $this->prepareJsonField($data['band_content']);
            }
            
            if (isset($data['band_order'])) {
                $updateData['band_order'] = $data['band_order'];
            }
            
            if (!empty($updateData)) {
                $this->db->update('layout_bands', $updateData, 'id = ?', [$bandId]);
            }
            
            // Hent det opdaterede band
            $updatedBand = $this->db->selectOne("SELECT * FROM layout_bands WHERE id = ?", [$bandId]);
            
            // Konverter band_content fra JSON til array
            if ($updatedBand['band_content']) {
                $updatedBand['band_content'] = json_decode($updatedBand['band_content'], true);
            }
            
            return ['status' => 'success', 'data' => $updatedBand];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Slet et specifikt band
     */
    public function deleteBand($bandId) {
        try {
            // Tjek om bandet eksisterer
            $band = $this->db->selectOne("SELECT id FROM layout_bands WHERE id = ?", [$bandId]);
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Slet bandet
            $this->db->delete('layout_bands', 'id = ?', [$bandId]);
            
            return ['status' => 'success', 'message' => 'Band deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hjælpefunktion til at konvertere arrays til JSON-strenge
     */
    private function prepareJsonField($data) {
        if (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }
    
    /**
     * Hjælpefunktion til at konvertere JSON-strenge til arrays
     */
    private function decodeJsonFields(&$layout) {
        $jsonFields = ['layout_data', 'global_styles', 'font_config', 'color_palette'];
        
        foreach ($jsonFields as $field) {
            if (isset($layout[$field]) && $layout[$field]) {
                $layout[$field] = json_decode($layout[$field], true);
            }
        }
    }
}
