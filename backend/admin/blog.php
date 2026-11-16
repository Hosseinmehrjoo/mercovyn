<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once dirname(__DIR__) . '/lib/blog.php';
require_once dirname(__DIR__) . '/lib/media.php';
require_once dirname(__DIR__) . '/lib/comments.php';
require_once dirname(__DIR__) . '/lib/seo.php';
require_once dirname(__DIR__) . '/lib/translate.php';
ad_require_capability('blog');

ad_blog_ensure_schema();
ad_media_ensure_schema();
ad_comments_ensure_schema();
$db = ad_db();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;

if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}
$editCoverAlt = $edit && !empty($edit['cover']) ? ad_media_alt((string) $edit['cover']) : '';
$editCategoryIds = $edit ? ad_blog_post_category_ids((int) $edit['id']) : [];
$categories = ad_blog_categories_list();
$openModal = $edit !== null || isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ad_csrf_check($_POST['csrf'] ?? null)) {
        ad_flash_set('err', 'نشست نامعتبر است.');
        header('Location: blog.php');
        exit;
    }
    $action = (string) ($_POST['action'] ?? 'save');

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare('DELETE FROM blog_post_categories WHERE post_id = ?')->execute([$id]);
                ad_comments_delete_for_post($id);
                $db->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([$id]);
                ad_seo_on_content_change();
            }
            ad_flash_set('ok', 'نوشته حذف شد.');
            header('Location: blog.php');
            exit;
        }

        if ($action === 'add_category') {
            $name = trim((string) ($_POST['cat_name'] ?? ''));
            $created = ad_blog_category_create($name);
            ad_flash_set('ok', 'دسته‌بندی «' . $created['name'] . '» اضافه شد.');
            $backId = (int) ($_POST['return_id'] ?? 0);
            header('Location: blog.php' . ($backId > 0 ? '?edit=' . $backId : '?new=1') . '#categories');
            exit;
        }

        @set_time_limit(120);
        $postInput = $_POST;
        if (!empty($_FILES['cover']['name'])) {
            $coverAlt = trim((string) ($_POST['cover_alt'] ?? ''));
            $uploaded = ad_media_upload($_FILES['cover'], 'blog', $coverAlt);
            if ($uploaded !== '') {
                $postInput['cover_path'] = $uploaded;
            }
        }

        $saved = ad_blog_save_post_from_input($postInput, false);
        $id = $saved['id'];
        $msg = !empty($saved['created']) ? 'نوشته افزوده شد.' : 'نوشته به‌روزرسانی شد.';
        $translateWarnings = ad_translate_take_warnings();
        if ($translateWarnings !== []) {
            $msg .= ' (هشدار ترجمه: ' . implode('؛ ', array_slice($translateWarnings, 0, 5)) . ')';
        }
        if (ad_translate_enabled()) {
            $msg .= ' عنوان و خلاصه/متا (در صورت خالی بودن در زبان‌های دیگر) خودکار به‌روز شد؛ بدنه را در هر زبان جدا وارد کنید.';
        }
        ad_flash_set('ok', $msg);
    } catch (Throwable $e) {
        ad_flash_set('err', $e->getMessage());
        $failId = (int) ($_POST['id'] ?? 0);
        header('Location: blog.php' . ($failId > 0 ? '?edit=' . $failId : '?new=1'));
        exit;
    }
    header('Location: blog.php');
    exit;
}

$rows = $db->query(
    'SELECT id, title, slug, status, cover, published_at, created_at FROM blog_posts ORDER BY id DESC'
)->fetchAll();
$pageTitle = 'نوشته‌ها';
require __DIR__ . '/_layout_top.php';
?>
<div class="page-toolbar">
  <div>
    <h1>نوشته‌ها</h1>
    <p class="muted" style="margin:0.35rem 0 0"><a href="/blog/" target="_blank" rel="noopener">مشاهده آرشیو وبلاگ</a> · متن اصلی <strong>انگلیسی</strong> است؛ عنوان/خلاصه/متا خودکار ترجمه می‌شود — <strong>بدنهٔ هر زبان را دستی</strong> در فرم وارد کنید.</p>
  </div>
  <div class="page-toolbar__actions">
    <a class="btn btn-secondary" href="blog-categories.php">دسته‌بندی‌ها</a>
    <a class="btn" href="blog.php?new=1">نوشتن جدید</a>
  </div>
</div>

