/* ============================================================
   PhoneVault – assets/js/students.js (Mirror of js/students.js)
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

async function loadStudents() {
    const table = document.getElementById('studentTable');
    if (!table) return;

    try {
        const response = await fetch('api/get_students.php');
        if (!response.ok) throw new Error('Network response was not ok');
        const students = await response.json();

        table.innerHTML = '';

        if (!Array.isArray(students) || students.length === 0) {
            table.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fa-solid fa-folder-open mb-2 d-block fs-3"></i>
                        No student records found. Click <strong>"Add New Student"</strong> to create one.
                    </td>
                </tr>
            `;
            return;
        }

        students.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="font-mono text-muted">#${student.id}</td>
                <td class="fw-bold">${escapeHtml(student.name)}</td>
                <td><span class="badge bg-indigo-subtle text-indigo px-2 py-1">${escapeHtml(student.course)}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning btn-sm" onclick="editStudent(${student.id})">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.id})">
                            <i class="fa-solid fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </td>
            `;
            table.appendChild(row);
        });

        const searchInput = document.getElementById('search');
        if (searchInput && searchInput.value) {
            searchInput.dispatchEvent(new Event('input'));
        }
    } catch (error) {
        console.error('Error fetching students:', error);
        table.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger py-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    Failed to load student records. Please check database connection.
                </td>
            </tr>
        `;
    }
}

function newStudent() {
    const studentForm = document.getElementById('studentForm');
    if (studentForm) studentForm.reset();

    const idInput = document.getElementById('studentId');
    if (idInput) idInput.value = '';

    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Add New Student';

    const modalEl = document.getElementById('studentModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }
}

async function editStudent(id) {
    try {
        const response = await fetch(`api/get_student.php?id=${id}`);
        if (!response.ok) throw new Error('Failed to fetch student details');

        const student = await response.json();

        if (student.status === 'error') {
            showAlert('danger', student.message);
            return;
        }

        document.getElementById('studentId').value = student.id;
        document.getElementById('name').value = student.name;
        document.getElementById('course').value = student.course;

        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Edit Student Record';

        const modalEl = document.getElementById('studentModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    } catch (error) {
        console.error('Error loading student record:', error);
        showAlert('danger', 'Unable to fetch student details.');
    }
}

async function deleteStudent(id) {
    const answer = confirm('Are you sure you want to delete this student record?');
    if (!answer) return;

    try {
        const response = await fetch('api/delete_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.status === 'success') {
            showAlert('success', result.message || 'Student record deleted successfully.');
            loadStudents();
        } else {
            showAlert('danger', result.message || 'Unable to delete student record.');
        }
    } catch (error) {
        console.error('Error deleting student:', error);
        showAlert('danger', 'API error occurred while deleting record.');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function () {
    loadStudents();

    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        studentForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const nameInput   = document.getElementById('name');
            const courseInput = document.getElementById('course');
            const name   = nameInput ? nameInput.value.trim() : '';
            const course = courseInput ? courseInput.value.trim() : '';
            const id     = document.getElementById('studentId').value;

            if (name === '') {
                showAlert('danger', 'Student name is required.');
                if (nameInput) nameInput.focus();
                return;
            }

            if (course === '') {
                showAlert('danger', 'Course is required.');
                if (courseInput) courseInput.focus();
                return;
            }

            const data = { name, course };
            let endpoint = 'api/add_student.php';

            if (id !== '') {
                data.id = id;
                endpoint = 'api/update_student.php';
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    const modalEl = document.getElementById('studentModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                    }

                    studentForm.reset();
                    document.getElementById('studentId').value = '';

                    showAlert('success', result.message || 'Operation completed successfully.');
                    loadStudents();
                } else {
                    showAlert('danger', result.message || 'An error occurred during submission.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                showAlert('danger', 'Network error or invalid server response.');
            }
        });
    }

    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value.toLowerCase();
            const rows = document.querySelectorAll('#studentTable tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(keyword) ? '' : 'none';
            });
        });
    }
});
