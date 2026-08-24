/* ============================================================
   PhoneVault – app.js (Core & Global UI Framework)
   ============================================================ */

(function () {
    'use strict';

    /* ── Global Namespace & Helpers ── */
    window.PV = window.PV || {};

    window.PV.debounce = function (fn, delay) {
        let t;
        return function () {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    };

    window.PV.escHtml = function (str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    window.PV.luhnCheck = function (num) {
        let sum = 0, alt = false;
        for (let i = num.length - 1; i >= 0; i--) {
            let n = parseInt(num[i], 10);
            if (alt) { n *= 2; if (n > 9) n -= 9; }
            sum += n;
            alt = !alt;
        }
        return sum % 10 === 0;
    };

    /* ── Dynamic Global Loading Overlay ── */
    function ensureLoadingOverlay() {
        let overlay = document.getElementById('pvLoadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'pvLoadingOverlay';
            overlay.className = 'pv-loading-overlay';
            overlay.innerHTML =
                '<div class="pv-loading-card">' +
                '  <div class="pv-loading-spinner">' +
                '    <i class="fa-solid fa-mobile-screen-button fa-bounce text-primary-pv"></i>' +
                '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
                '  </div>' +
                '  <div class="pv-loading-text" id="pvLoadingMsg">Loading, please wait...</div>' +
                '</div>';
            document.body.appendChild(overlay);
        }
        return overlay;
    }

    window.PV.showLoading = function (msg) {
        const overlay = ensureLoadingOverlay();
        const msgEl = document.getElementById('pvLoadingMsg');
        if (msgEl) msgEl.textContent = msg || 'Processing, please wait...';
        overlay.classList.add('show');
    };

    window.PV.hideLoading = function () {
        const overlay = document.getElementById('pvLoadingOverlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    };

    /* ── Theme Toggle ── */
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('pv_theme', theme);
        const icon = themeToggle ? themeToggle.querySelector('i') : null;
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    }

    const savedTheme = localStorage.getItem('pv_theme') || 'light';
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
    }

    /* ── Dropdown Toggle Handler (User Menu & Navbar Dropdowns) ── */
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.pv-user-pill, [data-bs-toggle="dropdown"], [data-pv-toggle="dropdown"]');
        
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            const parentDropdown = toggle.closest('.dropdown');
            if (parentDropdown) {
                const menu = parentDropdown.querySelector('.dropdown-menu');
                if (menu) {
                    const isOpen = menu.classList.contains('show');
                    
                    // Close all open dropdowns first
                    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                        m.classList.remove('show');
                    });
                    document.querySelectorAll('.pv-user-pill, [data-bs-toggle="dropdown"], [data-pv-toggle="dropdown"]').forEach(function (t) {
                        t.setAttribute('aria-expanded', 'false');
                    });

                    // If it was closed, open it now
                    if (!isOpen) {
                        menu.classList.add('show');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        } else if (!e.target.closest('.dropdown-menu')) {
            // Close any open dropdowns when clicking outside
            document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                m.classList.remove('show');
            });
            document.querySelectorAll('.pv-user-pill, [data-bs-toggle="dropdown"], [data-pv-toggle="dropdown"]').forEach(function (t) {
                t.setAttribute('aria-expanded', 'false');
            });
        }
    });

    /* ── Responsive Offcanvas Navigation Cleanup ── */
    const offcanvasLinks = document.querySelectorAll('#sidebarOffcanvas .pv-sidebar-link');
    offcanvasLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            const offcanvasEl = document.getElementById('sidebarOffcanvas');
            if (offcanvasEl && typeof bootstrap !== 'undefined') {
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (bsOffcanvas) bsOffcanvas.hide();
            }
        });
    });
})();
