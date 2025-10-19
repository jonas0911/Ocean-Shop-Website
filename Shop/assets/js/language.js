// Language Switcher for Ocean Hosting
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Language switcher script loaded');
        
        // Language switcher event listeners
        const languageLinks = document.querySelectorAll('.language-switcher a');
        console.log('Found language links:', languageLinks.length);
        
        languageLinks.forEach(function(link) {
            console.log('Setting up language link:', link.dataset.lang);
            
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const lang = this.dataset.lang;
                console.log('Language switch clicked:', lang);
                
                // Disable the link temporarily
                this.style.pointerEvents = 'none';
                
                // Get correct base path for API
                let basePath = window.location.pathname;
                if (basePath.includes('/ocean/shop/')) {
                    basePath = '/ocean/shop/';
                } else {
                    basePath = '/';
                }
                
                // Force language change by sending AJAX first, then redirect
                fetch(basePath + 'api/change-language', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ language: lang })
                })
                .then(response => {
                    console.log('API Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API Response data:', data);
                    if (data.success) {
                        // Reload page without changing URL
                        console.log('Language changed successfully, reloading page');
                        window.location.reload();
                    } else {
                        console.error('Language change failed:', data.message);
                        // Fallback: just reload page
                        window.location.reload();
                        currentUrl.searchParams.set('lang', lang);
                        window.location.href = currentUrl.toString();
                    }
                })
                .catch(error => {
                    console.error('Error changing language:', error);
                    // Fallback: just redirect with parameter
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('lang', lang);
                    window.location.href = currentUrl.toString();
                });
            }, true);
        });
    });
});