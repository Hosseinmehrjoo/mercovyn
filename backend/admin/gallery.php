<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/gallery.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/translate.php';
ad_require_role(['admin']);

ad_gallery_ensure_schema();
ad_media_ensure_schema();
$db = ad_db();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = $editId > 0 ? ad_gallery_get($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ad_csrf_check($_POST['csrf'] ?? null)) {
        ad_flash_set('err', 'نشست نامعتبر است.');
        header('Location: gallery.php');
        exit;
    }
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM site_gallery_images WHERE id = ?')->execute([$id]);
            ad_flash_set('ok', 'تصویر حذف شد.');
            header('Location: gallery.php');
            exit;
        }

        if ($action === 'bulk') {
            $sortBase = (int) ($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            $files = $_FILES['gallery_files'] ?? null;
            if (!is_array($files) || empty($files['name']) || !is_array($files['name'])) {
                throw new RuntimeException('حداقل یک تصویر انتخاب کنید.');
            }
            $added = 0;
            $n = count($files['name']);
            for ($i = 0; $i < $n; $i++) {
                if (empty($files['name'][$i]) || (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0,
                ];
                $uploaded = ad_media_upload($single, 'gallery', pathinfo($files['name'][$i], PATHINFO_FILENAME));
                if ($uploaded === '') {
                    continue;
                }
                $imageUrl = '/' . ltrim($uploaded, '/');
                $title = pathinfo($files['name'][$i], PATHINFO_FILENAME);
                $english = ['title' => $title];
                $localized = ad_auto_translate_record($english, ['title'], null, ['title']);
                $stmt = $db->prepare(
                    'INSERT INTO site_gallery_images (title, title_ru, title_hy, title_ar, image_url, sort_order, is_active)
                     VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $title,
                    (string) ($localized['title_ru'] ?? ''),
                    (string) ($localized['title_hy'] ?? ''),
                    (string) ($localized['title_ar'] ?? ''),
                    $imageUrl,
                    $sortBase + $added,
                    $active,
                ]);
                $added++;
            }
            if ($added < 1) {
                throw new RuntimeException('هیچ تصویری آپلود نشد.');
            }
            ad_flash_set('ok', $added . ' تصویر به گالری اضافه شد.');
            header('Location: gallery.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? ad_gallery_get($id) : null;
        if ($id > 0 && !$existing) {
            throw new RuntimeException('تصویر یافت نشد.');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($_FILES['image_file']['name'])) {
            $uploaded = ad_media_upload($_FILES['image_file'], 'gallery', $title);
            if ($uploaded !== '') {
                $imageUrl = '/' . ltrim($uploaded, '/');
            }
        }

        if ($imageUrl === '') {
            throw new RuntimeException('تصویر الزامی است.');
        }

        $english = ['title' => $title];
        $localized = ad_auto_translate_record($english, ['title'], $existing, ['title']);
        $titleRu = (string) ($localized['title_ru'] ?? '');
        $titleHy = (string) ($localized['title_hy'] ?? '');
        $titleAr = (string) ($localized['title_ar'] ?? '');

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE site_gallery_images SET title=?, title_ru=?, title_hy=?, title_ar=?, image_url=?, sort_order=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$title, $titleRu, $titleHy, $titleAr, $imageUrl, $sort, $active, $id]);
            ad_flash_set('ok', 'تصویر به‌روزرسانی شد.');
            header('Location: gallery.php?edit=' . $id);
            exit;
        }

        $stmt = $db->prepare(
            'INSERT INTO site_gallery_images (title, title_ru, title_hy, title_ar, image_url, sort_order, is_active)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([$title, $titleRu, $titleHy, $titleAr, $imageUrl, $sort, $active]);
        ad_flash_set('ok', 'تصویر افزوده شد.');
    } catch (Throwable $e) {
        ad_flash_set('err', $e->getMessage());
    }
    header('Location: gallery.php' . ($editId > 0 ? '?edit=' . $editId : ''));
    exit;
}

