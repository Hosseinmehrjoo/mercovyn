(function () {
  "use strict";

  var INTERVAL_MS = 20000;
  var MODAL_ID = "blog-form-modal";
  var FORM_ID = "blog-post-form";
  var STATUS_ID = "blog-autosave-status";
  var EDITOR_IDS = ["blog-content", "blog-content-ru", "blog-content-hy", "blog-content-ar"];
  var STORAGE_PREFIX = "mercovyn_blog_draft_v1:";

  var timer = null;
  var inFlight = false;
  var manualSubmit = false;
  var lastFingerprint = "";

  function getForm() {
    return document.getElementById(FORM_ID);
  }

  function getStatusEl() {
    return document.getElementById(STATUS_ID);
  }

  function setStatus(text, isErr) {
    var el = getStatusEl();
    if (!el) return;
    el.textContent = text;
    el.style.color = isErr ? "var(--wp-danger, #d63638)" : "var(--wp-muted, #646970)";
  }

  function syncEditors() {
    if (typeof CKEDITOR === "undefined") return;
    EDITOR_IDS.forEach(function (id) {
      if (CKEDITOR.instances[id]) {
        try {
          CKEDITOR.instances[id].updateElement();
        } catch (e) {}
      }
    });
  }

  function collectFormData() {
    var form = getForm();
    if (!form) return null;
    syncEditors();
    return new FormData(form);
  }

  function fingerprint(fd) {
    var parts = [];
    fd.forEach(function (value, key) {
      if (key === "csrf") return;
      parts.push(key + "=" + String(value).length + ":" + String(value).slice(0, 80));
    });
    return parts.sort().join("|");
  }

  function storageKey(postId) {
    return STORAGE_PREFIX + (postId > 0 ? String(postId) : "new");
  }

  function saveLocalBackup(fd) {
    try {
      var id = parseInt(String(fd.get("id") || "0"), 10) || 0;
      var payload = {};
      fd.forEach(function (value, key) {
        if (key === "csrf") return;
        payload[key] = value;
      });
      payload.__saved_at = new Date().toISOString();
      localStorage.setItem(storageKey(id), JSON.stringify(payload));
    } catch (e) {}
  }

  function autosave() {
    var form = getForm();
    var modal = document.getElementById(MODAL_ID);
    if (!form || !modal || modal.hidden || manualSubmit || inFlight) return;

    var fd = collectFormData();
    if (!fd) return;

    var fp = fingerprint(fd);
    if (fp === lastFingerprint) return;

    inFlight = true;
    setStatus("در حال ذخیرهٔ خودکار…", false);

    fetch("blog-autosave.php", {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, status: r.status, data: data };
        });
      })
      .then(function (res) {
        inFlight = false;
        if (res.data && res.data.ok) {
          lastFingerprint = fp;
          saveLocalBackup(fd);
          var id = res.data.id;
          if (id) {
            var idInput = form.querySelector('input[name="id"]');
            if (idInput && parseInt(idInput.value, 10) < 1) {
              idInput.value = String(id);
              if (window.history && window.history.replaceState) {
                window.history.replaceState(null, "", "blog.php?edit=" + id);
              }
            }
          }
          var t = new Date();
          setStatus("پیش‌نویس ذخیره شد — " + t.toLocaleTimeString("fa-IR"), false);
          return;
        }
        if (res.data && res.data.error === "login_required") {
          saveLocalBackup(fd);
          setStatus("نشست تمام شد؛ متن در مرورگر نگه‌داری شد — وارد شوید و دوباره باز کنید.", true);
          return;
        }
        if (res.data && res.data.error === "csrf") {
          setStatus("نشست منقضی شد — صفحه را رفرش کنید.", true);
          return;
        }
        if (res.data && res.data.message && res.data.message.indexOf("محتوایی") >= 0) {
          setStatus("برای ذخیره خودکار کمی متن بنویسید.", false);
          return;
        }
        setStatus((res.data && res.data.message) || "خطا در ذخیره خودکار", true);
      })
      .catch(function () {
        inFlight = false;
        saveLocalBackup(collectFormData());
        setStatus("اتصال قطع شد — نسخهٔ موقت در مرورگر ذخیره شد.", true);
      });
  }

  function startTimer() {
    if (timer) window.clearInterval(timer);
    timer = window.setInterval(autosave, INTERVAL_MS);
  }

  function stopTimer() {
    if (timer) {
      window.clearInterval(timer);
      timer = null;
    }
  }

  function offerLocalRestore() {
    var form = getForm();
    if (!form) return;
    var idEl = form.querySelector('input[name="id"]');
    var id = parseInt(String(idEl && idEl.value ? idEl.value : "0"), 10) || 0;
    var key = storageKey(id);
    var raw;
    try {
      raw = localStorage.getItem(key);
    } catch (e) {
      return;
    }
    if (!raw) return;
    var data;
    try {
      data = JSON.parse(raw);
    } catch (e2) {
      return;
    }
    if (!data || (!data.title && !data.content)) return;
    if (!window.confirm("یک پیش‌نویس ذخیره‌شده در مرورگر پیدا شد. بازیابی شود؟")) return;

    Object.keys(data).forEach(function (key) {
      if (key.indexOf("__") === 0) return;
      var el = form.querySelector('[name="' + key.replace(/"/g, '\\"') + '"]');
      if (!el) return;
      if (el.type === "checkbox") {
        el.checked = data[key] === el.value || data[key] === true || data[key] === "on";
      } else {
        el.value = data[key];
      }
    });
    setStatus("پیش‌نویس محلی بازیابی شد — یک بار «ذخیره» یا صبر برای ذخیره خودکار.", false);
    if (window.MercovynBlogCkeBoot) window.MercovynBlogCkeBoot();
  }

  function onModalOpen() {
    setStatus("ذخیره خودکار هر " + Math.round(INTERVAL_MS / 1000) + " ثانیه (پیش‌نویس)", false);
    startTimer();
    window.setTimeout(offerLocalRestore, 400);
    window.setTimeout(autosave, 2500);
  }

  function onModalClose() {
    stopTimer();
  }

  document.addEventListener("admin-modal:open", function (e) {
    if (!e.detail || e.detail.id !== MODAL_ID) return;
    onModalOpen();
  });

  document.addEventListener("click", function (e) {
    var closeBtn = e.target.closest ? e.target.closest("[data-admin-modal-close]") : null;
    if (!closeBtn) return;
    var modal = closeBtn.closest(".admin-modal");
    if (modal && modal.id === MODAL_ID) onModalClose();
  });

  var form = getForm();
  if (form) {
    form.addEventListener("submit", function () {
      manualSubmit = true;
      stopTimer();
      syncEditors();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById(MODAL_ID);
    if (modal && !modal.hidden) onModalOpen();
  });
})();