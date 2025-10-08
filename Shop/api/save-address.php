<?php
session_start();
require_once __DIR__ . '/../includes/User.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = new User();

if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$firstName = trim($input['firstName'] ?? '');
$lastName = trim($input['lastName'] ?? '');
$address = trim($input['address'] ?? '');
$city = trim($input['city'] ?? '');
$zip = trim($input['zip'] ?? '');
$country = trim($input['country'] ?? 'DE');

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($address) || empty($city) || empty($zip)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Update user address
$result = $user->updateUserAddress($userId, $firstName, $lastName, $address, $city, $zip, $country);

echo json_encode($result);
?>