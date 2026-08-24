/* ============================================================
   PhoneVault – phones.js (JavaScript-Driven Phone CRUD Engine)
   ============================================================ */

/**
 * Display dynamic alert notification banner
 */
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;

    const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const alertHtml = `
        <div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="fa-solid ${iconClass} me-2 fs-5"></i>
            <div>${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    alertContainer.innerHTML = alertHtml;

    setTimeout(() => {
        const bsAlert = alertContainer.querySelector('.alert');
        if (bsAlert) {
            bsAlert.classList.remove('show');
            setTimeout(() => { alertContainer.innerHTML = ''; }, 300);
        }
    }, 4000);
}

/**
 * Step 4 & Step 19: Load all phone records using Fetch API (async/await)
 */
async function loadPhones() {
    const table = document.getElementById('phoneTable');
    if (!table) return;

    try {
        const response = await fetch('api/get_phones.php');
        if (!response.ok) throw new Error('Network response was not ok');
        const phones = await response.json();

        table.innerHTML = '';

        if (!Array.isArray(phones) || phones.length === 0) {
            table.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fa-solid fa-mobile-screen-button mb-2 d-block fs-3"></i>
                        No phone inventory records found. Click <strong>"Add New Phone"</strong> to add stock.
                    </td>
                </tr>
            `;
            return;
        }

        phones.forEach(phone => {
            const statusBadge = phone.status === 'Available' 
                ? '<span class="pv-status pv-status-approved">Available</span>'
                : (phone.status === 'Sold' 
                    ? '<span class="pv-status pv-status-pending">Sold</span>'
                    : '<span class="pv-status pv-status-rejected">' + escapeHtml(phone.status) + '</span>');

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="font-mono text-muted">#${phone.id}</td>
                <td>
                    <div class="fw-bold">${escapeHtml(phone.brand)} ${escapeHtml(phone.model)}</div>
                    <div class="small text-muted">${escapeHtml(phone.storage || '')} • ${escapeHtml(phone.color || '')}</div>
                </td>
                <td class="font-mono text-muted fs-7">${escapeHtml(phone.imei)}</td>
                <td><span class="badge bg-secondary-subtle text-body px-2 py-1">${escapeHtml(phone.condition_grade)}</span></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-battery-three-quarters ${phone.battery_health >= 80 ? 'text-success' : 'text-warning'}"></i>
                        <span>${phone.battery_health}%</span>
                    </div>
                </td>
                <td class="fw-bold text-primary-pv">₱${parseFloat(phone.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editPhone(${phone.id})" title="Edit Device">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deletePhone(${phone.id})" title="Delete Device">
                            <i class="fa-solid fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </td>
            `;
            table.appendChild(row);
        });

        // Trigger search filter refresh if input has text
        const searchInput = document.getElementById('search');
        if (searchInput && searchInput.value) {
            searchInput.dispatchEvent(new Event('input'));
        }
    } catch (error) {
        console.error('Error fetching phones:', error);
        table.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    Failed to load phone inventory. Please check database connection.
                </td>
            </tr>
        `;
    }
}

/**
 * Step 14: Prepare modal for adding a new phone
 */
function newPhone() {
    const phoneForm = document.getElementById('phoneForm');
    if (phoneForm) phoneForm.reset();

    const idInput = document.getElementById('phoneId');
    if (idInput) idInput.value = '';

    const batteryInput = document.getElementById('batteryHealth');
    if (batteryInput) batteryInput.value = '100';

    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-mobile-screen-button me-2"></i>Add New Phone Device';

    const modalEl = document.getElementById('phoneModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }
}

/**
 * Step 10: Read one phone record for editing & populate Bootstrap modal
 */
async function editPhone(id) {
    try {
        const response = await fetch(`api/get_phone.php?id=${id}`);
        if (!response.ok) throw new Error('Failed to fetch phone details');

        const phone = await response.json();

        if (phone.status === 'error') {
            showAlert('danger', phone.message);
            return;
        }

        document.getElementById('phoneId').value         = phone.id;
        document.getElementById('brand').value           = phone.brand;
        document.getElementById('model').value           = phone.model;
        document.getElementById('imei').value            = phone.imei;
        document.getElementById('storage').value          = phone.storage || '128GB';
        document.getElementById('color').value            = phone.color || 'Black';
        document.getElementById('batteryHealth').value    = phone.battery_health || '100';
        document.getElementById('conditionGrade').value   = phone.condition_grade || 'Grade A';
        document.getElementById('costPrice').value        = phone.cost_price || '0.00';
        document.getElementById('sellingPrice').value     = phone.selling_price || '0.00';
        document.getElementById('status').value           = phone.status || 'Available';

        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Edit Phone Device';

        const modalEl = document.getElementById('phoneModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    } catch (error) {
        console.error('Error loading phone record:', error);
        showAlert('danger', 'Unable to fetch phone details.');
    }
}

/**
 * Step 13: Delete a record with confirmation prompt
 */
async function deletePhone(id) {
    const answer = confirm('Are you sure you want to delete this phone device from inventory?');
    if (!answer) return;

    try {
        const response = await fetch('api/delete_phone.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.status === 'success') {
            showAlert('success', result.message || 'Phone record deleted successfully.');
            loadPhones();
        } else {
            showAlert('danger', result.message || 'Unable to delete phone record.');
        }
    } catch (error) {
        console.error('Error deleting phone:', error);
        showAlert('danger', 'API error occurred while deleting record.');
    }
}

/**
 * Helper to escape HTML characters
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;');
}

/* ── DOM Content Loaded Event Initializer ── */
document.addEventListener('DOMContentLoaded', function () {
    // Initial Load
    loadPhones();

    // Step 7 & Step 11 & Step 12 & Step 16: Form Submission Handler (Create / Update)
    const phoneForm = document.getElementById('phoneForm');
    if (phoneForm) {
        phoneForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            // Step 16: Client-Side Validation
            const brand          = document.getElementById('brand').value.trim();
            const model          = document.getElementById('model').value.trim();
            const imei           = document.getElementById('imei').value.trim();
            const storage        = document.getElementById('storage').value.trim();
            const color          = document.getElementById('color').value.trim();
            const batteryHealth  = parseInt(document.getElementById('batteryHealth').value, 10) || 100;
            const conditionGrade = document.getElementById('conditionGrade').value;
            const costPrice      = parseFloat(document.getElementById('costPrice').value) || 0;
            const sellingPrice   = parseFloat(document.getElementById('sellingPrice').value) || 0;
            const status         = document.getElementById('status').value;
            const id             = document.getElementById('phoneId').value;

            if (brand === '') {
                showAlert('danger', 'Brand is required.');
                document.getElementById('brand').focus();
                return;
            }

            if (model === '') {
                showAlert('danger', 'Model name is required.');
                document.getElementById('model').focus();
                return;
            }

            if (imei === '') {
                showAlert('danger', 'IMEI is required.');
                document.getElementById('imei').focus();
                return;
            }

            if (sellingPrice <= 0) {
                showAlert('danger', 'Selling price must be greater than 0.');
                document.getElementById('sellingPrice').focus();
                return;
            }

            const data = {
                brand, model, imei, storage, color,
                battery_health: batteryHealth,
                condition_grade: conditionGrade,
                cost_price: costPrice,
                selling_price: sellingPrice,
                status
            };

            let endpoint = 'api/add_phone.php';

            // Step 11: Determine ADD vs UPDATE
            if (id !== '') {
                data.id = id;
                endpoint = 'api/update_phone.php';
            }

            try {
                // Step 8 & Step 12: Send JSON to PHP API via Fetch API
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Hide Modal
                    const modalEl = document.getElementById('phoneModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                    }

                    // Reset Form
                    phoneForm.reset();
                    document.getElementById('phoneId').value = '';

                    // Display Feedback Notification & Refresh Table
                    showAlert('success', result.message || 'Operation completed successfully.');
                    loadPhones();
                } else {
                    showAlert('danger', result.message || 'An error occurred during submission.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                showAlert('danger', 'Network error or invalid server response.');
            }
        });
    }

    // Step 15: Real-Time Search Filter
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value.toLowerCase();
            const rows = document.querySelectorAll('#phoneTable tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(keyword) ? '' : 'none';
            });
        });
    }
});
