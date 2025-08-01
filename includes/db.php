<?php
// db.php - Database forbindelsesklasse og hjælpefunktioner
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $this->query($sql, array_merge($params, $whereParams));
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
    }
}

// Kompatibilitetsfunktioner til brug med eksisterende kode

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
