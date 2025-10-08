<?php
require_once 'PterodactylAPI.php';

/**
 * Server Management Class
 * Handles server lifecycle and database integration
 */
class ServerManager {
    private $db;
    private $pterodactyl;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->pterodactyl = new PterodactylAPI();
        
        // Create servers table if not exists
        $this->initializeServersTable();
    }

    /**
     * Create servers table
     */
    private function initializeServersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            order_id INTEGER NOT NULL,
            pterodactyl_server_id VARCHAR(36),
            pterodactyl_user_id INTEGER,
            server_name VARCHAR(100) NOT NULL,
            game_type VARCHAR(50) NOT NULL,
            memory INTEGER NOT NULL,
            disk INTEGER DEFAULT 5000,
            cpu INTEGER DEFAULT 100,
            status VARCHAR(20) DEFAULT 'pending',
            ip_address VARCHAR(45),
            port INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            last_renewed_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )";
        
        $this->db->exec($sql);
    }

    /**
     * Process server creation from order
     */
    public function createServerFromOrder($orderId, $userId) {
        try {
            // Get order details with user email
            $stmt = $this->db->prepare("
                SELECT o.*, oi.game_name, oi.ram_amount, oi.duration, u.email as user_email
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ? AND o.user_id = ?
            ");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Order not found');
            }

            // Get or create Pterodactyl user
            $pterodactylUserId = $this->getOrCreatePterodactylUser($userId);

            // Get game configuration
            $gameConfig = $this->pterodactyl->getGameConfig($order['game_name']);
            if (!$gameConfig) {
                throw new Exception('Unsupported game type: ' . $order['game_name']);
            }

            // Get available allocation
            $allocation = $this->getAvailableAllocation();
            if (!$allocation) {
                throw new Exception('No available server ports');
            }

            // Calculate server expiration
            $expiresAt = $this->calculateExpirationDate($order['duration']);

            // Prepare server data mit festen Ocean-Werten
            $serverName = $this->generateServerName($order['game_name'], $userId);
            $serverData = [
                'name' => $serverName,
                'user_email' => $order['user_email'], // Server Owner ist die User Email
                'egg_id' => $gameConfig['egg_id'],
                'docker_image' => $gameConfig['docker_image'],
                'startup_command' => $gameConfig['startup_command'],
                'environment' => $gameConfig['environment'],
                'memory' => $order['ram_amount'] * 1024, // Convert GB to MB - vom User gewählt
                'allocation_id' => $allocation['id']
                // Disk, CPU, Threads werden in PterodactylAPI fest gesetzt:
                // - disk: IMMER 20GB (20480 MB)
                // - cpu: IMMER 0 (unlimited)
                // - threads: IMMER '0-19'
            ];

            // Create server in Pterodactyl
            $response = $this->pterodactyl->createServer($pterodactylUserId, $serverData);

            if (!$response['success']) {
                throw new Exception('Failed to create server in Pterodactyl');
            }

            $pterodactylServerId = $response['data']['attributes']['uuid'];

            // Save server to database
            $stmt = $this->db->prepare("
                INSERT INTO servers (
                    user_id, order_id, pterodactyl_server_id, pterodactyl_user_id,
                    server_name, game_type, memory, disk, cpu, status,
                    ip_address, port, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $orderId,
                $pterodactylServerId,
                $pterodactylUserId,
                $serverName,
                $order['game_name'],
                $order['ram_amount'] * 1024, // Memory vom User gewählt
                20480, // IMMER 20GB Disk Space
                0, // IMMER 0 = Unlimited CPU
                $allocation['ip'],
                $allocation['port'],
                $expiresAt
            ]);

            // Update order status
            $stmt = $this->db->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt->execute([$orderId]);

            return [
                'success' => true,
                'server_id' => $this->db->lastInsertId(),
                'pterodactyl_id' => $pterodactylServerId,
                'ip' => $allocation['ip'],
                'port' => $allocation['port'],
                'expires_at' => $expiresAt
            ];

        } catch (Exception $e) {
            error_log('Server creation failed: ' . $e->getMessage());
            
            // Update order status to failed
            $stmt = $this->db->prepare("UPDATE orders SET status = 'failed' WHERE id = ?");
            $stmt->execute([$orderId]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get or create Pterodactyl user
     */
    private function getOrCreatePterodactylUser($userId) {
        // Get user from database
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

        // Check if user already exists in Pterodactyl
        try {
            $response = $this->pterodactyl->getUserByEmail($user['email']);
            if ($response['success'] && !empty($response['data']['data'])) {
                return $response['data']['data'][0]['attributes']['id'];
            }
        } catch (Exception $e) {
            // User doesn't exist, create new one
        }

        // Create new user in Pterodactyl
        $userData = [
            'email' => $user['email'],
            'username' => strtolower(str_replace(' ', '', $user['name'])) . '_' . $userId,
            'first_name' => $user['first_name'] ?: explode(' ', $user['name'])[0],
            'last_name' => $user['last_name'] ?: (explode(' ', $user['name'])[1] ?? ''),
            'password' => bin2hex(random_bytes(8)) // Generate random password
        ];

        $response = $this->pterodactyl->createUser($userData);
        if (!$response['success']) {
            throw new Exception('Failed to create Pterodactyl user');
        }

        return $response['data']['attributes']['id'];
    }

    /**
     * Get available allocation (simplified - should be more sophisticated)
     */
    private function getAvailableAllocation() {
        try {
            $nodes = $this->pterodactyl->getNodes();
            if (!$nodes['success'] || empty($nodes['data']['data'])) {
                throw new Exception('No nodes available');
            }

            $nodeId = $nodes['data']['data'][0]['attributes']['id'];
            $allocations = $this->pterodactyl->getNodeAllocations($nodeId);

            if (!$allocations['success'] || empty($allocations['data']['data'])) {
                throw new Exception('No allocations available');
            }

            // Find first available allocation
            foreach ($allocations['data']['data'] as $allocation) {
                if (!$allocation['attributes']['assigned']) {
                    return [
                        'id' => $allocation['attributes']['id'],
                        'ip' => $allocation['attributes']['ip'],
                        'port' => $allocation['attributes']['port']
                    ];
                }
            }

            throw new Exception('No free allocations available');
        } catch (Exception $e) {
            throw new Exception('Failed to get allocation: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique server name
     */
    private function generateServerName($gameType, $userId) {
        $gameNames = [
            'minecraft' => 'MC',
            'rust' => 'RUST',
            'ark' => 'ARK'
        ];
        
        $prefix = $gameNames[strtolower($gameType)] ?? 'SRV';
        return $prefix . '-Ocean-' . $userId . '-' . time();
    }

    /**
     * Calculate expiration date
     */
    private function calculateExpirationDate($duration) {
        $days = [
            '3_days' => 3,
            '1_week' => 7,
            '1_month' => 30
        ];

        $daysToAdd = $days[$duration] ?? 30;
        return date('Y-m-d H:i:s', strtotime("+{$daysToAdd} days"));
    }

    /**
     * Get user servers
     */
    public function getUserServers($userId) {
        $stmt = $this->db->prepare("
            SELECT s.*, o.total_amount 
            FROM servers s 
            LEFT JOIN orders o ON s.order_id = o.id 
            WHERE s.user_id = ? 
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete server
     */
    public function deleteServer($serverId, $userId) {
        try {
            // Get server details
            $stmt = $this->db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ?");
            $stmt->execute([$serverId, $userId]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$server) {
                throw new Exception('Server not found');
            }

            // Delete from Pterodactyl
            if ($server['pterodactyl_server_id']) {
                $response = $this->pterodactyl->deleteServer($server['pterodactyl_server_id'], true);
                // Continue even if Pterodactyl deletion fails
            }

            // Update database
            $stmt = $this->db->prepare("UPDATE servers SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$serverId]);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Renew server
     */
    public function renewServer($serverId, $userId, $duration) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ?");
            $stmt->execute([$serverId, $userId]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$server) {
                throw new Exception('Server not found');
            }

            $newExpiration = $this->calculateExpirationDate($duration);
            
            $stmt = $this->db->prepare("
                UPDATE servers 
                SET expires_at = ?, last_renewed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$newExpiration, $serverId]);

            return ['success' => true, 'new_expiration' => $newExpiration];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Server power actions (start, stop, restart)
     */
    public function serverPowerAction($serverId, $userId, $action) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ?");
            $stmt->execute([$serverId, $userId]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$server) {
                throw new Exception('Server not found');
            }

            if (!$server['pterodactyl_server_id']) {
                throw new Exception('Server not yet provisioned');
            }

            // Execute power action via Pterodactyl API
            switch (strtolower($action)) {
                case 'start':
                    $result = $this->pterodactyl->startServer($server['pterodactyl_server_id']);
                    break;
                case 'stop':
                    $result = $this->pterodactyl->stopServer($server['pterodactyl_server_id']);
                    break;
                case 'restart':
                    $result = $this->pterodactyl->restartServer($server['pterodactyl_server_id']);
                    break;
                default:
                    throw new Exception('Invalid power action');
            }

            if (!$result['success']) {
                throw new Exception('Failed to execute power action');
            }

            return ['success' => true, 'message' => ucfirst($action) . ' command sent successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>