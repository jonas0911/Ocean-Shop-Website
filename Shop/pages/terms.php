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
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <!-- CRITICAL: Theme MUST load BEFORE any styling to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('ocean-theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('terms_conditions'); ?> - Ocean Hosting</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="Allgemeine Geschäftsbedingungen - Ocean Hosting">
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
                            <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                        </a>
                    </li>
                    
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> <?php echo t('account'); ?></a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-cog"></i> <?php echo t('admin'); ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo t('menu'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/login"><i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?></a></li>
                                <li><a class="dropdown-item" href="/ocean/shop/register"><i class="fas fa-user-plus"></i> <?php echo t('register'); ?></a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 120px;">
        <h1 class="mb-4"><?php echo t('terms_conditions'); ?></h1>
        
        <div class="row">
            <div class="col-md-10">
                <h3>§ 1 Geltungsbereich</h3>
                <p>Diese Allgemeinen Geschäftsbedingungen gelten für alle Verträge zur Miete von Gameservern, die zwischen der Ocean Hosting GmbH und dem Kunden geschlossen werden.</p>
                
                <h3>§ 2 Vertragsschluss</h3>
                <p>Der Vertrag kommt durch die Bestätigung der Bestellung durch Ocean Hosting GmbH zustande. Die Darstellung der Produkte im Online-Shop stellt kein rechtlich bindendes Angebot dar.</p>
                
                <h3>§ 3 Leistungen</h3>
                <p>Ocean Hosting GmbH stellt dem Kunden Gameserver zur Verfügung. Die technischen Spezifikationen und Laufzeiten ergeben sich aus der jeweiligen Produktbeschreibung.</p>
                
                <h4>3.1 Verfügbarkeit</h4>
                <p>Wir bemühen uns um eine Verfügbarkeit von 99% pro Monat. Wartungsarbeiten können zu temporären Ausfällen führen.</p>
                
                <h4>3.2 Support</h4>
                <p>Der technische Support steht per E-Mail zur Verfügung. Wir bemühen uns, Anfragen innerhalb von 24 Stunden zu beantworten.</p>
                
                <h3>§ 4 Preise und Zahlung</h3>
                <p>Die angegebenen Preise verstehen sich inklusive der gesetzlichen Mehrwertsteuer. Die Zahlung erfolgt im Voraus für den gebuchten Zeitraum.</p>
                
                <h4>4.1 Zahlungsarten</h4>
                <p>Folgende Zahlungsarten werden akzeptiert:</p>
                <ul>
                    <li>PayPal</li>
                    <li>Kreditkarte</li>
                    <li>Bankeinzug (auf Anfrage)</li>
                </ul>
                
                <h3>§ 5 Laufzeit und Kündigung</h3>
                <p>Die Vertragslaufzeit richtet sich nach der gewählten Mietdauer (3 Tage, 1 Woche, 1 Monat). Eine Verlängerung ist möglich.</p>
                
                <h4>5.1 Kündigung</h4>
                <p>Der Vertrag endet automatisch mit Ablauf der Mietdauer. Eine ordentliche Kündigung ist nicht erforderlich.</p>
                
                <h3>§ 6 Widerrufsrecht</h3>
                <p>Verbrauchern steht ein 14-tägiges Widerrufsrecht zu. Bei sofortiger Bereitstellung des Servers erlischt das Widerrufsrecht bei Beginn der Leistungserbringung.</p>
                
                <h3>§ 7 Haftung</h3>
                <p>Ocean Hosting GmbH haftet nur für Schäden, die auf Vorsatz oder grober Fahrlässigkeit beruhen. Die Haftung für leichte Fahrlässigkeit ist ausgeschlossen.</p>
                
                <h3>§ 8 Datenschutz</h3>
                <p>Der Schutz Ihrer personenbezogenen Daten ist uns wichtig. Details entnehmen Sie bitte unserer Datenschutzerklärung.</p>
                
                <h3>§ 9 Schlussbestimmungen</h3>
                <p>Es gilt deutsches Recht. Gerichtsstand ist der Sitz der Ocean Hosting GmbH. Sollten einzelne Bestimmungen unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen unberührt.</p>
                
                <p class="text-muted mt-4">
                    <small>Stand: Oktober 2025</small><br>
                    <small>Dies sind Muster-AGB. Lassen Sie diese von einem Rechtsanwalt prüfen und an Ihr Unternehmen anpassen!</small>
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