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

    'site_name' => 'Mercovyn LLC',
    // آدرس عمومی سایت بدون اسلش پایانی (برای sitemap و robots)
    // مثال: https://ana-developer.ir
    'site_url' => 'https://www.mercovyn.com',
    'timezone' => 'Asia/Yerevan',

    // ترجمه خودکار محتوای CMS (انگلیسی → ru / hy / ar) هنگام ذخیره در پنل
    'auto_translate' => true,
    // libretranslate | mymemory | auto (اول LibreTranslate محلی، سپس MyMemory)
    'translate_provider' => 'auto',
    'translate_source_lang' => 'en',
    // LibreTranslate روی سرور: docker run -p 5000:5000 libretranslate/libretranslate
    'libretranslate_url' => 'http://127.0.0.1:5000',
    'libretranslate_api_key' => '',
    // اختیاری؛ برای سهمیه بالاتر MyMemory
    'mymemory_email' => '',

    // محدودیت ورود (پس از چند تلاش ناموفق)
    'login_max_attempts' => 8,
    'login_lockout_seconds' => 900,
];
