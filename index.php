<?php
/**
 * SAZUG Student Record Management System
 * Public Landing Page, Login Portal & Certificate Verification
 */
session_start();

// Redirect authenticated users to their portals
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $role = $_SESSION['role'];
    if (in_array($role, ['Super Administrator', 'Administrator', 'Registrar', 'Department Officer'])) {
        header('Location: admin_spa.php'); exit;
    } elseif ($role === 'Lecturer') {
        header('Location: lecturer_spa.php'); exit;
    } else {
        header('Location: student_spa.php'); exit;
    }
}

// Public Certificate Verification
$verificationData  = null;
$verificationError = null;
$showVerify        = false;

if (isset($_GET['verify']) && !empty($_GET['verify'])) {
    $showVerify = true;
    $certNumber = trim($_GET['verify']);
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '12417';
    $db   = getenv('DB_NAME') ?: 'sazug_srms';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $ssl  = getenv('DB_SSL')  ?: 'false';
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4" . ($ssl==='true' ? ';sslmode=require' : '');
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare("
            SELECT c.certificate_number, c.issue_date, s.full_name, s.matric_number, s.admission_number,
                   s.graduation_date, p.name AS programme, p.type AS programme_type,
                   d.name AS department, f.name AS faculty
            FROM certificates c
            JOIN students s ON c.student_id = s.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            WHERE c.certificate_number = :cert LIMIT 1
        ");
        $stmt->execute(['cert' => $certNumber]);
        $verificationData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$verificationData) {
            $verificationError = 'Certificate number not found or invalid.';
        }
    } catch (PDOException $e) {
        $verificationError = 'Verification system temporarily unavailable.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG SRMS — Student Record Management System</title>
    <meta name="description" content="SAZUG Student Record Management System — Secure, efficient, and modern academic records management for Zamfara State polytechnics.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:      #0f3460;
            --primary-dark: #0a2540;
            --accent:       #3b82f6;
            --accent-light: #ff6b81;
            --gold:         #f5a623;
            --dark:         #0d1117;
            --light-bg:     #f8fafc;
            --card-shadow:  0 20px 60px rgba(0,0,0,0.08);
            --card-hover:   0 30px 80px rgba(0,0,0,0.14);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; color: #1a202c; background: #fff; overflow-x: hidden; }

        /* ===== NAVBAR ===== */
        .navbar-main {
            background: rgba(10, 37, 64, 0.97);
            backdrop-filter: blur(20px);
            padding: 14px 0;
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: all .3s;
        }
        .navbar-brand-text { font-size: 1.35rem; font-weight: 800; color: #fff; letter-spacing: -.5px; }
        .navbar-brand-text span { color: var(--gold); }
        .nav-link-custom { color: rgba(255,255,255,0.8) !important; font-weight: 500; font-size: .9rem; transition: color .2s; }
        .nav-link-custom:hover { color: var(--gold) !important; }
        .btn-login {
            background: var(--accent); color: #fff; border: none;
            padding: 9px 24px; border-radius: 50px; font-weight: 600; font-size: .88rem;
            transition: all .3s; letter-spacing: .3px;
        }
        .btn-login:hover { background: var(--accent-light); transform: translateY(-1px); box-shadow: 0 8px 25px rgba(233,69,96,.35); }

        /* ===== HERO ===== */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #0a2540 0%, #0f3460 45%, #162447 100%);
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .hero-particles {
            position: absolute; inset: 0; overflow: hidden; pointer-events: none;
        }
        .hero-particles .dot {
            position: absolute;
            border-radius: 50%;
            animation: floatDot linear infinite;
            opacity: .15;
        }
        @keyframes floatDot {
            0%   { transform: translateY(100vh) scale(0); opacity: 0; }
            10%  { opacity: .2; }
            90%  { opacity: .2; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }
        .hero-grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-glow {
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(233,69,96,.18) 0%, transparent 70%);
            right: -100px; top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(245,166,35,.12); border: 1px solid rgba(245,166,35,.3);
            color: var(--gold); padding: 7px 18px; border-radius: 50px; font-size: .82rem;
            font-weight: 600; margin-bottom: 24px; letter-spacing: .5px;
        }
        .hero-title { font-size: clamp(2.4rem,5vw,4rem); font-weight: 900; color: #fff; line-height: 1.1; margin-bottom: 20px; }
        .hero-title span { color: var(--gold); }
        .hero-sub { font-size: 1.1rem; color: rgba(255,255,255,.72); line-height: 1.8; max-width: 520px; margin-bottom: 36px; }
        .btn-hero-primary {
            background: var(--accent); color: #fff; border: none;
            padding: 14px 34px; border-radius: 50px; font-weight: 700; font-size: 1rem;
            transition: all .3s; box-shadow: 0 10px 40px rgba(233,69,96,.35);
        }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 50px rgba(233,69,96,.45); background: var(--accent-light); color: #fff; }
        .btn-hero-outline {
            background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.3);
            padding: 13px 34px; border-radius: 50px; font-weight: 600; font-size: 1rem;
            transition: all .3s; backdrop-filter: blur(8px);
        }
        .btn-hero-outline:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.6); color: #fff; }
        .hero-stats { display: flex; gap: 36px; margin-top: 50px; flex-wrap: wrap; }
        .hero-stat-item { text-align: left; }
        .hero-stat-num { font-size: 1.9rem; font-weight: 900; color: var(--gold); }
        .hero-stat-label { font-size: .8rem; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: 1px; }
        .hero-card-float {
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
            border-radius: 16px; padding: 24px;
            backdrop-filter: blur(20px);
            animation: cardFloat 4s ease-in-out infinite alternate;
        }
        @keyframes cardFloat { from { transform: translateY(0); } to { transform: translateY(-12px); } }
        .hero-dashboard-preview {
            background: rgba(15,52,96,.6); border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px; padding: 20px;
            backdrop-filter: blur(30px);
        }

        /* ===== CAROUSEL / FEATURES SLIDER ===== */
        .features-carousel-section {
            background: var(--primary-dark);
            padding: 60px 0;
            overflow: hidden;
        }
        .carousel-track {
            display: flex;
            gap: 20px;
            animation: scrollTrack 30s linear infinite;
            width: max-content;
        }
        .carousel-track:hover { animation-play-state: paused; }
        @keyframes scrollTrack {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .carousel-feature-card {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            padding: 22px 26px;
            min-width: 220px;
            color: #fff;
            flex-shrink: 0;
        }
        .carousel-feature-card .icon { font-size: 1.6rem; margin-bottom: 10px; }
        .carousel-feature-card .label { font-size: .85rem; font-weight: 600; opacity: .9; }

        /* ===== STATS BANNER ===== */
        .stats-banner { background: var(--light-bg); padding: 70px 0; }
        .stat-box {
            text-align: center;
            padding: 30px;
        }
        .stat-box .stat-num {
            font-size: 3rem; font-weight: 900; color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .stat-box .stat-label { color: #64748b; font-size: .92rem; font-weight: 500; margin-top: 6px; }
        .stat-divider { width: 1px; background: #e2e8f0; margin: 10px 0; }

        /* ===== FEATURES SECTION ===== */
        .features-section { padding: 100px 0; background: #fff; }
        .section-tag { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--accent); margin-bottom: 12px; }
        .section-title { font-size: clamp(1.8rem,3.5vw,2.8rem); font-weight: 800; color: var(--primary-dark); line-height: 1.2; }
        .section-title span { color: var(--accent); }
        .feature-card {
            background: var(--light-bg); border: 1px solid #e8edf3; border-radius: 20px;
            padding: 36px 30px; height: 100%;
            transition: all .35s cubic-bezier(.25,.8,.25,1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover);
            border-color: var(--primary);
            background: #fff;
        }
        .feature-icon-box {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 20px;
        }

        /* ===== ROLES SECTION ===== */
        .roles-section { padding: 90px 0; background: linear-gradient(135deg, #0a2540 0%, #0f3460 100%); }
        .role-card {
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px; padding: 32px 24px; text-align: center;
            transition: all .35s; color: #fff;
        }
        .role-card:hover {
            background: rgba(255,255,255,.12);
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(0,0,0,.25);
        }
        .role-icon {
            width: 70px; height: 70px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin: 0 auto 18px;
        }
        .role-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 10px; }
        .role-desc { font-size: .85rem; opacity: .7; line-height: 1.6; }

        /* ===== HOW IT WORKS ===== */
        .how-section { padding: 100px 0; background: var(--light-bg); }
        .step-card {
            background: #fff; border-radius: 20px; padding: 36px 28px;
            box-shadow: var(--card-shadow); height: 100%;
            position: relative; overflow: hidden;
        }
        .step-number {
            position: absolute; top: -10px; right: 20px;
            font-size: 5rem; font-weight: 900; color: var(--primary);
            opacity: .06; line-height: 1;
        }
        .step-icon { font-size: 2rem; margin-bottom: 16px; }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
            padding: 80px 0; position: relative; overflow: hidden;
        }
        .cta-section::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .cta-section .title { font-size: clamp(1.8rem,3.5vw,2.8rem); font-weight: 800; color: #fff; }
        .cta-section .sub { color: rgba(255,255,255,.85); font-size: 1.05rem; }
        .btn-cta-white {
            background: #fff; color: var(--accent); border: none;
            padding: 14px 36px; border-radius: 50px; font-weight: 700; font-size: 1rem;
            transition: all .3s; box-shadow: 0 10px 30px rgba(0,0,0,.15);
        }
        .btn-cta-white:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(0,0,0,.2); color: var(--primary); }

        /* ===== SECURITY SECTION ===== */
        .security-section { padding: 90px 0; background: #fff; }
        .security-badge {
            display: flex; align-items: center; gap: 14px;
            background: var(--light-bg); border: 1px solid #e2e8f0;
            border-radius: 14px; padding: 18px 22px;
            transition: all .3s;
        }
        .security-badge:hover { border-color: var(--primary); background: #f0f7ff; }
        .security-badge-icon { font-size: 1.5rem; flex-shrink: 0; }

        /* ===== VERIFY SECTION ===== */
        .verify-section { padding: 90px 0; background: var(--light-bg); }
        .verify-card {
            background: #fff; border-radius: 24px;
            box-shadow: var(--card-shadow); overflow: hidden;
        }
        .verify-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 32px 40px; color: #fff;
        }
        .verify-result-success { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 2px solid #10b981; border-radius: 16px; }
        .verify-result-fail { background: linear-gradient(135deg, #fff7ed, #fff7ed); border: 2px solid #f97316; border-radius: 16px; }

        /* ===== FOOTER ===== */
        .footer-main {
            background: #0a1628;
            color: rgba(255,255,255,.75);
            padding: 70px 0 0;
        }
        .footer-brand { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 16px; }
        .footer-brand span { color: var(--gold); }
        .footer-desc { font-size: .9rem; line-height: 1.8; color: rgba(255,255,255,.55); max-width: 280px; }
        .footer-heading { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--gold); margin-bottom: 20px; }
        .footer-link { display: block; color: rgba(255,255,255,.55); font-size: .9rem; margin-bottom: 12px; text-decoration: none; transition: color .2s; }
        .footer-link:hover { color: var(--gold); }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.06);
            margin-top: 50px; padding: 24px 0;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .footer-bottom-text { font-size: .82rem; color: rgba(255,255,255,.35); }
        .footer-social a {
            width: 36px; height: 36px; background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1); border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.5); font-size: .85rem; text-decoration: none;
            margin-left: 8px; transition: all .3s;
        }
        .footer-social a:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

        /* ===== LOGIN MODAL ===== */
        .modal-login .modal-content {
            border: none; border-radius: 24px; overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,.25);
        }
        .modal-login .modal-header { border: none; padding: 0; }
        .login-panel-left {
            background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 60%, #3b82f6 100%);
            padding: 50px 36px;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .login-panel-left::before {
            content: ''; position: absolute; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,.08), transparent);
            right: -80px; top: -80px; border-radius: 50%;
        }
        .login-panel-right { padding: 50px 40px; background: #fff; }
        .form-control-custom {
            background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px;
            padding: 13px 18px; font-size: .95rem; transition: all .25s;
        }
        .form-control-custom:focus {
            border-color: var(--primary); background: #fff;
            box-shadow: 0 0 0 4px rgba(15,52,96,.08);
        }
        .btn-login-submit {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; border: none; border-radius: 12px; padding: 14px;
            font-weight: 700; font-size: 1rem; width: 100%;
            transition: all .3s;
        }
        .btn-login-submit:hover { opacity: .92; transform: translateY(-1px); }
        .input-group-icon {
            background: #f8fafc; border: 2px solid #e2e8f0; border-right: none;
            border-radius: 12px 0 0 12px; padding: 0 14px; color: #94a3b8;
        }
        .input-group-icon + .form-control-custom { border-radius: 0 12px 12px 0; border-left: none; }

        /* ===== SCROLL ANIMATIONS ===== */
        .fade-up { opacity: 0; transform: translateY(30px); transition: all .6s cubic-bezier(.25,.8,.25,1); }
        .fade-up.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .hero-stats { gap: 20px; }
            .login-panel-left { display: none; }
            .hero-card-float { display: none; }
        }

        /* ===== REGISTRATION MODAL ===== */
        .modal-register .modal-content {
            border: none; border-radius: 24px; overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,.25); max-width: 640px; margin: 0 auto;
        }
        .modal-register .modal-dialog {
            max-width: 640px;
        }
        .register-header {
            background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 60%, #3b82f6 100%);
            padding: 28px 32px; color: #fff; position: relative; overflow: hidden;
        }
        .register-header::before {
            content: ''; position: absolute; width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,.08), transparent);
            right: -60px; top: -60px; border-radius: 50%;
        }
        .register-body { padding: 28px 32px 32px; background: #fff; max-height: 70vh; overflow-y: auto; }
        .register-tabs {
            display: flex; gap: 0; margin-bottom: 24px; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;
        }
        .register-tab {
            flex: 1; padding: 12px 16px; text-align: center; font-weight: 600; font-size: .88rem;
            cursor: pointer; transition: all .25s; background: #f8fafc; color: #64748b; border: none;
        }
        .register-tab:not(:last-child) { border-right: 2px solid #e2e8f0; }
        .register-tab.active {
            background: var(--primary); color: #fff;
        }
        .register-tab:hover:not(.active) { background: #eef2ff; color: var(--primary); }
        .register-form-group { margin-bottom: 14px; }
        .register-form-group label {
            display: block; font-weight: 600; font-size: .82rem; color: #374151; margin-bottom: 5px;
        }
        .register-form-group .form-control,
        .register-form-group .form-select {
            background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 11px 14px; font-size: .9rem; transition: all .25s; width: 100%;
        }
        .register-form-group .form-control:focus,
        .register-form-group .form-select:focus {
            border-color: var(--primary); background: #fff;
            box-shadow: 0 0 0 3px rgba(15,52,96,.08); outline: none;
        }
        .register-form-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .btn-register-submit {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; border: none; border-radius: 12px; padding: 13px;
            font-weight: 700; font-size: .95rem; width: 100%; transition: all .3s;
        }
        .btn-register-submit:hover { opacity: .92; transform: translateY(-1px); }
        .btn-register-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn-create-account-link {
            background: none; border: 2px solid var(--primary); color: var(--primary);
            border-radius: 12px; padding: 11px; font-weight: 600; font-size: .9rem;
            width: 100%; transition: all .3s; cursor: pointer;
        }
        .btn-create-account-link:hover { background: var(--primary); color: #fff; }
        .register-divider {
            display: flex; align-items: center; gap: 12px; margin: 18px 0;
            color: #94a3b8; font-size: .8rem;
        }
        .register-divider::before,
        .register-divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }
        @media (max-width: 576px) {
            .register-form-row { grid-template-columns: 1fr; gap: 0; }
            .register-body { padding: 20px 18px 24px; }
            .modal-register .modal-dialog { margin: 8px; }
        }
    </style>
</head>
<body>

<!-- ============================= NAVBAR ============================= -->
<nav class="navbar-main">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand-text text-decoration-none" href="#home">
            <i class="fas fa-university me-2" style="color:var(--gold)"></i>
            <span>SAZUG</span> SRMS
        </a>
        <div class="d-none d-lg-flex align-items-center gap-4">
            <a class="nav-link-custom text-decoration-none" href="#features">Features</a>
            <a class="nav-link-custom text-decoration-none" href="#roles">Access Levels</a>
            <a class="nav-link-custom text-decoration-none" href="#verify">Verify</a>
            <a class="nav-link-custom text-decoration-none" href="#security">Security</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link-custom text-decoration-none d-none d-md-inline" href="#verify" style="font-size:.84rem"><i class="fas fa-qrcode me-1"></i>Verify Cert</a>
            <button class="btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="fas fa-sign-in-alt me-1"></i> Portal Login
            </button>
        </div>
    </div>
</nav>

<!-- ============================= HERO SECTION ============================= -->
<section id="home" class="hero-section">
    <div class="hero-particles" id="heroParticles"></div>
    <div class="hero-grid-lines"></div>
    <div class="hero-glow"></div>
    <div class="container position-relative py-5 mt-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="fas fa-circle" style="font-size:.55rem;color:var(--gold)"></i>
                    Academic Year 2026/2027 — Now Active
                </div>
                <h1 class="hero-title">
                    The <span>Smarter Way</span> to Manage Student Records
                </h1>
                <p class="hero-sub">
                    The university's enterprise-grade Student Record Management System — unified admissions, 
                    academic tracking, certificate issuance, and role-based access control in one secure platform.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <button class="btn-hero-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-rocket me-2"></i>Access Portal
                    </button>
                    <a href="#features" class="btn-hero-outline text-decoration-none">
                        <i class="fas fa-play-circle me-2"></i>Explore Features
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <div class="hero-stat-num" data-count="6">0</div>
                        <div class="hero-stat-label">User Roles</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num" data-count="100">0</div>
                        <div class="hero-stat-label">% Secure</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num" data-count="24">0</div>
                        <div class="hero-stat-label">Data Points / Student</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-num" data-count="9">0</div>
                        <div class="hero-stat-label">Departments</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-card-float">
                    <div class="hero-dashboard-preview p-3">
                        <div class="d-flex align-items-center mb-3 gap-2">
                            <div style="width:10px;height:10px;border-radius:50%;background:#f97316"></div>
                            <div style="width:10px;height:10px;border-radius:50%;background:#f5a623"></div>
                            <div style="width:10px;height:10px;border-radius:50%;background:#22c55e"></div>
                            <span style="color:rgba(255,255,255,.4);font-size:.75rem;margin-left:8px">Admin Portal</span>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div style="background:rgba(15,52,96,.8);border-radius:10px;padding:14px;border:1px solid rgba(255,255,255,.1)">
                                    <div style="color:rgba(255,255,255,.5);font-size:.7rem;margin-bottom:4px">Total Students</div>
                                    <div style="color:#fff;font-size:1.4rem;font-weight:800">1,284</div>
                                    <div style="color:#22c55e;font-size:.7rem"><i class="fas fa-arrow-up"></i> 12% this session</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background:rgba(233,69,96,.2);border-radius:10px;padding:14px;border:1px solid rgba(233,69,96,.3)">
                                    <div style="color:rgba(255,255,255,.5);font-size:.7rem;margin-bottom:4px">Active Students</div>
                                    <div style="color:#fff;font-size:1.4rem;font-weight:800">1,180</div>
                                    <div style="color:#22c55e;font-size:.7rem"><i class="fas fa-arrow-up"></i> 3% this month</div>
                                </div>
                            </div>
                        </div>
                        <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:14px;border:1px solid rgba(255,255,255,.08)">
                            <div style="color:rgba(255,255,255,.5);font-size:.7rem;margin-bottom:10px">Enrollment by Dept.</div>
                            <?php $bars = [['Computer Eng',85],['Business Admin',72],['Accountancy',60],['Electrical Eng',50]]; foreach($bars as [$n,$w]): ?>
                            <div class="mb-2">
                                <div style="color:rgba(255,255,255,.7);font-size:.68rem;margin-bottom:3px"><?= $n ?></div>
                                <div style="background:rgba(255,255,255,.06);border-radius:20px;height:6px">
                                    <div style="background:linear-gradient(90deg,#3b82f6,#0f3460);width:<?= $w ?>%;height:100%;border-radius:20px"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= FEATURES CAROUSEL ============================= -->
<div class="features-carousel-section">
    <div style="overflow:hidden;position:relative">
        <div class="carousel-track" id="carouselTrack">
            <?php
            $features = [
                ['icon'=>'fa-user-graduate','label'=>'Student Admissions'],
                ['icon'=>'fa-id-card','label'=>'Matriculation Registry'],
                ['icon'=>'fa-building','label'=>'Faculty Management'],
                ['icon'=>'fa-sitemap','label'=>'Department Structure'],
                ['icon'=>'fa-book','label'=>'Programme Catalogue'],
                ['icon'=>'fa-chalkboard-teacher','label'=>'Lecturer Portal'],
                ['icon'=>'fa-certificate','label'=>'Certificate Issuance'],
                ['icon'=>'fa-qrcode','label'=>'Anti-Forgery QR Codes'],
                ['icon'=>'fa-shield-alt','label'=>'RBAC Security'],
                ['icon'=>'fa-chart-bar','label'=>'Analytics Dashboard'],
                ['icon'=>'fa-search','label'=>'Advanced Search'],
                ['icon'=>'fa-file-alt','label'=>'Document Upload'],
                ['icon'=>'fa-history','label'=>'Audit Trail Logs'],
                ['icon'=>'fa-lock','label'=>'Account Lockout'],
                ['icon'=>'fa-graduation-cap','label'=>'Graduation Workflow'],
            ];
            // Duplicate for infinite scroll
            $allFeatures = array_merge($features, $features);
            foreach ($allFeatures as $f): ?>
            <div class="carousel-feature-card">
                <div class="icon" style="color:var(--gold)"><i class="fas <?= $f['icon'] ?>"></i></div>
                <div class="label"><?= $f['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================= STATS BANNER ============================= -->
<section class="stats-banner">
    <div class="container">
        <div class="row g-0 text-center">
            <div class="col-6 col-md-3 fade-up">
                <div class="stat-box">
                    <div class="stat-num">6+</div>
                    <div class="stat-label">Role-Based Access Levels</div>
                </div>
            </div>
            <div class="col-6 col-md-3 fade-up">
                <div class="stat-box">
                    <div class="stat-num">24</div>
                    <div class="stat-label">Student Data Fields Tracked</div>
                </div>
            </div>
            <div class="col-6 col-md-3 fade-up">
                <div class="stat-box">
                    <div class="stat-num">100%</div>
                    <div class="stat-label">CSRF & XSS Protected</div>
                </div>
            </div>
            <div class="col-6 col-md-3 fade-up">
                <div class="stat-box">
                    <div class="stat-num">∞</div>
                    <div class="stat-label">Audit Trail History</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= FEATURES SECTION ============================= -->
<section id="features" class="features-section">
    <div class="container">
        <div class="row justify-content-center text-center mb-60">
            <div class="col-lg-7 mb-5 fade-up">
                <div class="section-tag">Platform Capabilities</div>
                <h2 class="section-title">Everything you need to run a <span>modern institution</span></h2>
                <p class="text-muted mt-16 fs-6">From admissions to graduation — a complete lifecycle management platform built for Nigerian polytechnics and universities.</p>
            </div>
        </div>
        <div class="row g-4">
            <?php
            $cards = [
                ['icon'=>'fa-user-graduate','bg'=>'#e8f0fe','color'=>'#4285f4','title'=>'Student Lifecycle Management','desc'=>'Full CRUD from admission to graduation. Track status, update details, suspend, reinstate, or archive students with complete audit history.'],
                ['icon'=>'fa-certificate','bg'=>'#dbeafe','color'=>'#3b82f6','title'=>'Certificate Generation & QR Verification','desc'=>'Issue anti-forgery graduation certificates with unique QR codes. Anyone can verify authenticity via the public verification portal.'],
                ['icon'=>'fa-shield-alt','bg'=>'#e6f4ea','color'=>'#34a853','title'=>'Role-Based Access Control','desc'=>'Six distinct roles — Super Admin, Admin, Registrar, Department Officer, Lecturer, and Student — each with precisely scoped permissions.'],
                ['icon'=>'fa-chart-bar','bg'=>'#fef3e2','color'=>'#f5a623','title'=>'Analytics & Dashboard','desc'=>'Real-time dashboard with enrollment charts, department breakdowns, level distributions, and recent activity feeds.'],
                ['icon'=>'fa-university','bg'=>'#f0e6ff','color'=>'#9c27b0','title'=>'Academic Structure','desc'=>'Manage faculties, departments, programmes, courses, and academic sessions in a fully relational hierarchy.'],
                ['icon'=>'fa-history','bg'=>'#e8f5f8','color'=>'#00acc1','title'=>'Full Audit Logging','desc'=>'Every action is recorded — login attempts, record changes, status updates — with IP address and timestamp for full accountability.'],
            ];
            foreach ($cards as $c): ?>
            <div class="col-md-6 col-lg-4 fade-up">
                <div class="feature-card">
                    <div class="feature-icon-box" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>">
                        <i class="fas <?= $c['icon'] ?>"></i>
                    </div>
                    <h5 style="font-weight:700;color:#1a202c;margin-bottom:10px"><?= $c['title'] ?></h5>
                    <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin:0"><?= $c['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================= ROLES SECTION ============================= -->
<section id="roles" class="roles-section">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-7 fade-up">
                <div class="section-tag" style="color:var(--gold)">Access Control</div>
                <h2 class="section-title" style="color:#fff">Six distinct <span style="color:var(--gold)">user roles</span>, one unified system</h2>
                <p style="color:rgba(255,255,255,.6);font-size:.95rem;margin-top:12px">Every user type has precisely tailored access to exactly what they need — nothing more, nothing less.</p>
            </div>
        </div>
        <div class="row g-4">
            <?php
            $roles = [
                ['icon'=>'fa-crown','bg'=>'rgba(245,166,35,.15)','color'=>'#f5a623','title'=>'Super Administrator','desc'=>'Full system control. Manages all users, settings, data, and security configurations.'],
                ['icon'=>'fa-user-shield','bg'=>'rgba(59,130,246,.15)','color'=>'#3b82f6','title'=>'Administrator','desc'=>'Oversees staff, students, and academic structure. Creates reports and manages accounts.'],
                ['icon'=>'fa-file-signature','bg'=>'rgba(52,168,83,.15)','color'=>'#34a853','title'=>'Registrar','desc'=>'Handles student admissions, record updates, status changes, and certificate issuance.'],
                ['icon'=>'fa-building','bg'=>'rgba(66,133,244,.15)','color'=>'#4285f4','title'=>'Department Officer','desc'=>'Views and manages student records within their assigned department.'],
                ['icon'=>'fa-chalkboard-teacher','bg'=>'rgba(156,39,176,.15)','color'=>'#9c27b0','title'=>'Lecturer','desc'=>'Accesses the student roster for their courses. Read-only view of academic profiles.'],
                ['icon'=>'fa-user-graduate','bg'=>'rgba(0,172,193,.15)','color'=>'#00acc1','title'=>'Student','desc'=>'Self-service portal to view personal academic profile, documents, and digital certificate.'],
            ];
            foreach ($roles as $r): ?>
            <div class="col-md-6 col-lg-4 fade-up">
                <div class="role-card">
                    <div class="role-icon" style="background:<?= $r['bg'] ?>;color:<?= $r['color'] ?>">
                        <i class="fas <?= $r['icon'] ?>"></i>
                    </div>
                    <div class="role-title"><?= $r['title'] ?></div>
                    <div class="role-desc"><?= $r['desc'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================= HOW IT WORKS ============================= -->
<section class="how-section">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-7 fade-up">
                <div class="section-tag">Simple Workflow</div>
                <h2 class="section-title">From admission to <span>graduation</span> in four steps</h2>
            </div>
        </div>
        <div class="row g-4">
            <?php
            $steps = [
                ['num'=>'1','icon'=>'fa-user-plus','color'=>'#4285f4','title'=>'Admit a Student','desc'=>'Registrar creates the student profile with all academic and personal details. A login account is automatically provisioned.'],
                ['num'=>'2','icon'=>'fa-id-card','color'=>'#34a853','title'=>'Assign Matric Number','desc'=>'Once admitted, the registrar assigns the official matriculation number to activate the student\'s academic record.'],
                ['num'=>'3','icon'=>'fa-graduation-cap','color'=>'#f5a623','title'=>'Track Academic Progress','desc'=>'Level, session, status, and programme changes are updated throughout the student\'s academic lifecycle with full audit logs.'],
                ['num'=>'4','icon'=>'fa-certificate','color'=>'#3b82f6','title'=>'Issue Digital Certificate','desc'=>'Upon graduation, a tamper-proof certificate with QR code is generated. Anyone can verify its authenticity online.'],
            ];
            foreach ($steps as $s): ?>
            <div class="col-md-6 col-lg-3 fade-up">
                <div class="step-card">
                    <div class="step-number"><?= $s['num'] ?></div>
                    <div class="step-icon" style="color:<?= $s['color'] ?>"><i class="fas <?= $s['icon'] ?>"></i></div>
                    <h6 style="font-weight:700;margin-bottom:10px"><?= $s['title'] ?></h6>
                    <p style="color:#64748b;font-size:.875rem;line-height:1.7;margin:0"><?= $s['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================= CTA SECTION ============================= -->
<section class="cta-section">
    <div class="container position-relative text-center">
        <div class="fade-up">
            <p class="section-tag" style="color:rgba(255,255,255,.6)">Get Started Today</p>
            <h2 class="title mb-3">Ready to modernize your academic records?</h2>
            <p class="sub mb-5">Log into the SAZUG SRMS portal now and experience enterprise-grade academic records management.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <button class="btn-cta-white" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Portal
                </button>
                <a href="#verify" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-600">
                    <i class="fas fa-search me-2"></i>Verify Certificate
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================= SECURITY SECTION ============================= -->
<section id="security" class="security-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 fade-up">
                <div class="section-tag">Enterprise Security</div>
                <h2 class="section-title">Built secure from the <span>ground up</span></h2>
                <p class="text-muted mt-3" style="font-size:.95rem;line-height:1.8">
                    SAZUG SRMS is hardened against modern web threats. Every request is validated, every session is protected, and every action is logged.
                </p>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <span class="badge py-2 px-3 rounded-pill" style="background:#e8f0fe;color:#4285f4;font-weight:600">PHP 8.3+</span>
                    <span class="badge py-2 px-3 rounded-pill" style="background:#e6f4ea;color:#34a853;font-weight:600">PDO Prepared Statements</span>
                    <span class="badge py-2 px-3 rounded-pill" style="background:#dbeafe;color:#3b82f6;font-weight:600">RBAC</span>
                    <span class="badge py-2 px-3 rounded-pill" style="background:#fef3e2;color:#f5a623;font-weight:600">MySQL 8</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php
                    $sec = [
                        ['icon'=>'fa-shield-alt','color'=>'#4285f4','bg'=>'#e8f0fe','title'=>'CSRF Protection','desc'=>'Every state-changing request requires a verified CSRF token'],
                        ['icon'=>'fa-lock','color'=>'#34a853','bg'=>'#e6f4ea','title'=>'Secure Sessions','desc'=>'HttpOnly, SameSite=Strict cookies with 30-min timeout'],
                        ['icon'=>'fa-key','color'=>'#f5a623','bg'=>'#fef3e2','title'=>'Password Hashing','desc'=>'bcrypt hashing — passwords never stored in plain text'],
                        ['icon'=>'fa-ban','color'=>'#3b82f6','bg'=>'#dbeafe','title'=>'Account Lockout','desc'=>'5 failed attempts triggers automatic 30-minute lockout'],
                        ['icon'=>'fa-code','color'=>'#9c27b0','bg'=>'#f0e6ff','title'=>'XSS Prevention','desc'=>'All output HTML-escaped; input sanitized via PDO params'],
                        ['icon'=>'fa-history','color'=>'#00acc1','bg'=>'#e8f5f8','title'=>'Audit Logging','desc'=>'Every action recorded with user, IP, and timestamp'],
                        ['icon'=>'fa-file-upload','color'=>'#ff5722','bg'=>'#fbe9e7','title'=>'File Validation','desc'=>'MIME-type verified uploads with size limits enforced'],
                        ['icon'=>'fa-user-lock','color'=>'#607d8b','bg'=>'#eceff1','title'=>'Permission Middleware','desc'=>'Role enforcement on every API endpoint, server-side'],
                    ];
                    foreach ($sec as $s): ?>
                    <div class="col-md-6 fade-up">
                        <div class="security-badge">
                            <div class="security-badge-icon" style="color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>;width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center">
                                <i class="fas <?= $s['icon'] ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.9rem;color:#1a202c"><?= $s['title'] ?></div>
                                <div style="font-size:.78rem;color:#64748b"><?= $s['desc'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= CERTIFICATE VERIFY SECTION ============================= -->
<section id="verify" class="verify-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 fade-up">
                <div class="verify-card">
                    <div class="verify-header">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:50px;height:50px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem"><i class="fas fa-certificate"></i></div>
                            <div>
                                <h4 class="mb-1 fw-bold">Certificate Verification</h4>
                                <p class="mb-0" style="opacity:.75;font-size:.88rem">Instantly verify the authenticity of a university graduation certificate</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 p-md-5">
                        <?php if ($verificationData): ?>
                        <div class="verify-result-success p-4 mb-4 text-center">
                            <div style="font-size:3rem;color:#10b981;margin-bottom:10px"><i class="fas fa-check-circle"></i></div>
                            <h5 class="text-success fw-bold mb-1">Authentic Certificate</h5>
                            <p class="text-muted mb-0 small">This certificate is genuine and verified</p>
                        </div>
                        <table class="table table-borderless">
                            <?php $rows = [
                                'Certificate Number' => $verificationData['certificate_number'],
                                'Full Name'          => $verificationData['full_name'],
                                'Matric Number'      => $verificationData['matric_number'] ?? 'N/A',
                                'Programme'          => $verificationData['programme'] . ' (' . $verificationData['programme_type'] . ')',
                                'Department'         => $verificationData['department'],
                                'Faculty'            => $verificationData['faculty'],
                                'Graduation Date'    => $verificationData['graduation_date'] ? date('F j, Y', strtotime($verificationData['graduation_date'])) : 'N/A',
                                'Issue Date'         => date('F j, Y', strtotime($verificationData['issue_date'])),
                            ];
                            foreach ($rows as $label => $val): ?>
                            <tr>
                                <td class="text-muted fw-500 pe-3" style="font-size:.88rem;width:40%"><?= $label ?></td>
                                <td class="fw-bold" style="font-size:.9rem"><?= htmlspecialchars($val) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php elseif ($verificationError): ?>
                        <div class="verify-result-fail p-4 mb-4 text-center">
                            <div style="font-size:3rem;color:#f97316;margin-bottom:10px"><i class="fas fa-times-circle"></i></div>
                            <h5 class="text-danger fw-bold mb-1">Certificate Not Found</h5>
                            <p class="text-muted mb-0 small"><?= htmlspecialchars($verificationError) ?></p>
                        </div>
                        <form method="GET" action="#verify">
                            <input type="text" name="verify" class="form-control-custom form-control mb-3" placeholder="Try another certificate number..." value="">
                            <button class="btn btn-primary w-100 rounded-pill py-2" type="submit">Search Again</button>
                        </form>
                        <?php else: ?>
                        <p class="text-muted mb-4" style="font-size:.93rem;line-height:1.7">
                            Enter the unique certificate number found on a SAZUG graduation certificate to verify its authenticity. This system confirms whether the document is genuine and was officially issued by the university.
                        </p>
                        <form method="GET" action="#verify">
                            <div class="mb-3">
                                <label class="form-label fw-600" style="font-size:.88rem">Certificate Number</label>
                                <input type="text" name="verify" class="form-control form-control-lg" style="border-radius:12px;border:2px solid #e2e8f0" placeholder="e.g. SAZUG-CERT-2024-A1B2C3D4" required>
                            </div>
                            <button type="submit" class="btn-login-submit btn w-100 mt-2" style="border-radius:12px;padding:13px;background:linear-gradient(135deg,#0f3460,#3b82f6);color:#fff;font-weight:700;font-size:.95rem">
                                <i class="fas fa-search me-2"></i>Verify Certificate
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= FOOTER ============================= -->
<footer class="footer-main">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="footer-brand"><i class="fas fa-university me-2" style="color:var(--gold)"></i><span>SAZUG</span> SRMS</div>
                <p class="footer-desc">The official Student Record Management System for Zamfara State polytechnics — secure, modern, and built for Nigerian higher education.</p>
                <div class="footer-social mt-4">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-heading">System</div>
                <a class="footer-link" href="#features">Features</a>
                <a class="footer-link" href="#roles">Access Roles</a>
                <a class="footer-link" href="#security">Security</a>
                <a class="footer-link" href="#verify">Verify Cert</a>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-heading">Portals</div>
                <a class="footer-link" href="admin_spa.php">Admin Portal</a>
                <a class="footer-link" href="lecturer_spa.php">Lecturer Portal</a>
                <a class="footer-link" href="student_spa.php">Student Portal</a>
                <a class="footer-link" href="install.php">System Setup</a>
            </div>
            <div class="col-lg-4">
                <div class="footer-heading">Contact & Support</div>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-map-marker-alt mt-1" style="color:var(--gold);flex-shrink:0"></i>
                        <span style="font-size:.88rem;color:rgba(255,255,255,.5)">Zamfara State Polytechnic, Kaura Namoda, Zamfara State, Nigeria</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-envelope" style="color:var(--gold)"></i>
                        <span style="font-size:.88rem;color:rgba(255,255,255,.5)">ict@sazugpoly.edu.ng</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-phone" style="color:var(--gold)"></i>
                        <span style="font-size:.88rem;color:rgba(255,255,255,.5)">+234 800 000 0000</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span class="footer-bottom-text">© <?= date('Y') ?> SAZUG Student Record Management System. All rights reserved.</span>
            <span class="footer-bottom-text">Built with <i class="fas fa-heart" style="color:var(--accent)"></i> for Nigerian Education</span>
        </div>
    </div>
</footer>

<!-- ============================= LOGIN MODAL ============================= -->
<div class="modal fade modal-login" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="row g-0">
                <!-- Left panel -->
                <div class="col-lg-5 login-panel-left">
                    <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal" style="top:16px;right:16px"></button>
                    <div class="position-relative">
                        <div style="font-size:2.8rem;font-weight:900;color:#fff;line-height:1.1;margin-bottom:16px">Secure Portal Access</div>
                        <p style="color:rgba(255,255,255,.7);font-size:.93rem;line-height:1.7">Log in with your assigned credentials. Your session is protected with industry-standard security protocols.</p>
                        <div class="mt-4 d-flex flex-column gap-3">
                            <?php $tips = [['fa-shield-alt','CSRF token protected'],['fa-lock','AES session encryption'],['fa-history','Full audit logging'],['fa-ban','Auto account lockout']]; foreach($tips as [$icon,$text]): ?>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas <?= $icon ?>" style="color:var(--gold);width:16px;text-align:center"></i>
                                <span style="color:rgba(255,255,255,.65);font-size:.82rem"><?= $text ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Right panel -->
                <div class="col-lg-7 login-panel-right">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold mb-0" style="color:#1a202c">Sign In</h5>
                        <button type="button" class="btn-close d-lg-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div id="loginAlert" class="alert d-none mb-3 rounded-3 border-0" role="alert"></div>
                    <form id="loginForm">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:600;font-size:.85rem;color:#374151">Username / Admission No.</label>
                            <div class="input-group">
                                <span class="input-group-icon"><i class="fas fa-user"></i></span>
                                <input type="text" id="loginUsername" class="form-control form-control-custom" placeholder="Enter your username" required autocomplete="username">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:.85rem;color:#374151">Password</label>
                            <div class="input-group">
                                <span class="input-group-icon"><i class="fas fa-lock"></i></span>
                                <input type="password" id="loginPassword" class="form-control form-control-custom" placeholder="Enter your password" required autocomplete="current-password">
                                <button class="btn" type="button" id="togglePwd" style="border:2px solid #e2e8f0;border-left:none;border-radius:0 12px 12px 0;background:#f8fafc;color:#94a3b8;transition:all .2s">
                                    <i class="fas fa-eye" id="togglePwdIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;font-size:.85rem;color:#374151">
                                <input type="checkbox" id="rememberMe" class="form-check-input m-0"> Remember me
                            </label>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" data-bs-dismiss="modal" style="font-size:.82rem;color:var(--accent);text-decoration:none;font-weight:600">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn-login-submit" id="loginBtn">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In Securely
                        </button>
                    </form>
                    <div class="register-divider">or</div>
                    <button type="button" class="btn-create-account-link" id="openRegisterModalBtn" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                    <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <p class="mb-1" style="font-size:.78rem;color:#64748b;font-weight:600">DEFAULT CREDENTIALS</p>
                        <p class="mb-0" style="font-size:.8rem;color:#374151">Username: <code>superadmin</code> &nbsp;|&nbsp; Password: <code>Admin@123</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================= FORGOT PASSWORD MODAL ============================= -->
<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4" style="font-size:.9rem">Enter your username or email address. A reset token will be generated — contact the system administrator to obtain it.</p>
                <div id="forgotAlert" class="alert d-none mb-3"></div>
                <form id="forgotForm">
                    <div class="mb-3">
                        <input type="text" id="forgotUsername" class="form-control" style="border-radius:12px;padding:12px 16px;border:2px solid #e2e8f0" placeholder="Username or email" required>
                    </div>
                    <button type="submit" class="btn w-100 py-2 fw-700" style="background:var(--primary);color:#fff;border-radius:12px">
                        <i class="fas fa-paper-plane me-2"></i>Request Reset Token
                    </button>
                </form>
                <hr class="my-4">
                <div id="resetSection">
                    <p class="fw-600 mb-2" style="font-size:.85rem">Have a reset token?</p>
                    <form id="resetForm">
                        <div class="mb-2">
                            <input type="text" id="resetToken" class="form-control mb-2" style="border-radius:10px;border:2px solid #e2e8f0;padding:10px 14px;font-size:.88rem" placeholder="Reset token">
                        </div>
                        <div class="mb-2">
                            <input type="password" id="resetNewPwd" class="form-control" style="border-radius:10px;border:2px solid #e2e8f0;padding:10px 14px;font-size:.88rem" placeholder="New password (min. 6 chars)">
                        </div>
                        <button type="submit" class="btn w-100 py-2" style="background:var(--accent);color:#fff;border-radius:10px;font-weight:600">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================= REGISTRATION MODAL ============================= -->
<div class="modal fade modal-register" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="register-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:46px;height:46px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <h5 class="mb-0 fw-bold" style="color:#fff;font-size:1.15rem">Create Account</h5>
                            <p class="mb-0" style="opacity:.7;font-size:.82rem">Register as a new student or lecturer</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="register-body">
                <div id="registerAlert" class="alert d-none mb-3 rounded-3 border-0" role="alert"></div>

                <!-- Tabs -->
                <div class="register-tabs">
                    <button type="button" class="register-tab active" id="tabStudent" data-tab="student">
                        <i class="fas fa-user-graduate me-1"></i> Student Registration
                    </button>
                    <button type="button" class="register-tab" id="tabLecturer" data-tab="lecturer">
                        <i class="fas fa-chalkboard-teacher me-1"></i> Lecturer Registration
                    </button>
                </div>

                <!-- Student Registration Form -->
                <form id="studentRegisterForm" class="register-form">
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuFullName">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="stuFullName" name="full_name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="register-form-group">
                            <label for="stuUsername">Username <span class="text-danger">*</span></label>
                            <input type="text" id="stuUsername" name="username" class="form-control" placeholder="Choose username" required>
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuEmail">Email <span class="text-danger">*</span></label>
                            <input type="email" id="stuEmail" name="email" class="form-control" placeholder="email@example.com" required>
                        </div>
                        <div class="register-form-group">
                            <label for="stuPhone">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="stuPhone" name="phone" class="form-control" placeholder="+234..." required>
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuPassword">Password <span class="text-danger">*</span></label>
                            <input type="password" id="stuPassword" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="register-form-group">
                            <label for="stuConfirmPassword">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" id="stuConfirmPassword" name="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="6">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuGender">Gender</label>
                            <select id="stuGender" name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="register-form-group">
                            <label for="stuDob">Date of Birth</label>
                            <input type="date" id="stuDob" name="date_of_birth" class="form-control">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuNationality">Nationality</label>
                            <input type="text" id="stuNationality" name="nationality" class="form-control" value="Nigerian">
                        </div>
                        <div class="register-form-group">
                            <label for="stuState">State of Origin</label>
                            <input type="text" id="stuState" name="state_of_origin" class="form-control" placeholder="State of origin">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuLga">LGA</label>
                            <input type="text" id="stuLga" name="lga" class="form-control" placeholder="Local Government Area">
                        </div>
                        <div class="register-form-group">
                            <label for="stuReligion">Religion</label>
                            <input type="text" id="stuReligion" name="religion" class="form-control" placeholder="Religion">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuMarital">Marital Status</label>
                            <select id="stuMarital" name="marital_status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div class="register-form-group">
                            <label for="stuPob">Place of Birth</label>
                            <input type="text" id="stuPob" name="place_of_birth" class="form-control" placeholder="Place of birth">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuHomeTown">Home Town</label>
                            <input type="text" id="stuHomeTown" name="home_town" class="form-control" placeholder="Home town">
                        </div>
                        <div class="register-form-group"></div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuFaculty">Faculty <span class="text-danger">*</span></label>
                            <select id="stuFaculty" name="faculty_id" class="form-select" required>
                                <option value="">Loading faculties...</option>
                            </select>
                        </div>
                        <div class="register-form-group">
                            <label for="stuDepartment">Department <span class="text-danger">*</span></label>
                            <select id="stuDepartment" name="department_id" class="form-select" required disabled>
                                <option value="">Select faculty first</option>
                            </select>
                        </div>
                    </div>
                    <div class="register-form-group">
                        <label for="stuProgramme">Programme <span class="text-danger">*</span></label>
                        <select id="stuProgramme" name="programme_id" class="form-select" required disabled>
                            <option value="">Select department first</option>
                        </select>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="stuGuardianName">Guardian Name</label>
                            <input type="text" id="stuGuardianName" name="guardian_name" class="form-control" placeholder="Guardian/Sponsor name">
                        </div>
                        <div class="register-form-group">
                            <label for="stuGuardianPhone">Guardian Phone</label>
                            <input type="tel" id="stuGuardianPhone" name="guardian_phone" class="form-control" placeholder="Guardian phone number">
                        </div>
                    </div>
                    <button type="submit" class="btn-register-submit mt-2" id="stuRegisterBtn">
                        <i class="fas fa-user-plus me-2"></i>Register as Student
                    </button>
                </form>

                <!-- Lecturer Registration Form -->
                <form id="lecturerRegisterForm" class="register-form" style="display:none">
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecFullName">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="lecFullName" name="full_name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="register-form-group">
                            <label for="lecUsername">Username <span class="text-danger">*</span></label>
                            <input type="text" id="lecUsername" name="username" class="form-control" placeholder="Choose username" required>
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecEmail">Email <span class="text-danger">*</span></label>
                            <input type="email" id="lecEmail" name="email" class="form-control" placeholder="email@example.com" required>
                        </div>
                        <div class="register-form-group">
                            <label for="lecPhone">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="lecPhone" name="phone" class="form-control" placeholder="+234..." required>
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecPassword">Password <span class="text-danger">*</span></label>
                            <input type="password" id="lecPassword" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="register-form-group">
                            <label for="lecConfirmPassword">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" id="lecConfirmPassword" name="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="6">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecGender">Gender</label>
                            <select id="lecGender" name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="register-form-group">
                            <label for="lecQualification">Qualification</label>
                            <input type="text" id="lecQualification" name="qualification" class="form-control" placeholder="e.g. Ph.D, M.Sc, B.Sc">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecSpecialization">Specialization</label>
                            <input type="text" id="lecSpecialization" name="specialization" class="form-control" placeholder="Area of specialization">
                        </div>
                        <div class="register-form-group">
                            <label for="lecStaffId">Staff ID</label>
                            <input type="text" id="lecStaffId" name="staff_id" class="form-control" placeholder="Staff ID number">
                        </div>
                    </div>
                    <div class="register-form-row">
                        <div class="register-form-group">
                            <label for="lecFaculty">Faculty <span class="text-danger">*</span></label>
                            <select id="lecFaculty" name="faculty_id" class="form-select" required>
                                <option value="">Loading faculties...</option>
                            </select>
                        </div>
                        <div class="register-form-group">
                            <label for="lecDepartment">Department <span class="text-danger">*</span></label>
                            <select id="lecDepartment" name="department_id" class="form-select" required disabled>
                                <option value="">Select faculty first</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-register-submit mt-2" id="lecRegisterBtn">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Register as Lecturer
                    </button>
                </form>

                <div class="text-center mt-3" style="font-size:.85rem;color:#64748b">
                    Already have an account? <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal" style="color:var(--accent);font-weight:600;text-decoration:none">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ===== HERO PARTICLES =====
(function() {
    const container = document.getElementById('heroParticles');
    const colors = ['#f5a623','#1d4ed8','#0ea5e9','#34a853'];
    for (let i = 0; i < 25; i++) {
        const dot = document.createElement('div');
        dot.className = 'dot';
        const size = Math.random() * 8 + 4;
        dot.style.cssText = `
            width:${size}px;height:${size}px;
            left:${Math.random()*100}%;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            animation-duration:${Math.random()*12+8}s;
            animation-delay:${Math.random()*10}s;
        `;
        container.appendChild(dot);
    }
})();

// ===== COUNTER ANIMATION =====
function animateCounter(el) {
    const target = parseInt(el.getAttribute('data-count'));
    let count = 0;
    const increment = target / 60;
    const timer = setInterval(() => {
        count += increment;
        if (count >= target) { count = target; clearInterval(timer); }
        el.textContent = Math.floor(count);
    }, 25);
}
const counters = document.querySelectorAll('[data-count]');
const counterObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); counterObs.unobserve(e.target); } });
}, { threshold: .5 });
counters.forEach(c => counterObs.observe(c));

// ===== SCROLL FADE =====
const fadeEls = document.querySelectorAll('.fade-up');
const fadeObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); fadeObs.unobserve(e.target); } });
}, { threshold: .1 });
fadeEls.forEach(el => fadeObs.observe(el));

