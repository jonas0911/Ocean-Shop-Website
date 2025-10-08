// Pterodactyl Egg Selector Component
// Global registry for egg selectors
window.eggSelectors = window.eggSelectors || new Map();

class PterodactylEggSelector {
    constructor(containerId, inputId, displayId = null) {
        this.container = document.getElementById(containerId);
        this.inputId = inputId;
        this.displayId = displayId;
        this.eggs = [];
        this.filteredEggs = [];
        this.selectedEgg = null;
        this.isOpen = false;
        this.selectorId = containerId + '_' + Date.now();
        
        // Register this instance
        window.eggSelectors.set(this.selectorId, this);
        this.container.dataset.selectorId = this.selectorId;
        
        this.init();
        this.loadEggs();
    }
    
    init() {
        this.container.innerHTML = `
            <div class="egg-selector-wrapper position-relative">
                <div class="input-group">
                    <input type="text" 
                           class="form-control egg-search" 
                           placeholder="Suche oder wähle ein Egg..."
                           autocomplete="off">
                    <button class="btn btn-outline-secondary dropdown-toggle" 
                            type="button">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <input type="hidden" id="${this.inputId}" name="pterodactyl_egg_id">
                ${this.displayId ? `<input type="hidden" id="${this.displayId}">` : ''}
                <div class="egg-dropdown position-absolute w-100 border rounded shadow" 
                     style="display: none; max-height: 300px; overflow-y: auto; z-index: 1050;">
                    <div class="egg-loading p-3 text-center">
                        <i class="fas fa-spinner fa-spin"></i> Lade Eggs...
                    </div>
                </div>
            </div>
        `;
        
        this.searchInput = this.container.querySelector('.egg-search');
        this.dropdown = this.container.querySelector('.egg-dropdown');
        this.dropdownToggle = this.container.querySelector('.dropdown-toggle');
        this.hiddenInput = document.getElementById(this.inputId);
        
        this.bindEvents();
        this.bindThemeEvents();
    }
    
    bindThemeEvents() {
        // Listen for theme changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    // Theme changed, update styles if needed
                    this.updateThemeStyles();
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }
    
