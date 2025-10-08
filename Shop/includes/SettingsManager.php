<?php
/**
 * Settings Manager for Ocean Hosting
 * Handles dynamic configuration from database
 */
class SettingsManager {
    private $conn;
    private static $cache = [];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get a setting value
     */
    public function get($key, $default = null) {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $stmt = $this->conn->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $value = $this->castValue($result['setting_value'], $result['setting_type']);
                self::$cache[$key] = $value;
                return $value;
            }

            return $default;
        } catch (Exception $e) {
            error_log("SettingsManager get error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Set a setting value
     */
    public function set($key, $value, $type = 'string', $description = '') {
        try {
            // Convert value to string for storage
            $stringValue = $this->valueToString($value, $type);

            // Check if setting exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                // Update existing setting
                $stmt = $this->conn->prepare("
                    UPDATE settings 
                    SET setting_value = ?, setting_type = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE setting_key = ?
                ");
                $stmt->execute([$stringValue, $type, $description, $key]);
            } else {
                // Insert new setting
                $stmt = $this->conn->prepare("
                    INSERT INTO settings (setting_key, setting_value, setting_type, description) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$key, $stringValue, $type, $description]);
            }

            // Update cache
            self::$cache[$key] = $value;
            return true;

        } catch (Exception $e) {
            error_log("SettingsManager set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all settings
     */
    public function getAll() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM settings ORDER BY setting_key");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                    'type' => $row['setting_type'],
                    'description' => $row['description']
                ];
            }

            return $settings;
        } catch (Exception $e) {
            error_log("SettingsManager getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Pterodactyl configuration
     */
    public function getPterodactylConfig() {
        return [
            'panel_url' => $this->get('pterodactyl_panel_url', 'https://panel.tonne.dev'),
            'api_key' => $this->get('pterodactyl_api_key', ''),
            'default_node_id' => $this->get('pterodactyl_default_node_id', 1),
            'default_disk' => $this->get('pterodactyl_default_disk', 5000),
            'default_cpu' => $this->get('pterodactyl_default_cpu', 100)
        ];
    }

    /**
     * Update Pterodactyl configuration
     */
    public function updatePterodactylConfig($config) {
        $updated = true;
        
        if (isset($config['panel_url'])) {
            $updated &= $this->set('pterodactyl_panel_url', $config['panel_url'], 'string', 'Pterodactyl Panel URL');
        }
        
        if (isset($config['api_key'])) {
            $updated &= $this->set('pterodactyl_api_key', $config['api_key'], 'string', 'Pterodactyl Application API Key');
        }
        
        if (isset($config['default_node_id'])) {
            $updated &= $this->set('pterodactyl_default_node_id', $config['default_node_id'], 'integer', 'Default Pterodactyl Node ID');
        }
        
        if (isset($config['default_disk'])) {
            $updated &= $this->set('pterodactyl_default_disk', $config['default_disk'], 'integer', 'Default Disk Space (MB)');
        }
        
        if (isset($config['default_cpu'])) {
            $updated &= $this->set('pterodactyl_default_cpu', $config['default_cpu'], 'integer', 'Default CPU Limit (%)');
        }

        return $updated;
    }

    /**
     * Cast value based on type
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Convert value to string for storage
     */
    private function valueToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'json':
                return json_encode($value);
            case 'integer':
            case 'float':
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Clear cache
     */
    public static function clearCache() {
        self::$cache = [];
    }
}
?>