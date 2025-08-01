<?php
// api/layout.php - Layout controller
class LayoutController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll() {
        try {
            $result = $this->db->select("SELECT * FROM layout_config ORDER BY page_id");
            
            // Konverter layout_data fra JSON til array for hver layout
            foreach ($result as &$layout) {
                if ($layout['layout_data']) {
                    $layout['layout_data'] = json_decode($layout['layout_data'], true);
                    
                    // Hvis bands findes i layout_data, flyt dem til top-level
                    if (isset($layout['layout_data']['bands'])) {
                        $layout['bands'] = $layout['layout_data']['bands'];
                        unset($layout['layout_data']['bands']);
                    }
                }
            }
            
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
            
            // Konverter layout_data fra JSON til array
            if ($layout['layout_data']) {
                $layoutData = json_decode($layout['layout_data'], true);
                
                // Hvis bands findes i layout_data, flyt dem til top-level
                if (isset($layoutData['bands'])) {
                    $layout['bands'] = $layoutData['bands'];
                    unset($layoutData['bands']);
                }
                
                $layout['layout_data'] = $layoutData;
            } else {
                $layout['layout_data'] = [];
            }
            
            return ['status' => 'success', 'data' => $layout];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function create($data) {
        try {
            // Valider input data
            if (!isset($data['page_id']) || !isset($data['layout_data'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Page ID and layout data are required'];
            }
            
            // Tjek om layout allerede eksisterer
            $existingLayout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$data['page_id']]);
            if ($existingLayout) {
                http_response_code(409);
                return ['status' => 'error', 'message' => 'Layout for this page already exists'];
            }
            
            // Forbered layout_data
            $layoutData = $data['layout_data'];
            
            // Hvis bands er angivet separat, gem dem i layout_data
            if (isset($data['bands'])) {
                $layoutData['bands'] = $data['bands'];
            }
            
            // Konverter layout_data til JSON
            $layoutDataJson = is_array($layoutData) ? json_encode($layoutData) : $layoutData;
            
            // Opret layout
            $this->db->insert('layout_config', [
                'page_id' => $data['page_id'],
                'layout_data' => $layoutDataJson
            ]);
            
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
            $layout = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            // Opdater layout data
            if (isset($data['layout_data']) || isset($data['bands'])) {
                // Hent eksisterende layout_data
                $currentLayoutData = $layout['layout_data'] ? json_decode($layout['layout_data'], true) : [];
                
                // Opdater layout_data hvis angivet
                if (isset($data['layout_data'])) {
                    $currentLayoutData = array_merge($currentLayoutData, $data['layout_data']);
                }
                
                // Hvis bands er angivet separat, opdater dem i layout_data
                if (isset($data['bands'])) {
                    $currentLayoutData['bands'] = $data['bands'];
                }
                
                // Konverter layout_data til JSON
                $layoutDataJson = json_encode($currentLayoutData);
                
                $this->db->update('layout_config', [
                    'layout_data' => $layoutDataJson
                ], 'page_id = ?', [$pageId]);
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
            
            // Slet layout
            $this->db->delete('layout_config', 'page_id = ?', [$pageId]);
            
            return ['status' => 'success', 'message' => 'Layout deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
