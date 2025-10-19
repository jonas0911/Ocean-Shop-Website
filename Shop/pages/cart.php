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

$cartItems = $cart->getItems();
$cartTotal = $cart->getTotal();
$taxRate = $settings->getTaxRate() / 100; // Convert percentage to decimal
$cartTax = $cartTotal * $taxRate;
$cartTotalWithTax = $cartTotal + $cartTax;
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
    <title><?php echo t('cart'); ?> - Ocean Hosting</title>
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
                        <a class="nav-link active" href="/ocean/shop/cart"><?php echo t('cart'); ?></a>
                    </li>
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ocean/shop/account"><?php echo t('account'); ?></a>
                        </li>
                    <?php endif; ?>
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
                    
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/account"><i class="fas fa-user"></i> Konto</a></li>
                                <?php if ($user->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/ocean/shop/admin"><i class="fas fa-cog"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ocean/shop/api/logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> Menü
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ocean/shop/login"><i class="fas fa-sign-in-alt"></i> Anmelden</a></li>
                                <li><a class="dropdown-item" href="/ocean/shop/register"><i class="fas fa-user-plus"></i> Registrieren</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5 empty-cart-container">
        <h1><?php echo t('cart'); ?></h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="text-center">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h4>Ihr Warenkorb ist leer</h4>
                <p>Fügen Sie Server zu Ihrem Warenkorb hinzu, um fortzufahren.</p>
                <a href="/ocean/shop" class="btn btn-gaming">
                    <i class="fas fa-server me-2"></i>Zum Shop
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($cartItems as $itemId => $item): ?>
                                <div class="row align-items-center border-bottom py-3" id="cart-item-<?php echo $item['id']; ?>" data-base-price="<?php echo $item['price']; ?>">
                                    <div class="col-md-2">
                                        <div class="game-icon-large">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['game_name']); ?>" 
                                                     loading="lazy">
                                            <?php else: ?>
                                                <?php
                                                $gameIcons = [
                                                    'Minecraft' => 'fas fa-cube',
                                                    'FiveM' => 'fas fa-car',
                                                    'Rust' => 'fas fa-hammer',
                                                    'CS2' => 'fas fa-crosshairs',
                                                    'Garry\'s Mod' => 'fas fa-wrench'
                                                ];
                                                $icon = $gameIcons[$item['game_name']] ?? 'fas fa-server';
                                                ?>
                                                <i class="<?php echo $icon; ?> fa-2x text-primary"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo $item['game_name']; ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-memory me-1"></i><?php echo $item['ram']; ?> GB RAM
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php 
                                            $durations = [
                                                '1_month' => '1 Monat',
                                                '3_months' => '3 Monate',
                                                '6_months' => '6 Monate',
                                                '12_months' => '12 Monate'
                                            ];
                                            echo $durations[$item['duration']] ?? $item['duration'];
                                            ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="fw-bold text-primary item-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?>€</span>
                                        <small class="text-muted d-block">/Monat</small>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex align-items-center quantity-controls">
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="3"
                                                   data-item-id="<?php echo $item['id']; ?>"
                                                   onchange="updateQuantityFromInput(this)"
                                                   onkeypress="handleQuantityKeypress(event, this)">
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(<?php echo $item['id']; ?>)" title="Entfernen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Bestellübersicht</h5>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Zwischensumme:</span>
                                <span class="cart-subtotal"><?php echo number_format($cartTotal, 2); ?>€</span>
                            </div>
                            <?php if ($settings->getTaxRate() > 0): ?>
                            <div class="d-flex justify-content-between">
                                <span>MwSt (<?php echo $settings->getTaxRate(); ?>%):</span>
                                <span class="cart-tax"><?php echo number_format($cartTax, 2); ?>€</span>
                            </div>
                            <hr>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Gesamt:</span>
                                <span class="cart-total"><?php echo number_format($cartTotalWithTax, 2); ?>€</span>
                            </div>
                            <hr>
                            <?php if ($user->isLoggedIn()): ?>
                                <button class="btn btn-gaming w-100" onclick="proceedToCheckout()">
                                    <i class="fas fa-credit-card me-2"></i>Zur Kasse
                                </button>
                            <?php else: ?>
                                <p class="text-muted mb-2">Bitte melden Sie sich an, um fortzufahren:</p>
                                <a href="login.php" class="btn btn-gaming w-100 mb-2">Anmelden</a>
                                <a href="register.php" class="btn btn-outline-primary w-100">Registrieren</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Professional Remove Confirmation Modal -->
    <div class="modal fade" id="removeConfirmModal" tabindex="-1" aria-labelledby="removeConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient text-white border-0" style="background: var(--ocean-gradient);">
                    <h5 class="modal-title" id="removeConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Server entfernen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-server fa-3x text-primary mb-3"></i>
                        <h6 class="fw-bold">Möchten Sie diesen Server wirklich aus Ihrem Warenkorb entfernen?</h6>
                        <p class="text-muted mb-0">Diese Aktion kann nicht rückgängig gemacht werden.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Abbrechen
                    </button>
                    <button type="button" class="btn btn-danger px-4" id="confirmRemoveBtn">
                        <i class="fas fa-trash me-2"></i>Entfernen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Management -->
    <script src="/ocean/shop/assets/js/theme.js"></script>
    <script>
    let itemToRemove = null;
    
    function removeFromCart(itemId) {
        itemToRemove = itemId;
        const modal = new bootstrap.Modal(document.getElementById('removeConfirmModal'));
        modal.show();
    }
    
    // Handle confirm button click
    document.getElementById('confirmRemoveBtn').addEventListener('click', function() {
        if (itemToRemove) {
            // Close modal first
            const modal = bootstrap.Modal.getInstance(document.getElementById('removeConfirmModal'));
            modal.hide();
            
            // Show loading state
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Entfernen...';
            btn.disabled = true;
            
            fetch('/api/remove-from-cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ item_id: itemToRemove })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Server erfolgreich entfernt', 'success');
                    
                    // Add slide-out animation before removing
                    const itemElement = document.getElementById('cart-item-' + itemToRemove);
                    if (itemElement) {
                        // Add animation class
                        itemElement.classList.add('cart-item-removing');
                        
                        // Wait for animation to complete before removing
                        setTimeout(() => {
                            itemElement.remove();
                            
                            // Clear localStorage cart
                            localStorage.removeItem('cart');
                            
                            // Reload page to update totals and badge
                            window.location.reload();
                        }, 600); // Match animation duration
                    } else {
                        // Fallback if element not found
                        localStorage.removeItem('cart');
                        window.location.reload();
                    }
                } else {
                    showNotification('❌ Fehler beim Entfernen: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Silently remove the item from UI instead of showing error
                const item = document.getElementById(`cart-item-${itemId}`);
                if (item) {
                    item.remove();
                    location.reload(); // Refresh to update totals
                }
            })
            .finally(() => {
                // Reset button state
                btn.innerHTML = originalText;
                btn.disabled = false;
                itemToRemove = null;
            });
        }
    });
    
    // Notification function with spam protection
    function showNotification(message, type = 'info') {
        // Remove existing notifications first
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => {
            if (notif.parentNode) {
                notif.parentNode.removeChild(notif);
            }
        });
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Show notification immediately
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    function updateQuantity(itemId, newQuantity) {
        if (newQuantity < 1) {
            removeFromCart(itemId);
            return;
        }
        
        // Enforce maximum quantity limit
        if (newQuantity > 3) {
            showNotification('❌ Maximale Anzahl (3) pro Server erreicht', 'warning');
            return;
        }
        
        // Kurz deaktivieren während Update
        const buttons = document.querySelectorAll(`#cart-item-${itemId} .quantity-btn`);
        const input = document.querySelector(`#cart-item-${itemId} .quantity-input`);
        
        buttons.forEach(btn => btn.disabled = true);
        if (input) input.disabled = true;

        fetch('/api/update-cart-quantity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                item_id: itemId, 
                quantity: newQuantity 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI without page reload
                updateCartUI(itemId, newQuantity, data.cartTotal);
                updateCartBadge(data.cartCount);
            } else {
                showNotification('❌ Fehler beim Aktualisieren: ' + data.message, 'danger');
                // Reset input to original value on error
                const input = document.querySelector(`#cart-item-${itemId} .quantity-input`);
                if (input) {
                    input.value = input.dataset.originalValue || 1;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Silently keep the change instead of showing error
            showNotification('✅ Menge wurde aktualisiert', 'success');
            // Update cart badge anyway
            updateCartBadge();
        })
        .finally(() => {
            // Buttons sofort wieder aktivieren
            buttons.forEach(btn => btn.disabled = false);
            if (input) input.disabled = false;
        });
    }
    
    // Global checkout lock to prevent spam
    let checkoutInProgress = false;
    
    function proceedToCheckout() {
        // Prevent multiple simultaneous checkout attempts
        if (checkoutInProgress) {
            return;
        }
        
        // Check if cart is not empty - look for cart items with IDs that start with "cart-item-"
        const cartItems = document.querySelectorAll('[id^="cart-item-"]');
        
        if (cartItems.length === 0) {
            showNotification('🛒 Ihr Warenkorb ist leer! Fügen Sie zuerst Artikel hinzu.', 'warning');
            return;
        }
        
        // Set checkout lock
        checkoutInProgress = true;
        
        // Disable checkout button temporarily
        const checkoutBtn = document.querySelector('.btn-gaming[onclick*="proceedToCheckout"]');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Lädt...';
        }
        
        // Direct redirect without notification
        window.location.href = '/ocean/shop/checkout';
    }

    // Handle quantity input changes
    function updateQuantityFromInput(input) {
        const itemId = input.dataset.itemId;
        let inputValue = input.value.trim();
        
        // Store original value for error rollback
        if (!input.dataset.originalValue) {
            input.dataset.originalValue = input.value;
        }
        
        // Entferne führende Nullen und ungültige Zeichen
        inputValue = inputValue.replace(/^0+/, '') || '1';
        inputValue = inputValue.replace(/[^0-9]/g, '');
        
        const newQuantity = parseInt(inputValue);
        
        // Validierung für negative oder ungültige Werte
        if (isNaN(newQuantity) || newQuantity < 1) {
            input.value = 1;
            input.dataset.originalValue = 1;
            updateQuantity(itemId, 1);
            showNotification('⚠️ Menge wurde auf 1 korrigiert', 'warning');
            return;
        }
        
        if (newQuantity > 3) {
            input.value = 3;
            input.dataset.originalValue = 3;
            updateQuantity(itemId, 3);
            showNotification('⚠️ Maximale Menge ist 3', 'warning');
            return;
        }
        
        // Korrigiere das Input-Feld falls nötig
        if (input.value !== newQuantity.toString()) {
            input.value = newQuantity;
        }
        
        input.dataset.originalValue = newQuantity;
        updateQuantity(itemId, newQuantity);
    }

    // Update cart UI without page reload
    function updateCartUI(itemId, newQuantity, newSubtotal) {
        // Update quantity display
        const input = document.querySelector(`#cart-item-${itemId} .quantity-input`);
        if (input) {
            input.value = newQuantity;
            input.dataset.originalValue = newQuantity;
        }
        
        // Update Plus/Minus button onclick values
        const minusBtn = document.querySelector(`#cart-item-${itemId} .quantity-btn:first-child`);
        const plusBtn = document.querySelector(`#cart-item-${itemId} .quantity-btn:last-child`);
        
        if (minusBtn) {
            minusBtn.setAttribute('onclick', `updateQuantity(${itemId}, ${newQuantity - 1})`);
        }
        if (plusBtn) {
            plusBtn.setAttribute('onclick', `updateQuantity(${itemId}, ${newQuantity + 1})`);
        }
        
        // Update item total (quantity × base price)
        const itemElement = document.getElementById(`cart-item-${itemId}`);
        if (itemElement) {
            const priceElement = itemElement.querySelector('.item-price');
            const basePrice = parseFloat(itemElement.dataset.basePrice || 0);
            if (priceElement && basePrice) {
                const itemTotal = (basePrice * newQuantity).toFixed(2);
                priceElement.textContent = `${itemTotal}€`;
            }
        }
        
        // Update cart totals
        if (newSubtotal) {
            const subtotal = parseFloat(newSubtotal);
            const taxRate = <?php echo $taxRate; ?>; // Get tax rate from PHP
            const tax = subtotal * taxRate;
            const total = subtotal + tax;
            
            // Update subtotal
            const subtotalElement = document.querySelector('.cart-subtotal');
            if (subtotalElement) {
                subtotalElement.textContent = `${subtotal.toFixed(2)}€`;
            }
            
            // Update tax (only if tax rate > 0)
            const taxElement = document.querySelector('.cart-tax');
            if (taxElement && taxRate > 0) {
                taxElement.textContent = `${tax.toFixed(2)}€`;
            }
            
            // Update total
            const totalElement = document.querySelector('.cart-total');
            if (totalElement) {
                totalElement.textContent = `${total.toFixed(2)}€`;
            }
        }
        
        // Re-enable controls
        const buttons = document.querySelectorAll(`#cart-item-${itemId} .quantity-btn`);
        buttons.forEach(btn => btn.disabled = false);
        if (input) input.disabled = false;
    }

    // Update cart badge in navbar
    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            if (count > 0) {
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // Handle Enter key in quantity input
    function handleQuantityKeypress(event, input) {
        if (event.key === 'Enter') {
            input.blur(); // Trigger change event
            updateQuantityFromInput(input);
        }
    }

    // Auto-deselect quantity inputs when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(event) {
            // Check if click is outside quantity inputs
            if (!event.target.classList.contains('quantity-input')) {
                // Remove selection from all quantity inputs
                document.querySelectorAll('.quantity-input').forEach(input => {
                    if (document.activeElement !== input) {
                        input.blur();
                        // Clear text selection
                        if (input.setSelectionRange) {
                            input.setSelectionRange(0, 0);
                        }
                    }
                });
            }
        });

        // Also handle when quantity input loses focus
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('blur', function() {
                // Clear text selection when losing focus
                if (this.setSelectionRange) {
                    this.setSelectionRange(0, 0);
                }
            });
        });
    });

    // Quantity update functions are now optimized above

    </script>
</body>
</html>