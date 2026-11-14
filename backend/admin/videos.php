<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/videos.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/translate.php';
ad_require_role(['admin']);

ad_videos_ensure_schema();
ad_media_ensure_schema();
$db = ad_db();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = $editId > 0 ? ad_videos_get($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ad_csrf_check($_POST['csrf'] ?? null)) {
        ad_flash_set('err', 'نشست نامعتبر است.');
        header('Location: videos.php');
        exit;
    }
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM site_videos WHERE id = ?')->execute([$id]);
            ad_flash_set('ok', 'ویدیو حذف شد.');
            header('Location: videos.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? ad_videos_get($id) : null;
        if ($id > 0 && !$existing) {
            throw new RuntimeException('ویدیو یافت نشد.');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $posterUrl = trim((string) ($_POST['poster_url'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        $subEn = trim((string) ($_POST['subtitle_srt_en'] ?? ''));
        $subRu = trim((string) ($_POST['subtitle_srt_ru'] ?? ''));
        $subHy = trim((string) ($_POST['subtitle_srt_hy'] ?? ''));

        if (!empty($_FILES['poster_file']['name'])) {
            $uploaded = ad_media_upload($_FILES['poster_file'], 'videos', $title);
            if ($uploaded !== '') {
                $posterUrl = '/' . ltrim($uploaded, '/');
            }
        }

        foreach (['en' => 'subtitle_file_en', 'ru' => 'subtitle_file_ru', 'hy' => 'subtitle_file_hy'] as $code => $fileKey) {
            if (empty($_FILES[$fileKey]['name'])) {
                continue;
            }
            $path = ad_upload_subtitle_srt($_FILES[$fileKey], 'subtitles');
            if ($path !== '') {
                if ($code === 'en') {
                    $subEn = $path;
                } elseif ($code === 'ru') {
                    $subRu = $path;
                } else {
                    $subHy = $path;
                }
            }
        }

        if (!empty($_FILES['video_file']['name'])) {
            throw new RuntimeException(
                'فایل ویدیو را با دکمه «بارگذاری ویدیو» آپلود کنید، سپس «افزودن» را بزنید (ارسال مستقیم با فرم از CDN مسدود می‌شود).'
            );
        }

        if ($videoUrl === '') {
            throw new RuntimeException('آدرس ویدیو الزامی است. ابتدا «بارگذاری ویدیو» را بزنید.');
        }

        $placement = trim((string) ($_POST['placement'] ?? ''));
        if (!ad_video_placement_valid($placement)) {
            throw new RuntimeException('انتخاب جایگاه نمایش ویدیو الزامی است (صفحه اصلی یا درباره ما).');
        }

        @set_time_limit(120);
        $english = ['title' => $title];
        $localized = ad_auto_translate_record($english, ['title'], $existing, ['title']);
        $titleRu = (string) ($localized['title_ru'] ?? '');
        $titleHy = (string) ($localized['title_hy'] ?? '');

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE site_videos SET title=?, title_ru=?, title_hy=?, video_url=?, poster_url=?,
                    subtitle_srt_en=?, subtitle_srt_ru=?, subtitle_srt_hy=?, placement=?, sort_order=?, is_active=? WHERE id=?'
            );
            $stmt->execute([
                $title,
                $titleRu,
                $titleHy,
                $videoUrl,
                $posterUrl,
                $subEn,
                $subRu,
                $subHy,
                $placement,
                $sort,
                $active,
                $id,
            ]);
            ad_flash_set('ok', 'ویدیو به‌روزرسانی شد.');
            header('Location: videos.php?edit=' . $id);
            exit;
        }

        $stmt = $db->prepare(
            'INSERT INTO site_videos (title, title_ru, title_hy, video_url, poster_url, subtitle_srt_en, subtitle_srt_ru, subtitle_srt_hy, placement, sort_order, is_active)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $title,
            $titleRu,
            $titleHy,
            $videoUrl,
            $posterUrl,
            $subEn,
            $subRu,
            $subHy,
            $placement,
            $sort,
            $active,
        ]);
        ad_flash_set('ok', 'ویدیو افزوده شد.');
    } catch (Throwable $e) {
        ad_flash_set('err', $e->getMessage());
    }
    header('Location: videos.php' . ($editId > 0 ? '?edit=' . $editId : ''));
    exit;
}

