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
    <title><?php echo t('about'); ?> - Ocean Hosting</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        /* Light Mode Card Styles - Verschiedene Ocean Blue Töne */
        
        /* Haupt-Welcome Card - Helleres Ocean Blue */
        [data-theme="light"] .card:nth-of-type(1) {
            background: linear-gradient(135deg, #3a8bd4, #2A7BC4) !important;
            color: white !important;
            border: none !important;
            border-radius: 20px !important;
        }
        
        /* Feature Cards (Premium Hardware, DDoS, Support) - Mittleres Ocean Blue */
        [data-theme="light"] .card.h-100 {
            background: linear-gradient(135deg, #2A7BC4, #1f5a94) !important;
            color: white !important;
            border: none !important;
            border-radius: 20px !important;
        }
        
        /* Statistik Card - Dunkles Ocean Blue */
        [data-theme="light"] .card.bg-primary {
            background: linear-gradient(135deg, #1f5a94, #164570) !important;
            color: white !important;
            border-radius: 20px !important;
        }
        
        /* Technologie Card - Medium Ocean Blue */
        [data-theme="light"] .card.mt-5 {
            background: linear-gradient(135deg, #2570b8, #1f5a94) !important;
            color: white !important;
            border: none !important;
            border-radius: 20px !important;
        }
        
        /* Allgemeine Card Body Styles */
        [data-theme="light"] .card-body {
            color: white !important;
        }
        [data-theme="light"] .card h3,
        [data-theme="light"] .card h4,
        [data-theme="light"] .card h5,
        [data-theme="light"] .card p {
            color: white !important;
        }
        
        /* Light Mode Accordion Styles - Dunkelster Ocean Blue Ton */
        [data-theme="light"] .accordion-item {
            background: linear-gradient(135deg, #164570, #0f2f4d) !important;
            border-color: #0f2f4d !important;
            border-radius: 15px !important;
            margin-bottom: 10px !important;
        }
        [data-theme="light"] .accordion-button {
            background: linear-gradient(135deg, #164570, #0f2f4d) !important;
            color: white !important;
            border-color: #0f2f4d !important;
        }
        [data-theme="light"] .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #0f2f4d, #0a1f33) !important;
            color: white !important;
        }
        [data-theme="light"] .accordion-body {
            background: linear-gradient(135deg, #164570, #0f2f4d) !important;
            color: white !important;
        }
        [data-theme="light"] .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(42, 123, 196, 0.25) !important;
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
        [data-theme="dark"] .card.bg-primary {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-primary)) !important;
            color: var(--text-color) !important;
            border-radius: 20px !important;
        }
        [data-theme="dark"] .card h3,
        [data-theme="dark"] .card h4,
        [data-theme="dark"] .card h5,
        [data-theme="dark"] .card p {
            color: var(--text-color) !important;
        }
        
        /* Dark Mode Accordion Styles */
        [data-theme="dark"] .accordion-item {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            border-radius: 15px !important;
            margin-bottom: 10px !important;
        }
        [data-theme="dark"] .accordion-button {
            background-color: var(--bg-secondary);
            color: var(--text-color);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .accordion-button:not(.collapsed) {
            background-color: var(--bg-primary);
            color: var(--text-color);
        }
        [data-theme="dark"] .accordion-body {
            background-color: var(--bg-secondary);
            color: var(--text-color);
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
                        <a class="nav-link active" href="/ocean/shop/about"><?php echo t('about'); ?></a>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="text-center mb-5"><?php echo t('about'); ?></h1>
                
                <div class="card mb-5">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4"><?php echo t('welcome_ocean_hosting'); ?></h3>
                        <p class="lead text-center"><?php echo t('professional_gameserver_hosting'); ?></p>
                        
                        <hr class="my-4">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4><i class="fas fa-rocket text-primary me-2"></i><?php echo t('our_mission'); ?></h4>
                                <p><?php echo t('mission_text'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h4><i class="fas fa-users text-primary me-2"></i><?php echo t('our_team'); ?></h4>
                                <p><?php echo t('team_text'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="row text-center mb-5">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-server fa-3x text-primary mb-3"></i>
                                <h5><?php echo t('premium_hardware'); ?></h5>
                                <p><?php echo t('premium_hardware_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <h5><?php echo t('ddos_protection'); ?></h5>
                                <p><?php echo t('ddos_protection_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-headset fa-3x text-info mb-3"></i>
                                <h5><?php echo t('support_247'); ?></h5>
                                <p><?php echo t('support_247_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3><i class="fas fa-users"></i></h3>
                                <h4>1000+</h4>
                                <p><?php echo t('satisfied_customers'); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h3><i class="fas fa-server"></i></h3>
                                <h4>500+</h4>
                                <p><?php echo t('active_servers'); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h3><i class="fas fa-clock"></i></h3>
                                <h4>99.9%</h4>
                                <p><?php echo t('uptime'); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h3><i class="fas fa-gamepad"></i></h3>
                                <h4>10+</h4>
                                <p><?php echo t('supported_games'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Zusätzliche Sections für mehr Scroll-Content -->
                <div class="card mt-5">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4"><?php echo t('our_technology'); ?></h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-microchip text-primary me-2"></i><?php echo t('high_end_hardware'); ?></h5>
                                <p><?php echo t('hardware_desc'); ?></p>
                                
                                <h5><i class="fas fa-network-wired text-primary me-2"></i><?php echo t('gbit_internet'); ?></h5>
                                <p><?php echo t('internet_desc'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-shield-alt text-primary me-2"></i><?php echo t('ddos_protection'); ?></h5>
                                <p><?php echo t('ddos_protection_desc'); ?></p>
                                
                                <h5><i class="fas fa-lock text-primary me-2"></i><?php echo t('hack_secure'); ?></h5>
                                <p><?php echo t('hack_secure_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-5">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4"><?php echo t('faq_title'); ?></h3>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        <?php echo t('faq_provisioning'); ?>
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo t('faq_provisioning_answer'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        <?php echo t('faq_upgrade'); ?>
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo t('faq_upgrade_answer'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        <?php echo t('faq_payment'); ?>
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo t('faq_payment_answer'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 mb-5">
                    <h3><?php echo t('ready_to_start'); ?></h3>
                    <p class="lead"><?php echo t('configure_perfect_server'); ?></p>
                    <a href="/ocean/shop#configurator" class="btn btn-gaming btn-lg">
                        <i class="fas fa-rocket me-2"></i><?php echo t('configure_server'); ?>
                    </a>
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