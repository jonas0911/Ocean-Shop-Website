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
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('imprint'); ?> - Ocean Hosting</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="Impressum - Ocean Hosting">
    <meta name="author" content="Ocean Hosting">
</head>
<body>
    <!-- Navigation -->
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
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop#configurator"><?php echo t('shop'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop/about"><?php echo t('about'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop/contact"><?php echo t('contact'); ?></a>
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
                            <span class="cart-badge" id="cartBadge"><?php echo $cart->getItemCount(); ?></span>
                        </a>
                    </li>
                    
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><?php echo t('account'); ?></a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><?php echo t('admin'); ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/api/logout"><?php echo t('logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/login"><?php echo t('login'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/register"><?php echo t('register'); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 120px;">
        <h1 class="mb-4"><?php echo t('imprint'); ?></h1>
        
        <div class="row">
            <div class="col-md-8">
                <h3>Angaben gemäß § 5 TMG</h3>
                <p>
                    <strong>Ocean Hosting GmbH</strong><br>
                    Musterstraße 123<br>
                    12345 Musterstadt<br>
                    Deutschland
                </p>
                
                <h4>Vertreten durch:</h4>
                <p>Geschäftsführer: Max Mustermann</p>
                
                <h4>Kontakt:</h4>
                <p>
                    Telefon: +49 (0) 123 456789<br>
                    E-Mail: info@ocean-hosting.com
                </p>
                
                <h4>Registereintrag:</h4>
                <p>
                    Eintragung im Handelsregister<br>
                    Registergericht: Amtsgericht Musterstadt<br>
                    Registernummer: HRB 12345
                </p>
                
                <h4>Umsatzsteuer-ID:</h4>
                <p>
                    Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:<br>
                    DE123456789
                </p>
                
                <h4>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:</h4>
                <p>
                    Max Mustermann<br>
                    Musterstraße 123<br>
                    12345 Musterstadt
                </p>
                
                <h4>Streitschlichtung</h4>
                <p>
                    Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: 
                    <a href="https://ec.europa.eu/consumers/odr/" target="_blank">https://ec.europa.eu/consumers/odr/</a><br>
                    Unsere E-Mail-Adresse finden Sie oben im Impressum.
                </p>
                
                <p class="text-muted mt-4">
                    <small>Bitte passen Sie diese Angaben an Ihre tatsächlichen Unternehmensdaten an!</small>
                </p>
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