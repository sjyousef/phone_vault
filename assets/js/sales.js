/* ============================================================
   PhoneVault – sales.js (Sales / POS Page Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const escHtml = window.PV ? window.PV.escHtml : function(s){ return s || ''; };
    const debounce = window.PV ? window.PV.debounce : function(fn, d){ return fn; };

    /* ── POS: IMEI lookup & price fill ── */
    const posImei    = document.getElementById('posImei');
    const posPrice   = document.getElementById('posPrice');
    const posPhoneId = document.getElementById('posPhoneId');
    const posInfo    = document.getElementById('posPhoneInfo');
    const posTotalDisplay = document.getElementById('posTotalDisplay');

    if (posImei) {
        posImei.addEventListener('input', debounce(function () {
            const imei = this.value.trim();
            if (imei.length < 10) {
                if (posInfo) posInfo.innerHTML = '';
                return;
            }
            fetch('/Second_Hand_Phone_Store/sales.php?ajax=lookup&imei=' + encodeURIComponent(imei))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.found) {
                        if (posPrice)   {
                            posPrice.value = data.selling_price;
                            if (posTotalDisplay) {
                                posTotalDisplay.textContent = parseFloat(data.selling_price).toFixed(2);
                            }
                        }
                        if (posPhoneId) posPhoneId.value = data.id;
                        if (posInfo) {
                            posInfo.innerHTML =
                                '<div class="pv-alert pv-alert-success mt-2">' +
                                '<i class="fa-solid fa-circle-check"></i>' +
                                '<span><strong>' + escHtml(data.brand) + ' ' + escHtml(data.model) + '</strong> — ' +
                                escHtml(data.storage) + ' ' + escHtml(data.color) +
                                ' | Battery: ' + data.battery_health + '% | ' + escHtml(data.condition_grade) +
                                ' | ₱' + parseFloat(data.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2}) +
                                '</span></div>';
                        }
                    } else {
                        if (posPrice)   posPrice.value   = '';
                        if (posPhoneId) posPhoneId.value = '';
                        if (posTotalDisplay) posTotalDisplay.textContent = '0.00';
                        if (posInfo) {
                            posInfo.innerHTML = data.imei
                                ? '<div class="pv-alert pv-alert-danger mt-2"><i class="fa-solid fa-circle-xmark"></i><span>' + escHtml(data.message) + '</span></div>'
                                : '';
                        }
                    }
                })
                .catch(function () {});
        }, 350));

        // Auto trigger lookup if IMEI is prefilled via URL parameter
        if (posImei.value.trim().length >= 10) {
            posImei.dispatchEvent(new Event('input'));
        }
    }

    /* ── POS Total Display Sync ── */
    if (posPrice && posTotalDisplay) {
        posPrice.addEventListener('input', function () {
            const val = parseFloat(this.value || 0);
            posTotalDisplay.textContent = isNaN(val) ? '0.00' : val.toFixed(2);
        });
        if (posPrice.value) {
            posTotalDisplay.textContent = parseFloat(posPrice.value).toFixed(2);
        }
    }

    /* ── POS: Warranty duration → end date preview ── */
    const warrantyDays = document.getElementById('warrantyDays');
    const warrantyEnd  = document.getElementById('warrantyEndPreview');

    if (warrantyDays && warrantyEnd) {
        const updateWarrantyPreview = function () {
            const days = parseInt(warrantyDays.value, 10);
            if (!isNaN(days) && days > 0) {
                const d = new Date();
                d.setDate(d.getDate() + days);
                const formattedDate = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                warrantyEnd.innerHTML = '<div class="pv-warranty-preview-badge"><i class="fa-solid fa-shield-halved"></i> Warranty ends: <strong>' + formattedDate + '</strong></div>';
            } else {
                warrantyEnd.innerHTML = '';
            }
        };

        warrantyDays.addEventListener('input', updateWarrantyPreview);
        updateWarrantyPreview();
    }

    /* ── Form Submission Loading Overlay ── */
    const posForm = document.querySelector('form[action*="sales.php"]') || document.querySelector('.pv-pos-panel form') || document.querySelector('form');
    if (posForm) {
        posForm.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Recording sale & generating invoice...');
            }
        });
    }
});
