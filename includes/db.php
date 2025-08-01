<?php
// Tilføj disse funktioner nederst i din db.php fil

function get_db_connection() {
    $db = Database::getInstance();
    return $db->getConnection();
}

function get_global_styles() {
    $db = Database::getInstance();
    $result = $db->selectOne("SELECT color_palette, font_config, global_styles FROM layout_config WHERE page_id = 'global'");
    
    if (!$result) {
        return [
            'color_palette' => [],
            'font_config' => [],
            'global_styles' => ['css' => '']
        ];
    }
    
    return [
        'color_palette' => json_decode($result['color_palette'] ?? '{}', true) ?: [],
        'font_config' => json_decode($result['font_config'] ?? '{}', true) ?: [],
        'global_styles' => json_decode($result['global_styles'] ?? '{}', true) ?: ['css' => '']
    ];
}

function get_page_layout($page_id) {
    $db = Database::getInstance();
    $result = $db->selectOne("SELECT title, meta_description FROM layout_config WHERE page_id = ?", [$page_id]);
    
    if (!$result) {
        return [
            'title' => SITE_NAME,
            'meta_description' => ''
        ];
    }
    
    return $result;
}

function get_page_bands($page_id) {
    $db = Database::getInstance();
    $bands = $db->select("SELECT * FROM layout_bands WHERE page_id = ? ORDER BY band_order ASC", [$page_id]);
    
    foreach ($bands as &$band) {
        $band['band_content'] = json_decode($band['band_content'], true);
    }
    
    return $bands;
}

function save_band($page_id, $band_type, $band_height, $band_content, $band_order, $band_id = null) {
    $db = Database::getInstance();
    
    if ($band_id) {
        // Opdater eksisterende bånd
        $db->update(
            'layout_bands',
            [
                'band_type' => $band_type,
                'band_height' => $band_height,
                'band_content' => json_encode($band_content),
                'band_order' => $band_order
            ],
            'id = ?',
            [$band_id]
        );
        return $band_id;
    } else {
        // Opret nyt bånd
        return $db->insert(
            'layout_bands',
            [
                'page_id' => $page_id,
                'band_type' => $band_type,
                'band_height' => $band_height,
                'band_content' => json_encode($band_content),
                'band_order' => $band_order
            ]
        );
    }
}

function delete_band($band_id) {
    $db = Database::getInstance();
    $db->delete('layout_bands', 'id = ?', [$band_id]);
    return true;
}
?>
