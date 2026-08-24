<?php
/* ============================================================
   PhoneVault – students-js.php (JavaScript-Driven CRUD Page)
   ============================================================ */

require_once __DIR__ . '/config/auth.php';
requireLogin();

$pageTitle  = 'Student JavaScript CRUD';
$pageScript = 'students.js';

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
                    <i class="fa-solid fa-graduation-cap text-primary-pv me-2"></i>Student Management (JS-Driven CRUD)
                </h1>
                <p class="pv-page-subtitle text-muted mb-0">
                    Asynchronous single-page CRUD using Fetch API, Async/Await, Prepared Statements, and JSON APIs.
                </p>
            </div>
            <div>
                <!-- Add Student Button -->
                <button class="pv-btn-primary" onclick="newStudent()">
                    <i class="fa-solid fa-plus me-1"></i> Add New Student
                </button>
            </div>
        </div>

        <!-- Dynamic Feedback Alert Container -->
        <div id="alertContainer" class="mb-3"></div>

        <!-- Search & Records Card -->
        <div class="pv-card">
            <div class="pv-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pb-3">
                <h2 class="pv-card-title m-0">
                    <i class="fa-solid fa-list-check me-2"></i>Student Records
                </h2>
                <!-- Search Input Field (Step 15) -->
                <div class="position-relative" style="max-width: 320px; width: 100%;">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="search" class="form-control pv-input ps-5" placeholder="Search student name or course...">
                </div>
            </div>

            <!-- Step 3: Bootstrap Records Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped pv-table align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Name</th>
                            <th style="width: 35%;">Course</th>
                            <th style="width: 20%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTable">
                        <!-- Populated asynchronously by loadStudents() in js/students.js -->
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-2"></i>Loading student records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Step 6: Add / Edit Bootstrap Modal Form -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="fa-solid fa-user-plus me-2"></i>Add New Student
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="studentForm">
                    <!-- Hidden ID input for determining ADD vs UPDATE -->
                    <input type="hidden" id="studentId">

                    <div class="mb-3">
                        <label for="name" class="form-label fw-600">Student Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control pv-input" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label for="course" class="form-label fw-600">Course / Program <span class="text-danger">*</span></label>
                        <input type="text" id="course" class="form-control pv-input" placeholder="e.g. BS Information Technology" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="pv-btn-primary px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Step 18: Move JavaScript to an External File -->
<script src="js/students.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
