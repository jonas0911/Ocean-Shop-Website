<?php
// Check maintenance mode first
require_once 'maintenance_check.php';

require_once 'config/database.php';
require_once 'includes/LanguageManager.php';
require_once 'includes/User.php';
require_once 'includes/GameManager.php';
require_once 'includes/Cart.php';
require_once 'includes/Settings.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de'; // Force German as default
}

// Initialize components
$lang = new LanguageManager();
$user = new User();
$gameManager = new GameManager();
$cart = new Cart();

// Test database connection first
$database = new Database();
$testConn = $database->getConnection();

if ($testConn === null) {
    die("Database connection failed. Please check the database configuration.");
}

// Get available games with error handling
try {
    $games = $gameManager->getAllGames();
} catch (Exception $e) {
    $games = [];
    error_log("Error loading games: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('gameserver_hosting'); ?> - Ocean Hosting</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="Ocean Hosting - Professional Gameserver Hosting. Minecraft, Rust, ARK and more. Configure your perfect server now!">
    <meta name="keywords" content="gameserver, hosting, minecraft, gaming, server">
    <meta name="author" content="Ocean Hosting">
    
    <style>
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
        [data-theme="light"] .card p {
            color: white !important;
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
        [data-theme="dark"] .card p {
            color: var(--text-color) !important;
        }
        
        /* Game Image Scaling Fix */
        .game-option-image {
            position: relative !important;
            height: 120px !important;
            width: 100% !important;
            overflow: hidden !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, rgba(0,0,0,0.1), rgba(0,0,0,0.2)) !important;
            border-radius: 12px !important;
        }
        
        .game-option-image img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
            border-radius: 12px !important;
            transition: transform 0.3s ease !important;
            max-width: none !important;
            max-height: none !important;
            min-width: 100% !important;
            min-height: 100% !important;
        }
        
        .game-option:hover .game-option-image img {
            transform: scale(1.05) !important;
        }
    </style>
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
                        <a class="nav-link active" href="/ocean/shop"><?php echo t('home'); ?></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#configurator" id="shopDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo t('shop'); ?>
                        </a>
                        <ul class="dropdown-menu shop-dropdown" aria-labelledby="shopDropdown">
                            <li><a class="dropdown-item" href="#configurator">
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
                                <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> <?php echo t('account'); ?></a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-cogs"></i> <?php echo t('admin'); ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/login"><i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/register"><i class="fas fa-user-plus"></i> <?php echo t('register'); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" style="margin-top: 60px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1 class="fade-in-up">Ocean Hosting</h1>
                    <p class="fade-in-up">Tauche ein in die Welt des professionellen Gameserver-Hostings! Kristallklare Performance, tiefe Zuverlässigkeit und ein Support so beständig wie das Meer.</p>
                    <a href="#configurator" class="btn btn-ocean btn-lg fade-in-up">
                        <i class="fas fa-anchor me-2"></i><?php echo t('configure_server'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Anchor für bessere Navigation -->
    <div id="configurator" style="position: relative; top: -150px; visibility: hidden;"></div>
    
    <!-- Server Configurator -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="configurator-card fade-in-up">
                        <h2 class="text-center mb-4"><?php echo t('configure_server'); ?></h2>
                        
                        <!-- Game Selection -->
                        <div class="mb-4">
                            <h4><?php echo t('select_game'); ?></h4>
                            
                            <!-- Game Search -->
                            <div class="game-search-container mb-3">
                                <div class="position-relative">
                                    <input type="text" class="form-control game-search-input" id="gameSearchInput" placeholder="Spiel suchen...">
                                    <i class="fas fa-search game-search-icon"></i>
                                </div>
                            </div>
                            
                            <div class="game-selector" id="gameSelector">
                                <?php if (empty($games)): ?>
                                    <!-- Default Minecraft if no games in database -->
                                    <div class="game-option" data-game-id="1" data-game-name="Minecraft" data-min-ram="4" data-max-ram="10">
                                        <div class="game-option-image">
                                            <img src="https://via.placeholder.com/400x300/4CAF50/white?text=Minecraft" alt="Minecraft">
                                        </div>
                                        <div class="game-option-content">
                                            <h6>Minecraft</h6>
                                            <small class="text-muted">4-10 GB RAM</small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($games as $game): ?>
                                        <div class="game-option" data-game-id="<?php echo $game['id']; ?>" data-game-name="<?php echo htmlspecialchars($game['name']); ?>" data-min-ram="<?php echo $game['min_ram']; ?>" data-max-ram="<?php echo $game['max_ram']; ?>">
                                            <div class="game-option-image">
                                                <img src="<?php echo htmlspecialchars($game['image_url']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" loading="lazy">
                                            </div>
                                            <div class="game-option-content">
                                                <h6><?php echo htmlspecialchars($game['name']); ?></h6>
                                                <small class="text-muted"><?php echo $game['min_ram']; ?>-<?php echo $game['max_ram']; ?> GB RAM</small>
                                                <?php if (!empty($game['description'])): ?>
                                                    <p class="game-description"><?php echo htmlspecialchars($game['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="no-games-found" id="noGamesFound" style="display: none;">
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5>Keine Spiele gefunden</h5>
                                    <p class="text-muted">Versuche einen anderen Suchbegriff</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Server Configuration -->
                        <div class="server-configurator" style="opacity: 0.5; pointer-events: none;">
                            <!-- RAM Selection -->
                            <div class="ram-slider-container">
                                <h4 class="text-center"><?php echo t('select_ram'); ?></h4>
                                <div class="ram-display" id="ramDisplay">4 GB</div>
                                <input type="range" class="ram-slider" id="ramSlider" min="4" max="50" value="4" step="1">
                                <div class="d-flex justify-content-between text-muted mt-2">
                                    <span>4 GB</span>
                                    <span>50 GB</span>
                                </div>
                            </div>
                            
                            <!-- Duration Selection -->
                            <div class="mb-4">
                                <h4 class="text-center"><?php echo t('select_duration'); ?></h4>
                                <div class="duration-buttons">
                                    <button class="duration-btn active" data-duration="1_month"><?php echo t('1_month'); ?></button>
                                    <button class="duration-btn" data-duration="1_week"><?php echo t('1_week'); ?></button>
                                    <button class="duration-btn" data-duration="3_days"><?php echo t('3_days'); ?></button>
                                </div>
                            </div>
                            
                            <!-- Price Display -->
                            <div class="price-display" id="priceDisplay">
                                <span class="price-amount">4.00€</span>
                                <span class="price-period">/ 30 Tage</span>
                            </div>
                            
                            <!-- Add to Cart Button -->
                            <div class="text-center">
                                <button class="btn btn-gaming btn-lg" id="addToCartBtn">
                                    <i class="fas fa-shopping-cart me-2"></i><?php echo t('add_to_cart'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                            <h5>High Performance</h5>
                            <p>Neueste Hardware und SSD-Speicher für maximale Performance</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <h5>DDoS Protection</h5>
                            <p>Professioneller DDoS-Schutz für unterbrechungsfreies Gaming</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-headset fa-3x text-info mb-3"></i>
                            <h5>24/7 Support</h5>
                            <p>Unser Expertenteam steht dir rund um die Uhr zur Verfügung</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-links">
                <a href="/ocean/shop/imprint"><?php echo t('imprint'); ?></a>
                <a href="/ocean/shop/privacy"><?php echo t('privacy_policy'); ?></a>
                <a href="/ocean/shop/terms"><?php echo t('terms_conditions'); ?></a>
                <a href="/ocean/shop/contact"><?php echo t('contact'); ?></a>
            </div>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Ocean Hosting. <?php echo t('all_rights_reserved'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Cookie Banner -->
    <div class="cookie-banner" id="cookieBanner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0"><?php echo t('cookie_consent'); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light" id="acceptCookies"><?php echo t('accept_cookies'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/shop.js"></script>
    <!-- Theme Management -->
    <script src="/ocean/shop/assets/js/theme.js"></script>
    
    <!-- Dropdown Z-Index Fix -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Force all dropdowns to maximum z-index
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(menu => {
            menu.style.zIndex = '2147483647';
            menu.style.position = 'absolute';
        });
        
        // Also fix on Bootstrap dropdown events
        document.addEventListener('shown.bs.dropdown', function(e) {
            const menu = e.target.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.zIndex = '2147483647';
            }
        });
    });
    </script>
</body>
</html>