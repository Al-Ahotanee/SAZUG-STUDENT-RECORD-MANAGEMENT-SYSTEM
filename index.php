<?php
/**
 * SAZUG Student Record Management System - Public Enterprise Portal
 * Handles Premium Landing Page, Secure Authentication, and Anti-Forgery Verification.
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
            $verificationError = "The provided Certificate Identification Number could not be authenticated against the SAZUG encrypted registry.";
        }
    } catch (PDOException $e) {
        $verificationError = "Secure verification services are temporarily offline. Please try again shortly.";
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
            --brand-dark: #0B1121;
            --brand-primary: #3B82F6;
            --brand-primary-hover: #2563EB;
            --brand-accent: #8B5CF6;
            --bg-body: #FAFAFA;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow-soft: 0 20px 40px -15px rgba(0,0,0,0.05);
            --shadow-glow: 0 0 40px rgba(59, 130, 246, 0.3);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* 1. ULTRA-SLEEK NAVBAR */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--brand-dark) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
        }
        .nav-link {
            color: var(--text-muted) !important;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0 10px;
            transition: color 0.2s;
        }
        .nav-link:hover { color: var(--brand-primary) !important; }
        
        .btn-premium {
            background: var(--brand-dark);
            color: white !important;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid var(--brand-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-premium:hover {
            background: transparent;
            color: var(--brand-dark) !important;
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        /* 2. HERO SECTION (Mesh Gradient & Typography) */
        .hero-section {
            padding: 180px 0 120px;
            position: relative;
            background-color: #ffffff;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.1) 0px, transparent 50%);
            border-bottom: 1px solid rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--brand-primary);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -2px;
            color: var(--brand-dark);
            margin-bottom: 24px;
        }
        .text-gradient {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 40px;
            max-width: 600px;
        }
        
        /* 3. HERO VISUAL (CSS Floating Cards) */
        .hero-visual-container {
            position: relative;
            height: 400px;
            width: 100%;
            perspective: 1000px;
        }
        .floating-card {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 30px 60px -20px rgba(0,0,0,0.15);
            animation: float 6s ease-in-out infinite;
        }
        .card-1 { top: 10%; left: 10%; width: 280px; z-index: 3; animation-delay: 0s; transform: rotate(-5deg); }
        .card-2 { top: 40%; right: 5%; width: 250px; z-index: 2; animation-delay: -2s; transform: rotate(3deg); background: var(--brand-dark); color: white; border-color: rgba(255,255,255,0.1); }
        .card-3 { bottom: 0; left: 20%; width: 300px; z-index: 4; animation-delay: -4s; transform: rotate(2deg); }
        
        @keyframes float {
            0% { transform: translateY(0px) rotate(var(--rot, 0deg)); }
            50% { transform: translateY(-20px) rotate(var(--rot, 0deg)); }
            100% { transform: translateY(0px) rotate(var(--rot, 0deg)); }
        }

        /* 4. BENTO BOX FEATURES */
        .bento-section {
            padding: 100px 0;
            background: var(--bg-body);
        }
        .bento-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: var(--shadow-soft);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bento-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.08);
            border-color: rgba(59, 130, 246, 0.2);
        }
        .icon-box {
            width: 56px;
            height: 56px;
            background: rgba(59, 130, 246, 0.08);
            color: var(--brand-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 24px;
        }
        .icon-box.dark { background: var(--brand-dark); color: white; }
        .icon-box.purple { background: rgba(139, 92, 246, 0.1); color: var(--brand-accent); }

        /* 5. VERIFICATION SECTION */
        .verify-section {
            padding: 120px 0;
            background: var(--brand-dark);
            color: white;
            position: relative;
            overflow: hidden;
        }
        .verify-section::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, rgba(0,0,0,0) 50%);
            pointer-events: none;
        }
        .search-bar-wrapper {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 8px;
            display: flex;
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            max-width: 700px;
            margin: 0 auto;
            transition: border-color 0.3s;
        }
        .search-bar-wrapper:focus-within { border-color: var(--brand-primary); box-shadow: var(--shadow-glow); }
        .search-input {
            background: transparent;
            border: none;
            color: white;
            padding: 20px 24px;
            font-size: 1.1rem;
            width: 100%;
        }
        .search-input:focus { outline: none; box-shadow: none; }
        .search-input::placeholder { color: rgba(255,255,255,0.4); }
        .btn-search {
            background: var(--brand-primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0 32px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-search:hover { background: var(--brand-primary-hover); }

        /* 6. MODAL STYLING */
        .modal-backdrop.show { opacity: 0.6; backdrop-filter: blur(5px); }
        .custom-modal .modal-content {
            border: none;
            border-radius: 24px;
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .modal-left-pane { background: var(--brand-dark); color: white; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        .form-floating > .form-control { border-radius: 12px; border: 1px solid #E5E7EB; }
        .form-floating > .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }

        /* Footer */
        .footer { padding: 60px 0; border-top: 1px solid rgba(0,0,0,0.05); background: #ffffff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-layer-group brand-icon"></i>
            <span>SAZUG <span style="font-weight:400; color:var(--text-muted)">Enterprise</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navItems">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navItems">
            <ul class="navbar-nav align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="#platform">Platform</a></li>
                <li class="nav-item"><a class="nav-link" href="#verify">Verify Credential</a></li>
                <li class="nav-item ms-lg-3">
                    <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Access Portal <i class="fas fa-arrow-right ms-2" style="font-size: 0.8em;"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_GET['verify'])): ?>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center pt-5">
        <div class="card border-0 w-100 mt-5" style="max-width: 700px; border-radius: 24px; box-shadow: var(--shadow-soft);">
            <div class="card-header bg-white border-0 text-center pt-5 pb-3">
                <div class="mb-3">
                    <?php if ($verificationData): ?>
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 80px; height: 80px;">
                            <i class="fas fa-shield-check fa-3x"></i>
                        </div>
                        <h2 class="fw-bold mt-4 text-dark">Verified & Authentic</h2>
                        <p class="text-muted">This certificate record is permanently logged in the institutional registry.</p>
                    <?php else: ?>
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 80px; height: 80px;">
                            <i class="fas fa-shield-xmark fa-3x"></i>
                        </div>
                        <h2 class="fw-bold mt-4 text-dark">Record Not Found</h2>
                        <p class="text-danger"><?= htmlspecialchars($verificationError) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($verificationData): ?>
            <div class="card-body px-5 pb-5">
                <div class="bg-light rounded-4 p-4 mb-4 border" style="border-color: rgba(0,0,0,0.05) !important;">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Graduate Name</span>
                            <span class="fs-5 fw-bold text-dark"><?= htmlspecialchars($verificationData['full_name']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Matriculation Number</span>
                            <span class="fs-6 fw-semibold font-monospace bg-white border px-2 py-1 rounded"><?= htmlspecialchars($verificationData['matric_number']) ?></span>
                        </div>
                        <div class="col-12 border-top pt-3 mt-3"></div>
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Award</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($verificationData['programme']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Graduation Date</span>
                            <span class="fw-semibold text-dark"><?= date('F j, Y', strtotime($verificationData['graduation_date'])) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Department</span>
                            <span class="text-dark"><?= htmlspecialchars($verificationData['department']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="d-block text-muted small fw-semibold text-uppercase tracking-wide mb-1">Faculty</span>
                            <span class="text-dark"><?= htmlspecialchars($verificationData['faculty']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <span class="badge bg-dark bg-opacity-10 text-dark border px-3 py-2 rounded-pill font-monospace">
                        <i class="fas fa-fingerprint me-2"></i> ID: <?= htmlspecialchars($verificationData['certificate_number']) ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            <div class="card-footer bg-white border-top-0 text-center pb-5">
                <a href="index.php" class="btn btn-light border px-4 py-2 rounded-pill fw-semibold text-muted hover-dark"><i class="fas fa-arrow-left me-2"></i> Back to Gateway</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="hero-badge">
                        <span class="me-2 rounded-circle bg-primary" style="width:8px; height:8px; display:inline-block;"></span> 
                        SRMS Core v1.0 is now live
                    </div>
                    <h1 class="hero-title">
                        Modernize your <br>
                        <span class="text-gradient">academic infrastructure.</span>
                    </h1>
                    <p class="hero-subtitle">
                        A centralized, lightning-fast platform designed to manage the entire student lifecycle with bank-grade security and zero friction.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-premium" style="padding: 14px 32px; font-size: 1.05rem;" data-bs-toggle="modal" data-bs-target="#loginModal">
                            Enter Workspace
                        </button>
                        <a href="#verify" class="btn btn-light" style="padding: 14px 32px; font-size: 1.05rem; border-radius: 8px; font-weight: 600; border: 1px solid #E5E7EB; color: var(--text-main);">
                            Verify Document
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual-container">
                        <!-- Abstract UI Cards built with CSS -->
                        <div class="floating-card card-1" style="--rot: -5deg;">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3"><i class="fas fa-shield-alt"></i></div>
                                <div><h6 class="mb-0 fw-bold">RBAC Security</h6><small class="text-muted">Active Protection</small></div>
                            </div>
                            <div class="w-100 bg-light rounded-pill mb-2" style="height: 6px;"></div>
                            <div class="w-75 bg-light rounded-pill" style="height: 6px;"></div>
                        </div>
                        
                        <div class="floating-card card-2" style="--rot: 3deg;">
                            <h2 class="fw-bold mb-0">99.9%</h2>
                            <p class="text-white-50 small mb-3">System Uptime</p>
                            <div class="d-flex gap-1 align-items-end" style="height: 40px;">
                                <div class="bg-primary rounded-top w-100 h-50"></div>
                                <div class="bg-primary rounded-top w-100 h-75"></div>
                                <div class="bg-primary rounded-top w-100 h-100"></div>
                                <div class="bg-primary rounded-top w-100 h-50"></div>
                                <div class="bg-primary rounded-top w-100 h-75"></div>
                            </div>
                        </div>

                        <div class="floating-card card-3" style="--rot: 2deg;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0 fw-bold text-dark">Certificate Gen</h6>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Success</span>
                            </div>
                            <div class="d-flex align-items-center bg-light rounded-3 p-2 border">
                                <i class="fas fa-qrcode fa-2x text-dark opacity-50 me-3"></i>
                                <div><small class="d-block fw-bold text-dark">SAZUG/2026/001</small><small class="text-muted" style="font-size:0.7rem">Anti-Forgery QR Attached</small></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bento-section" id="platform">
        <div class="container">
            <div class="text-center mb-5 pb-3">
                <h2 class="fw-bold text-dark" style="font-size: 2.5rem; letter-spacing: -1px;">Built for scale and simplicity.</h2>
                <p class="text-muted fs-5">Everything you need to run an institution, out of the box.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="bento-card">
                        <div class="icon-box"><i class="fas fa-users-cog"></i></div>
                        <h4 class="fw-bold mb-3">Unified Records</h4>
                        <p class="text-muted">A single source of truth for demographics, enrollments, and academic standings across all faculties and departments.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bento-card">
                        <div class="icon-box dark"><i class="fas fa-fingerprint"></i></div>
                        <h4 class="fw-bold mb-3">Granular Access</h4>
                        <p class="text-muted">Strict Role-Based Access Control (RBAC) ensures Registrars, Lecturers, and Students only see what they are authorized to see.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bento-card">
                        <div class="icon-box purple"><i class="fas fa-certificate"></i></div>
                        <h4 class="fw-bold mb-3">Digital Credentials</h4>
                        <p class="text-muted">Automated PDF certificate generation with embedded cryptographic QR codes to permanently eliminate document forgery.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="verify-section" id="verify">
        <div class="container position-relative z-1 text-center">
            <i class="fas fa-shield-check text-primary mb-4" style="font-size: 3rem; opacity: 0.8;"></i>
            <h2 class="fw-bold mb-3" style="font-size: 2.5rem;">Global Verification Gateway</h2>
            <p class="text-white-50 mb-5 fs-5 max-w-2xl mx-auto">Employers and institutions can instantly cryptographically verify the authenticity of any SAZUG graduation document.</p>
            
            <form method="GET" action="index.php">
                <div class="search-bar-wrapper">
                    <input type="text" name="verify" class="search-input" placeholder="Enter Certificate ID (e.g., SAZUG/2026/000001)" required autocomplete="off">
                    <button type="submit" class="btn-search">Authenticate</button>
                </div>
            </form>
            <p class="mt-4 small text-white-50"><i class="fas fa-lock me-1"></i> Connections are encrypted via 256-bit TLS.</p>
        </div>
    </section>

<?php endif; ?>

<footer class="footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <i class="fas fa-layer-group text-primary"></i>
                    <span class="fw-bold text-dark tracking-tight">SAZUG SRMS</span>
                </div>
                <p class="text-muted small mb-0">&copy; <?= date('Y') ?> SAZUG Institution. Enterprise Production Engine.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="#" class="text-muted text-decoration-none small mx-2">Privacy Policy</a>
                <a href="#" class="text-muted text-decoration-none small mx-2">Terms of Service</a>
                <a href="#" class="text-muted text-decoration-none small mx-2">System Status</a>
            </div>
        </div>
    </div>
</footer>

<!-- PREMIUM LOGIN MODAL -->
<div class="modal fade custom-modal" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content overflow-hidden row flex-row m-0">
            <!-- Left Info Pane -->
            <div class="col-md-5 modal-left-pane d-none d-md-flex">
                <div class="mb-5">
                    <i class="fas fa-layer-group fa-2x text-primary mb-3"></i>
                    <h3 class="fw-bold">Welcome back.</h3>
                    <p class="text-white-50 mt-2">Sign in to your workspace to manage records, review analytics, and issue credentials.</p>
                </div>
                <div class="mt-auto">
                    <div class="d-flex align-items-center text-white-50 small bg-white bg-opacity-10 p-3 rounded-3">
                        <i class="fas fa-info-circle me-3 fa-lg"></i>
                        <span>Students: Use your Admission Number to access your portal.</span>
                    </div>
                </div>
            </div>
            <!-- Right Login Pane -->
            <div class="col-md-7 p-5 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4 class="fw-bold text-dark mb-0">Sign In</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="loginForm">
                    <div class="form-floating mb-4">
                        <input type="text" class="form-control" id="username" placeholder="Username" required>
                        <label for="username" class="text-muted">Username or Admission No.</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" placeholder="Password" required>
                        <label for="password" class="text-muted">Password</label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label text-muted small" for="remember">Remember this device</label>
                        </div>
                        <a href="#" class="text-primary text-decoration-none small fw-semibold">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-premium w-100 py-3 d-flex justify-content-center align-items-center gap-2" id="loginBtn">
                        Authenticate <i class="fas fa-arrow-right"></i>
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
            
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Authenticating...';
            btn.disabled = true;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', username: user, password: pass })
                });
                const data = await response.json();

                if (data.status) {
                    btn.innerHTML = '<i class="fas fa-check me-2"></i> Success';
                    btn.classList.replace('btn-premium', 'btn-success');
                    
                    setTimeout(() => {
                        if (['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'].includes(data.role)) {
                            window.location.href = 'admin_spa.php';
                        } else if (data.role === 'Lecturer') {
                            window.location.href = 'lecturer_spa.php';
                        } else {
                            window.location.href = 'student_spa.php';
                        }
                    }, 600);
                } else {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Authentication Failed', 
                        text: data.message,
                        confirmButtonColor: '#0B1121',
                        customClass: { popup: 'rounded-4' }
                    });
                    btn.innerHTML = 'Authenticate <i class="fas fa-arrow-right"></i>';
                    btn.disabled = false;
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to securely connect to authentication servers.' });
                btn.innerHTML = 'Authenticate <i class="fas fa-arrow-right"></i>';
                btn.disabled = false;
            }
        });
    }

    // Add scroll effect to navbar
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar-glass');
        if (window.scrollY > 20) {
            nav.style.boxShadow = '0 10px 30px -10px rgba(0,0,0,0.1)';
            nav.style.background = 'rgba(255, 255, 255, 0.95)';
        } else {
            nav.style.boxShadow = 'none';
            nav.style.background = 'rgba(255, 255, 255, 0.85)';
        }
    });
</script>
</body>
</html>
