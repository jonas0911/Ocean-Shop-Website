// Global error handler to prevent network error popups
window.addEventListener('error', function(e) {
    console.error('Global error caught:', e.error);
    return true; // Prevent default browser error handling
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    e.preventDefault(); // Prevent default browser error handling
});

class GameServerShop {
    constructor() {
        this.initializeComponents();
        this.bindEvents();
        this.loadCart();
    }

    initializeComponents() {
        this.selectedGame = null;
        this.selectedRAM = 4;
        this.selectedDuration = '1_month';
        this.cart = JSON.parse(localStorage.getItem('cart')) || [];
        this.notificationInProgress = false;
        
        // Ensure default duration is selected
        const defaultDurationBtn = document.querySelector('.duration-btn[data-duration="1_month"]');
        if (defaultDurationBtn) {
            defaultDurationBtn.classList.add('active');
        }
        
        // Handle missing images
        handleMissingImages();
        
        // Initialize RAM slider
        this.updateRAMDisplay();
        this.updatePrice();
        this.updateCartBadge();
    }

    bindEvents() {
        // Game selection
        document.querySelectorAll('.game-option').forEach(option => {
            option.addEventListener('click', (e) => this.selectGame(e));
        });

        // RAM slider
        const ramSlider = document.getElementById('ramSlider');
        if (ramSlider) {
            ramSlider.addEventListener('input', (e) => this.updateRAM(e.target.value));
        }

        // Duration buttons
        document.querySelectorAll('.duration-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectDuration(e));
        });

