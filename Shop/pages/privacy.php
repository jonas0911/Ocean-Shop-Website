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
    <title><?php echo t('privacy_policy'); ?> - Ocean Hosting</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="Datenschutzerklärung - Ocean Hosting">
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
        <h1 class="mb-4"><?php echo t('privacy_policy'); ?></h1>
        
        <div class="row">
            <div class="col-md-10">
                <h3>1. Datenschutz auf einen Blick</h3>
                <h4>Allgemeine Hinweise</h4>
                <p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.</p>
                
                <h4>Datenerfassung auf dieser Website</h4>
                <p><strong>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</strong></p>
                <p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Impressum dieser Website entnehmen.</p>
                
                <h3>2. Hosting</h3>
                <p>Wir hosten die Inhalte unserer Website bei folgendem Anbieter:</p>
                <p>Externe Hosting-Provider verarbeiten Ihre Daten auf Grundlage unserer berechtigten Interessen nach Art. 6 Abs. 1 lit. f DSGVO.</p>
                
                <h3>3. Allgemeine Hinweise und Pflichtinformationen</h3>
                <h4>Datenschutz</h4>
                <p>Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre personenbezogenen Daten vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften sowie dieser Datenschutzerklärung.</p>
                
                <h4>Cookies</h4>
                <p>Diese Website verwendet Cookies. Cookies sind Textdateien, die in Ihrem Browser gespeichert werden und eine Analyse der Nutzung der Website durch Sie ermöglichen.</p>
                
                <h3>4. Datenerfassung auf dieser Website</h3>
                <h4>Server-Log-Dateien</h4>
                <p>Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, die Ihr Browser automatisch an uns übermittelt.</p>
                
                <h4>Kontaktformular</h4>
                <p>Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert.</p>
                
                <h4>Registrierung auf dieser Website</h4>
                <p>Sie können sich auf dieser Website registrieren, um zusätzliche Funktionen zu nutzen. Die dazu eingegebenen Daten verwenden wir nur zum Zwecke der Nutzung des jeweiligen Angebotes oder Dienstes, für den Sie sich registriert haben.</p>
                
                <h3>5. Zahlungsdienstleister</h3>
                <h4>PayPal</h4>
                <p>Auf dieser Website bieten wir u.a. die Bezahlung via PayPal an. Anbieter dieses Zahlungsdienstes ist die PayPal (Europe) S.à.r.l. et Cie, S.C.A., 22-24 Boulevard Royal, L-2449 Luxembourg.</p>
                
                <h3>6. Ihre Rechte</h3>
                <p>Sie haben jederzeit das Recht:</p>
                <ul>
                    <li>unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten zu erhalten</li>
                    <li>Berichtigung oder Löschung dieser Daten zu verlangen</li>
                    <li>eine Einschränkung der Datenverarbeitung zu verlangen</li>
                    <li>der Datenverarbeitung zu widersprechen</li>
                    <li>Datenübertragbarkeit zu verlangen</li>
                </ul>
                
                <p class="text-muted mt-4">
                    <small>Dies ist eine Musterdatenschutzerklärung. Bitte lassen Sie diese von einem Rechtsanwalt prüfen und an Ihr Unternehmen anpassen!</small>
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