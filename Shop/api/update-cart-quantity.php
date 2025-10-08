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
    $quantity = $input['quantity'] ?? null;
    
    if (!$itemId || !$quantity) {
        echo json_encode(['success' => false, 'message' => 'Item ID oder Menge fehlt']);
        exit;
    }
    
    if ($quantity < 1 || $quantity > 10) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Menge (1-10 erlaubt)']);
        exit;
    }
    
    try {
        $result = $cart->updateQuantity($itemId, $quantity);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Menge erfolgreich aktualisiert',
                'cartCount' => $cart->getItemCount(),
                'cartTotal' => $cart->getTotal()
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