<?php
// api/global_styles.php - Global styles controller

class GlobalStylesController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getStyles() {
        try {
            // Forsøg at hente global styles fra layout_config tabellen
            $layout = $this->db->selectOne("SELECT * FROM layout_config WHERE page_id = ?", ['global']);
            
            if (!$layout) {
                // Hvis ingen global styles findes, opret en standard
                $defaultStyles = [
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
                        ],
                        'price' => [
                            'font-family' => "'Allerta Stencil', sans-serif",
                            'font-weight' => '400'
                        ],
                        'button' => [
                            'font-family' => "'Open Sans', sans-serif",
                            'font-weight' => '600'
                        ]
                    ],
                    'global_styles' => [
                        'css' => "/* Global styling */\nbody {\n  line-height: 1.6;\n}\n\n.container {\n  max-width: 1200px;\n  margin: 0 auto;\n  padding: 0 15px;\n}\n\n.button {\n  transition: all 0.3s ease;\n}\n\n.button:hover {\n  transform: translateY(-2px);\n  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\n}"
                    ]
                ];
                
                // Opret global styles i databasen
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'layout_data' => json_encode($defaultStyles)
                ]);
                
                return ['status' => 'success', 'data' => $defaultStyles];
            }
            
            // Konverter layout_data fra JSON til array
            if ($layout['layout_data']) {
                $layout['layout_data'] = json_decode($layout['layout_data'], true);
            } else {
                $layout['layout_data'] = [];
            }
            
            return ['status' => 'success', 'data' => $layout['layout_data']];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function updateStyles($data) {
        try {
            if (!$data) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'No data provided'];
            }
            
            // Tjek om global styles allerede eksisterer
            $layout = $this->db->selectOne("SELECT id FROM layout_config WHERE page_id = ?", ['global']);
            
            if (!$layout) {
                // Opret global styles
                $this->db->insert('layout_config', [
                    'page_id' => 'global',
                    'layout_data' => json_encode($data)
                ]);
            } else {
                // Opdater global styles
                $this->db->update('layout_config', [
                    'layout_data' => json_encode($data)
                ], 'page_id = ?', ['global']);
            }
            
            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
