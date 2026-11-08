<?php
/**
 * کپی این فایل به config.php و مقادیر را پر کنید.
 * روی هاست: از cPanel دیتابیس بسازید و مشخصات را اینجا بگذارید.
 */
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'ana_developer',
    'db_user' => 'db_user',
    'db_pass' => 'db_password',
    'db_charset' => 'utf8mb4',

    // مسیر نسبی آپلودها نسبت به ریشه سایت (برای ذخیره در DB)
    // نکته: در اجرا همیشه به assets/images/uploads/ نرمال می‌شود
    'upload_url_prefix' => 'assets/images/uploads/',
    'upload_dir' => dirname(__DIR__) . '/assets/images/uploads/',

    'site_name' => 'آنا دولوپر',
    // آدرس عمومی سایت بدون اسلش پایانی (برای sitemap و robots)
    // مثال: https://ana-developer.ir
    'site_url' => '',
    'timezone' => 'Asia/Tehran',
];
