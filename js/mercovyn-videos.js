/**
 * Mercovyn video carousel (from AnaDental) + subtitle tracks (SRT via VTT API).
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  var SUB_LABELS = window.MercovynVideoSubLabels || { en: 'EN', ru: 'RU', hy: 'HY', off: 'Off' };

  function defaultSubtitleLang() {
    if (window.MercovynVideoDefaultSub) return window.MercovynVideoDefaultSub;
    var pageLang = (document.documentElement.lang || 'en').toLowerCase();
    return ['en', 'ru', 'hy'].indexOf(pageLang) >= 0 ? pageLang : 'en';
  }

  function applySubtitles(video, lang) {
    var tracks = video.querySelectorAll('track');
    tracks.forEach(function (t) {
      if (t.track) t.track.mode = 'disabled';
    });
    if (!lang || lang === 'off') return;
    var track = video.querySelector('track[srclang="' + lang + '"]');
    if (track && track.track) track.track.mode = 'showing';
  }

  function attachSubtitleTracks(video, item) {
    var subs = item.subtitles || {};
    Object.keys(subs).forEach(function (lang) {
      if (!subs[lang]) return;
      var track = document.createElement('track');
      track.kind = 'subtitles';
      track.srclang = lang;
      track.label = SUB_LABELS[lang] || lang.toUpperCase();
      track.src = subs[lang];
      if (lang === (window.MercovynVideoDefaultSub || 'en')) track.default = true;
      video.appendChild(track);
    });
  }

  function formatVideoTime(sec) {
    if (!isFinite(sec) || sec < 0) sec = 0;
    var m = Math.floor(sec / 60);
    var s = Math.floor(sec % 60);
    return m + ':' + String(s).padStart(2, '0');
  }

  function applyVideoAspect(slide, video) {
    var w = video.videoWidth;
    var h = video.videoHeight;
    if (!w || !h) return;
    slide.classList.remove('is-portrait', 'is-landscape');
    slide.classList.add(h > w * 1.08 ? 'is-portrait' : 'is-landscape');
    slide.style.setProperty('--video-ar', w + ' / ' + h);
  }

  function normalizeStreamUrl(url) {
    if (!url || url.indexOf('uupload.ir') === -1) return url || '';
    if (/[?&]play(?:=|$)/.test(url)) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'play';
  }

  function ensureVideoSource(video) {
    if (video.dataset.loaded === '1') return Promise.resolve();
    var src = normalizeStreamUrl(video.getAttribute('data-src') || '');
    if (!src) return Promise.reject(new Error('no src'));
    video.setAttribute('data-src', src);
    return new Promise(function (resolve, reject) {
      function onMeta() {
        video.removeEventListener('loadedmetadata', onMeta);
        video.removeEventListener('error', onErr);
        video.dataset.loaded = '1';
        resolve();
      }
      function onErr() {
        video.removeEventListener('loadedmetadata', onMeta);
        video.removeEventListener('error', onErr);
        reject(new Error('load failed'));
      }
      video.addEventListener('loadedmetadata', onMeta);
      video.addEventListener('error', onErr);
      video.src = src;
      video.load();
    });
  }

  function pauseSlidePlayer(slide) {
    if (!slide) return;
    var video = slide.querySelector('video');
    if (video) {
      try { video.pause(); } catch (e) {}
    }
    slide.classList.remove('is-playing', 'is-buffering');
    var loading = slide.querySelector('.ana-video-loading');
    if (loading) loading.hidden = true;
  }

  function pauseAllVideos(root) {
    root.querySelectorAll('.video-slide').forEach(pauseSlidePlayer);
  }

  function toggleFullscreen(shell, video) {
    var el = shell || video;
    if (!el) return;
    if (document.fullscreenElement) {
      document.exitFullscreen().catch(function () {});
      return;
    }
    if (el.requestFullscreen) el.requestFullscreen().catch(function () {});
  }

  function buildSlideOriginal(item, index) {
    var slide = document.createElement('div');
    slide.className = 'video-slide is-landscape';
    slide.dataset.index = String(index);

    var shell = document.createElement('div');
    shell.className = 'ana-video-shell';

    if (item.poster_url) {
      var posterImg = document.createElement('img');
      posterImg.className = 'ana-video-poster';
      posterImg.src = item.poster_url;
      posterImg.alt = item.title || '';
      posterImg.loading = 'lazy';
      posterImg.decoding = 'async';
      shell.appendChild(posterImg);
    }

    var video = document.createElement('video');
    video.setAttribute('playsinline', '');
    video.setAttribute('preload', 'none');
    video.setAttribute('data-src', item.video_url || '');
    video.title = item.title || '';

    var loading = document.createElement('div');
    loading.className = 'ana-video-loading';
    loading.hidden = true;
    loading.setAttribute('aria-hidden', 'true');

    var playBtn = document.createElement('button');
    playBtn.type = 'button';
    playBtn.className = 'ana-video-play';
    playBtn.setAttribute('aria-label', 'پخش ویدیو');
    playBtn.innerHTML = '<span aria-hidden="true">▶</span>';

    var controls = document.createElement('div');
    controls.className = 'ana-video-controls';
    controls.hidden = true;

    var progress = document.createElement('input');
    progress.type = 'range';
    progress.className = 'ana-video-progress';
    progress.min = '0';
    progress.max = '1000';
    progress.value = '0';
    progress.setAttribute('aria-label', 'پیشرفت پخش');

    var row = document.createElement('div');
    row.className = 'ana-video-controls__row';

    var playSmall = document.createElement('button');
    playSmall.type = 'button';
    playSmall.className = 'ana-video-ctl';
    playSmall.setAttribute('aria-label', 'توقف / پخش');
    playSmall.textContent = '⏸';

    var timeEl = document.createElement('span');
    timeEl.className = 'ana-video-time';
    timeEl.textContent = '0:00 / 0:00';

    var muteBtn = document.createElement('button');
    muteBtn.type = 'button';
    muteBtn.className = 'ana-video-ctl';
    muteBtn.setAttribute('aria-label', 'صدا');
    muteBtn.textContent = '🔊';

    var speedBtn = document.createElement('button');
    speedBtn.type = 'button';
    speedBtn.className = 'ana-video-ctl ana-video-speed';
    speedBtn.setAttribute('aria-label', 'سرعت پخش');
    speedBtn.textContent = '1×';

    var fsBtn = document.createElement('button');
    fsBtn.type = 'button';
    fsBtn.className = 'ana-video-ctl';
    fsBtn.setAttribute('aria-label', 'تمام‌صفحه');
    fsBtn.textContent = '⛶';

    var pipBtn = null;
    if (document.pictureInPictureEnabled) {
      pipBtn = document.createElement('button');
      pipBtn.type = 'button';
      pipBtn.className = 'ana-video-ctl';
      pipBtn.setAttribute('aria-label', 'تصویر در تصویر');
      pipBtn.textContent = 'PiP';
    }

    row.appendChild(playSmall);
    row.appendChild(timeEl);
    row.appendChild(muteBtn);
    row.appendChild(speedBtn);
    if (pipBtn) row.appendChild(pipBtn);
    row.appendChild(fsBtn);
    controls.appendChild(progress);
    controls.appendChild(row);

    shell.appendChild(video);
    shell.appendChild(loading);
    shell.appendChild(playBtn);
    shell.appendChild(controls);
    slide.appendChild(shell);

    var speeds = [1, 1.25, 1.5, 0.75];
    var speedIdx = 0;
    var waitingTimer = null;

    function hideLoading() {
      if (waitingTimer) {
        clearTimeout(waitingTimer);
        waitingTimer = null;
      }
      loading.hidden = true;
      slide.classList.remove('is-buffering');
    }

    function showLoadingSoon() {
      if (waitingTimer) return;
      waitingTimer = window.setTimeout(function () {
        waitingTimer = null;
        if (video.paused || video.ended) return;
        if (video.readyState >= 3) return;
        loading.hidden = false;
        slide.classList.add('is-buffering');
      }, 280);
    }

    function syncTime() {
      if (!video.duration) {
        timeEl.textContent = formatVideoTime(video.currentTime) + ' / --:--';
        return;
      }
      progress.value = String(Math.round((video.currentTime / video.duration) * 1000));
      timeEl.textContent =
        formatVideoTime(video.currentTime) + ' / ' + formatVideoTime(video.duration);
    }

    function startPlayback() {
      if (!slide.classList.contains('center')) return;
      pauseAllVideos(slide.closest('.video-carousel') || document);
      loading.hidden = false;
      slide.classList.add('is-buffering');
      ensureVideoSource(video)
        .then(function () {
          applyVideoAspect(slide, video);
          return video.play();
        })
        .then(function () {
          slide.classList.add('is-playing');
          controls.hidden = false;
          var poster = shell.querySelector('.ana-video-poster');
          if (poster) poster.style.opacity = '0';
          hideLoading();
        })
        .catch(function (err) {
          hideLoading();
          console.warn('Video playback failed:', err || 'unknown');
        });
    }

    playBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      startPlayback();
    });

    playSmall.addEventListener('click', function (e) {
      e.stopPropagation();
      if (video.paused) video.play().catch(function () {});
      else video.pause();
    });

    progress.addEventListener('input', function (e) {
      e.stopPropagation();
      if (!video.duration) return;
      video.currentTime = (parseInt(progress.value, 10) / 1000) * video.duration;
    });

    muteBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      video.muted = !video.muted;
      muteBtn.textContent = video.muted ? '🔇' : '🔊';
    });

    speedBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      speedIdx = (speedIdx + 1) % speeds.length;
      video.playbackRate = speeds[speedIdx];
      speedBtn.textContent = speeds[speedIdx] + '×';
    });

    fsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleFullscreen(shell, video);
    });

    if (pipBtn) {
      pipBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (document.pictureInPictureElement === video) {
          document.exitPictureInPicture().catch(function () {});
        } else {
          video.requestPictureInPicture().catch(function () {});
        }
      });
    }

    video.addEventListener('play', function () {
      slide.classList.add('is-playing');
      playSmall.textContent = '⏸';
      controls.hidden = false;
      hideLoading();
    });
    video.addEventListener('pause', function () {
      playSmall.textContent = '▶';
      if (!video.ended) slide.classList.remove('is-playing');
    });
    video.addEventListener('ended', function () {
      slide.classList.remove('is-playing');
      playSmall.textContent = '▶';
    });
    video.addEventListener('timeupdate', function () {
      syncTime();
      if (!video.paused && !video.ended && video.readyState >= 2) {
        hideLoading();
      }
    });
    video.addEventListener('loadedmetadata', function () {
      applyVideoAspect(slide, video);
      syncTime();
    });
    video.addEventListener('waiting', showLoadingSoon);
    video.addEventListener('playing', hideLoading);
    video.addEventListener('canplay', hideLoading);
    video.addEventListener('canplaythrough', hideLoading);

    shell.addEventListener('dblclick', function (e) {
      e.stopPropagation();
      if (!slide.classList.contains('center')) return;
      toggleFullscreen(shell, video);
    });

    return slide;
  }

  function initVideoCarousel(carousel, items) {
    var track = carousel.querySelector('.video-carousel-track');
    if (!track) return;
    track.innerHTML = '';

    if (!items.length) {
      track.innerHTML = '<p class="video-carousel-empty">ویدیویی برای نمایش ثبت نشده است.</p>';
      return;
    }

    var slides = items.map(function (item, i) {
      return buildSlide(item, i);
    });
    slides.forEach(function (s) { track.appendChild(s); });

    var nextButton = carousel.querySelector('.next');
    var prevButton = carousel.querySelector('.prev');
    var currentIndex = 0;
    var isAnimating = false;

    function updateCarousel() {
      if (isAnimating) return;
      isAnimating = true;
      pauseAllVideos(carousel);
      var total = slides.length;

      slides.forEach(function (slide, index) {
        var position = (index - currentIndex + total) % total;
        slide.classList.remove('center', 'left', 'right');

        if (position === 0) {
          slide.classList.add('center');
        } else if (position === 1) {
          slide.classList.add('right');
        } else if (position === total - 1) {
          slide.classList.add('left');
        } else {
          slide.style.opacity = '0';
          slide.style.pointerEvents = 'none';
          slide.style.transform = 'translateX(0) scale(.5)';
          slide.style.zIndex = '0';
          return;
        }

        slide.style.opacity = '';
        slide.style.pointerEvents = '';
        slide.style.transform = '';
        slide.style.zIndex = '';
      });

      setTimeout(function () { isAnimating = false; }, 650);
    }

    function nextVideo() {
      currentIndex = (currentIndex + 1) % slides.length;
      updateCarousel();
    }
    function previousVideo() {
      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
      updateCarousel();
    }

    if (nextButton) nextButton.addEventListener('click', nextVideo);
    if (prevButton) prevButton.addEventListener('click', previousVideo);

    slides.forEach(function (slide, index) {
      slide.addEventListener('click', function () {
        if (slide.classList.contains('center')) return;
        currentIndex = index;
        updateCarousel();
      });
    });

    var touchStartX = 0;
    carousel.addEventListener('touchstart', function (event) {
      touchStartX = event.changedTouches[0].screenX;
    }, { passive: true });
    carousel.addEventListener('touchend', function (event) {
      var diff = touchStartX - event.changedTouches[0].screenX;
      if (Math.abs(diff) < 50) return;
      if (diff > 0) nextVideo();
      else previousVideo();
    }, { passive: true });

    updateCarousel();
  }

  function buildSlide(item, index) {
    var slide = buildSlideOriginal(item, index);
    var video = slide.querySelector('video');
    var controls = slide.querySelector('.ana-video-controls__row');
    if (!video || !controls) return slide;
    attachSubtitleTracks(video, item);
    var keys = Object.keys(item.subtitles || {});
    if (!keys.length) return slide;
    var sel = document.createElement('select');
    sel.className = 'ana-video-ctl ana-video-subtitles';
    sel.setAttribute('aria-label', 'Subtitles');
    sel.innerHTML = '<option value="off">' + (SUB_LABELS.off || 'Off') + '</option>';
    keys.forEach(function (k) {
      var opt = document.createElement('option');
      opt.value = k;
      opt.textContent = SUB_LABELS[k] || k.toUpperCase();
      sel.appendChild(opt);
    });
    var def = defaultSubtitleLang();
    if (keys.indexOf(def) >= 0) sel.value = def;
    else sel.value = keys[0];
    sel.addEventListener('change', function (e) {
      e.stopPropagation();
      applySubtitles(video, sel.value);
    });
    sel.addEventListener('click', function (e) { e.stopPropagation(); });
    controls.insertBefore(sel, controls.children[3] || null);
    video.addEventListener('loadedmetadata', function () {
      applySubtitles(video, sel.value);
    });
    return slide;
  }

  function fetchVideoItems(placement) {
    var key = placement || 'home';
    if (window.MercovynVideoItems && window.MercovynVideoItems[key]) {
      return Promise.resolve(window.MercovynVideoItems[key]);
    }
    var lang = document.documentElement.lang || 'en';
    var qs = '?lang=' + encodeURIComponent(lang) + '&placement=' + encodeURIComponent(key);
    return fetch('/backend/api/videos.php' + qs, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) { return (data && data.items) || []; });
  }

  function loadVideoSection(section) {
    if (!section) return;
    var placement = section.getAttribute('data-video-placement') || 'home';
    var carousel = section.querySelector('.video-carousel');
    if (!carousel) return;
    fetchVideoItems(placement).then(function (items) {
      items = Array.isArray(items) ? items : [];
      if (!items.length) {
        section.hidden = true;
        section.setAttribute('aria-hidden', 'true');
        return;
      }
      section.hidden = false;
      section.removeAttribute('aria-hidden');
      initVideoCarousel(carousel, items);
    }).catch(function () {
      section.hidden = true;
      section.setAttribute('aria-hidden', 'true');
    });
  }

  function loadVideos() {
    var sections = document.querySelectorAll('[data-video-placement]');
    if (!sections.length) return;
    sections.forEach(loadVideoSection);
  }

  ready(loadVideos);
})();
