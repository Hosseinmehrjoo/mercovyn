<?php

/** @return list<string> */
function ad_supported_languages(): array
{
    if (defined('SUPPORTED_LANGUAGES')) {
        return SUPPORTED_LANGUAGES;
    }

    return ['en', 'ru', 'hy', 'ar'];
}

function ad_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ad_cors_public(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($origin !== '') {
        $parts = parse_url($origin);
        $allowed = false;
        if (!empty($parts['host']) && strcasecmp($parts['host'], $host) === 0) {
            $allowed = true;
        }
        if (!$allowed && in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true)) {
            $allowed = true;
        }
        if ($allowed) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
        }
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function ad_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

const AD_PANEL_NAME = 'hossein mehrjoo panel';

function ad_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ad_slugify(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/[\s_]+/u', '-', $text) ?? $text;
    $text = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text) ?? $text;
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'post-' . date('YmdHis');
    }
    return mb_substr($text, 0, 200, 'UTF-8');
}

function ad_upload_image(array $file, string $subdir = ''): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('خطا در آپلود فایل.');
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('فرمت تصویر مجاز نیست (jpg, png, webp, gif).');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('حجم تصویر نباید بیشتر از ۵ مگابایت باشد.');
    }

    $config = ad_config();
    $baseDir = rtrim($config['upload_dir'], '/\\') . '/';
    if ($subdir !== '') {
        $baseDir .= trim($subdir, '/\\') . '/';
    }
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        throw new RuntimeException('ساخت پوشه آپلود ممکن نشد.');
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = $baseDir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('ذخیره فایل آپلودشده ممکن نشد.');
    }

    $prefix = rtrim($config['upload_url_prefix'], '/') . '/';
    if ($subdir !== '') {
        $prefix .= trim($subdir, '/') . '/';
    }
    return $prefix . $name;
}

function ad_upload_video(array $file, string $subdir = 'videos'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('خطا در آپلود فایل ویدیو.');
    }
    $allowed = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-m4v' => 'm4v',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $origName = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!is_string($mime) || !isset($allowed[$mime])) {
        $extMap = ['mp4' => 'mp4', 'webm' => 'webm', 'mov' => 'mov', 'm4v' => 'm4v'];
        if ($ext !== '' && isset($extMap[$ext]) && in_array($mime, ['application/octet-stream', 'application/mp4', 'binary/octet-stream'], true)) {
            $mime = $ext === 'webm' ? 'video/webm' : ($ext === 'mov' ? 'video/quicktime' : 'video/mp4');
        }
    }
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('فرمت ویدیو مجاز نیست (mp4, webm, mov).');
    }
    $maxBytes = 200 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('حجم ویدیو نباید بیشتر از ۲۰۰ مگابایت باشد.');
    }

    $config = ad_config();
    $baseDir = rtrim($config['upload_dir'], '/\\') . '/';
    if ($subdir !== '') {
        $baseDir .= trim($subdir, '/\\') . '/';
    }
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        throw new RuntimeException('ساخت پوشه آپلود ممکن نشد.');
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = $baseDir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('ذخیره فایل ویدیو ممکن نشد.');
    }

    $prefix = rtrim($config['upload_url_prefix'], '/') . '/';
    if ($subdir !== '') {
        $prefix .= trim($subdir, '/') . '/';
    }
    return $prefix . $name;
}

function ad_upload_subtitle_srt(array $file, string $subdir = 'subtitles'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('خطا در آپلود زیرنویس.');
    }
    $origName = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if ($ext !== 'srt') {
        throw new RuntimeException('فقط فایل SRT مجاز است.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('حجم فایل زیرنویس حداکثر ۲ مگابایت است.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $okMimes = ['text/plain', 'application/octet-stream', 'application/x-subrip'];
    if (!in_array($mime, $okMimes, true)) {
        throw new RuntimeException('نوع فایل زیرنویس نامعتبر است.');
    }

    $config = ad_config();
    $baseDir = rtrim($config['upload_dir'], '/\\') . '/';
    if ($subdir !== '') {
        $baseDir .= trim($subdir, '/\\') . '/';
    }
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        throw new RuntimeException('ساخت پوشه آپلود ممکن نشد.');
    }

    $name = bin2hex(random_bytes(8)) . '.srt';
    $dest = $baseDir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('ذخیره فایل زیرنویس ممکن نشد.');
    }

    $prefix = rtrim($config['upload_url_prefix'], '/') . '/';
    if ($subdir !== '') {
        $prefix .= trim($subdir, '/') . '/';
    }
    return $prefix . $name;
}

