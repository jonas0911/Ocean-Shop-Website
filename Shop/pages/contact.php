<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Cart.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de';
}

$lang = new LanguageManager();
$user = new User();
$cart = new Cart();

$message = '';
$messageType = '';

if ($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $messageText = $_POST['message'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($messageText)) {
        // Hier würde normalerweise eine E-Mail gesendet werden
        $message = 'Vielen Dank für Ihre Nachricht! Wir werden uns in Kürze bei Ihnen melden.';
        $messageType = 'success';
    } else {
        $message = 'Bitte füllen Sie alle Felder aus.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('contact'); ?> - Ocean Hosting</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            overflow: hidden;
        }
        .container {
            max-height: calc(100vh - 80px);
            overflow: hidden;
        }
        
        /* Light Mode Card Styles - Ocean Blue mit runden Ecken */
        [data-theme="light"] .card {
            background: linear-gradient(135deg, #2A7BC4, #1f5a94) !important;
            color: white !important;
            border: none !important;
            border-radius: 20px !important;
        }
        [data-theme="light"] .card-body {
            color: white !important;
        }
        [data-theme="light"] .card h3,
        [data-theme="light"] .card h4,
        [data-theme="light"] .card h5,
        [data-theme="light"] .card p,
        [data-theme="light"] .card label {
            color: white !important;
        }
        [data-theme="light"] .form-control {
            background-color: rgba(255,255,255,0.9) !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            color: #333 !important;
        }
        [data-theme="light"] .form-control:focus {
            background-color: white !important;
            border-color: #3a8bd4 !important;
            box-shadow: 0 0 0 0.2rem rgba(42, 123, 196, 0.25) !important;
        }
        
        /* Dark Mode Card Styles */
        [data-theme="dark"] .card {
            background-color: var(--bg-secondary) !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
            border-radius: 20px !important;
        }
        [data-theme="dark"] .card-body {
            background-color: var(--bg-secondary) !important;
            color: var(--text-color) !important;
        }
        [data-theme="dark"] .card h3,
        [data-theme="dark"] .card h4,
        [data-theme="dark"] .card h5,
        [data-theme="dark"] .card p,
        [data-theme="dark"] .card label {
            color: var(--text-color) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/ocean/shop">
                <i class="fas fa-waves me-2"></i>Ocean Hosting
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop"><?php echo t('home'); ?></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="/ocean/shop#configurator" id="shopDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo t('shop'); ?>
                        </a>
                        <ul class="dropdown-menu shop-dropdown" aria-labelledby="shopDropdown">
                            <li><a class="dropdown-item" href="/ocean/shop#configurator">
                                <i class="fas fa-gamepad me-2"></i><?php echo t('gameserver_hosting'); ?>
                            </a></li>
                            <li><a class="dropdown-item" href="/ocean/cloud">
                                <i class="fas fa-cloud me-2"></i><?php echo t('ocean_cloud'); ?>
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop/about"><?php echo t('about'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/ocean/shop/contact"><?php echo t('contact'); ?></a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-language"></i> <?php echo strtoupper($lang->getCurrentLanguage()); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=de"><i class="fas fa-flag"></i> Deutsch</a></li>
                            <li><a class="dropdown-item" href="?lang=en"><i class="fas fa-flag"></i> English</a></li>
                        </ul>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item me-3">
                        <button class="btn btn-outline-light btn-sm" id="theme-toggle">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </button>
                    </li>
                    
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link cart-icon" href="/ocean/shop/cart">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                        </a>
                    </li>
                    
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> Konto</a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-cog"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> Menü
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/login"><i class="fas fa-sign-in-alt"></i> Anmelden</a></li>
                                <li><a class="dropdown-item" href="/ocean/shop/register"><i class="fas fa-user-plus"></i> Registrieren</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pb-5" style="margin-top: 120px;">
        <h1 class="mb-4"><?php echo t('contact'); ?></h1>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h3>Kontaktformular</h3>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-Mail</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Betreff</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Nachricht</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-gaming">
                                <i class="fas fa-paper-plane me-2"></i>Nachricht senden
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Kontaktinformationen</h5>
                        <hr>
                        
                        <div class="mb-3">
                            <i class="fas fa-building text-primary me-2"></i>
                            <strong>Ocean Hosting GmbH</strong><br>
                            <small class="text-muted">Musterstraße 123<br>12345 Musterstadt</small>
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <strong>Telefon</strong><br>
                            <small class="text-muted">+49 (0) 123 456789</small>
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <strong>E-Mail</strong><br>
                            <small class="text-muted">info@ocean-hosting.com</small>
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <strong>Support-Zeiten</strong><br>
                            <small class="text-muted">Mo-Fr: 9:00 - 18:00 Uhr<br>Sa-So: 10:00 - 16:00 Uhr</small>
                        </div>
                        
                        <hr>
                        <h6>Folgen Sie uns</h6>
                        <div class="d-flex gap-2">
                            <a href="https://discord.gg/PSbV2N4eTe" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fab fa-discord"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-primary"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-primary"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/language.js"></script>
    <script src="/assets/js/shop.js"></script>
</body>
</html>