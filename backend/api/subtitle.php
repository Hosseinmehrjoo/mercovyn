<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/videos.php';
require_once dirname(__DIR__) . '/lib/subtitles.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lang = trim((string) ($_GET['lang'] ?? 'en'));
if ($id < 1 || !in_array($lang, ad_supported_languages(), true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$row = ad_videos_get($id);
if (!$row || !(int) ($row['is_active'] ?? 0)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$stored = ad_video_subtitle_path($row, $lang);
if ($stored === '') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$abs = ad_subtitle_file_absolute($stored);
if ($abs === '') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$srt = file_get_contents($abs);
if ($srt === false) {
    http_response_code(500);
    echo 'Error';
    exit;
}

header('Content-Type: text/vtt; charset=utf-8');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo ad_srt_to_vtt($srt);
exit;