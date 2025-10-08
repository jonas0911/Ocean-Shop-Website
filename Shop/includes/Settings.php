<?php
require_once __DIR__ . '/../config/database.php';

class Settings {
    private $conn;
    private $cache = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Create settings table if not exists
        $this->conn->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function get($key, $default = '') {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $value = $result ? $result['setting_value'] : $default;
            $this->cache[$key] = $value; // Cache the result
            
            return $value;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function set($key, $value) {
        try {
            $stmt = $this->conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, datetime('now'))");
            $success = $stmt->execute([$key, $value]);
            
            if ($success) {
                $this->cache[$key] = $value; // Update cache
            }
            
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Specific setting getters with proper type casting
    public function isMaintenanceMode() {
        return $this->get('maintenance_mode', '0') === '1';
    }

    public function isRegistrationAllowed() {
        return $this->get('allow_registration', '1') === '1';
    }

    public function getWebsiteName() {
        return $this->get('website_name', 'Ocean Hosting');
    }

    public function getWebsiteUrl() {
        return $this->get('website_url', 'http://localhost:8000/ocean/shop');
    }

    public function getAdminEmail() {
        return $this->get('admin_email', 'admin@oceanhosting.com');
    }

    public function getDefaultLanguage() {
        return $this->get('default_language', 'de');
    }

    public function getCurrency() {
        return $this->get('currency', 'EUR');
    }

    public function getTaxRate() {
        return floatval($this->get('tax_rate', '19'));
    }

    public function isPayPalEnabled() {
        return $this->get('enable_paypal', '1') === '1';
    }

    public function getPayPalMode() {
        return $this->get('paypal_mode', 'sandbox');
    }

    public function getPayPalClientId() {
        return $this->get('paypal_client_id', '');
    }

    public function getSMTPSettings() {
        return [
            'host' => $this->get('smtp_host', 'smtp.gmail.com'),
            'port' => intval($this->get('smtp_port', '587')),
            'user' => $this->get('smtp_user', ''),
            'password' => $this->get('smtp_password', ''),
            'encryption' => $this->get('smtp_encryption', 'tls')
        ];
    }
}
?>