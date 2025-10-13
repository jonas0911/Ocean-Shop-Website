<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Security.php';

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
    
    // Rate Limiting prüfen
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit($clientIP)) {
        Security::logSecurityEvent('rate_limit_exceeded', ['email' => $email, 'ip' => $clientIP]);
        echo json_encode(['success' => false, 'message' => 'Zu viele Login-Versuche. Bitte warten Sie 15 Minuten.']);
        exit;
    }
    
    $result = $user->login($email, $password);
    
    // Login-Versuch registrieren (auch bei Erfolg für Statistiken)
    Security::registerLoginAttempt($clientIP);
    
    if (!$result['success']) {
        Security::logSecurityEvent('failed_login', ['email' => $email, 'ip' => $clientIP]);
    } else {
        Security::logSecurityEvent('successful_login', ['email' => $email, 'ip' => $clientIP]);
    }
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
}
?>