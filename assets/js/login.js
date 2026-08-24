/* ============================================================
   PhoneVault – login.js (Login Page Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ── Login Form Submission Loading Overlay ── */
    const loginForm = document.querySelector('.pv-login-card form');
    if (loginForm) {
        loginForm.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Signing in to PhoneVault...');
            }
        });
    }

    /* ── Click-to-Fill Demo Credentials ── */
    const demoCreds = document.querySelectorAll('.pv-demo-creds strong');
    const userInput = document.querySelector('input[name="username"]');
    const passInput = document.querySelector('input[name="password"]');

    demoCreds.forEach(function (el) {
        el.style.cursor = 'pointer';
        el.title = 'Click to fill this credential';
        el.addEventListener('click', function () {
            const text = this.textContent.trim();
            if (text === 'admin') {
                if (userInput) userInput.value = 'admin';
                if (passInput) passInput.value = 'admin123';
            } else if (text === 'admin123') {
                if (passInput) passInput.value = 'admin123';
            } else if (text === 'staff') {
                if (userInput) userInput.value = 'staff';
                if (passInput) passInput.value = 'staff123';
            } else if (text === 'staff123') {
                if (passInput) passInput.value = 'staff123';
            }
        });
    });

    /* ── Password Visibility Toggle ── */
    const togglePassBtn = document.getElementById('togglePasswordBtn');
    if (togglePassBtn && passInput) {
        togglePassBtn.addEventListener('click', function () {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
            }
        });
    }
});
