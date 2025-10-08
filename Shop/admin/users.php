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

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($_POST['action']) {
        case 'add_user':
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            try {
                $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
                $stmt->execute([$name, $email, $password, $is_admin]);
                $success = "Benutzer erfolgreich hinzugefügt!";
            } catch (PDOException $e) {
                $error = "Fehler beim Hinzufügen des Benutzers: " . $e->getMessage();
            }
            break;
            
        case 'make_admin':
            $user_id = (int)$_POST['user_id'];
            try {
                $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "Benutzer wurde zum Administrator ernannt!";
            } catch (PDOException $e) {
                $error = "Fehler beim Aktualisieren des Benutzers: " . $e->getMessage();
            }
            break;
            
        case 'edit_user':
            $user_id = (int)$_POST['user_id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            try {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$name, $email, $is_admin, $user_id]);
                $success = "Benutzer erfolgreich aktualisiert!";
            } catch (PDOException $e) {
                $error = "Fehler beim Aktualisieren des Benutzers: " . $e->getMessage();
            }
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            if ($user_id === $_SESSION['user_id']) {
                $error = "Sie können sich nicht selbst löschen!";
            } else {
                try {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success = "Benutzer wurde gelöscht!";
                } catch (PDOException $e) {
                    $error = "Fehler beim Löschen des Benutzers: " . $e->getMessage();
                }
            }
            break;
    }
}

