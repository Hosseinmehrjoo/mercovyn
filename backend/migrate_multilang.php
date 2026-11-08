<?php
/**
 * Migration script to add multilingual columns to existing tables
 * Run this once after updating the codebase
 */
require_once __DIR__ . '/lib/db.php';

try {
    $db = ad_db();
    echo "Connected to database.\n";

    // Check and add columns to services table
    echo "Checking services table...\n";
    $cols = $db->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    $serviceColumns = [
        'title_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'title_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'summary_ru' => "TEXT NULL",
        'summary_hy' => "TEXT NULL",
        'content_ru' => "MEDIUMTEXT NULL",
        'content_hy' => "MEDIUMTEXT NULL",
        'slug_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
    ];
    foreach ($serviceColumns as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE services ADD COLUMN `$col` $def AFTER `slug`");
            echo "  Added $col\n";
        } else {
            echo "  $col already exists\n";
        }
    }

    // Check and add columns to team_members table
    echo "Checking team_members table...\n";
    $cols = $db->query('SHOW COLUMNS FROM team_members')->fetchAll(PDO::FETCH_COLUMN);
    $teamColumns = [
        'name_ru' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'name_hy' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'role_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'role_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'bio_ru' => "TEXT NULL",
        'bio_hy' => "TEXT NULL",
        'slug_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'highlight_1_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'highlight_1_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'highlight_2_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'highlight_2_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'highlight_3_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'highlight_3_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_title_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_title_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_description_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_description_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
    ];
    foreach ($teamColumns as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE team_members ADD COLUMN `$col` $def");
            echo "  Added $col\n";
        } else {
            echo "  $col already exists\n";
        }
    }

    // Check and add columns to portfolio_items table
    echo "Checking portfolio_items table...\n";
    $cols = $db->query('SHOW COLUMNS FROM portfolio_items')->fetchAll(PDO::FETCH_COLUMN);
    $portfolioColumns = [
        'title_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'title_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'description_ru' => "TEXT NULL",
        'description_hy' => "TEXT NULL",
        'category_ru' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'category_hy' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'tags_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'tags_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'client_name_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'client_name_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'executor_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'executor_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'slug_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'meta_title_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_title_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_description_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_description_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
    ];
    foreach ($portfolioColumns as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE portfolio_items ADD COLUMN `$col` $def");
            echo "  Added $col\n";
        } else {
            echo "  $col already exists\n";
        }
    }

    // Check and add columns to blog_posts table (if not already there)
    echo "Checking blog_posts table...\n";
    $cols = $db->query('SHOW COLUMNS FROM blog_posts')->fetchAll(PDO::FETCH_COLUMN);
    $blogColumns = [
        'title_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'title_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'slug_ru' => "VARCHAR(220) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(220) NOT NULL DEFAULT ''",
        'meta_title_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_title_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'meta_description_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_description_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'meta_keywords_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'excerpt_ru' => "TEXT NULL",
        'excerpt_hy' => "TEXT NULL",
        'content_ru' => "MEDIUMTEXT NULL",
        'content_hy' => "MEDIUMTEXT NULL",
    ];
    foreach ($blogColumns as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE blog_posts ADD COLUMN `$col` $def");
            echo "  Added $col\n";
        } else {
            echo "  $col already exists\n";
        }
    }

    // Check and add columns to blog_categories table
    echo "Checking blog_categories table...\n";
    $cols = $db->query('SHOW COLUMNS FROM blog_categories')->fetchAll(PDO::FETCH_COLUMN);
    $catColumns = [
        'name_ru' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'name_hy' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'slug_ru' => "VARCHAR(140) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(140) NOT NULL DEFAULT ''",
        'description_ru' => "TEXT NULL",
        'description_hy' => "TEXT NULL",
    ];
    foreach ($catColumns as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE blog_categories ADD COLUMN `$col` $def");
            echo "  Added $col\n";
        } else {
            echo "  $col already exists\n";
        }
    }

    echo "\nMigration completed successfully!\n";

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}