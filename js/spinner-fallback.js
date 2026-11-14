/** @deprecated use page-skeleton.js */
(function () {
    'use strict';
    function hide() {
        var sk = document.getElementById('page-skeleton');
        var sp = document.getElementById('spinner');
        if (sk) sk.classList.remove('show');
        if (sp) sp.classList.remove('show');
    }
    window.addEventListener('load', hide, { once: true });
    window.setTimeout(hide, 4000);
})();