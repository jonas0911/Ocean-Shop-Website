<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/LanguageManager.php';

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'de';
}

$lang = new LanguageManager();
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
    <title>Nicht verfügbar auf Mobilgeräten - Ocean Hosting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/ocean/shop/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #4299e1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 50%, #2a4066 100%);
        }
        
        .blocked-container {
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 600px;
        }
        
        .blocked-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .blocked-container h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .blocked-container p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            opacity: 0.95;
        }
        
        .device-list {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .device-list h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        .device-list ul {
            list-style: none;
            padding: 0;
        }
        
        .device-list li {
            padding: 0.5rem 0;
            font-size: 1rem;
        }
        
        .device-list i {
            margin-right: 0.5rem;
            color: #4299e1;
        }
    </style>
</head>
<body>
    <div class="blocked-container">
        <i class="fas fa-mobile-alt blocked-icon"></i>
        <h1>📱 Nicht verfügbar auf Mobilgeräten</h1>
        <p>
            Ocean Hosting ist derzeit nur für Desktop-Computer, Laptops und Tablets optimiert.
        </p>
        <p>
            Für die beste Erfahrung und volle Funktionalität besuche uns bitte von einem größeren Gerät.
        </p>
        
        <div class="device-list">
            <h3><i class="fas fa-check-circle"></i> Unterstützte Geräte:</h3>
            <ul>
                <li><i class="fas fa-desktop"></i> Desktop-Computer</li>
                <li><i class="fas fa-laptop"></i> Laptops</li>
                <li><i class="fas fa-tablet-alt"></i> Tablets (iPad und größer)</li>
            </ul>
        </div>
        
        <p style="margin-top: 2rem; font-size: 0.9rem; opacity: 0.7;">
            <i class="fas fa-info-circle"></i> Eine mobile Version ist in Planung.
        </p>
    </div>
</body>
</html>
