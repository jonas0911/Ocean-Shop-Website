<?php
// Prevent infinite loop when called from router
if (isset($_ROUTER_PROCESSED)) {
    // Called from router, skip routing logic
} else {
    // Direct access, redirect to router
    header('Location: /ocean/shop/');
    exit;
}

// Check maintenance mode first
require_once 'maintenance_check.php';

require_once 'config/database.php';
require_once 'includes/URLHelper.php';
require_once 'includes/LanguageManager.php';
require_once 'includes/User.php';
require_once 'includes/GameManager.php';
require_once 'includes/Cart.php';
require_once 'includes/Settings.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    // Redirect to clean URL without lang parameter
    $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $clean_url", true, 302);
    exit;
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de'; // Force German as default
}

// Initialize components - LAZY LOADING
$lang = new LanguageManager();
$user = new User();

// Initialize cart for navigation badge
$cart = new Cart();

// Only load games when needed (not on initial page load)
$games = []; // Will be loaded via AJAX
$gameManager = null; // Lazy load

// Skip database connection test for faster loading
// Database will be tested when actually needed
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
    <title><?php echo t('gameserver_hosting'); ?> - Ocean Hosting</title>
    
    <!-- DNS Prefetch for faster loading -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Critical CSS - Load immediately -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    
    <!-- Preload API for faster game loading -->
    <link rel="preload" href="/ocean/shop/api/get-games" as="fetch" crossorigin>
    
    <!-- Inline Critical CSS for instant theme -->
    <style>
        
        /* Navbar Farbe - Match mit Hero Background */
        .navbar {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #4299e1 100%) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Hero Section - ECHTES Fullscreen ohne Scrollbar */
        .hero-section {
            height: 100vh;
            min-height: 100vh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #4299e1 100%);
            color: white;
            text-align: center;
            padding: 0 20px 80px 20px; /* Extra padding unten */
            position: relative;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        /* Smooth Scrolling ohne Ruckler */
        html {
            scroll-behavior: smooth;
        }
        
        * {
            -webkit-overflow-scrolling: touch;
        }
        
        [data-theme="dark"] .hero-section {
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 50%, #2a4066 100%);
        }
        
        /* Configurator Section - VIEL mehr Abstand nach oben */
        #configurator {
            margin-top: 0;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Configurator Container größer */
        #configurator .container {
            max-width: 100%;
            padding: 2rem 0;
        }
        
        #configurator .col-lg-10 {
            max-width: 95% !important;
        }
        
        /* Scroll Indicator weiter nach oben damit mehr Platz ist */
        .scroll-indicator {
            position: absolute;
            bottom: 40px; /* Von default auf 40px erhöht */
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            z-index: 10;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-content h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-content p {
            font-size: clamp(1rem, 2vw, 1.3rem);
            line-height: 1.7;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .btn-ocean {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #0077BE 0%, #00A8E8 100%);
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 119, 190, 0.3);
        }
        
        .btn-ocean:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 168, 232, 0.5);
            color: white;
        }
        
        .scroll-indicator:hover {
            opacity: 0.8;
        }
        
        .scroll-indicator i {
            font-size: 2.5rem;
            color: white;
            opacity: 0.9;
            animation: bounce 2s ease-in-out infinite;
            display: block;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(15px); }
        }
        
        /* Responsive Fixes */
        @media (max-width: 768px) {
            .hero-section {
                height: 100vh;
                min-height: 100vh;
                max-height: 100vh;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .scroll-indicator {
                bottom: 25px;
            }
            
            .scroll-indicator i {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .btn-ocean {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
    </style>
    
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
                        <a class="nav-link dropdown-toggle" href="#" id="shopDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo t('shop'); ?>
                        </a>
                        <ul class="dropdown-menu shop-dropdown" aria-labelledby="shopDropdown">
                            <li><a class="dropdown-item scroll-to-configurator" href="/ocean/shop">
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
    <section class="hero-section">
        <div class="hero-content">
            <h1>Ocean Hosting</h1>
            <p>Tauche ein in die Welt des professionellen Gameserver-Hostings.<br>Kristallklare Performance, tiefe Zuverlässigkeit und ein Support so beständig wie das Meer.</p>
            <a href="#configurator" class="btn-ocean">
                <i class="fas fa-anchor"></i>
                <span><?php echo t('configure_server'); ?></span>
            </a>
        </div>
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Configurator Section -->
    <section id="configurator">
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
                                <!-- Loading skeleton while games load -->
                                <div class="game-option game-skeleton loading-skeleton">
                                    <div class="skeleton-content"></div>
                                </div>
                                <div class="game-option game-skeleton loading-skeleton">
                                    <div class="skeleton-content"></div>
                                </div>
                                <div class="game-option game-skeleton loading-skeleton">
                                    <div class="skeleton-content"></div>
                                </div>
                                
                                <!-- Games will be loaded via AJAX -->
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

    <!-- Load JS async for faster initial rendering -->
    <script async src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Instant theme loading -->
    <script>
        // Apply theme immediately without waiting
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <!-- Load remaining JS deferred -->
    <script defer src="/ocean/shop/assets/js/theme.js"></script>
    <script defer src="/ocean/shop/assets/js/shop.js"></script>
    
    <!-- Fast game loading -->
    <script>
        // Load games immediately when page is ready
        document.addEventListener('DOMContentLoaded', function() {
            loadGamesAsync();
        });
        
        async function loadGamesAsync() {
            try {
                const basePath = window.location.pathname.includes('/ocean/shop/') ? '/ocean/shop/' : '/';
                const response = await fetch(basePath + 'api/get-games');
                const data = await response.json();
                
                if (data.success && data.games) {
                    renderGames(data.games);
                } else {
                    console.error('Failed to load games:', data.error);
                    showGameError();
                }
            } catch (error) {
                console.error('Error loading games:', error);
                showGameError();
            }
        }
        
        function renderGames(games) {
            const gameSelector = document.getElementById('gameSelector');
            gameSelector.innerHTML = '';
            
            games.forEach(game => {
                const gameElement = document.createElement('div');
                gameElement.className = 'game-option';
                gameElement.setAttribute('data-game-id', game.id);
                gameElement.setAttribute('data-game-name', game.name);
                gameElement.setAttribute('data-min-ram', game.min_ram);
                gameElement.setAttribute('data-max-ram', game.max_ram);
                
                gameElement.innerHTML = `
                    <div class="game-option-image">
                        <img src="${game.image_url || 'https://via.placeholder.com/400x300/007bff/white?text=' + encodeURIComponent(game.name)}" 
                             alt="${game.name}" loading="lazy">
                    </div>
                    <div class="game-option-content">
                        <h6>${game.name}</h6>
                        <small class="text-muted">${game.min_ram}-${game.max_ram} GB RAM</small>
                        ${game.description ? `<p class="game-description">${game.description}</p>` : ''}
                    </div>
                `;
                
                gameSelector.appendChild(gameElement);
            });
            
            // Re-bind event listeners for dynamically loaded games
            document.querySelectorAll('.game-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    // Remove active class from all games
                    document.querySelectorAll('.game-option').forEach(opt => {
                        opt.classList.remove('active');
                    });
                    
                    // Add active class to clicked game
                    this.classList.add('active');
                    
                    const minRAM = parseInt(this.dataset.minRam) || 4;
                    const maxRAM = parseInt(this.dataset.maxRam) || 50;
                    
                    // Direkt Configurator freischalten
                    const configurator = document.querySelector('.server-configurator');
                    if (configurator) {
                        configurator.style.opacity = '1';
                        configurator.style.pointerEvents = 'all';
                        configurator.style.transition = 'opacity 0.3s ease';
                    }
                    
                    // Update shop.js instance if exists
                    if (window.gameShop) {
                        window.gameShop.selectedGame = {
                            id: this.dataset.gameId,
                            name: this.dataset.gameName,
                            image: this.querySelector('img')?.src || '',
                            minRAM: minRAM,
                            maxRAM: maxRAM
                        };
                        
                        window.gameShop.updateRAMSliderLimits(minRAM, maxRAM);
                        window.gameShop.selectedRAM = minRAM;
                        
                        const ramSlider = document.getElementById('ramSlider');
                        if (ramSlider) {
                            ramSlider.value = minRAM;
                        }
                        
                        window.gameShop.updateRAMDisplay();
                        window.gameShop.updatePrice();
                        window.gameShop.updateSliderProgress();
                    }
                });
            });
        }
        
        function showGameError() {
            const gameSelector = document.getElementById('gameSelector');
            gameSelector.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Spiele konnten nicht geladen werden</h5>
                    <button class="btn btn-primary" onclick="loadGamesAsync()">
                        <i class="fas fa-redo"></i> Erneut versuchen
                    </button>
                </div>
            `;
        }
        
        // Smooth scroll for anchor links with navbar offset
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navbarHeight = document.querySelector('.navbar').offsetHeight;
                    // Zusätzlicher Offset damit Pfeil komplett verschwindet (+ 100px)
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navbarHeight + 100;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Scroll Indicator Click Handler
        const scrollIndicator = document.querySelector('.scroll-indicator');
        if (scrollIndicator) {
            scrollIndicator.addEventListener('click', function() {
                const configurator = document.getElementById('configurator');
                if (configurator) {
                    const navbarHeight = document.querySelector('.navbar').offsetHeight;
                    // Zusätzlicher Offset damit Pfeil komplett verschwindet (+ 100px)
                    const targetPosition = configurator.getBoundingClientRect().top + window.pageYOffset - navbarHeight + 100;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        // Navbar hide/show on scroll - versteckt bei Configurator
        let lastScrollTop = 0;
        const navbar = document.querySelector('.navbar');
        
        window.addEventListener('scroll', function() {
            const configurator = document.getElementById('configurator');
            const configuratorTop = configurator.getBoundingClientRect().top;
            const navbarHeight = navbar.offsetHeight;
            
            // Navbar verstecken wenn Configurator erreicht ist
            if (configuratorTop <= navbarHeight) {
                navbar.style.transform = 'translateY(-100%)';
                navbar.style.transition = 'transform 0.3s ease-in-out';
            } else {
                navbar.style.transform = 'translateY(0)';
            }
        });
        
        // Gameserver Hosting Link - scrollt zum Configurator ohne # in URL
        document.querySelectorAll('.scroll-to-configurator').forEach(link => {
            link.addEventListener('click', function(e) {
                // Nur wenn wir bereits auf /ocean/shop sind
                if (window.location.pathname === '/ocean/shop' || window.location.pathname === '/ocean/shop/') {
                    e.preventDefault();
                    const configurator = document.getElementById('configurator');
                    if (configurator) {
                        const navbarHeight = document.querySelector('.navbar').offsetHeight;
                        const targetPosition = configurator.getBoundingClientRect().top + window.pageYOffset - navbarHeight + 100;
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
                // Sonst normaler Link zur Homepage
            });
        });
    </script>
    
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