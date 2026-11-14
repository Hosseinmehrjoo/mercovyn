<?php

/**
 * Simple file-based login throttling (per IP + username).
 */
function ad_login_throttle_key(string $login): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $login = mb_strtolower(trim($login), 'UTF-8');
    return hash('sha256', $ip . '|' . $login);
}

function ad_login_throttle_path(string $key): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . '/mercovyn_login_' . $key . '.json';
}

/**
 * @return array{failures:int,until:int}
 */
function ad_login_throttle_read(string $key): array
{
    $path = ad_login_throttle_path($key);
    if (!is_file($path)) {
        return ['failures' => 0, 'until' => 0];
    }
    $raw = @file_get_contents($path);
    if ($raw === false) {
        return ['failures' => 0, 'until' => 0];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['failures' => 0, 'until' => 0];
    }
    return [
        'failures' => (int) ($data['failures'] ?? 0),
        'until' => (int) ($data['until'] ?? 0),
    ];
}

function ad_login_throttle_write(string $key, array $data): void
{
    $path = ad_login_throttle_path($key);
    @file_put_contents($path, json_encode($data), LOCK_EX);
}

function ad_login_max_attempts(): int
{
    $c = ad_config();
    $n = (int) ($c['login_max_attempts'] ?? 8);
    return max(3, min(20, $n));
}

function ad_login_lockout_seconds(): int
{
    $c = ad_config();
    $n = (int) ($c['login_lockout_seconds'] ?? 900);
    return max(60, min(3600, $n));
}

function ad_login_is_locked(string $login): bool
{
    $key = ad_login_throttle_key($login);
    $state = ad_login_throttle_read($key);
  if ($state['until'] > time()) {
        return true;
    }
    if ($state['until'] > 0 && $state['until'] <= time()) {
        ad_login_throttle_write($key, ['failures' => 0, 'until' => 0]);
    }
    return false;
}

function ad_login_lockout_remaining(string $login): int
{
    $state = ad_login_throttle_read(ad_login_throttle_key($login));
    $left = $state['until'] - time();
    return $left > 0 ? $left : 0;
}

function ad_login_record_failure(string $login): void
{
    $key = ad_login_throttle_key($login);
    $state = ad_login_throttle_read($key);
    $state['failures'] = (int) $state['failures'] + 1;
    if ($state['failures'] >= ad_login_max_attempts()) {
        $state['until'] = time() + ad_login_lockout_seconds();
        $state['failures'] = 0;
    }
    ad_login_throttle_write($key, $state);
}

function ad_login_clear_failures(string $login): void
{
    ad_login_throttle_write(ad_login_throttle_key($login), ['failures' => 0, 'until' => 0]);
}