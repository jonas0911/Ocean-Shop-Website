<?php
/**
 * URL Helper Class for Production Environment
 * Handles URL generation without /ocean/shop prefix
 */
class URLHelper {
    private static $baseURL = '';
    
    /**
     * Initialize base URL (auto-detect or set manually)
     */
    public static function init() {
        if (self::$baseURL === '') {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$baseURL = $protocol . '://' . $host;
        }
    }
    
    /**
     * Generate clean URL for pages
     */
    public static function url($path = '') {
        self::init();
        $path = ltrim($path, '/');
        
        // Remove /ocean/shop prefix for production
        $path = preg_replace('#^ocean/shop/?#', '', $path);
        
        return self::$baseURL . '/' . $path;
    }
    
    /**
     * Generate API URL
     */
    public static function api($endpoint) {
        return self::url('api/' . ltrim($endpoint, '/'));
    }
    
    /**
     * Generate admin URL
     */
    public static function admin($path = '') {
        return self::url('admin/' . ltrim($path, '/'));
    }
    
    /**
     * Get current URL
     */
    public static function current() {
        self::init();
        return self::$baseURL . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Check if we're on HTTPS
     */
    public static function isSecure() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * Redirect to clean URL
     */
    public static function redirect($path, $permanent = true) {
        $url = self::url($path);
        $code = $permanent ? 301 : 302;
        header("Location: $url", true, $code);
        exit;
    }
}

/**
 * Helper functions for templates
 */
function url($path = '') {
    return URLHelper::url($path);
}

function api_url($endpoint) {
    return URLHelper::api($endpoint);
}

function admin_url($path = '') {
    return URLHelper::admin($path);
}
?>