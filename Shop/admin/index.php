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

// Get database stats
$database = new Database();
$db = $database->getConnection();

$userStats = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$cartStats = $db->query("SELECT COUNT(*) as count FROM cart_items")->fetch()['count'];
$gameStats = $db->query("SELECT COUNT(*) as count FROM games WHERE active = 1")->fetch()['count'];

// Today's stats (mockdata for now)
$todayOrders = 0;
$todayRevenue = 0;
$activeServers = 0;
?>
<!DOCTYPE html>
<html>
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
    <title><?php echo t('admin_panel'); ?> - Ocean Hosting Admin</title>
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
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-online { background: #28a745; }
        .status-offline { background: #dc3545; }
        .status-warning { background: #ffc107; }
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
                        <a class="nav-link active" href="/ocean/shop/admin">
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
                        <a class="nav-link" href="/ocean/shop/admin/settings">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1><i class="fas fa-tachometer-alt me-2"></i>Admin-Panel</h1>
                        <div class="text-muted">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-shopping-bag fa-3x text-primary mb-3"></i>
                                    <h3><?php echo $todayOrders; ?></h3>
                                    <p class="text-muted">Bestellungen heute</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-euro-sign fa-3x text-success mb-3"></i>
                                    <h3><?php echo $todayRevenue; ?>€</h3>
                                    <p class="text-muted">Umsatz heute</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-server fa-3x text-info mb-3"></i>
                                    <h3><?php echo $activeServers; ?></h3>
                                    <p class="text-muted">Aktive Server</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-users fa-3x text-warning mb-3"></i>
                                    <h3><?php echo $userStats; ?></h3>
                                    <p class="text-muted">Registrierte Benutzer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-shopping-bag me-2"></i>Letzte Bestellungen</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>Keine Bestellungen vorhanden</p>
                                        <small>Neue Bestellungen werden hier angezeigt</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-heartbeat me-2"></i>System Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Server Status</strong><br>
                                        <span class="status-indicator status-online"></span>Online
                                    </div>
                                    <div class="mb-3">
                                        <strong>Datenbank</strong><br>
                                        <span class="status-indicator status-online"></span>Verbunden
                                    </div>
                                    <div class="mb-3">
                                        <strong>PayPal API</strong><br>
                                        <span class="status-indicator status-warning"></span>Test-Modus
                                    </div>
                                    <div class="mb-3">
                                        <strong>Spiele verfügbar</strong><br>
                                        <span class="status-indicator status-online"></span><?php echo $gameStats; ?> Spiele
                                    </div>
                                    <div class="mb-3">
                                        <strong>Warenkorb-System</strong><br>
                                        <span class="status-indicator status-online"></span><?php echo $cartStats; ?> aktive Körbe
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Stats -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-pie me-2"></i>Beliebte Spiele</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-gamepad fa-3x mb-3"></i>
                                        <p>Noch keine Verkaufsdaten</p>
                                        <small>Statistiken werden nach ersten Verkäufen angezeigt</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-calendar-alt me-2"></i>Letzte Aktivitäten</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-history fa-3x mb-3"></i>
                                        <p>Keine aktuellen Aktivitäten</p>
                                        <small>Login, Registrierungen und Aktionen werden hier angezeigt</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <!-- Admin Functions -->
    <script>
        function showOrderManagement() {
            // Redirect to orders page instead of showing alert
            window.location.href = '/ocean/shop/admin/orders';
        }
        
        function showUserManagement() {
            // Redirect to users page instead of showing alert
            window.location.href = '/ocean/shop/admin/users';
        }
        
        function showSettings() {
            // Redirect to settings page instead of showing alert
            window.location.href = '/ocean/shop/admin/settings';
        }

        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            // This would refresh stats in a real implementation
            console.log('Stats würden hier aktualisiert werden...');
        }, 30000);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script src="/ocean/shop/assets/js/language.js"></script>
</body>
</html>
