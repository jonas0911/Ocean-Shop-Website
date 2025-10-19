<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Settings.php';

$lang = new LanguageManager();
$user = new User();
$settings = new Settings();

// Check if registration is allowed
if (!$settings->isRegistrationAllowed()) {
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
        <title>Registrierung deaktiviert - <?php echo $settings->getWebsiteName(); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    </head>
    <body>
        <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-user-lock" style="font-size: 5rem; color: var(--ocean-blue);"></i>
                </div>
                <h1 class="mb-4">🚫 Registrierung deaktiviert</h1>
                <p class="lead mb-4">
                    Neue Registrierungen sind derzeit nicht möglich.<br>
                    Bitte kontaktieren Sie den Administrator für weitere Informationen.
                </p>
                <p class="text-muted">
                    Kontakt: <a href="mailto:<?php echo $settings->getAdminEmail(); ?>"><?php echo $settings->getAdminEmail(); ?></a>
                </p>
                <div class="mt-4">
                    <a href="/ocean/shop/login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i>Zur Anmeldung
                    </a>
                    <a href="/ocean/shop" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-home me-1"></i>Zur Startseite
                    </a>
                </div>
            </div>
        </div>
        
        <script src="/ocean/shop/assets/js/theme.js"></script>
    </body>
    </html>
    <?php
    exit;
}

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
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Input validation
        if (empty($name) || empty($email) || empty($password)) {
            $message = 'Bitte alle Felder ausfüllen';
            $messageType = 'danger';
        } elseif (strlen($name) < 2 || strlen($name) > 50) {
            $message = 'Name muss zwischen 2 und 50 Zeichen lang sein';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Ungültige E-Mail-Adresse';
            $messageType = 'danger';
        } elseif (strlen($password) < 8) {
            $message = 'Passwort muss mindestens 8 Zeichen lang sein';
            $messageType = 'danger';
        } elseif ($password !== $confirm_password) {
            $message = 'Passwörter stimmen nicht überein';
            $messageType = 'danger';
        } else {
            // Password strength check
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $message = 'Passwort muss mindestens einen Großbuchstaben, einen Kleinbuchstaben und eine Zahl enthalten';
                $messageType = 'danger';
            } else {
                $result = $user->register($name, $email, $password);
                if ($result['success']) {
                    $message = 'Konto erfolgreich erstellt! Sie können sich jetzt anmelden.';
                    $messageType = 'success';
                    // Clear form data on success
                    $name = $email = '';
                } else {
                    $message = $result['message'];
                    $messageType = 'danger';
                }
            }
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
    <title><?php echo t('register'); ?> - Ocean Hosting</title>
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
        .password-strength {
            font-size: 0.85em;
            margin-top: 0.25rem;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
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
                        <a class="nav-link" href="/ocean/shop/login"><?php echo t('login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/ocean/shop/register"><?php echo t('register'); ?></a>
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
                        <h2 class="text-center mb-4"><?php echo t('register'); ?></h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
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
                                <label for="name" class="form-label"><?php echo t('name'); ?> *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required minlength="2" maxlength="50" autocomplete="name"
                                       value="<?php echo htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">Zwischen 2 und 50 Zeichen</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo t('email'); ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       required maxlength="255" autocomplete="email"
                                       value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">Gültige E-Mail-Adresse erforderlich</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo t('password'); ?> *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8" maxlength="255" autocomplete="new-password">
                                <div id="passwordStrength" class="password-strength"></div>
                                <div class="form-text">
                                    Mindestens 8 Zeichen mit Groß-, Kleinbuchstaben und Zahl
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?> *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       required minlength="8" maxlength="255" autocomplete="new-password">
                                <div id="passwordMatch" class="form-text"></div>
                            </div>
                            
                            <!-- Security Info -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt"></i> 
                                    Ihre Daten werden sicher verschlüsselt und DSGVO-konform verarbeitet
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-gaming w-100" id="registerBtn" disabled>
                                <i class="fas fa-user-plus"></i> <?php echo t('create_account'); ?>
                            </button>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <p>Bereits ein Konto? <a href="/ocean/shop/login"><?php echo t('login'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Management -->
    <script src="/ocean/shop/assets/js/theme.js"></script>
    
    <!-- Registration Form Enhancement -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('registerBtn');
            const strengthDiv = document.getElementById('passwordStrength');
            const matchDiv = document.getElementById('passwordMatch');
            
            function checkPasswordStrength(pass) {
                let strength = 0;
                let feedback = '';
                
                if (pass.length >= 8) strength++;
                if (pass.match(/[a-z]/)) strength++;
                if (pass.match(/[A-Z]/)) strength++;
                if (pass.match(/[0-9]/)) strength++;
                if (pass.match(/[^a-zA-Z0-9]/)) strength++;
                
                switch (strength) {
                    case 0:
                    case 1:
                        feedback = '<span class="strength-weak">Sehr schwach</span>';
                        break;
                    case 2:
                    case 3:
                        feedback = '<span class="strength-medium">Mittel</span>';
                        break;
                    case 4:
                    case 5:
                        feedback = '<span class="strength-strong">Stark</span>';
                        break;
                }
                
                strengthDiv.innerHTML = 'Passwort-Stärke: ' + feedback;
                return strength >= 3;
            }
            
            function checkPasswordMatch() {
                if (confirmPassword.value === '') {
                    matchDiv.innerHTML = '';
                    return false;
                }
                
                if (password.value === confirmPassword.value) {
                    matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Passwörter stimmen überein</span>';
                    return true;
                } else {
                    matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> Passwörter stimmen nicht überein</span>';
                    return false;
                }
            }
            
            function validateForm() {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const isStrongPassword = checkPasswordStrength(password.value);
                const passwordsMatch = checkPasswordMatch();
                const isValidEmail = email.includes('@') && email.includes('.');
                
                const isValid = name.length >= 2 && isValidEmail && isStrongPassword && passwordsMatch;
                submitBtn.disabled = !isValid;
            }
            
            // Real-time validation
            password.addEventListener('input', validateForm);
            confirmPassword.addEventListener('input', validateForm);
            document.getElementById('name').addEventListener('input', validateForm);
            document.getElementById('email').addEventListener('input', validateForm);
            
            // Form submission enhancement
            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wird erstellt...';
            });
        });
    </script>
</body>
</html>