<?php

function ad_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $path = dirname(__DIR__) . '/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('فایل config.php یافت نشد. ابتدا نصب را انجام دهید.');
    }
    $config = require $path;
    if (!empty($config['timezone'])) {
        date_default_timezone_set($config['timezone']);
    }
    // همیشه آپلودها در assets/images/uploads ذخیره شوند
    $siteRoot = dirname(__DIR__, 2);
    $config['upload_url_prefix'] = 'assets/images/uploads/';
    $config['upload_dir'] = rtrim(str_replace('\\', '/', $siteRoot), '/') . '/assets/images/uploads/';
    return $config;
}

function ad_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = ad_config();
    
    $port = (int) ($c['db_port'] ?? 3306);
    if ($port < 1) {
        $port = 3306;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['db_host'],
        $port,
        $c['db_name'],
        $c['db_charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
