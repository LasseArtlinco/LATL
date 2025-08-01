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
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            error_log('LayoutController getAll error: ' . $e->getMessage());
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
                $layout['layout_data'] = json_decode($layout['layout_data'], true);
            }
            
            return ['status' => 'success', 'data' => $layout];
        } catch (Exception $e) {
            error_log('LayoutController getById error: ' . $e->getMessage());
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
            
            // Konverter layout_data til JSON
            $layoutData = is_array($data['layout_data']) ? json_encode($data['layout_data']) : $data['layout_data'];
            
            // Opret layout
            $this->db->insert('layout_config', [
                'page_id' => $data['page_id'],
                'layout_data' => $layoutData
            ]);
            
            // Hent det nye layout
            return $this->getById($data['page_id']);
        } catch (Exception $e) {
            error_log('LayoutController create error: ' . $e->getMessage());
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
            
            // Opdater layout data
            if (isset($data['layout_data'])) {
                $layoutData = is_array($data['layout_data']) ? json_encode($data['layout_data']) : $data['layout_data'];
                
                $this->db->update('layout_config', [
                    'layout_data' => $layoutData
                ], 'page_id = ?', [$pageId]);
            }
            
            // Hent det opdaterede layout
            return $this->getById($pageId);
        } catch (Exception $e) {
            error_log('LayoutController update error: ' . $e->getMessage());
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
            error_log('LayoutController delete error: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