$rows = ad_videos_list_all();
$placementOptions = ad_video_placement_options();
$currentPlacement = (string) ($edit['placement'] ?? '');
$pageTitle = 'ویدیوها';
require __DIR__ . '/_layout_top.php';
?>
<style>
.ana-upload-progress{position:relative;height:22px;background:#f0f0f1;border-radius:3px;overflow:hidden;margin:8px 0;max-width:480px}
.ana-upload-progress__bar{height:100%;background:#2271b1;transition:width .2s}
.ana-upload-progress__label{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px}
.subtitle-grid{display:grid;grid-template-columns:1fr;gap:16px;max-width:720px}
@media (min-width:782px){.subtitle-grid{grid-template-columns:repeat(3,1fr)}}
.subtitle-block{border:1px solid #c3c4c7;border-radius:4px;padding:12px;background:#f6f7f7}
.subtitle-block h3{margin:0 0 8px;font-size:13px}
</style>
<h1>ویدیوهای صفحه اصلی</h1>
<p class="muted">دو جایگاه نمایش دارید: <strong>صفحه اصلی</strong> (کاروسل، بلافاصله زیر Our Advantages) و <strong>درباره ما</strong> (بلافاصله زیر سکشن اصلی). انتخاب جایگاه برای هر ویدیو اجباری است. زیرنویس SRT برای en / ru / hy (در سایت به VTT تبدیل می‌شود).</p>
<p class="muted"><strong>مهم:</strong> فایل ویدیو را فقط با «بارگذاری ویدیو» بفرستید؛ دکمه «افزودن» فقط اطلاعات را ذخیره می‌کند.</p>

<div class="card">
  <h2><?= $edit ? 'ویرایش ویدیو' : 'افزودن ویدیو' ?></h2>
  <form method="post" enctype="multipart/form-data" id="ana-video-form">
    <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>" />

    <p>
      <label>جایگاه نمایش <span style="color:#b32d2e">*</span><br>
        <select name="placement" required style="max-width:480px;width:100%">
          <option value="" disabled <?= $currentPlacement === '' ? 'selected' : '' ?>>انتخاب کنید…</option>
          <?php foreach ($placementOptions as $value => $label): ?>
            <option value="<?= ad_e($value) ?>" <?= $currentPlacement === $value ? 'selected' : '' ?>><?= ad_e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </p>
    <p>
      <label>عنوان (انگلیسی — پایهٔ ترجمه)<br>
        <input type="text" name="title" class="regular-text" style="width:100%;max-width:480px"
               value="<?= ad_e((string) ($edit['title'] ?? '')) ?>" placeholder="e.g. Customer feedback" />
      </label>
    </p>
    <?php if ($edit): ?>
      <p class="muted" style="font-size:12px">
        RU: <?= ad_e((string) ($edit['title_ru'] ?? '—')) ?> —
        HY: <?= ad_e((string) ($edit['title_hy'] ?? '—')) ?>
      </p>
    <?php endif; ?>
    <p>
      <label>آدرس ویدیو (mp4 / webm / mov)<br>
        <input type="text" name="video_url" id="ana-video-url" class="regular-text" style="width:100%;max-width:640px"
               value="<?= ad_e((string) ($edit['video_url'] ?? '')) ?>" dir="ltr"
               placeholder="/assets/images/uploads/videos/…" />
      </label>
    </p>
    <p>
      <label>آپلود فایل ویدیو<br>
        <input type="file" name="video_file" id="ana-video-file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" />
      </label>
    </p>
    <p>
      <button type="button" class="btn" id="ana-video-upload-btn">بارگذاری ویدیو</button>
      <span id="ana-video-upload-status" class="muted" role="status" style="margin-inline-start:10px"></span>
    </p>
    <div id="ana-video-upload-progress" class="ana-upload-progress" hidden>
      <div class="ana-upload-progress__bar" id="ana-video-upload-bar" style="width:0%"></div>
      <span class="ana-upload-progress__label" id="ana-video-upload-pct">0%</span>
    </div>
    <p>
      <label>آدرس پوستر (اختیاری)<br>
        <input type="url" name="poster_url" class="regular-text" style="width:100%;max-width:640px"
               value="<?= ad_e((string) ($edit['poster_url'] ?? '')) ?>" dir="ltr" />
      </label>
    </p>
    <p>
      <label>یا آپلود پوستر<br>
        <input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp,image/gif" />
      </label>
    </p>

    <h3 style="margin-top:20px">زیرنویس (SRT)</h3>
    <div class="subtitle-grid">
      <?php
      foreach (
          [
              'en' => ['label' => 'English (SRT)', 'field' => 'subtitle_srt_en', 'file' => 'subtitle_file_en'],
              'ru' => ['label' => 'Русский (SRT)', 'field' => 'subtitle_srt_ru', 'file' => 'subtitle_file_ru'],
              'hy' => ['label' => 'Հայերեն (SRT)', 'field' => 'subtitle_srt_hy', 'file' => 'subtitle_file_hy'],
          ] as $code => $meta
      ):
          $stored = trim((string) ($edit[$meta['field']] ?? ''));
          ?>
      <div class="subtitle-block">
        <h3><?= ad_e($meta['label']) ?></h3>
        <p>
          <label>مسیر ذخیره‌شده (اختیاری)<br>
            <input type="text" name="<?= ad_e($meta['field']) ?>" class="regular-text" style="width:100%" dir="ltr"
                   value="<?= ad_e($stored) ?>" placeholder="/assets/…/file.srt" />
          </label>
        </p>
        <?php if ($stored !== ''): ?>
          <p class="muted" style="font-size:11px;word-break:break-all"><?= ad_e($stored) ?></p>
        <?php endif; ?>
        <p>
          <label>آپلود فایل .srt<br>
            <input type="file" name="<?= ad_e($meta['file']) ?>" accept=".srt,application/x-subrip,text/plain" />
          </label>
        </p>
      </div>
      <?php endforeach; ?>
    </div>

    <p style="margin-top:16px">
      <label>ترتیب نمایش
        <input type="number" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>" style="width:100px" />
      </label>
    </p>
    <p>
      <label>
        <input type="checkbox" name="is_active" value="1" <?= !isset($edit['is_active']) || (int) ($edit['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
        فعال در سایت
      </label>
    </p>
    <p>
      <button type="submit" class="btn"><?= $edit ? 'ذخیره تغییرات' : 'افزودن' ?></button>
      <?php if ($edit): ?>
        <a class="btn btn-secondary" href="videos.php">انصراف</a>
      <?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <h2>ویدیوهای موجود</h2>
  <?php if (!$rows): ?>
    <p class="muted">هنوز ویدیویی ثبت نشده است.</p>
  <?php else: ?>
    <table class="widefat striped" style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:right;padding:8px">پوستر</th>
          <th style="text-align:right;padding:8px">عنوان</th>
          <th style="text-align:right;padding:8px">جایگاه</th>
          <th style="text-align:right;padding:8px">زیرنویس</th>
          <th style="text-align:right;padding:8px">ترتیب</th>
          <th style="text-align:right;padding:8px">وضعیت</th>
          <th style="text-align:right;padding:8px">عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php
          $subFlags = [];
          foreach (['en', 'ru', 'hy'] as $c) {
              if (trim((string) ($row['subtitle_srt_' . $c] ?? '')) !== '') {
                  $subFlags[] = strtoupper($c);
              }
          }
          ?>
          <tr>
            <td style="padding:8px;width:90px">
              <?php if (!empty($row['poster_url'])): ?>
                <img src="<?= ad_e(ad_public_upload_url((string) $row['poster_url'])) ?>" alt="" style="width:72px;height:40px;object-fit:cover;border-radius:4px" />
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td style="padding:8px">
              <strong><?= ad_e((string) ($row['title'] ?: 'بدون عنوان')) ?></strong><br>
              <span class="muted" dir="ltr" style="font-size:12px;word-break:break-all"><?= ad_e((string) $row['video_url']) ?></span>
            </td>
            <td style="padding:8px;white-space:normal;max-width:140px">
              <?php
                $pl = (string) ($row['placement'] ?? 'home');
              echo ad_e($placementOptions[$pl] ?? $pl);
                ?>
            </td>
            <td style="padding:8px"><?= $subFlags ? ad_e(implode(', ', $subFlags)) : '<span class="muted">—</span>' ?></td>
            <td style="padding:8px"><?= (int) $row['sort_order'] ?></td>
            <td style="padding:8px"><?= (int) $row['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a class="btn btn-secondary" href="videos.php?edit=<?= (int) $row['id'] ?>">ویرایش</a>
              <form method="post" style="display:inline" onsubmit="return confirm('حذف این ویدیو؟');">
                <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                <button type="submit" class="btn btn-secondary">حذف</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<script src="videos-upload.js?ver=20261114c" defer></script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>