<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GameManager.php';
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

$database = new Database();
$pdo = $database->getConnection();

$user = new User($pdo);
if (!$user->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$gameManager = new GameManager($pdo);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = '';

// Clear the session message after displaying
if (!empty($success_message)) {
    unset($_SESSION['success_message']);
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $min_ram = (int)($_POST['min_ram'] ?? 4);
            $max_ram = (int)($_POST['max_ram'] ?? 50);
            $image_url = $_POST['image'] ?? '';
            
            // Pterodactyl data - Port wird bei Server-Erstellung individuell zugewiesen
            $pterodactyl_data = [
                'egg_id' => !empty($_POST['pterodactyl_egg_id']) ? (int)$_POST['pterodactyl_egg_id'] : null,
                'docker_image' => $_POST['pterodactyl_docker_image'] ?? null,
                'startup_command' => $_POST['pterodactyl_startup_command'] ?? null,
                'environment' => !empty($_POST['pterodactyl_environment']) ? $_POST['pterodactyl_environment'] : null
            ];
            
            if ($name && $description) {
                try {
                    if ($gameManager->addGame($name, $image_url, $min_ram, $max_ram, $description, $pterodactyl_data)) {
                        $_SESSION['success_message'] = 'Spiel erfolgreich hinzugefügt!';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error_message = 'Fehler beim Hinzufügen des Spiels.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Netzwerkfehler: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Bitte füllen Sie alle Pflichtfelder aus.';
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $min_ram = (int)($_POST['min_ram'] ?? 4);
            $max_ram = (int)($_POST['max_ram'] ?? 50);
            $image_url = $_POST['image'] ?? '';
            
            // Pterodactyl data - Port wird bei Server-Erstellung individuell zugewiesen
            $pterodactyl_data = [
                'egg_id' => !empty($_POST['pterodactyl_egg_id']) ? (int)$_POST['pterodactyl_egg_id'] : null,
                'docker_image' => $_POST['pterodactyl_docker_image'] ?? null,
                'startup_command' => $_POST['pterodactyl_startup_command'] ?? null,
                'environment' => !empty($_POST['pterodactyl_environment']) ? $_POST['pterodactyl_environment'] : null
            ];
            
            if ($name && $description) {
                try {
                    if ($gameManager->updateGame($id, $name, $image_url, $min_ram, $max_ram, $description, $pterodactyl_data)) {
                        $_SESSION['success_message'] = 'Spiel erfolgreich aktualisiert!';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error_message = 'Fehler beim Aktualisieren des Spiels.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Netzwerkfehler: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Bitte füllen Sie alle Pflichtfelder aus.';
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            try {
                if ($gameManager->deleteGame($id)) {
                    $_SESSION['success_message'] = 'Spiel erfolgreich gelöscht!';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error_message = 'Fehler beim Löschen des Spiels.';
                }
            } catch (Exception $e) {
                $error_message = 'Netzwerkfehler: ' . $e->getMessage();
            }
        }
    }
}

// Get all games
$games = $gameManager->getAllGames();
?>
<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang->get('games_management'); ?> - Ocean Hosting Admin</title>
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
                        <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo $lang->get('logout'); ?></a></li>
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
                        <a class="nav-link active" href="/ocean/shop/admin/games">
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
                        <h1><i class="fas fa-gamepad me-2"></i>Spiele-Verwaltung</h1>
                        <div class="text-muted">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </div>
                    </div>

                    <!-- Success and Error Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

            <div class="card shadow">
                <div class="card-header d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGameModal">
                        <i class="fas fa-plus me-2"></i>Spiel hinzufügen
                    </button>
                </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Bild</th>
                                        <th>Name</th>
                                        <th>Beschreibung</th>
                                        <th>RAM</th>
                                        <th>Pterodactyl</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($games)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Keine Spiele gefunden</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td><?php echo $game['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($game['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($game['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($game['name']); ?>" 
                                                             style="width: 40px; height: 40px; object-fit: cover;" 
                                                             class="rounded">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <small class="text-white">N/A</small>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($game['name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($game['description'], 0, 50)) . '...'; ?></td>
                                                <td><span class="badge bg-info text-dark"><?php echo $game['min_ram']; ?> - <?php echo $game['max_ram']; ?> GB</span></td>
                                                <td>
                                                    <?php if (!empty($game['pterodactyl_egg_id'])): ?>
                                                        <span class="badge bg-success">Egg: <?php echo $game['pterodactyl_egg_id']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Kein Egg</span>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted">Port: Auto-Zuweisung</small>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="editGame(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($game['description'], ENT_QUOTES); ?>', <?php echo $game['min_ram']; ?>, <?php echo $game['max_ram']; ?>, '<?php echo htmlspecialchars($game['image_url'], ENT_QUOTES); ?>', <?php echo $game['pterodactyl_egg_id'] ?? 'null'; ?>, '<?php echo htmlspecialchars($game['pterodactyl_docker_image'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($game['pterodactyl_startup_command'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($game['pterodactyl_environment'] ?? '', ENT_QUOTES); ?>')">
                                                        <i class="fas fa-edit me-1"></i>Bearbeiten
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="deleteGame(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['name'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-trash me-1"></i>Löschen
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

    <!-- Add Game Modal -->
    <div class="modal fade" id="addGameModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Spiel hinzufügen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="addName" class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="addName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addDescription" class="form-label">Beschreibung</label>
                            <textarea class="form-control" name="description" id="addDescription" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="addMinRam" class="form-label">Min. RAM (GB)</label>
                                <input type="number" class="form-control" name="min_ram" id="addMinRam" min="1" max="50" value="4" required>
                            </div>
                            <div class="col-md-6">
                                <label for="addMaxRam" class="form-label">Max. RAM (GB)</label>
                                <input type="number" class="form-control" name="max_ram" id="addMaxRam" min="1" max="50" value="50" required>
                            </div>
                        </div>
                        
                        <!-- Pterodactyl Configuration -->
                        <div class="mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-server me-2"></i>Pterodactyl-Konfiguration</h6>
                            <small class="text-muted">Diese Einstellungen sind optional und werden für die automatische Server-Erstellung verwendet.</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="addPterodactylEggId" class="form-label">Egg ID</label>
                                <input type="hidden" name="pterodactyl_egg_id" id="addPterodactylEggId">
                                <div id="addEggSelector"></div>
                                <small class="text-muted">Wähle ein Egg aus den verfügbaren Nests</small>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-1"><i class="fas fa-network-wired me-2"></i>Port-Zuweisung</h6>
                                        <p class="card-text mb-0 small">Ports werden automatisch bei der Server-Erstellung für jeden Kunden individuell zugewiesen</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for Pterodactyl data - filled automatically by Egg selection -->
                        <input type="hidden" name="pterodactyl_docker_image" id="addDockerImage">
                        <input type="hidden" name="pterodactyl_startup_command" id="addStartupCommand">
                        <input type="hidden" name="pterodactyl_environment" id="addEnvironment">
                        
                        <!-- Info display for selected Egg data -->
                        <div id="addEggInfo" style="display: none;" class="mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-success"><i class="fas fa-info-circle me-2"></i>Egg-Konfiguration</h6>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <strong>Docker Image:</strong> <span id="addDockerImageDisplay" class="text-muted">Nicht gesetzt</span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <strong>Startup Command:</strong> <span id="addStartupCommandDisplay" class="text-muted">Nicht gesetzt</span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <strong>Environment:</strong> <span id="addEnvironmentDisplay" class="text-muted">Keine Variablen</span>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Diese Werte werden automatisch vom ausgewählten Egg übernommen</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">Spiel-Bild</label>
                            
                            <!-- Image Upload Tabs -->
                            <ul class="nav nav-tabs" id="imageUploadTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-content" type="button">
                                        <i class="fas fa-link me-1"></i>URL
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-content" type="button">
                                        <i class="fas fa-upload me-1"></i>Upload / Paste
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content border border-top-0 p-3">
                                <!-- URL Tab -->
                                <div class="tab-pane fade show active" id="url-content">
                                    <input type="url" class="form-control" name="image" id="image_url" placeholder="https://example.com/image.jpg">
                                    <small class="text-muted">Enter a direct link to an image (JPG, PNG, GIF)</small>
                                </div>
                                
                                <!-- Upload Tab with integrated Paste -->
                                <div class="tab-pane fade" id="upload-content">
                                    <div class="image-drop-zone" id="imageDropZone">
                                        <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                        <p>Drag & Drop image here, click to browse, or paste (Ctrl+V)</p>
                                        <small class="text-muted">Supported: JPG, PNG, GIF (Max: 5MB) | Paste from clipboard works anywhere</small>
                                    </div>
                                    <input type="file" id="imageFileInput" accept="image/*" style="display: none;">
                                </div>
                            </div>
                            
                            <!-- Image Preview -->
                            <div id="imagePreview" style="display: none;" class="mt-3">
                                <label class="form-label">Preview:</label>
                                <div class="border rounded p-2">
                                    <img id="previewImage" src="" alt="Preview" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang->get('cancel'); ?></button>
                        <button type="submit" class="btn btn-success"><?php echo $lang->get('add_game'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Game Modal -->
    <div class="modal fade" id="editGameModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo $lang->get('edit_game'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="mb-3">
                            <label for="editName" class="form-label"><?php echo $lang->get('name'); ?></label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label"><?php echo $lang->get('description'); ?></label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="editMinRam" class="form-label"><?php echo $lang->get('min_ram'); ?> (GB)</label>
                                <input type="number" class="form-control" name="min_ram" id="editMinRam" min="1" max="50" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editMaxRam" class="form-label"><?php echo $lang->get('max_ram'); ?> (GB)</label>
                                <input type="number" class="form-control" name="max_ram" id="editMaxRam" min="1" max="50" required>
                            </div>
                        </div>
                        
                        <!-- Pterodactyl Configuration -->
                        <div class="mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-server me-2"></i>Pterodactyl-Konfiguration</h6>
                            <small class="text-muted">Diese Einstellungen sind optional und werden für die automatische Server-Erstellung verwendet.</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editPterodactylEggId" class="form-label">Egg ID</label>
                                <input type="hidden" name="pterodactyl_egg_id" id="editPterodactylEggId">
                                <div id="editEggSelector"></div>
                                <small class="text-muted">Wähle ein Egg aus den verfügbaren Nests</small>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-1"><i class="fas fa-network-wired me-2"></i>Port-Zuweisung</h6>
                                        <p class="card-text mb-0 small">Ports werden automatisch bei der Server-Erstellung für jeden Kunden individuell zugewiesen</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for Pterodactyl data - filled automatically by Egg selection -->
                        <input type="hidden" name="pterodactyl_docker_image" id="editDockerImage">
                        <input type="hidden" name="pterodactyl_startup_command" id="editStartupCommand">
                        <input type="hidden" name="pterodactyl_environment" id="editEnvironment">
                        
                        <!-- Info display for selected Egg data -->
                        <div id="editEggInfo" style="display: none;" class="mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-success"><i class="fas fa-info-circle me-2"></i>Egg-Konfiguration</h6>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <strong>Docker Image:</strong> <span id="editDockerImageDisplay" class="text-muted">Nicht gesetzt</span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <strong>Startup Command:</strong> <span id="editStartupCommandDisplay" class="text-muted">Nicht gesetzt</span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <strong>Environment:</strong> <span id="editEnvironmentDisplay" class="text-muted">Keine Variablen</span>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Diese Werte werden automatisch vom ausgewählten Egg übernommen</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label"><?php echo $lang->get('game_image'); ?></label>
                            
                            <!-- Image Upload Tabs for Edit -->
                            <ul class="nav nav-tabs" id="editImageUploadTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="edit-url-tab" data-bs-toggle="tab" data-bs-target="#edit-url-content" type="button">
                                        <i class="fas fa-link me-1"></i>URL
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="edit-upload-tab" data-bs-toggle="tab" data-bs-target="#edit-upload-content" type="button">
                                        <i class="fas fa-upload me-1"></i>Upload / Paste
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content border border-top-0 p-3">
                                <!-- URL Tab -->
                                <div class="tab-pane fade show active" id="edit-url-content">
                                    <input type="url" class="form-control" name="image" id="editImage" placeholder="https://example.com/image.jpg">
                                    <small class="text-muted">Enter a direct link to an image (JPG, PNG, GIF)</small>
                                </div>
                                
                                <!-- Upload Tab with integrated Paste -->
                                <div class="tab-pane fade" id="edit-upload-content">
                                    <div class="image-drop-zone" id="editImageDropZone">
                                        <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                        <p>Drag & Drop image here, click to browse, or paste (Ctrl+V)</p>
                                        <small class="text-muted">Supported: JPG, PNG, GIF (Max: 5MB) | Paste from clipboard works anywhere</small>
                                    </div>
                                    <input type="file" id="editImageFileInput" accept="image/*" style="display: none;">
                                </div>
                            </div>
                            
                            <!-- Image Preview for Edit -->
                            <div id="editImagePreview" style="display: none;" class="mt-3">
                                <label class="form-label">Preview:</label>
                                <div class="border rounded p-2">
                                    <img id="editPreviewImage" src="" alt="Preview" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Game Modal -->
    <div class="modal fade" id="deleteGameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo $lang->get('delete_game'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <p>Sind Sie sicher, dass Sie dieses Spiel löschen möchten?</p>
                        <p><strong id="deleteGameName"></strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang->get('cancel'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo $lang->get('delete'); ?></button>
                    </div>
                </form>
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
    <script src="/ocean/shop/assets/js/admin-image-upload.js"></script>
    <script src="/ocean/shop/assets/js/pterodactyl-egg-selector.js"></script>

    <script>
        // Egg selector instances
        let addEggSelector, editEggSelector;
        
        // Ensure theme toggle works properly
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Games page loaded, checking theme system...');
            
            // Check if theme manager is available
            if (window.oceanTheme) {
                console.log('ThemeManager found:', window.oceanTheme);
            } else {
                console.log('ThemeManager not found, waiting...');
                setTimeout(function() {
                    if (window.oceanTheme) {
                        console.log('ThemeManager found after delay:', window.oceanTheme);
                    }
                }, 100);
            }
            
            // Check button existence
            const button = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-icon');
            console.log('Theme button found:', button);
            console.log('Theme icon found:', icon);
            
            // Initialize egg selectors
            initializeEggSelectors();
        });
        
        function initializeEggSelectors() {
            // Initialize add game egg selector
            addEggSelector = new PterodactylEggSelector('addEggSelector', 'addPterodactylEggId');
            
            // Initialize edit game egg selector  
            editEggSelector = new PterodactylEggSelector('editEggSelector', 'editPterodactylEggId');
            
            console.log('Egg selectors initialized:', {
                addSelector: addEggSelector,
                editSelector: editEggSelector
            });
            
            // Setup modal event listeners for automatic port assignment
            setupModalEventListeners();
        }
        
        function setupModalEventListeners() {
            // Setup modal reset behavior
            const addGameModal = document.getElementById('addGameModal');
            if (addGameModal) {
                // Reset form when modal is hidden
                addGameModal.addEventListener('hidden.bs.modal', function() {
                    const form = addGameModal.querySelector('form');
                    if (form) form.reset();
                    
                    // Hide egg info card
                    const eggInfoCard = document.getElementById('addEggInfo');
                    if (eggInfoCard) {
                        eggInfoCard.style.display = 'none';
                    }
                    
                    // Reset egg selector
                    if (addEggSelector) {
                        addEggSelector.clear();
                    }
                });
            }
        }
        

    </script>

    <script>
        function editGame(id, name, description, minRam, maxRam, image, eggId, dockerImage, startupCommand, environment) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editMinRam').value = minRam;
            document.getElementById('editMaxRam').value = maxRam;
            document.getElementById('editImage').value = image;
            
            // Pterodactyl fields - hidden fields and display
            document.getElementById('editDockerImage').value = dockerImage || '';
            document.getElementById('editStartupCommand').value = startupCommand || '';
            document.getElementById('editEnvironment').value = environment || '';
            
            // Set egg selector and show info if egg exists
            if (editEggSelector) {
                editEggSelector.setValue(eggId === 'null' ? '' : eggId);
            }
            
            // Show egg info if data exists
            if (dockerImage || startupCommand || environment) {
                const eggInfoCard = document.getElementById('editEggInfo');
                if (eggInfoCard) {
                    eggInfoCard.style.display = 'block';
                    
                    const dockerDisplay = document.getElementById('editDockerImageDisplay');
                    const startupDisplay = document.getElementById('editStartupCommandDisplay');
                    const envDisplay = document.getElementById('editEnvironmentDisplay');
                    
                    if (dockerDisplay) dockerDisplay.textContent = dockerImage || 'Nicht gesetzt';
                    if (startupDisplay) startupDisplay.textContent = startupCommand || 'Nicht gesetzt';
                    if (envDisplay) {
                        if (environment) {
                            const envVars = environment.split('\n').join(', ');
                            envDisplay.textContent = envVars;
                        } else {
                            envDisplay.textContent = 'Keine Variablen';
                        }
                    }
                }
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editGameModal'));
            modal.show();
        }

        function deleteGame(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteGameName').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteGameModal'));
            modal.show();
        }
        

    </script>
</body>
</html>