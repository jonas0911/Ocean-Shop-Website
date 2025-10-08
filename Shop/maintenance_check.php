<?php
// Maintenance Mode Check - Include at the top of pages
session_start();
require_once __DIR__ . '/includes/Settings.php';

$settings = new Settings();

// Check if maintenance mode is enabled and user is not admin
if ($settings->isMaintenanceMode()) {
    // Allow admins to bypass maintenance mode
    require_once __DIR__ . '/includes/User.php';
    $user = new User();
    
    if (!$user->isLoggedIn() || !$user->isAdmin()) {
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html data-theme="light">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Wartung - <?php echo $settings->getWebsiteName(); ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
        </head>
        <body>
            <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-tools" style="font-size: 5rem; color: var(--ocean-blue);"></i>
                    </div>
                    <h1 class="mb-4">🔧 Wartungsmodus</h1>
                    <p class="lead mb-4">
                        <?php echo $settings->getWebsiteName(); ?> befindet sich derzeit im Wartungsmodus.<br>
                        Wir arbeiten an Verbesserungen und sind bald wieder da!
                    </p>
                    <p class="text-muted">
                        Bei Fragen wenden Sie sich an: <a href="mailto:<?php echo $settings->getAdminEmail(); ?>"><?php echo $settings->getAdminEmail(); ?></a>
                    </p>
                    <div class="mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="/ocean/shop/assets/js/theme.js"></script>
        </body>
        </html>
        <?php
        exit;
    }
}
?>