// ===== PASSWORD TOGGLE =====
document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd = document.getElementById('loginPassword');
    const icon = document.getElementById('togglePwdIcon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'fas fa-eye'; }
});

// ===== LOGIN =====
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn      = document.getElementById('loginBtn');
    const alertDiv = document.getElementById('loginAlert');
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Authenticating...';
    alertDiv.className = 'alert d-none';

    try {
        const res  = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'login', username, password})
        });
        const data = await res.json();
        if (data.status) {
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Success! Redirecting...';
            btn.style.background = 'linear-gradient(135deg,#34a853,#22c55e)';
            const role = data.role;
            setTimeout(() => {
                if (['Super Administrator','Administrator','Registrar','Department Officer'].includes(role)) {
                    window.location.href = 'admin_spa.php';
                } else if (role === 'Lecturer') {
                    window.location.href = 'lecturer_spa.php';
                } else {
                    window.location.href = 'student_spa.php';
                }
            }, 800);
        } else {
            alertDiv.className = 'alert alert-danger mb-3 rounded-3 border-0';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Sign In Securely';
        }
    } catch (err) {
        alertDiv.className = 'alert alert-danger mb-3 rounded-3 border-0';
        alertDiv.innerHTML = '<i class="fas fa-wifi me-2"></i>Network error. Please try again.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Sign In Securely';
    }
});

