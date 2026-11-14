(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('motion-off');
        var nodes = document.querySelectorAll('.wow');
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].classList.remove('wow');
        }
        return;
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.defer = true;
            s.onload = resolve;
            s.onerror = reject;
            document.body.appendChild(s);
        });
    }

    function loadCss(href) {
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = href;
        document.head.appendChild(l);
    }

    function boot() {
        loadCss('/lib/animate/animate.min.css');
        Promise.all([
            loadScript('/lib/wow/wow.min.js'),
            loadScript('/lib/easing/easing.min.js'),
        ])
            .then(function () {
                if (typeof window.WOW !== 'undefined') {
                    new window.WOW().init();
                }
            })
            .catch(function () {});
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(boot, { timeout: 2200 });
    } else {
        window.setTimeout(boot, 600);
    }
})();