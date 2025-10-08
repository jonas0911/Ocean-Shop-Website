<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';

$user = new User();
$result = $user->logout();

if (isset($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
} else {
    $redirect = '../index.php';
}

header('Location: ' . $redirect . '?message=logged_out');
exit;
?>