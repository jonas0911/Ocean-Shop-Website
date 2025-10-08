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
                                    <input type="text" class="form-control" id="firstName" value="<?php echo htmlspecialchars($userAddressData['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Nachname *</label>
                                    <input type="text" class="form-control" id="lastName" value="<?php echo htmlspecialchars($userAddressData['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-Mail *</label>
                                <input type="email" class="form-control" id="email" value="<?php echo $user->isLoggedIn() ? htmlspecialchars($currentUser['email']) : ''; ?>" required>
                                <div id="email-feedback" class="form-text"></div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="mb-4">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Rechnungsadresse</h5>
                            <div class="mb-3">
                                <label for="address" class="form-label">Straße und Hausnummer *</label>
                                <input type="text" class="form-control" id="address" value="<?php echo htmlspecialchars($userAddressData['address']); ?>" required>
                                <div id="address-feedback" class="form-text"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">PLZ *</label>
                                    <input type="text" class="form-control" id="zip" value="<?php echo htmlspecialchars($userAddressData['zip']); ?>" required>
                                    <div id="zip-feedback" class="form-text"></div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="city" class="form-label">Stadt *</label>
                                    <input type="text" class="form-control" id="city" value="<?php echo htmlspecialchars($userAddressData['city']); ?>" required>
                                    <div id="city-feedback" class="form-text"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="country" class="form-label">Land *</label>
                                <select class="form-control" id="country" required>
                                    <option value="DE" <?php echo ($userAddressData['country'] == 'DE') ? 'selected' : ''; ?>>Deutschland</option>
                                    <option value="AT" <?php echo ($userAddressData['country'] == 'AT') ? 'selected' : ''; ?>>Österreich</option>
                                    <option value="CH" <?php echo ($userAddressData['country'] == 'CH') ? 'selected' : ''; ?>>Schweiz</option>
                                    <option value="US" <?php echo ($userAddressData['country'] == 'US') ? 'selected' : ''; ?>>USA</option>
                                    <option value="GB" <?php echo ($userAddressData['country'] == 'GB') ? 'selected' : ''; ?>>Großbritannien</option>
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
                        <button class="btn btn-primary btn-lg w-100" id="continueBtn" onclick="validateAndShowPayPal()">
                            <i class="fas fa-lock me-2"></i>Zur Zahlung
                        </button>
                        
                        <!-- Demo/Test Button -->
                        <button class="btn btn-success btn-lg w-100 mt-2" id="demoOrderBtn" onclick="processDemoOrder()">
                            <i class="fas fa-play me-2"></i>Demo-Bestellung (Test)
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
            const statusIcon = document.getElementById(fieldId + '-status');
            const feedback = document.getElementById(fieldId + '-feedback');
            
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
            
            const addressField = document.getElementById('address');
            const zipField = document.getElementById('zip');
            const cityField = document.getElementById('city');
            
            const addressFeedback = document.getElementById('address-feedback');
            const zipFeedback = document.getElementById('zip-feedback');
            const cityFeedback = document.getElementById('city-feedback');
            
            const address = addressField ? addressField.value.trim() : '';
            const zip = zipField ? zipField.value.trim() : '';
            const city = cityField ? cityField.value.trim() : '';
            
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
                    }
                    
                    if (!cityValid) {
                        console.log('City not valid');
                        if (cityField && cityFeedback) {
                            cityField.classList.remove('is-valid');
                            cityField.classList.add('is-invalid');
                            cityFeedback.textContent = 'Stadt nicht gefunden';
                            cityFeedback.className = 'form-text text-danger';
                        }
                    }
                    
                    // Adresse Validierung (einfacher Check ob örtlich existiert)
                    if (addressResult && addressResult.length > 0) {
                        console.log('Address validation result:', addressResult);
                        // Prüfe ob Adresse in den Ergebnissen vorkommt
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
                    
                    // Nur wenn alle drei einzeln gültig sind, teste die Kombination
                    if (zipValid && cityValid && addressValid) {
                        console.log('All three fields valid individually - now testing combination');
                        const combinationQueries = [
                            { query: `street=${encodeURIComponent(address)}&postalcode=${zip}&city=${encodeURIComponent(city)}&country=DE&format=json`, fields: ['address', 'zip', 'city'], isSpecialQuery: true }
                        ];
                        executeQueries(combinationQueries);
                    } else {
                        console.log('Individual validation failed - not testing combination');
                    }
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
                            let foundZip = data.postcode || data.address?.postcode || '';
                            
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
                                const foundCity = (data.address?.city || data.address?.town || data.address?.village || data.name || '').toLowerCase();
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
                            if (expectedAddress && !foundAddress.includes(expectedAddress.split(' ')[0])) {
                                console.log('Address mismatch - rejecting result');
                                isValidMatch = false;
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
                    
                    // Setze alle beteiligten Felder auf valid
                    bestResult.fields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        const feedback = document.getElementById(fieldName + '-feedback');
                        
                        if (field && feedback) {
                            field.classList.remove('is-invalid');
                            field.classList.add('is-valid');
                            
                            let message = '';
                            switch(fieldName) {
                                case 'address': message = 'Adresse ist korrekt'; break;
                                case 'zip': message = 'Postleitzahl ist korrekt'; break;
                                case 'city': message = 'Stadt ist korrekt'; break;
                            }
                            
                            feedback.textContent = message;
                            feedback.className = 'form-text text-success';
                        }
                    });
                    
                } else {
                    console.log('No valid address found');
                    
                    // Setze alle ausgefüllten Felder auf invalid mit passender Fehlermeldung
                    const address = addressField ? addressField.value.trim() : '';
                    const zip = zipField ? zipField.value.trim() : '';
                    const city = cityField ? cityField.value.trim() : '';
                    
                    const filledFields = [];
                    if (addressField && address) filledFields.push('address');
                    if (zipField && zip) filledFields.push('zip');
                    if (cityField && city) filledFields.push('city');
                    
                    // Passende Fehlermeldung je nach Kombination
                    let zipMessage = 'Postleitzahl nicht gefunden';
                    let cityMessage = 'Stadt nicht gefunden';
                    let addressMessage = 'Adresse nicht gefunden';
                    
                    if (filledFields.includes('zip') && filledFields.includes('city')) {
                        zipMessage = 'PLZ + Adresse + Stadt passen nicht';
                        cityMessage = 'Stadt + PLZ + Adresse passen nicht';
                    }
                    if (filledFields.includes('address') && filledFields.includes('zip')) {
                        addressMessage = 'Adresse + PLZ passen nicht';
                        zipMessage = filledFields.includes('city') ? zipMessage : 'PLZ + Adresse passen nicht';
                    }
                    if (filledFields.includes('address') && filledFields.includes('city')) {
                        addressMessage = filledFields.includes('zip') ? addressMessage : 'Adresse + Stadt passen nicht';
                        cityMessage = filledFields.includes('zip') ? cityMessage : 'Stadt + Adresse passen nicht';
                    }
                    
                    [
                        { field: addressField, feedback: addressFeedback, name: 'address', message: addressMessage },
                        { field: zipField, feedback: zipFeedback, name: 'zip', message: zipMessage },
                        { field: cityField, feedback: cityFeedback, name: 'city', message: cityMessage }
                    ].forEach(item => {
                        if (item.field && item.field.value.trim() && item.feedback) {
                            item.field.classList.remove('is-valid');
                            item.field.classList.add('is-invalid');
                            item.feedback.textContent = item.message;
                            item.feedback.className = 'form-text text-danger';
                        }
                    });
                }
                
                validateCompleteAddress();
            })
            .catch(error => {
                console.error('Smart address validation failed:', error);
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
            
            // Handle checkbox
            const acceptTerms = document.getElementById('acceptTerms');
            if (acceptTerms) {
                acceptTerms.addEventListener('change', function() {
                    if (this.checked) {
                        this.classList.remove('is-invalid');
                    }
                });
            }
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
            // Validate required fields first
            const requiredFields = ['firstName', 'lastName', 'email', 'address', 'zip', 'city'];
            let isValid = true;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field || !field.value.trim()) {
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
            }
            
            if (!isValid) {
                showNotification('⚠️ Bitte füllen Sie alle Pflichtfelder aus und akzeptieren Sie die AGB.', 'warning');
                showValidationErrors();
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Adresse erfolgreich gespeichert');
                } else {
                    console.log('Fehler beim Speichern der Adresse:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving address:', error);
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>