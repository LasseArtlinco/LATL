<?php
// api/layout.php - Layout controller
class LayoutController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Hent alle layouts
     */
    public function getAll() {
        try {
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            if (empty($tables)) {
                // Opret tabellen hvis den ikke findes
                $this->createLayoutConfigTable();
                return ['status' => 'success', 'data' => []];
            }
            
            $layouts = $this->db->select("SELECT * FROM layout_config ORDER BY page_id");
            
            // Konverter JSON-data for hvert layout
            foreach ($layouts as &$layout) {
                $this->decodeLayoutData($layout);
                
                // Hent bands for dette layout
                if ($layout['page_id'] !== 'global') {
                    $layout['bands'] = $this->getBandsForLayout($layout['id']);
                }
            }
            
            return ['status' => 'success', 'data' => $layouts];
        } catch (Exception $e) {
            error_log('Error in getAll: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Hent et specifikt layout
     */
    public function getById($pageId) {
        try {
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            if (empty($tables)) {
                // Opret tabellen hvis den ikke findes
                $this->createLayoutConfigTable();
            }
            
            $layout = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", [$pageId]);
            
            if (!$layout) {
                // Hvis layout ikke findes, opret et tomt layout med standardværdier
                if ($pageId === 'forside') {
                    return $this->createDefaultFrontpage();
                } else {
                    return $this->createEmptyLayout($pageId);
                }
            }
            
            // Dekoder JSON-data
            $this->decodeLayoutData($layout);
            
            // Hent bands for dette layout
            if ($pageId !== 'global') {
                $layout['bands'] = $this->getBandsForLayout($layout['id']);
            }
            
            return ['status' => 'success', 'data' => $layout];
        } catch (Exception $e) {
            error_log('Error in getById: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opret et nyt layout
     */
    public function create($data) {
        try {
            // Valider input data
            if (!isset($data['page_id'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Page ID is required'];
            }
            
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            if (empty($tables)) {
                // Opret tabellen hvis den ikke findes
                $this->createLayoutConfigTable();
            }
            
            // Tjek om layout allerede eksisterer
            $existingLayout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$data['page_id']]);
            if ($existingLayout) {
                http_response_code(409);
                return ['status' => 'error', 'message' => 'Layout for this page already exists'];
            }
            
            // Forbered data til lagring
            $dbData = [
                'page_id' => $data['page_id']
            ];
            
            // Håndter forskellige dataformater
            if (isset($data['layout_data'])) {
                if (is_array($data['layout_data'])) {
                    $dbData['layout_data'] = json_encode($data['layout_data']);
                } else {
                    $dbData['layout_data'] = $data['layout_data'];
                }
            } else {
                $dbData['layout_data'] = '{}';
            }
            
            // Opret layout
            $layoutId = $this->db->insert('layout_config', $dbData);
            
            // Opret bands hvis de er angivet
            if (isset($data['bands']) && is_array($data['bands'])) {
                $this->createBands($layoutId, $data['bands']);
            }
            
            // Hent det nye layout
            return $this->getById($data['page_id']);
        } catch (Exception $e) {
            error_log('Error in create: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opdater et eksisterende layout
     */
    public function update($pageId, $data) {
        try {
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            if (empty($tables)) {
                // Opret tabellen hvis den ikke findes
                $this->createLayoutConfigTable();
                
                // Opret nyt layout
                $createData = array_merge(['page_id' => $pageId], $data);
                return $this->create($createData);
            }
            
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                // Hvis layoutet ikke eksisterer, opret det
                $createData = array_merge(['page_id' => $pageId], $data);
                return $this->create($createData);
            }
            
            $layoutId = $layout['id'];
            
            // Forbered data til opdatering
            $dbData = [];
            
            // Håndter forskellige dataformater for layout_data
            if (isset($data['layout_data'])) {
                if (is_array($data['layout_data'])) {
                    $dbData['layout_data'] = json_encode($data['layout_data']);
                } else {
                    $dbData['layout_data'] = $data['layout_data'];
                }
            }
            
            // Opdater andre JSON-felter hvis de findes
            if (isset($data['global_styles'])) {
                if (is_array($data['global_styles'])) {
                    $dbData['global_styles'] = json_encode($data['global_styles']);
                } else {
                    $dbData['global_styles'] = $data['global_styles'];
                }
            }
            
            if (isset($data['font_config'])) {
                if (is_array($data['font_config'])) {
                    $dbData['font_config'] = json_encode($data['font_config']);
                } else {
                    $dbData['font_config'] = $data['font_config'];
                }
            }
            
            if (isset($data['color_palette'])) {
                if (is_array($data['color_palette'])) {
                    $dbData['color_palette'] = json_encode($data['color_palette']);
                } else {
                    $dbData['color_palette'] = $data['color_palette'];
                }
            }
            
            // Hvis der er data at opdatere
            if (!empty($dbData)) {
                // Opdater layout
                $this->db->update('layout_config', $dbData, 'page_id = ?', [$pageId]);
            }
            
            // Opdater bands hvis de er angivet
            if (isset($data['bands']) && is_array($data['bands'])) {
                $this->updateBands($layoutId, $data['bands']);
            }
            
            // Hent det opdaterede layout
            return $this->getById($pageId);
        } catch (Exception $e) {
            error_log('Error in update: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Slet et layout
     */
    public function delete($pageId) {
        try {
            // Kontroller om tabellen eksisterer
            $tables = $this->db->select("SHOW TABLES LIKE 'layout_config'");
            if (empty($tables)) {
                return ['status' => 'success', 'message' => 'Layout deleted successfully (table does not exist)'];
            }
            
            // Tjek om layout eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", [$pageId]);
            if (!$layout) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Layout not found'];
            }
            
            $layoutId = $layout['id'];
            
            // Slet alle bands for dette layout
            $this->db->delete('layout_bands', 'layout_id = ?', [$layoutId]);
            
            // Slet layout
            $this->db->delete('layout_config', 'page_id = ?', [$pageId]);
            
            return ['status' => 'success', 'message' => 'Layout deleted successfully'];
        } catch (Exception $e) {
            error_log('Error in delete: ' . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Opret layout_config tabellen hvis den ikke findes
     */
    private function createLayoutConfigTable() {
        $query = "
            CREATE TABLE IF NOT EXISTS layout_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id VARCHAR(255) NOT NULL UNIQUE,
                layout_data JSON,
                global_styles JSON,
                font_config JSON,
                color_palette JSON,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $this->db->query($query);
    }
    
    /**
     * Dekoder JSON-data i et layout
     */
    private function decodeLayoutData(&$layout) {
        // Dekoder layout_data
        if (isset($layout['layout_data']) && $layout['layout_data']) {
            $layoutData = json_decode($layout['layout_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $layout['layout_data'] = $layoutData;
            } else {
                error_log('JSON decode error in layout_data: ' . json_last_error_msg());
                $layout['layout_data'] = [];
            }
        } else {
            $layout['layout_data'] = [];
        }
        
        // Dekoder global_styles
        if (isset($layout['global_styles']) && $layout['global_styles']) {
            $globalStyles = json_decode($layout['global_styles'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $layout['global_styles'] = $globalStyles;
            } else {
                error_log('JSON decode error in global_styles: ' . json_last_error_msg());
                $layout['global_styles'] = null;
            }
        }
        
        // Dekoder font_config
        if (isset($layout['font_config']) && $layout['font_config']) {
            $fontConfig = json_decode($layout['font_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $layout['font_config'] = $fontConfig;
            } else {
                error_log('JSON decode error in font_config: ' . json_last_error_msg());
                $layout['font_config'] = null;
            }
        }
        
        // Dekoder color_palette
        if (isset($layout['color_palette']) && $layout['color_palette']) {
            $colorPalette = json_decode($layout['color_palette'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $layout['color_palette'] = $colorPalette;
            } else {
                error_log('JSON decode error in color_palette: ' . json_last_error_msg());
                $layout['color_palette'] = null;
            }
        }
    }
    
    /**
     * Hent bands for et layout
     */
    private function getBandsForLayout($layoutId) {
        // Tjek om layout_bands tabellen eksisterer
        $tables = $this->db->select("SHOW TABLES LIKE 'layout_bands'");
        if (empty($tables)) {
            return [];
        }
        
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
        
        return $bands;
    }
    
    /**
     * Opret bands for et layout
     */
    private function createBands($layoutId, $bands) {
        // Kontroller om layout_bands tabellen eksisterer
        $tables = $this->db->select("SHOW TABLES LIKE 'layout_bands'");
        if (empty($tables)) {
            // Opret tabellen hvis den ikke findes
            $this->createLayoutBandsTable();
        }
        
        foreach ($bands as $band) {
            $dbData = [
                'layout_id' => $layoutId,
                'band_type' => $band['band_type'],
                'band_height' => $band['band_height'] ?? 1,
                'band_order' => $band['band_order'] ?? 1
            ];
            
            // Konverter band_content til JSON hvis det findes
            if (isset($band['band_content'])) {
                if (is_array($band['band_content'])) {
                    $dbData['band_content'] = json_encode($band['band_content']);
                } else {
                    $dbData['band_content'] = $band['band_content'];
                }
            } else {
                $dbData['band_content'] = '{}';
            }
            
            // Indsæt i databasen
            $this->db->insert('layout_bands', $dbData);
        }
    }
    
    /**
     * Opdater bands for et layout
     */
    private function updateBands($layoutId, $bands) {
        // Kontroller om layout_bands tabellen eksisterer
        $tables = $this->db->select("SHOW TABLES LIKE 'layout_bands'");
        if (empty($tables)) {
            // Opret tabellen hvis den ikke findes
            $this->createLayoutBandsTable();
            $this->createBands($layoutId, $bands);
            return;
        }
        
        // Find eksisterende band IDs
        $existingBands = $this->db->select(
            "SELECT id FROM layout_bands WHERE layout_id = ?",
            [$layoutId]
        );
        
        $existingIds = array_map(function($band) {
            return $band['id'];
        }, $existingBands);
        
        // Holder styr på opdaterede bånd
        $updatedIds = [];
        
        foreach ($bands as $band) {
            // Tjek om båndet har et ID og om det eksisterer
            $bandId = null;
            
            if (isset($band['id']) && is_numeric($band['id'])) {
                $bandId = $band['id'];
            } else if (isset($band['band_id']) && preg_match('/^band_(\d+)$/', $band['band_id'], $matches)) {
                $bandId = $matches[1];
            }
            
            if ($bandId && in_array($bandId, $existingIds)) {
                // Opdater eksisterende bånd
                $dbData = [];
                
                if (isset($band['band_type'])) {
                    $dbData['band_type'] = $band['band_type'];
                }
                
                if (isset($band['band_height'])) {
                    $dbData['band_height'] = $band['band_height'];
                }
                
                if (isset($band['band_order'])) {
                    $dbData['band_order'] = $band['band_order'];
                }
                
                // Opdater band_content hvis det findes
                if (isset($band['band_content'])) {
                    if (is_array($band['band_content'])) {
                        $dbData['band_content'] = json_encode($band['band_content']);
                    } else {
                        $dbData['band_content'] = $band['band_content'];
                    }
                }
                
                // Hvis der er data at opdatere
                if (!empty($dbData)) {
                    $this->db->update(
                        'layout_bands', 
                        $dbData, 
                        'id = ?', 
                        [$bandId]
                    );
                }
                
                $updatedIds[] = $bandId;
            } else {
                // Opret nyt bånd
                $dbData = [
                    'layout_id' => $layoutId,
                    'band_type' => $band['band_type'],
                    'band_height' => $band['band_height'] ?? 1,
                    'band_order' => $band['band_order'] ?? 1
                ];
                
                // Konverter band_content til JSON hvis det findes
                if (isset($band['band_content'])) {
                    if (is_array($band['band_content'])) {
                        $dbData['band_content'] = json_encode($band['band_content']);
                    } else {
                        $dbData['band_content'] = $band['band_content'];
                    }
                } else {
                    $dbData['band_content'] = '{}';
                }
                
                // Indsæt i databasen
                $newBandId = $this->db->insert('layout_bands', $dbData);
                $updatedIds[] = $newBandId;
            }
        }
        
        // Slet bånd der ikke er med i opdateringen
        $idsToDelete = array_diff($existingIds, $updatedIds);
        
        if (!empty($idsToDelete)) {
            foreach ($idsToDelete as $idToDelete) {
                $this->db->delete('layout_bands', 'id = ?', [$idToDelete]);
            }
        }
    }
    
    /**
     * Opret layout_bands tabellen hvis den ikke findes
     */
    private function createLayoutBandsTable() {
        $query = "
            CREATE TABLE IF NOT EXISTS layout_bands (
                id INT AUTO_INCREMENT PRIMARY KEY,
                layout_id INT NOT NULL,
                band_type VARCHAR(50) NOT NULL,
                band_height INT NOT NULL DEFAULT 1,
                band_content JSON,
                band_order INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (layout_id) REFERENCES layout_config(id) ON DELETE CASCADE
            )
        ";
        
        $this->db->query($query);
    }
    
    /**
     * Opret et standard forsidelayout
     */
    private function createDefaultFrontpage() {
        $defaultData = [
            'page_id' => 'forside',
            'layout_data' => [
                'title' => 'LATL.dk - Læder og Laserskæring',
                'meta_description' => 'Håndlavede lædervarer og laserskæring i høj kvalitet fra LATL.dk.'
            ]
        ];
        
        try {
            // Indsæt i databasen
            $dbData = [
                'page_id' => 'forside',
                'layout_data' => json_encode($defaultData['layout_data'])
            ];
            
            $layoutId = $this->db->insert('layout_config', $dbData);
            
            // Opret standard bands til forsiden
            $defaultBands = [
                [
                    'band_type' => 'slideshow',
                    'band_height' => 2,
                    'band_order' => 1,
                    'band_content' => [
                        'slides' => [
                            [
                                'position' => 0,
                                'title' => 'Velkommen til LATL',
                                'subtitle' => 'Din lædervarebutik',
                                'link' => '/produkter',
                                'alt' => 'Velkommen til LATL'
                            ]
                        ]
                    ]
                ],
                [
                    'band_type' => 'product',
                    'band_height' => 1,
                    'band_order' => 2,
                    'band_content' => [
                        'title' => 'Unikke læderprodukter',
                        'subtitle' => 'Håndlavede i Danmark',
                        'link' => '/produkter',
                        'background_color' => '#D6D58E'
                    ]
                ]
            ];
            
            $this->createBands($layoutId, $defaultBands);
            
            // Hent det oprettede layout med bands
            return $this->getById('forside');
        } catch (Exception $e) {
            error_log('Error creating default frontpage: ' . $e->getMessage());
            // Returner standardlayout selvom lagring fejlede
            $defaultData['bands'] = [];
            return ['status' => 'success', 'data' => $defaultData];
        }
    }
    
    /**
     * Opret et tomt layout
     */
    private function createEmptyLayout($pageId) {
        $emptyData = [
            'page_id' => $pageId,
            'layout_data' => []
        ];
        
        try {
            // Indsæt i databasen
            $dbData = [
                'page_id' => $pageId,
                'layout_data' => '{}'
            ];
            
            $layoutId = $this->db->insert('layout_config', $dbData);
            
            // Hent det oprettede layout
            return $this->getById($pageId);
        } catch (Exception $e) {
            error_log('Error creating empty layout: ' . $e->getMessage());
            // Returner tomt layout selvom lagring fejlede
            $emptyData['bands'] = [];
            return ['status' => 'success', 'data' => $emptyData];
        }
    }
}
