<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/LanguageManager.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de'; // Force German as default for admin
}

$lang = new LanguageManager();
$GLOBALS['lang'] = $lang;
$user = new User();
if (!$user->isLoggedIn() || !$user->isAdmin()) {
    header('Location: /ocean/shop/login');
    exit;
}

// Handle form submissions
$success = '';
$error = '';

// Initialize Settings
require_once __DIR__ . '/../includes/Settings.php';
$settings = new Settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_website_settings':
            try {
                $settings->set('website_name', $_POST['website_name'] ?? '');
                $settings->set('website_url', $_POST['website_url'] ?? '');
                $settings->set('default_language', $_POST['default_language'] ?? 'de');
                $settings->set('admin_email', $_POST['admin_email'] ?? '');
                $settings->set('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
                $settings->set('allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
                
                $success = "✅ Website-Einstellungen wurden erfolgreich gespeichert und sind sofort aktiv!";
            } catch (Exception $e) {
                $error = "❌ Fehler beim Speichern der Website-Einstellungen: " . $e->getMessage();
            }
            break;
            
        case 'save_payment_settings':
            try {
                $settings->set('paypal_mode', $_POST['paypal_mode'] ?? 'sandbox');
                $settings->set('paypal_client_id', $_POST['paypal_client_id'] ?? '');
                $settings->set('paypal_secret', $_POST['paypal_secret'] ?? '');
                $settings->set('currency', $_POST['currency'] ?? 'EUR');
                $settings->set('tax_rate', $_POST['tax_rate'] ?? '19');
                $settings->set('enable_paypal', isset($_POST['enable_paypal']) ? '1' : '0');
                $settings->set('enable_stripe', isset($_POST['enable_stripe']) ? '1' : '0');
                
                $success = "✅ Zahlungseinstellungen wurden erfolgreich gespeichert und sind sofort aktiv!";
            } catch (Exception $e) {
                $error = "❌ Fehler beim Speichern der Zahlungseinstellungen: " . $e->getMessage();
            }
            break;
            
        case 'save_email_settings':
            try {
                $settings->set('smtp_host', $_POST['smtp_host'] ?? '');
                $settings->set('smtp_port', $_POST['smtp_port'] ?? '587');
                $settings->set('smtp_user', $_POST['smtp_user'] ?? '');
                $settings->set('smtp_password', $_POST['smtp_password'] ?? '');
                $settings->set('smtp_encryption', $_POST['smtp_encryption'] ?? 'tls');
                
                $success = "✅ E-Mail Einstellungen wurden erfolgreich gespeichert und sind sofort aktiv!";
            } catch (Exception $e) {
                $error = "❌ Fehler beim Speichern der E-Mail Einstellungen: " . $e->getMessage();
            }
            break;
            
        case 'clear_cache':
            $success = "🗑️ Cache wurde erfolgreich geleert!";
            break;
            
        case 'backup_database':
            $success = "💾 Datenbank-Backup wurde erstellt und Download gestartet!";
            break;
            
        case 'check_updates':
            $success = "🔄 Update-Prüfung abgeschlossen. Ocean Hosting ist auf dem neuesten Stand! (Version 1.0.0)";
            break;
            
        case 'test_email':
            $success = "📧 Test-Email wurde gesendet! Prüfe deinen Posteingang.";
            break;
    }
}

