<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/cookies.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/comments.php';
ad_require_capability('dashboard');

$db = ad_db();
ad_cookies_ensure_schema();
ad_media_ensure_schema();
ad_comments_ensure_schema();
$counts = [
    'team' => (int) $db->query('SELECT COUNT(*) FROM team_members')->fetchColumn(),
    'portfolio' => (int) $db->query('SELECT COUNT(*) FROM portfolio_items')->fetchColumn(),
    'blog' => (int) $db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn(),
    'blog_pub' => (int) $db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn(),
    'messages' => (int) $db->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'unread' => (int) $db->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
    'comments_pending' => ad_comments_count_by_status('pending'),
    'comments' => ad_comments_count_by_status(''),
    'cookies' => (int) $db->query('SELECT COUNT(*) FROM cookie_consents')->fetchColumn(),
    'media' => ad_media_count(),
];

$recentMessages = $db->query(
    'SELECT id, name, subject, created_at, is_read
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'پیشخوان';
require __DIR__ . '/_layout_top.php';
?>
<h1>پیشخوان</h1>

<div class="welcome-panel">
  <h2>به پنل آنا دولوپر خوش آمدید</h2>
  <p>از اینجا می‌توانید محتوا، نمونه‌کارها، پیام‌ها و تنظیمات کوکی را مدیریت کنید — ظاهر شبیه پیشخوان وردپرس، مخصوص سایت شما.</p>
  <div class="actions">
    <a class="btn" href="portfolio.php">افزودن نمونه‌کار</a>
    <a class="btn btn-secondary" href="blog.php">نوشتن مطلب</a>
    <a class="btn btn-secondary" href="../../portfolio.html" target="_blank" rel="noopener">مشاهده آرشیو</a>
  </div>
</div>

<div class="dashboard-widgets">
  <div class="postbox">
    <div class="postbox-header"><h2>در یک نگاه</h2></div>
    <div class="inside">
      <ul class="at-a-glance-list">
        <li>
          <a href="media.php">
            <span class="count"><?= $counts['media'] ?></span>
            <span>رسانه</span>
          </a>
        </li>
        <li>
          <a href="portfolio.php">
            <span class="count"><?= $counts['portfolio'] ?></span>
            <span>نمونه‌کار</span>
          </a>
        </li>
        <li>
          <a href="blog.php">
            <span class="count"><?= $counts['blog'] ?></span>
            <span>نوشته (<?= $counts['blog_pub'] ?> منتشر)</span>
          </a>
        </li>
        <li>
          <a href="team.php">
            <span class="count"><?= $counts['team'] ?></span>
            <span>عضو تیم</span>
          </a>
        </li>
        <li>
          <a href="messages.php">
            <span class="count"><?= $counts['messages'] ?></span>
            <span>پیام<?= $counts['unread'] ? ' · ' . $counts['unread'] . ' جدید' : '' ?></span>
          </a>
        </li>
        <li>
          <a href="comments.php<?= $counts['comments_pending'] ? '?status=pending' : '' ?>">
            <span class="count"><?= $counts['comments'] ?></span>
            <span>نظر<?= $counts['comments_pending'] ? ' · ' . $counts['comments_pending'] . ' در انتظار' : '' ?></span>
          </a>
        </li>
        <li>
          <a href="cookies.php">
            <span class="count"><?= $counts['cookies'] ?></span>
            <span>رضایت کوکی</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="postbox">
    <div class="postbox-header"><h2>دسترسی سریع</h2></div>
    <div class="inside">
      <div class="quick-links-grid">
        <a href="team.php">مدیریت تیم</a>
        <a href="portfolio.php">نمونه‌کارها</a>
        <a href="blog.php">وبلاگ</a>
        <a href="comments.php">نظرات</a>
        <a href="messages.php">پیام‌ها</a>
        <a href="cookies.php">کوکی‌ها</a>
        <a href="../../index.html" target="_blank" rel="noopener">سایت</a>
      </div>
    </div>
  </div>

  <div class="postbox">
    <div class="postbox-header"><h2>فعالیت — پیام‌های اخیر</h2></div>
    <div class="inside">
      <?php if (!$recentMessages): ?>
        <p class="muted">هنوز پیامی ثبت نشده است.</p>
      <?php else: ?>
        <ul class="activity-list">
          <?php foreach ($recentMessages as $msg): ?>
            <li>
              <span>
                <?php if (!(int) $msg['is_read']): ?><span class="badge badge-unread">جدید</span> <?php endif; ?>
                <a href="messages.php?id=<?= (int) $msg['id'] ?>"><?= ad_e($msg['name']) ?></a>
                — <?= ad_e($msg['subject'] ?: 'بدون موضوع') ?>
              </span>
              <span class="muted"><?= ad_e($msg['created_at']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="postbox">
    <div class="postbox-header"><h2>وضعیت سایت</h2></div>
    <div class="inside">
      <p><strong>عنوان سایت:</strong> آنا دولوپر</p>
      <p class="muted">نسخه پنل: ساده · PHP + MySQL</p>
      <p class="muted">برای افزودن محتوا از منوی سمت راست استفاده کنید.</p>
      <div class="actions">
        <a class="btn btn-secondary" href="cookies.php">تنظیمات کوکی</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
