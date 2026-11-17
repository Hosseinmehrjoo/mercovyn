<?php

/**
 * Geo-based language hint (country → site language).
 */

const LANG_MANUAL_COOKIE = 'site_language_manual';

/** @var list<string> */
const AD_GEO_ARAB_COUNTRY_CODES = [
    'AE', 'SA', 'EG', 'IQ', 'JO', 'KW', 'LB', 'LY', 'MA', 'OM', 'QA', 'SY', 'TN', 'YE',
    'BH', 'DZ', 'SD', 'PS', 'MR', 'KM', 'DJ', 'SO', 'EH',
];

function ad_geo_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $file = dirname(__DIR__) . '/config.php';
    if (!is_file($file)) {
        $cfg = ['geoip_enabled' => true, 'geoip_country_header' => ''];

        return $cfg;
    }
    /** @var array<string, mixed> $all */
    $all = require $file;
    $cfg = [
        'geoip_enabled' => (bool) ($all['geoip_enabled'] ?? true),
        'geoip_country_header' => trim((string) ($all['geoip_country_header'] ?? '')),
    ];

    return $cfg;
}

function ad_geo_set_manual_choice(bool $manual = true): void
{
    if (headers_sent()) {
        return;
    }
    setcookie(LANG_MANUAL_COOKIE, $manual ? '1' : '0', [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[LANG_MANUAL_COOKIE] = $manual ? '1' : '0';
}

function ad_geo_has_manual_choice(): bool
{
    return isset($_COOKIE[LANG_MANUAL_COOKIE]) && (string) $_COOKIE[LANG_MANUAL_COOKIE] === '1';
}

function ad_geo_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_TRUE_CLIENT_IP',
        'HTTP_X_REAL_IP',
    ];
    foreach ($headers as $key) {
        $raw = trim((string) ($_SERVER[$key] ?? ''));
        if ($raw !== '' && filter_var($raw, FILTER_VALIDATE_IP)) {
            return $raw;
        }
    }
    $xff = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($xff !== '') {
        foreach (explode(',', $xff) as $part) {
            $ip = trim($part);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
        $first = trim(explode(',', $xff)[0] ?? '');
        if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}

function ad_geo_country_from_headers(): string
{
    $cfg = ad_geo_config();
    $custom = $cfg['geoip_country_header'];
    if ($custom !== '') {
        $key = str_starts_with($custom, 'HTTP_') ? $custom : 'HTTP_' . strtoupper(str_replace('-', '_', $custom));
        $cc = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $cc)) {
            return $cc;
        }
    }
    $cf = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
    if (preg_match('/^[A-Z]{2}$/', $cf) && $cf !== 'XX' && $cf !== 'T1') {
        return $cf;
    }
    foreach (['HTTP_X_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE'] as $key) {
        $cc = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $cc)) {
            return $cc;
        }
    }

    return '';
}

function ad_geo_lookup_country_api(string $ip): string
{
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }
    $cacheDir = sys_get_temp_dir() . '/mercovyn-geo-cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($ip) . '.txt';
    if (is_file($cacheFile) && filemtime($cacheFile) > time() - 86400) {
        $cached = strtoupper(trim((string) file_get_contents($cacheFile)));

        return preg_match('/^[A-Z]{2}$/', $cached) ? $cached : '';
    }
    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 2.0,
            'ignore_errors' => true,
            'header' => "User-Agent: Mercovyn-Geo/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        return '';
    }
    $data = json_decode($body, true);
    if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
        return '';
    }
    $cc = strtoupper(trim((string) ($data['countryCode'] ?? '')));
    if (preg_match('/^[A-Z]{2}$/', $cc)) {
        @file_put_contents($cacheFile, $cc);

        return $cc;
    }

    return '';
}

function ad_geo_country_code(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }
    $fromHeader = ad_geo_country_from_headers();
    if ($fromHeader !== '') {
        $resolved = $fromHeader;

        return $resolved;
    }
    $resolved = ad_geo_lookup_country_api(ad_geo_client_ip());

    return $resolved;
}

function ad_geo_lang_for_country(string $countryCode): ?string
{
    $cc = strtoupper(trim($countryCode));
    if ($cc === '' || $cc === 'XX' || $cc === 'T1') {
        return null;
    }
    if ($cc === 'RU') {
        return 'ru';
    }
    if ($cc === 'AM') {
        return 'hy';
    }
    if (in_array($cc, AD_GEO_ARAB_COUNTRY_CODES, true)) {
        return 'ar';
    }

    return null;
}

function ad_geo_is_bot_user_agent(): bool
{
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') {
        return false;
    }
    foreach (['googlebot', 'bingbot', 'yandex', 'duckduckbot', 'slurp', 'baiduspider', 'facebot', 'ia_archiver'] as $bot) {
        if (str_contains($ua, $bot)) {
            return true;
        }
    }

    return false;
}

function ad_request_clean_path_segments(): array
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');
    if ($path === '') {
        return [];
    }
    $segments = explode('/', $path);
    if (in_array($segments[0] ?? '', SUPPORTED_LANGUAGES, true)) {
        array_shift($segments);
    }

    return $segments;
}

function ad_geo_is_default_language_path(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $path = trim($path, '/');
    if ($path === '') {
        return true;
    }
    $first = explode('/', $path)[0] ?? '';

    return !in_array($first, SUPPORTED_LANGUAGES, true);
}

function ad_geo_handle_lang_picked_query(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    if (!isset($_GET['lang_picked']) || (string) $_GET['lang_picked'] !== '1') {
        return;
    }
    ad_geo_set_manual_choice(true);
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    unset($query['lang_picked']);
    $qs = $query !== [] ? '?' . http_build_query($query) : '';
    header('Location: ' . $path . $qs, true, 302);
    exit;
}

function maybeRedirectGeoLanguage(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $pathOnly = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (str_starts_with($pathOnly, '/backend')) {
        return;
    }
    $base = basename(trim($pathOnly, '/'));
    if (in_array($base, ['sitemap.xml', 'robots.txt', 'llms.txt'], true)) {
        return;
    }
    if (!ad_geo_config()['geoip_enabled']) {
        return;
    }
    if (ad_geo_has_manual_choice()) {
        return;
    }
    if (ad_geo_is_bot_user_agent()) {
        return;
    }
    if (!ad_geo_is_default_language_path()) {
        $first = explode('/', trim($pathOnly, '/'))[0] ?? '';
        if (in_array($first, ['ru', 'hy', 'ar'], true)) {
            ad_geo_set_manual_choice(true);
        }

        return;
    }
    $country = ad_geo_country_code();
    $lang = ad_geo_lang_for_country($country);
    if ($lang === null || $lang === DEFAULT_LANGUAGE) {
        return;
    }
    $segments = ad_request_clean_path_segments();
    $inner = implode('/', $segments);
    $target = url($inner === '' ? '' : $inner, $lang);
    $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    $qs = $query !== null && $query !== '' ? '?' . $query : '';
    $normCurrent = '/' . trim($pathOnly, '/') . ($pathOnly !== '/' && str_ends_with($pathOnly, '/') ? '/' : '');
    if ($pathOnly === '/') {
        $normCurrent = '/';
    }
    $normTarget = $target;
    if ($normCurrent === $normTarget || rtrim($normCurrent, '/') === rtrim($normTarget, '/')) {
        return;
    }
    header('Location: ' . $target . $qs, true, 302);
    exit;
}