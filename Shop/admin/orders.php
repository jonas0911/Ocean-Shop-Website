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

$lang = new LanguageManager();

// Get database stats
$database = new Database();
$db = $database->getConnection();

// Get real orders from database (empty for now)
$orders = [];

// TODO: Implement real orders table and query:
// $stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
// $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title><?php echo t('orders_management'); ?> - Ocean Hosting Admin</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1"><?php echo t('orders'); ?></span>
            
            <div class="navbar-nav ms-auto d-flex flex-row">
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
                        <a class="nav-link active" href="/ocean/shop/admin/orders">
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
                    <div class="mb-4">
                        <h1><i class="fas fa-shopping-bag me-2"></i>Bestellungen verwalten</h1>
                    </div>

                    <!-- Orders Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h4>0</h4>
                                    <p class="text-muted">Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-cog fa-2x text-info mb-2"></i>
                                    <h4>0</h4>
                                    <p class="text-muted">Processing</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-check fa-2x text-success mb-2"></i>
                                    <h4>0</h4>
                                    <p class="text-muted">Completed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-euro-sign fa-2x text-primary mb-2"></i>
                                    <h4>0.00€</h4>
                                    <p class="text-muted">Gesamtumsatz</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>Alle Bestellungen</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kunde</th>
                                            <th>Spiel</th>
                                            <th>Betrag</th>
                                            <th>Status</th>
                                            <th>Datum</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    Keine Bestellungen vorhanden
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo $order['customer']; ?></td>
                                            <td><?php echo $order['game']; ?></td>
                                            <td><?php echo $order['amount']; ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = '';
                                                switch($order['status']) {
                                                    case 'Pending': $badgeClass = 'bg-warning'; break;
                                                    case 'Processing': $badgeClass = 'bg-info'; break;
                                                    case 'Completed': $badgeClass = 'bg-success'; break;
                                                    default: $badgeClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?> status-badge">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $order['date']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary me-1" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning me-1" onclick="editOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
        function viewOrder(id) {
            console.log('Viewing order #' + id);
            // Future implementation: Open order details modal
        }
        
        function editOrder(id) {
            console.log('Editing order #' + id);
            // Future implementation: Open order edit modal
        }
        
        function deleteOrder(id) {
            if (confirm('Bestellung #' + id + ' wirklich löschen?')) {
                console.log('Deleting order #' + id);
                // Future implementation: AJAX delete request
            }
        }
    </script>
</body>
</html>