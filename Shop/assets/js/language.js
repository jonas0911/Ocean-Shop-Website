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
                
                // Force language change by sending AJAX first, then redirect
                fetch('/ocean/shop/api/change-language', {
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
                        // Force the URL parameter and reload
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('lang', lang);
                        currentUrl.searchParams.set('_t', Date.now()); // Cache buster
                        console.log('Redirecting to:', currentUrl.toString());
                        window.location.href = currentUrl.toString();
                    } else {
                        console.error('Language change failed:', data.message);
                        // Fallback: just redirect with parameter
                        const currentUrl = new URL(window.location.href);
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