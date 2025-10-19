<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GameManager.php';

try {
    $gameManager = new GameManager();
    $games = $gameManager->getAllGames();
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'count' => count($games)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Spiele',
        'message' => $e->getMessage(),
        'games' => []
    ], JSON_PRETTY_PRINT);
}