function ad_save_video_from_local_path(string $srcPath, string $origName, string $subdir = 'videos'): string
{
    if (!is_file($srcPath)) {
        throw new RuntimeException('فایل ویدیو پیدا نشد.');
    }
    $allowed = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-m4v' => 'm4v',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($srcPath);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!is_string($mime) || !isset($allowed[$mime])) {
        $extMap = ['mp4' => 'mp4', 'webm' => 'webm', 'mov' => 'mov', 'm4v' => 'm4v'];
        if ($ext !== '' && isset($extMap[$ext]) && in_array($mime, ['application/octet-stream', 'application/mp4', 'binary/octet-stream'], true)) {
            $mime = $ext === 'webm' ? 'video/webm' : ($ext === 'mov' ? 'video/quicktime' : 'video/mp4');
        }
    }
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('فرمت ویدیو مجاز نیست (mp4, webm, mov).');
    }
    $size = (int) filesize($srcPath);
    $maxBytes = 200 * 1024 * 1024;
    if ($size > $maxBytes) {
        throw new RuntimeException('حجم ویدیو نباید بیشتر از ۲۰۰ مگابایت باشد.');
    }

    $config = ad_config();
    $baseDir = rtrim($config['upload_dir'], '/\\') . '/';
    if ($subdir !== '') {
        $baseDir .= trim($subdir, '/\\') . '/';
    }
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        throw new RuntimeException('ساخت پوشه آپلود ممکن نشد.');
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = $baseDir . $name;
    if (!copy($srcPath, $dest)) {
        throw new RuntimeException('ذخیره فایل ویدیو ممکن نشد.');
    }

    $prefix = rtrim($config['upload_url_prefix'], '/') . '/';
    if ($subdir !== '') {
        $prefix .= trim($subdir, '/') . '/';
    }
    return $prefix . $name;
}

function ad_public_upload_url(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function ad_flash_set(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function ad_flash_get(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function ad_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function ad_csrf_check(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * PHP CLI binary for background jobs (never php-fpm — PHP_BINARY under FPM points at FPM).
 */
function ad_php_cli_binary(): string
{
    foreach (
        [
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php8.5',
            '/usr/bin/php8.4',
            '/usr/bin/php8.3',
            '/usr/bin/php8.2',
        ] as $candidate
    ) {
        if (is_executable($candidate)) {
            return $candidate;
        }
    }
    if (
        defined('PHP_BINARY')
        && is_string(PHP_BINARY)
        && PHP_BINARY !== ''
        && is_executable(PHP_BINARY)
        && stripos(basename(PHP_BINARY), 'fpm') === false
    ) {
        return PHP_BINARY;
    }

    return 'php';
}

function ad_exec_enabled(): bool
{
    if (!function_exists('exec')) {
        return false;
    }
    $disabled = ini_get('disable_functions');
    if (!is_string($disabled) || trim($disabled) === '') {
        return true;
    }
    $list = array_map('trim', explode(',', strtolower($disabled)));

    return !in_array('exec', $list, true);
}

/**
 * Run work after the HTTP response is sent (FPM-friendly fallback when exec is unavailable).
 */
function ad_run_after_http_response(callable $callback): void
{
    register_shutdown_function(static function () use ($callback): void {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        @set_time_limit(0);
        try {
            $callback();
        } catch (Throwable $e) {
            error_log('ad_run_after_http_response: ' . $e->getMessage());
        }
    });
}

/**
 * Run a PHP script under backend/ in the background (requires exec).
 */
function ad_spawn_background_php(string $relativePath, array $argv = []): bool
{
    if (!ad_exec_enabled()) {
        return false;
    }
    $root = dirname(__DIR__, 2);
    $script = $root . '/backend/' . ltrim($relativePath, '/');
    if (!is_file($script)) {
        return false;
    }
    $phpBin = ad_php_cli_binary();
    $cmd = escapeshellarg($phpBin)
        . ' ' . escapeshellarg($script);
    foreach ($argv as $arg) {
        $cmd .= ' ' . escapeshellarg((string) $arg);
    }
    $cmd .= ' > /dev/null 2>&1 &';
    @exec($cmd, $unused, $exitCode);
    if ($exitCode !== 0 && $exitCode !== null) {
        return false;
    }

    return true;
}
