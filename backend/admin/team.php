<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/team.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/seo.php';
ad_require_role(['admin']);

ad_team_ensure_schema();
ad_media_ensure_schema();
$db = ad_db();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;

if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM team_members WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}
$editPhotoAlt = $edit && !empty($edit['photo']) ? ad_media_alt((string) $edit['photo']) : '';
$openModal = $edit !== null || isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ad_csrf_check($_POST['csrf'] ?? null)) {
        ad_flash_set('err', 'نشست نامعتبر است.');
        header('Location: team.php');
        exit;
    }
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM team_members WHERE id = ?')->execute([$id]);
            ad_seo_on_content_change();
            ad_flash_set('ok', 'عضو تیم حذف شد.');
            header('Location: team.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $nameEn = trim((string) ($_POST['name_en'] ?? ''));
        $nameRu = trim((string) ($_POST['name_ru'] ?? ''));
        $nameHy = trim((string) ($_POST['name_hy'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $roleEn = trim((string) ($_POST['role_en'] ?? ''));
        $roleRu = trim((string) ($_POST['role_ru'] ?? ''));
        $roleHy = trim((string) ($_POST['role_hy'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $bioRu = trim((string) ($_POST['bio_ru'] ?? ''));
        $bioHy = trim((string) ($_POST['bio_hy'] ?? ''));
        $rating = (float) ($_POST['rating'] ?? 5);
        if ($rating < 0) {
            $rating = 0;
        }
        if ($rating > 5) {
            $rating = 5;
        }
        $h1 = trim((string) ($_POST['highlight_1'] ?? ''));
        $h2 = trim((string) ($_POST['highlight_2'] ?? ''));
        $h3 = trim((string) ($_POST['highlight_3'] ?? ''));
        $h1Ru = trim((string) ($_POST['highlight_1_ru'] ?? ''));
        $h1Hy = trim((string) ($_POST['highlight_1_hy'] ?? ''));
        $h2Ru = trim((string) ($_POST['highlight_2_ru'] ?? ''));
        $h2Hy = trim((string) ($_POST['highlight_2_hy'] ?? ''));
        $h3Ru = trim((string) ($_POST['highlight_3_ru'] ?? ''));
        $h3Hy = trim((string) ($_POST['highlight_3_hy'] ?? ''));
        $joinedAt = trim((string) ($_POST['joined_at'] ?? ''));
        if ($joinedAt === '') {
            $joinedAt = null;
        }
        $wa = trim((string) ($_POST['social_whatsapp'] ?? ''));
        $tg = trim((string) ($_POST['social_telegram'] ?? ''));
        $ig = trim((string) ($_POST['social_instagram'] ?? ''));
        $li = trim((string) ($_POST['social_linkedin'] ?? ''));
        $web = trim((string) ($_POST['social_web'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $photo = trim((string) ($_POST['photo_path'] ?? ''));
        $photoAlt = trim((string) ($_POST['photo_alt'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slugRu = trim((string) ($_POST['slug_ru'] ?? ''));
        $slugHy = trim((string) ($_POST['slug_hy'] ?? ''));
        $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
        $metaTitleRu = trim((string) ($_POST['meta_title_ru'] ?? ''));
        $metaTitleHy = trim((string) ($_POST['meta_title_hy'] ?? ''));
        $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
        $metaDescriptionRu = trim((string) ($_POST['meta_description_ru'] ?? ''));
        $metaDescriptionHy = trim((string) ($_POST['meta_description_hy'] ?? ''));
        $metaKeywords = trim((string) ($_POST['meta_keywords'] ?? ''));
        $metaKeywordsRu = trim((string) ($_POST['meta_keywords_ru'] ?? ''));
        $metaKeywordsHy = trim((string) ($_POST['meta_keywords_hy'] ?? ''));
        if (mb_strlen($metaTitle) > 255) {
            $metaTitle = mb_substr($metaTitle, 0, 255);
        }
        if (mb_strlen($metaDescription) > 500) {
            $metaDescription = mb_substr($metaDescription, 0, 500);
        }
        if (mb_strlen($metaKeywords) > 500) {
            $metaKeywords = mb_substr($metaKeywords, 0, 500);
        }

        if ($name === '') {
            throw new RuntimeException('نام الزامی است.');
        }

        $slugBase = $slugInput !== '' ? $slugInput : ($nameEn !== '' ? $nameEn : $name);
        $slug = ad_team_unique_slug($slugBase, $id);

        if (!empty($_FILES['photo']['name'])) {
            $uploaded = ad_media_upload($_FILES['photo'], 'team', $photoAlt);
            if ($uploaded !== '') {
                $photo = $uploaded;
            }
        } elseif ($photo !== '') {
            ad_media_set_alt($photo, $photoAlt);
        }

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE team_members SET name=?, name_en=?, name_ru=?, name_hy=?, role=?, role_en=?, role_ru=?, role_hy=?, bio=?, bio_ru=?, bio_hy=?, rating=?, highlight_1=?, highlight_1_ru=?, highlight_1_hy=?, highlight_2=?, highlight_2_ru=?, highlight_2_hy=?, highlight_3=?, highlight_3_ru=?, highlight_3_hy=?, joined_at=?, social_whatsapp=?, social_telegram=?, social_instagram=?, social_linkedin=?, social_web=?, photo=?, sort_order=?, is_active=?, slug=?, slug_ru=?, slug_hy=?, meta_title=?, meta_title_ru=?, meta_title_hy=?, meta_description=?, meta_description_ru=?, meta_description_hy=?, meta_keywords=?, meta_keywords_ru=?, meta_keywords_hy=? WHERE id=?'
            );
            $stmt->execute([
                $name, $nameEn, $nameRu, $nameHy, $role, $roleEn, $roleRu, $roleHy, $bio, $bioRu, $bioHy, $rating,
                $h1, $h1Ru, $h1Hy, $h2, $h2Ru, $h2Hy, $h3, $h3Ru, $h3Hy, $joinedAt,
                $wa, $tg, $ig, $li, $web, $photo, $sort, $active, $slug, $slugRu, $slugHy,
                $metaTitle, $metaTitleRu, $metaTitleHy, $metaDescription, $metaDescriptionRu, $metaDescriptionHy,
                $metaKeywords, $metaKeywordsRu, $metaKeywordsHy, $id,
            ]);
            ad_flash_set('ok', 'عضو تیم به‌روزرسانی شد.');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO team_members (name, name_en, name_ru, name_hy, role, role_en, role_ru, role_hy, bio, bio_ru, bio_hy, rating, highlight_1, highlight_1_ru, highlight_1_hy, highlight_2, highlight_2_ru, highlight_2_hy, highlight_3, highlight_3_ru, highlight_3_hy, joined_at, social_whatsapp, social_telegram, social_instagram, social_linkedin, social_web, photo, sort_order, is_active, slug, slug_ru, slug_hy, meta_title, meta_title_ru, meta_title_hy, meta_description, meta_description_ru, meta_description_hy, meta_keywords, meta_keywords_ru, meta_keywords_hy) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $name, $nameEn, $nameRu, $nameHy, $role, $roleEn, $roleRu, $roleHy, $bio, $bioRu, $bioHy, $rating,
                $h1, $h1Ru, $h1Hy, $h2, $h2Ru, $h2Hy, $h3, $h3Ru, $h3Hy, $joinedAt,
                $wa, $tg, $ig, $li, $web, $photo, $sort, $active, $slug, $slugRu, $slugHy,
                $metaTitle, $metaTitleRu, $metaTitleHy, $metaDescription, $metaDescriptionRu, $metaDescriptionHy,
                $metaKeywords, $metaKeywordsRu, $metaKeywordsHy,
            ]);
            ad_flash_set('ok', 'عضو تیم افزوده شد.');
        }
        ad_seo_on_content_change();
    } catch (Throwable $e) {
        ad_flash_set('err', $e->getMessage());
        $failId = (int) ($_POST['id'] ?? 0);
        header('Location: team.php' . ($failId > 0 ? '?edit=' . $failId : '?new=1'));
        exit;
    }
    header('Location: team.php');
    exit;
}

$rows = $db->query('SELECT * FROM team_members ORDER BY sort_order ASC, id ASC')->fetchAll();
$pageTitle = 'مدیریت تیم';
require __DIR__ . '/_layout_top.php';
?>
<div class="page-toolbar">
  <div>
    <h1>مدیریت تیم</h1>
    <p class="muted" style="margin:0.35rem 0 0">نمونه‌کارها از پنل نمونه‌کار و «مسئولین پروژه» تعیین می‌شوند.</p>
  </div>
  <div class="page-toolbar__actions">
    <a class="btn" href="team.php?new=1">افزودن عضو</a>
  </div>
</div>

<div class="card">
  <h2>لیست اعضا</h2>
  <table>
    <thead>
      <tr><th>عکس</th><th>نام</th><th>نقش</th><th>امتیاز</th><th>ترتیب</th><th>وضعیت</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['photo']): ?><img class="thumb" src="../../<?= ad_e($row['photo']) ?>" alt="" /><?php endif; ?></td>
        <td><?= ad_e($row['name']) ?></td>
        <td><?= ad_e($row['role']) ?></td>
        <td><?= ad_e((string) ($row['rating'] ?? '5')) ?></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><?= $row['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
        <td class="row-actions">
          <a href="team.php?edit=<?= (int) $row['id'] ?>">ویرایش</a>
          <a href="../../member.html?<?= trim((string) ($row['slug'] ?? '')) !== '' ? 'slug=' . rawurlencode((string) $row['slug']) : 'id=' . (int) $row['id'] ?>" target="_blank" rel="noopener">رزومه</a>
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
<div class="admin-modal" id="team-form-modal"<?= $openModal ? ' data-admin-modal-autoopen' : '' ?> hidden>
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__dialog">
    <div class="admin-modal__header">
      <h2><?= $edit ? 'ویرایش عضو' : 'افزودن عضو' ?></h2>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="بستن">×</button>
    </div>
    <div class="admin-modal__body">
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
    <input type="hidden" name="action" value="save" />
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>" />

    <div class="form-section">
      <h3 class="form-section__title">اطلاعات اصلی</h3>
      <div class="form-grid">
        <div class="field">
          <label>نام فارسی</label>
          <input type="text" name="name" required value="<?= ad_e($edit['name'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نام انگلیسی</label>
          <input type="text" name="name_en" value="<?= ad_e($edit['name_en'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نام روسی</label>
          <input type="text" name="name_ru" value="<?= ad_e($edit['name_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نام ارمنی</label>
          <input type="text" name="name_hy" value="<?= ad_e($edit['name_hy'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>امتیاز (۰ تا ۵)</label>
          <input type="number" name="rating" min="0" max="5" step="0.1" value="<?= ad_e((string) ($edit['rating'] ?? '5.0')) ?>" />
        </div>
        <div class="field">
          <label>سمت فارسی</label>
          <input type="text" name="role" value="<?= ad_e($edit['role'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>سمت انگلیسی</label>
          <input type="text" name="role_en" value="<?= ad_e($edit['role_en'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>سمت روسی</label>
          <input type="text" name="role_ru" value="<?= ad_e($edit['role_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>سمت ارمنی</label>
          <input type="text" name="role_hy" value="<?= ad_e($edit['role_hy'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>تاریخ عضویت</label>
          <input type="date" name="joined_at" value="<?= ad_e($edit['joined_at'] ?? '') ?>" />
        </div>
        <div class="field field--span-3">
          <label>بیوگرافی کامل (فارسی)</label>
          <textarea name="bio" rows="7"><?= ad_e($edit['bio'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>بیوگرافی کامل (روسی)</label>
          <textarea name="bio_ru" rows="7"><?= ad_e($edit['bio_ru'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-3">
          <label>بیوگرافی کامل (ارمنی)</label>
          <textarea name="bio_hy" rows="7"><?= ad_e($edit['bio_hy'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section__title">نکات برجسته</h3>
      <div class="form-grid">
        <div class="field">
          <label>نکته برجسته ۱ (فارسی)</label>
          <input type="text" name="highlight_1" value="<?= ad_e($edit['highlight_1'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۱ (روسی)</label>
          <input type="text" name="highlight_1_ru" value="<?= ad_e($edit['highlight_1_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۱ (ارمنی)</label>
          <input type="text" name="highlight_1_hy" value="<?= ad_e($edit['highlight_1_hy'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۲ (فارسی)</label>
          <input type="text" name="highlight_2" value="<?= ad_e($edit['highlight_2'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۲ (روسی)</label>
          <input type="text" name="highlight_2_ru" value="<?= ad_e($edit['highlight_2_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۲ (ارمنی)</label>
          <input type="text" name="highlight_2_hy" value="<?= ad_e($edit['highlight_2_hy'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۳ (فارسی)</label>
          <input type="text" name="highlight_3" value="<?= ad_e($edit['highlight_3'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۳ (روسی)</label>
          <input type="text" name="highlight_3_ru" value="<?= ad_e($edit['highlight_3_ru'] ?? '') ?>" />
        </div>
        <div class="field">
          <label>نکته برجسته ۳ (ارمنی)</label>
          <input type="text" name="highlight_3_hy" value="<?= ad_e($edit['highlight_3_hy'] ?? '') ?>" />
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section__title">شبکه‌های اجتماعی</h3>
      <div class="form-grid">
        <div class="field">
          <label>واتساپ</label>
          <input type="text" name="social_whatsapp" value="<?= ad_e($edit['social_whatsapp'] ?? '') ?>" placeholder="https://wa.me/98..." />
        </div>
        <div class="field">
          <label>تلگرام</label>
          <input type="text" name="social_telegram" value="<?= ad_e($edit['social_telegram'] ?? '') ?>" placeholder="https://t.me/..." />
        </div>
        <div class="field">
          <label>اینستاگرام</label>
          <input type="url" name="social_instagram" value="<?= ad_e($edit['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/..." />
        </div>
        <div class="field">
          <label>لینکدین</label>
          <input type="url" name="social_linkedin" value="<?= ad_e($edit['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/..." />
        </div>
        <div class="field field--span-2">
          <label>وبسایت / لینک دیگر</label>
          <input type="url" name="social_web" value="<?= ad_e($edit['social_web'] ?? '') ?>" placeholder="https://..." />
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section__title">تصویر و انتشار</h3>
      <div class="form-grid">
        <div class="field field--span-2">
          <label>عکس عضو</label>
          <input type="text" name="photo_path" class="media-field-paths" value="<?= ad_e($edit['photo'] ?? '') ?>" placeholder="assets/images/uploads/..." hidden />
          <div class="media-picked-blocks media-picked-blocks--single" data-media-preview="photo_path" data-media-alt="photo_alt"></div>
          <div class="media-field-actions">
            <button type="button" class="btn btn-secondary" data-media-picker data-media-target="photo_path" data-media-alt-target="photo_alt">انتخاب از رسانه</button>
          </div>
          <input type="file" name="photo" accept="image/*" style="margin-top:0.5rem" />
          <p class="hint">یا عکس را از دستگاه آپلود کنید.</p>
        </div>
        <div class="field">
          <label>متن جایگزین (alt)</label>
          <input type="text" name="photo_alt" maxlength="255" value="<?= ad_e($editPhotoAlt) ?>" placeholder="مثلاً تصویر مهندس حسین مهرجو" />
        </div>
        <div class="field">
          <label>ترتیب نمایش</label>
          <input type="number" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>" />
        </div>
        <div class="field field--check">
          <label><input type="checkbox" name="is_active" <?= !isset($edit) || !empty($edit['is_active']) ? 'checked' : '' ?> /> فعال</label>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section__title">سئو گوگل</h3>
      <div class="form-grid">
        <div class="field field--span-2">
          <label>اسلاگ (فارسی)</label>
          <input type="text" name="slug" maxlength="200" value="<?= ad_e($edit['slug'] ?? '') ?>" placeholder="خالی = از نام انگلیسی یا فارسی ساخته می‌شود" dir="ltr" />
          <p class="hint">آدرس صفحه: member.html?slug=...</p>
        </div>
        <div class="field field--span-2">
          <label>اسلاگ (روسی)</label>
          <input type="text" name="slug_ru" maxlength="200" value="<?= ad_e($edit['slug_ru'] ?? '') ?>" placeholder="خالی = از نام Русский ساخته می‌شود" dir="ltr" />
        </div>
        <div class="field field--span-2">
          <label>اسلاگ (ارمنی)</label>
          <input type="text" name="slug_hy" maxlength="200" value="<?= ad_e($edit['slug_hy'] ?? '') ?>" placeholder="خالی = از نام Հայերեն ساخته می‌شود" dir="ltr" />
        </div>
        <div class="field field--span-2">
          <label>متا تایتل (فارسی)</label>
          <input type="text" name="meta_title" maxlength="255" value="<?= ad_e($edit['meta_title'] ?? '') ?>" placeholder="خالی = نام عضو | آنا دولوپر" />
        </div>
        <div class="field field--span-2">
          <label>متا تایتل (روسی)</label>
          <input type="text" name="meta_title_ru" maxlength="255" value="<?= ad_e($edit['meta_title_ru'] ?? '') ?>" placeholder="خالی = نام عضو | آنا دولوپر" />
        </div>
        <div class="field field--span-2">
          <label>متا تایتل (ارمنی)</label>
          <input type="text" name="meta_title_hy" maxlength="255" value="<?= ad_e($edit['meta_title_hy'] ?? '') ?>" placeholder="خالی = نام عضو | آنا دولوپر" />
        </div>
        <div class="field field--span-2">
          <label>متا دیسکریپشن (فارسی)</label>
          <textarea name="meta_description" rows="3" maxlength="500" placeholder="توضیح کوتاه برای نتایج گوگل"><?= ad_e($edit['meta_description'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-2">
          <label>متا دیسکریپشن (روسی)</label>
          <textarea name="meta_description_ru" rows="3" maxlength="500" placeholder="توضیح کوتاه برای نتایج گوگل"><?= ad_e($edit['meta_description_ru'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-2">
          <label>متا دیسکریپشن (ارمنی)</label>
          <textarea name="meta_description_hy" rows="3" maxlength="500" placeholder="توضیح کوتاه برای نتایج گوگل"><?= ad_e($edit['meta_description_hy'] ?? '') ?></textarea>
        </div>
        <div class="field field--span-2">
          <label>متا کیورد (فارسی)</label>
          <input type="text" name="meta_keywords" maxlength="500" value="<?= ad_e($edit['meta_keywords'] ?? '') ?>" placeholder="کلمه۱, کلمه۲, کلمه۳" />
        </div>
        <div class="field field--span-2">
          <label>متا کیورد (روسی)</label>
          <input type="text" name="meta_keywords_ru" maxlength="500" value="<?= ad_e($edit['meta_keywords_ru'] ?? '') ?>" placeholder="کلمه۱, کلمه۲, کلمه۳" />
        </div>
        <div class="field field--span-2">
          <label>متا کیورد (ارمنی)</label>
          <input type="text" name="meta_keywords_hy" maxlength="500" value="<?= ad_e($edit['meta_keywords_hy'] ?? '') ?>" placeholder="کلمه۱, کلمه۲, کلمه۳" />
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn" type="submit">ذخیره</button>
      <a class="btn btn-secondary" href="team.php">انصراف</a>
      <?php if ($edit): ?>
        <a class="btn btn-secondary" href="../../member.html?<?= trim((string) ($edit['slug'] ?? '')) !== '' ? 'slug=' . rawurlencode((string) $edit['slug']) : 'id=' . (int) $edit['id'] ?>" target="_blank" rel="noopener">مشاهده رزومه</a>
      <?php endif; ?>
    </div>
  </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