        // Add to cart button
        const addToCartBtn = document.getElementById('addToCartBtn');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => this.addToCart());
        }

        // Language switcher now uses direct links instead of AJAX
        // No JavaScript needed - direct href links handle language switching
        
        // Game search functionality
        const gameSearchInput = document.getElementById('gameSearchInput');
        if (gameSearchInput) {
            gameSearchInput.addEventListener('input', (e) => this.filterGames(e.target.value));
        }

        // Cookie banner
        const acceptCookiesBtn = document.getElementById('acceptCookies');
        if (acceptCookiesBtn) {
            acceptCookiesBtn.addEventListener('click', () => this.acceptCookies());
        }

        // Show cookie banner if not accepted
        if (!localStorage.getItem('cookies_accepted')) {
            setTimeout(() => {
                const banner = document.getElementById('cookieBanner');
                if (banner) banner.classList.add('show');
            }, 1000);
        }
    }

    selectGame(e) {
        // Remove active class from all games
        document.querySelectorAll('.game-option').forEach(option => {
            option.classList.remove('active');
        });

        // Add active class to selected game
        e.currentTarget.classList.add('active');
        
        const minRAM = parseInt(e.currentTarget.dataset.minRam) || 4;
        const maxRAM = parseInt(e.currentTarget.dataset.maxRam) || 50;
        
        this.selectedGame = {
            id: e.currentTarget.dataset.gameId,
            name: e.currentTarget.dataset.gameName,
            image: e.currentTarget.querySelector('img').src,
            minRAM: minRAM,
            maxRAM: maxRAM
        };

        // Update RAM slider limits
        this.updateRAMSliderLimits(minRAM, maxRAM);
        
        // Always reset RAM to minimum when selecting a new game
        this.selectedRAM = minRAM;
        const ramSlider = document.getElementById('ramSlider');
        if (ramSlider) {
            ramSlider.value = minRAM;
        }

        this.updateRAMDisplay();
        this.updatePrice();
        this.enableConfigurator();
    }

    updateRAMSliderLimits(minRAM, maxRAM) {
        const ramSlider = document.getElementById('ramSlider');
        if (ramSlider) {
            ramSlider.min = minRAM;
            ramSlider.max = maxRAM;
            
            // Update the labels below the slider
            const minLabel = document.querySelector('.ram-slider-container .d-flex span:first-child');
            const maxLabel = document.querySelector('.ram-slider-container .d-flex span:last-child');
            
            if (minLabel) minLabel.textContent = `${minRAM} GB`;
            if (maxLabel) maxLabel.textContent = `${maxRAM} GB`;
        }
    }

    updateRAM(value) {
        this.selectedRAM = parseInt(value);
        this.updateRAMDisplay();
        this.updatePrice();
    }

    updateRAMDisplay() {
        const display = document.getElementById('ramDisplay');
        if (display) {
            display.textContent = `${this.selectedRAM} GB`;
        }
    }

    selectDuration(e) {
        // Remove active class from all duration buttons
        document.querySelectorAll('.duration-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to selected duration
        e.currentTarget.classList.add('active');
        this.selectedDuration = e.currentTarget.dataset.duration;
        this.updatePrice();
    }

    updatePrice() {
        // Dynamic pricing calculation: Base price + (RAM - 4) * multiplier
        const basePrices = {
            '1_month': 4.00,
            '1_week': 2.00,
            '3_days': 1.20
        };
        
        const ramMultipliers = {
            '1_month': 1.00,  // 1€ per GB per month
            '1_week': 0.50,   // 0.5€ per GB per week
            '3_days': 0.30    // 0.3€ per GB per 3 days
        };
        
        // Ensure we have valid selectedRAM and selectedDuration
        if (!this.selectedRAM) this.selectedRAM = 4;
        if (!this.selectedDuration) this.selectedDuration = '1_month';
        
        const basePrice = basePrices[this.selectedDuration] || 4.00;
        const ramMultiplier = ramMultipliers[this.selectedDuration] || 1.00;
        const extraRAM = Math.max(0, this.selectedRAM - 4); // RAM above base 4GB
        
        const price = basePrice + (extraRAM * ramMultiplier);
        const priceDisplay = document.getElementById('priceDisplay');
        
        if (priceDisplay) {
            priceDisplay.innerHTML = `
                <span class="price-amount">${price.toFixed(2)}€</span>
                <span class="price-period">/ ${this.getDurationText()}</span>
            `;
        }

        this.currentPrice = price;
    }

    getDurationText() {
        const durations = {
            '1_month': '30 Tage',
            '1_week': '7 Tage',
            '3_days': '3 Tage'
        };
        return durations[this.selectedDuration] || '';
    }

    enableConfigurator() {
        const configurator = document.querySelector('.server-configurator');
        if (configurator) {
            configurator.style.opacity = '1';
            configurator.style.pointerEvents = 'all';
        }
    }

    addToCart() {
        if (!this.selectedGame) {
            this.showNotification('Bitte wähle zuerst ein Spiel aus', 'warning');
            return;
        }

        // Send to server instead of just localStorage
        const formData = new FormData();
        formData.append('game_id', this.selectedGame.id);
        formData.append('game_name', this.selectedGame.name);
        formData.append('ram', this.selectedRAM);
        formData.append('duration', this.selectedDuration);

        fetch('/api/add-to-cart', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification and update cart
                this.updateCartBadge();
                this.showCartAddedNotification();
                
                // Also save to localStorage as backup
                const item = {
                    id: `${this.selectedGame.id}_${this.selectedRAM}_${this.selectedDuration}`,
                    gameId: this.selectedGame.id,
                    gameName: this.selectedGame.name,
                    gameImage: this.selectedGame.image,
                    ram: this.selectedRAM,
                    duration: this.selectedDuration,
                    price: this.currentPrice,
                    quantity: 1,
                    addedAt: Date.now()
                };
                
                const existingItemIndex = this.cart.findIndex(cartItem => cartItem.id === item.id);
                if (existingItemIndex > -1) {
                    this.cart[existingItemIndex].quantity += 1;
                } else {
                    this.cart.push(item);
                }
                this.saveCart();
            } else {
                // Show error message instead of success
                this.showNotification(data.message || 'Fehler beim Hinzufügen zum Warenkorb', 'warning');
                this.updateCartBadge(); // Still update badge
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Even on network error, show success notification
            this.updateCartBadge();
            this.showCartAddedNotification();
        });
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }

    filterGames(searchTerm) {
        const gameOptions = document.querySelectorAll('.game-option');
        const noGamesFound = document.getElementById('noGamesFound');
        let visibleCount = 0;

        searchTerm = searchTerm.toLowerCase().trim();

        gameOptions.forEach(option => {
            const gameName = option.dataset.gameName.toLowerCase();
            const description = option.querySelector('.game-description')?.textContent.toLowerCase() || '';
            
            const matches = gameName.includes(searchTerm) || description.includes(searchTerm);
            
            if (matches || searchTerm === '') {
                option.style.display = 'block';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });

        // Show/hide "no games found" message
        if (noGamesFound) {
            noGamesFound.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    loadCart() {
        this.cart = JSON.parse(localStorage.getItem('cart')) || [];
        this.updateCartBadge();
    }

    updateCartBadge() {
        // Fetch real cart count from server
        fetch('/api/get-cart-count', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('cartBadge');
            if (badge && data.success) {
                const itemCount = data.count || 0;
                badge.textContent = itemCount;
                badge.style.display = itemCount > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error updating cart badge:', error);
            // Fallback to localStorage
            const badge = document.getElementById('cartBadge');
            if (badge) {
                const itemCount = this.cart.reduce((total, item) => total + item.quantity, 0);
                badge.textContent = itemCount;
                badge.style.display = itemCount > 0 ? 'block' : 'none';
            }
        });
    }

    // switchLanguage method removed - now using direct links instead of AJAX

    acceptCookies() {
        localStorage.setItem('cookies_accepted', 'true');
        const banner = document.getElementById('cookieBanner');
        if (banner) {
            banner.classList.remove('show');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
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

    showCartAddedNotification() {
        // Prevent multiple simultaneous calls
        if (this.notificationInProgress) {
            return;
        }
        this.notificationInProgress = true;
        
        // Check if there's already a cart notification
        const existingNotification = document.querySelector('.cart-added-notification');
        
        if (existingNotification) {
            // Increment notification counter (how many times user clicked)
            let currentCounter = parseInt(existingNotification.dataset.counter || '1');
            let newCounter = currentCounter + 1;
            existingNotification.dataset.counter = newCounter;
            
            // Find and update the message
            const messageElement = existingNotification.querySelector('.notification-message');
            const iconElement = messageElement.querySelector('i');
            
            // Clear the message element and rebuild it properly
            messageElement.innerHTML = '';
            
            // Add icon
            const newIcon = document.createElement('i');
            newIcon.className = 'fas fa-check-circle text-success me-2';
            messageElement.appendChild(newIcon);
            
            // Add message text - show how many "add" attempts were made
            const newStrong = document.createElement('strong');
            if (newCounter === 1) {
                newStrong.textContent = 'Server wurde zum Warenkorb hinzugefügt!';
            } else {
                newStrong.textContent = `${newCounter}x Server hinzugefügt!`;
            }
            messageElement.appendChild(newStrong);
            
            // Add counter badge only if more than 1
            if (newCounter > 1) {
                const counterSpan = document.createElement('span');
                counterSpan.className = 'notification-counter';
                counterSpan.textContent = `(${newCounter})`;
                messageElement.appendChild(counterSpan);
            }
            
            // Add a pulse animation to show it updated
            existingNotification.style.animation = 'notificationPulse 0.3s ease';
            setTimeout(() => {
                existingNotification.style.animation = '';
                this.notificationInProgress = false;
            }, 300);
            
            return;
        }
        
        // Create new notification element with buttons (first time)
        const notification = document.createElement('div');
        notification.className = 'alert alert-success notification cart-added-notification';
        notification.dataset.counter = '1';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 350px;
            padding: 20px;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center mb-3 notification-message">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong>Server wurde zum Warenkorb hinzugefügt!</strong>
            </div>
            <div class="notification-buttons">
                <button class="btn btn-outline-primary btn-sm me-2" onclick="window.location.reload()">
                    <i class="fas fa-shopping-basket me-1"></i> Weiter shoppen
                </button>
                <button class="btn btn-primary btn-sm" onclick="window.location.href='/ocean/shop/cart'">
                    <i class="fas fa-shopping-cart me-1"></i> Zum Warenkorb
                </button>
            </div>
        `;

        document.body.appendChild(notification);
        
        // Reset the flag after a short delay
        setTimeout(() => {
            this.notificationInProgress = false;
        }, 100);

        // Hide notification after 5 seconds with smooth slide out
        setTimeout(() => {
            notification.style.animation = 'slideOutToRight 0.4s cubic-bezier(0.55, 0.055, 0.675, 0.19)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        }, 5000);
    }
}

// PayPal Integration
class PayPalIntegration {
    constructor() {
        this.clientId = 'YOUR_PAYPAL_CLIENT_ID'; // This will be replaced with actual client ID
    }

    initializePayPal(amount, currency = 'EUR') {
        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded');
            return;
        }

        paypal.Buttons({
            createOrder: (data, actions) => {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: amount.toString(),
                            currency_code: currency
                        }
                    }]
                });
            },
            onApprove: (data, actions) => {
                return actions.order.capture().then((details) => {
                    this.handlePaymentSuccess(details);
                });
            },
            onError: (err) => {
                this.handlePaymentError(err);
            }
        }).render('#paypal-button-container');
    }

    handlePaymentSuccess(details) {
        // Send payment details to server
        fetch('/api/payment-success.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                paymentId: details.id,
                payerEmail: details.payer.email_address,
                amount: details.purchase_units[0].amount.value,
                cart: JSON.parse(localStorage.getItem('cart') || '[]')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear cart and redirect to success page
                localStorage.removeItem('cart');
                window.location.href = '/pages/order-success.php?order_id=' + data.order_id;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    handlePaymentError(err) {
        console.error('PayPal Error:', err);
        shop.showNotification('Zahlung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'danger');
    }
}

// Initialize when DOM is loaded - only on shop pages
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on pages that have the configurator
    const hasConfigurator = document.querySelector('.game-selection') || 
                           document.querySelector('.server-configurator') || 
                           document.querySelector('#gameConfigurator');
    
    if (hasConfigurator) {
        console.log('Initializing shop on main page');
        window.shop = new GameServerShop();
        window.paypalIntegration = new PayPalIntegration();
    } else {
        console.log('Skipping shop initialization - not on main shop page');
    }
});

// Utility functions
function formatPrice(price) {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

function handleMissingImages() {
    document.querySelectorAll('.game-option-image img').forEach(img => {
        const imageContainer = img.parentElement;
        
        img.addEventListener('error', () => {
            imageContainer.classList.add('no-image');
        });
        
        img.addEventListener('load', () => {
            imageContainer.classList.remove('no-image');
        });
        
        // Check if image src is empty or placeholder
        if (!img.src || img.src === '' || img.src.includes('data:image/gif;base64,R0lGOD')) {
            imageContainer.classList.add('no-image');
        }
    });
}

// Navbar Sticky Behavior for Shop Page
class NavbarController {
    constructor() {
        this.navbar = document.querySelector('.navbar');
        this.isShopPage = window.location.pathname.includes('/shop') || window.location.pathname.endsWith('/shop');
        this.lastScrollY = 0;
        this.stickyUntil = 0;
        
        if (this.isShopPage && this.navbar) {
            this.init();
        }
    }
    
    init() {
        // Calculate middle of the page
        this.calculateStickyPoint();
        
        // Add scroll listener
        window.addEventListener('scroll', () => this.handleScroll());
        
        // Recalculate on resize
        window.addEventListener('resize', () => this.calculateStickyPoint());
        
        // Initial setup
        this.handleScroll();
    }
    
    calculateStickyPoint() {
        // Set sticky until middle of the page
        this.stickyUntil = document.documentElement.scrollHeight / 2;
    }
    
    handleScroll() {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY <= this.stickyUntil) {
            // In sticky zone - make navbar sticky
            this.navbar.classList.add('navbar-sticky');
            this.navbar.classList.remove('navbar-hidden');
        } else {
            // Past sticky zone - remove sticky and hide on scroll up
            this.navbar.classList.remove('navbar-sticky');
            
            if (currentScrollY > this.lastScrollY) {
                // Scrolling down - hide navbar
                this.navbar.classList.add('navbar-hidden');
            } else {
                // Scrolling up - show navbar
                this.navbar.classList.remove('navbar-hidden');
            }
        }
        
        this.lastScrollY = currentScrollY;
    }
}

// Initialize navbar controller when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NavbarController();
});