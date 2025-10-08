<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Cart.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cart = new Cart();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = $input['item_id'] ?? null;
    
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Item ID fehlt']);
        exit;
    }
    
    try {
        $result = $cart->removeItem($itemId);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Artikel erfolgreich entfernt',
                'cartCount' => $cart->getItemCount()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Artikel nicht gefunden']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Requests erlaubt']);
}
?>