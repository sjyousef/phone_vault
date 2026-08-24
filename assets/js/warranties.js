/* ============================================================
   PhoneVault – warranties.js (Warranty Management Page Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const escHtml = window.PV ? window.PV.escHtml : function(s){ return s || ''; };

    /* ── Warranty Checker (warranties.php) ── */
    const warrantySearchBtn   = document.getElementById('warrantySearchBtn');
    const warrantySearchInput = document.getElementById('warrantySearchInput');
    const warrantyResult      = document.getElementById('warrantyResult');

    if (warrantySearchBtn && warrantySearchInput && warrantyResult) {
        const performSearch = function () {
            const q = warrantySearchInput.value.trim();
            if (!q) return;
            warrantyResult.innerHTML = '<div class="text-center py-3"><i class="fa-solid fa-spinner fa-spin me-2"></i> Searching warranty database...</div>';
            warrantyResult.classList.add('show');
            
            fetch('/Second_Hand_Phone_Store/warranties.php?ajax=check&q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.found) {
                        const today = new Date();
                        const end   = new Date(data.warranty_end_date);
                        const diff  = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
                        const total = parseInt(data.warranty_duration_days, 10) || 30;
                        const pct   = Math.max(0, Math.min(100, Math.round((diff / total) * 100)));
                        const expired = diff <= 0;
                        
                        warrantyResult.innerHTML =
                            '<div class="row g-3">' +
                            '<div class="col-md-6"><div class="pv-stat-card"><div class="pv-stat-icon primary"><i class="fa-solid fa-mobile-screen"></i></div><div><div class="pv-stat-value" style="font-size:1.25rem">' + escHtml(data.brand) + ' ' + escHtml(data.model) + '</div><div class="pv-stat-label">Device</div></div></div></div>' +
                            '<div class="col-md-6"><div class="pv-stat-card"><div class="pv-stat-icon ' + (expired ? 'danger' : 'success') + '"><i class="fa-solid fa-shield-halved"></i></div><div><div class="pv-stat-value" style="font-size:1.25rem">' + (expired ? 'Expired' : diff + ' days remaining') + '</div><div class="pv-stat-label">Status</div></div></div></div>' +
                            '</div>' +
                            '<div class="mt-3"><div class="d-flex justify-content-between mb-1"><small>Invoice: <strong>' + escHtml(data.invoice_no) + '</strong></small><small>Customer: <strong>' + escHtml(data.customer_name) + '</strong></small></div>' +
                            '<div class="pv-warranty-bar"><div class="pv-warranty-fill ' + (expired ? 'expired' : '') + '" style="width:' + pct + '%"></div></div>' +
                            '<div class="d-flex justify-content-between mt-1"><small class="text-muted">Sold: ' + escHtml(data.created_at) + '</small><small class="text-muted">Ends: ' + escHtml(data.warranty_end_date) + '</small></div></div>';
                    } else {
                        warrantyResult.innerHTML = '<div class="pv-alert pv-alert-danger mb-0"><i class="fa-solid fa-circle-xmark"></i><span>No warranty record found for "<strong>' + escHtml(q) + '</strong>".</span></div>';
                    }
                })
                .catch(function () {
                    warrantyResult.innerHTML = '<div class="pv-alert pv-alert-danger mb-0"><i class="fa-solid fa-circle-xmark"></i><span>Search failed. Please try again.</span></div>';
                });
        };

        warrantySearchBtn.addEventListener('click', performSearch);
        warrantySearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }

    /* ── Warranty Detail Modal ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-warranty-sale]');
        if (!btn) return;
        
        const saleId = btn.dataset.warrantySale;
        const body   = document.getElementById('warrantyModalBody');
        
        if (body) {
            body.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        }
        
        const modalEl = document.getElementById('warrantyModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
        }

        fetch('/Second_Hand_Phone_Store/warranties.php?ajax=detail&sale_id=' + saleId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!body) return;
                if (!data.found) {
                    body.innerHTML = '<p class="text-center text-muted">Warranty details not found.</p>';
                    return;
                }
                const today = new Date();
                const end   = new Date(data.warranty_end_date);
                const diff  = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
                const total = parseInt(data.warranty_duration_days, 10) || 30;
                const pct   = Math.max(0, Math.min(100, Math.round((diff / total) * 100)));
                const expired = diff <= 0;
                
                body.innerHTML =
                    '<dl class="row mb-2">' +
                    '<dt class="col-5">Invoice</dt><dd class="col-7 font-mono">' + escHtml(data.invoice_no) + '</dd>' +
                    '<dt class="col-5">Customer</dt><dd class="col-7">' + escHtml(data.customer_name) + '</dd>' +
                    '<dt class="col-5">Phone</dt><dd class="col-7">' + escHtml(data.brand) + ' ' + escHtml(data.model) + '</dd>' +
                    '<dt class="col-5">IMEI</dt><dd class="col-7 font-mono">' + escHtml(data.imei) + '</dd>' +
                    '<dt class="col-5">Sold On</dt><dd class="col-7">' + escHtml(data.created_at) + '</dd>' +
                    '<dt class="col-5">Warranty End</dt><dd class="col-7">' + escHtml(data.warranty_end_date) + '</dd>' +
                    '<dt class="col-5">Status</dt><dd class="col-7"><span class="pv-status ' + (expired ? 'pv-status-rejected' : 'pv-status-approved') + '">' + (expired ? 'Expired' : diff + ' days left') + '</span></dd>' +
                    '</dl>' +
                    '<div class="pv-warranty-bar"><div class="pv-warranty-fill ' + (expired ? 'expired' : '') + '" style="width:' + pct + '%"></div></div>';
            })
            .catch(function () {
                if (body) body.innerHTML = '<p class="text-center text-danger">Failed to load warranty details.</p>';
            });
    });
});
