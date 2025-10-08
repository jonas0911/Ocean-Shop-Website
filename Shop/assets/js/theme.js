// Central Theme Management System
if (typeof ThemeManager === 'undefined') {
    class ThemeManager {
        constructor() {
            this.init();
        }
    
    init() {
        // Check for saved theme or use system preference
        const savedTheme = localStorage.getItem('ocean-theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const currentTheme = savedTheme || systemTheme;
        
        this.setTheme(currentTheme);
        this.bindEvents();
        this.createToggleButton();
    }
    
    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('ocean-theme', theme);
        
        // Update all theme icons on the page
        const icons = document.querySelectorAll('#themeIcon, #theme-icon, .theme-icon');
        icons.forEach(icon => {
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
        
        // Update toggle button title
        const toggleBtns = document.querySelectorAll('#themeToggle, #theme-toggle, .theme-toggle');
        toggleBtns.forEach(btn => {
            if (btn) {
                btn.title = theme === 'dark' ? 'Light Mode aktivieren' : 'Dark Mode aktivieren';
            }
        });
    }
    
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }
    
    createToggleButton() {
        // Only create if not already exists
        if (document.getElementById('themeToggle') || document.getElementById('theme-toggle')) return;
        
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'themeToggle';
        toggleBtn.className = 'theme-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-moon theme-icon" id="themeIcon"></i>';
        toggleBtn.title = 'Dark Mode aktivieren';
        
        document.body.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', () => this.toggleTheme());
    }
    
    bindEvents() {
        // Bind to existing toggle buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('#themeToggle, #theme-toggle, .theme-toggle')) {
                this.toggleTheme();
            }
        });
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('ocean-theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
}

// Auto-initialize on all pages
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.oceanTheme === 'undefined') {
        window.oceanTheme = new ThemeManager();
    }
});

// Export for manual use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
}