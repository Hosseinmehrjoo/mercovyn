<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * @return list<string>
 */
function ad_translate_locales(): array
{
    return ['ru', 'hy'];
}

function ad_translate_enabled(): bool
{
    $c = ad_config();
    if (array_key_exists('auto_translate', $c)) {
        return (bool) $c['auto_translate'];
    }
    return true;
}

/**
 * @return array{provider:string,libretranslate_url:string,libretranslate_api_key:string,mymemory_email:string,source:string}
 */
function ad_translate_settings(): array
{
    $c = ad_config();
  $provider = (string) ($c['translate_provider'] ?? 'auto');
    return [
        'provider' => $provider,
        'libretranslate_url' => rtrim((string) ($c['libretranslate_url'] ?? 'http://127.0.0.1:5000'), '/'),
        'libretranslate_api_key' => (string) ($c['libretranslate_api_key'] ?? ''),
        'mymemory_email' => (string) ($c['mymemory_email'] ?? ''),
        'source' => (string) ($c['translate_source_lang'] ?? 'en'),
    ];
}

/** @var list<string> */
$GLOBALS['_ad_translate_warnings'] = [];

function ad_translate_take_warnings(): array
{
    $w = $GLOBALS['_ad_translate_warnings'] ?? [];
    $GLOBALS['_ad_translate_warnings'] = [];
    return $w;
}

function ad_translate_warn(string $message): void
{
    $GLOBALS['_ad_translate_warnings'][] = $message;
}

