<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Cart.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $cart = new Cart();
    $itemCount = $cart->getItemCount();
    
    echo json_encode([
        'success' => true, 
        'count' => $itemCount,
        'message' => 'Cart count retrieved successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'count' => 0,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>