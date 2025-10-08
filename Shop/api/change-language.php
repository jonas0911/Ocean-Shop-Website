<?php
session_start();
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $language = $input['language'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $language = $_GET['lang'] ?? '';
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST and GET allowed']);
    exit;
}

if (in_array($language, ['de', 'en'])) {
    $_SESSION['language'] = $language;
    error_log("API: Language changed to: " . $language . " (Session ID: " . session_id() . ")");
    echo json_encode([
        'success' => true, 
        'language' => $language,
        'session_id' => session_id(),
        'message' => 'Language successfully changed to ' . $language
    ]);
} else {
    error_log("API: Invalid language requested: " . $language);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid language: ' . $language,
        'valid_languages' => ['de', 'en']
    ]);
}
?>