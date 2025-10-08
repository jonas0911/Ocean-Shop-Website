<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';
require_once __DIR__ . '/../includes/User.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de';
}

$lang = new LanguageManager();
$user = new User();

// Redirect if not logged in
if (!$user->isLoggedIn()) {
    header('Location: /ocean/shop/login');
    exit;
}

$currentUser = $user->getCurrentUser();

// Get user servers
require_once __DIR__ . '/../includes/ServerManager.php';
$serverManager = new ServerManager();
$userServers = $serverManager->getUserServers($user->getId());
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('account'); ?> - Ocean Hosting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
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
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop/cart"><?php echo t('cart'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/ocean/shop/account"><?php echo t('account'); ?></a>
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
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> <?php echo t('account'); ?></a></li>
                            <li><a class="dropdown-item" href="/ocean/shop/cart"><i class="fas fa-shopping-cart"></i> <?php echo t('cart'); ?></a></li>
                            <?php if ($user->isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-cog"></i> <?php echo t('admin'); ?></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pb-5" style="margin-top: 120px;">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-user-circle me-2"></i><?php echo t('account'); ?>
                        </h2>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5><i class="fas fa-info-circle me-2"></i><?php echo t('user_information'); ?></h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong><?php echo t('name'); ?>:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong><?php echo t('email'); ?>:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong><?php echo t('status'); ?>:</strong> 
                                            <?php if ($currentUser['is_admin']): ?>
                                                <span class="badge bg-success"><?php echo t('administrator'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?php echo t('user'); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-server me-2"></i><?php echo t('my_servers'); ?></h5>
                                
                                <?php if (empty($userServers)): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p><?php echo t('no_servers_ordered'); ?></p>
                                        <a href="/ocean/shop" class="btn btn-gaming"><?php echo t('order_server'); ?></a>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($userServers as $server): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0">
                                                                <i class="fas fa-gamepad me-2"></i>
                                                                <?php echo htmlspecialchars($server['server_name']); ?>
                                                            </h6>
                                                            <span class="badge <?php echo $server['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                                                <?php echo ucfirst($server['status']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="row text-sm">
                                                            <div class="col-6">
                                                                <p class="mb-1"><strong>Game:</strong> <?php echo ucfirst($server['game_type']); ?></p>
                                                                <p class="mb-1"><strong>RAM:</strong> <?php echo $server['memory']; ?>MB</p>
                                                            </div>
                                                            <div class="col-6">
                                                                <p class="mb-1"><strong>IP:</strong> <?php echo htmlspecialchars($server['ip_address']); ?></p>
                                                                <p class="mb-1"><strong>Port:</strong> <?php echo $server['port']; ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <strong>Expires:</strong> 
                                                                <?php 
                                                                $expiresAt = new DateTime($server['expires_at']);
                                                                echo $expiresAt->format('d.m.Y H:i');
                                                                ?>
                                                            </small>
                                                        </div>
                                                        
                                                        <div class="mt-3">
                                                            <button class="btn btn-sm btn-primary me-2" onclick="manageServer(<?php echo $server['id']; ?>, 'start')">
                                                                <i class="fas fa-play"></i> Start
                                                            </button>
                                                            <button class="btn btn-sm btn-warning me-2" onclick="manageServer(<?php echo $server['id']; ?>, 'restart')">
                                                                <i class="fas fa-redo"></i> Restart
                                                            </button>
                                                            <button class="btn btn-sm btn-secondary me-2" onclick="manageServer(<?php echo $server['id']; ?>, 'stop')">
                                                                <i class="fas fa-stop"></i> Stop
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteServer(<?php echo $server['id']; ?>)">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="/ocean/shop" class="btn btn-gaming">
                                            <i class="fas fa-plus me-2"></i><?php echo t('order_server'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
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
        // Server Management Functions
        function manageServer(serverId, action) {
            if (!confirm(`Are you sure you want to ${action} this server?`)) {
                return;
            }
            
            fetch('/ocean/shop/api/server-management', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'server_power',
                    server_id: serverId,
                    power_action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Server ${action} command sent successfully!`);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while managing the server.');
            });
        }
        
        function deleteServer(serverId) {
            if (!confirm('Are you sure you want to delete this server? This action cannot be undone!')) {
                return;
            }
            
            fetch('/ocean/shop/api/server-management', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_server',
                    server_id: serverId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Server deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the server.');
            });
        }
    </script>
</body>
</html>