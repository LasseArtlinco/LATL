<?php
// api/products.php - Produkt controller
class ProductsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll() {
        try {
            $result = $this->db->select("SELECT * FROM products ORDER BY created_at DESC");
            
            // Hent relaterede data for hvert produkt
            foreach ($result as &$product) {
                $product['variations'] = $this->getProductVariations($product['id']);
                $product['images'] = $this->getProductImages($product['id']);
            }
            
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $product = $this->db->selectOne("SELECT * FROM products WHERE id = ?", [$id]);
            
            if (!$product) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Product not found'];
            }
            
            // Hent relaterede data
            $product['variations'] = $this->getProductVariations($id);
            $product['images'] = $this->getProductImages($id);
            
            return ['status' => 'success', 'data' => $product];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function create($data) {
        try {
            // Valider input data
            if (!isset($data['name']) || !isset($data['price'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Product name and price are required'];
            }
            
            // Opret produkt
            $productId = $this->db->insert('products', [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => $data['price'],
                'is_configurable' => $data['is_configurable'] ?? false
            ]);
            
            // Håndter variationer hvis de er angivet
            if (isset($data['variations']) && is_array($data['variations'])) {
                foreach ($data['variations'] as $variation) {
                    $this->db->insert('product_variations', [
                        'product_id' => $productId,
                        'variation_name' => $variation['name'],
                        'variation_value' => $variation['value'],
                        'price_adjustment' => $variation['price_adjustment'] ?? 0
                    ]);
                }
            }
            
            // Håndter billeder hvis de er angivet (billedsti skal gemmes fra en separat upload)
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    $this->db->insert('product_images', [
                        'product_id' => $productId,
                        'image_path' => $image['path'],
                        'is_primary' => ($index === 0) // Første billede bliver primært
                    ]);
                }
            }
            
            // Hent det nye produkt med alle relaterede data
            return $this->getById($productId);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Tjek om produktet eksisterer
            $product = $this->db->selectOne("SELECT id FROM products WHERE id = ?", [$id]);
            if (!$product) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Product not found'];
            }
            
            // Opdater produkt data
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['price'])) $updateData['price'] = $data['price'];
            if (isset($data['is_configurable'])) $updateData['is_configurable'] = $data['is_configurable'];
            
            if (!empty($updateData)) {
                $this->db->update('products', $updateData, 'id = ?', [$id]);
            }
            
            // Håndter variationer hvis de er angivet
            if (isset($data['variations'])) {
                // Slet eksisterende variationer
                $this->db->delete('product_variations', 'product_id = ?', [$id]);
                
                // Opret nye variationer
                foreach ($data['variations'] as $variation) {
                    $this->db->insert('product_variations', [
                        'product_id' => $id,
                        'variation_name' => $variation['name'],
                        'variation_value' => $variation['value'],
                        'price_adjustment' => $variation['price_adjustment'] ?? 0
                    ]);
                }
            }
            
            // Håndter billeder hvis de er angivet
            if (isset($data['images'])) {
                // Slet eksisterende billeder fra databasen
                $this->db->delete('product_images', 'product_id = ?', [$id]);
                
                // Tilføj nye billeder
                foreach ($data['images'] as $index => $image) {
                    $this->db->insert('product_images', [
                        'product_id' => $id,
                        'image_path' => $image['path'],
                        'is_primary' => ($index === 0) // Første billede bliver primært
                    ]);
                }
            }
            
            // Hent det opdaterede produkt
            return $this->getById($id);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Tjek om produktet eksisterer
            $product = $this->db->selectOne("SELECT id FROM products WHERE id = ?", [$id]);
            if (!$product) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Product not found'];
            }
            
            // Hent produkt billeder for at kunne slette filerne
            $images = $this->getProductImages($id);
            
            // Slet produkt (cascade vil slette relaterede variationer og billeder i databasen)
            $this->db->delete('products', 'id = ?', [$id]);
            
            // Slet billedfiler (implementer senere)
            // Her skulle vi slette de faktiske billedfiler
            
            return ['status' => 'success', 'message' => 'Product deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    // Hjælpefunktioner
    private function getProductVariations($productId) {
        return $this->db->select(
            "SELECT * FROM product_variations WHERE product_id = ?",
            [$productId]
        );
    }
    
    private function getProductImages($productId) {
        return $this->db->select(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC",
            [$productId]
        );
    }
}