<div class="card">
  <h2>همه نوشته‌ها</h2>
  <table>
    <thead>
      <tr>
        <th>کاور</th>
        <th>عنوان</th>
        <th>دسته‌ها</th>
        <th>وضعیت</th>
        <th>تاریخ</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="muted">هنوز نوشته‌ای ثبت نشده است.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row):
        $cats = ad_blog_post_categories((int) $row['id']);
        $catNames = array_map(static fn(array $c): string => (string) $c['name'], $cats);
        ?>
      <tr>
        <td><?php if (!empty($row['cover'])): ?><img class="thumb" src="../../<?= ad_e($row['cover']) ?>" alt="" /><?php else: ?>—<?php endif; ?></td>
        <td>
          <strong><?= ad_e($row['title']) ?></strong>
          <div class="muted" style="font-size:12px"><?= ad_e($row['slug']) ?></div>
        </td>
        <td><?= $catNames ? ad_e(implode('، ', $catNames)) : '—' ?></td>
        <td><?= $row['status'] === 'published' ? 'منتشرشده' : 'پیش‌نویس' ?></td>
        <td><?= ad_e($row['published_at'] ?? $row['created_at'] ?? '—') ?></td>
        <td class="row-actions">
          <a href="blog.php?edit=<?= (int) $row['id'] ?>">ویرایش</a>
          <?php if ($row['status'] === 'published'): ?>
            <a href="/blog-single/<?= ad_e(rawurlencode((string) $row['slug'])) ?>/" target="_blank" rel="noopener">مشاهده</a>
          <?php endif; ?>
          <form method="post" style="display:inline" onsubmit="return confirm('این نوشته حذف شود؟');">
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

