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
    <html data-theme="light">
    <head>
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
    header('Location: ../index.php');
    exit;
}

$message = '';
$messageType = '';

if ($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($password)) {
        if ($password === $confirm_password) {
            $result = $user->register($name, $email, $password);
            if ($result['success']) {
                $message = 'Konto erfolgreich erstellt! Sie können sich jetzt anmelden.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        } else {
            $message = 'Passwörter stimmen nicht überein';
            $messageType = 'danger';
        }
    } else {
        $message = 'Bitte alle Felder ausfüllen';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register'); ?> - Ocean Hosting</title>
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
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo t('name'); ?></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo t('email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo t('password'); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-gaming w-100"><?php echo t('create_account'); ?></button>
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
</body>
</html>