$rows = ad_gallery_list_all();
$pageTitle = 'گالری تصاویر';
require __DIR__ . '/_layout_top.php';
?>
<h1>گالری تصاویر</h1>
<p class="muted">تصاویر این بخش در صفحه اصلی، <strong>زیر</strong> بخش «Ready to Place an Order?» نمایش داده می‌شوند. چند تصویر را یک‌جا آپلود کنید یا تک‌تک مدیریت کنید.</p>

<div class="card">
  <h2>آپلود چند تصویر</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
    <input type="hidden" name="action" value="bulk" />
    <p>
      <label>انتخاب تصاویر (چند فایل)<br>
        <input type="file" name="gallery_files[]" accept="image/*" multiple required />
      </label>
    </p>
    <p>
      <label>ترتیب شروع
        <input type="number" name="sort_order" value="0" style="width:100px" />
      </label>
      <span class="muted" style="font-size:12px"> — برای هر تصویر یکی اضافه می‌شود.</span>
    </p>
    <p>
      <label>
        <input type="checkbox" name="is_active" value="1" checked />
        فعال در سایت
      </label>
    </p>
    <p><button type="submit" class="btn">بارگذاری تصاویر</button></p>
  </form>
</div>

<div class="card">
  <h2><?= $edit ? 'ویرایش تصویر' : 'افزودن یک تصویر' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>" />
  <p>
      <label>عنوان (alt)<br>
        <input type="text" name="title" class="regular-text" style="width:100%;max-width:480px"
               value="<?= ad_e((string) ($edit['title'] ?? '')) ?>" />
      </label>
    </p>
    <?php if (!empty($edit['image_url'])): ?>
      <p>
        <img src="<?= ad_e(ad_public_upload_url((string) $edit['image_url'])) ?>" alt=""
             style="max-width:200px;height:auto;border-radius:4px;border:1px solid #c3c4c7" />
      </p>
    <?php endif; ?>
    <p>
      <label>آپلود تصویر<?= $edit ? '' : ' *' ?><br>
        <input type="file" name="image_file" accept="image/*" <?= $edit ? '' : 'required' ?> />
      </label>
    </p>
    <p>
      <label>مسیر ذخیره‌شده (اختیاری)<br>
        <input type="text" name="image_url" class="regular-text" dir="ltr" style="width:100%;max-width:480px"
               value="<?= ad_e((string) ($edit['image_url'] ?? '')) ?>" placeholder="/assets/images/uploads/gallery/…" />
      </label>
    </p>
    <p>
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
        <a class="btn btn-secondary" href="gallery.php">انصراف</a>
      <?php endif; ?>
    </p>
  </form>
</div>

<div class="card">
  <h2>تصاویر گالری</h2>
  <?php if (!$rows): ?>
    <p class="muted">هنوز تصویری ثبت نشده است.</p>
  <?php else: ?>
    <table class="widefat striped" style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:right;padding:8px">پیش‌نمایش</th>
          <th style="text-align:right;padding:8px">عنوان</th>
          <th style="text-align:right;padding:8px">ترتیب</th>
          <th style="text-align:right;padding:8px">وضعیت</th>
          <th style="text-align:right;padding:8px">عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td style="padding:8px;width:100px">
              <img src="<?= ad_e(ad_public_upload_url((string) $row['image_url'])) ?>" alt=""
                   style="width:80px;height:56px;object-fit:cover;border-radius:4px" />
            </td>
            <td style="padding:8px"><?= ad_e((string) ($row['title'] ?: '—')) ?></td>
            <td style="padding:8px"><?= (int) $row['sort_order'] ?></td>
            <td style="padding:8px"><?= (int) $row['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a class="btn btn-secondary" href="gallery.php?edit=<?= (int) $row['id'] ?>">ویرایش</a>
              <form method="post" style="display:inline" onsubmit="return confirm('حذف این تصویر؟');">
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
<?php require __DIR__ . '/_layout_bottom.php'; ?>