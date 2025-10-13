<?php
/**
 * Security Helper Functions
 * Zusätzliche Sicherheitsfunktionen für Ocean Hosting
 */

class Security {
    
    /**
     * Generiert ein sicheres CSRF Token
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validiert CSRF Token
     */
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Bereinigt Input für sichere Ausgabe
     */
    public static function sanitizeOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validiert E-Mail sicher
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generiert sicheres Passwort
     */
    public static function generateSecurePassword($length = 12) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }
    
    /**
     * Rate Limiting für Login-Versuche
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $attempts = $_SESSION['login_attempts'][$identifier] ?? [];
        $now = time();
        
        // Entferne alte Versuche außerhalb des Zeitfensters
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        $_SESSION['login_attempts'][$identifier] = $attempts;
        
        return count($attempts) < $maxAttempts;
    }
    
    /**
     * Registriert Login-Versuch
     */
    public static function registerLoginAttempt($identifier) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$identifier])) {
            $_SESSION['login_attempts'][$identifier] = [];
        }
        
        $_SESSION['login_attempts'][$identifier][] = time();
    }
    
    /**
     * Loggt Sicherheitsereignisse
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
    }
}
?>