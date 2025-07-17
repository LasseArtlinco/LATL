<?php
// api/orders.php - Ordre controller
class OrdersController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAll() {
        try {
            $result = $this->db->select("SELECT * FROM orders ORDER BY created_at DESC");
            
            // Hent ordrelinjer for hver ordre
            foreach ($result as &$order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }
            
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $order = $this->db->selectOne("SELECT * FROM orders WHERE id = ?", [$id]);
            
            if (!$order) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Order not found'];
            }
            
            // Hent ordrelinjer
            $order['items'] = $this->getOrderItems($id);
            
            return ['status' => 'success', 'data' => $order];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function create($data) {
        try {
            // Valider input data
            if (!isset($data['customer_name']) || !isset($data['customer_email']) || !isset($data['items']) || empty($data['items'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Customer information and order items are required'];
            }
            
            // Generer ordrenummer
            $orderNumber = $this->generateOrderNumber();
            
            // Beregn total beløb
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            
            // Opret ordre
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'shipping_method' => $data['shipping_method'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            // Opret ordrelinjer
            foreach ($data['items'] as $item) {
                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'variations' => isset($item['variations']) ? json_encode($item['variations']) : null
                ]);
            }
            
            // Hent den oprettede ordre med alle relaterede data
            return $this->getById($orderId);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Tjek om ordren eksisterer
            $order = $this->db->selectOne("SELECT id FROM orders WHERE id = ?", [$id]);
            if (!$order) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Order not found'];
            }
            
            // Opdater ordre data
            $updateData = [];
            if (isset($data['customer_name'])) $updateData['customer_name'] = $data['customer_name'];
            if (isset($data['customer_email'])) $updateData['customer_email'] = $data['customer_email'];
            if (isset($data['customer_phone'])) $updateData['customer_phone'] = $data['customer_phone'];
            if (isset($data['shipping_address'])) $updateData['shipping_address'] = $data['shipping_address'];
            if (isset($data['billing_address'])) $updateData['billing_address'] = $data['billing_address'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['payment_method'])) $updateData['payment_method'] = $data['payment_method'];
            if (isset($data['payment_id'])) $updateData['payment_id'] = $data['payment_id'];
            if (isset($data['shipping_method'])) $updateData['shipping_method'] = $data['shipping_method'];
            if (isset($data['shipping_id'])) $updateData['shipping_id'] = $data['shipping_id'];
            if (isset($data['notes'])) $updateData['notes'] = $data['notes'];
            
            if (!empty($updateData)) {
                $this->db->update('orders', $updateData, 'id = ?', [$id]);
            }
            
            // Opdater ordrelinjer hvis de er angivet
            if (isset($data['items'])) {
                // Slet eksisterende ordrelinjer
                $this->db->delete('order_items', 'order_id = ?', [$id]);
                
                // Beregn nyt total beløb
                $totalAmount = 0;
                foreach ($data['items'] as $item) {
                    $totalAmount += $item['price'] * $item['quantity'];
                }
                
                // Opdater total beløb
                $this->db->update('orders', ['total_amount' => $totalAmount], 'id = ?', [$id]);
                
                // Opret nye ordrelinjer
                foreach ($data['items'] as $item) {
                    $this->db->insert('order_items', [
                        'order_id' => $id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'variations' => isset($item['variations']) ? json_encode($item['variations']) : null
                    ]);
                }
            }
            
            // Hent den opdaterede ordre
            return $this->getById($id);
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Tjek om ordren eksisterer
            $order = $this->db->selectOne("SELECT id FROM orders WHERE id = ?", [$id]);
            if (!$order) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Order not found'];
            }
            
            // Slet ordrelinjer
            $this->db->delete('order_items', 'order_id = ?', [$id]);
            
            // Slet ordre
            $this->db->delete('orders', 'id = ?', [$id]);
            
            return ['status' => 'success', 'message' => 'Order deleted successfully'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    // Hjælpefunktioner
    private function getOrderItems($orderId) {
        return $this->db->select(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId]
        );
    }
    
    private function generateOrderNumber() {
        // Format: LATL-ÅÅÅÅ-MMDD-XXXX hvor X er et tilfældigt tal
        $date = new DateTime();
        $prefix = 'LATL-' . $date->format('Y-md');
        $randomPart = sprintf('%04d', mt_rand(1, 9999));
        return $prefix . '-' . $randomPart;
    }
}