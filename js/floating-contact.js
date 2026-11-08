(function () {
    'use strict';

    function initFabContact() {
        var root = document.getElementById('fabContact');
        var toggle = document.getElementById('fabContactToggle');
        var backdrop = document.getElementById('fabContactBackdrop');
        var actions = document.getElementById('fabContactActions');
        if (!root || !toggle) {
            return;
        }

        function setOpen(open) {
            root.dataset.open = open ? 'true' : 'false';
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (actions) {
                actions.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
            if (backdrop) {
                backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
            var label = open
                ? toggle.getAttribute('data-label-close')
                : toggle.getAttribute('data-label-open');
            if (label) {
                toggle.setAttribute('aria-label', label);
            }
        }

        function isOpen() {
            return root.dataset.open === 'true';
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!isOpen());
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setOpen(false);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                setOpen(false);
            }
        });

        document.addEventListener('click', function (e) {
            if (!isOpen()) {
                return;
            }
            if (root.contains(e.target)) {
                return;
            }
            setOpen(false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFabContact);
    } else {
        initFabContact();
    }
})();
