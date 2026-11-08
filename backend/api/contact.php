<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/router.php';
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

ad_cors_public();
ad_security_headers();

/**
 * Simple rate limiting: max 5 requests per IP per 15 minutes
 */
function ad_rate_limit_contact(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'contact_rate_' . md5($ip);
    $window = 15 * 60; // 15 minutes
    $maxRequests = 5;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $now = time();
    $data = $_SESSION[$key] ?? ['count' => 0, 'window_start' => $now];

    if ($now - $data['window_start'] > $window) {
        $data = ['count' => 0, 'window_start' => $now];
    }

    if ($data['count'] >= $maxRequests) {
        http_response_code(429);
        ad_json(['ok' => false, 'error' => 'Too many requests. Please try again later.'], 429);
    }

    $data['count']++;
    $_SESSION[$key] = $data;
}

ad_rate_limit_contact();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = ad_read_json_body();
if (!$input) {
    $input = $_POST;
}

$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$subject = trim((string) ($input['subject'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));

if ($name === '' || mb_strlen($name) > 120) {
    ad_json(['ok' => false, 'error' => 'نام معتبر نیست.'], 422);
}
if ($message === '' || mb_strlen($message) > 5000) {
    ad_json(['ok' => false, 'error' => 'متن پیام معتبر نیست.'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ad_json(['ok' => false, 'error' => 'ایمیل معتبر نیست.'], 422);
}
if ($email === '' && $phone === '') {
    ad_json(['ok' => false, 'error' => 'حداقل یکی از ایمیل یا تلفن را وارد کنید.'], 422);
}
if (mb_strlen($phone) > 40 || mb_strlen($subject) > 200 || mb_strlen($email) > 180) {
    ad_json(['ok' => false, 'error' => 'طول فیلدها بیش از حد مجاز است.'], 422);
}

try {
    $stmt = ad_db()->prepare(
        'INSERT INTO contact_messages (name, email, phone, subject, message)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $phone, $subject, $message]);
    ad_json(['ok' => true, 'message' => 'پیام شما با موفقیت ثبت شد.']);
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'خطای سرور در ذخیره پیام'], 500);
}
