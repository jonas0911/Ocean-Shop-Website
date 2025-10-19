<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';
require_once __DIR__ . '/../includes/User.php';

$lang = new LanguageManager();
$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    header('Location: /ocean/shop');
    exit;
}

$message = '';
$messageType = '';

if ($_POST) {
    // CSRF Protection
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.';
        $messageType = 'danger';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!empty($email) && !empty($password)) {
            $result = $user->login($email, $password);
            if ($result['success']) {
                header('Location: /ocean/shop');
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        } else {
            $message = 'Bitte alle Felder ausfüllen';
            $messageType = 'danger';
        }
    }
}
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
    <title><?php echo t('login'); ?> - Ocean Hosting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    
    <!-- Anti-flashbang protection -->
    <style>
        :root {
            --bg-color: #121212;
            --text-color: #ffffff;
            --card-bg: #1e1e1e;
        }
        [data-theme="dark"] { background: var(--bg-color) !important; color: var(--text-color) !important; }
        .card { background: var(--card-bg) !important; border: 1px solid #333 !important; }
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
                    
                    <li class="nav-item">
                        <a class="nav-link active" href="/ocean/shop/login"><?php echo t('login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ocean/shop/register"><?php echo t('register'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pb-5" style="margin-top: 120px;">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4"><?php echo t('login'); ?></h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <?php
                            // CSRF Token
                            if (session_status() == PHP_SESSION_NONE) {
                                session_start();
                            }
                            if (!isset($_SESSION['csrf_token'])) {
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            }
                            ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo t('email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       required maxlength="255" autocomplete="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo t('password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8" maxlength="255" autocomplete="current-password">
                            </div>
                            
                            <!-- Rate Limiting Info -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt"></i> 
                                    Sichere Anmeldung mit Schutz vor Brute-Force-Angriffen
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-gaming w-100" id="loginBtn">
                                <i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?>
                            </button>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <p>Noch kein Konto? <a href="/ocean/shop/register"><?php echo t('create_account'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Management -->
    <script src="/ocean/shop/assets/js/theme.js"></script>
    
    <!-- Login Form Enhancement -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Form submission enhancement
            loginForm.addEventListener('submit', function(e) {
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Anmelden...';
            });
            
            // Email validation
            emailInput.addEventListener('blur', function() {
                if (this.value && !this.value.includes('@')) {
                    this.setCustomValidity('Bitte geben Sie eine gültige E-Mail-Adresse ein');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });
            
            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                if (this.value.length < 8) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Auto-focus email field
            emailInput.focus();
        });
    </script>
</body>
</html>