<?php
// Ocean Hosting Router
// Handles URL routing for clean URLs

$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string for routing
$path = trim($request_path, '/');

// Debug: Zeige aktuelle URL
error_log("Router: Requested path: " . $path);

// Ocean Shop Routes
switch ($path) {
    case '':
    case 'index.php':
        // Redirect root to ocean/shop
        header('Location: /ocean/shop');
        exit;
        
    case 'shop':
        // Redirect old shop URL to ocean/shop
        header('Location: /ocean/shop');
        exit;
        
    case 'ocean':
    case 'ocean/':
        // Redirect to ocean/shop
        header('Location: /ocean/shop');
        exit;
        
    case 'ocean/shop':
    case 'ocean/shop/':
        // Main shop page
        require_once 'index.php';
        break;
        
    case 'ocean/shop/login':
        require_once 'pages/login.php';
        break;
        
    case 'ocean/shop/register':
        require_once 'pages/register.php';
        break;
        
    case 'ocean/shop/cart':
        require_once 'pages/cart.php';
        break;
        
    case 'ocean/shop/checkout':
        require_once 'pages/checkout.php';
        break;
        
    case 'ocean/shop/checkout/success':
        require_once 'pages/checkout/success.php';
        break;
        
    case 'ocean/shop/about':
        require_once 'pages/about.php';
        break;
        
    case 'ocean/shop/contact':
        require_once 'pages/contact.php';
        break;
        
    case 'ocean/shop/imprint':
        require_once 'pages/imprint.php';
        break;
        
    case 'ocean/shop/privacy':
        require_once 'pages/privacy.php';
        break;
        
    case 'ocean/shop/terms':
        require_once 'pages/terms.php';
        break;
        
    case 'ocean/shop/account':
        require_once 'pages/account.php';
        break;
        
    case 'ocean/shop/admin':
        require_once 'admin/index.php';
        break;
        
    case 'ocean/shop/admin/games':
        require_once 'admin/games.php';
        break;
        
    case 'ocean/shop/admin/orders':
        require_once 'admin/orders.php';
        break;
        
    case 'ocean/shop/admin/users':
        require_once 'admin/users.php';
        break;
        
    case 'ocean/shop/admin/settings':
        require_once 'admin/settings.php';
        break;
        
    // API Routes
    case 'api/login':
    case 'ocean/shop/api/login':
        require_once 'api/login.php';
        break;
        
    case 'api/register':
    case 'ocean/shop/api/register':
        require_once 'api/register.php';
        break;
        
    case 'api/logout':
    case 'ocean/shop/api/logout':
        require_once 'api/logout.php';
        break;
        
    case 'api/add-to-cart':
    case 'ocean/shop/api/add-to-cart':
        require_once 'api/add-to-cart.php';
        break;
        
    case 'api/remove-from-cart':
    case 'ocean/shop/api/remove-from-cart':
        require_once 'api/remove-from-cart.php';
        break;
        
    case 'api/update-cart-quantity':
    case 'ocean/shop/api/update-cart-quantity':
        require_once 'api/update-cart-quantity.php';
        break;
        
    case 'api/get-cart-count':
    case 'ocean/shop/api/get-cart-count':
        require_once 'api/get-cart-count.php';
        break;
        
    case 'api/change-language':
    case 'ocean/shop/api/change-language':
        require_once 'api/change-language.php';
        break;
        
    case 'direct-eggs-api.php':
    case 'ocean/shop/direct-eggs-api.php':
        header('Content-Type: application/json');
        require_once 'direct-eggs-api.php';
        break;
        
    case 'get-next-port.php':
    case 'ocean/shop/get-next-port.php':
        header('Content-Type: application/json');
        require_once 'get-next-port.php';
        break;
        
    // Static files (CSS, JS, Images)
    default:
        // Handle static files directly
        if (preg_match('/^assets\//', $path) || preg_match('/^ocean\/shop\/assets\//', $path)) {
            // Static file in assets folder
            $actual_path = preg_replace('/^ocean\/shop\//', '', $path);
            $file_path = __DIR__ . '/' . $actual_path;
            if (file_exists($file_path)) {
                // Get MIME type
                $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf' => 'font/ttf'
                ];
                
                if (isset($mime_types[$extension])) {
                    header('Content-Type: ' . $mime_types[$extension]);
                }
                
                readfile($file_path);
                exit;
            }
        }
        
        // Check if it's a static file request in root
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|woff|woff2|ttf|svg)$/', $path)) {
            // Let PHP serve static files normally
            return false;
        }
        
        // If no route matches, redirect to ocean/shop
        header('Location: /ocean/shop');
        exit;
}
?>