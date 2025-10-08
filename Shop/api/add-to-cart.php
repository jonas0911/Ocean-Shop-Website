<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GameManager.php';
require_once __DIR__ . '/../includes/Cart.php';

$gameManager = new GameManager();
$cart = new Cart();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $game_id = $input['game_id'] ?? 0;
    $game_name = $input['game_name'] ?? '';
    $ram = $input['ram'] ?? 4;
    $duration = $input['duration'] ?? '1_month';
    
    // Validate input
    if (empty($game_id) || empty($game_name)) {
        echo json_encode(['success' => false, 'message' => 'Spiel-Informationen fehlen']);
        exit;
    }
    
    // Validate RAM for specific game
    if (!$gameManager->validateRAMForGame($game_id, $ram)) {
        $game = $gameManager->getGameById($game_id);
        $minRAM = $game['min_ram'] ?? 4;
        $maxRAM = $game['max_ram'] ?? 50;
        echo json_encode(['success' => false, 'message' => "Ungültige RAM-Menge für {$game_name}. Erlaubt: {$minRAM}-{$maxRAM} GB"]);
        exit;
    }
    
    if (!in_array($duration, ['1_month', '1_week', '3_days'])) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Laufzeit']);
        exit;
    }
    
    // Calculate price
    $price = $gameManager->calculatePrice($ram, $duration);
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Preis konnte nicht berechnet werden']);
        exit;
    }
    
    // Add to cart
    $result = $cart->addItem($game_id, $game_name, $ram, $duration, $price);
    
    if ($result === 'item_added') {
        echo json_encode([
            'success' => true, 
            'message' => 'Server wurde zum Warenkorb hinzugefügt',
            'cart_count' => $cart->getItemCount(),
            'cart_total' => $cart->getTotal()
        ]);
    } elseif ($result === 'quantity_updated') {
        echo json_encode([
            'success' => true, 
            'message' => 'Anzahl wurde im Warenkorb erhöht.',
            'cart_count' => $cart->getItemCount(),
            'cart_total' => $cart->getTotal()
        ]);
    } elseif ($result === 'max_quantity_reached') {
        echo json_encode([
            'success' => false, 
            'message' => 'Maximale Anzahl (3) für diesen Server bereits erreicht.',
            'cart_count' => $cart->getItemCount(),
            'cart_total' => $cart->getTotal()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Hinzufügen zum Warenkorb']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
}
?>