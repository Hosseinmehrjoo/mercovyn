#!/usr/bin/env bash
# Roundcube webmail at https://mail.mercovyn.com (Postfix/Dovecot unchanged).
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

debconf-set-selections <<'EOF'
roundcube-core roundcube/database-type select sqlite
roundcube-core roundcube/dbconfig_install boolean true
EOF

apt-get update -qq
apt-get install -y roundcube roundcube-sqlite3 roundcube-plugins

# Nginx vhost (does not touch shomaredar or mercovyn site root)
cat > /etc/nginx/sites-available/mail.mercovyn.com <<'NGINX'
# Roundcube webmail — mail.mercovyn.com only
limit_req_zone $binary_remote_addr zone=mail_rc_login:10m rate=15r/m;

server {
    listen 80;
    listen [::]:80;
    server_name mail.mercovyn.com;

    location ^~ /.well-known/acme-challenge/ {
        root /opt/mercovyn;
        allow all;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name mail.mercovyn.com;

    ssl_certificate     /etc/letsencrypt/live/mercovyn.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mercovyn.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    root /var/lib/roundcube/public_html;
    index index.php;

    client_max_body_size 25M;

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;
    add_header X-Frame-Options SAMEORIGIN always;

    location ~ /(config|temp|logs)/ {
        deny all;
    }
    location ~ ^/\. {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        limit_req zone=mail_rc_login burst=20 nodelay;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
        include fastcgi_params;
    }

    location ~ \.php$ {
        return 404;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/mail.mercovyn.com /etc/nginx/sites-enabled/mail.mercovyn.com

# Local IMAP/SMTP (Dovecot + Postfix submission on this host)
RC_LOCAL=/etc/roundcube/debian-db-roundcube.php
if [[ -f /etc/roundcube/config.inc.php ]]; then
  sed -i '/\$config\['\''imap_host'\''\] = \["localhost:143"\];/d' /etc/roundcube/config.inc.php
  grep -q "mercovyn: local mail stack" /etc/roundcube/config.inc.php || cat >> /etc/roundcube/config.inc.php <<'PHP'

// mercovyn: local mail stack (TLS cert is for mail.mercovyn.com)
$config['product_name'] = 'Mercovyn Mail';
$config['support_url'] = 'https://www.mercovyn.com/contact/';
$config['imap_host'] = 'ssl://mail.mercovyn.com:993';
$config['smtp_host'] = 'tls://mail.mercovyn.com:587';
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';
$config['smtp_auth_type'] = 'LOGIN';
$config['login_password_maxlen'] = 128;
$config['ip_check'] = false;
$config['imap_conn_options'] = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => 'mail.mercovyn.com']];
$config['smtp_conn_options'] = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => 'mail.mercovyn.com']];
PHP
fi

# PHP 8.5: avoid redeclare of array_first()
bash "$(dirname "$0")/roundcube-php85-patch.sh"

nginx -t
systemctl reload nginx
systemctl enable php8.5-fpm dovecot postfix

# Avoid duplicate server_name on :80 (ACME still on mail vhost)
if grep -q 'mail.mercovyn.com' /etc/nginx/sites-available/mercovyn; then
  sed -i 's/server_name mercovyn.com www.mercovyn.com mail.mercovyn.com;/server_name mercovyn.com www.mercovyn.com;/' /etc/nginx/sites-available/mercovyn
  nginx -t && systemctl reload nginx
fi

echo "OK: Roundcube vhost enabled at https://mail.mercovyn.com"