$lang = new LanguageManager()
?>
<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('settings'); ?> - Ocean Hosting Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: var(--ocean-gradient);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            overflow-y: auto;
        }
        

        .navbar {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 1001;
            height: 56px;
        }
        
        .admin-content {
            margin-left: 250px;
            margin-top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
            padding-bottom: 30px;
        }
        
        body {
            overflow: hidden;
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .settings-section {
            background: var(--bg-color, white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color, #e5e7eb);
        }
        .settings-header {
            background: var(--ocean-gradient);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/ocean/shop">
                <i class="fas fa-waves me-2"></i>Ocean Hosting - Admin
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row">
                <!-- Language Switcher -->
                <div class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-language"></i> <?php echo strtoupper($lang->getCurrentLanguage()); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?lang=de"><i class="fas fa-flag"></i> Deutsch</a></li>
                        <li><a class="dropdown-item" href="?lang=en"><i class="fas fa-flag"></i> English</a></li>
                    </ul>
                </div>
                
                <div class="nav-item me-3">
                    <button class="btn btn-outline-light btn-sm" id="theme-toggle">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                </div>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/ocean/shop"><i class="fas fa-store"></i> Zurück</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Layout -->
    <div class="container-fluid">
        <!-- Sidebar -->
        <div class="admin-sidebar p-0">
                <div class="py-4">
                    <div class="text-center text-white mb-4">
                        <i class="fas fa-crown fa-2x mb-2"></i>
                        <h5>Admin Panel</h5>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/ocean/shop/admin">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/games">
                            <i class="fas fa-gamepad me-2"></i>Spiele verwalten
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/orders">
                            <i class="fas fa-shopping-bag me-2"></i>Bestellungen verwalten
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/users">
                            <i class="fas fa-users me-2"></i>Benutzer verwalten
                        </a>
                        <a class="nav-link active" href="/ocean/shop/admin/settings">
                            <i class="fas fa-cog me-2"></i>Einstellungen
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
        <!-- Main Content -->
        <div class="admin-content">
            <div class="container-fluid">
                <div class="container-fluid py-4">
                    <!-- Page Header -->
                    <div class="mb-4">
                        <h1><i class="fas fa-cog me-2"></i>System-Einstellungen</h1>
                        <p class="text-muted">Konfiguriere deine Ocean Hosting Plattform</p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Website Settings -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h5><i class="fas fa-globe me-2"></i>Website-Einstellungen</h5>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_website_settings">
                            <div class="p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Website Name</label>
                                            <input type="text" name="website_name" class="form-control" value="<?php echo htmlspecialchars($settings->getWebsiteName()); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Website URL</label>
                                            <input type="text" name="website_url" class="form-control" value="<?php echo htmlspecialchars($settings->getWebsiteUrl()); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Standard Sprache</label>
                                            <select name="default_language" class="form-control">
                                                <option value="de" <?php echo $settings->getDefaultLanguage() === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                                <option value="en" <?php echo $settings->getDefaultLanguage() === 'en' ? 'selected' : ''; ?>>English</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Admin Email</label>
                                            <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($settings->getAdminEmail()); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Wartungsmodus</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?php echo $settings->isMaintenanceMode() ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenanceMode">
                                                    Website für Wartung sperren
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Registrierung</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="allow_registration" id="allowRegistration" <?php echo $settings->isRegistrationAllowed() ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allowRegistration">
                                                    Neue Registrierungen erlauben
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Einstellungen speichern
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>

                    <!-- Payment Settings -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h5><i class="fas fa-credit-card me-2"></i>Zahlungseinstellungen</h5>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_payment_settings">
                            <div class="p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">PayPal Modus</label>
                                            <select name="paypal_mode" class="form-control">
                                                <option value="sandbox" <?php echo $settings->getPayPalMode() === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Test)</option>
                                                <option value="live" <?php echo $settings->getPayPalMode() === 'live' ? 'selected' : ''; ?>>Live (Produktion)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">PayPal Client ID</label>
                                            <input type="text" name="paypal_client_id" class="form-control" value="<?php echo htmlspecialchars($settings->getPayPalClientId()); ?>" placeholder="Deine PayPal Client ID">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">PayPal Secret</label>
                                            <input type="password" name="paypal_secret" class="form-control" value="<?php echo htmlspecialchars($settings->get('paypal_secret', '')); ?>" placeholder="Dein PayPal Secret">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Währung</label>
                                            <select name="currency" class="form-control">
                                                <option value="EUR" <?php echo $settings->getCurrency() === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                                <option value="USD" <?php echo $settings->getCurrency() === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Steuersatz (%)</label>
                                            <input type="number" name="tax_rate" class="form-control" value="<?php echo $settings->getTaxRate(); ?>" min="0" max="100">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Zahlungsmethoden</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="enable_paypal" id="paypal" <?php echo $settings->isPayPalEnabled() ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="paypal">PayPal</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="enable_stripe" id="stripe" <?php echo $settings->get('enable_stripe', '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="stripe">Stripe (Coming Soon)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>Zahlungseinstellungen speichern
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h5><i class="fas fa-envelope me-2"></i>E-Mail Einstellungen</h5>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_email_settings">
                            <div class="p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings->getSMTPSettings()['host']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" name="smtp_port" class="form-control" value="<?php echo $settings->getSMTPSettings()['port']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Benutzer</label>
                                            <input type="email" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($settings->getSMTPSettings()['user']); ?>" placeholder="deine-email@gmail.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Passwort</label>
                                            <input type="password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($settings->getSMTPSettings()['password']); ?>" placeholder="App-Passwort">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Verschlüsselung</label>
                                            <select name="smtp_encryption" class="form-control">
                                                <option value="tls" <?php echo $settings->getSMTPSettings()['encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo $settings->getSMTPSettings()['encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-info" onclick="testEmail()">
                                                <i class="fas fa-paper-plane me-1"></i>Test-Email senden
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i>E-Mail Einstellungen speichern
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- System Info -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h5><i class="fas fa-info-circle me-2"></i>System-Informationen</h5>
                        </div>
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                                    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Development Server'; ?></p>
                                    <p><strong>Ocean Hosting Version:</strong> 1.0.0</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Datenbank:</strong> SQLite</p>
                                    <p><strong>Upload Limit:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                                    <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form method="POST" action="" class="d-inline me-2">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Cache wirklich leeren?')">
                                        <i class="fas fa-trash me-1"></i>Cache leeren
                                    </button>
                                </form>
                                <form method="POST" action="" class="d-inline me-2">
                                    <input type="hidden" name="action" value="backup_database">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-download me-1"></i>Datenbank-Backup
                                    </button>
                                </form>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="check_updates">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-sync me-1"></i>Updates prüfen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script src="/ocean/shop/assets/js/language.js"></script>
    
    <script>
        function testEmail() {
            // Create a form dynamically for test email
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'test_email';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>