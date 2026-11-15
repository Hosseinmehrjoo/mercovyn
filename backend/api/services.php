<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/services.php';
require_once dirname(__DIR__) . '/lib/media.php';

ad_cors_public();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lang = trim((string) ($_GET['lang'] ?? ''));

$supportedLangs = ad_supported_languages();
if ($lang !== '' && !in_array($lang, $supportedLangs, true)) {
    $lang = '';
}

$suffix = $lang === 'en' || $lang === '' ? '' : '_' . $lang;

try {
    ad_services_ensure_schema();
    ad_media_ensure_schema();

    $baseFields = ['id', 'title', 'slug', 'summary', 'content', 'cover', 'icon', 'sort_order'];
    $localizedFields = [];
    if ($suffix !== '') {
        foreach ($baseFields as $field) {
            if ($field !== 'id' && $field !== 'cover' && $field !== 'icon' && $field !== 'sort_order') {
                $localizedFields[] = $field . $suffix;
            }
        }
    }
    $selectFields = implode(', ', array_merge($baseFields, $localizedFields));

    if ($slug !== '') {
        $stmt = ad_db()->prepare(
            "SELECT $selectFields FROM services WHERE is_active = 1 AND slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            ad_json(['ok' => false, 'error' => 'Not found'], 404);
        }
        $row = applyLocalization($row, $suffix, $baseFields);
        ad_json(['ok' => true, 'data' => ad_media_with_cover_alt($row), 'lang' => $lang ?: 'en']);
    }

    if ($id > 0) {
        $stmt = ad_db()->prepare(
            "SELECT $selectFields FROM services WHERE is_active = 1 AND id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            ad_json(['ok' => false, 'error' => 'Not found'], 404);
        }
        $row = applyLocalization($row, $suffix, $baseFields);
        ad_json(['ok' => true, 'data' => ad_media_with_cover_alt($row), 'lang' => $lang ?: 'en']);
    }

    $stmt = ad_db()->query(
        "SELECT $selectFields FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    );
    $items = array_map(function($row) use ($suffix, $baseFields) {
        $row = applyLocalization($row, $suffix, $baseFields);
        return ad_media_with_cover_alt($row);
    }, $stmt->fetchAll());
    
    ad_json(['ok' => true, 'data' => $items, 'lang' => $lang ?: 'en']);
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'Server error'], 500);
}

function applyLocalization(array $row, string $suffix, array $baseFields): array
{
    if ($suffix === '') {
        return $row;
    }
    
    $localizedFields = ['title', 'summary', 'content'];
    foreach ($localizedFields as $field) {
        $localizedKey = $field . $suffix;
        if (isset($row[$localizedKey]) && $row[$localizedKey] !== '') {
            $row[$field] = $row[$localizedKey];
        }
    }
    
    return $row;
}