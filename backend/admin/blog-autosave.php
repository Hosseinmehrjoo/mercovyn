<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/blog.php';
require_once dirname(__DIR__) . '/lib/media.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ad_is_logged_in() || !ad_user_can('blog')) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'login_required', 'message' => 'نشست پایان یافته — دوباره وارد شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ad_csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf', 'message' => 'نشست نامعتبر — صفحه را رفرش کنید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['ad_last_activity'] = time();

try {
    ad_blog_ensure_schema();
    $result = ad_blog_save_post_from_input($_POST, true);
    echo json_encode([
        'ok' => true,
        'id' => $result['id'],
        'slug' => $result['slug'],
        'status' => $result['status'],
        'saved_at' => $result['saved_at'],
        'created' => $result['created'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $code = str_contains($e->getMessage(), 'محتوایی') ? 200 : 400;
    http_response_code($code);
    echo json_encode([
        'ok' => false,
        'error' => 'save_failed',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}