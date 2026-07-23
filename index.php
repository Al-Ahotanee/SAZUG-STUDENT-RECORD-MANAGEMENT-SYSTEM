<?php
/**
 * SAZUG Student Record Management System - Public Enterprise Portal & Landing Page
 * Handles Elite Landing Page, Secure Authentication, and Public Anti-Forgery Verification.
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
    $port = getenv('DB_PORT') ?: '12417';
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4;sslmode=require", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
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
            $verificationError = "Certificate Number not found or is invalid in the SAZUG registry.";
        }
    } catch (PDOException $e) {
        $verificationError = "Verification service temporarily unavailable.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG - Enterprise Student Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1e293b 100%);
            --accent-glow: 0 0 30px rgba(37, 99, 235, 0.2);
            --card-bg: rgba(255, 255, 255, 0.95);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            overflow-x: hidden;
        }
        /* Navbar */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 120px 0 100px 0;
            position: relative;
            clip-path: ellipse(150% 100% at 50% 0%);
        }
        .hero-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        /* Carousel */
        .carousel-item {
            padding: 20px 0;
        }
        /* Feature Cards */
        .feature-card {
            border: none;
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        /* Login Modal & Verification Box */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .btn-glow {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.5);
            transition: all 0.2s ease;
        }
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(37, 99, 235, 0.6);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 60px 0 30px 0;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4" href="index.php">
            <div class="bg-primary text-white p-2 rounded-3 me-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span>SAZUG <span class="text-primary fw-light">SRMS</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center gap-3">
                <li class="nav-item"><a class="nav-link text-white" href="#features">Capabilities</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#verify-section">Certificate Verification</a></li>
                <li class="nav-item">
                    <button class="btn btn-glow text-white px-4 py-2 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-lock me-2"></i> Portal Login
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_GET['verify'])): ?>
    <!-- PUBLIC CERTIFICATE VERIFICATION VIEW -->
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5 mt-5">
        <div class="card shadow-lg border-0 rounded-4 w-100" style="max-width: 650px;">
            <div class="card-header bg-dark text-white text-center py-4 rounded-top-4">
                <h3 class="mb-0 fw-bold"><i class="fas fa-shield-alt text-primary me-2"></i> Official Verification Portal</h3>
                <p class="text-white-50 small mb-0 mt-1">SAZUG Anti-Forgery Certificate Authenticator</p>
            </div>
            <div class="card-body text-center p-5">
                <?php if ($verificationData): ?>
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle p-4 mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </span>
                        <h3 class="text-success fw-bold">Authentic & Verified</h3>
                        <p class="text-muted small">This certificate record matches official institutional archives.</p>
                    </div>
                    <hr class="text-muted opacity-25">
                    <table class="table table-borderless text-start align-middle">
                        <tr><th class="text-muted w-40">Recipient Name:</th> <td class="fw-bold fs-5 text-dark"><?= htmlspecialchars($verificationData['full_name']) ?></td></tr>
                        <tr><th class="text-muted">Matriculation No:</th> <td><code><?= htmlspecialchars($verificationData['matric_number']) ?></code></td></tr>
                        <tr><th class="text-muted">Award / Programme:</th> <td class="fw-semibold"><?= htmlspecialchars($verificationData['programme']) ?></td></tr>
                        <tr><th class="text-muted">Department:</th> <td><?= htmlspecialchars($verificationData['department']) ?></td></tr>
                        <tr><th class="text-muted">Faculty:</th> <td><?= htmlspecialchars($verificationData['faculty']) ?></td></tr>
                        <tr><th class="text-muted">Date Awarded:</th> <td><?= htmlspecialchars($verificationData['graduation_date']) ?></td></tr>
                        <tr><th class="text-muted">Certificate ID:</th> <td><span class="badge bg-primary"><?= htmlspecialchars($verificationData['certificate_number']) ?></span></td></tr>
                    </table>
                <?php else: ?>
                    <div class="mb-4">
                        <span class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle p-4 mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-times-circle fa-3x"></i>
                        </span>
                        <h3 class="text-danger fw-bold">Verification Failed</h3>
                        <p class="text-muted"><?= htmlspecialchars($verificationError) ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-4 pt-3 border-top">
                    <a href="index.php" class="btn btn-outline-dark px-4 py-2 rounded-pill"><i class="fas fa-arrow-left me-2"></i> Return to Home</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container pt-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold mb-4">
                        <i class="fas fa-bolt me-2"></i> Enterprise SRMS v1.0 Production
                    </span>
                    <h1 class="display-3 fw-extrabold mb-4 lh-tight">
                        Shaping the Future of <span class="text-primary text-gradient">Academic Excellence</span>
                    </h1>
                    <p class="lead text-light opacity-80 mb-5">
                        A world-class, lightning-fast, and secure Student Record Management System designed for end-to-end lifecycle management from admission to graduation.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-glow text-white px-5 py-3 rounded-pill fw-bold fs-5" data-bs-toggle="modal" data-bs-target="#loginModal">
                            Access Secure Portal <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        <a href="#verify-section" class="btn btn-outline-light px-4 py-3 rounded-pill fw-semibold fs-5">
                            Verify Certificate
                        </a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-card p-4 text-white">
                        <h4 class="fw-bold mb-3"><i class="fas fa-shield-alt text-primary me-2"></i> System Highlights</h4>
                        <div class="d-flex align-items-center mb-3 bg-white bg-opacity-10 p-3 rounded-3">
                            <i class="fas fa-user-check fa-2x text-success me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Role-Based Access Control</h6>
                                <small class="text-white-50">Secure multi-tier permissions for Admins, Registrars & Faculty.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3 bg-white bg-opacity-10 p-3 rounded-3">
                            <i class="fas fa-qrcode fa-2x text-info me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Anti-Forgery QR Verification</h6>
                                <small class="text-white-50">Instant public validation for official graduation credentials.</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center bg-white bg-opacity-10 p-3 rounded-3">
                            <i class="fas fa-tachometer-alt fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Optimized Performance</h6>
                                <small class="text-white-50">Minimal memory footprint engineered for high availability.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CAROUSEL / CAPABILITIES SHOWCASE -->
    <section class="py-5 bg-white" id="features">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-primary text-uppercase fw-bold tracking-wider">Enterprise Architecture</h6>
                <h2 class="fw-bold display-5">Engineered for Complete Student Lifecycles</h2>
                <p class="text-muted col-lg-6 mx-auto">From secure document storage to complex academic reporting and audit trails.</p>
            </div>

            <!-- BOOTSTRAP CAROUSEL -->
            <div id="enterpriseCarousel" class="carousel slide mb-5 pb-4" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#enterpriseCarousel" data-bs-slide-to="0" class="active bg-primary" aria-current="true"></button>
                    <button type="button" data-bs-target="#enterpriseCarousel" data-bs-slide-to="1" class="bg-primary"></button>
                    <button type="button" data-bs-target="#enterpriseCarousel" data-bs-slide-to="2" class="bg-primary"></button>
                </div>
                <div class="carousel-inner rounded-4 shadow-lg overflow-hidden">
                    <div class="carousel-item active bg-dark text-white p-5">
                        <div class="row align-items-center py-4 px-3">
                            <div class="col-md-6">
                                <span class="badge bg-primary mb-3">Module 01</span>
                                <h2 class="fw-bold display-6 mb-3">Advanced Student Information System</h2>
                                <p class="text-white-50 lead">Complete CRUD operations, demographic tracking, academic standing status updates, promotion, suspension, and alumni record archiving.</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <i class="fas fa-users-cog fa-5x text-primary opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item bg-dark text-white p-5">
                        <div class="row align-items-center py-4 px-3">
                            <div class="col-md-6">
                                <span class="badge bg-success mb-3">Module 02</span>
                                <h2 class="fw-bold display-6 mb-3">Secure Document & Certificate Engine</h2>
                                <p class="text-white-50 lead">Integrated mPDF generator coupled with Endroid QR codes for instant forgery-proof certificate generation and student document uploads.</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <i class="fas fa-certificate fa-5x text-success opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item bg-dark text-white p-5">
                        <div class="row align-items-center py-4 px-3">
                            <div class="col-md-6">
                                <span class="badge bg-warning text-dark mb-3">Module 03</span>
                                <h2 class="fw-bold display-6 mb-3">Audit Trails & System Security</h2>
                                <p class="text-white-50 lead">Immutable audit logs recording every login, record creation, password reset, and administrative action with IP address tracking.</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <i class="fas fa-fingerprint fa-5x text-warning opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#enterpriseCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#enterpriseCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            </div>

            <!-- QUICK VERIFICATION WIDGET -->
            <div class="row justify-content-center" id="verify-section">
                <div class="col-lg-8">
                    <div class="card feature-card p-5 border border-primary border-opacity-25 shadow-lg">
                        <div class="text-center mb-4">
                            <div class="feature-icon mx-auto"><i class="fas fa-search"></i></div>
                            <h3 class="fw-bold">Instant Certificate Verification</h3>
                            <p class="text-muted">Enter any official SAZUG certificate identification number to verify authenticity.</p>
                        </div>
                        <form method="GET" action="index.php" class="row g-3">
                            <div class="col-md-9">
                                <input type="text" name="verify" class="form-control form-control-lg rounded-pill px-4" placeholder="e.g. SAZUG/2026/000001" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-glow btn-lg text-white w-100 rounded-pill fw-semibold">Verify Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="text-center text-md-start">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-5">
                <h5 class="text-white fw-bold mb-3"><i class="fas fa-graduation-cap text-primary me-2"></i> SAZUG SRMS</h5>
                <p class="small text-muted">A fully optimized, production-grade Student Record Management System built for superior institutional administration and high availability.</p>
            </div>
            <div class="col-md-3">
                <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="#" class="text-decoration-none text-muted" data-bs-toggle="modal" data-bs-target="#loginModal">Staff Portal</a></li>
                    <li><a href="#" class="text-decoration-none text-muted" data-bs-toggle="modal" data-bs-target="#loginModal">Student Portal</a></li>
                    <li><a href="#verify-section" class="text-decoration-none text-muted">Verify Certificate</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-white fw-bold mb-3">Security & Compliance</h6>
                <p class="small text-muted mb-0">Secured with PDO Prepared Statements, CSRF Token validation, Argon2/Bcrypt hashing, and rigorous RBAC protocols.</p>
            </div>
        </div>
        <hr class="border-secondary opacity-25">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted pt-3">
            <p class="mb-0">&copy; <?= date('Y') ?> SAZUG Institution. All rights reserved.</p>
            <p class="mb-0">Production Environment v1.0.0</p>
        </div>
    </div>
</footer>

<!-- LOGIN MODAL -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold">Sign In to Portal</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="text-muted small mb-4">Enter your institutional credentials to access your role dashboard.</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Username or Admission No.</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light py-2" id="username" placeholder="e.g. superadmin" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0 bg-light py-2" id="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-glow text-white w-100 py-3 rounded-pill fw-bold" id="loginBtn">
                        Authenticate Securely <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
                    Swal.fire({ icon: 'success', title: 'Welcome Back', text: 'Authentication successful!', timer: 1200, showConfirmButton: false }).then(() => {
                        if (['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'].includes(data.role)) {
                            window.location.href = 'admin_spa.php';
                        } else if (data.role === 'Lecturer') {
                            window.location.href = 'lecturer_spa.php';
                        } else {
                            window.location.href = 'student_spa.php';
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Access Denied', text: data.message, confirmButtonColor: '#2563eb' });
                    btn.innerHTML = 'Authenticate Securely <i class="fas fa-arrow-right ms-2"></i>';
                    btn.disabled = false;
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to communicate with server.' });
                btn.innerHTML = 'Authenticate Securely <i class="fas fa-arrow-right ms-2"></i>';
                btn.disabled = false;
            }
        });
    }
</script>
</body>
</html>
