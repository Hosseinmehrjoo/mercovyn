<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Convert SubRip (SRT) to WebVTT for HTML5 &lt;track&gt;.
 */
function ad_srt_to_vtt(string $srt): string
{
    $srt = str_replace(["\r\n", "\r"], "\n", trim($srt));
    if ($srt === '') {
        return "WEBVTT\n\n";
    }
    $blocks = preg_split("/\n\n+/", $srt) ?: [];
    $out = "WEBVTT\n\n";
    foreach ($blocks as $block) {
        $lines = explode("\n", trim($block));
        if (count($lines) < 2) {
            continue;
        }
        $timeLine = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/\d{2}:\d{2}:\d{2}[,.]\d{3}\s*-->\s*\d{2}:\d{2}:\d{2}[,.]\d{3}/', $line)) {
                $timeLine = $i;
                break;
            }
        }
        if ($timeLine === null) {
            continue;
        }
        $timing = str_replace(',', '.', $lines[$timeLine]);
        $text = implode("\n", array_slice($lines, $timeLine + 1));
        if (trim($text) === '') {
            continue;
        }
        $out .= $timing . "\n" . $text . "\n\n";
    }
    return $out;
}

function ad_subtitle_file_absolute(string $storedPath): string
{
    $storedPath = trim(str_replace('\\', '/', $storedPath));
    if ($storedPath === '' || str_contains($storedPath, '..')) {
        return '';
    }
    $config = ad_config();
    $uploadRoot = realpath(rtrim($config['upload_dir'], '/\\'));
    if ($uploadRoot === false) {
        return '';
    }
    $prefix = trim(str_replace('\\', '/', (string) $config['upload_url_prefix']), '/');
    $relative = $storedPath;
    if ($prefix !== '' && str_starts_with($storedPath, $prefix . '/')) {
        $relative = substr($storedPath, strlen($prefix) + 1);
    }
    $full = $uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $real = realpath($full);
    if ($real === false || !str_starts_with($real, $uploadRoot) || !is_file($real)) {
        return '';
    }
    return $real;
}