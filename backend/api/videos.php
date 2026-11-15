<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/videos.php';

ad_cors_public();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$lang = trim((string) ($_GET['lang'] ?? 'en'));
if (!in_array($lang, ad_supported_languages(), true)) {
    $lang = 'en';
}

$placement = trim((string) ($_GET['placement'] ?? ''));
if (!ad_video_placement_valid($placement)) {
    ad_json(['ok' => true, 'items' => []]);
}

ad_json([
    'ok' => true,
    'items' => ad_videos_list_public($lang, $placement),
]);