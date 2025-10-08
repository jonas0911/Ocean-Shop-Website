<?php
class LanguageManager {
    private $language;
    private $translations = [];

    public function __construct() {
        $this->detectLanguage();
        $this->loadTranslations();
    }

    private function detectLanguage() {
        // AJAX-Request für Sprachwechsel ohne URL-Änderung
        if (isset($_POST['change_language']) && in_array($_POST['change_language'], ['de', 'en'])) {
            $this->language = $_POST['change_language'];
            $_SESSION['language'] = $this->language;
            // JSON Response für AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'language' => $this->language]);
                exit;
            }
            return;
        }

        // URL-Parameter hat höchste Priorität
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
            $this->language = $_GET['lang'];
            $_SESSION['language'] = $this->language;
            error_log("LanguageManager: Language set from URL parameter: " . $this->language);
            return;
        }

        // Session-Sprache hat Vorrang vor Browser-Sprache
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], ['de', 'en'])) {
            $this->language = $_SESSION['language'];
            error_log("LanguageManager: Language set from session: " . $this->language);
            return;
        }

        // Default fallback: Deutsch (nicht Browser-Sprache)
        $this->language = 'de';
        $_SESSION['language'] = $this->language;
        error_log("LanguageManager: Using default language: " . $this->language);
    }

    private function loadTranslations() {
        $file = __DIR__ . '/../languages/' . $this->language . '.php';
        if (file_exists($file)) {
            $this->translations = include $file;
        }
    }

    public function get($key) {
        return isset($this->translations[$key]) ? $this->translations[$key] : $key;
    }

    public function getCurrentLanguage() {
        return $this->language;
    }

    public function setLanguage($lang) {
        if (in_array($lang, ['de', 'en'])) {
            $this->language = $lang;
            $_SESSION['language'] = $this->language;
            $this->loadTranslations();
            error_log("LanguageManager: Language manually set to: " . $this->language);
        }
    }
}

// Globale Funktion für Übersetzungen
function t($key) {
    if (isset($GLOBALS['lang']) && $GLOBALS['lang'] instanceof LanguageManager) {
        return $GLOBALS['lang']->get($key);
    }
    
    // Fallback: try global variable
    global $lang;
    if ($lang instanceof LanguageManager) {
        return $lang->get($key);
    }
    
    // Last fallback: create new instance
    $lang = new LanguageManager();
    $GLOBALS['lang'] = $lang;
    return $lang->get($key);
}
?>