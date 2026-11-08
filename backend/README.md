# بک‌اند CMS آنا دولوپر

PHP خالص + MySQL — مناسب هاست اشتراکی و لوکال.

## نصب

از ریشه پروژه `/install.php` را باز کنید، یا مستقیم:

`/backend/install.php`

راهنمای کامل: [../INSTALL.md](../INSTALL.md)

## بعد از نصب

- پنل: `/backend/admin/`
- API: `/backend/api/`

## امنیت

- `config.php`، `installed.lock` و فایل‌های `.sql` با `.htaccess` از دسترسی وب محافظت می‌شوند.
- رمز ادمین با `password_hash` ذخیره می‌شود.
- فرم‌های ادمین CSRF دارند.
