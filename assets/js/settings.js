/* ============================================================
   PhoneVault – settings.js (Settings Page Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ── Store Settings Form Submit Loading ── */
    const storeSettingsForm = id('storeSettingsForm');
    if (storeSettingsForm) {
        storeSettingsForm.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Saving store preferences...');
            }
        });
    }

    /* ── Password Change Form Validation & Loading ── */
    const changePasswordForm = id('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            const newPass     = id('newPassword') ? id('newPassword').value : '';
            const confirmPass = id('confirmPassword') ? id('confirmPassword').value : '';
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New password and confirm password do not match.');
                return false;
            }
            
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Updating security password...');
            }
        });
    }

    /* ── Add User Form Submission Loading ── */
    const addUserForm = document.querySelector('form[action*="settings.php"] input[name="action"][value="add_user"]')?.closest('form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Creating new user account...');
            }
        });
    }

    function id(s) {
        return document.getElementById(s);
    }
});
