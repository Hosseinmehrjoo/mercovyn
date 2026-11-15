<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/portfolio.php';

ad_cors_public();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = trim((string) ($_GET['slug'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$lang = trim((string) ($_GET['lang'] ?? ''));

$supportedLangs = ad_supported_languages();
if ($lang !== '' && !in_array($lang, $supportedLangs, true)) {
    $lang = '';
}

$suffix = $lang === 'en' || $lang === '' ? '' : '_' . $lang;
$allowed = ['websites', 'apps'];

try {
    ad_portfolio_ensure_schema();

    $baseFields = 'id, member_id, member_summary, team_roles, title, slug, meta_title, meta_description, meta_keywords,
                   description, category, cover, tags, technologies, provided_services, price, show_price, url,
                   client_name, delivery_date, executor, uploaded_at, screenshots, sort_order, created_at';
    $localizedFields = [];
    if ($suffix !== '') {
        $localizedFields = ['title' . $suffix, 'slug' . $suffix, 'meta_title' . $suffix, 'meta_description' . $suffix, 
                           'meta_keywords' . $suffix, 'description' . $suffix, 'member_summary' . $suffix, 
                           'team_roles' . $suffix];
    }
    $selectFields = implode(', ', array_merge(explode(', ', $baseFields), $localizedFields));

    if ($id > 0 || $slug !== '') {
        if ($id > 0) {
            $stmt = ad_db()->prepare(
                "SELECT {$selectFields}
                 FROM portfolio_items
                 WHERE is_active = 1 AND id = ?
                 LIMIT 1"
            );
            $stmt->execute([$id]);
        } else {
            $stmt = ad_db()->prepare(
                "SELECT {$selectFields}
                 FROM portfolio_items
                 WHERE is_active = 1 AND slug = ?
                 LIMIT 1"
            );
            $stmt->execute([$slug]);
        }
        $row = $stmt->fetch();
        if (!$row) {
            ad_json(['ok' => false, 'error' => 'Not found'], 404);
        }
        $row = applyLocalization($row, $suffix);
        ad_json(['ok' => true, 'data' => ad_portfolio_normalize_item($row), 'lang' => $lang ?: 'en']);
    }

    $select = "SELECT {$selectFields}
               FROM portfolio_items
               WHERE is_active = 1";
    if ($category !== '' && in_array($category, $allowed, true)) {
        $stmt = ad_db()->prepare($select . ' AND category = ? ORDER BY sort_order ASC, id DESC');
        $stmt->execute([$category]);
    } else {
        $stmt = ad_db()->query($select . ' ORDER BY sort_order ASC, id DESC');
    }
    $items = array_map(function($row) use ($suffix) {
        return ad_portfolio_normalize_item(applyLocalization($row, $suffix));
    }, $stmt->fetchAll());
    
    ad_json(['ok' => true, 'data' => $items, 'lang' => $lang ?: 'en']);
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'Server error'], 500);
}

function applyLocalization(array $row, string $suffix): array
{
    if ($suffix === '') {
        return $row;
    }
    
    $localizedFields = ['title', 'slug', 'meta_title', 'meta_description', 'meta_keywords', 
                       'description', 'member_summary', 'team_roles'];
    foreach ($localizedFields as $field) {
        $localizedKey = $field . $suffix;
        if (isset($row[$localizedKey]) && $row[$localizedKey] !== '') {
            $row[$field] = $row[$localizedKey];
        }
    }
    
    return $row;
}