<?php
// Complete Ocean Shop Router for /ocean/shop/ structure
// Add this to the beginning of index.php

$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove /ocean/shop prefix and normalize path
$path = str_replace('/ocean/shop/', '', $request_path);
$path = str_replace('/ocean/shop', '', $path);
$path = trim($path, '/');

// Skip routing for main page
if (empty($path) || $path === 'index.php') {
    // This is the main page, continue to normal index.php content
    return;
}

// Route to appropriate pages
switch ($path) {
    // Main pages
    case 'login':
        require_once 'pages/login.php';
        exit;
    case 'register':
        require_once 'pages/register.php';
        exit;
    case 'cart':
        require_once 'pages/cart.php';
        exit;
    case 'checkout':
        require_once 'pages/checkout.php';
        exit;
    case 'checkout/success':
        require_once 'pages/checkout/success.php';
        exit;
    case 'about':
        require_once 'pages/about.php';
        exit;
    case 'contact':
        require_once 'pages/contact.php';
        exit;
    case 'imprint':
        require_once 'pages/imprint.php';
        exit;
    case 'privacy':
        require_once 'pages/privacy.php';
        exit;
    case 'terms':
        require_once 'pages/terms.php';
        exit;
    case 'account':
        require_once 'pages/account.php';
        exit;

    // Admin pages
    case 'admin':
        require_once 'admin/index.php';
        exit;
    case 'admin/games':
        require_once 'admin/games.php';
        exit;
    case 'admin/orders':
        require_once 'admin/orders.php';
        exit;
    case 'admin/users':
        require_once 'admin/users.php';
        exit;
    case 'admin/settings':
        require_once 'admin/settings.php';
        exit;

    // API routes
    case 'api/login':
        require_once 'api/login.php';
        exit;
    case 'api/register':
        require_once 'api/register.php';
        exit;
    case 'api/logout':
        require_once 'api/logout.php';
        exit;
    case 'api/add-to-cart':
        require_once 'api/add-to-cart.php';
        exit;
    case 'api/remove-from-cart':
        require_once 'api/remove-from-cart.php';
        exit;
    case 'api/update-cart-quantity':
        require_once 'api/update-cart-quantity.php';
        exit;
    case 'api/get-cart-count':
        require_once 'api/get-cart-count.php';
        exit;
    case 'api/change-language':
        require_once 'api/change-language.php';
        exit;
    case 'api/process-order':
        require_once 'api/process-order.php';
        exit;
    case 'api/save-address':
        require_once 'api/save-address.php';
        exit;
    case 'api/server-management':
        require_once 'api/server-management.php';
        exit;

    // Special API files
    case 'direct-eggs-api.php':
        header('Content-Type: application/json');
        require_once 'direct-eggs-api.php';
        exit;
    case 'get-next-port.php':
        header('Content-Type: application/json');
        require_once 'get-next-port.php';
        exit;

    default:
        // Unknown path, redirect to main page
        header('Location: /ocean/shop/', true, 302);
        exit;
}
?>