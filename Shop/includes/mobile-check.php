<?php
/**
 * Mobile Device Detection
 * Blocks access from mobile devices (smartphones)
 * Allows: Desktop, Laptop, iPad, Tablets
 */

function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Check screen width via JavaScript cookie (set on first visit)
    if (isset($_COOKIE['screen_width'])) {
        $screenWidth = intval($_COOKIE['screen_width']);
        // Block devices with screen width < 768px (typical mobile/small tablet)
        if ($screenWidth > 0 && $screenWidth < 768) {
            return true;
        }
    }
    
    // Fallback: User Agent detection for mobile devices
    $mobileKeywords = [
        'Mobile', 'Android', 'iPhone', 'iPod', 'BlackBerry', 
        'Windows Phone', 'webOS', 'Opera Mini', 'IEMobile'
    ];
    
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            // Allow iPad (it's big enough)
            if (stripos($userAgent, 'iPad') !== false) {
                return false;
            }
            return true;
        }
    }
    
    return false;
}

// Redirect mobile users to blocked page (NO BYPASS)
if (isMobileDevice()) {
    // Get the base path
    $basePath = '/ocean/shop/pages/mobile-blocked.php';
    header("Location: $basePath");
    exit;
}
