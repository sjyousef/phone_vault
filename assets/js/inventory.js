/* ============================================================
   PhoneVault – inventory.js (3-Level Folder Drilldown Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const escHtml = window.PV ? window.PV.escHtml : function(s){ return s || ''; };
    const luhnCheck = window.PV ? window.PV.luhnCheck : function(){ return true; };

    /* ── 3-Level Drilldown Navigation State ── */
    let currentBrand = '';
    let currentModel = '';
    let currentLevel = 1;

    const level1 = document.getElementById('level1BrandFolders');
    const level2 = document.getElementById('level2ModelVariants');
    const level3 = document.getElementById('level3DeviceUnits');

    const bcRoot  = document.getElementById('bcRoot');
    const bcBrand = document.getElementById('bcBrand');
    const bcModel = document.getElementById('bcModel');

    const btnBackToBrands = document.getElementById('btnBackToBrands');
    const btnBackToModels = document.getElementById('btnBackToModels');

    function goToLevel1() {
        currentLevel = 1;
        currentBrand = '';
        currentModel = '';

        if (level1) level1.classList.remove('d-none');
        if (level2) level2.classList.add('d-none');
        if (level3) level3.classList.add('d-none');

        if (bcBrand) bcBrand.classList.add('d-none');
        if (bcModel) bcModel.classList.add('d-none');
    }

    function goToLevel2(brandName) {
        currentLevel = 2;
        currentBrand = brandName;
        currentModel = '';

        if (level1) level1.classList.add('d-none');
        if (level2) level2.classList.remove('d-none');
        if (level3) level3.classList.add('d-none');

        const titleEl = document.getElementById('currentBrandTitle');
        if (titleEl) titleEl.textContent = brandName;

        if (bcBrand) {
            bcBrand.textContent = brandName;
            bcBrand.classList.remove('d-none');
        }
        if (bcModel) bcModel.classList.add('d-none');

        // Filter Level 2 Model Cards
        document.querySelectorAll('[data-model-folder]').forEach(function (card) {
            const bParent = card.getAttribute('data-brand-parent');
            if (bParent === brandName) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    }

    function goToLevel3(brandName, modelName) {
        currentLevel = 3;
        currentBrand = brandName;
        currentModel = modelName;

        if (level1) level1.classList.add('d-none');
        if (level2) level2.classList.add('d-none');
        if (level3) level3.classList.remove('d-none');

        const bTitleEl = document.getElementById('unitDetailBrandTitle');
        const mTitleEl = document.getElementById('unitDetailModelTitle');
        if (bTitleEl) bTitleEl.textContent = brandName;
        if (mTitleEl) mTitleEl.textContent = modelName;

        if (bcBrand) {
            bcBrand.textContent = brandName;
            bcBrand.classList.remove('d-none');
        }
        if (bcModel) {
            bcModel.textContent = modelName;
            bcModel.classList.remove('d-none');
        }

        // Filter Level 3 Unit Table Rows
        filterLevel3Units('all');
    }

    function filterLevel3Units(statusFilter) {
        document.querySelectorAll('[data-unit-row]').forEach(function (row) {
            const bName = row.getAttribute('data-brand');
            const mName = row.getAttribute('data-model');
            const status = row.getAttribute('data-status');

            const matchModel = (bName === currentBrand && mName === currentModel);
            const matchStatus = (statusFilter === 'all' || status === statusFilter);

            if (matchModel && matchStatus) {
                row.classList.remove('d-none');
            } else {
                row.classList.add('d-none');
            }
        });
    }

    /* ── Click Handlers for Drilldown Folders & Breadcrumbs ── */

    // Level 1 Brand Folder Click
    document.addEventListener('click', function (e) {
        const folder = e.target.closest('[data-open-brand]');
        if (folder) {
            const brand = folder.getAttribute('data-open-brand');
            goToLevel2(brand);
        }
    });

    // Level 2 Model Card Click
    document.addEventListener('click', function (e) {
        const modelCard = e.target.closest('[data-open-model]');
        if (modelCard) {
            const brand = modelCard.getAttribute('data-parent-brand');
            const model = modelCard.getAttribute('data-open-model');
            goToLevel3(brand, model);
        }
    });

    // Breadcrumb Actions
    if (bcRoot) {
        bcRoot.addEventListener('click', function (e) {
            e.preventDefault();
            goToLevel1();
        });
    }
    if (bcBrand) {
        bcBrand.addEventListener('click', function (e) {
            e.preventDefault();
            if (currentBrand) goToLevel2(currentBrand);
        });
    }

    // Back Buttons
    if (btnBackToBrands) {
        btnBackToBrands.addEventListener('click', function () {
            goToLevel1();
        });
    }
    if (btnBackToModels) {
        btnBackToModels.addEventListener('click', function () {
            if (currentBrand) goToLevel2(currentBrand);
            else goToLevel1();
        });
    }

    // Status Filter Pills in Level 3
    const unitStatusBtns = document.querySelectorAll('#unitStatusFilterGroup [data-unit-status]');
    unitStatusBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            unitStatusBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            const status = this.getAttribute('data-unit-status');
            filterLevel3Units(status);
        });
    });

    /* ── Inspect Full Device Specs Details Modal ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-inspect-phone]');
        if (!btn) return;

        const d = btn.dataset;
        const setTxt = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '-';
        };

        setTxt('inspBrandModel', d.brand + ' ' + d.model);
        setTxt('inspImei', 'IMEI: ' + d.imei);
        setTxt('inspStorage', d.storage);
        setTxt('inspColor', d.color);
        setTxt('inspBattery', d.battery + '%');
        setTxt('inspCost', (window.PV_ANALYTICS ? window.PV_ANALYTICS.currencySymbol : '₱') + d.cost);
        setTxt('inspPrice', (window.PV_ANALYTICS ? window.PV_ANALYTICS.currencySymbol : '₱') + d.price);
        setTxt('inspDate', d.date);

        const statusEl = document.getElementById('inspStatus');
        if (statusEl) {
            const sClass = 'pv-status-' + (d.status || '').toLowerCase();
            statusEl.innerHTML = '<span class="pv-status ' + sClass + '">' + escHtml(d.status) + '</span>';
        }

        const gradeEl = document.getElementById('inspGrade');
        if (gradeEl) {
            const gClass = d.grade === 'Grade A' ? 'pv-grade-a' : (d.grade === 'Grade B' ? 'pv-grade-b' : 'pv-grade-c');
            gradeEl.innerHTML = '<span class="pv-grade ' + gClass + '">' + escHtml(d.grade) + '</span>';
        }

        const modalEl = document.getElementById('inspectPhoneModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
        }
    });

    /* ── Live Inventory Search Across All 3 Levels ── */
    const inventorySearch = document.getElementById('inventorySearch');
    if (inventorySearch) {
        inventorySearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();

            if (q.length === 0) {
                if (currentLevel === 1) goToLevel1();
                return;
            }

            // Auto switch to Level 2 or Level 3 when searching
            document.querySelectorAll('#level1BrandFolders [data-search]').forEach(function (folder) {
                const text = (folder.getAttribute('data-search') || '').toLowerCase();
                const col = folder.closest('.col-12');
                if (col) col.style.display = text.includes(q) ? '' : 'none';
            });

            document.querySelectorAll('[data-model-folder]').forEach(function (card) {
                const text = (card.getAttribute('data-search') || '').toLowerCase();
                if (text.includes(q)) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });

            document.querySelectorAll('[data-unit-row]').forEach(function (row) {
                const text = (row.getAttribute('data-search') || '').toLowerCase();
                if (text.includes(q)) {
                    row.classList.remove('d-none');
                } else {
                    row.classList.add('d-none');
                }
            });
        });
    }

    /* ── IMEI Live Validation ── */
    const imeiInput = document.getElementById('phoneImei');
    const imeiMsg   = document.getElementById('imeiValidation');

    if (imeiInput && imeiMsg) {
        imeiInput.addEventListener('input', function () {
            const val = this.value.replace(/\D/g, '');
            this.value = val;
            if (val.length === 0) {
                imeiMsg.textContent = '';
                imeiMsg.className = 'form-text';
            } else if (val.length !== 15) {
                imeiMsg.textContent = 'IMEI must be exactly 15 digits.';
                imeiMsg.className = 'form-text text-danger';
            } else if (!luhnCheck(val)) {
                imeiMsg.textContent = 'Invalid IMEI (Luhn check failed).';
                imeiMsg.className = 'form-text text-danger';
            } else {
                imeiMsg.textContent = '✓ Valid IMEI';
                imeiMsg.className = 'form-text text-success';
            }
        });
    }

    /* ── Edit Phone Modal Population ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-edit-phone]');
        if (!btn) return;
        const d = btn.dataset;

        const titleEl = document.getElementById('phoneModalTitle');
        if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Edit Phone';

        const actionEl = document.getElementById('phoneAction');
        if (actionEl) actionEl.value = 'edit';

        if (document.getElementById('phoneId'))      document.getElementById('phoneId').value      = d.id || '';
        if (document.getElementById('phoneBrand'))   document.getElementById('phoneBrand').value   = d.brand || '';
        if (document.getElementById('phoneModel'))   document.getElementById('phoneModel').value   = d.model || '';
        if (document.getElementById('phoneImei'))    document.getElementById('phoneImei').value    = d.imei || '';
        if (document.getElementById('phoneStorage')) document.getElementById('phoneStorage').value = d.storage || '';
        if (document.getElementById('phoneColor'))   document.getElementById('phoneColor').value   = d.color || '';
        if (document.getElementById('phoneBattery')) document.getElementById('phoneBattery').value = d.battery || '';
        if (document.getElementById('phoneGrade'))   document.getElementById('phoneGrade').value   = d.grade || '';
        if (document.getElementById('phoneStatus'))  document.getElementById('phoneStatus').value  = d.status || '';
        if (document.getElementById('phoneCost'))    document.getElementById('phoneCost').value    = d.cost || '';
        if (document.getElementById('phonePrice'))   document.getElementById('phonePrice').value   = d.price || '';

        const modalEl = document.getElementById('phoneModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
        }
    });

    /* ── Add Phone Modal Reset ── */
    const addPhoneBtn = document.getElementById('addPhoneBtn');
    if (addPhoneBtn) {
        addPhoneBtn.addEventListener('click', function () {
            const titleEl = document.getElementById('phoneModalTitle');
            if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-mobile-screen me-2"></i>Add Phone';

            const actionEl = document.getElementById('phoneAction');
            if (actionEl) actionEl.value = 'add';

            const formEl = document.getElementById('phoneForm');
            if (formEl) formEl.reset();

            if (document.getElementById('phoneId')) document.getElementById('phoneId').value = '';
            if (imeiMsg) { imeiMsg.textContent = ''; imeiMsg.className = 'form-text'; }

            const modalEl = document.getElementById('phoneModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modalEl).show();
            }
        });
    }

    /* ── Form Submission Loading Overlay ── */
    const phoneForm = document.getElementById('phoneForm');
    if (phoneForm) {
        phoneForm.addEventListener('submit', function () {
            if (window.PV && window.PV.showLoading) {
                const action = document.getElementById('phoneAction') ? document.getElementById('phoneAction').value : 'save';
                window.PV.showLoading(action === 'edit' ? 'Updating phone details...' : 'Adding phone to inventory...');
            }
        });
    }
});
