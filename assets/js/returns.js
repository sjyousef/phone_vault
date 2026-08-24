/* ============================================================
   PhoneVault – returns.js (Returns & Refunds Page Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ── Returns: Auto-fill phone_id & amount from sale dropdown ── */
    const claimSaleSelect = document.getElementById('claimSaleSelect');
    const claimPhoneId    = document.getElementById('claimPhoneId');
    const claimAmount     = document.getElementById('refundSaleAmount');

    if (claimSaleSelect) {
        claimSaleSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (claimPhoneId) claimPhoneId.value = opt.dataset.phoneId || '';
            if (claimAmount)  claimAmount.value  = opt.dataset.amount  || '';
        });
    }

    /* ── Process Refund Modal Trigger ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-refund-id]');
        if (!btn) return;
        
        const refundIdInput = document.getElementById('refundId');
        if (refundIdInput) refundIdInput.value = btn.dataset.refundId || '';
        
        const amtInput = document.getElementById('refundAmount');
        if (amtInput && btn.dataset.refundAmount) {
            amtInput.value = btn.dataset.refundAmount;
        }

        const modalEl = document.getElementById('refundModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
        }
    });

    /* ── Form Submission Loading Overlay ── */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                window.PV.showLoading('Processing return request...');
            }
        });
    });
});
