<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}
$csrf_token = $_SESSION['csrf_token'] ?? '';
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get Student Data from DB safely
require_once 'SystemCore.php';
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'sazug_srms';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '');
$stmt = $pdo->prepare("SELECT s.*, p.name as prog_name, d.name as dept_name, c.certificate_number, c.qr_code_path 
                       FROM students s 
                       JOIN programmes p ON s.programme_id = p.id 
                       JOIN departments d ON s.department_id = d.id 
                       LEFT JOIN certificates c ON s.id = c.student_id 
                       WHERE s.user_id = :uid");
$stmt->execute(['uid' => $user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-weight: bold; }
        .profile-card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .cert-card { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; border: none; border-radius: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-user-graduate"></i> SAZUG Student Portal</a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3"><?= htmlspecialchars($student['full_name']) ?></span>
            <button class="btn btn-outline-light btn-sm" onclick="logout()">Logout</button>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-md-8">
            <div class="card profile-card">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Academic Profile</h5></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Admission Number</div>
                        <div class="col-sm-8 fw-bold"><?= htmlspecialchars($student['admission_number']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Matriculation Number</div>
                        <div class="col-sm-8 fw-bold"><?= htmlspecialchars($student['matric_number'] ?? 'Not Assigned') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Department</div>
                        <div class="col-sm-8"><?= htmlspecialchars($student['dept_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Programme</div>
                        <div class="col-sm-8"><?= htmlspecialchars($student['prog_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Level</div>
                        <div class="col-sm-8"><?= htmlspecialchars($student['level']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Current Status</div>
                        <div class="col-sm-8">
                            <?php 
                                $color = $student['status'] == 'Active' ? 'success' : ($student['status'] == 'Graduated' ? 'info' : 'danger');
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($student['status']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Upload & Certificate -->
        <div class="col-md-4">
            <?php if ($student['status'] === 'Graduated' && $student['certificate_number']): ?>
            <div class="card cert-card mb-4">
                <div class="card-body text-center p-4">
                    <i class="fas fa-graduation-cap fa-3x mb-3 text-warning"></i>
                    <h5 class="fw-bold">Congratulations!</h5>
                    <p class="small">Your official graduation certificate is ready for download.</p>
                    <p class="small text-warning">Cert No: <?= htmlspecialchars($student['certificate_number']) ?></p>
                    <a href="uploads/certificates/cert_<?= $student['id'] ?>.pdf" class="btn btn-warning fw-bold w-100" target="_blank">
                        <i class="fas fa-download"></i> Download Certificate
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="card profile-card">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Document Upload</h5></div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_document">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label small">Document Type</label>
                            <select name="doc_type" class="form-select form-select-sm" required>
                                <option value="Admission">Admission Letter</option>
                                <option value="Passport">Passport Photograph</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input type="file" name="document" class="form-control form-control-sm" accept=".jpg,.png,.pdf" required>
                            <div class="form-text" style="font-size:11px;">Max 5MB. PDF, JPG, PNG only.</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-upload"></i> Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const CSRF_TOKEN = '<?= $csrf_token ?>';
    
    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        Swal.fire({title: 'Uploading...', allowOutsideClick: false, didOpen: () => {Swal.showLoading()}});
        
        try {
            const res = await fetch('api.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status) {
                Swal.fire('Success', 'Document uploaded successfully.', 'success');
                e.target.reset();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Network request failed.', 'error');
        }
    });

    async function logout() {
        await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN}, body: JSON.stringify({action: 'logout'})});
        window.location.href = 'index.php';
    }
</script>
</body>
</html>