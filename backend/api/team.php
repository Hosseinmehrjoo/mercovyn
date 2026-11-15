<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/team.php';

ad_cors_public();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ad_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = trim((string) ($_GET['slug'] ?? ''));
$lang = trim((string) ($_GET['lang'] ?? ''));

$supportedLangs = ad_supported_languages();
if ($lang !== '' && !in_array($lang, $supportedLangs, true)) {
    $lang = '';
}

$suffix = $lang === 'en' || $lang === '' ? '' : '_' . $lang;

try {
    ad_team_ensure_schema();

    $baseFields = 'id, name, name_en, role, role_en, bio, rating, highlight_1, highlight_2, highlight_3,
                   joined_at, social_whatsapp, social_telegram, social_instagram, social_linkedin, social_web,
                   photo, sort_order, slug, meta_title, meta_description, meta_keywords';
    $localizedFields = [];
    if ($suffix !== '') {
        $localizedFields = ['name' . $suffix, 'role' . $suffix, 'bio' . $suffix];
    }
    $selectFields = implode(', ', array_merge(explode(', ', $baseFields), $localizedFields));

    if ($id > 0 || $slug !== '') {
        if ($id > 0) {
            $stmt = ad_db()->prepare(
                "SELECT {$selectFields}
                 FROM team_members
                 WHERE is_active = 1 AND id = ?
                 LIMIT 1"
            );
            $stmt->execute([$id]);
        } else {
            $stmt = ad_db()->prepare(
                "SELECT {$selectFields}
                 FROM team_members
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
        $member = ad_team_normalize_member($row);
        $memberId = (int) $member['id'];

        $worksStmt = ad_db()->query(
            'SELECT id, member_id, member_summary, team_roles, title, slug, meta_title, meta_description, meta_keywords,
                    description, category, cover, tags, price, show_price, url, sort_order
             FROM portfolio_items
             WHERE is_active = 1
             ORDER BY sort_order ASC, id DESC'
        );
        $works = [];
        foreach ($worksStmt->fetchAll() as $workRow) {
            $entry = ad_portfolio_team_entry_for_member($workRow, $memberId);
            if ($entry === null) {
                continue;
            }
            $normalized = ad_portfolio_normalize_item($workRow);
            $normalized['member_role'] = $entry['role'];
            $normalized['member_summary'] = $entry['summary'] !== ''
                ? $entry['summary']
                : (string) ($normalized['member_summary'] ?? '');
            $works[] = $normalized;
        }
        $member['works'] = $works;

        ad_json(['ok' => true, 'data' => $member, 'lang' => $lang ?: 'en']);
    }

    $stmt = ad_db()->query(
        "SELECT {$selectFields}
         FROM team_members
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC"
    );
    $items = array_map(function($row) use ($suffix) {
        return ad_team_normalize_member(applyLocalization($row, $suffix));
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
    
    $localizedFields = ['name', 'role', 'bio'];
    foreach ($localizedFields as $field) {
        $localizedKey = $field . $suffix;
        if (isset($row[$localizedKey]) && $row[$localizedKey] !== '') {
            $row[$field] = $row[$localizedKey];
        }
    }
    
    return $row;
}