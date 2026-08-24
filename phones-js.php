<?php
/* ============================================================
   PhoneVault – phones-js.php (JavaScript-Driven Phone CRUD Page)
   ============================================================ */

require_once __DIR__ . '/config/auth.php';
requireLogin();

$pageTitle  = 'Phones JS Management';
$pageScript = 'phones.js';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="pv-main">
    <div class="container-fluid">
        <!-- Page Header Card -->
        <div class="pv-page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="pv-page-title mb-1">
                    <i class="fa-solid fa-mobile-screen text-primary-pv me-2"></i>Phone Inventory (JS-Driven CRUD)
                </h1>
                <p class="pv-page-subtitle text-muted mb-0">
                    Manage store phone stock asynchronously using Fetch API, Async/Await, Prepared Statements, and JSON APIs.
                </p>
            </div>
            <div>
                <!-- Add New Phone Button -->
                <button class="pv-btn-primary" onclick="newPhone()">
                    <i class="fa-solid fa-plus me-1"></i> Add New Phone
                </button>
            </div>
        </div>

        <!-- Dynamic Feedback Alert Container -->
        <div id="alertContainer" class="mb-3"></div>

        <!-- Search & Records Card -->
        <div class="pv-card">
            <div class="pv-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pb-3">
                <h2 class="pv-card-title m-0">
                    <i class="fa-solid fa-boxes-stacked me-2"></i>Inventory Records
                </h2>
                <!-- Search Input Field -->
                <div class="position-relative" style="max-width: 340px; width: 100%;">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="search" class="form-control pv-input ps-5" placeholder="Search brand, model, or IMEI...">
                </div>
            </div>

            <!-- Bootstrap Records Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped pv-table align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 25%;">Phone Device</th>
                            <th style="width: 17%;">IMEI</th>
                            <th style="width: 10%;">Condition</th>
                            <th style="width: 10%;">Battery</th>
                            <th style="width: 12%;">Price</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="phoneTable">
                        <!-- Populated asynchronously by loadPhones() in js/phones.js -->
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-2"></i>Loading phone inventory...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add / Edit Bootstrap Modal Form -->
<div class="modal fade" id="phoneModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content pv-modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="fa-solid fa-mobile-screen-button me-2"></i>Add New Phone Device
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="phoneForm">
                    <!-- Hidden Phone ID input for determining ADD vs UPDATE -->
                    <input type="hidden" id="phoneId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="brand" class="form-label fw-600">Brand <span class="text-danger">*</span></label>
                            <input type="text" id="brand" class="form-control pv-input" placeholder="e.g. Apple, Samsung, Google" required>
                        </div>

                        <div class="col-md-6">
                            <label for="model" class="form-label fw-600">Model Name <span class="text-danger">*</span></label>
                            <input type="text" id="model" class="form-control pv-input" placeholder="e.g. iPhone 13, Galaxy S22" required>
                        </div>

                        <div class="col-md-6">
                            <label for="imei" class="form-label fw-600">IMEI Serial Number <span class="text-danger">*</span></label>
                            <input type="text" id="imei" class="form-control pv-input font-mono" placeholder="15-digit IMEI number" required>
                        </div>

                        <div class="col-md-3">
                            <label for="storage" class="form-label fw-600">Storage Capacity</label>
                            <input type="text" id="storage" class="form-control pv-input" placeholder="e.g. 128GB, 256GB" value="128GB">
                        </div>

                        <div class="col-md-3">
                            <label for="color" class="form-label fw-600">Color</label>
                            <input type="text" id="color" class="form-control pv-input" placeholder="e.g. Midnight, Blue" value="Black">
                        </div>

                        <div class="col-md-4">
                            <label for="conditionGrade" class="form-label fw-600">Condition Grade</label>
                            <select id="conditionGrade" class="form-select pv-input">
                                <option value="Grade A">Grade A (Like New)</option>
                                <option value="Grade B">Grade B (Minor Wear)</option>
                                <option value="Grade C">Grade C (Visible Scratches)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="batteryHealth" class="form-label fw-600">Battery Health (%)</label>
                            <input type="number" id="batteryHealth" class="form-control pv-input" min="1" max="100" value="100">
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label fw-600">Inventory Status</label>
                            <select id="status" class="form-select pv-input">
                                <option value="Available">Available</option>
                                <option value="Sold">Sold</option>
                                <option value="Returned">Returned</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="costPrice" class="form-label fw-600">Cost Price (₱)</label>
                            <input type="number" step="0.01" id="costPrice" class="form-control pv-input" placeholder="0.00" value="0.00">
                        </div>

                        <div class="col-md-6">
                            <label for="sellingPrice" class="form-label fw-600">Selling Price (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="sellingPrice" class="form-control pv-input" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="pv-btn-primary px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Phone Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Move JavaScript to an External File -->
<script src="js/phones.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
