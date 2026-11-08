<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/cookies.php';

ad_cors_public();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    ad_cookies_ensure_schema();
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'خطای دیتابیس'], 500);
}

if ($method === 'GET') {
    try {
        $s = ad_cookie_settings();
        ad_json([
            'ok' => true,
            'data' => [
                'banner_enabled' => (int) ($s['banner_enabled'] ?? 1) === 1,
                'banner_text' => (string) ($s['banner_text'] ?? ''),
                'accept_label' => (string) ($s['accept_label'] ?? 'پذیرش'),
                'reject_label' => (string) ($s['reject_label'] ?? 'رد'),
                'manage_label' => (string) ($s['manage_label'] ?? 'مدیریت کوکی'),
                'categories' => [
                    'necessary' => true,
                    'analytics' => (int) ($s['enable_analytics'] ?? 1) === 1,
                    'marketing' => (int) ($s['enable_marketing'] ?? 1) === 1,
                    'social' => (int) ($s['enable_social'] ?? 1) === 1,
                ],
                'privacy_url' => (string) ($s['privacy_url'] ?? ''),
            ],
        ]);
    } catch (Throwable $e) {
        ad_json(['ok' => false, 'error' => 'خطای سرور'], 500);
    }
}

if ($method !== 'POST') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = ad_read_json_body();
if (!$input) {
    $input = $_POST;
}

$action = (string) ($input['action'] ?? '');
if (!in_array($action, ['accept', 'reject', 'custom'], true)) {
    ad_json(['ok' => false, 'error' => 'نوع رضایت نامعتبر است.'], 422);
}

$visitorId = preg_replace('/[^a-zA-Z0-9\-_]/', '', (string) ($input['visitor_id'] ?? '')) ?? '';
$visitorId = substr($visitorId, 0, 64);

$settings = ad_cookie_settings();
$allowAnalytics = (int) ($settings['enable_analytics'] ?? 1) === 1;
$allowMarketing = (int) ($settings['enable_marketing'] ?? 1) === 1;
$allowSocial = (int) ($settings['enable_social'] ?? 1) === 1;

if ($action === 'accept') {
    $analytics = $allowAnalytics ? 1 : 0;
    $marketing = $allowMarketing ? 1 : 0;
    $social = $allowSocial ? 1 : 0;
} elseif ($action === 'reject') {
    $analytics = 0;
    $marketing = 0;
    $social = 0;
} else {
    $analytics = $allowAnalytics && !empty($input['analytics']) ? 1 : 0;
    $marketing = $allowMarketing && !empty($input['marketing']) ? 1 : 0;
    $social = $allowSocial && !empty($input['social']) ? 1 : 0;
}

$pageUrl = trim((string) ($input['page_url'] ?? ''));
if (mb_strlen($pageUrl) > 500) {
    $pageUrl = mb_substr($pageUrl, 0, 500);
}
$ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

try {
    $stmt = ad_db()->prepare(
        'INSERT INTO cookie_consents
         (visitor_id, action, necessary, analytics, marketing, social, ip_hash, user_agent, page_url)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $visitorId,
        $action,
        $analytics,
        $marketing,
        $social,
        ad_cookie_ip_hash(),
        $ua,
        $pageUrl,
    ]);
    ad_json([
        'ok' => true,
        'message' => 'رضایت کوکی ثبت شد.',
        'consent' => [
            'action' => $action,
            'necessary' => true,
            'analytics' => (bool) $analytics,
            'marketing' => (bool) $marketing,
            'social' => (bool) $social,
        ],
    ]);
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'خطا در ذخیره رضایت'], 500);
}