<div class="admin-modal" id="blog-form-modal"<?= $openModal ? ' data-admin-modal-autoopen' : '' ?> hidden>
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__dialog admin-modal__dialog--wide">
    <div class="admin-modal__header">
      <div>
        <h2 style="margin:0"><?= $edit ? 'ویرایش نوشته' : 'نوشتن جدید' ?></h2>
        <p id="blog-autosave-status" class="muted" style="margin:0.35rem 0 0;font-size:12px"></p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="بستن">×</button>
    </div>
    <div class="admin-modal__body">
      <form method="post" enctype="multipart/form-data" id="blog-post-form">
        <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>" />

        <div class="form-section">
          <h3 class="form-section__title">محتوای نوشته (انگلیسی — منبع)</h3>
          <div class="form-grid">
            <div class="field field--span-2">
              <label>عنوان (English)</label>
              <input type="text" name="title" required value="<?= ad_e($edit['title'] ?? '') ?>" />
            </div>
            <div class="field">
              <label>وضعیت</label>
              <select name="status">
                <option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                <option value="published" <?= ($edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>منتشرشده</option>
              </select>
            </div>
            <div class="field field--span-3">
              <label>نامک (اسلاگ)</label>
              <input type="text" name="slug" value="<?= ad_e($edit['slug'] ?? '') ?>" placeholder="خالی = از عنوان ساخته می‌شود" />
            </div>
            <div class="field field--span-3">
              <label>خلاصه</label>
              <textarea name="excerpt" rows="3"><?= ad_e($edit['excerpt'] ?? '') ?></textarea>
            </div>
            <div class="field field--span-3">
              <label>محتوا</label>
              <textarea name="content" id="blog-content" rows="14"><?= ad_e($edit['content'] ?? '') ?></textarea>
              <p class="hint">از دکمهٔ تمام‌صفحه در نوار ابزار ادیتور برای نوشتن راحت‌تر استفاده کنید.</p>
            </div>
          </div>
        </div>

        <?php
        $blogLocalePanels = [
            'ru' => 'روسی (RU)',
            'hy' => 'ارمنی (HY)',
            'ar' => 'عربی (AR)',
        ];
        foreach ($blogLocalePanels as $locale => $localeLabel):
            ?>
        <div class="form-section blog-locale-panel">
          <h3 class="form-section__title">محتوای نوشته — <?= ad_e($localeLabel) ?></h3>
          <p class="hint" style="margin:0 0 0.75rem">فقط بدنهٔ مقاله را دستی وارد کنید. عنوان، خلاصه و متا با ذخیره خودکار از انگلیسی ترجمه می‌شوند.</p>
          <div class="form-grid">
            <div class="field field--span-3">
              <label>محتوا (بدنه)</label>
              <textarea name="content_<?= ad_e($locale) ?>" id="blog-content-<?= ad_e($locale) ?>" class="blog-content-locale" rows="12"><?= ad_e($edit['content_' . $locale] ?? '') ?></textarea>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="form-section" id="categories">
          <h3 class="form-section__title">دسته‌بندی‌ها</h3>
          <div class="tech-toolbar">
            <p class="hint" style="margin:0">یک یا چند دسته را انتخاب کنید.</p>
            <button type="button" class="btn btn-secondary" data-admin-modal-open="blog-cat-add-modal">افزودن دسته‌بندی</button>
          </div>
          <?php if (!$categories): ?>
            <p class="muted">هنوز دسته‌ای نیست — از دکمه بالا یکی بسازید یا به <a href="blog-categories.php">صفحه دسته‌بندی‌ها</a> بروید.</p>
          <?php else: ?>
            <div class="tech-check-grid blog-cat-check-grid">
              <?php foreach ($categories as $cat):
                  $checked = in_array((int) $cat['id'], $editCategoryIds, true);
                  ?>
                <label class="tech-check">
                  <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>" <?= $checked ? 'checked' : '' ?> />
                  <span><?= ad_e($cat['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-section">
          <h3 class="form-section__title">تصویر شاخص</h3>
          <div class="form-grid">
            <div class="field field--span-2">
              <label>کاور</label>
              <input type="text" name="cover_path" class="media-field-paths" value="<?= ad_e($edit['cover'] ?? '') ?>" placeholder="assets/images/uploads/..." hidden />
              <div class="media-picked-blocks media-picked-blocks--single" data-media-preview="cover_path" data-media-alt="cover_alt"></div>
              <div class="media-field-actions">
                <button type="button" class="btn btn-secondary" data-media-picker data-media-target="cover_path" data-media-alt-target="cover_alt">انتخاب از رسانه</button>
              </div>
              <input type="file" name="cover" accept="image/*" style="margin-top:0.5rem" />
            </div>
            <div class="field">
              <label>متن جایگزین (alt)</label>
              <input type="text" name="cover_alt" maxlength="255" value="<?= ad_e($editCoverAlt) ?>" placeholder="توضیح کوتاه تصویر کاور" />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3 class="form-section__title">سئو گوگل</h3>
          <div class="form-grid">
            <div class="field field--span-2">
              <label>متا تایتل</label>
              <input type="text" name="meta_title" maxlength="255" value="<?= ad_e($edit['meta_title'] ?? '') ?>" placeholder="خالی = عنوان نوشته | آنا دولوپر" />
            </div>
            <div class="field field--span-2">
              <label>متا دیسکریپشن</label>
              <textarea name="meta_description" rows="3" maxlength="500" placeholder="توضیح کوتاه برای نتایج گوگل"><?= ad_e($edit['meta_description'] ?? '') ?></textarea>
            </div>
            <div class="field field--span-2">
              <label>متا کیورد</label>
              <input type="text" name="meta_keywords" maxlength="500" value="<?= ad_e($edit['meta_keywords'] ?? '') ?>" placeholder="کلمه۱, کلمه۲, کلمه۳" />
            </div>
          </div>
        </div>

        <div class="actions">
          <button class="btn" type="submit">ذخیره</button>
          <a class="btn btn-secondary" href="blog.php">انصراف</a>
          <?php if ($edit && ($edit['status'] ?? '') === 'published'): ?>
            <a class="btn btn-secondary" href="/blog-single/<?= ad_e(rawurlencode((string) $edit['slug'])) ?>/" target="_blank" rel="noopener">مشاهده</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="admin-modal" id="blog-cat-add-modal" hidden>
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__dialog admin-modal__dialog--sm">
    <div class="admin-modal__header">
      <h2>افزودن دسته‌بندی</h2>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="بستن">×</button>
    </div>
    <div class="admin-modal__body">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= ad_e(ad_csrf_token()) ?>" />
        <input type="hidden" name="action" value="add_category" />
        <input type="hidden" name="return_id" value="<?= (int) ($edit['id'] ?? 0) ?>" />
        <div class="field">
          <label>نام دسته‌بندی</label>
          <input type="text" name="cat_name" required maxlength="120" placeholder="مثلاً توسعه وب" />
        </div>
        <div class="actions">
          <button class="btn" type="submit">افزودن</button>
          <button type="button" class="btn btn-secondary" data-admin-modal-close>انصراف</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js" id="mercovyn-ckeditor-js"></script>
<script>
(function () {
  var EDITOR_IDS = ["blog-content", "blog-content-ru", "blog-content-hy", "blog-content-ar"];
  var MODAL_ID = "blog-form-modal";
  var CKE_FLOAT_Z = 200000;
  var CKE_DIALOG_COVER_Z = 200010;
  var CKE_DIALOG_Z = 200020;
  var bootScheduled = false;

  function ckeditorReady() {
    return typeof window.CKEDITOR !== "undefined" && typeof window.CKEDITOR.replace === "function";
  }

  function liftCkeDialogsToBody() {
    var cover = document.querySelector(".cke_dialog_background_cover");
    var dialog = document.querySelector(".cke_dialog");
    [cover, dialog].forEach(function (node) {
      if (!node || !node.parentNode) return;
      if (node.parentNode === document.body) {
        return;
      }
      if (node.parentNode.closest && node.parentNode.closest(".admin-modal")) {
        document.body.appendChild(node);
      }
    });
    if (cover) cover.style.zIndex = String(CKE_DIALOG_COVER_Z);
    if (dialog) dialog.style.zIndex = String(CKE_DIALOG_Z);
  }

  function wireEditorDialogs(editor) {
    editor.on("dialogShow", function () {
      window.setTimeout(liftCkeDialogsToBody, 0);
      window.setTimeout(liftCkeDialogsToBody, 50);
    });
  }

  function applyGlobalCkeZIndex() {
    if (!ckeditorReady()) return;
    CKEDITOR.config.baseFloatZIndex = CKE_FLOAT_Z;
  }

  function editorConfig(id) {
    var locale = id === "blog-content" ? "en" : id.replace("blog-content-", "");
    var rtl = locale === "ar";
    return {
      language: "en",
      contentsLangDirection: rtl ? "rtl" : "ltr",
      height: id === "blog-content" ? 380 : 300,
      baseFloatZIndex: CKE_FLOAT_Z,
      removePlugins: "exportpdf",
      allowedContent: true,
      toolbar: [
        { name: "document", items: ["Source", "-", "Maximize", "Preview"] },
        { name: "clipboard", items: ["Cut", "Copy", "Paste", "PasteText", "PasteFromWord", "-", "Undo", "Redo"] },
        { name: "editing", items: ["Find", "Replace", "-", "SelectAll"] },
        "/",
        { name: "basicstyles", items: ["Bold", "Italic", "Underline", "Strike", "-", "RemoveFormat"] },
        { name: "paragraph", items: ["NumberedList", "BulletedList", "-", "Outdent", "Indent", "-", "Blockquote", "-", "JustifyLeft", "JustifyCenter", "JustifyRight", "JustifyBlock"] },
        { name: "links", items: ["Link", "Unlink", "Anchor"] },
        { name: "insert", items: ["Image", "Table", "HorizontalRule", "SpecialChar"] },
        "/",
        { name: "styles", items: ["Styles", "Format", "Font", "FontSize"] },
        { name: "colors", items: ["TextColor", "BGColor"] }
      ]
    };
  }

  function initEditor(id) {
    if (!ckeditorReady()) return false;
    if (CKEDITOR.instances[id]) {
      try { CKEDITOR.instances[id].destroy(true); } catch (ignore) {}
    }
    var el = document.getElementById(id);
    if (!el) return false;
    var modal = el.closest(".admin-modal");
    if (modal && modal.hidden) return false;
    try {
      applyGlobalCkeZIndex();
      var editor = CKEDITOR.replace(id, editorConfig(id));
      if (editor) {
        var attach = function () { wireEditorDialogs(editor); };
        if (editor.status === "ready") attach();
        else editor.on("instanceReady", attach);
      }
      return true;
    } catch (err) {
      console.warn("CKEditor init failed for", id, err);
      return false;
    }
  }

  function modalIsOpen() {
    var modal = document.getElementById(MODAL_ID);
    return modal && !modal.hidden;
  }

  function bootEditors() {
    if (!modalIsOpen()) return;
    if (!ckeditorReady()) {
      if (!bootScheduled) {
        bootScheduled = true;
        setTimeout(function () {
          bootScheduled = false;
          bootEditors();
        }, 150);
      }
      return;
    }
    window.setTimeout(function () {
      EDITOR_IDS.forEach(function (id) {
        initEditor(id);
      });
      EDITOR_IDS.forEach(function (id) {
        var inst = CKEDITOR.instances[id];
        if (inst) {
          try { inst.resize("100%", id === "blog-content" ? 380 : 300, true); } catch (e) {}
        }
      });
    }, 60);
  }

  window.MercovynBlogCkeBoot = bootEditors;

  document.addEventListener("admin-modal:open", function (e) {
    if (!e.detail || e.detail.id !== MODAL_ID) return;
    bootEditors();
  });

  window.addEventListener("load", function () {
    if (modalIsOpen()) bootEditors();
  });

  var ckScript = document.getElementById("mercovyn-ckeditor-js");
  if (ckScript) {
    ckScript.addEventListener("load", function () {
      applyGlobalCkeZIndex();
      if (modalIsOpen()) bootEditors();
    });
    ckScript.addEventListener("error", function () {
      console.error("CKEditor CDN failed to load. Check network or CSP.");
    });
  }

  var form = document.getElementById("blog-post-form");
  if (form) {
    form.addEventListener("submit", function () {
      if (typeof CKEDITOR === "undefined") return;
      EDITOR_IDS.forEach(function (id) {
        if (CKEDITOR.instances[id]) CKEDITOR.instances[id].updateElement();
      });
    });
  }
})();
</script>
<script src="blog-autosave.js?ver=20261116autosave" defer></script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
