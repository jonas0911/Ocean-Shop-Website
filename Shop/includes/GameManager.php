<?php
require_once __DIR__ . '/../config/database.php';

class GameManager {
    private $conn;
    private $table = 'games';

    public function __construct($pdo = null) {
        if ($pdo !== null) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function getAllGames() {
        try {
            // Prüfe ob Verbindung vorhanden ist
            if ($this->conn === null) {
                return [];
            }
            
            $query = "SELECT * FROM " . $this->table . " WHERE active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("GameManager Error: " . $e->getMessage());
            return [];
        }
    }

    public function getGameById($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }

    public function addGame($name, $image_url, $min_ram, $max_ram, $description = '', $pterodactyl_data = []) {
        try {
            // Check if connection exists
            if ($this->conn === null) {
                throw new Exception("Database connection is null");
            }
            
            // Check if game with same name already exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE name = :name AND active = 1";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':name', $name);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Ein Spiel mit diesem Namen existiert bereits");
            }
            
            $query = "INSERT INTO " . $this->table . " (
                name, image_url, min_ram, max_ram, description, active, created_at,
                pterodactyl_egg_id, pterodactyl_docker_image, pterodactyl_startup_command, 
                pterodactyl_environment, default_port
            ) VALUES (
                :name, :image_url, :min_ram, :max_ram, :description, 1, datetime('now'),
                :egg_id, :docker_image, :startup_command, :environment, :default_port
            )";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':min_ram', $min_ram);
            $stmt->bindParam(':max_ram', $max_ram);
            $stmt->bindParam(':description', $description);
            
            // Pterodactyl parameters
            $stmt->bindParam(':egg_id', $pterodactyl_data['egg_id'] ?? null);
            $stmt->bindParam(':docker_image', $pterodactyl_data['docker_image'] ?? null);
            $stmt->bindParam(':startup_command', $pterodactyl_data['startup_command'] ?? null);
            $stmt->bindParam(':environment', $pterodactyl_data['environment'] ?? null);
            $stmt->bindParam(':default_port', $pterodactyl_data['default_port'] ?? 25565);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("GameManager addGame Error: " . $e->getMessage());
            throw new Exception("Datenbankfehler beim Hinzufügen des Spiels: " . $e->getMessage());
        } catch(Exception $e) {
            error_log("GameManager addGame Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateGame($id, $name, $image_url, $min_ram, $max_ram, $description = '', $pterodactyl_data = []) {
        try {
            // Check if connection exists
            if ($this->conn === null) {
                throw new Exception("Database connection is null");
            }
            
            // Check if game exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = :id AND active = 1";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Spiel nicht gefunden");
            }
            
            $query = "UPDATE " . $this->table . " 
                     SET name = :name, image_url = :image_url, min_ram = :min_ram, 
                         max_ram = :max_ram, description = :description, updated_at = datetime('now'),
                         pterodactyl_egg_id = :egg_id, pterodactyl_docker_image = :docker_image,
                         pterodactyl_startup_command = :startup_command, pterodactyl_environment = :environment,
                         default_port = :default_port
                     WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':min_ram', $min_ram);
            $stmt->bindParam(':max_ram', $max_ram);
            $stmt->bindParam(':description', $description);
            
            // Pterodactyl parameters
            $stmt->bindParam(':egg_id', $pterodactyl_data['egg_id'] ?? null);
            $stmt->bindParam(':docker_image', $pterodactyl_data['docker_image'] ?? null);
            $stmt->bindParam(':startup_command', $pterodactyl_data['startup_command'] ?? null);
            $stmt->bindParam(':environment', $pterodactyl_data['environment'] ?? null);
            $stmt->bindParam(':default_port', $pterodactyl_data['default_port'] ?? 25565);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("GameManager updateGame Error: " . $e->getMessage());
            throw new Exception("Datenbankfehler beim Aktualisieren des Spiels: " . $e->getMessage());
        } catch(Exception $e) {
            error_log("GameManager updateGame Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteGame($id) {
        try {
            // Check if connection exists
            if ($this->conn === null) {
                throw new Exception("Database connection is null");
            }
            
            // Check if game exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = :id AND active = 1";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Spiel nicht gefunden");
            }
            
            // Soft delete - set active to 0
            $query = "UPDATE " . $this->table . " SET active = 0, updated_at = datetime('now') WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("GameManager deleteGame Error: " . $e->getMessage());
            throw new Exception("Datenbankfehler beim Löschen des Spiels: " . $e->getMessage());
        } catch(Exception $e) {
            error_log("GameManager deleteGame Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function calculatePrice($ram, $duration) {
        // Dynamic pricing calculation: Base price + (RAM - 4) * multiplier
        $basePrices = [
            '1_month' => 4.00,
            '1_week' => 2.00,
            '3_days' => 1.20
        ];
        
        $ramMultiplier = [
            '1_month' => 1.00,
            '1_week' => 0.50,
            '3_days' => 0.30
        ];
        
        if (!isset($basePrices[$duration]) || !isset($ramMultiplier[$duration])) {
            return 0.00;
        }
        
        // Validate RAM range (4-50 GB)
        if ($ram < 4 || $ram > 50) {
            return 0.00;
        }
        
        $basePrice = $basePrices[$duration];
        $additionalRAM = $ram - 4; // Base is 4GB
        $additionalCost = $additionalRAM * $ramMultiplier[$duration];
        
        return round($basePrice + $additionalCost, 2);
    }
    
    public function validateRAMForGame($gameId, $ram) {
        $game = $this->getGameById($gameId);
        if (!$game) {
            return false;
        }
        
        $minRAM = $game['min_ram'] ?? 4;
        $maxRAM = $game['max_ram'] ?? 50;
        
        return ($ram >= $minRAM && $ram <= $maxRAM);
    }
}
?>