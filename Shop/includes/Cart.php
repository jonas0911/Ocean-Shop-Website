<?php
class Cart {
    private $db;
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addItem($game_id, $game_name, $ram, $duration, $price) {
        $item_id = md5($game_id . '_' . $ram . '_' . $duration);
        
        // Get user ID if logged in, otherwise use session ID
        $user_id = $_SESSION['user_id'] ?? null;
        $session_id = $user_id ? null : session_id();
        
        // Check if item already exists in database
        $sql = "SELECT * FROM cart_items WHERE ";
        $params = [];
        
        if ($user_id) {
            $sql .= "user_id = ? AND game_id = ? AND ram_amount = ? AND duration = ?";
            $params = [$user_id, $game_id, $ram, $duration];
        } else {
            $sql .= "session_id = ? AND game_id = ? AND ram_amount = ? AND duration = ?";
            $params = [session_id(), $game_id, $ram, $duration];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Check if we're at the maximum quantity limit (e.g., 3 servers max)
            $maxQuantity = 3;
            if ($existing['quantity'] >= $maxQuantity) {
                return 'max_quantity_reached';
            }
            
            // Update quantity
            $sql = "UPDATE cart_items SET quantity = quantity + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$existing['id']]);
            
            // Also update session for JavaScript
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity'] += 1;
            }
            
            return 'quantity_updated'; // Return specific code for quantity update
        } else {
            // Insert new item
            $sql = "INSERT INTO cart_items (user_id, session_id, game_id, game_name, ram_amount, duration, price, quantity) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $session_id, $game_id, $game_name, $ram, $duration, $price]);
            
            // Also update session for JavaScript
            $_SESSION['cart'][$item_id] = [
                'game_id' => $game_id,
                'game_name' => $game_name,
                'ram' => $ram,
                'duration' => $duration,
                'price' => $price,
                'quantity' => 1,
                'added_at' => time()
            ];
            
            return 'item_added'; // Return specific code for new item
        }
    }
    
    private function saveToSession() {
        // Force session save
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['cart'] = $_SESSION['cart'];
        session_write_close();
        session_start();
    }

    public function removeItem($item_id) {
        // Remove from database
        $user_id = $_SESSION['user_id'] ?? null;
        $affected_rows = 0;
        
        if ($user_id) {
            $sql = "DELETE FROM cart_items WHERE user_id = ? AND id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $item_id]);
            $affected_rows = $stmt->rowCount();
        } else {
            $sql = "DELETE FROM cart_items WHERE session_id = ? AND id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([session_id(), $item_id]);
            $affected_rows = $stmt->rowCount();
        }
        
        // Also remove from session (if exists)
        if (isset($_SESSION['cart'][$item_id])) {
            unset($_SESSION['cart'][$item_id]);
        }
        
        // Return true if we actually deleted something from database
        return $affected_rows > 0;
    }

    public function getItems() {
        $user_id = $_SESSION['user_id'] ?? null;
        $items = [];
        
        if ($user_id) {
            $sql = "SELECT ci.*, g.image_url FROM cart_items ci 
                    LEFT JOIN games g ON ci.game_id = g.id 
                    WHERE ci.user_id = ? ORDER BY ci.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
        } else {
            $sql = "SELECT ci.*, g.image_url FROM cart_items ci 
                    LEFT JOIN games g ON ci.game_id = g.id 
                    WHERE ci.session_id = ? ORDER BY ci.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([session_id()]);
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item_key = md5($row['game_id'] . '_' . $row['ram_amount'] . '_' . $row['duration']);
            $items[$item_key] = [
                'id' => $row['id'],
                'game_id' => $row['game_id'],
                'game_name' => $row['game_name'],
                'image_url' => $row['image_url'],
                'ram' => $row['ram_amount'],
                'duration' => $row['duration'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'added_at' => strtotime($row['created_at'])
            ];
        }
        
        return $items;
    }

    public function getTotal() {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function getItemCount() {
        return count($this->getItems());
    }

    public function clear() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            $sql = "DELETE FROM cart_items WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
        } else {
            $sql = "DELETE FROM cart_items WHERE session_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([session_id()]);
        }
        
        $_SESSION['cart'] = [];
    }

    public function isEmpty() {
        return count($this->getItems()) === 0;
    }

    public function updateQuantity($item_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($item_id);
        }
        
        // Enforce maximum quantity limit
        $maxQuantity = 3;
        if ($quantity > $maxQuantity) {
            $quantity = $maxQuantity;
        }
        
        $user_id = $_SESSION['user_id'] ?? null;
        $affected_rows = 0;
        
        if ($user_id) {
            $sql = "UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$quantity, $user_id, $item_id]);
            $affected_rows = $stmt->rowCount();
        } else {
            $sql = "UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE session_id = ? AND id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$quantity, session_id(), $item_id]);
            $affected_rows = $stmt->rowCount();
        }
        
        return $affected_rows > 0;
    }
}
?>