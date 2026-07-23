<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: index.php");
    exit;
}
$csrf_token = $_SESSION['csrf_token'] ?? '';
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Portal - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --sidebar-bg: #2b6cb0; --sidebar-hover: #2c5282; --main-bg: #f7fafc; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--main-bg); overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; height: 100vh; }
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: white; transition: all 0.3s; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; font-size: 1.5rem; font-weight: bold; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { color: #e2e8f0; padding: 15px 20px; cursor: pointer; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: var(--sidebar-hover); color: white; border-left: 4px solid #ecc94b; }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .spa-view { display: none; animation: fadeIn 0.3s; }
        .spa-view.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="wrapper">
    <nav class="sidebar">
        <div class="sidebar-header"><i class="fas fa-chalkboard-teacher"></i> SAZUG</div>
        <div class="py-3 px-3 text-center text-light small">
            Lecturer Portal<br><strong class="text-white"><?= htmlspecialchars($username) ?></strong>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item"><a class="nav-link active" data-target="dashboard"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" data-target="students"><i class="fas fa-user-graduate"></i> My Students</a></li>
        </ul>
        <div class="mt-auto p-3">
            <button class="btn btn-outline-light w-100" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </nav>

    <div class="content">
        <div class="top-nav">
            <h4 id="page-title" class="mb-0 fw-bold">Home</h4>
            <span class="text-muted"><?= date('l, F j, Y') ?></span>
        </div>

        <div id="dashboard" class="spa-view active">
            <div class="alert alert-info border-0 shadow-sm">
                <h4 class="alert-heading">Welcome back, <?= htmlspecialchars($username) ?>!</h4>
                <p>Use the sidebar to view the students enrolled in the department you are assigned to.</p>
                <hr>
                <p class="mb-0">Note: Lecturers have Read-Only access to student profiles to ensure data integrity.</p>
            </div>
        </div>

        <div id="students" class="spa-view">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Student Roster</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="lecturerStudentsTable" class="table table-hover w-100">
                            <thead><tr><th>Admission No</th><th>Name</th><th>Department</th><th>Level</th><th>Status</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    const CSRF_TOKEN = '<?= $csrf_token ?>';
    let stdTable;

    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.spa-view').forEach(v => v.classList.remove('active'));
            e.currentTarget.classList.add('active');
            const target = e.currentTarget.getAttribute('data-target');
            document.getElementById(target).classList.add('active');
            document.getElementById('page-title').innerText = e.currentTarget.innerText;
            if (target === 'students' && !stdTable) {
                stdTable = $('#lecturerStudentsTable').DataTable({
                    ajax: { url: 'api.php', type: 'POST', data: { action: 'get_students', csrf_token: CSRF_TOKEN }, dataSrc: 'data' },
                    columns: [
                        { data: 'admission_number' }, { data: 'full_name' }, { data: 'department_name' },
                        { data: 'level' },
                        { data: 'status', render: data => `<span class="badge bg-${data === 'Active' ? 'success' : 'secondary'}">${data}</span>` }
                    ]
                });
            }
        });
    });

    async function logout() {
        await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body: JSON.stringify({action: 'logout'})});
        window.location.href = 'index.php';
    }
</script>
</body>
</html>