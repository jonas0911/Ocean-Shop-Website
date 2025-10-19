<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/LanguageManager.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de';
}

$lang = new LanguageManager();

// Get order ID from URL
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$orderId) {
    header('Location: /ocean/shop');
    exit;
}

// Get order details
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: /ocean/shop');
    exit;
}

// Get order items
$itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Bestellung erfolgreich - Ocean Hosting</title>
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
        </div>
    </nav>

    <!-- Success Content -->
    <div class="container py-5 mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Success Message -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center py-5">
                        <div class="text-success mb-4">
                            <i class="fas fa-check-circle fa-5x"></i>
                        </div>
                        <h1 class="text-success mb-3">Bestellung erfolgreich!</h1>
                        <p class="lead mb-4">
                            Vielen Dank für Ihre Bestellung bei Ocean Hosting. 
                            Ihre Zahlung wurde erfolgreich verarbeitet.
                        </p>
                        <div class="alert alert-info">
                            <strong>Bestellnummer:</strong> #<?php echo $orderId; ?><br>
                            <strong>PayPal Transaction ID:</strong> <?php echo htmlspecialchars($order['paypal_order_id']); ?>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-receipt me-2"></i>Bestelldetails</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Kundendaten:</h6>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']); ?></strong></p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p class="mb-0"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                                <p class="mb-0"><?php echo htmlspecialchars($order['customer_zip'] . ' ' . $order['customer_city']); ?></p>
                                <p class="mb-0"><?php echo htmlspecialchars($order['customer_country']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Bestellinformationen:</h6>
                                <p class="mb-1"><strong>Datum:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Bezahlt</span></p>
                                <p class="mb-0"><strong>Gesamtbetrag:</strong> <?php echo number_format($order['total_amount'], 2, ',', '.'); ?>€</p>
                            </div>
                        </div>

                        <h6>Bestellte Artikel:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Artikel</th>
                                        <th>Menge</th>
                                        <th>Preis</th>
                                        <th>Gesamt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['game_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item['price'], 2, ',', '.'); ?>€</td>
                                        <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?>€</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="3">Gesamtsumme:</th>
                                        <th><?php echo number_format($order['total_amount'], 2, ',', '.'); ?>€</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Wie geht es weiter?</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h6>1. E-Mail Bestätigung</h6>
                                <p class="small">Sie erhalten eine Bestellbestätigung per E-Mail.</p>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-cogs fa-2x text-primary mb-2"></i>
                                <h6>2. Server Setup</h6>
                                <p class="small">Wir richten Ihren Game Server ein.</p>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <i class="fas fa-rocket fa-2x text-primary mb-2"></i>
                                <h6>3. Server Start</h6>
                                <p class="small">Sie erhalten die Zugangsdaten binnen 24h.</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-clock me-2"></i>Setup-Zeit:</strong> 
                            Ihr Server wird normalerweise innerhalb von 2-24 Stunden eingerichtet. 
                            Bei Fragen kontaktieren Sie unseren Support.
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="/ocean/shop" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-home me-2"></i>Zur Startseite
                    </a>
                    <a href="/ocean/shop/contact" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-headset me-2"></i>Support kontaktieren
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script src="/ocean/shop/assets/js/language.js"></script>
</body>
</html>