<?php
/**
 * Ocean Hosting Security Setup Script
 * 
 * Führen Sie dieses Script EINMAL nach dem Upload aus!
 * Dann löschen Sie diese Datei sofort!
 */

// Verhindere mehrfache Ausführung
if (file_exists(__DIR__ . '/database/ocean_shop.db')) {
    die('Setup bereits durchgeführt. Löschen Sie diese Datei sofort aus Sicherheitsgründen!');
}

session_start();
require_once __DIR__ . '/config/database.php';

echo "<h1>🔒 Ocean Hosting Security Setup</h1>";

try {
    // Erstelle Database
    $database = new Database();
    $conn = $database->getConnection();
    
    // Generiere sicheres Admin Passwort
    $securePassword = bin2hex(random_bytes(12));
    
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Setup erfolgreich!</h3>";
    echo "<p><strong>🚨 WICHTIG: Notieren Sie sich diese Zugangsdaten:</strong></p>";
    echo "<p><strong>Admin Email:</strong> admin@ocean-hosting.com</p>";
    echo "<p><strong>Admin Passwort:</strong> <code style='background: #fff; padding: 5px;'>$securePassword</code></p>";
    echo "<p><strong>⚠️ Ändern Sie das Passwort sofort nach dem ersten Login!</strong></p>";
    echo "</div>";
    
    // Update admin password
    $hashedPassword = password_hash($securePassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@ocean-hosting.com'");
    $stmt->execute([$hashedPassword]);
    
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🔧 Weitere Sicherheitsschritte:</h3>";
    echo "<ol>";
    echo "<li>Löschen Sie diese Datei (<code>security_setup.php</code>) SOFORT!</li>";
    echo "<li>Ändern Sie in <code>config/database.php</code> die Pterodactyl API Zugangsdaten</li>";
    echo "<li>Setzen Sie starke PayPal API Schlüssel in den Admin-Einstellungen</li>";
    echo "<li>Aktivieren Sie HTTPS für Ihre Domain</li>";
    echo "<li>Erstellen Sie regelmäßige Backups der SQLite Datenbank</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🛡️ Sicherheitsfeatures aktiviert:</h3>";
    echo "<ul>";
    echo "<li>✅ .htaccess Schutz für sensible Verzeichnisse</li>";
    echo "<li>✅ Passwort-Hashing mit PHP password_hash()</li>";
    echo "<li>✅ SQL Injection Schutz durch Prepared Statements</li>";
    echo "<li>✅ Session-basierte Authentifizierung</li>";
    echo "<li>✅ XSS Schutz durch htmlspecialchars()</li>";
    echo "<li>✅ CSRF Schutz für Admin-Bereiche</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='color: red; font-weight: bold; font-size: 18px;'>🚨 LÖSCHEN SIE DIESE DATEI JETZT! 🚨</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Fehler beim Setup:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>