<?php
/**
 * Language Detection and Routing System
 * 
 * URL Structure:
 * - domain.com/ (default: English)
 * - domain.com/ru/ (Russian)
 * - domain.com/hy/ (Armenian)
 * - domain.com/ar/ (Arabic)
 * - domain.com/en/ (English - explicit)
 */

// Supported languages
const SUPPORTED_LANGUAGES = ['en', 'ru', 'hy', 'ar'];
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
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://use.fontawesome.com https://cdn.ckeditor.com https://www.clarity.ms",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://use.fontawesome.com https://cdn.jsdelivr.net https://cdn.ckeditor.com",
        "font-src 'self' data: https://fonts.gstatic.com https://use.fontawesome.com",
        "img-src 'self' data: https:",
        "media-src 'self' https: blob: data:",
        "connect-src 'self' https://wa.me https://www.clarity.ms https://c.clarity.ms https://q.clarity.ms",
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
 * Get current language from URL prefix only (SEO + switcher must match the address bar).
 * / = en, /ru/ = ru, /hy/ = hy, /en/ = en
 */
function getCurrentLanguage(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');
    $first = explode('/', $path)[0] ?? '';

    if (in_array($first, SUPPORTED_LANGUAGES, true)) {
        setLanguageCookie($first);
        return $first;
    }

    setLanguageCookie(DEFAULT_LANGUAGE);
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
 * Canonical public origin (https + www). Used for canonical, hreflang, sitemap.
 */
function getCanonicalSiteOrigin(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $default = 'https://www.mercovyn.com';
    $configFile = __DIR__ . '/backend/config.php';
    if (!is_file($configFile)) {
        $cached = $default;
        return $cached;
    }
    /** @var array<string, mixed> $config */
    $config = require $configFile;
    $url = trim((string) ($config['site_url'] ?? ''));
    if ($url === '') {
        $cached = $default;
        return $cached;
    }
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        $cached = $default;
        return $cached;
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
    if ($scheme !== 'https' && $scheme !== 'http') {
        $scheme = 'https';
    }
    if ($scheme === 'http') {
        $scheme = 'https';
    }
    $host = strtolower((string) $parts['host']);
    if (!str_starts_with($host, 'www.')) {
        $host = 'www.' . preg_replace('/^www\./', '', $host);
    }
    $cached = $scheme . '://' . $host;
    return $cached;
}

/**
 * Build one absolute canonical URL for a language path (no query string).
 */
function buildCanonicalUrl(string $langPrefixPath, string $pathWithoutLang = ''): string
{
    $origin = getCanonicalSiteOrigin();
    $pathWithoutLang = trim($pathWithoutLang, '/');
    $pathWithoutLang = preg_replace('/\.php$/', '', $pathWithoutLang);
    $langPrefixPath = trim($langPrefixPath, '/');
    if ($langPrefixPath === '' && $pathWithoutLang === '') {
        return $origin . '/';
    }
    if ($langPrefixPath === '') {
        return $origin . '/' . $pathWithoutLang . '/';
    }
    if ($pathWithoutLang === '') {
        return $origin . '/' . $langPrefixPath . '/';
    }

    return $origin . '/' . $langPrefixPath . '/' . $pathWithoutLang . '/';
}

/**
 * 301 to canonical host (www) and default English URL (/ not /en/) when needed.
 */
function enforceCanonicalRequest(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $query = parse_url($requestUri, PHP_URL_QUERY);
    $qs = $query !== null && $query !== '' ? '?' . $query : '';

    $origin = getCanonicalSiteOrigin();
    $canonicalHost = parse_url($origin, PHP_URL_HOST);
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $target = null;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    if ($canonicalHost && $requestHost === strtolower((string) $canonicalHost) && !$isHttps) {
        $target = $origin . $path . $qs;
    }

    if ($canonicalHost && $requestHost !== '' && strcasecmp($requestHost, $canonicalHost) !== 0) {
        $reqBare = preg_replace('/^www\./', '', $requestHost);
        $canBare = preg_replace('/^www\./', '', (string) $canonicalHost);
        if ($reqBare === $canBare) {
            $target = $origin . $path . $qs;
        }
    }

    if ($target === null && preg_match('#^/en(/.*)?$#', $path, $m)) {
        $rest = $m[1] ?? '';
        if ($rest === '' || $rest === '/') {
            $newPath = '/';
        } else {
            $newPath = $rest;
            if (!str_ends_with($newPath, '/')) {
                $newPath .= '/';
            }
        }
        $target = $origin . $newPath . $qs;
    }

    if ($target !== null) {
        header('Location: ' . $target, true, 301);
        exit;
    }
}

/**
 * @param array<string, string> $pathByLang Optional full path per lang without leading slash (e.g. blog-single/foo)
 * @return array<string, string> lang code => absolute URL
 */
function getHreflangUrls(string $currentPath = '', array $pathByLang = []): array
{
    $urls = [];
    $currentPath = ltrim($currentPath, '/');
    $currentPath = preg_replace('/\.php$/', '', $currentPath);

    foreach (SUPPORTED_LANGUAGES as $lang) {
        $prefix = getLanguagePrefix($lang);
        $prefixPart = $prefix === '' ? '' : trim($prefix, '/');
        if (isset($pathByLang[$lang]) && $pathByLang[$lang] !== '') {
            $urls[$lang] = buildCanonicalUrl($prefixPart, $pathByLang[$lang]);
            continue;
        }
        $urls[$lang] = buildCanonicalUrl($prefixPart, $currentPath);
    }

    $defaultPath = isset($pathByLang['en']) && $pathByLang['en'] !== ''
        ? $pathByLang['en']
        : $currentPath;
    $urls['x-default'] = buildCanonicalUrl('', $defaultPath);

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

/** Root-absolute path for static assets (works on /ru/ and /hy/ pages). */
function root_asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

/** Short HTML cache for CDN (dynamic PHP pages). */
function ad_send_html_cache_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
}

/** Long cache + Last-Modified for static files served via index.php. */
function ad_send_static_file_headers(string $absolutePath): void
{
    if (headers_sent() || !is_file($absolutePath)) {
        return;
    }
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($absolutePath)) . ' GMT');
    header('ETag: "' . dechex(filesize($absolutePath)) . '-' . dechex(filemtime($absolutePath)) . '"');
}