// ===== FORGOT PASSWORD =====
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const username  = document.getElementById('forgotUsername').value.trim();
    const alertDiv  = document.getElementById('forgotAlert');
    const res       = await fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'forgot_password', username})
    });
    const data = await res.json();
    alertDiv.className = 'alert ' + (data.status ? 'alert-success' : 'alert-danger');
    alertDiv.textContent = data.message;
});

// ===== RESET PASSWORD =====
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const token    = document.getElementById('resetToken').value.trim();
    const password = document.getElementById('resetNewPwd').value;
    const alertDiv = document.getElementById('forgotAlert');
    const res      = await fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'reset_password', token, password})
    });
    const data = await res.json();
    alertDiv.className = 'alert ' + (data.status ? 'alert-success' : 'alert-danger');
    alertDiv.textContent = data.message;
    if (data.status) {
        setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('forgotModal'))?.hide(); }, 2000);
    }
});

// Auto-open login if hash is #login
if (window.location.hash === '#login') {
    new bootstrap.Modal(document.getElementById('loginModal')).show();
}

// ===== REGISTRATION MODAL =====
(function() {
    // Tab switching
    const tabs = document.querySelectorAll('.register-tab');
    const stuForm = document.getElementById('studentRegisterForm');
    const lecForm = document.getElementById('lecturerRegisterForm');
    const regAlert = document.getElementById('registerAlert');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const target = this.getAttribute('data-tab');
            if (target === 'student') {
                stuForm.style.display = 'block';
                lecForm.style.display = 'none';
            } else {
                stuForm.style.display = 'none';
                lecForm.style.display = 'block';
            }
            regAlert.className = 'alert d-none mb-3 rounded-3 border-0';
        });
    });

    // Load faculties into dropdowns
    async function loadFaculties(selectId) {
        const sel = document.getElementById(selectId);
        try {
            const res = await fetch('api.php?action=get_faculties');
            const data = await res.json();
            sel.innerHTML = '<option value="">Select Faculty</option>';
            if (data.status && data.data) {
                data.data.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    sel.appendChild(opt);
                });
            }
        } catch(e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
        }
    }

    // Load departments based on faculty
    async function loadDepartments(selectId, facultyId) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '<option value="">Loading...</option>';
        sel.disabled = true;
        try {
            const res = await fetch('api.php?action=get_departments&faculty_id=' + facultyId);
            const data = await res.json();
            sel.innerHTML = '<option value="">Select Department</option>';
            if (data.status && data.data) {
                data.data.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    sel.appendChild(opt);
                });
            }
            sel.disabled = false;
        } catch(e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
        }
    }

    // Load programmes based on department
    async function loadProgrammes(selectId, departmentId) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '<option value="">Loading...</option>';
        sel.disabled = true;
        try {
            const res = await fetch('api.php?action=get_programmes&department_id=' + departmentId);
            const data = await res.json();
            sel.innerHTML = '<option value="">Select Programme</option>';
            if (data.status && data.data) {
                data.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name + (p.type ? ' (' + p.type + ')' : '');
                    sel.appendChild(opt);
                });
            }
            sel.disabled = false;
        } catch(e) {
            sel.innerHTML = '<option value="">Failed to load</option>';
        }
    }

    // Student faculty -> department -> programme chain
    document.getElementById('stuFaculty').addEventListener('change', function() {
        const deptSel = document.getElementById('stuDepartment');
        const progSel = document.getElementById('stuProgramme');
        deptSel.innerHTML = '<option value="">Select Department</option>';
        progSel.innerHTML = '<option value="">Select department first</option>';
        progSel.disabled = true;
        if (this.value) loadDepartments('stuDepartment', this.value);
        else deptSel.disabled = true;
    });

    document.getElementById('stuDepartment').addEventListener('change', function() {
        const progSel = document.getElementById('stuProgramme');
        progSel.innerHTML = '<option value="">Select Programme</option>';
        if (this.value) loadProgrammes('stuProgramme', this.value);
        else progSel.disabled = true;
    });

    // Lecturer faculty -> department chain
    document.getElementById('lecFaculty').addEventListener('change', function() {
        const deptSel = document.getElementById('lecDepartment');
        deptSel.innerHTML = '<option value="">Select Department</option>';
        if (this.value) loadDepartments('lecDepartment', this.value);
        else deptSel.disabled = true;
    });

    // Load faculties when modal opens
    const registerModalEl = document.getElementById('registerModal');
    registerModalEl.addEventListener('show.bs.modal', function() {
        loadFaculties('stuFaculty');
        loadFaculties('lecFaculty');
    });

    // Helper: collect form data into an object
    function getFormData(formId) {
        const form = document.getElementById(formId);
        const data = {};
        new FormData(form).forEach((val, key) => { data[key] = val; });
        return data;
    }

    // Student Registration Submit
    document.getElementById('studentRegisterForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('stuRegisterBtn');
        const formData = getFormData('studentRegisterForm');

        // Validate passwords match
        if (formData.password !== formData.confirm_password) {
            regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
            regAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Passwords do not match.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registering...';
        regAlert.className = 'alert d-none mb-3 rounded-3 border-0';

        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action:'register', request_type:'Student', ...formData})
            });
            const data = await res.json();
            if (data.status) {
                regAlert.className = 'alert alert-success mb-3 rounded-3 border-0';
                regAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Registration successful! Your account is pending admin approval. You will be able to log in once an administrator approves your account.');
                document.getElementById('studentRegisterForm').reset();
                document.getElementById('stuNationality').value = 'Nigerian';
                document.getElementById('stuDepartment').innerHTML = '<option value="">Select faculty first</option>';
                document.getElementById('stuDepartment').disabled = true;
                document.getElementById('stuProgramme').innerHTML = '<option value="">Select department first</option>';
                document.getElementById('stuProgramme').disabled = true;
            } else {
                regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
                regAlert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Registration failed. Please try again.');
            }
        } catch(err) {
            regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
            regAlert.innerHTML = '<i class="fas fa-wifi me-2"></i>Network error. Please try again.';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Register as Student';
    });

    // Lecturer Registration Submit
    document.getElementById('lecturerRegisterForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('lecRegisterBtn');
        const formData = getFormData('lecturerRegisterForm');

        // Validate passwords match
        if (formData.password !== formData.confirm_password) {
            regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
            regAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Passwords do not match.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registering...';
        regAlert.className = 'alert d-none mb-3 rounded-3 border-0';

        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action:'register', request_type:'Lecturer', ...formData})
            });
            const data = await res.json();
            if (data.status) {
                regAlert.className = 'alert alert-success mb-3 rounded-3 border-0';
                regAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Registration successful! Your account is pending admin approval. You will be able to log in once an administrator approves your account.');
                document.getElementById('lecturerRegisterForm').reset();
                document.getElementById('lecDepartment').innerHTML = '<option value="">Select faculty first</option>';
                document.getElementById('lecDepartment').disabled = true;
            } else {
                regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
                regAlert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Registration failed. Please try again.');
            }
        } catch(err) {
            regAlert.className = 'alert alert-warning mb-3 rounded-3 border-0';
            regAlert.innerHTML = '<i class="fas fa-wifi me-2"></i>Network error. Please try again.';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-chalkboard-teacher me-2"></i>Register as Lecturer';
    });
})();
</script>
</body>
</html>
