<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Cart.php';
require_once __DIR__ . '/../includes/Settings.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
} elseif (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de';
}

$lang = new LanguageManager();
$user = new User();
$cart = new Cart();
$settings = new Settings();

// Redirect if cart is empty
$cartItems = $cart->getItems();
if (empty($cartItems)) {
    header('Location: /ocean/shop/cart');
    exit;
}

$cartTotal = $cart->getTotal();
$taxRate = $settings->getTaxRate() / 100; // Convert percentage to decimal
$cartTax = $cartTotal * $taxRate;

$cartTotalWithTax = $cartTotal + $cartTax;

// Get user address data if logged in
$userAddressData = ['first_name' => '', 'last_name' => '', 'address' => '', 'city' => '', 'zip' => '', 'country' => 'DE'];
if ($user->isLoggedIn()) {
    $currentUser = $user->getCurrentUser();
    $userAddressData = $user->getUserAddressData($currentUser['id']);
}

// PayPal Configuration from Settings
$paypal_client_id = $settings->getPayPalClientId();
$paypal_mode = $settings->getPayPalMode();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('checkout'); ?> - Ocean Hosting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id; ?>&currency=EUR&intent=capture"></script>
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
                        <a class="nav-link active" href="/ocean/shop/checkout"><?php echo t('checkout'); ?></a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-language"></i> <?php echo strtoupper($lang->getCurrentLanguage()); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=de"><i class="fas fa-flag"></i> Deutsch</a></li>
                            <li><a class="dropdown-item" href="?lang=en"><i class="fas fa-flag"></i> English</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm me-2" id="theme-toggle">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </button>
                    </li>
                    
                    <?php if ($user->isLoggedIn()): ?>
                        <?php $currentUser = $user->getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $currentUser['name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> <?php echo t('account'); ?></a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-crown"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/login"><?php echo t('login'); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5 mt-4">
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4><i class="fas fa-credit-card me-2"></i><?php echo t('checkout'); ?></h4>
                    </div>
                    <div class="card-body">
                        <!-- Customer Information -->
                        <div class="mb-4">
                            <h5><i class="fas fa-user me-2"></i>Kundendaten</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">Vorname *</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" autocomplete="given-name" value="<?php echo htmlspecialchars($userAddressData['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Nachname *</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" autocomplete="family-name" value="<?php echo htmlspecialchars($userAddressData['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-Mail *</label>
                                <input type="email" class="form-control" id="email" name="email" autocomplete="email" value="<?php echo $user->isLoggedIn() ? htmlspecialchars($currentUser['email']) : ''; ?>" required>
                                <div id="email-feedback" class="form-text"></div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="mb-4">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Rechnungsadresse</h5>
                            
                            <!-- Zentrales Adress-Status Info-Feld -->
                            <div class="alert alert-info d-none py-2 px-3 mb-2" id="address-status-info" style="font-size: 0.85rem; border-width: 1px;">
                                <div class="d-flex align-items-center">
                                    <div id="address-status-icon" class="me-2" style="font-size: 0.8rem;">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div id="address-status-text" class="flex-grow-1">
                                        Adressvalidierung läuft...
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Straße und Hausnummer *</label>
                                <input type="text" class="form-control" id="address" name="address" autocomplete="street-address" value="<?php echo htmlspecialchars($userAddressData['address']); ?>" required>
                                <div id="address-feedback" class="form-text"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">PLZ *</label>
                                    <input type="text" class="form-control" id="zip" name="zip" autocomplete="postal-code" value="<?php echo htmlspecialchars($userAddressData['zip']); ?>" required>
                                    <div id="zip-feedback" class="form-text"></div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="city" class="form-label">Stadt *</label>
                                    <input type="text" class="form-control" id="city" name="city" autocomplete="address-level2" value="<?php echo htmlspecialchars($userAddressData['city']); ?>" required>
                                    <div id="city-feedback" class="form-text"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="country" class="form-label">Land *</label>
                                <select class="form-control" id="country" name="country" autocomplete="country" required>
                                    <!-- Europa -->
                                    <optgroup label="Europa">
                                        <option value="DE" <?php echo ($userAddressData['country'] == 'DE') ? 'selected' : ''; ?>>🇩🇪 Deutschland</option>
                                        <option value="AT" <?php echo ($userAddressData['country'] == 'AT') ? 'selected' : ''; ?>>🇦🇹 Österreich</option>
                                        <option value="CH" <?php echo ($userAddressData['country'] == 'CH') ? 'selected' : ''; ?>>🇨🇭 Schweiz</option>
                                        <option value="BE" <?php echo ($userAddressData['country'] == 'BE') ? 'selected' : ''; ?>>🇧🇪 Belgien</option>
                                        <option value="NL" <?php echo ($userAddressData['country'] == 'NL') ? 'selected' : ''; ?>>🇳🇱 Niederlande</option>
                                        <option value="FR" <?php echo ($userAddressData['country'] == 'FR') ? 'selected' : ''; ?>>🇫🇷 Frankreich</option>
                                        <option value="IT" <?php echo ($userAddressData['country'] == 'IT') ? 'selected' : ''; ?>>🇮🇹 Italien</option>
                                        <option value="ES" <?php echo ($userAddressData['country'] == 'ES') ? 'selected' : ''; ?>>🇪🇸 Spanien</option>
                                        <option value="PT" <?php echo ($userAddressData['country'] == 'PT') ? 'selected' : ''; ?>>🇵🇹 Portugal</option>
                                        <option value="GB" <?php echo ($userAddressData['country'] == 'GB') ? 'selected' : ''; ?>>🇬🇧 Großbritannien</option>
                                        <option value="IE" <?php echo ($userAddressData['country'] == 'IE') ? 'selected' : ''; ?>>🇮🇪 Irland</option>
                                        <option value="DK" <?php echo ($userAddressData['country'] == 'DK') ? 'selected' : ''; ?>>🇩🇰 Dänemark</option>
                                        <option value="SE" <?php echo ($userAddressData['country'] == 'SE') ? 'selected' : ''; ?>>🇸🇪 Schweden</option>
                                        <option value="NO" <?php echo ($userAddressData['country'] == 'NO') ? 'selected' : ''; ?>>🇳🇴 Norwegen</option>
                                        <option value="FI" <?php echo ($userAddressData['country'] == 'FI') ? 'selected' : ''; ?>>🇫🇮 Finnland</option>
                                        <option value="PL" <?php echo ($userAddressData['country'] == 'PL') ? 'selected' : ''; ?>>🇵🇱 Polen</option>
                                        <option value="CZ" <?php echo ($userAddressData['country'] == 'CZ') ? 'selected' : ''; ?>>🇨🇿 Tschechien</option>
                                        <option value="SK" <?php echo ($userAddressData['country'] == 'SK') ? 'selected' : ''; ?>>🇸🇰 Slowakei</option>
                                        <option value="HU" <?php echo ($userAddressData['country'] == 'HU') ? 'selected' : ''; ?>>🇭🇺 Ungarn</option>
                                        <option value="SI" <?php echo ($userAddressData['country'] == 'SI') ? 'selected' : ''; ?>>🇸🇮 Slowenien</option>
                                        <option value="HR" <?php echo ($userAddressData['country'] == 'HR') ? 'selected' : ''; ?>>🇭🇷 Kroatien</option>
                                        <option value="RO" <?php echo ($userAddressData['country'] == 'RO') ? 'selected' : ''; ?>>🇷🇴 Rumänien</option>
                                        <option value="BG" <?php echo ($userAddressData['country'] == 'BG') ? 'selected' : ''; ?>>🇧🇬 Bulgarien</option>
                                        <option value="GR" <?php echo ($userAddressData['country'] == 'GR') ? 'selected' : ''; ?>>🇬🇷 Griechenland</option>
                                        <option value="LU" <?php echo ($userAddressData['country'] == 'LU') ? 'selected' : ''; ?>>🇱🇺 Luxemburg</option>
                                    </optgroup>
                                    
                                    <!-- Nordamerika -->
                                    <optgroup label="Nordamerika">
                                        <option value="US" <?php echo ($userAddressData['country'] == 'US') ? 'selected' : ''; ?>>🇺🇸 USA</option>
                                        <option value="CA" <?php echo ($userAddressData['country'] == 'CA') ? 'selected' : ''; ?>>🇨🇦 Kanada</option>
                                        <option value="MX" <?php echo ($userAddressData['country'] == 'MX') ? 'selected' : ''; ?>>🇲🇽 Mexiko</option>
                                    </optgroup>
                                    
                                    <!-- Asien-Pazifik -->
                                    <optgroup label="Asien-Pazifik">
                                        <option value="JP" <?php echo ($userAddressData['country'] == 'JP') ? 'selected' : ''; ?>>🇯🇵 Japan</option>
                                        <option value="KR" <?php echo ($userAddressData['country'] == 'KR') ? 'selected' : ''; ?>>🇰🇷 Südkorea</option>
                                        <option value="CN" <?php echo ($userAddressData['country'] == 'CN') ? 'selected' : ''; ?>>🇨🇳 China</option>
                                        <option value="IN" <?php echo ($userAddressData['country'] == 'IN') ? 'selected' : ''; ?>>🇮🇳 Indien</option>
                                        <option value="SG" <?php echo ($userAddressData['country'] == 'SG') ? 'selected' : ''; ?>>🇸🇬 Singapur</option>
                                        <option value="AU" <?php echo ($userAddressData['country'] == 'AU') ? 'selected' : ''; ?>>🇦🇺 Australien</option>
                                        <option value="NZ" <?php echo ($userAddressData['country'] == 'NZ') ? 'selected' : ''; ?>>🇳🇿 Neuseeland</option>
                                        <option value="TH" <?php echo ($userAddressData['country'] == 'TH') ? 'selected' : ''; ?>>🇹🇭 Thailand</option>
                                        <option value="MY" <?php echo ($userAddressData['country'] == 'MY') ? 'selected' : ''; ?>>🇲🇾 Malaysia</option>
                                        <option value="ID" <?php echo ($userAddressData['country'] == 'ID') ? 'selected' : ''; ?>>🇮🇩 Indonesien</option>
                                        <option value="PH" <?php echo ($userAddressData['country'] == 'PH') ? 'selected' : ''; ?>>🇵🇭 Philippinen</option>
                                        <option value="VN" <?php echo ($userAddressData['country'] == 'VN') ? 'selected' : ''; ?>>🇻🇳 Vietnam</option>
                                        <option value="TW" <?php echo ($userAddressData['country'] == 'TW') ? 'selected' : ''; ?>>🇹🇼 Taiwan</option>
                                        <option value="HK" <?php echo ($userAddressData['country'] == 'HK') ? 'selected' : ''; ?>>🇭🇰 Hongkong</option>
                                    </optgroup>
                                    
                                    <!-- Südamerika -->
                                    <optgroup label="Südamerika">
                                        <option value="BR" <?php echo ($userAddressData['country'] == 'BR') ? 'selected' : ''; ?>>🇧🇷 Brasilien</option>
                                        <option value="AR" <?php echo ($userAddressData['country'] == 'AR') ? 'selected' : ''; ?>>🇦🇷 Argentinien</option>
                                        <option value="CL" <?php echo ($userAddressData['country'] == 'CL') ? 'selected' : ''; ?>>🇨🇱 Chile</option>
                                        <option value="CO" <?php echo ($userAddressData['country'] == 'CO') ? 'selected' : ''; ?>>🇨🇴 Kolumbien</option>
                                        <option value="PE" <?php echo ($userAddressData['country'] == 'PE') ? 'selected' : ''; ?>>🇵🇪 Peru</option>
                                        <option value="UY" <?php echo ($userAddressData['country'] == 'UY') ? 'selected' : ''; ?>>🇺🇾 Uruguay</option>
                                    </optgroup>
                                    
                                    <!-- Afrika & Naher Osten -->
                                    <optgroup label="Afrika & Naher Osten">
                                        <option value="ZA" <?php echo ($userAddressData['country'] == 'ZA') ? 'selected' : ''; ?>>🇿🇦 Südafrika</option>
                                        <option value="IL" <?php echo ($userAddressData['country'] == 'IL') ? 'selected' : ''; ?>>🇮🇱 Israel</option>
                                        <option value="AE" <?php echo ($userAddressData['country'] == 'AE') ? 'selected' : ''; ?>>🇦🇪 Vereinigte Arabische Emirate</option>
                                        <option value="SA" <?php echo ($userAddressData['country'] == 'SA') ? 'selected' : ''; ?>>🇸🇦 Saudi-Arabien</option>
                                        <option value="TR" <?php echo ($userAddressData['country'] == 'TR') ? 'selected' : ''; ?>>🇹🇷 Türkei</option>
                                        <option value="EG" <?php echo ($userAddressData['country'] == 'EG') ? 'selected' : ''; ?>>🇪🇬 Ägypten</option>
                                    </optgroup>
                                    
                                    <!-- Sonstige -->
                                    <optgroup label="Sonstige">
                                        <option value="RU" <?php echo ($userAddressData['country'] == 'RU') ? 'selected' : ''; ?>>🇷🇺 Russland</option>
                                        <option value="UA" <?php echo ($userAddressData['country'] == 'UA') ? 'selected' : ''; ?>>🇺🇦 Ukraine</option>
                                        <option value="BY" <?php echo ($userAddressData['country'] == 'BY') ? 'selected' : ''; ?>>🇧🇾 Belarus</option>
                                        <option value="OTHER" <?php echo ($userAddressData['country'] == 'OTHER') ? 'selected' : ''; ?>>🌍 Anderes Land</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                                <label class="form-check-label" for="acceptTerms">
                                    Ich akzeptiere die <a href="/ocean/shop/terms" target="_blank">AGB</a> und <a href="/ocean/shop/privacy" target="_blank">Datenschutzbestimmungen</a> *
                                </label>
                            </div>
                            <?php if ($user->isLoggedIn()): ?>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="saveAddress" checked>
                                <label class="form-check-label" for="saveAddress">
                                    <i class="fas fa-save me-1"></i> Adresse für nächstes Mal speichern
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top">
                    <div class="card-header">
                        <h5><i class="fas fa-receipt me-2"></i>Bestellübersicht</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['game_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        RAM: <?php echo $item['ram']; ?> GB | 
                                        Laufzeit: <?php 
                                            switch($item['duration']) {
                                                case '3_days': echo '3 Tage'; break;
                                                case '1_week': echo '1 Woche'; break;
                                                case '1_month': echo '1 Monat'; break;
                                                default: echo $item['duration'];
                                            }
                                        ?> | 
                                        Menge: <?php echo $item['quantity']; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?>€
                                </div>
                            </div>
                            <hr>
                        <?php endforeach; ?>

                        <!-- Totals -->
                        <div class="d-flex justify-content-between">
                            <span>Zwischensumme:</span>
                            <span><?php echo number_format($cartTotal, 2, ',', '.'); ?>€</span>
                        </div>
                        <?php if ($settings->getTaxRate() > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>MwSt (<?php echo $settings->getTaxRate(); ?>%):</span>
                            <span><?php echo number_format($cartTax, 2, ',', '.'); ?>€</span>
                        </div>
                        <hr>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-3">
                            <strong class="h5">Gesamtsumme:</strong>
                            <strong class="h5 text-primary"><?php echo number_format($cartTotalWithTax, 2, ',', '.'); ?>€</strong>
                        </div>

                        <!-- PayPal Button -->
                        <div id="paypal-button-container" class="d-none"></div>
                        
                        <!-- Continue Button -->
                        <button class="btn btn-primary btn-lg w-100" id="continueBtn" onclick="validateAndShowPayPal()" disabled>
                            <i class="fas fa-lock me-2"></i>Zur Zahlung
                        </button>
                        
                        <!-- Demo/Test Button -->
                        <button class="btn btn-secondary btn-lg w-100 mt-2" id="demoOrderBtn" onclick="processDemoOrder()" disabled>
                            <i class="fas fa-exclamation-triangle me-2"></i>Felder ausfüllen für Demo
                        </button>
                        
                        <!-- Back to Cart -->
                        <a href="/ocean/shop/cart" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left me-2"></i>Zurück zum Warenkorb
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script src="/ocean/shop/assets/js/language.js"></script>
    
    <script>
        let paypalRendered = false;

        function showValidationErrors() {
            // Just highlight invalid fields - no popup messages, no auto-focus
            const firstInvalidField = document.querySelector('.is-invalid');
            if (firstInvalidField) {
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Removed auto-focus so field doesn't automatically clear red border
            }
        }
        
        // Enhanced validation with real-time checks
        let validationTimers = {};
        
        // Name validation regex (nur Buchstaben, Leerzeichen, Bindestriche, Apostrophe)
        const nameRegex = /^[a-zA-ZäöüßÄÖÜ\s\-']+$/;
        
        // Email validation regex (erweitert)
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        
        // Address validation (flexibler für verschiedene Adressformate)
        const addressRegex = /^[a-zA-ZäöüßÄÖÜ0-9\s\-\.,]+$/;
        
        function updateFieldStatus(fieldId, status, message = '') {
            const field = document.getElementById(fieldId);
            const statusIcon = document.getElementById(fieldId + '-status');
            const feedback = document.getElementById(fieldId + '-feedback');
            
            // Setze CSS-Klassen am Feld
            if (field) {
                field.classList.remove('is-valid', 'is-invalid');
                if (status === 'valid') {
                    field.classList.add('is-valid');
                } else if (status === 'invalid') {
                    field.classList.add('is-invalid');
                }
            }
            
            if (statusIcon) {
                statusIcon.innerHTML = '';
                switch(status) {
                    case 'loading':
                        statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin text-info"></i>';
                        break;
                    case 'valid':
                        statusIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                        break;
                    case 'invalid':
                        statusIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
                        break;
                    default:
                        statusIcon.innerHTML = '<i class="fas fa-question-circle text-muted"></i>';
                }
            }
            
            if (feedback) {
                feedback.textContent = message;
                feedback.className = 'form-text ' + (status === 'invalid' ? 'text-danger' : status === 'valid' ? 'text-success' : 'text-muted');
            }
            
            // Nach jeder Feld-Update prüfen ob alle Felder valid sind
            checkAllFieldsValid();
        }
        
        // Prüfe ob alle Pflichtfelder korrekt validiert sind
        function checkAllFieldsValid() {
            const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            const continueBtn = document.getElementById('continueBtn');
            const demoBtn = document.getElementById('demoOrderBtn');
            
            if (!continueBtn) return;
            
            let allValid = true;
            console.log('=== Checking all fields validity ===');
            
            for (const fieldId of requiredFields) {
                const field = document.getElementById(fieldId);
                
                if (!field) {
                    console.log('Field not found:', fieldId);
                    allValid = false;
                    break;
                }
                
                // Prüfe ob Feld ausgefüllt ist
                if (!field.value.trim()) {
                    console.log('Field empty:', fieldId);
                    allValid = false;
                    break;
                }
                
                // Prüfe ob Feld als valid markiert ist (grüner Rahmen)
                if (!field.classList.contains('is-valid')) {
                    console.log('Field not valid:', fieldId, 'Classes:', field.className);
                    allValid = false;
                    break;
                } else {
                    console.log('Field OK:', fieldId, 'Value:', field.value.trim().substring(0, 20) + '...');
                }
            }
            
            console.log('All fields valid:', allValid);
            
            // Beide Buttons aktivieren/deaktivieren
            if (allValid) {
                // Hauptbutton aktivieren
                continueBtn.disabled = false;
                continueBtn.classList.remove('btn-secondary');
                continueBtn.classList.add('btn-primary');
                continueBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Zur Zahlung';
                
                // Demo-Button aktivieren
                if (demoBtn) {
                    demoBtn.disabled = false;
                    demoBtn.classList.remove('btn-secondary');
                    demoBtn.classList.add('btn-success');
                    demoBtn.innerHTML = '<i class="fas fa-play me-2"></i>Demo-Bestellung (Test)';
                }
            } else {
                // Hauptbutton deaktivieren
                continueBtn.disabled = true;
                continueBtn.classList.remove('btn-primary');
                continueBtn.classList.add('btn-secondary');
                continueBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Alle Felder ausfüllen';
                
                // Demo-Button deaktivieren
                if (demoBtn) {
                    demoBtn.disabled = true;
                    demoBtn.classList.remove('btn-success');
                    demoBtn.classList.add('btn-secondary');
                    demoBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Felder ausfüllen für Demo';
                }
            }
        }
        
        function validateName(fieldId, value) {
            if (!value.trim()) {
                updateFieldStatus(fieldId, 'neutral');
                return false;
            }
            
            if (value.length < 2) {
                updateFieldStatus(fieldId, 'invalid', 'Name muss mindestens 2 Zeichen haben');
                return false;
            }
            
            if (!nameRegex.test(value)) {
                updateFieldStatus(fieldId, 'invalid', 'Nur Buchstaben, Leerzeichen und Bindestriche erlaubt');
                return false;
            }
            
            updateFieldStatus(fieldId, 'valid', 'Gültiger Name');
            return true;
        }
        
        function validateEmail(value) {
            const emailField = document.getElementById('email');
            const feedback = document.getElementById('email-feedback');
            
            if (!value.trim()) {
                emailField.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
                return false;
            }
            
            if (!emailRegex.test(value)) {
                emailField.classList.remove('is-valid');
                emailField.classList.add('is-invalid');
                feedback.textContent = 'Ungültige E-Mail-Adresse';
                feedback.className = 'form-text text-danger';
                return false;
            }
            
            emailField.classList.remove('is-invalid');
            emailField.classList.add('is-valid');
            feedback.textContent = 'Gültige E-Mail-Adresse';
            feedback.className = 'form-text text-success';
            
            // Prüfe ob alle Felder jetzt valid sind
            checkAllFieldsValid();
            
            return true;
        }
        
        function validateAddress(value, zip, city) {
            if (!value.trim()) {
                updateFieldStatus('address', 'neutral');
                validateSmartAddress();
                return false;
            }
            
            if (!addressRegex.test(value)) {
                updateFieldStatus('address', 'invalid', 'Ungültige Zeichen in der Adresse');
                return false;
            }
            
            // Trigger smart address validation
            validateSmartAddress();
            return true;
        }
        
        // Zentrale Benachrichtigungsfunktion
        function showNotification(message, type = 'info') {
            // Erstelle eine Toast-Benachrichtigung
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove nach 5 Sekunden
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 5000);
        }

        // Zentrale Adress-Status-Verwaltung
        function showAddressStatus(type, message, details = null) {
            const statusInfo = document.getElementById('address-status-info');
            const statusIcon = document.getElementById('address-status-icon');
            const statusText = document.getElementById('address-status-text');
            
            if (!statusInfo || !statusIcon || !statusText) return;
            
            // Zeige das Status-Feld
            statusInfo.classList.remove('d-none', 'alert-info', 'alert-warning', 'alert-danger', 'alert-success');
            
            switch(type) {
                case 'loading':
                    statusInfo.classList.add('alert-info');
                    statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    break;
                case 'analyzing':
                    statusInfo.classList.add('alert-warning');
                    statusIcon.innerHTML = '<i class="fas fa-search fa-spin"></i>';
                    break;
                case 'error':
                    statusInfo.classList.add('alert-danger');
                    statusIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                case 'partial':
                    statusInfo.classList.add('alert-warning');
                    statusIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                    break;
                case 'success':
                    statusInfo.classList.add('alert-success');
                    statusIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    break;
                default:
                    statusInfo.classList.add('alert-info');
                    statusIcon.innerHTML = '<i class="fas fa-info-circle"></i>';
            }
            
            statusText.innerHTML = message;
        }
        
        function hideAddressStatus() {
            const statusInfo = document.getElementById('address-status-info');
            if (statusInfo) {
                statusInfo.classList.add('d-none');
            }
        }
        
        // Zeige Loading-Indikatoren während Adressvalidierung
        function showAddressValidationLoading() {
            const addressField = document.getElementById('address');
            const zipField = document.getElementById('zip');
            const cityField = document.getElementById('city');
            
            // Entferne alle individuellen Feedback-Nachrichten
            clearIndividualFeedback();
            
            // Entferne Validierungsklassen
            [addressField, zipField, cityField].forEach(field => {
                if (field) {
                    field.classList.remove('is-valid', 'is-invalid');
                }
            });
            
            // Zeige zentrale Loading-Nachricht
            showAddressStatus('loading', 'Validiere Adresse, PLZ und Stadt...');
        }
        
        // Entferne alle individuellen Feedback-Nachrichten
        function clearIndividualFeedback() {
            const addressFeedback = document.getElementById('address-feedback');
            const zipFeedback = document.getElementById('zip-feedback');
            const cityFeedback = document.getElementById('city-feedback');
            
            [addressFeedback, zipFeedback, cityFeedback].forEach(feedback => {
                if (feedback) {
                    feedback.innerHTML = '';
                    feedback.className = 'form-text';
                }
            });
        }
        
        // Entferne Loading-Indikatoren
        function hideAddressValidationLoading() {
            clearIndividualFeedback();
            hideAddressStatus();
        }
        
        // Intelligente Adressvalidierung - erkennt automatisch welche Felder ausgefüllt sind
        let addressValidationTimeout;
        let addressValidationRunning = false;
        
        function validateSmartAddress() {
            // Verhindere mehrfache gleichzeitige Ausführung
            if (addressValidationRunning) {
                console.log('Address validation already running, skipping...');
                return;
            }
            
            // Clear existing timeout
            if (addressValidationTimeout) {
                clearTimeout(addressValidationTimeout);
            }
            
            // Debounce - warte 800ms nach letzter Eingabe
            addressValidationTimeout = setTimeout(() => {
                executeAddressValidation();
            }, 800);
        }
        
        function executeAddressValidation() {
            if (addressValidationRunning) return;
            
            addressValidationRunning = true;
            console.log('Starting address validation...');
            
            // Zeige Loading-Indikatoren für alle Adressfelder
            showAddressValidationLoading();
            
            const addressField = document.getElementById('address');
            const zipField = document.getElementById('zip');
            const cityField = document.getElementById('city');
            
            const addressFeedback = document.getElementById('address-feedback');
            const zipFeedback = document.getElementById('zip-feedback');
            const cityFeedback = document.getElementById('city-feedback');
            
            let address = addressField ? addressField.value.trim() : '';
            const zip = zipField ? zipField.value.trim() : '';
            const city = cityField ? cityField.value.trim() : '';
            
            // Speichere originale Adresse für spätere API-basierte Korrektur
            const originalAddress = address;
            
            console.log('Smart validation - Address:', address, 'ZIP:', zip, 'City:', city);
            
            // Prüfe ob Adresse eine Hausnummer enthält
            if (address && address.length > 0) {
                const hasHouseNumber = /\d+/.test(address);
                if (!hasHouseNumber) {
                    // Setze Adressfeld auf invalid
                    if (addressField && addressFeedback) {
                        addressField.classList.remove('is-valid');
                        addressField.classList.add('is-invalid');
                        addressFeedback.textContent = 'Bitte geben Sie eine Hausnummer ein (z.B. Musterstraße 12)';
                        addressFeedback.className = 'form-text text-danger';
                    }
                    
                    // Reset validation flag
                    addressValidationRunning = false;
                    return;
                }
            }
            
            // Verschiedene Validierungsstrategien je nach verfügbaren Feldern
            let queries = [];
            
            // WICHTIG: Immer Kombinationsvalidierung wenn mindestens 2 Felder vorhanden
            
            // 1. Alle drei Felder vorhanden - Parallele Einzelvalidierung, dann Kombination
            if (address && zip && /^\d{5}$/.test(zip) && city && city.length >= 2) {
                console.log('Testing all three fields: individual validation first, then combination');
                
                // Validiere alle drei Felder parallel
                const zipUrl = `https://nominatim.openstreetmap.org/search?postalcode=${zip}&country=DE&format=json&limit=5`;
                const cityUrl = `https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(city)}&country=DE&format=json&limit=5`;
                const addressUrl = `https://nominatim.openstreetmap.org/search?format=json&street=${encodeURIComponent(address)}&country=DE&limit=5`;
                
                console.log('ZIP validation URL:', zipUrl);
                console.log('City validation URL:', cityUrl);
                console.log('Address validation URL:', addressUrl);
                
                // Sequenzielle Requests (der Reihe nach) um Server-Überlastung zu vermeiden
                async function validateSequentially() {
                    try {
                        // ZIP prüfen
                        const zipResponse = await fetch(zipUrl);
                        const zipResult = zipResponse.ok ? await zipResponse.json() : [];
                        
                        // Kurze Pause zwischen Requests
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        // Stadt prüfen
                        const cityResponse = await fetch(cityUrl);
                        const cityResult = cityResponse.ok ? await cityResponse.json() : [];
                        
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        // Adresse prüfen
                        const addressResponse = await fetch(addressUrl);
                        const addressResult = addressResponse.ok ? await addressResponse.json() : [];
                        
                        return { zipResult, cityResult, addressResult };
                    } catch (error) {
                        console.error('Validation error:', error);
                        return { zipResult: [], cityResult: [], addressResult: [] };
                    }
                }
                
                validateSequentially().then(({ zipResult, cityResult, addressResult }) => {
                    
                    let zipValid = false;
                    let cityValid = false;
                    let addressValid = false;
                    
                    // PLZ Validierung - Großzügige Einzelvalidierung
                    if (zipResult && zipResult.length > 0) {
                        console.log('ZIP validation result:', zipResult);
                        zipValid = true;
                    } else {
                        console.log('ZIP not found individually - will verify in combination');
                        // Akzeptiere erstmal als möglicherweise gültig
                        zipValid = true;
                    }
                    
                    // Stadt Validierung - Großzügige Einzelvalidierung
                    if (cityResult && cityResult.length > 0) {
                        console.log('City validation result:', cityResult);
                        cityValid = true;
                        console.log('City accepted (individual validation)');
                    } else {
                        console.log('City not found individually - will verify in combination');
                        // Akzeptiere erstmal als möglicherweise gültig
                        cityValid = true;
                    }
                    
                    // Adresse Validierung - Großzügige Einzelvalidierung (Kombinationsvalidierung ist maßgeblich)
                    if (addressResult && addressResult.length > 0) {
                        console.log('Address validation result:', addressResult);
                        
                        // Wenn API überhaupt Ergebnisse zurückgibt, akzeptiere als möglicherweise gültig
                        // Die finale Validierung erfolgt in der Kombinationsabfrage
                        addressValid = true;
                        console.log('Address accepted (individual validation) - will be verified in combination check');
                    } else {
                        console.log('No address results from API - possibly invalid street name');
                        // Trotzdem erstmal als gültig markieren und Kombinationsvalidierung abwarten
                        addressValid = true;
                    }
                    
                    // Teste IMMER die Kombination - API ist oft besser bei Kombinationsabfragen
                    console.log('Testing combination with results - ZIP:', zipValid, 'City:', cityValid, 'Address:', addressValid);
                    const combinationQueries = [
                        { query: `street=${encodeURIComponent(address)}&postalcode=${zip}&city=${encodeURIComponent(city)}&country=DE&format=json`, fields: ['address', 'zip', 'city'], isSpecialQuery: true }
                    ];
                    executeQueries(combinationQueries);
                    
                    // Reset validation flag
                    addressValidationRunning = false;
                });
                return;
            }
            // 2. Adresse + PLZ (ohne Stadt) - Parallele Einzelvalidierung, dann Kombination
            else if (address && zip && /^\d{5}$/.test(zip) && (!city || city.length < 2)) {
                console.log('Testing Address + ZIP: individual validation first, then combination');
                
                // Validiere beide Felder parallel
                const zipUrl = `https://nominatim.openstreetmap.org/search?postalcode=${zip}&country=DE&format=json&limit=5`;
                const addressUrl = `https://nominatim.openstreetmap.org/search?format=json&street=${encodeURIComponent(address)}&country=DE&limit=5`;
                
                console.log('ZIP validation URL:', zipUrl);
                console.log('Address validation URL:', addressUrl);
                
                // Sequenzielle Requests für Adresse + PLZ
                async function validateAddressZip() {
                    try {
                        // ZIP prüfen
                        const zipResponse = await fetch(zipUrl);
                        const zipResult = zipResponse.ok ? await zipResponse.json() : [];
                        
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        // Adresse prüfen
                        const addressResponse = await fetch(addressUrl);
                        const addressResult = addressResponse.ok ? await addressResponse.json() : [];
                        
                        return { zipResult, addressResult };
                    } catch (error) {
                        console.error('Validation error:', error);
                        return { zipResult: [], addressResult: [] };
                    }
                }
                
                validateAddressZip().then(({ zipResult, addressResult }) => {
                    
                    let zipValid = false;
                    let addressValid = false;
                    
                    // PLZ Validierung
                    if (zipResult && zipResult.length > 0) {
                        console.log('ZIP validation result:', zipResult);
                        zipValid = true;
                    } else {
                        console.log('ZIP not found');
                        if (zipField && zipFeedback) {
                            zipField.classList.remove('is-valid');
                            zipField.classList.add('is-invalid');
                            zipFeedback.textContent = 'PLZ + Adresse nicht gefunden';
                            zipFeedback.className = 'form-text text-danger';
                        }
                    }
                    
                    // Adresse Validierung
                    if (addressResult && addressResult.length > 0) {
                        console.log('Address validation result:', addressResult);
                        const expectedAddress = address.toLowerCase();
                        for (let item of addressResult) {
                            if (item.display_name && item.display_name.toLowerCase().includes(expectedAddress.split(' ')[0].toLowerCase())) {
                                addressValid = true;
                                console.log('Address found in result:', item.display_name);
                                break;
                            }
                        }
                    }
                    
                    if (!addressValid) {
                        console.log('Address not found');
                        if (addressField && addressFeedback) {
                            addressField.classList.remove('is-valid');
                            addressField.classList.add('is-invalid');
                            addressFeedback.textContent = 'Adresse nicht gefunden';
                            addressFeedback.className = 'form-text text-danger';
                        }
                    }
                    
                    // Teste IMMER die Kombination - auch wenn einzelne Daten nicht gefunden wurden
                    console.log('Testing combination regardless of individual results');
                    const combinationQueries = [
                        { query: `street=${encodeURIComponent(address)}&postalcode=${zip}&country=DE&format=json`, fields: ['address', 'zip'], isSpecialQuery: true }
                    ];
                    executeQueries(combinationQueries);
                    
                    // Reset validation flag
                    addressValidationRunning = false;
                });
                return;
            }
            // 3. PLZ + Stadt (ohne Adresse) - Parallele Validierung beider Felder
            else if ((!address || address.length < 3) && zip && /^\d{5}$/.test(zip) && city && city.length >= 2) {
                console.log('Testing both fields parallel: ZIP and City individual validation');
                
                // Validiere PLZ und Stadt parallel
                const zipUrl = `https://nominatim.openstreetmap.org/search?postalcode=${zip}&country=DE&format=json&limit=5`;
                const cityUrl = `https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(city)}&country=DE&format=json&limit=5`;
                
                console.log('ZIP validation URL:', zipUrl);
                console.log('City validation URL:', cityUrl);
                
                // Sequenzielle Requests für ZIP + Stadt
                async function validateZipCity() {
                    try {
                        // ZIP prüfen
                        const zipResponse = await fetch(zipUrl);
                        const zipResult = zipResponse.ok ? await zipResponse.json() : [];
                        
                        await new Promise(resolve => setTimeout(resolve, 300));
                        
                        // Stadt prüfen
                        const cityResponse = await fetch(cityUrl);
                        const cityResult = cityResponse.ok ? await cityResponse.json() : [];
                        
                        return { zipResult, cityResult };
                    } catch (error) {
                        console.error('Validation error:', error);
                        return { zipResult: [], cityResult: [] };
                    }
                }
                
                validateZipCity().then(({ zipResult, cityResult }) => {
                    
                    let zipValid = false;
                    let cityValid = false;
                    
                    // PLZ Validierung
                    if (zipResult && zipResult.length > 0) {
                        console.log('ZIP validation result:', zipResult);
                        zipValid = true;
                    } else {
                        console.log('ZIP not found individually');
                        // Setze NOCH KEINE Fehlermeldung - warten auf Kombinations-Test
                    }
                    
                    // Stadt Validierung
                    if (cityResult && cityResult.length > 0) {
                        console.log('City validation result:', cityResult);
                        const expectedCity = city.toLowerCase();
                        
                        for (let item of cityResult) {
                            const itemType = item.type || '';
                            const itemClass = item.class || '';
                            
                            if ((itemClass === 'place' && ['city', 'town', 'village', 'suburb'].includes(itemType)) ||
                                (itemClass === 'boundary' && itemType === 'administrative')) {
                                const foundName = (item.name || '').toLowerCase();
                                if (foundName === expectedCity) {
                                    cityValid = true;
                                    console.log('Valid city found:', item.display_name);
                                    break;
                                }
                            }
                        }
                        
                        if (!cityValid) {
                            console.log('City found but not valid type');
                        }
                    } else {
                        console.log('City API call failed or no results');
                    }
                    
                    // Setze Stadt-Fehlermeldung wenn nicht gültig
                    if (!cityValid) {
                        console.log('City not found individually');
                        // Setze NOCH KEINE Fehlermeldung - warten auf Kombinations-Test
                    }
                    
                    // Teste IMMER die Kombination - auch wenn einzelne Felder nicht gefunden wurden
                    console.log('Testing combination regardless of individual results');
                    const combinationQueries = [
                        { query: `postalcode=${zip}&city=${encodeURIComponent(city)}&country=DE&format=json`, fields: ['zip', 'city'], isSpecialQuery: true }
                    ];
                    executeQueries(combinationQueries);
                    
                    // Reset validation flag
                    addressValidationRunning = false;
                });
                return; // Früher Return
            }
            // 4. Adresse + Stadt (ohne PLZ)
            else if (address && (!zip || !/^\d{5}$/.test(zip)) && city && city.length >= 2) {
                console.log('Testing combination: Address + City');
                queries = [
                    { query: `${address}, ${city}, Germany`, fields: ['address', 'city'] },
                    { query: `${city}, ${address}, Germany`, fields: ['address', 'city'] }
                ];
            }
            // 5. Nur einzelne Felder
            else if (address && (!zip || !/^\d{5}$/.test(zip)) && (!city || city.length < 2)) {
                console.log('Testing single: Address only');
                queries = [
                    { query: `${address}, Germany`, fields: ['address'] }
                ];
            }
            else if ((!address || address.length < 3) && zip && /^\d{5}$/.test(zip) && (!city || city.length < 2)) {
                console.log('Testing single: ZIP only (strict)');
                queries = [
                    { query: `postalcode=${zip}&country=DE&format=json`, fields: ['zip'], isSpecialQuery: true }
                ];
            }
            else if ((!address || address.length < 3) && (!zip || !/^\d{5}$/.test(zip)) && city && city.length >= 2) {
                console.log('Testing single: City only (strict)');
                queries = [
                    { query: `city=${encodeURIComponent(city)}&country=DE&format=json`, fields: ['city'], isSpecialQuery: true }
                ];
            }
            
            if (queries.length === 0) {
                console.log('No valid fields to validate');
                
                // Reset validation flag
                addressValidationRunning = false;
                return;
            }
            
            executeQueries(queries);
        }
            
        function executeQueries(queries) {
            // Hole DOM-Referenzen für diese Funktion
            const addressField = document.getElementById('address');
            const zipField = document.getElementById('zip');
            const cityField = document.getElementById('city');
            
            const addressFeedback = document.getElementById('address-feedback');
            const zipFeedback = document.getElementById('zip-feedback');
            const cityFeedback = document.getElementById('city-feedback');
            
            // Führe alle Queries sequenziell aus (der Reihe nach)
            async function executeSequentially() {
                const results = [];
                
                for (let i = 0; i < queries.length; i++) {
                    const item = queries[i];
                    let url;
                    
                    if (item.isSpecialQuery) {
                        // Spezielle Query für strenge Adress+PLZ Validierung
                        url = `https://nominatim.openstreetmap.org/search?${item.query}&limit=5`;
                    } else {
                        // Normale Query - verwende structured query wenn möglich
                        if (item.fields.includes('address') && item.query.includes(',')) {
                            // Strukturierte Adressabfrage
                            const parts = item.query.split(',').map(p => p.trim());
                            const addressPart = parts[0];
                            url = `https://nominatim.openstreetmap.org/search?format=json&street=${encodeURIComponent(addressPart)}&country=DE&limit=5`;
                        } else {
                            // Fallback für andere Queries
                            url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(item.query)}&limit=5`;
                        }
                    }
                    
                    console.log('Query URL:', url);
                    
                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        const data = await response.json();
                        console.log('Query result for', item.fields, ':', data);
                        results.push({ status: 'fulfilled', value: { ...item, data } });
                    } catch (error) {
                        console.error('Query failed for', item.fields, ':', error);
                        results.push({ status: 'fulfilled', value: { ...item, data: [], error: error.message } });
                    }
                    
                    // Pause zwischen Requests
                    if (i < queries.length - 1) {
                        await new Promise(resolve => setTimeout(resolve, 300));
                    }
                }
                
                return results;
            }
            
            executeSequentially().then(results => {
                let foundValid = false;
                let bestResult = null;
                
                // Suche nach der besten Übereinstimmung mit STRIKTER Validierung
                for (let result of results) {
                    if (result.status === 'fulfilled' && result.value.data && result.value.data.length > 0) {
                        const data = result.value.data[0];
                        let isValidMatch = true;
                        
                        console.log('Validating result:', data.display_name);
                        console.log('Expected fields:', result.value.fields);
                        
                        // STRIKTE Validierung bei KOMBINATIONEN und bei Einzelfeld-ZIP
                        if (result.value.fields.includes('zip')) {
                            const expectedZip = zipField ? zipField.value.trim() : '';
                            // Versuche verschiedene Eigenschaften für PLZ
                            let foundZip = data.postcode || (data.address && data.address.postcode) || '';
                            
                            // Fallback: Extrahiere PLZ aus display_name wenn nicht direkt verfügbar
                            if (!foundZip && data.display_name) {
                                const zipMatch = data.display_name.match(/\b(\d{5})\b/);
                                foundZip = zipMatch ? zipMatch[1] : '';
                            }
                            
                            console.log('ZIP check - Expected:', expectedZip, 'Found:', foundZip);
                            console.log('Full data object:', data);
                            
                            if (expectedZip && foundZip && expectedZip !== foundZip) {
                                console.log('ZIP mismatch - rejecting result');
                                isValidMatch = false;
                            } else if (expectedZip && !foundZip) {
                                console.log('ZIP not found in result - checking display_name');
                                // Bei Kombinationen: Prüfe ob PLZ im display_name steht
                                if (!data.display_name.includes(expectedZip)) {
                                    console.log('ZIP not in display_name - rejecting');
                                    isValidMatch = false;
                                }
                            }
                        }
                        
                        if (result.value.fields.includes('city')) {
                            const expectedCity = cityField ? cityField.value.trim().toLowerCase() : '';
                            
                            // Bei Einzelfeld-Stadt-Validierung: Strikte Typ-Prüfung
                            if (result.value.fields.length === 1 && result.value.fields[0] === 'city') {
                                console.log('Single city validation - checking type');
                                const itemType = data.type || '';
                                const itemClass = data.class || '';
                                
                                // Nur echte Städte, Dörfer, etc. - keine Straßen
                                let isValidCityType = false;
                                if ((itemClass === 'place' && ['city', 'town', 'village', 'suburb'].includes(itemType)) ||
                                    (itemClass === 'boundary' && itemType === 'administrative')) {
                                    const foundName = (data.name || '').toLowerCase();
                                    if (foundName === expectedCity) {
                                        isValidCityType = true;
                                        console.log('Valid city type found:', data.display_name);
                                    }
                                }
                                
                                if (!isValidCityType) {
                                    console.log('Invalid city type - rejecting (type:', itemType, 'class:', itemClass, ')');
                                    isValidMatch = false;
                                }
                            } else {
                                // Bei Kombinationen: Normale Stadt-Validierung
                                const foundCity = ((data.address && data.address.city) || (data.address && data.address.town) || (data.address && data.address.village) || data.name || '').toLowerCase();
                                console.log('City check - Expected:', expectedCity, 'Found:', foundCity);
                                console.log('Full address object:', data.address);
                                if (expectedCity && foundCity && !foundCity.includes(expectedCity) && !expectedCity.includes(foundCity)) {
                                    console.log('City mismatch - rejecting result');
                                    isValidMatch = false;
                                } else if (expectedCity && !foundCity) {
                                    console.log('City not found in result - check display_name');
                                    // Bei Kombinationen: Prüfe ob Stadt im display_name steht
                                    if (!data.display_name.toLowerCase().includes(expectedCity)) {
                                        console.log('City not in display_name - rejecting');
                                        isValidMatch = false;
                                    }
                                }
                            }
                        }
                        
                        if (result.value.fields.includes('address')) {
                            const expectedAddress = addressField ? addressField.value.trim().toLowerCase() : '';
                            const foundAddress = (data.display_name || '').toLowerCase();
                            console.log('Address check - Expected:', expectedAddress, 'Found in:', foundAddress);
                            
                            // Intelligente Adressvalidierung - prüfe verschiedene Varianten
                            if (expectedAddress) {
                                const expectedStreet = expectedAddress.replace(/\s*\d+.*$/, '').trim(); // Ohne Hausnummer
                                const expectedWords = expectedStreet.split(/\s+/);
                                
                                // Prüfe ob die Adresse in irgendeiner Form im Ergebnis vorkommt
                                let addressFound = false;
                                
                                // 1. Exakte Übereinstimmung
                                if (foundAddress.includes(expectedStreet)) {
                                    addressFound = true;
                                    console.log('Address found: exact match');
                                }
                                // 2. Zusammengeschrieben vs. getrennt (hückeswagenerstraße vs hückeswagener straße)
                                else if (expectedWords.length > 1) {
                                    // User hat getrennt eingegeben, API hat zusammengeschrieben
                                    const joinedExpected = expectedWords.join('');
                                    if (foundAddress.includes(joinedExpected)) {
                                        addressFound = true;
                                        console.log('Address found: user separated, API joined');
                                    }
                                } else if (expectedWords.length === 1 && expectedStreet.length > 8) {
                                    // User hat zusammengeschrieben, API hat getrennt - teste verschiedene Trennungen
                                    const streetTypes = ['straße', 'gasse', 'weg', 'platz', 'allee', 'ring', 'damm'];
                                    for (const type of streetTypes) {
                                        if (expectedStreet.endsWith(type.toLowerCase())) {
                                            const prefix = expectedStreet.substring(0, expectedStreet.length - type.length);
                                            const separatedVersion = prefix + ' ' + type;
                                            if (foundAddress.includes(separatedVersion)) {
                                                addressFound = true;
                                                console.log('Address found: user joined, API separated (' + separatedVersion + ')');
                                                break;
                                            }
                                        }
                                    }
                                }
                                // 3. Fallback: Prüfe ob der erste Teil der Adresse vorkommt
                                if (!addressFound && expectedWords.length > 0) {
                                    const firstWord = expectedWords[0];
                                    if (firstWord.length >= 4 && foundAddress.includes(firstWord)) {
                                        addressFound = true;
                                        console.log('Address found: first word match (' + firstWord + ')');
                                    }
                                }
                                
                                if (!addressFound) {
                                    console.log('Address mismatch - rejecting result');
                                    isValidMatch = false;
                                }
                            }
                        }
                        
                        if (isValidMatch) {
                            console.log('Result accepted - all fields match');
                            foundValid = true;
                            bestResult = result.value;
                            break;
                        } else {
                            console.log('Result rejected - field mismatch');
                        }
                    }
                }
                
                if (foundValid && bestResult) {
                    console.log('Found valid address:', bestResult.data[0].display_name);
                    
                    // API-basierte Auto-Korrektur der Adresse
                    const apiResult = bestResult.data[0];
                    const currentAddress = addressField ? addressField.value.trim() : '';
                    
                    // Extrahiere Straßenname aus API-Antwort
                    let apiStreetName = '';
                    if (apiResult.address && apiResult.address.road) {
                        apiStreetName = apiResult.address.road;
                    } else if (apiResult.display_name) {
                        // Fallback: Extrahiere ersten Teil der display_name
                        const parts = apiResult.display_name.split(',');
                        if (parts.length > 1) {
                            // Entferne Hausnummer aus dem ersten Teil und nimm zweiten Teil
                            apiStreetName = parts[1].trim().replace(/^\d+\s*,?\s*/, '');
                        }
                    }
                    
                    // Prüfe ob Korrektur nötig ist (API-Straße vs eingegebene Straße)
                    if (apiStreetName && currentAddress && bestResult.fields.includes('address')) {
                        const userStreetPart = currentAddress.replace(/\s*\d+.*$/, '').trim().toLowerCase();
                        const apiStreetLower = apiStreetName.toLowerCase();
                        
                        console.log('Comparing streets - User:', userStreetPart, 'API:', apiStreetLower);
                        
                        // Prüfe ob Korrektur nötig ist - verschiedene Ansätze
                        let needsCorrection = false;
                        
                        if (apiStreetLower !== userStreetPart) {
                            // 1. Direkte Ähnlichkeit (contains)
                            if (apiStreetLower.includes(userStreetPart) || userStreetPart.includes(apiStreetLower)) {
                                needsCorrection = true;
                                console.log('Correction needed: direct similarity');
                            }
                            // 2. Zusammengeschrieben vs. getrennt (hückeswagenerstraße vs hückeswagener straße)
                            else {
                                const userWords = userStreetPart.split(/\s+/);
                                const apiWords = apiStreetLower.split(/\s+/);
                                
                                // User zusammengeschrieben, API getrennt
                                if (userWords.length === 1 && apiWords.length > 1) {
                                    const joinedApi = apiWords.join('');
                                    if (userWords[0] === joinedApi) {
                                        needsCorrection = true;
                                        console.log('Correction needed: user joined, API separated');
                                    }
                                }
                                // User getrennt, API zusammengeschrieben  
                                else if (userWords.length > 1 && apiWords.length === 1) {
                                    const joinedUser = userWords.join('');
                                    if (joinedUser === apiWords[0]) {
                                        needsCorrection = true;
                                        console.log('Correction needed: user separated, API joined');
                                    }
                                }
                            }
                        }
                        
                        if (needsCorrection) {
                            
                            // Extrahiere Hausnummer aus API-Ergebnis (bevorzugt) oder User-Eingabe (Fallback)
                            let houseNumber = '';
                            
                            // 1. Versuche Hausnummer aus API display_name zu extrahieren
                            if (apiResult.display_name) {
                                const displayParts = apiResult.display_name.split(',');
                                if (displayParts.length > 0) {
                                    const firstPart = displayParts[0].trim();
                                    const apiHouseMatch = firstPart.match(/^(\d+[a-zA-Z]?)/);
                                    if (apiHouseMatch) {
                                        houseNumber = apiHouseMatch[1];
                                        console.log('Using house number from API:', houseNumber);
                                    }
                                }
                            }
                            
                            // 2. Fallback: Hausnummer aus User-Eingabe wenn API keine liefert
                            if (!houseNumber) {
                                const userHouseMatch = currentAddress.match(/\d+[a-zA-Z]?.*$/);
                                houseNumber = userHouseMatch ? userHouseMatch[0] : '';
                                console.log('Using house number from user input:', houseNumber);
                            }
                            
                            const correctedAddress = apiStreetName + (houseNumber ? ' ' + houseNumber : '');
                            
                            console.log('API-based correction:', currentAddress, '->', correctedAddress);
                            
                            // Aktualisiere das Adressfeld
                            if (addressField) {
                                addressField.value = correctedAddress;
                                console.log('Address field updated to:', correctedAddress);
                                
                                // Sofort als valid markieren
                                addressField.classList.remove('is-invalid');
                                addressField.classList.add('is-valid');
                                
                                // Kurzes visuelles Feedback
                                const addressFeedback = document.getElementById('address-feedback');
                                if (addressFeedback) {
                                    addressFeedback.textContent = `Auto-korrigiert: ${correctedAddress}`;
                                    addressFeedback.className = 'form-text text-info';
                                    
                                    // Nach 3 Sekunden zurück zu Erfolg
                                    setTimeout(() => {
                                        addressFeedback.textContent = 'Adresse ist korrekt';
                                        addressFeedback.className = 'form-text text-success';
                                    }, 3000);
                                }
                            }
                        }
                    }
                    
                    // Setze alle beteiligten Felder auf valid und zeige Erfolgsmeldung
                    bestResult.fields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field) {
                            console.log('Setting field as valid:', fieldName);
                            field.classList.remove('is-invalid');
                            field.classList.add('is-valid');
                        }
                    });
                    
                    // Zentrale Erfolgsmeldung
                    let successMessage = 'Adressvalidierung erfolgreich! ';
                    if (bestResult.fields.includes('address')) successMessage += 'Adresse ';
                    if (bestResult.fields.includes('zip')) successMessage += 'PLZ ';
                    if (bestResult.fields.includes('city')) successMessage += 'Stadt ';
                    successMessage += 'sind korrekt.';
                    
                    showAddressStatus('success', successMessage);
                    
                    console.log('Calling checkAllFieldsValid after successful validation');
                    // Prüfe ob alle Felder jetzt valid sind
                    setTimeout(() => {
                        checkAllFieldsValid();
                    }, 100); // Kurze Verzögerung um sicherzustellen dass DOM-Updates abgeschlossen sind
                    
                } else {
                    console.log('No valid address found - analyzing which fields are problematic');
                    
                    // Entferne Loading-Indikatoren vor Fehleranalyse
                    hideAddressValidationLoading();
                    
                    // Intelligente Fehleranalyse: Teste einzelne Kombinationen um herauszufinden welches Feld falsch ist
                    analyzeAddressErrors();
                }
                
                // Reset validation flag at the end
                addressValidationRunning = false;
            })
            .catch(error => {
                console.error('Smart address validation failed:', error);
                
                // Entferne Loading-Indikatoren bei Fehler
                hideAddressValidationLoading();
                
                // Reset validation flag on error
                addressValidationRunning = false;
            });
        }

        // New function to validate complete address combination
        function validateCompleteAddress() {
            const address = document.getElementById('address').value.trim();
            const zip = document.getElementById('zip').value.trim();
            const city = document.getElementById('city').value.trim();
            
            // Only validate if all fields have content
            if (!address || !zip || !city) {
                return;
            }
            
            // Debounce the complete address validation
            if (window.completeAddressTimeout) {
                clearTimeout(window.completeAddressTimeout);
            }
            
            window.completeAddressTimeout = setTimeout(() => {
                console.log('Validating complete address combination...');
                
                // Multiple query strategies for complete address
                const queries = [
                    `${address}, ${zip} ${city}, Germany`,
                    `${address}, ${city}, ${zip}, Germany`,
                    `${zip} ${city}, ${address}, Germany`,
                    `${city}, ${address}, Germany`
                ];
                
                // Sequenzielle Abfrage der kompletten Adresse
                async function validateCompleteAddress() {
                    const results = [];
                    
                    for (const query of queries) {
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=3`);
                            const data = await response.json();
                            results.push({ status: 'fulfilled', value: data });
                        } catch (error) {
                            results.push({ status: 'rejected', reason: error });
                        }
                        
                        // Pause zwischen Requests
                        await new Promise(resolve => setTimeout(resolve, 300));
                    }
                    
                    return results;
                }
                
                validateCompleteAddress().then(results => {
                    let found = false;
                    let bestMatch = null;
                    
                    for (let result of results) {
                        if (result.status === 'fulfilled' && result.value && result.value.length > 0) {
                            found = true;
                            bestMatch = result.value[0];
                            break;
                        }
                    }
                    
                    // Keine Popup-Notifications mehr - nur Feld-Status
                })
                .catch(() => {
                    console.error('Complete address validation failed');
                });
            }, 1000); // Wait 1 second after last field change
        }
        
        // Intelligente Fehleranalyse für Adressvalidierung
        async function analyzeAddressErrors() {
            const addressField = document.getElementById('address');
            const zipField = document.getElementById('zip');
            const cityField = document.getElementById('city');
            
            const addressFeedback = document.getElementById('address-feedback');
            const zipFeedback = document.getElementById('zip-feedback');
            const cityFeedback = document.getElementById('city-feedback');
            
            const address = addressField ? addressField.value.trim() : '';
            const zip = zipField ? zipField.value.trim() : '';
            const city = cityField ? cityField.value.trim() : '';
            
            console.log('Analyzing address errors for:', { address, zip, city });
            
            // Zeige zentrale Analyse-Nachricht
            showAddressStatus('analyzing', 'Analysiere Adressprobleme...');
            
            // Teste verschiedene Kombinationen um herauszufinden welches Feld falsch ist
            const tests = [];
            
            // 1. Teste PLZ + Stadt (ohne Adresse) - um zu sehen ob diese Kombination stimmt
            if (zip && city) {
                tests.push({
                    name: 'zip_city',
                    url: `https://nominatim.openstreetmap.org/search?postalcode=${zip}&city=${encodeURIComponent(city)}&country=DE&format=json&limit=3`,
                    fields: ['zip', 'city']
                });
            }
            
            // 2. Teste nur PLZ - um zu sehen ob PLZ überhaupt existiert
            if (zip) {
                tests.push({
                    name: 'zip_only',
                    url: `https://nominatim.openstreetmap.org/search?postalcode=${zip}&country=DE&format=json&limit=3`,
                    fields: ['zip']
                });
            }
            
            // 3. Teste nur Stadt - um zu sehen ob Stadt existiert
            if (city) {
                tests.push({
                    name: 'city_only',
                    url: `https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(city)}&country=DE&format=json&limit=3`,
                    fields: ['city']
                });
            }
            
            // 4. Teste Adresse + Stadt (ohne PLZ) - um die korrekte PLZ herauszufinden
            if (address && city) {
                tests.push({
                    name: 'address_city',
                    url: `https://nominatim.openstreetmap.org/search?street=${encodeURIComponent(address)}&city=${encodeURIComponent(city)}&country=DE&format=json&limit=3`,
                    fields: ['address', 'city']
                });
            }
            
            const results = {};
            
            // Führe Tests sequenziell aus
            for (const test of tests) {
                try {
                    console.log('Testing:', test.name, 'URL:', test.url);
                    const response = await fetch(test.url);
                    const data = response.ok ? await response.json() : [];
                    results[test.name] = data.length > 0;
                    console.log('Test result:', test.name, results[test.name], 'Results count:', data.length);
                    
                    // Pause zwischen Requests
                    await new Promise(resolve => setTimeout(resolve, 300));
                } catch (error) {
                    console.error('Test failed:', test.name, error);
                    results[test.name] = false;
                }
            }
            
            // Analysiere Ergebnisse und setze spezifische Fehlermeldungen
            let addressValid = true; // Adresse kann nicht einzeln getestet werden, nehme an sie ist OK
            let zipValid = results.zip_only || false;
            let cityValid = results.city_only || false;
            let zipCityValid = results.zip_city || false;
            let addressCityValid = results.address_city || false; // Neue Variable für Adresse+Stadt Test
            
            console.log('Analysis results:', { addressValid, zipValid, cityValid, zipCityValid, addressCityValid });
            
            // Spezifische Fehlermeldungen basierend auf Tests - KONSISTENTE LOGIK
            if (address && zip && city) {
                // Alle drei Felder vorhanden - analysiere schrittweise
                if (!zipValid && !cityValid) {
                    // Sowohl PLZ als auch Stadt existieren nicht
                    zipField.classList.add('is-invalid');
                    cityField.classList.add('is-invalid');
                    addressField.classList.add('is-invalid');
                    showAddressStatus('error', 'PLZ und Stadt existieren nicht. Bitte überprüfen Sie Ihre Eingaben.');
                } else if (!zipValid && cityValid && !zipCityValid) {
                    // PLZ existiert nicht, aber Stadt ist OK - Stadt ist korrekt, PLZ falsch
                    zipField.classList.add('is-invalid');
                    zipField.classList.remove('is-valid');
                    cityField.classList.add('is-valid');
                    cityField.classList.remove('is-invalid');
                    if (addressCityValid) {
                        addressField.classList.add('is-valid');
                        addressField.classList.remove('is-invalid');
                        showAddressStatus('error', 'Stadt und Adresse sind korrekt, aber PLZ existiert nicht oder passt nicht dazu.');
                    } else {
                        addressField.classList.add('is-invalid');
                        addressField.classList.remove('is-valid');
                        showAddressStatus('error', 'Stadt ist korrekt, aber PLZ existiert nicht.');
                    }
                } else if (zipValid && !cityValid && !zipCityValid) {
                    // PLZ existiert, aber Stadt nicht - PLZ ist korrekt, Stadt falsch
                    zipField.classList.add('is-valid');
                    zipField.classList.remove('is-invalid');
                    cityField.classList.add('is-invalid');
                    cityField.classList.remove('is-valid');
                    addressField.classList.add('is-invalid');
                    addressField.classList.remove('is-valid');
                    showAddressStatus('error', 'PLZ ist korrekt, aber Stadt existiert nicht oder ist falsch geschrieben.');
                } else if (zipValid && cityValid && !zipCityValid) {
                    // Beide existieren einzeln, aber passen nicht zusammen
                    zipField.classList.add('is-invalid');
                    cityField.classList.add('is-invalid');
                    addressField.classList.add('is-invalid');
                    showAddressStatus('error', 'PLZ und Stadt existieren beide, passen aber nicht zusammen.');
                } else {
                    // PLZ und Stadt sind beide einzeln OK und passen zusammen
                    // Prüfe ob Adresse+Stadt-Kombination funktioniert (andere PLZ?)
                    if (addressCityValid) {
                        // Adresse + Stadt passen zusammen, aber nicht mit dieser PLZ
                        zipField.classList.add('is-invalid');
                        addressField.classList.add('is-valid');
                        cityField.classList.add('is-valid');
                        showAddressStatus('partial', 'PLZ ist falsch für diese Adresse und Stadt. Adresse und Stadt sind korrekt.');
                    } else {
                        // PLZ und Stadt passen zusammen, aber Adresse nicht
                        zipField.classList.add('is-valid');
                        cityField.classList.add('is-valid');
                        addressField.classList.add('is-invalid');  
                        showAddressStatus('partial', 'Straße existiert nicht in ' + city + ' (PLZ ' + zip + '). PLZ und Stadt sind korrekt.');
                    }
                }
            } else if (address && zip) {
                // Adresse + PLZ (ohne Stadt) - KONSISTENTE LOGIK
                if (!zipValid) {
                    setFieldError(zipField, zipFeedback, 'Postleitzahl existiert nicht');
                    setFieldError(addressField, addressFeedback, 'Ungültige PLZ - Adresse kann nicht geprüft werden');
                } else {
                    setFieldValid(zipField, zipFeedback, 'Postleitzahl ist korrekt');
                    setFieldError(addressField, addressFeedback, 'Straße existiert nicht für PLZ ' + zip);
                }
            } else if (zip && city) {
                // PLZ + Stadt (ohne Adresse) - KONSISTENTE LOGIK
                if (!zipValid && !cityValid) {
                    setFieldError(zipField, zipFeedback, 'Postleitzahl existiert nicht');
                    setFieldError(cityField, cityFeedback, 'Stadt existiert nicht');
                } else if (!zipValid && cityValid) {
                    setFieldError(zipField, zipFeedback, 'Postleitzahl existiert nicht');
                    setFieldError(cityField, cityFeedback, 'Stadt existiert, aber PLZ ist ungültig');
                } else if (zipValid && !cityValid) {
                    setFieldError(zipField, zipFeedback, 'PLZ existiert, aber Stadt ist ungültig');
                    setFieldError(cityField, cityFeedback, 'Stadt existiert nicht oder falsch geschrieben');
                } else if (zipValid && cityValid && !zipCityValid) {
                    setFieldError(zipField, zipFeedback, 'PLZ gehört nicht zu dieser Stadt');
                    setFieldError(cityField, cityFeedback, 'Stadt gehört nicht zu dieser PLZ');
                } else {
                    setFieldValid(zipField, zipFeedback, 'Postleitzahl ist korrekt');
                    setFieldValid(cityField, cityFeedback, 'Stadt ist korrekt');
                }
            }
            
            // Reset validation flag
            addressValidationRunning = false;
            
            // Update button states
            checkAllFieldsValid();
        }
        
        function setFieldError(field, feedback, message) {
            if (field && feedback) {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                feedback.textContent = message;
                feedback.className = 'form-text text-danger';
            }
        }
        
        function setFieldValid(field, feedback, message) {
            if (field && feedback) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                feedback.textContent = message;
                feedback.className = 'form-text text-success';
            }
        }
        
        function validateZip(value) {
            const zipField = document.getElementById('zip');
            const feedback = document.getElementById('zip-feedback');
            
            if (!value.trim()) {
                zipField.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
                validateSmartAddress();
                return false;
            }
            
            // German ZIP code validation (5 digits)
            const zipRegex = /^\d{5}$/;
            if (!zipRegex.test(value)) {
                zipField.classList.remove('is-valid');
                zipField.classList.add('is-invalid');
                feedback.textContent = 'Ungültige Postleitzahl (5 Ziffern erforderlich)';
                feedback.className = 'form-text text-danger';
                return false;
            }
            
            // Trigger smart address validation
            validateSmartAddress();
            return true;
        }
        
        function validateCity(value) {
            const cityField = document.getElementById('city');
            const feedback = document.getElementById('city-feedback');
            
            if (!value.trim()) {
                cityField.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
                validateSmartAddress();
                return false;
            }
            
            if (value.length < 2) {
                cityField.classList.remove('is-valid');
                cityField.classList.add('is-invalid');
                feedback.textContent = 'Stadt muss mindestens 2 Zeichen haben';
                feedback.className = 'form-text text-danger';
                return false;
            }
            
            // City name validation (letters, spaces, hyphens)
            const cityRegex = /^[a-zA-ZäöüßÄÖÜ\s\-'\.]+$/;
            if (!cityRegex.test(value)) {
                cityField.classList.remove('is-valid');
                cityField.classList.add('is-invalid');
                feedback.textContent = 'Ungültige Zeichen im Stadtnamen';
                feedback.className = 'form-text text-danger';
                return false;
            }
            
            // Trigger smart address validation
            validateSmartAddress();
            return true;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    // Clear validation error on input with debouncing
                    field.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                        
                        // Clear existing timer
                        if (validationTimers[fieldId]) {
                            clearTimeout(validationTimers[fieldId]);
                        }
                        
                        // Set new timer for validation
                        validationTimers[fieldId] = setTimeout(() => {
                            const value = this.value.trim();
                            
                            switch(fieldId) {
                                case 'firstName':
                                case 'lastName':
                                    validateName(fieldId, value);
                                    break;
                                case 'email':
                                    validateEmail(value);
                                    break;
                                case 'address':
                                    const zip = document.getElementById('zip').value;
                                    const city = document.getElementById('city').value;
                                    validateAddress(value, zip, city);
                                    break;
                                case 'zip':
                                    validateZip(value);
                                    break;
                                case 'city':
                                    validateCity(value);
                                    break;
                            }
                        }, 500);
                    });
                    
                    // Clear validation error on focus
                    field.addEventListener('focus', function() {
                        this.classList.remove('is-invalid');
                    });
                }
            });
            
            // Handle country dropdown change
            const countryField = document.getElementById('country');
            if (countryField) {
                countryField.addEventListener('change', function() {
                    console.log('Country changed to:', this.value);
                    
                    // Reset all address field validations
                    const addressField = document.getElementById('address');
                    const zipField = document.getElementById('zip');
                    const cityField = document.getElementById('city');
                    
                    if (addressField) {
                        addressField.classList.remove('is-valid', 'is-invalid');
                    }
                    if (zipField) {
                        zipField.classList.remove('is-valid', 'is-invalid');
                    }
                    if (cityField) {
                        cityField.classList.remove('is-valid', 'is-invalid');
                    }
                    
                    // Clear feedback messages
                    const addressFeedback = document.getElementById('address-feedback');
                    const zipFeedback = document.getElementById('zip-feedback');
                    const cityFeedback = document.getElementById('city-feedback');
                    
                    if (addressFeedback) addressFeedback.textContent = '';
                    if (zipFeedback) zipFeedback.textContent = '';
                    if (cityFeedback) cityFeedback.textContent = '';
                    
                    // Trigger re-validation if address fields have content
                    if (addressField && addressField.value.trim()) {
                        console.log('Re-validating address after country change');
                        validateSmartAddress();
                    }
                    
                    // Update button states
                    checkAllFieldsValid();
                });
            }

            // Handle checkbox
            const acceptTerms = document.getElementById('acceptTerms');
            if (acceptTerms) {
                acceptTerms.addEventListener('change', function() {
                    if (this.checked) {
                        this.classList.remove('is-invalid');
                    }
                });
            }

            // Validiere bereits vorausgefüllte Felder beim Laden der Seite
            console.log('Validating pre-filled fields on page load...');
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value.trim()) {
                    console.log('Pre-filled field found:', fieldId, 'Value:', field.value.trim().substring(0, 20) + '...');
                    
                    // Validiere das vorausgefüllte Feld
                    switch(fieldId) {
                        case 'firstName':
                        case 'lastName':
                            validateName(fieldId, field.value.trim());
                            break;
                        case 'email':
                            validateEmail(field.value.trim());
                            break;
                        case 'address':
                            if (field.value.trim()) {
                                const zip = document.getElementById('zip').value;
                                const city = document.getElementById('city').value;
                                validateAddress(field.value.trim(), zip, city);
                            }
                            break;
                        case 'zip':
                            validateZip(field.value.trim());
                            break;
                        case 'city':
                            validateCity(field.value.trim());
                            break;
                    }
                }
            });
        });

        function validateAndShowPayPal() {
            // Validate required fields
            const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            let isValid = true;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Check terms acceptance
            const acceptTerms = document.getElementById('acceptTerms');
            if (!acceptTerms.checked) {
                acceptTerms.classList.add('is-invalid');
                isValid = false;
            } else {
                acceptTerms.classList.remove('is-invalid');
            }

            if (!isValid) {
                // Show visual feedback instead of alert
                showValidationErrors();
                return;
            }

            // Save address if checkbox is checked (for logged in users)
            <?php if ($user->isLoggedIn()): ?>
            const saveAddressCheckbox = document.getElementById('saveAddress');
            if (saveAddressCheckbox && saveAddressCheckbox.checked) {
                saveUserAddress();
            }
            <?php endif; ?>

            // Hide continue button and show PayPal
            document.getElementById('continueBtn').classList.add('d-none');
            document.getElementById('paypal-button-container').classList.remove('d-none');

            if (!paypalRendered) {
                renderPayPalButton();
                paypalRendered = true;
            }
        }

        function renderPayPalButton() {
            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal'
                },
                
                createOrder: function(data, actions) {
                    // Erste Validierung der Felder vor PayPal Order
                    const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
                    for (let fieldId of requiredFields) {
                        const field = document.getElementById(fieldId);
                        if (!field || !field.value.trim()) {
                            alert('Bitte füllen Sie alle Pflichtfelder aus, bevor Sie mit PayPal bezahlen.');
                            return Promise.reject('Validation failed');
                        }
                    }
                    
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '<?php echo number_format($cartTotalWithTax, 2, '.', ''); ?>',
                                currency_code: 'EUR'
                            },
                            description: 'Ocean Hosting - Game Server Order',
                            custom_id: 'OCEAN_ORDER_' + Date.now()
                        }]
                    });
                },
                
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // Send order data to server
                        const orderData = {
                            paypal_order_id: data.orderID,
                            paypal_payer_id: details.payer.payer_id,
                            customer_data: {
                                first_name: document.getElementById('firstName').value,
                                last_name: document.getElementById('lastName').value,
                                email: document.getElementById('email').value,
                                address: document.getElementById('address').value,
                                zip: document.getElementById('zip').value,
                                city: document.getElementById('city').value,
                                country: document.getElementById('country').value
                            },
                            cart_items: <?php echo json_encode($cartItems); ?>,
                            total_amount: <?php echo $cartTotalWithTax; ?>
                        };

                        // Process order
                        fetch('/ocean/shop/api/process-order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(orderData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear cart and redirect to success page
                                window.location.href = '/ocean/shop/checkout/success?order=' + data.order_id;
                            } else {
                                showNotification('❌ Fehler beim Verarbeiten der Bestellung: ' + data.message, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('❌ Ein Fehler ist aufgetreten. Bitte kontaktieren Sie den Support.', 'danger');
                        });
                    });
                },
                
                onError: function(err) {
                    console.error('PayPal Error:', err);
                    showNotification('❌ Ein Fehler bei der PayPal-Zahlung ist aufgetreten. Bitte versuchen Sie es erneut.', 'danger');
                },

                onCancel: function(data) {
                    // Show continue button again
                    document.getElementById('continueBtn').classList.remove('d-none');
                    document.getElementById('paypal-button-container').classList.add('d-none');
                }
            }).render('#paypal-button-container');
        }

        // Demo Order Processing (for testing without PayPal)
        function processDemoOrder() {
            // Prüfe erst ob alle Felder valid sind (gleiche Logik wie für Hauptbutton)
            const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            const demoBtn = document.getElementById('demoOrderBtn');
            
            let allValid = true;
            
            for (const fieldId of requiredFields) {
                const field = document.getElementById(fieldId);
                
                if (!field) {
                    allValid = false;
                    break;
                }
                
                // Prüfe ob Feld ausgefüllt ist
                if (!field.value.trim()) {
                    allValid = false;
                    break;
                }
                
                // Prüfe ob Feld als valid markiert ist (grüner Rahmen)
                if (!field.classList.contains('is-valid')) {
                    allValid = false;
                    break;
                }
            }
            
            if (!allValid) {
                alert('Bitte füllen Sie alle Felder korrekt aus, bevor Sie eine Demo-Bestellung durchführen können.');
                return;
            }
            
            // Check terms acceptance
            const acceptTerms = document.getElementById('acceptTerms');
            if (!acceptTerms.checked) {
                acceptTerms.classList.add('is-invalid');
                allValid = false;
            }
            
            if (!allValid) {
                showNotification('⚠️ Bitte füllen Sie alle Pflichtfelder aus und akzeptieren Sie die AGB.', 'warning');
                return;
            }
            
            // Show loading
            document.getElementById('demoOrderBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verarbeite...';
            document.getElementById('demoOrderBtn').disabled = true;
            
            // Simulate order processing
            const orderData = {
                demo_order: true,
                paypal_order_id: 'DEMO_' + Date.now(),
                paypal_payer_id: 'demo_payer_' + Date.now(),
                customer_data: {
                    first_name: document.getElementById('firstName').value,
                    last_name: document.getElementById('lastName').value,
                    email: document.getElementById('email').value,
                    address: document.getElementById('address').value,
                    zip: document.getElementById('zip').value,
                    city: document.getElementById('city').value,
                    country: document.getElementById('country').value
                },
                cart_items: <?php echo json_encode($cartItems); ?>,
                total_amount: <?php echo $cartTotalWithTax; ?>
            };

            // Process demo order
            fetch('/ocean/shop/api/process-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear cart and redirect to success page
                    fetch('/ocean/shop/api/add-to-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'clear' })
                    });
                    
                    // Redirect to success page
                    window.location.href = '/ocean/shop/checkout/success?order_id=' + data.order_id;
                } else {
                    showNotification('❌ Fehler beim Verarbeiten der Demo-Bestellung: ' + data.message, 'danger');
                    document.getElementById('demoOrderBtn').innerHTML = '<i class="fas fa-play me-2"></i>Demo-Bestellung (Test)';
                    document.getElementById('demoOrderBtn').disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('❌ Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'danger');
                document.getElementById('demoOrderBtn').innerHTML = '<i class="fas fa-play me-2"></i>Demo-Bestellung (Test)';
                document.getElementById('demoOrderBtn').disabled = false;
            });
        }

        // Save Address functionality
        <?php if ($user->isLoggedIn()): ?>
        function saveUserAddress() {
            const addressData = {
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                address: document.getElementById('address').value.trim(),
                city: document.getElementById('city').value.trim(),
                zip: document.getElementById('zip').value.trim(),
                country: document.getElementById('country').value
            };
            
            // Save address silently in background
            fetch('/ocean/shop/api/save-address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(addressData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Adresse erfolgreich gespeichert');
                } else {
                    console.log('Fehler beim Speichern der Adresse:', data.message);
                }
            })
            .catch(error => {
                // Silent fail - address saving is optional
                console.log('Address saving skipped (not logged in or other error):', error.message);
            });
        }
        <?php endif; ?>
        
        // Initiale Prüfung beim Laden der Seite
        document.addEventListener('DOMContentLoaded', function() {
            checkAllFieldsValid();
            
            // Event Listener für alle Eingabefelder hinzufügen
            const inputFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            inputFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        // Kleine Verzögerung für bessere Performance
                        setTimeout(() => {
                            checkAllFieldsValid();
                        }, 100);
                    });
                    
                    field.addEventListener('blur', function() {
                        checkAllFieldsValid();
                    });
                }
            });
        });
    </script>
</body>
</html>