<?php
require_once '../config/database.php';
require_once '../includes/User.php';
require_once '../includes/ServerManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

session_start();

$user = new User();
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$serverManager = new ServerManager();

try {
    switch ($action) {
        case 'create_server':
            $orderId = $input['order_id'] ?? null;
            if (!$orderId) {
                throw new Exception('Order ID required');
            }
            
            $result = $serverManager->createServerFromOrder($orderId, $user->getId());
            echo json_encode($result);
            break;
            
        case 'get_servers':
            $servers = $serverManager->getUserServers($user->getId());
            echo json_encode(['success' => true, 'servers' => $servers]);
            break;
            
        case 'delete_server':
            $serverId = $input['server_id'] ?? null;
            if (!$serverId) {
                throw new Exception('Server ID required');
            }
            
            $result = $serverManager->deleteServer($serverId, $user->getId());
            echo json_encode($result);
            break;
            
        case 'renew_server':
            $serverId = $input['server_id'] ?? null;
            $duration = $input['duration'] ?? null;
            
            if (!$serverId || !$duration) {
                throw new Exception('Server ID and duration required');
            }
            
            $result = $serverManager->renewServer($serverId, $user->getId(), $duration);
            echo json_encode($result);
            break;
            
        case 'server_power':
            $serverId = $input['server_id'] ?? null;
            $powerAction = $input['power_action'] ?? null;
            
            if (!$serverId || !$powerAction) {
                throw new Exception('Server ID and power action required');
            }
            
            $result = $serverManager->serverPowerAction($serverId, $user->getId(), $powerAction);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>