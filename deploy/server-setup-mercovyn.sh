#!/bin/bash
# One-time Mercovyn setup on origin (Ubuntu). Does NOT modify shomaredar nginx site.
set -euo pipefail

SITE_ROOT="/opt/mercovyn"
NGINX_SITE="/etc/nginx/sites-available/mercovyn"

if [[ ! -d "$SITE_ROOT" ]]; then
    echo "Missing $SITE_ROOT"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y php8.5-fpm php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl mariadb-server

systemctl enable --now php8.5-fpm mariadb

DB_PASS="$(openssl rand -hex 20)"

mysql -e "CREATE DATABASE IF NOT EXISTS mercovyn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "DROP USER IF EXISTS 'mercovyn'@'localhost';"
mysql -e "CREATE USER 'mercovyn'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON mercovyn.* TO 'mercovyn'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

mysql mercovyn < "${SITE_ROOT}/backend/install.sql"
if [[ -f "${SITE_ROOT}/backend/install-seed.sql" ]]; then
    mysql mercovyn < "${SITE_ROOT}/backend/install-seed.sql" || true
fi

cat > "${SITE_ROOT}/backend/config.php" <<PHP
<?php
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'mercovyn',
    'db_user' => 'mercovyn',
    'db_pass' => '${DB_PASS}',
    'db_charset' => 'utf8mb4',
    'upload_url_prefix' => 'assets/images/uploads/',
    'upload_dir' => dirname(__DIR__) . '/assets/images/uploads/',
    'site_name' => 'Mercovyn LLC',
    'site_url' => 'https://mercovyn.com',
    'timezone' => 'Asia/Yerevan',
];
PHP

chmod 640 "${SITE_ROOT}/backend/config.php"
chown root:www-data "${SITE_ROOT}/backend/config.php"

if [[ -f "${SITE_ROOT}/backend/migrate_multilang.php" ]]; then
    php "${SITE_ROOT}/backend/migrate_multilang.php" || true
fi

mkdir -p "${SITE_ROOT}/assets/images/uploads"
chown -R www-data:www-data "${SITE_ROOT}/assets/images/uploads"
if [[ -d "${SITE_ROOT}/backend/uploads" ]]; then
    chown -R www-data:www-data "${SITE_ROOT}/backend/uploads"
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cp "${SCRIPT_DIR}/nginx-mercovyn.conf" "$NGINX_SITE"
ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/mercovyn

nginx -t
systemctl reload nginx

CREDS="/root/mercovyn-db-credentials.txt"
{
    echo "database=mercovyn"
    echo "user=mercovyn"
    echo "password=${DB_PASS}"
    echo "created=$(date -Is)"
} > "$CREDS"
chmod 600 "$CREDS"

echo "Mercovyn ready. DB credentials: $CREDS"
curl -sS -o /dev/null -w "HTTP %{http_code}\n" -H "Host: mercovyn.com" http://127.0.0.1/ || true
