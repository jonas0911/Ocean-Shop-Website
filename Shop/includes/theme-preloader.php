<!-- Anti-Flashbang Theme Script - Add this directly in <head> before any CSS -->
<script>
(function() {
    // Immediate theme detection to prevent flash
    const savedTheme = localStorage.getItem('ocean-theme');
    const systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const currentTheme = savedTheme || systemTheme;
    
    // Set theme immediately on document element
    document.documentElement.setAttribute('data-theme', currentTheme);
})();
</script>