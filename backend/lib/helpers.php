<?php

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
