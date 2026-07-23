<?php
/**
 * SAZUG Student Record Management System - Public Entry
 * Handles Login and Public Certificate Verification.
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if (in_array($_SESSION['role'], ['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'])) {
        header("Location: admin_spa.php");
        exit;
    } elseif ($_SESSION['role'] === 'Lecturer') {
        header("Location: lecturer_spa.php");
        exit;
    } else {
        header("Location: student_spa.php");
        exit;
    }
}

// Database Connection strictly for Public QR Verification
$verificationData = null;
$verificationError = null;

if (isset($_GET['verify']) && !empty($_GET['verify'])) {
    $certNumber = trim($_GET['verify']);
    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME') ?: 'sazug_srms';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare("
            SELECT c.certificate_number, c.issue_date, s.full_name, s.matric_number, s.graduation_date, 
                   p.name as programme, d.name as department, f.name as faculty 
            FROM certificates c
            JOIN students s ON c.student_id = s.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            WHERE c.certificate_number = :cert
        ");
        $stmt->execute(['cert' => $certNumber]);
        $verificationData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$verificationData) {
            $verificationError = "Certificate Number not found or is invalid.";
        }
    } catch (PDOException $e) {
        $verificationError = "System temporarily unavailable.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG SRMS - Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card, .verify-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; max-width: 900px; width: 100%; }
        .bg-sazug { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; }
        .btn-primary { background-color: #203a43; border: none; }
        .btn-primary:hover { background-color: #0f2027; }
        .cert-valid { color: #198754; font-size: 4rem; }
        .cert-invalid { color: #dc3545; font-size: 4rem; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_GET['verify'])): ?>
        <!-- PUBLIC CERTIFICATE VERIFICATION VIEW -->
        <div class="card verify-card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-sazug text-center py-4">
                <h3 class="mb-0">Official Certificate Verification</h3>
            </div>
            <div class="card-body text-center p-5">
                <?php if ($verificationData): ?>
                    <i class="fas fa-check-circle cert-valid mb-3"></i>
                    <h4 class="text-success fw-bold">Authentic Certificate</h4>
                    <hr>
                    <table class="table table-borderless text-start">
                        <tr><th class="text-muted">Student Name:</th> <td><strong><?= htmlspecialchars($verificationData['full_name']) ?></strong></td></tr>
                        <tr><th class="text-muted">Matric Number:</th> <td><?= htmlspecialchars($verificationData['matric_number']) ?></td></tr>
                        <tr><th class="text-muted">Programme:</th> <td><?= htmlspecialchars($verificationData['programme']) ?></td></tr>
                        <tr><th class="text-muted">Department:</th> <td><?= htmlspecialchars($verificationData['department']) ?></td></tr>
                        <tr><th class="text-muted">Faculty:</th> <td><?= htmlspecialchars($verificationData['faculty']) ?></td></tr>
                        <tr><th class="text-muted">Graduation Date:</th> <td><?= htmlspecialchars($verificationData['graduation_date']) ?></td></tr>
                        <tr><th class="text-muted">Certificate No:</th> <td><?= htmlspecialchars($verificationData['certificate_number']) ?></td></tr>
                    </table>
                <?php else: ?>
                    <i class="fas fa-times-circle cert-invalid mb-3"></i>
                    <h4 class="text-danger fw-bold">Verification Failed</h4>
                    <p class="text-muted"><?= htmlspecialchars($verificationError) ?></p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">Go to Login</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- LOGIN VIEW -->
        <div class="card login-card mx-auto">
            <div class="row g-0">
                <div class="col-md-6 bg-sazug d-flex flex-column justify-content-center align-items-center p-5 text-center">
                    <i class="fas fa-university fa-4x mb-3"></i>
                    <h2>SAZUG</h2>
                    <p class="lead">Student Record Management System</p>
                    <small class="mt-5 text-white-50">Secure, Scalable & Fast.</small>
                </div>
                <div class="col-md-6 p-5 bg-white">
                    <h3 class="mb-4 fw-bold">Sign In</h3>
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Username / Admission No.</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2" id="loginBtn">
                            Secure Login <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const loginForm = document.getElementById('loginForm');
    if(loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            const user = document.getElementById('username').value;
            const pass = document.getElementById('password').value;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            btn.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', username: user, password: pass })
                });
                const data = await response.json();

                if (data.status) {
                    Swal.fire({ icon: 'success', title: 'Welcome', text: 'Login successful!', timer: 1500, showConfirmButton: false }).then(() => {
                        // Routing based on RBAC role returned from API
                        if (['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'].includes(data.role)) {
                            window.location.href = 'admin_spa.php';
                        } else if (data.role === 'Lecturer') {
                            window.location.href = 'lecturer_spa.php';
                        } else {
                            window.location.href = 'student_spa.php';
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Access Denied', text: data.message });
                    btn.innerHTML = 'Secure Login <i class="fas fa-arrow-right ms-2"></i>';
                    btn.disabled = false;
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to communicate with the server.' });
                btn.innerHTML = 'Secure Login <i class="fas fa-arrow-right ms-2"></i>';
                btn.disabled = false;
            }
        });
    }
</script>
</body>
</html>