<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email und Passwort sind erforderlich']);
        exit;
    }
    
    $result = $user->login($email, $password);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
}
?>