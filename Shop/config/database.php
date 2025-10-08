<?php
// Datenbank-Konfiguration
class Database {
    private $db_file;
    private $conn;

    public function __construct() {
        // Use absolute path to database file
        $this->db_file = __DIR__ . '/../database/shop.db';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // Erstelle database Ordner falls nicht vorhanden
            $dir = dirname($this->db_file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
            // Initialisiere Datenbank falls leer
            $this->initializeDatabase();
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
    
    private function initializeDatabase() {
        // Prüfe ob Tabellen existieren
        $result = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($result->fetchColumn() === false) {
            $this->createTables();
            $this->insertDefaultData();
        }
    }
    
    private function createTables() {
        $sql = "
        -- Users Table
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            is_admin INTEGER DEFAULT 0,
            google_id VARCHAR(100) NULL,
            email_verified INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Games Table
        CREATE TABLE games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            min_ram INTEGER NOT NULL DEFAULT 4,
            max_ram INTEGER NOT NULL DEFAULT 10,
            description TEXT,
            active INTEGER DEFAULT 1,
            -- Pterodactyl Integration Fields
            pterodactyl_egg_id INTEGER,
            pterodactyl_docker_image VARCHAR(200),
            pterodactyl_startup_command TEXT,
            pterodactyl_environment TEXT, -- JSON string
            default_port INTEGER DEFAULT 25565,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Orders Table
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            status VARCHAR(20) DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'EUR',
            payment_method VARCHAR(50),
            payment_id VARCHAR(100),
            paypal_order_id VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );

        -- Settings Table for Pterodactyl Configuration
        CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Order Items Table
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            game_id INTEGER,
            game_name VARCHAR(100) NOT NULL,
            ram_amount INTEGER NOT NULL,
            duration VARCHAR(20) NOT NULL,
            price DECIMAL(8,2) NOT NULL,
            quantity INTEGER DEFAULT 1,
            server_ip VARCHAR(45),
            server_port INTEGER,
            server_password VARCHAR(100),
            expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
        );

        -- Cart Table
        CREATE TABLE cart_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            session_id VARCHAR(100),
            game_id INTEGER NOT NULL,
            game_name VARCHAR(100) NOT NULL,
            ram_amount INTEGER NOT NULL,
            duration VARCHAR(20) NOT NULL,
            price DECIMAL(8,2) NOT NULL,
            quantity INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        );

        -- Settings Table
        CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(20) DEFAULT 'string',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        $this->conn->exec($sql);
    }
    
    private function insertDefaultData() {
        // Default Admin User (password: admin123)
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin', 'admin@example.com', $admin_password, 1]);
        
        // Default Games
        $this->conn->exec("
            INSERT INTO games (name, image_url, min_ram, max_ram, description) VALUES 
            ('Minecraft', 'https://via.placeholder.com/64x64/4CAF50/white?text=MC', 4, 10, 'Der beliebteste Sandbox-Baukasten der Welt. Erstelle deine eigene Welt!'),
            ('Rust', 'https://via.placeholder.com/64x64/FF5722/white?text=RUST', 6, 16, 'Survival-Spiel in einer post-apokalyptischen Welt.'),
            ('ARK: Survival Evolved', 'https://via.placeholder.com/64x64/8BC34A/white?text=ARK', 8, 32, 'Überlebe in einer Welt voller Dinosaurier.')
        ");
        
        // Default Settings
        $this->conn->exec("
            INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES 
            ('site_name', 'Ocean Hosting', 'string', 'Website Name'),
            ('paypal_client_id', 'YOUR_PAYPAL_CLIENT_ID', 'string', 'PayPal Client ID'),
            ('paypal_client_secret', 'YOUR_PAYPAL_CLIENT_SECRET', 'string', 'PayPal Client Secret'),
            ('paypal_sandbox', 'true', 'boolean', 'PayPal Sandbox Mode'),
            ('google_client_id', 'YOUR_GOOGLE_CLIENT_ID', 'string', 'Google OAuth Client ID'),
            ('google_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET', 'string', 'Google OAuth Client Secret'),
            ('default_currency', 'EUR', 'string', 'Default Currency'),
            ('company_name', 'Ocean Hosting GmbH', 'string', 'Company Name'),
            ('company_email', 'info@ocean-hosting.com', 'string', 'Company Email'),
            ('pterodactyl_panel_url', 'https://panel.tonne.dev', 'string', 'Pterodactyl Panel URL'),
            ('pterodactyl_api_key', 'ptla_Zzq8wqyewjIbhdMGvydAfdBaybQTyACN1KOYK4lZkGv', 'string', 'Pterodactyl Application API Key'),
            ('pterodactyl_default_node_id', '1', 'integer', 'Default Pterodactyl Node ID'),
            ('pterodactyl_default_disk', '5000', 'integer', 'Default Disk Space (MB)'),
            ('pterodactyl_default_cpu', '100', 'integer', 'Default CPU Limit (%)')
        ");
    }
}

// Site-Konfiguration
define('SITE_URL', 'http://localhost/Shop');
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PAYPAL_CLIENT_SECRET');
define('PAYPAL_SANDBOX', true); // false für Live-Modus

// Google OAuth
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');

// Email-Konfiguration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Session wird von der jeweiligen Anwendung gestartet
?>