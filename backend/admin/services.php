<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/services.php';
require_once dirname(__DIR__) . '/lib/media.php';
ad_require_role(['admin']);

ad_services_ensure_schema();
ad_media_ensure_schema();
$db = ad_db();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;

if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}
$editCoverAlt = $edit && !empty($edit['cover']) ? ad_media_alt((string) $edit['cover']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ad_csrf_check($_POST['csrf'] ?? null)) {
        ad_flash_set('err', 'نشست نامعتبر است.');
        header('Location: services.php');
        exit;
    }
    $action = $_POST['action'] ?? 'save';
    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
            ad_flash_set('ok', 'محصول حذف شد.');
            header('Location: services.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $titleRu = trim((string) ($_POST['title_ru'] ?? ''));
        $titleHy = trim((string) ($_POST['title_hy'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $slugRu = trim((string) ($_POST['slug_ru'] ?? ''));
        $slugHy = trim((string) ($_POST['slug_hy'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $summaryRu = trim((string) ($_POST['summary_ru'] ?? ''));
        $summaryHy = trim((string) ($_POST['summary_hy'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $contentRu = trim((string) ($_POST['content_ru'] ?? ''));
        $contentHy = trim((string) ($_POST['content_hy'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $cover = trim((string) ($_POST['cover_path'] ?? ''));
        $coverAlt = trim((string) ($_POST['cover_alt'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('عنوان الزامی است.');
        }
        if ($slug === '') {
            $slug = ad_slugify($title);
        } else {
            $slug = ad_slugify($slug);
        }
        $slug = ad_service_unique_slug($slug, $id);
        if ($slugRu === '') {
            $slugRu = ad_slugify($titleRu !== '' ? $titleRu : $title);
        } else {
            $slugRu = ad_slugify($slugRu);
        }
        if ($slugHy === '') {
            $slugHy = ad_slugify($titleHy !== '' ? $titleHy : $title);
        } else {
            $slugHy = ad_slugify($slugHy);
        }

        if (!empty($_FILES['cover']['name'])) {
            $uploaded = ad_media_upload($_FILES['cover'], 'services', $coverAlt);
            if ($uploaded !== '') {
                $cover = $uploaded;
            }
        } elseif ($cover !== '') {
            ad_media_set_alt($cover, $coverAlt);
        }

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE services SET title=?, title_ru=?, title_hy=?, slug=?, slug_ru=?, slug_hy=?, summary=?, summary_ru=?, summary_hy=?, content=?, content_ru=?, content_hy=?, cover=?, sort_order=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$title, $titleRu, $titleHy, $slug, $slugRu, $slugHy, $summary, $summaryRu, $summaryHy, $content, $contentRu, $contentHy, $cover, $sort, $active, $id]);
            ad_flash_set('ok', 'محصول به‌روزرسانی شد.');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO services (title, title_ru, title_hy, slug, slug_ru, slug_hy, summary, summary_ru, summary_hy, content, content_ru, content_hy, cover, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$title, $titleRu, $titleHy, $slug, $slugRu, $slugHy, $summary, $summaryRu, $summaryHy, $content, $contentRu, $contentHy, $cover, $sort, $active]);
            ad_flash_set('ok', 'محصول افزوده شد.');
        }
    } catch (Throwable $e) {
        ad_flash_set('err', $e->getMessage());
    }
    header('Location: services.php');
    exit;
}

$rows = $db->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
$pageTitle = 'محصولات';
require __DIR__ . '/_layout_top.php';
?>
<h1>مدیریت محصولات</h1>
<p class="muted"><a href="/products/" target="_blank" rel="noopener">آرشیو محصولات (سایت)</a><?php if ($edit && !empty($edit['slug'])): ?> · <a href="/product/<?= ad_e(rawurlencode((string) $edit['slug'])) ?>/" target="_blank" rel="noopener">پیش‌نمایش محصول</a><?php endif; ?></p>

<div class="card">
  <h2><?= $edit ? 'ویرایش محصول' : 'افزودن محصول' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>" />

    <div class="form-section">
      <h3 class="form-section__title">اطلاعات محصول</h3>
      <div class="form-grid">
        <div class="field field--span-2">
          <label>عنوان پیش‌فرض / انگلیسی (الزامی — در EN نمایش داده می‌شود)</label>
          <input type="text" name="title" required value="<?= ad_e($edit['title'] ?? '') ?>" />
        </div>
        <div class="field field--span-2">
          <label>عنوان (روسی)</label>
          <input type="text" name="title_ru" value="<?= ad_e($edit['title_ru'] ?? '') ?>" />
        </div>
        <div class="field field--span-2">
          <label>عنوان (ارمنی)</label>
          <input type="text" name="title_hy" value="<?= ad_e($edit['title_hy'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>اسلاگ انگلیسی (خالی = خودکار)</label>
          <input type="text" name="slug" value="<?= ad_e($edit['slug'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>اسلاگ (روسی)</label>
          <input type="text" name="slug_ru" value="<?= ad_e($edit['slug_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>اسلاگ (ارمنی)</label>
          <input type="text" name="slug_hy" value="<?= ad_e($edit['slug_hy'] ?? '') ?>" />
        </div>
        <div class="field field--span-3">
          <label>خلاصه (انگلیسی)</label>
          <textarea name="summary" rows="3"><?= ad_e($edit['summary'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>خلاصه (روسی)</label>
          <textarea name="summary_ru" rows="3"><?= ad_e($edit['summary_ru'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>خلاصه (ارمنی)</label>
          <textarea name="summary_hy" rows="3"><?= ad_e($edit['summary_hy'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>محتوا (انگلیسی)</label>
          <textarea name="content" style="min-height:180px"><?= ad_e($edit['content'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>محتوا (روسی)</label>
          <textarea name="content_ru" style="min-height:180px"><?= ad_e($edit['content_ru'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>محتوا (ارمنی)</label>
          <textarea name="content_hy" style="min-height:180px"><?= ad_e($edit['content_hy'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section__title">تصویر و انتشار</h3>
      <div class="form-grid">
        <div class="field field--span-2">
          <label>مسیر کاور</label>
          <input type="text" name="cover_path" value="<?= ad_e($edit['cover'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>متن جایگزین (alt)</label>
          <input type="text" name="cover_alt" maxlength="255" value="<?= ad_e($editCoverAlt) ?>" placeholder="توضیح کوتاه تصویر کاور" />
        </div>
        <div class="field">
          <label>آپلود کاور</label>
          <input type="file" name="cover" accept="image/*" />
          <div class="media-field-actions">
            <button type="button" class="btn btn-secondary" data-media-picker data-media-target="cover_path" data-media-alt-target="cover_alt">انتخاب از رسانه</button>
          </div>
        </div>
        <div class="field">
          <label>ترتیب</label>
          <input type="number" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>" />
        </div>
        <div class="field field--check">
          <label><input type="checkbox" name="is_active" <?= !isset($edit) || !empty($edit['is_active']) ? 'checked' : '' ?> /> فعال</label>
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn" type="submit">ذخیره</button>
      <?php if ($edit): ?><a class="btn btn-secondary" href="services.php">انصراف</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>لیست محصولات</h2>
  <table>
    <thead><tr><th>عنوان</th><th>اسلاگ</th><th>ترتیب</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= ad_e($row['title']) ?></td>
        <td><a href="/product/<?= ad_e(rawurlencode((string) $row['slug'])) ?>/" target="_blank" rel="noopener"><?= ad_e($row['slug']) ?></a></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><?= $row['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
        <td class="row-actions">
          <a href="services.php?edit=<?= (int) $row['id'] ?>">ویرایش</a>
          <form method="post" style="display:inline" onsubmit="return confirm('حذف شود؟');">
            <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <button type="submit">حذف</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
