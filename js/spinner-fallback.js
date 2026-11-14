(function () {
    'use strict';
    function hideSpinner() {
        var el = document.getElementById('spinner');
        if (el) {
            el.classList.remove('show');
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideSpinner);
    } else {
        hideSpinner();
    }
    window.setTimeout(hideSpinner, 2500);
})();