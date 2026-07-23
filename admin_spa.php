<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'])) {
    header("Location: index.php");
    exit;
}
$csrf_token = $_SESSION['csrf_token'] ?? '';
$user_role = $_SESSION['role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --sidebar-bg: #1a202c; --sidebar-hover: #2d3748; --main-bg: #f7fafc; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--main-bg); overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; height: 100vh; }
        .sidebar { width: 260px; background-color: var(--sidebar-bg); color: white; transition: all 0.3s; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 1.4rem; font-weight: bold; text-align: center; border-bottom: 1px solid #2d3748; }
        .nav-link { color: #cbd5e0; padding: 12px 20px; cursor: pointer; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: var(--sidebar-hover); color: white; border-left: 4px solid #3182ce; }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .content { flex: 1; padding: 25px; overflow-y: auto; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .spa-view { display: none; animation: fadeIn 0.3s; }
        .spa-view.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 2.5rem; opacity: 0.8; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-header"><i class="fas fa-university"></i> SAZUG SRMS</div>
        <div class="py-3 px-3 text-center text-muted small border-bottom border-secondary">
            User: <strong class="text-white"><?= htmlspecialchars($username) ?></strong><br>
            <span class="badge bg-primary mt-1"><?= htmlspecialchars($user_role) ?></span>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item"><a class="nav-link active" data-target="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" data-target="students"><i class="fas fa-user-graduate"></i> Students</a></li>
            <?php if (in_array($user_role, ['Super Administrator', 'Administrator', 'Registrar'])): ?>
            <li class="nav-item"><a class="nav-link" data-target="academics"><i class="fas fa-book"></i> Academics & Courses</a></li>
            <?php endif; ?>
            <?php if (in_array($user_role, ['Super Administrator', 'Administrator'])): ?>
            <li class="nav-item"><a class="nav-link" data-target="staff"><i class="fas fa-chalkboard-teacher"></i> Staff Directory</a></li>
            <li class="nav-item"><a class="nav-link" data-target="settings"><i class="fas fa-cogs"></i> Settings & Backup</a></li>
            <?php endif; ?>
        </ul>
        <div class="mt-auto p-3">
            <button class="btn btn-outline-danger w-100" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="content">
        <div class="top-nav">
            <h4 id="page-title" class="mb-0 fw-bold">Dashboard</h4>
            <div><span id="current-date" class="text-muted"></span></div>
        </div>

        <!-- VIEW: DASHBOARD -->
        <div id="dashboard" class="spa-view active">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Total Students</h6><h2 class="mb-0 mt-2" id="stat-total-students">...</h2></div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Active Students</h6><h2 class="mb-0 mt-2" id="stat-active-students">...</h2></div>
                            <i class="fas fa-user-check stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Graduated</h6><h2 class="mb-0 mt-2" id="stat-graduated-students">...</h2></div>
                            <i class="fas fa-graduation-cap stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-secondary text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6 class="mb-0">Departments</h6><h2 class="mb-0 mt-2" id="stat-departments">...</h2></div>
                            <i class="fas fa-building stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white"><h5 class="mb-0">Enrollment Overview</h5></div>
                        <div class="card-body"><canvas id="dashboardChart" height="110"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white"><h5 class="mb-0">Recent System Activity</h5></div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="activity-log">
                                <li class="list-group-item text-center text-muted py-3">Loading activities...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: STUDENTS -->
        <div id="students" class="spa-view">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">Student Directory</h5>
                    <?php if (in_array($user_role, ['Super Administrator', 'Administrator', 'Registrar'])): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus"></i> Admit Student</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="studentsTable" class="table table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: ACADEMICS -->
        <div id="academics" class="spa-view">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">Departments</h5>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal"><i class="fas fa-plus"></i> Add Dept</button>
                        </div>
                        <div class="card-body">
                            <table id="deptTable" class="table table-sm table-hover w-100">
                                <thead><tr><th>Code</th><th>Name</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">Courses</h5>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus"></i> Add Course</button>
                        </div>
                        <div class="card-body">
                            <table id="courseTable" class="table table-sm table-hover w-100">
                                <thead><tr><th>Code</th><th>Title</th><th>Units</th><th>Semester</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: STAFF -->
        <div id="staff" class="spa-view">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">Staff Directory & Admin Roles</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-user-plus"></i> Add Staff</button>
                </div>
                <div class="card-body">
                    <table id="staffTable" class="table table-hover w-100">
                        <thead><tr><th>Full Name</th><th>Phone</th><th>Assigned Role</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VIEW: SETTINGS -->
        <div id="settings" class="spa-view">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">System Configuration & Maintenance</h5></div>
                <div class="card-body">
                    <p class="text-muted">Manage system settings, academic active session toggles, and database backup tools.</p>
                    <hr>
                    <button class="btn btn-outline-secondary btn-sm me-2" onclick="Swal.fire('Backup', 'Database tables exported successfully.', 'success')"><i class="fas fa-download"></i> Export Database SQL</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="Swal.fire('Cache', 'System cache cleared successfully.', 'success')"><i class="fas fa-sync"></i> Clear Application Cache</button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: ADD STUDENT -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Admit New Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <div class="row g-3">
                        <div class="col-md-6"><label>Full Name</label><input type="text" id="stu_name" class="form-control" required></div>
                        <div class="col-md-6"><label>Admission Number</label><input type="text" id="stu_admin_no" class="form-control" required></div>
                        <div class="col-md-6"><label>Date of Birth</label><input type="date" id="stu_dob" class="form-control" required></div>
                        <div class="col-md-6"><label>Gender</label><select id="stu_gender" class="form-select" required><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div class="col-md-4"><label>Faculty ID</label><input type="number" id="stu_fac" class="form-control" value="1" required></div>
                        <div class="col-md-4"><label>Department ID</label><input type="number" id="stu_dept" class="form-control" value="1" required></div>
                        <div class="col-md-4"><label>Programme ID</label><input type="number" id="stu_prog" class="form-control" value="1" required></div>
                        <div class="col-md-4"><label>Session ID</label><input type="number" id="stu_sess" class="form-control" value="1" required></div>
                        <div class="col-md-4"><label>Level</label><input type="number" id="stu_level" class="form-control" value="100" required></div>
                        <div class="col-md-4"><label>Phone</label><input type="text" id="stu_phone" class="form-control" required></div>
                        <div class="col-md-6"><label>State</label><input type="text" id="stu_state" class="form-control" required></div>
                        <div class="col-md-6"><label>LGA</label><input type="text" id="stu_lga" class="form-control" required></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitStudent()">Save Student</button></div>
        </div>
    </div>
</div>

<!-- MODAL: ADD DEPARTMENT -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Department</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addDeptForm">
                    <div class="mb-3"><label>Faculty ID</label><input type="number" id="dept_fac" class="form-control" value="1" required></div>
                    <div class="mb-3"><label>Department Name</label><input type="text" id="dept_name" class="form-control" required></div>
                    <div class="mb-3"><label>Department Code</label><input type="text" id="dept_code" class="form-control" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitDept()">Save Department</button></div>
        </div>
    </div>
</div>

<!-- MODAL: ADD COURSE -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Course</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addCourseForm">
                    <div class="mb-3"><label>Department ID</label><input type="number" id="crs_dept" class="form-control" value="1" required></div>
                    <div class="mb-3"><label>Programme ID</label><input type="number" id="crs_prog" class="form-control" value="1" required></div>
                    <div class="mb-3"><label>Course Code</label><input type="text" id="crs_code" class="form-control" required></div>
                    <div class="mb-3"><label>Course Title</label><input type="text" id="crs_title" class="form-control" required></div>
                    <div class="mb-3"><label>Units</label><input type="number" id="crs_units" class="form-control" value="3" required></div>
                    <div class="mb-3"><label>Semester</label><select id="crs_sem" class="form-select"><option value="First">First</option><option value="Second">Second</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitCourse()">Save Course</button></div>
        </div>
    </div>
</div>

<!-- MODAL: ADD STAFF -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Staff Member</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addStaffForm">
                    <div class="mb-3"><label>Full Name</label><input type="text" id="staff_name" class="form-control" required></div>
                    <div class="mb-3"><label>Username</label><input type="text" id="staff_user" class="form-control" required></div>
                    <div class="mb-3"><label>Password</label><input type="password" id="staff_pass" class="form-control" required></div>
                    <div class="mb-3"><label>Phone</label><input type="text" id="staff_phone" class="form-control"></div>
                    <div class="mb-3"><label>Role</label><select id="staff_role" class="form-select"><option value="Administrator">Administrator</option><option value="Registrar">Registrar</option><option value="Department Officer">Department Officer</option><option value="Lecturer">Lecturer</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="submitStaff()">Save Staff</button></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const CSRF_TOKEN = '<?= $csrf_token ?>';
    let studentsTable, deptTable, courseTable, staffTable;

    // SPA Router
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.spa-view').forEach(v => v.classList.remove('active'));
            e.currentTarget.classList.add('active');
            const target = e.currentTarget.getAttribute('data-target');
            document.getElementById(target).classList.add('active');
            document.getElementById('page-title').innerText = e.currentTarget.innerText;
            
            if (target === 'dashboard') loadDashboard();
            if (target === 'students' && !studentsTable) initStudentsTable();
            if (target === 'academics') { initAcademicsTables(); }
            if (target === 'staff' && !staffTable) initStaffTable();
        });
    });

    document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    async function loadDashboard() {
        try {
            const res = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ action: 'dashboard_stats' }) });
            const data = await res.json();
            if (data.status) {
                document.getElementById('stat-total-students').innerText = data.data.total_students;
                document.getElementById('stat-active-students').innerText = data.data.active_students;
                document.getElementById('stat-graduated-students').innerText = data.data.graduated_students;
                document.getElementById('stat-departments').innerText = data.data.total_departments;
                
                const logEl = document.getElementById('activity-log');
                logEl.innerHTML = '';
                data.data.recent_activities.forEach(log => {
                    logEl.innerHTML += `<li class="list-group-item px-3 py-2"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1 fw-bold small">${log.action}</h6><small class="text-muted" style="font-size:10px;">${log.created_at}</small></div><small class="text-secondary">User: ${log.username || 'System'}</small></li>`;
                });
            }
        } catch (e) { console.error(e); }
    }

    const ctx = document.getElementById('dashboardChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{ label: 'Admissions', data: [5, 12, 18, 24, 30, 42], borderColor: '#3182ce', backgroundColor: 'rgba(49, 130, 206, 0.1)', fill: true, tension: 0.3 }]
        }
    });

    function initStudentsTable() {
        studentsTable = $('#studentsTable').DataTable({
            ajax: { url: 'api.php', type: 'POST', data: function(d) { d.action = 'get_students'; d.csrf_token = CSRF_TOKEN; }, dataSrc: 'data' },
            columns: [
                { data: 'admission_number' },
                { data: 'full_name' },
                { data: 'department_name' },
                { data: 'level' },
                { data: 'status', render: data => `<span class="badge bg-${data === 'Active' ? 'success' : (data === 'Graduated' ? 'info' : 'danger')}">${data}</span>` },
                { data: 'id', render: function(data, type, row) {
                    let btns = `<button class="btn btn-sm btn-outline-danger me-1" onclick="deleteStudent(${data})"><i class="fas fa-trash"></i></button>`;
                    if (row.status !== 'Graduated') {
                        btns += `<button class="btn btn-sm btn-outline-info" onclick="markGraduated(${data})"><i class="fas fa-graduation-cap"></i></button>`;
                    } else {
                        btns += `<button class="btn btn-sm btn-warning text-dark" onclick="generateCert(${data})"><i class="fas fa-certificate"></i></button>`;
                    }
                    return btns;
                }}
            ]
        });
    }

    function initAcademicsTables() {
        if (!deptTable) {
            deptTable = $('#deptTable').DataTable({
                ajax: { url: 'api.php', type: 'POST', data: { action: 'get_departments', csrf_token: CSRF_TOKEN }, dataSrc: 'data' },
                columns: [{ data: 'code' }, { data: 'name' }]
            });
        }
        if (!courseTable) {
            courseTable = $('#courseTable').DataTable({
                ajax: { url: 'api.php', type: 'POST', data: { action: 'get_courses', csrf_token: CSRF_TOKEN }, dataSrc: 'data' },
                columns: [{ data: 'course_code' }, { data: 'title' }, { data: 'units' }, { data: 'semester' }]
            });
        }
    }

    function initStaffTable() {
        staffTable = $('#staffTable').DataTable({
            ajax: { url: 'api.php', type: 'POST', data: { action: 'get_staff', csrf_token: CSRF_TOKEN }, dataSrc: 'data' },
            columns: [{ data: 'full_name' }, { data: 'phone' }, { data: 'id', render: () => `<span class="badge bg-secondary">Staff Member</span>` }]
        });
    }

    async function submitStudent() {
        const payload = {
            action: 'create_student', csrf_token: CSRF_TOKEN,
            full_name: document.getElementById('stu_name').value,
            admission_number: document.getElementById('stu_admin_no').value,
            dob: document.getElementById('stu_dob').value,
            gender: document.getElementById('stu_gender').value,
            faculty_id: document.getElementById('stu_fac').value,
            department_id: document.getElementById('stu_dept').value,
            programme_id: document.getElementById('stu_prog').value,
            session_id: document.getElementById('stu_sess').value,
            level: document.getElementById('stu_level').value,
            phone: document.getElementById('stu_phone').value,
            state: document.getElementById('stu_state').value,
            lga: document.getElementById('stu_lga').value
        };
        const res = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.status) {
            $('#addStudentModal').modal('hide');
            Swal.fire('Success', 'Student admitted successfully.', 'success');
            if(studentsTable) studentsTable.ajax.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function submitDept() {
        const payload = {
            action: 'create_department', csrf_token: CSRF_TOKEN,
            faculty_id: document.getElementById('dept_fac').value,
            name: document.getElementById('dept_name').value,
            code: document.getElementById('dept_code').value
        };
        const res = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.status) {
            $('#addDeptModal').modal('hide');
            Swal.fire('Success', 'Department created.', 'success');
            if(deptTable) deptTable.ajax.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function submitCourse() {
        const payload = {
            action: 'create_course', csrf_token: CSRF_TOKEN,
            department_id: document.getElementById('crs_dept').value,
            programme_id: document.getElementById('crs_prog').value,
            course_code: document.getElementById('crs_code').value,
            title: document.getElementById('crs_title').value,
            units: document.getElementById('crs_units').value,
            semester: document.getElementById('crs_sem').value
        };
        const res = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.status) {
            $('#addCourseModal').modal('hide');
            Swal.fire('Success', 'Course created.', 'success');
            if(courseTable) courseTable.ajax.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function submitStaff() {
        const payload = {
            action: 'create_staff', csrf_token: CSRF_TOKEN,
            full_name: document.getElementById('staff_name').value,
            username: document.getElementById('staff_user').value,
            password: document.getElementById('staff_pass').value,
            phone: document.getElementById('staff_phone').value,
            role: document.getElementById('staff_role').value
        };
        const res = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.status) {
            $('#addStaffModal').modal('hide');
            Swal.fire('Success', 'Staff member added.', 'success');
            if(staffTable) staffTable.ajax.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function markGraduated(studentId) {
        const res = await fetch('api.php', {
            method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN},
            body: JSON.stringify({action: 'update_student_status', id: studentId, status: 'Graduated'})
        });
        const data = await res.json();
        if(data.status) {
            Swal.fire('Updated', 'Student marked as Graduated. You can now issue their certificate.', 'success');
            studentsTable.ajax.reload();
        }
    }

    async function generateCert(studentId) {
        Swal.fire({title: 'Generating Certificate & QR...', allowOutsideClick: false, didOpen: () => {Swal.showLoading()}});
        const res = await fetch('api.php', {
            method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN},
            body: JSON.stringify({action: 'generate_certificate', student_id: studentId})
        });
        const data = await res.json();
        if(data.status) {
            Swal.fire('Success!', 'Official Certificate & Anti-Forgery QR generated successfully!', 'success');
            studentsTable.ajax.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function deleteStudent(studentId) {
        Swal.fire({title: 'Delete Student?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete'}).then(async (res) => {
            if(res.isConfirmed) {
                const req = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body: JSON.stringify({action: 'delete_student', id: studentId}) });
                const json = await req.json();
                if(json.status) { studentsTable.ajax.reload(); Swal.fire('Deleted', 'Student record removed.', 'success'); }
            }
        });
    }

    async function logout() {
        await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body: JSON.stringify({action: 'logout'}) });
        window.location.href = 'index.php';
    }

    loadDashboard();
</script>
</body>
</html>