// Handle AJAX requests for user details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user_details') {
    header('Content-Type: application/json');
    $user_id = (int)$_GET['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Get user details
        $stmt = $db->prepare("SELECT id, name, email, is_admin, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Get additional statistics for this user
            $stmt = $db->prepare("SELECT COUNT(*) as cart_items FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['cart_items'];
            
            $userData['cart_items'] = $cartCount;
            $userData['member_since'] = date('d.m.Y', strtotime($userData['created_at']));
            $userData['status'] = $userData['is_admin'] ? 'Administrator' : 'Benutzer';
            
            echo json_encode(['success' => true, 'user' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
    exit;
}

// Get database users
$database = new Database();
$db = $database->getConnection();

$users = $db->query("SELECT id, name, email, is_admin, created_at, first_name, last_name, address, city, zip, country FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('users_management'); ?> - Ocean Hosting Admin</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ocean-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--ocean-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .text-sm {
            font-size: 0.875rem;
            line-height: 1.3;
        }
        
        /* Hover effects only for statistics cards */
        .row .col-md-3 .card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .row .col-md-3 .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .row .col-md-3 .card .fas {
            transition: all 0.3s ease;
        }
        .row .col-md-3 .card:hover .fas {
            transform: scale(1.1);
        }
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
                        <li><a class="dropdown-item" href="/ocean/shop"><i class="fas fa-store"></i> Shop</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
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
                            <i class="fas fa-tachometer-alt me-2"></i><?php echo t('dashboard'); ?>
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/games">
                            <i class="fas fa-gamepad me-2"></i><?php echo t('manage_games'); ?>
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/orders">
                            <i class="fas fa-shopping-bag me-2"></i><?php echo t('manage_orders'); ?>
                        </a>
                        <a class="nav-link active" href="/ocean/shop/admin/users">
                            <i class="fas fa-users me-2"></i><?php echo t('manage_users'); ?>
                        </a>
                        <a class="nav-link" href="/ocean/shop/admin/settings">
                            <i class="fas fa-cog me-2"></i><?php echo t('settings'); ?>
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
                                        <h1 class="mb-0"><i class="fas fa-users me-2"></i><?php echo t('users_management'); ?></h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-1"></i><?php echo t('add_user'); ?>
                        </button>
                    </div>

                    <!-- Messages -->
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Users Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h4><?php echo count($users); ?></h4>
                                    <p class="text-muted"><?php echo t('total_users'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                                    <h4><?php echo count(array_filter($users, function($u) { return $u['is_admin']; })); ?></h4>
                                    <p class="text-muted"><?php echo t('admin_users'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-user fa-2x text-success mb-2"></i>
                                    <h4><?php echo count(array_filter($users, function($u) { return !$u['is_admin']; })); ?></h4>
                                    <p class="text-muted"><?php echo t('regular_users'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                    <h4>0</h4>
                                    <p class="text-muted">Online</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i><?php echo t('recent_users'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('user'); ?></th>
                                            <th><?php echo t('email'); ?></th>
                                            <th>Adresse</th>
                                            <th><?php echo t('role'); ?></th>
                                            <th><?php echo t('created'); ?></th>
                                            <th><?php echo t('actions'); ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $userData): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($userData['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">ID: <?php echo $userData['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($userData['email']); ?></td>
                                            <td>
                                                <?php if (!empty($userData['address']) || !empty($userData['city'])): ?>
                                                    <div class="text-sm">
                                                        <?php if (!empty($userData['first_name']) || !empty($userData['last_name'])): ?>
                                                            <strong><?php echo htmlspecialchars(trim($userData['first_name'] . ' ' . $userData['last_name'])); ?></strong><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($userData['address'])): ?>
                                                            <?php echo htmlspecialchars($userData['address']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($userData['zip']) || !empty($userData['city'])): ?>
                                                            <?php echo htmlspecialchars($userData['zip'] . ' ' . $userData['city']); ?>
                                                            <?php if (!empty($userData['country']) && $userData['country'] != 'DE'): ?>
                                                                (<?php echo htmlspecialchars($userData['country']); ?>)
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-minus"></i> Keine Adresse</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($userData['is_admin']): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-crown me-1"></i><?php echo t('administrator'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-user me-1"></i><?php echo t('user'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($userData['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary me-1" onclick="viewUser(<?php echo $userData['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning me-1" onclick="editUser(<?php echo $userData['id']; ?>, '<?php echo addslashes($userData['name']); ?>', '<?php echo addslashes($userData['email']); ?>', <?php echo $userData['is_admin']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$userData['is_admin']): ?>
                                                    <button class="btn btn-sm btn-success me-1" onclick="makeAdmin(<?php echo $userData['id']; ?>)">
                                                        <i class="fas fa-crown"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $userData['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo t('add_user'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/ocean/shop/admin/users">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('name'); ?></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Passwort</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" id="is_admin">
                                <label class="form-check-label" for="is_admin"><?php echo t('administrator'); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" name="action" value="add_user" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i><?php echo t('add_user'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Benutzer bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/ocean/shop/admin/users">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-Mail</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_admin" id="edit_is_admin">
                                <label class="form-check-label" for="edit_is_admin">Administrator</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Benutzer-Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="user-avatar-large mb-3" id="view_user_avatar">
                                <!-- Avatar will be populated by JavaScript -->
                            </div>
                            <h5 id="view_user_name">-</h5>
                            <span class="badge" id="view_user_role_badge">-</span>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <strong>Benutzer-ID:</strong><br>
                                    <span id="view_user_id">-</span>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <strong>E-Mail:</strong><br>
                                    <span id="view_user_email">-</span>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <strong>Status:</strong><br>
                                    <span id="view_user_status">-</span>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <strong>Mitglied seit:</strong><br>
                                    <span id="view_user_member_since">-</span>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <strong>Artikel im Warenkorb:</strong><br>
                                    <span id="view_user_cart_items">-</span>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <strong>Erstellt am:</strong><br>
                                    <span id="view_user_created_at">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Section -->
                    <hr>
                    <h6><i class="fas fa-chart-line me-2"></i>Aktivität</h6>
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="card bg-primary text-white mb-2">
                                <div class="card-body py-2">
                                    <h5 class="mb-1" id="view_user_cart_count">0</h5>
                                    <small>Warenkorb Items</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="card bg-success text-white mb-2">
                                <div class="card-body py-2">
                                    <h5 class="mb-1">0</h5>
                                    <small>Bestellungen</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="card bg-info text-white mb-2">
                                <div class="card-body py-2">
                                    <h5 class="mb-1">Aktiv</h5>
                                    <small>Account Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Schließen
                    </button>
                    <button type="button" class="btn btn-warning" onclick="editUserFromView()">
                        <i class="fas fa-edit me-1"></i>Bearbeiten
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewUser(id) {
            // Fetch user details via AJAX
            fetch(`/ocean/shop/admin/users?action=get_user_details&user_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        // Populate modal with user data
                        document.getElementById('view_user_id').textContent = user.id;
                        document.getElementById('view_user_name').textContent = user.name;
                        document.getElementById('view_user_email').textContent = user.email;
                        document.getElementById('view_user_status').textContent = user.status;
                        document.getElementById('view_user_member_since').textContent = user.member_since;
                        document.getElementById('view_user_cart_items').textContent = user.cart_items;
                        document.getElementById('view_user_created_at').textContent = new Date(user.created_at).toLocaleDateString('de-DE');
                        document.getElementById('view_user_cart_count').textContent = user.cart_items;
                        
                        // Set avatar
                        const avatar = document.getElementById('view_user_avatar');
                        avatar.textContent = user.name.charAt(0).toUpperCase();
                        
                        // Set role badge
                        const roleBadge = document.getElementById('view_user_role_badge');
                        if (user.is_admin == 1) {
                            roleBadge.className = 'badge bg-warning';
                            roleBadge.innerHTML = '<i class="fas fa-crown me-1"></i>Administrator';
                        } else {
                            roleBadge.className = 'badge bg-primary';
                            roleBadge.innerHTML = '<i class="fas fa-user me-1"></i>Benutzer';
                        }
                        
                        // Store user data for potential edit
                        window.currentViewUser = user;
                        
                        // Show modal with proper focus management
                        const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                        modal.show();
                        
                        // Ensure proper focus management
                        document.getElementById('viewUserModal').addEventListener('shown.bs.modal', function () {
                            // Remove focus from any buttons that might retain it
                            document.activeElement.blur();
                        });
                    } else {
                        console.error('Error loading user data:', data.message);
                        // Show inline error message instead of alert
                        const modal = document.getElementById('viewUserModal');
                        const modalBody = modal.querySelector('.modal-body');
                        modalBody.innerHTML = '<div class="alert alert-danger">Fehler beim Laden der Benutzerdaten</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show inline error message instead of alert
                    const modal = document.getElementById('viewUserModal');
                    const modalBody = modal.querySelector('.modal-body');
                    modalBody.innerHTML = '<div class="alert alert-danger">Netzwerkfehler beim Laden der Daten</div>';
                });
        }
        
        function editUser(id, name, email, isAdmin) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_is_admin').checked = isAdmin == 1;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function editUserFromView() {
            if (window.currentViewUser) {
                const user = window.currentViewUser;
                // Close view modal
                bootstrap.Modal.getInstance(document.getElementById('viewUserModal')).hide();
                // Open edit modal with user data
                setTimeout(() => {
                    editUser(user.id, user.name, user.email, user.is_admin);
                }, 300);
            }
        }
        
        function makeAdmin(id) {
            if (confirm('Benutzer #' + id + ' zu Administrator machen?')) {
                // Send AJAX request to update user role
                fetch('/ocean/shop/admin/users', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=make_admin&user_id=' + id
                })
                .then(() => location.reload());
            }
        }
        
        function deleteUser(id) {
            if (confirm('Benutzer #' + id + ' wirklich löschen?\n\nAlle Daten werden unwiderruflich gelöscht!')) {
                // Send AJAX request to delete user
                fetch('/ocean/shop/admin/users', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=delete_user&user_id=' + id
                })
                .then(() => location.reload());
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script src="/ocean/shop/assets/js/language.js"></script>
    
    <script>
        // Global modal focus management
        document.addEventListener('DOMContentLoaded', function() {
            // Handle all modals
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function () {
                    // Remove focus from any focused elements when modal closes
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                });
                
                modal.addEventListener('show.bs.modal', function () {
                    // Clear any stray focus before showing
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                });
            });
        });
    </script>
</body>
</html>