    updateThemeStyles() {
        // Force re-render dropdown if open to apply new theme styles
        if (this.isOpen) {
            this.renderDropdown();
        }
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('input', (e) => {
            this.filterEggs(e.target.value);
            this.showDropdown();
        });
        
        this.searchInput.addEventListener('focus', () => {
            this.showDropdown();
        });
        
        // Dropdown toggle
        this.dropdownToggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.isOpen ? this.hideDropdown() : this.showDropdown();
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.hideDropdown();
            }
        });
        
        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateDropdown('down');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateDropdown('up');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const selected = this.dropdown.querySelector('.egg-item.selected');
                if (selected) {
                    this.selectEgg(JSON.parse(selected.dataset.egg));
                }
            } else if (e.key === 'Escape') {
                this.hideDropdown();
            }
        });
    }
    
    async loadEggs() {
        try {
            // Use the working endpoint directly
            const endpoint = '/ocean/shop/direct-eggs-api.php';
            
            console.log('Loading eggs from:', endpoint);
            const response = await fetch(endpoint);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const text = await response.text();
            
            if (!text.startsWith('{') && !text.startsWith('[')) {
                throw new Error('Response is not JSON: ' + text.substring(0, 100));
            }
            
            const data = JSON.parse(text);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            this.eggs = data.eggs || [];
            this.sortEggs();
            this.filteredEggs = [...this.eggs];
            this.renderDropdown();
            console.log('Successfully loaded', this.eggs.length, 'eggs');
            
        } catch (error) {
            console.error('Error loading eggs:', error);
            this.dropdown.innerHTML = `
                <div class="p-3 text-danger">
                    <i class="fas fa-exclamation-triangle mb-2"></i> 
                    <div>Fehler beim Laden der Eggs: ${error.message}</div>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="window.eggSelectors?.get(this.closest('.egg-selector-wrapper').parentElement.dataset.selectorId)?.reload()">
                        <i class="fas fa-redo"></i> Erneut versuchen
                    </button>
                </div>
            `;
        }
    }
    
    sortEggs() {
        // Sort eggs by nest_name first, then by ID within each nest
        this.eggs.sort((a, b) => {
            // First sort by nest name
            if (a.nest_name !== b.nest_name) {
                return a.nest_name.localeCompare(b.nest_name);
            }
            // Then sort by ID within the same nest
            return parseInt(a.id) - parseInt(b.id);
        });
    }
    
    filterEggs(query) {
        const searchTerm = query.toLowerCase();
        this.filteredEggs = this.eggs.filter(egg => 
            egg.name.toLowerCase().includes(searchTerm)
        );
        // Keep sorting after filtering
        this.filteredEggs.sort((a, b) => {
            // First sort by nest name
            if (a.nest_name !== b.nest_name) {
                return a.nest_name.localeCompare(b.nest_name);
            }
            // Then sort by ID within the same nest
            return parseInt(a.id) - parseInt(b.id);
        });
        this.renderDropdown();
    }
    
    renderDropdown() {
        if (this.filteredEggs.length === 0) {
            this.dropdown.innerHTML = `
                <div class="p-3 text-muted">
                    <i class="fas fa-search"></i> Keine Eggs gefunden
                </div>
            `;
            return;
        }
        
        let currentNest = '';
        let html = '';
        
        this.filteredEggs.forEach((egg, index) => {
            // Add nest header if different from previous
            if (egg.nest_name !== currentNest) {
                currentNest = egg.nest_name;
                html += `
                    <div class="nest-header px-3 py-2 border-bottom">
                        <small class="text-muted fw-bold">
                            <i class="fas fa-folder"></i> ${egg.nest_name}
                        </small>
                    </div>
                `;
            }
            
            html += `
                <div class="egg-item px-3 py-2 border-bottom cursor-pointer" 
                     data-egg='${JSON.stringify(egg)}'
                     data-index="${index}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${egg.name}</div>
                            <div class="small text-muted">ID: ${egg.id}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">${egg.id}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        this.dropdown.innerHTML = html;
        
        // Bind click events to egg items
        this.dropdown.querySelectorAll('.egg-item').forEach(item => {
            item.addEventListener('click', () => {
                const egg = JSON.parse(item.dataset.egg);
                this.selectEgg(egg);
            });
            
            item.addEventListener('mouseenter', () => {
                this.dropdown.querySelectorAll('.egg-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            });
        });
    }
    
    selectEgg(egg) {
        this.selectedEgg = egg;
        this.searchInput.value = `${egg.name} (ID: ${egg.id})`;
        this.hiddenInput.value = egg.id;
        
        if (this.displayId) {
            const displayInput = document.getElementById(this.displayId);
            if (displayInput) {
                displayInput.value = JSON.stringify(egg);
            }
        }
        
        // Auto-fill Docker image and startup command if they're empty
        this.autoFillRelatedFields(egg);
        
        this.hideDropdown();
        
        // Trigger change event
        this.hiddenInput.dispatchEvent(new Event('change'));
    }
    
    autoFillRelatedFields(egg) {
        console.log('Auto-filling fields for egg:', egg);
        
        // Find the correct fields and display elements
        const modalContext = this.container.closest('.modal');
        const isAddModal = modalContext && modalContext.id === 'addGameModal';
        const isEditModal = modalContext && modalContext.id === 'editGameModal';
        
        const prefix = isAddModal ? 'add' : 'edit';
        
        // Hidden fields for form submission
        const dockerImageField = document.getElementById(`${prefix}DockerImage`);
        const startupField = document.getElementById(`${prefix}StartupCommand`);
        const envField = document.getElementById(`${prefix}Environment`);
        const portField = document.getElementById(`${prefix}DefaultPort`);
        
        // Display elements for user feedback
        const dockerImageDisplay = document.getElementById(`${prefix}DockerImageDisplay`);
        const startupCommandDisplay = document.getElementById(`${prefix}StartupCommandDisplay`);
        const environmentDisplay = document.getElementById(`${prefix}EnvironmentDisplay`);
        const eggInfoCard = document.getElementById(`${prefix}EggInfo`);
        
        // Set Docker Image
        if (dockerImageField && egg.docker_image) {
            dockerImageField.value = egg.docker_image;
            if (dockerImageDisplay) {
                dockerImageDisplay.textContent = egg.docker_image;
                dockerImageDisplay.className = 'text-success';
            }
            console.log('Docker image set to:', egg.docker_image);
        }
        
        // Set Startup Command
        if (startupField && egg.startup) {
            startupField.value = egg.startup;
            if (startupCommandDisplay) {
                startupCommandDisplay.textContent = egg.startup;
                startupCommandDisplay.className = 'text-success';
            }
            console.log('Startup command set to:', egg.startup);
        }
        
        // Set Environment Variables
        if (envField && egg.environment && typeof egg.environment === 'object' && Object.keys(egg.environment).length > 0) {
            const envString = Object.entries(egg.environment)
                .map(([key, value]) => `${key}=${value}`)
                .join('\n');
            
            envField.value = envString;
            if (environmentDisplay) {
                const envDisplayString = Object.entries(egg.environment)
                    .map(([key, value]) => `${key}=${value}`)
                    .join(', ');
                environmentDisplay.textContent = envDisplayString;
                environmentDisplay.className = 'text-success';
            }
            console.log('Environment variables set:', envString);
        } else {
            if (envField) envField.value = '';
            if (environmentDisplay) {
                environmentDisplay.textContent = 'Keine Variablen';
                environmentDisplay.className = 'text-muted';
            }
        }
        
        // Show the info card
        if (eggInfoCard) {
            eggInfoCard.style.display = 'block';
        }
        
        // Port assignment happens during individual server creation, not here
        
        // Show success feedback
        this.showSuccessFeedback(`Egg-Konfiguration von "${egg.name}" geladen`);
    }
    
    findFieldInContext(fieldName) {
        // First try to find in current modal context
        const modalContext = this.container.closest('.modal');
        if (modalContext) {
            const field = modalContext.querySelector(`input[name="${fieldName}"], textarea[name="${fieldName}"]`);
            if (field) return field;
        }
        
        // Fallback to document-wide search
        return document.querySelector(`input[name="${fieldName}"], textarea[name="${fieldName}"]`);
    }
    
    flashSuccess(element) {
        element.classList.add('text-success');
        setTimeout(() => element.classList.remove('text-success'), 2000);
    }
    
    async assignNextAvailablePort(portField) {
        try {
            console.log('Fetching next available port...');
            const response = await fetch('/ocean/shop/get-next-port.php');
            const data = await response.json();
            
            if (data.success) {
                portField.value = data.port;
                this.flashSuccess(portField);
                console.log('Port assigned:', data.port);
                
                // Show additional info in success message
                this.showSuccessFeedback(`Port ${data.port} automatisch zugewiesen (${data.used_ports_count} belegt, ${data.available_ports_count} verfügbar)`);
            } else {
                console.error('Port assignment failed:', data.error);
                // Keep default port but show warning
                this.showWarningFeedback('Port-Automatik fehlgeschlagen: ' + data.error);
            }
        } catch (error) {
            console.error('Error fetching port:', error);
            this.showWarningFeedback('Port-Automatik nicht verfügbar: ' + error.message);
        }
    }
    
    showSuccessFeedback(message) {
        this.showFeedback(message, 'success', 'fas fa-check-circle');
    }
    
    showWarningFeedback(message) {
        this.showFeedback(message, 'warning', 'fas fa-exclamation-triangle');
    }
    
    showFeedback(message, type, icon) {
        // Create or update feedback message
        let feedback = document.querySelector('.egg-selector-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = `egg-selector-feedback alert alert-${type} alert-dismissible fade mt-2`;
            feedback.setAttribute('role', 'alert');
            this.container.appendChild(feedback);
        } else {
            feedback.className = `egg-selector-feedback alert alert-${type} alert-dismissible fade mt-2 show`;
        }
        
        feedback.innerHTML = `
            <i class="${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        feedback.classList.add('show');
        
        // Auto-hide after 4 seconds for warnings, 3 for success
        const hideAfter = type === 'warning' ? 4000 : 3000;
        setTimeout(() => {
            if (feedback && feedback.classList.contains('show')) {
                feedback.classList.remove('show');
                setTimeout(() => feedback.remove(), 150);
            }
        }, hideAfter);
    }
    
    showDropdown() {
        this.dropdown.style.display = 'block';
        this.isOpen = true;
        this.dropdownToggle.querySelector('i').className = 'fas fa-chevron-up';
    }
    
    hideDropdown() {
        this.dropdown.style.display = 'none';
        this.isOpen = false;
        this.dropdownToggle.querySelector('i').className = 'fas fa-chevron-down';
        this.dropdown.querySelectorAll('.egg-item').forEach(item => item.classList.remove('selected'));
    }
    
    navigateDropdown(direction) {
        const items = this.dropdown.querySelectorAll('.egg-item');
        if (items.length === 0) return;
        
        let selectedIndex = -1;
        items.forEach((item, index) => {
            if (item.classList.contains('selected')) {
                selectedIndex = index;
            }
            item.classList.remove('selected');
        });
        
        if (direction === 'down') {
            selectedIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
        } else {
            selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
        }
        
        items[selectedIndex].classList.add('selected');
        items[selectedIndex].scrollIntoView({ block: 'nearest' });
    }
    
    setValue(eggId) {
        if (!eggId) {
            this.clear();
            return;
        }
        
        const egg = this.eggs.find(e => e.id == eggId);
        if (egg) {
            this.selectEgg(egg);
        } else {
            // If egg not found, just set the ID
            this.hiddenInput.value = eggId;
            this.searchInput.value = `Egg ID: ${eggId}`;
        }
    }
    
    clear() {
        this.selectedEgg = null;
        this.searchInput.value = '';
        this.hiddenInput.value = '';
        if (this.displayId) {
            const displayInput = document.getElementById(this.displayId);
            if (displayInput) displayInput.value = '';
        }
    }
    
    reload() {
        this.dropdown.innerHTML = '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Lade Eggs...</div>';
        this.loadEggs();
    }
}

// CSS Styles with Theme Support
const eggSelectorStyles = `
<style>
/* Light Mode - Blue Ocean Theme like Homepage */
.egg-selector-wrapper .egg-dropdown {
    background-color: #2a7bc4 !important;
    border: 1px solid #1e5a8a !important;
    color: white !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.egg-selector-wrapper .egg-item {
    transition: background-color 0.2s;
    color: white !important;
}

.egg-selector-wrapper .egg-item:hover,
.egg-selector-wrapper .egg-item.selected {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.egg-selector-wrapper .egg-item.selected {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
}

.egg-selector-wrapper .cursor-pointer {
    cursor: pointer;
}

.egg-selector-wrapper .nest-header {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #1e5a8a !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.egg-selector-wrapper .nest-header .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.egg-selector-wrapper .small.text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.egg-selector-wrapper .fw-semibold {
    color: white !important;
}

.egg-selector-wrapper .badge.bg-primary {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
}

/* Dark theme specific adjustments */
[data-theme="dark"] .egg-selector-wrapper .egg-dropdown {
    background-color: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    color: #e2e8f0 !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

[data-theme="dark"] .egg-selector-wrapper .egg-item {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .egg-selector-wrapper .egg-item:hover,
[data-theme="dark"] .egg-selector-wrapper .egg-item.selected {
    background-color: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .egg-selector-wrapper .egg-item.selected {
    background-color: rgba(13, 110, 253, 0.2);
}

[data-theme="dark"] .egg-selector-wrapper .nest-header {
    background-color: #4a5568 !important;
    border-color: #6b7280 !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 4px rgba(255,255,255,0.1);
}

[data-theme="dark"] .egg-selector-wrapper .nest-header .text-muted {
    color: #9ca3af !important;
}

[data-theme="dark"] .egg-selector-wrapper .small.text-muted {
    color: #9ca3af !important;
}

[data-theme="dark"] .egg-selector-wrapper .fw-semibold {
    color: #f7fafc !important;
}

[data-theme="dark"] .egg-selector-wrapper .badge.bg-primary {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}
</style>
`;

// Add styles to head if not already present
if (!document.querySelector('#egg-selector-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'egg-selector-styles';
    styleElement.innerHTML = eggSelectorStyles;
    document.head.appendChild(styleElement);
}