<?php
// api/bands.php - Band controller for LATL.dk

/**
 * Controller til håndtering af bånd (bands) på websitet
 */
class BandsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Hent alle bånd for en side
     */
    public function getBands($pageId) {
        try {
            // Først, få layout_id for den angivne side
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                // Hvis layout ikke findes, opret det først
                require_once dirname(__FILE__) . '/layout.php';
                $layoutController = new LayoutController($this->db);
                $result = $layoutController->create(['page_id' => $pageId]);
                
                if ($result['status'] !== 'success') {
                    return ['status' => 'error', 'message' => 'Failed to create layout'];
                }
                
                $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
                
                if (!$layout) {
                    return ['status' => 'error', 'message' => 'Layout not found after creation'];
                }
            }
            
            $layoutId = $layout['id'];
            
            // Hent bands fra layout_bands tabellen
            $bands = $this->db->select(
                "SELECT * FROM layout_bands WHERE layout_id = ? ORDER BY band_order",
                [$layoutId]
            );
            
            // Konverter band_content fra JSON til arrays
            foreach ($bands as &$band) {
                if (isset($band['band_content']) && !empty($band['band_content'])) {
                    $bandContent = json_decode($band['band_content'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $band['band_content'] = $bandContent;
                    } else {
                        error_log('JSON decode error in band_content: ' . json_last_error_msg());
                        $band['band_content'] = [];
                    }
                } else {
                    $band['band_content'] = [];
                }
                
                // Tilføj band_id hvis det ikke findes
                if (!isset($band['band_id'])) {
                    $band['band_id'] = 'band_' . $band['id'];
                }
            }
            
            return ['status' => 'success', 'data' => $bands];
        } catch (Exception $e) {
            error_log('Error in getBands: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent et specifikt bånd
     */
    public function getBand($pageId, $bandId) {
        try {
            // Først, få layout_id for den angivne side
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            
            // Tjek om bandId er et numerisk ID eller et band_id med prefix
            if (is_numeric($bandId)) {
                $band = $this->db->selectOne(
                    "SELECT * FROM layout_bands WHERE layout_id = ? AND id = ?",
                    [$layoutId, $bandId]
                );
            } else {
                // Antag at bandId er et unikt band_id med prefix (f.eks. 'band_123')
                $band = $this->db->selectOne(
                    "SELECT * FROM layout_bands WHERE layout_id = ? AND id = ?",
                    [$layoutId, str_replace('band_', '', $bandId)]
                );
                
                // Hvis det ikke blev fundet, prøv at søge i JSON-indholdet
                if (!$band) {
                    $allBands = $this->db->select(
                        "SELECT * FROM layout_bands WHERE layout_id = ?",
                        [$layoutId]
                    );
                    
                    foreach ($allBands as $currentBand) {
                        $content = json_decode($currentBand['band_content'], true);
                        if (isset($content['band_id']) && $content['band_id'] === $bandId) {
                            $band = $currentBand;
                            break;
                        }
                    }
                }
            }
            
            if (!$band) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Konverter band_content fra JSON til array
            if (isset($band['band_content']) && !empty($band['band_content'])) {
                $bandContent = json_decode($band['band_content'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $band['band_content'] = $bandContent;
                } else {
                    error_log('JSON decode error in band_content: ' . json_last_error_msg());
                    $band['band_content'] = [];
                }
            } else {
                $band['band_content'] = [];
            }
            
            // Tilføj band_id hvis det ikke findes
            if (!isset($band['band_id'])) {
                $band['band_id'] = 'band_' . $band['id'];
            }
            
            return ['status' => 'success', 'data' => $band];
        } catch (Exception $e) {
            error_log('Error in getBand: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
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
            
            // Få layout_id for den angivne side
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                // Hvis layout ikke findes, opret det først
                require_once dirname(__FILE__) . '/layout.php';
                $layoutController = new LayoutController($this->db);
                $result = $layoutController->create(['page_id' => $pageId]);
                
                if ($result['status'] !== 'success') {
                    return ['status' => 'error', 'message' => 'Failed to create layout'];
                }
                
                $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
                
                if (!$layout) {
                    return ['status' => 'error', 'message' => 'Layout not found after creation'];
                }
            }
            
            $layoutId = $layout['id'];
            
            // Forbered band data
            $dbData = [
                'layout_id' => $layoutId,
                'band_type' => $bandData['band_type'],
                'band_height' => $bandData['band_height'],
                'band_order' => $bandData['band_order']
            ];
            
            // Konverter band_content til JSON hvis det findes
            if (isset($bandData['band_content'])) {
                $dbData['band_content'] = json_encode($bandData['band_content']);
            } else {
                $dbData['band_content'] = '{}';
            }
            
            // Indsæt i databasen
            $bandId = $this->db->insert('layout_bands', $dbData);
            
            // Hent det nyoprettede bånd
            return $this->getBand($pageId, $bandId);
        } catch (Exception $e) {
            error_log('Error in createBand: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opdater et eksisterende bånd
     */
    public function updateBand($pageId, $bandId, $bandData) {
        try {
            // Få layout_id for den angivne side
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            
            // Find det eksisterende bånd
            $actualBandId = is_numeric($bandId) ? $bandId : str_replace('band_', '', $bandId);
            $existingBand = $this->db->selectOne(
                "SELECT * FROM layout_bands WHERE layout_id = ? AND id = ?",
                [$layoutId, $actualBandId]
            );
            
            if (!$existingBand) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Forbered data til opdatering
            $dbData = [];
            
            if (isset($bandData['band_type'])) {
                $dbData['band_type'] = $bandData['band_type'];
            }
            
            if (isset($bandData['band_height'])) {
                $dbData['band_height'] = $bandData['band_height'];
            }
            
            if (isset($bandData['band_order'])) {
                $dbData['band_order'] = $bandData['band_order'];
            }
            
            // Opdater band_content hvis det findes
            if (isset($bandData['band_content'])) {
                $dbData['band_content'] = json_encode($bandData['band_content']);
            }
            
            // Hvis der er data at opdatere
            if (!empty($dbData)) {
                $this->db->update(
                    'layout_bands', 
                    $dbData, 
                    'id = ?', 
                    [$actualBandId]
                );
            }
            
            // Hent det opdaterede bånd
            return $this->getBand($pageId, $actualBandId);
        } catch (Exception $e) {
            error_log('Error in updateBand: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Slet et bånd
     */
    public function deleteBand($pageId, $bandId) {
        try {
            // Få layout_id for den angivne side
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            
            // Find det eksisterende bånd
            $actualBandId = is_numeric($bandId) ? $bandId : str_replace('band_', '', $bandId);
            $existingBand = $this->db->selectOne(
                "SELECT * FROM layout_bands WHERE layout_id = ? AND id = ?",
                [$layoutId, $actualBandId]
            );
            
            if (!$existingBand) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Band not found'];
            }
            
            // Slet båndet
            $this->db->delete('layout_bands', 'id = ?', [$actualBandId]);
            
            return ['status' => 'success', 'message' => 'Band deleted successfully'];
        } catch (Exception $e) {
            error_log('Error in deleteBand: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
