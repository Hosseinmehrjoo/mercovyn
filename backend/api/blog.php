<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/blog.php';

ad_cors_public();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$lang = trim((string) ($_GET['lang'] ?? ''));

// Validate language
$supportedLangs = ['en', 'ru', 'hy'];
if ($lang !== '' && !in_array($lang, $supportedLangs, true)) {
    $lang = '';
}

// Determine field suffix
$suffix = $lang === 'en' || $lang === '' ? '' : '_' . $lang;

try {
    ad_media_ensure_schema();
    ad_blog_ensure_schema();

    $categories = ad_blog_categories_list();
    $publicCats = array_map(static function (array $c) use ($suffix): array {
        $nameField = 'name' . $suffix;
        $slugField = 'slug' . $suffix;
        return [
            'id' => (int) $c['id'],
            'name' => (string) ($c[$nameField] ?? $c['name'] ?? ''),
            'slug' => (string) ($c[$slugField] ?? $c['slug'] ?? ''),
            'post_count' => (int) ($c['post_count'] ?? 0),
        ];
    }, $categories);

    // Build field list with localized fields
    $baseFields = ['id', 'title', 'slug', 'meta_title', 'meta_description', 'meta_keywords', 'excerpt', 'content', 'cover', 'published_at'];
    $localizedFields = [];
    if ($suffix !== '') {
        foreach ($baseFields as $field) {
            if ($field !== 'id' && $field !== 'published_at' && $field !== 'cover') {
                $localizedFields[] = $field . $suffix;
            }
        }
    }
    $selectFields = implode(', ', array_merge($baseFields, $localizedFields));

    if ($slug !== '') {
        $stmt = ad_db()->prepare(
            "SELECT $selectFields
             FROM blog_posts
             WHERE status = 'published' AND slug = ?
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            ad_json(['ok' => false, 'error' => 'Not found'], 404);
        }
        // Apply localization to row
        $row = applyLocalization($row, $suffix);
        ad_json([
            'ok' => true,
            'data' => ad_blog_normalize_post($row),
            'categories' => $publicCats,
            'lang' => $lang ?: 'en',
        ]);
    }

    $sql = "SELECT DISTINCT p.$selectFields
            FROM blog_posts p";
    $params = [];
    if ($category !== '') {
        $sql .= ' INNER JOIN blog_post_categories pc ON pc.post_id = p.id
                  INNER JOIN blog_categories c ON c.id = pc.category_id
                    AND (c.slug = ? OR c.name = ?)';
        $params[] = $category;
        $params[] = $category;
    }
    $sql .= ' WHERE p.status = \'published\'
              ORDER BY p.published_at DESC, p.id DESC
              LIMIT 50';

    if ($params) {
        $stmt = ad_db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } else {
        $rows = ad_db()->query($sql)->fetchAll();
    }

    $items = array_map(function($row) use ($suffix) {
        $row = applyLocalization($row, $suffix);
        return ad_blog_normalize_post($row);
    }, $rows);
    
    ad_json([
        'ok' => true,
        'data' => $items,
        'categories' => $publicCats,
        'lang' => $lang ?: 'en',
    ]);
} catch (Throwable $e) {
    ad_json(['ok' => false, 'error' => 'Server error'], 500);
}

/**
 * Apply localization to row data
 */
function applyLocalization(array $row, string $suffix): array
{
    if ($suffix === '') {
        return $row;
    }
    
    $localizedFields = ['title', 'meta_title', 'meta_description', 'meta_keywords', 'excerpt', 'content'];
    foreach ($localizedFields as $field) {
        $localizedKey = $field . $suffix;
        if (isset($row[$localizedKey]) && $row[$localizedKey] !== '') {
            $row[$field] = $row[$localizedKey];
        }
    }
    
    return $row;
}