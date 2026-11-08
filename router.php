<?php
/**
 * Language Detection and Routing System
 * 
 * URL Structure:
 * - domain.com/ (default: English)
 * - domain.com/ru/ (Russian)
 * - domain.com/hy/ (Armenian)
 * - domain.com/en/ (English - explicit)
 */

// Supported languages
const SUPPORTED_LANGUAGES = ['en', 'ru', 'hy'];
const DEFAULT_LANGUAGE = 'en';

// Language cookie name
const LANG_COOKIE = 'site_language';
const LANG_COOKIE_EXPIRY = 365 * 24 * 60 * 60; // 1 year

/**
 * Apply security headers
 */
function ad_security_headers(): void
{
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://use.fontawesome.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://use.fontawesome.com https://cdn.jsdelivr.net",
        "font-src 'self' data: https://fonts.gstatic.com https://use.fontawesome.com",
        "img-src 'self' data: https:",
        "connect-src 'self' https://wa.me",
        "frame-src https://www.google.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self' https://wa.me",
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

/**
 * Detect language from URL path
 */
function detectLanguageFromPath(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');
    
    // Check if first segment is a language code
    $segments = explode('/', $path);
    $firstSegment = $segments[0] ?? '';
    
    if (in_array($firstSegment, SUPPORTED_LANGUAGES, true)) {
        return $firstSegment;
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Get current language (from URL, cookie, or default)
 */
function getCurrentLanguage(): string
{
    // 1. Check URL path first (highest priority for SEO)
    $langFromUrl = detectLanguageFromPath();
    if ($langFromUrl !== DEFAULT_LANGUAGE || str_starts_with($_SERVER['REQUEST_URI'], '/' . DEFAULT_LANGUAGE . '/')) {
        // If URL explicitly contains language, use it
        setLanguageCookie($langFromUrl);
        return $langFromUrl;
    }
    
    // 2. Check cookie
    if (isset($_COOKIE[LANG_COOKIE]) && in_array($_COOKIE[LANG_COOKIE], SUPPORTED_LANGUAGES, true)) {
        return $_COOKIE[LANG_COOKIE];
    }
    
    // 3. Check Accept-Language header (fallback)
    $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach (SUPPORTED_LANGUAGES as $lang) {
        if (stripos($acceptLang, $lang) !== false) {
            return $lang;
        }
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Set language cookie
 */
function setLanguageCookie(string $lang): void
{
    if (in_array($lang, SUPPORTED_LANGUAGES, true)) {
        setcookie(LANG_COOKIE, $lang, [
            'expires' => time() + LANG_COOKIE_EXPIRY,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Get language prefix for URLs
 */
function getLanguagePrefix(string $lang): string
{
    return $lang === DEFAULT_LANGUAGE ? '' : '/' . $lang;
}

/**
 * Build localized URL
 */
function url(string $path, string $lang = ''): string
{
    $lang = $lang ?: getCurrentLanguage();
    $prefix = getLanguagePrefix($lang);
    $path = ltrim($path, '/');
    
    // Remove .php extension for cleaner URLs
    $path = preg_replace('/\.php$/', '', $path);
    
    // Handle root
    if ($path === '' || $path === 'index') {
        return $prefix . '/';
    }
    
    return $prefix . '/' . $path . '/';
}

/**
 * Get all language URLs for hreflang
 */
function getHreflangUrls(string $currentPath = ''): array
{
    $urls = [];
    $currentPath = ltrim($currentPath, '/');
    $currentPath = preg_replace('/\.php$/', '', $currentPath);
    
    foreach (SUPPORTED_LANGUAGES as $lang) {
        $prefix = getLanguagePrefix($lang);
        $path = $currentPath === '' ? '' : $currentPath . '/';
        $urls[$lang] = 'https://' . $_SERVER['HTTP_HOST'] . $prefix . '/' . $path;
    }
    
    // x-default points to default language
    $urls['x-default'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ($currentPath === '' ? '' : $currentPath . '/');
    
    return $urls;
}

/**
 * Load translation file
 */
function loadTranslations(string $lang): array
{
    $file = __DIR__ . "/lang/{$lang}.php";
    if (is_file($file)) {
        return require $file;
    }
    return require __DIR__ . "/lang/" . DEFAULT_LANGUAGE . ".php";
}

/**
 * Translation function
 */
function __($key, string $lang = ''): string
{
    static $translations = [];
    
    $lang = $lang ?: getCurrentLanguage();
    
    if (!isset($translations[$lang])) {
        $translations[$lang] = loadTranslations($lang);
    }
    
    return $translations[$lang][$key] ?? $key;
}

/**
 * Get current language info
 */
function getLanguageInfo(): array
{
    $current = getCurrentLanguage();
    return [
        'current' => $current,
        'prefix' => getLanguagePrefix($current),
        'supported' => SUPPORTED_LANGUAGES,
        'default' => DEFAULT_LANGUAGE,
        'urls' => getHreflangUrls(),
    ];
}

/**
 * Check if current URL has explicit language prefix
 */
function hasExplicitLanguagePrefix(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');
    $segments = explode('/', $path);
    return in_array($segments[0] ?? '', SUPPORTED_LANGUAGES, true);
}

/**
 * Root-absolute asset URL with cache-busting (?v=filemtime) for CSS/JS.
 */
function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    $full = __DIR__ . '/' . $path;
    $v = is_file($full) ? (string) filemtime($full) : '0';
    return '/' . $path . '?v=' . $v;
}