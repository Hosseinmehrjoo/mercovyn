(function () {
    'use strict';

    function hidePageSkeleton() {
        var el = document.getElementById('page-skeleton');
        if (el) {
            el.classList.remove('show');
        }
        var legacy = document.getElementById('spinner');
        if (legacy) {
            legacy.classList.remove('show');
        }
    }

    if (document.readyState === 'complete') {
        hidePageSkeleton();
    } else {
        window.addEventListener('load', hidePageSkeleton, { once: true });
        document.addEventListener('DOMContentLoaded', function () {
            window.setTimeout(hidePageSkeleton, 120);
        }, { once: true });
    }

    window.setTimeout(hidePageSkeleton, 2500);
})();