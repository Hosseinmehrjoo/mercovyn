<?php
/**
 * Write backend/config.php for local Docker (env MERCOVYN_DB_*).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root . '/backend/config.php';
$sample = $root . '/backend/config.sample.php';
if (!is_file($sample)) {
    fwrite(STDERR, "config.sample.php not found\n");
    exit(1);
}
/** @var array<string, mixed> $cfg */
$cfg = require $sample;
$cfg['db_host'] = getenv('MERCOVYN_DB_HOST') ?: '127.0.0.1';
$cfg['db_port'] = (int) (getenv('MERCOVYN_DB_PORT') ?: '3306');
$cfg['db_name'] = getenv('MERCOVYN_DB_NAME') ?: (string) ($cfg['db_name'] ?? 'mercovyn');
$cfg['db_user'] = getenv('MERCOVYN_DB_USER') ?: (string) ($cfg['db_user'] ?? 'mercovyn');
$cfg['db_pass'] = getenv('MERCOVYN_DB_PASS') ?: (string) ($cfg['db_pass'] ?? '');
$cfg['site_url'] = 'http://127.0.0.1:8765';
$cfg['geoip_enabled'] = false;

$export = "<?php\nreturn " . var_export($cfg, true) . ";\n";
file_put_contents($target, $export);
echo "Wrote backend/config.php for local dev.\n";