function ad_translate_http_post_json(string $url, array $body, int $timeout = 45): ?array
{
    $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return null;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $payload,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function ad_translate_http_get(string $url, int $timeout = 45): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
    $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function ad_translate_libretranslate(string $text, string $to, string $from, bool $html): ?string
{
    $s = ad_translate_settings();
    $url = $s['libretranslate_url'] . '/translate';
    $body = [
        'q' => $text,
        'source' => $from,
        'target' => $to,
        'format' => $html ? 'html' : 'text',
    ];
    if ($s['libretranslate_api_key'] !== '') {
        $body['api_key'] = $s['libretranslate_api_key'];
    }
    $res = ad_translate_http_post_json($url, $body);
    if (!$res || !isset($res['translatedText'])) {
        return null;
    }
    return (string) $res['translatedText'];
}

function ad_translate_mymemory(string $text, string $to, string $from): ?string
{
    $s = ad_translate_settings();
    $pair = rawurlencode($from . '|' . $to);
    $q = rawurlencode($text);
    $url = 'https://api.mymemory.translated.net/get?q=' . $q . '&langpair=' . $pair;
    if ($s['mymemory_email'] !== '') {
        $url .= '&de=' . rawurlencode($s['mymemory_email']);
    }
    $res = ad_translate_http_get($url);
    if (!$res || ($res['responseStatus'] ?? 0) != 200) {
        return null;
    }
    $segment = $res['responseData']['translatedText'] ?? '';
    if (!is_string($segment) || trim($segment) === '') {
        return null;
    }
    if (stripos($segment, 'MYMEMORY WARNING') !== false) {
        return null;
    }
    return $segment;
}

function ad_translate_once(string $text, string $to, string $from, bool $html): ?string
{
    $s = ad_translate_settings();
    $providers = [];
    if ($s['provider'] === 'libretranslate') {
        $providers = ['libretranslate', 'mymemory'];
    } elseif ($s['provider'] === 'mymemory') {
        $providers = ['mymemory', 'libretranslate'];
    } else {
        $providers = ['libretranslate', 'mymemory'];
    }
    foreach ($providers as $p) {
        if ($p === 'libretranslate') {
            $out = ad_translate_libretranslate($text, $to, $from, $html);
        } else {
            $out = ad_translate_mymemory($text, $to, $from);
        }
        if ($out !== null && trim($out) !== '') {
            return $out;
        }
    }
    return null;
}

/**
 * Translate one string (chunks long text).
 */
function ad_translate_text(string $text, string $to, string $from = 'en'): string
{
    $text = (string) $text;
    if (trim($text) === '' || !ad_translate_enabled()) {
        return '';
    }
    $allowedTargets = array_unique(array_merge(['en'], ad_translate_locales(), ad_supported_languages()));
    if (!in_array($to, $allowedTargets, true)) {
        return '';
    }
    $html = (bool) preg_match('/<[a-z][\s\S]*?>/i', $text);
    $maxChunk = 3500;
    if (mb_strlen($text) <= $maxChunk) {
        $t = ad_translate_once($text, $to, $from, $html);
        return $t ?? '';
    }
    $parts = preg_split('/(\r\n\r\n|\n\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        $parts = [$text];
    }
    $out = '';
    $buffer = '';
    foreach ($parts as $part) {
        $candidate = $buffer . $part;
        if (mb_strlen($candidate) > $maxChunk && $buffer !== '') {
            $piece = ad_translate_once($buffer, $to, $from, $html);
            if ($piece === null) {
                ad_translate_warn('ترجمه بخشی از متن برای ' . $to . ' ناموفق بود.');
                $out .= $buffer;
            } else {
                $out .= $piece;
            }
            $buffer = $part;
            usleep(120000);
            continue;
        }
        $buffer = $candidate;
    }
    if ($buffer !== '') {
        $piece = ad_translate_once($buffer, $to, $from, $html);
        if ($piece === null) {
            ad_translate_warn('ترجمه بخشی از متن برای ' . $to . ' ناموفق بود.');
            $out .= $buffer;
        } else {
            $out .= $piece;
        }
    }
    return $out;
}

/**
 * Build localized DB columns from English source fields.
 *
 * @param array<string, string> $english  e.g. title, excerpt, content, meta_title, …
 * @param list<string>          $fields   base field names without suffix
 * @param array<string, mixed>|null $existing prior row for fallback on API failure
 * @param list<string>          $slugFromTitleFields fields that get slug_{locale} from translated title
 *
 * @return array<string, string>
 */
function ad_auto_translate_record(
    array $english,
    array $fields,
    ?array $existing = null,
    array $slugFromTitleFields = ['title']
): array {
    $existing = $existing ?? [];
    $out = [];
    if (!ad_translate_enabled()) {
        foreach (ad_translate_locales() as $locale) {
            foreach ($fields as $field) {
                $key = $field . '_' . $locale;
                $out[$key] = trim((string) ($existing[$key] ?? ''));
            }
            foreach ($slugFromTitleFields as $tf) {
                $sk = 'slug_' . $locale;
                if (!isset($out[$sk]) || $out[$sk] === '') {
                    $out[$sk] = trim((string) ($existing[$sk] ?? ''));
                }
            }
        }
        return $out;
    }

    foreach (ad_translate_locales() as $locale) {
        foreach ($fields as $field) {
            $src = trim((string) ($english[$field] ?? ''));
            $key = $field . '_' . $locale;
            if ($src === '') {
                $out[$key] = '';
                continue;
            }
            $translated = ad_translate_text($src, $locale, ad_translate_settings()['source']);
            if ($translated === '') {
                $fallback = trim((string) ($existing[$key] ?? ''));
                $out[$key] = $fallback !== '' ? $fallback : $src;
                ad_translate_warn('ترجمه «' . $field . '» به ' . $locale . ' انجام نشد؛ مقدار قبلی یا انگلیسی نگه داشته شد.');
            } else {
                $out[$key] = $translated;
            }
            usleep(100000);
        }
        foreach ($slugFromTitleFields as $tf) {
            $titleKey = $tf . '_' . $locale;
            $slugKey = 'slug_' . $locale;
            $titleVal = trim((string) ($out[$titleKey] ?? ''));
            $out[$slugKey] = $titleVal !== '' ? ad_slugify($titleVal) : ad_slugify((string) ($english[$tf] ?? ''));
        }
    }

    return $out;
}