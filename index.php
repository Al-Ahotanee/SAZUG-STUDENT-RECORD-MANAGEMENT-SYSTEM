<?php
session_start();
$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG Student Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root{--primary:#1a365d;--primary-light:#2a4a7f;--primary-dark:#0f2440;--gold:#c9973f;--gold-light:#daa84e;--gold-dark:#a87d2e;--white:#fff;--off-white:#f8f9fa;--dark-bg:#0d1b2a;--text:#333;--text-light:#666;--shadow:0 4px 20px rgba(0,0,0,.1)}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;color:var(--text);overflow-x:hidden}
        h1,h2,h3,h4,h5{font-family:'Playfair Display',serif}
        [data-bs-theme="dark"] body,[data-bs-theme="dark"]{background:var(--dark-bg);color:#e0e0e0}
        [data-bs-theme="dark"] .card,[data-bs-theme="dark"] .navbar{background:var(--primary-dark)!important;border-color:rgba(255,255,255,.1)!important}
        [data-bs-theme="dark"] .text-dark{color:#e0e0e0!important}
        [data-bs-theme="dark"] .bg-light{background:var(--dark-bg)!important}
        [data-bs-theme="dark"] .offcanvas{background:var(--primary-dark)!important}
        [data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1a2a3d;color:#e0e0e0;border-color:rgba(255,255,255,.15)}

        /* Navbar */
        .navbar-custom{background:var(--primary)!important;backdrop-filter:blur(20px);box-shadow:0 2px 20px rgba(0,0,0,.15);transition:all .3s}
        .navbar-custom.scrolled{background:rgba(26,54,93,.95)!important;padding:.3rem 0}
        .navbar-brand-custom{font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--gold)!important;font-weight:700;letter-spacing:1px}
        .nav-link-custom{color:rgba(255,255,255,.85)!important;font-weight:500;transition:all .3s;padding:.5rem 1rem!important;border-radius:8px}
        .nav-link-custom:hover,.nav-link-custom.active{color:var(--gold)!important;background:rgba(201,151,63,.1)}
        .theme-toggle{width:40px;height:40px;border-radius:50%;border:2px solid rgba(255,255,255,.3);background:transparent;color:var(--gold);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .3s}
        .theme-toggle:hover{background:rgba(201,151,63,.15);border-color:var(--gold)}

        /* Hero */
        .hero{position:relative;min-height:100vh;background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 50%,var(--primary-light) 100%);display:flex;align-items:center;overflow:hidden}
        .hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9973f' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
        #particles-canvas{position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none}
        .hero-content{position:relative;z-index:2}
        .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(201,151,63,.15);border:1px solid rgba(201,151,63,.3);color:var(--gold);padding:.5rem 1.2rem;border-radius:50px;font-size:.85rem;font-weight:600;margin-bottom:1.5rem;animation:fadeInUp .8s}
        .hero-title{font-size:clamp(2.5rem,6vw,4.5rem);color:var(--white);font-weight:700;line-height:1.15;margin-bottom:1.5rem;animation:fadeInUp .8s .2s both}
        .hero-title span{color:var(--gold)}
        .hero-subtitle{font-size:clamp(1rem,2vw,1.25rem);color:rgba(255,255,255,.75);max-width:600px;line-height:1.8;margin-bottom:2.5rem;animation:fadeInUp .8s .4s both}
        .hero-buttons{display:flex;gap:1rem;flex-wrap:wrap;animation:fadeInUp .8s .6s both}
        .btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--primary-dark);font-weight:700;padding:.85rem 2rem;border-radius:12px;border:none;font-size:1rem;transition:all .3s;box-shadow:0 4px 15px rgba(201,151,63,.3)}
        .btn-gold:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(201,151,63,.4);color:var(--primary-dark)}
        .btn-outline-light-custom{border:2px solid rgba(255,255,255,.3);color:var(--white);padding:.85rem 2rem;border-radius:12px;background:transparent;font-weight:600;transition:all .3s}
        .btn-outline-light-custom:hover{border-color:var(--gold);color:var(--gold);background:rgba(201,151,63,.05)}
        .hero-stats{margin-top:4rem;animation:fadeInUp .8s .8s both}
        .stat-item{text-align:center;padding:1.5rem}
        .stat-number{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:700;color:var(--gold)}
        .stat-label{color:rgba(255,255,255,.7);font-size:.9rem;font-weight:500;margin-top:.3rem}
        .hero-shape{position:absolute;border-radius:50%;background:rgba(201,151,63,.08)}
        .hero-shape-1{width:400px;height:400px;top:-100px;right:-100px}
        .hero-shape-2{width:250px;height:250px;bottom:50px;left:-80px}

        /* Carousel */
        .carousel-section{padding:5rem 0;background:var(--off-white)}
        [data-bs-theme="dark"] .carousel-section{background:var(--primary-dark)}
        .section-header{text-align:center;margin-bottom:3rem}
        .section-title{font-size:2.2rem;color:var(--primary);font-weight:700;margin-bottom:.5rem}
        [data-bs-theme="dark"] .section-title{color:var(--gold)}
        .section-line{width:80px;height:3px;background:linear-gradient(90deg,var(--gold),var(--primary));margin:0 auto 1rem;border-radius:3px}
        .section-subtitle{color:var(--text-light);font-size:1.05rem;max-width:600px;margin:0 auto}
        .feature-card{background:var(--white);border-radius:16px;padding:2rem;height:100%;border:none;transition:all .4s;box-shadow:0 4px 15px rgba(0,0,0,.06);position:relative;overflow:hidden}
        .feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--gold),var(--primary));transform:scaleX(0);transition:transform .4s;transform-origin:left}
        .feature-card:hover{transform:translateY(-8px);box-shadow:0 20px 40px rgba(0,0,0,.12)}
        .feature-card:hover::before{transform:scaleX(1)}
        .feature-icon{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:1.2rem;color:var(--white);background:linear-gradient(135deg,var(--primary),var(--primary-light))}
        .feature-card h5{color:var(--primary);font-weight:700;margin-bottom:.6rem;font-family:'Inter',sans-serif}
        [data-bs-theme="dark"] .feature-card h5{color:var(--gold)}
        .feature-card p{color:var(--text-light);font-size:.92rem;line-height:1.6;margin:0}
        .carousel-indicators button{width:12px!important;height:12px!important;border-radius:50%!important;background:var(--text-light)!important;opacity:.3!important;border:none!important;transition:all .3s}
        .carousel-indicators button.active{opacity:1!important;background:var(--gold)!important;width:30px!important;border-radius:6px!important}

        /* Features Grid */
        .features-grid{padding:5rem 0;background:var(--white)}
        [data-bs-theme="dark"] .features-grid{background:var(--dark-bg)}
        .icon-box{width:80px;height:80px;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem;transition:all .3s}
        .icon-box-primary{background:rgba(26,54,93,.1);color:var(--primary)}
        .icon-box-gold{background:rgba(201,151,63,.1);color:var(--gold)}
        [data-bs-theme="dark"] .icon-box-primary{background:rgba(201,151,63,.15);color:var(--gold)}

        /* Footer */
        .footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:4rem 0 2rem}
        .footer h5{color:var(--gold);font-family:'Inter',sans-serif;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem}
        .footer a{color:rgba(255,255,255,.6);text-decoration:none;transition:all .3s;font-size:.9rem}
        .footer a:hover{color:var(--gold);padding-left:5px}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.1);margin-top:3rem;padding-top:1.5rem;text-align:center;font-size:.85rem}
        .footer-brand{font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--gold);font-weight:700}

        /* Modals */
        .modal-content{border:none;border-radius:16px;overflow:hidden}
        .modal-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:var(--white);border:none;padding:1.5rem 2rem}
        .modal-header .btn-close{filter:brightness(0) invert(1)}
        .modal-body{padding:2rem}
        .form-floating-custom{position:relative;margin-bottom:1.2rem}
        .form-floating-custom .form-control{border:2px solid #e0e0e0;border-radius:12px;padding:1rem 1rem .5rem;font-size:.95rem;transition:all .3s}
        .form-floating-custom .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,151,63,.15)}
        .form-floating-custom label{position:absolute;top:.5rem;left:1rem;font-size:.75rem;color:var(--text-light);font-weight:500}

        /* Verify Page */
        .verify-section{min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--primary-dark),var(--primary));padding:3rem 0}
        .verify-card{background:var(--white);border-radius:20px;padding:3rem;max-width:550px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.15)}
        .verify-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-light));display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:var(--white)}

        /* Reset Page */
        .reset-section{min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--primary-dark),var(--primary));padding:3rem 0}

        /* Animations */
        @keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}
        .animate-on-scroll{opacity:0;transform:translateY(30px);transition:all .8s}
        .animate-on-scroll.visible{opacity:1;transform:translateY(0)}

        /* Responsive */
        @media(max-width:768px){
            .hero-stats .row>div{border-bottom:1px solid rgba(255,255,255,.1)}
            .hero-buttons{flex-direction:column}
            .hero-buttons .btn{width:100%}
            .stat-number{font-size:2rem}
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand navbar-brand-custom" href="?page=home">
                <i class="bi bi-mortarboard-fill me-2"></i>SAZUG SRMS
            </a>
            <div class="d-flex align-items-center gap-2 order-lg-last">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                </button>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link nav-link-custom active" href="?page=home">Home</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="?page=home#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="?page=verify">Verify Certificate</a></li>
                    <li class="nav-item"><a class="nav-link nav-link-custom" href="?page=home#contact">Contact</a></li>
                </ul>
                <button class="btn btn-gold px-4" onclick="openLoginModal()">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Offcanvas -->
    <div class="offcanvas offcanvas-end" id="mobileMenu" style="background:var(--primary)">
        <div class="offcanvas-header border-0">
            <h5 class="offcanvas-title text-gold" style="color:var(--gold)">
                <i class="bi bi-mortarboard-fill me-2"></i>SAZUG SRMS
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link text-white py-2" href="?page=home" data-bs-dismiss="offcanvas">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white py-2" href="?page=home#features" data-bs-dismiss="offcanvas">Features</a></li>
                <li class="nav-item"><a class="nav-link text-white py-2" href="?page=verify" data-bs-dismiss="offcanvas">Verify Certificate</a></li>
                <li class="nav-item"><a class="nav-link text-white py-2" href="?page=home#contact" data-bs-dismiss="offcanvas">Contact</a></li>
            </ul>
            <hr class="border-secondary my-3">
            <button class="btn btn-gold w-100" onclick="openLoginModal();bootstrap.Offcanvas.getInstance(document.getElementById('mobileMenu')).hide()">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </div>
    </div>

    <?php if ($page === 'verify'): ?>
    <!-- Certificate Verification Page -->
    <section class="verify-section">
        <div class="container">
            <div class="verify-card">
                <div class="verify-icon"><i class="bi bi-patch-check-fill"></i></div>
                <h2 class="text-center mb-2" style="color:var(--primary);font-size:1.8rem">Verify Certificate</h2>
                <p class="text-center mb-4" style="color:var(--text-light)">Enter the certificate number to verify its authenticity</p>
                <div class="form-floating-custom">
                    <input type="text" class="form-control" id="certNumber" placeholder="Certificate Number">
                    <label>Certificate Number</label>
                </div>
                <button class="btn btn-gold w-100 py-3" onclick="verifyCertificate()" id="verifyBtn">
                    <i class="bi bi-search me-2"></i>Verify Certificate
                </button>
                <div id="verifyResult" class="mt-4" style="display:none"></div>
                <div class="text-center mt-3">
                    <a href="?page=home" class="text-decoration-none" style="color:var(--gold);font-size:.9rem">
                        <i class="bi bi-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php elseif ($page === 'reset-password'): ?>
    <!-- Password Reset Page -->
    <section class="reset-section">
        <div class="container">
            <div class="verify-card">
                <div class="verify-icon"><i class="bi bi-key-fill"></i></div>
                <h2 class="text-center mb-2" style="color:var(--primary);font-size:1.8rem">Reset Password</h2>
                <p class="text-center mb-4" style="color:var(--text-light)">Enter your new password below</p>
                <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                <div class="form-floating-custom">
                    <input type="password" class="form-control" id="newPassword" placeholder="New Password">
                    <label>New Password</label>
                </div>
                <div class="form-floating-custom">
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm Password">
                    <label>Confirm Password</label>
                </div>
                <button class="btn btn-gold w-100 py-3" onclick="resetPassword()">
                    <i class="bi bi-check-circle me-2"></i>Reset Password
                </button>
            </div>
        </div>
    </section>

    <?php else: ?>
    <!-- Hero Section -->
    <section class="hero" id="hero">
        <canvas id="particles-canvas"></canvas>
        <div class="hero-shape hero-shape-1"></div>
        <div class="hero-shape hero-shape-2"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content">
                    <div class="hero-badge">
                        <i class="bi bi-stars"></i> Trusted by Leading Institutions
                    </div>
                    <h1 class="hero-title">
                        Student Record<br><span>Management System</span>
                    </h1>
                    <p class="hero-subtitle">
                        A comprehensive platform for managing student records, academic programmes, certificate issuance, and institutional administration — all in one place.
                    </p>
                    <div class="hero-buttons">
                        <button class="btn btn-gold btn-lg" onclick="openLoginModal()">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Get Started
                        </button>
                        <button class="btn btn-outline-light-custom btn-lg" onclick="document.getElementById('features').scrollIntoView({behavior:'smooth'})">
                            <i class="bi bi-play-circle me-2"></i>Learn More
                        </button>
                    </div>
                    <div class="hero-stats">
                        <div class="row">
                            <div class="col-6 col-md-3">
                                <div class="stat-item">
                                    <div class="stat-number" data-target="5000">0</div>
                                    <div class="stat-label">Students Managed</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-item">
                                    <div class="stat-number" data-target="50">0</div>
                                    <div class="stat-label">Departments</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-item">
                                    <div class="stat-number" data-target="120">0</div>
                                    <div class="stat-label">Programmes</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-item">
                                    <div class="stat-number" data-target="2000">0</div>
                                    <div class="stat-label">Certificates Issued</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Carousel -->
    <section class="carousel-section">
        <div class="container">
            <div class="section-header">
                <div class="section-line"></div>
                <h2 class="section-title">Why Choose SAZUG SRMS?</h2>
                <p class="section-subtitle">Discover the powerful features that make student record management effortless</p>
            </div>
            <div id="featureCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                <div class="carousel-indicators mb-0" style="bottom:-20px">
                    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2"></button>
                </div>
                <div class="carousel-inner">
                    <!-- Slide 1 -->
                    <div class="carousel-item active">
                        <div class="row g-4">
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-people-fill"></i></div><h5>Student Management</h5><p>Complete student lifecycle management from enrollment to graduation with status tracking and profile management.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-building"></i></div><h5>Faculty & Department</h5><p>Organize academic structure with hierarchical management of faculties, departments, and programmes.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-book"></i></div><h5>Course Management</h5><p>Manage courses with prerequisites, credit units, lecturer assignments, and semester planning.</p></div></div>
                        </div>
                    </div>
                    <!-- Slide 2 -->
                    <div class="carousel-item">
                        <div class="row g-4">
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-award"></i></div><h5>Certificate Issuance</h5><p>Auto-generate certificates with unique verification numbers for graduated students.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-shield-check"></i></div><h5>Certificate Verification</h5><p>Public verification portal allows employers and institutions to validate certificates instantly.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-file-earmark-arrow-up"></i></div><h5>Document Upload</h5><p>Secure document management with type classification, size limits, and easy retrieval.</p></div></div>
                        </div>
                    </div>
                    <!-- Slide 3 -->
                    <div class="carousel-item">
                        <div class="row g-4">
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-bar-chart-line"></i></div><h5>Analytics Dashboard</h5><p>Real-time statistics with interactive charts for enrollment trends, gender distribution, and more.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-clock-history"></i></div><h5>Audit Trail</h5><p>Complete audit logging of all system activities with user tracking and IP recording.</p></div></div>
                            <div class="col-md-4"><div class="feature-card"><div class="feature-icon"><i class="bi bi-download"></i></div><h5>CSV/Print Export</h5><p>Export student data, audit logs, and reports to CSV for offline analysis and record keeping.</p></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="features-grid" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-line"></div>
                <h2 class="section-title">Powerful Features</h2>
                <p class="section-subtitle">Everything you need for efficient student record management</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-primary"><i class="bi bi-speedometer2"></i></div><h5>Dashboard Analytics</h5><p class="text-muted">Visual insights with real-time statistics and interactive charts.</p></div></div>
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-gold"><i class="bi bi-person-check"></i></div><h5>Role-Based Access</h5><p class="text-muted">Separate dashboards for admin, lecturers, and students.</p></div></div>
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-primary"><i class="bi bi-calendar-event"></i></div><h5>Session Management</h5><p class="text-muted">Manage academic sessions, semesters, and enrollment periods.</p></div></div>
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-gold"><i class="bi bi-person-badge"></i></div><h5>Staff Management</h5><p class="text-muted">Manage admin and lecturer accounts with role assignments.</p></div></div>
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-primary"><i class="bi bi-gear"></i></div><h5>System Settings</h5><p class="text-muted">Customize institution details, upload limits, and system preferences.</p></div></div>
                <div class="col-md-4 animate-on-scroll"><div class="text-center"><div class="icon-box icon-box-gold"><i class="bi bi-moon"></i></div><h5>Dark Mode</h5><p class="text-muted">Beautiful dark theme for comfortable viewing at any time.</p></div></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand mb-3"><i class="bi bi-mortarboard-fill me-2"></i>SAZUG SRMS</div>
                    <p>A comprehensive student record management system designed for modern educational institutions. Streamline your academic administration with powerful tools and intuitive interfaces.</p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="?page=home">Home</a></li>
                        <li><a href="?page=home#features">Features</a></li>
                        <li><a href="?page=verify">Verify Certificate</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">API Reference</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><a href="mailto:info@sazug.edu.ng"><i class="bi bi-envelope me-2"></i>info@sazug.edu.ng</a></li>
                        <li><a href="tel:+2348000000000"><i class="bi bi-phone me-2"></i>+234 800 000 0000</a></li>
                        <li><a href="#"><i class="bi bi-geo-alt me-2"></i>Nigeria</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SAZUG Student Record Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-right me-2"></i>Login to SRMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating-custom">
                        <input type="text" class="form-control" id="loginUsername" placeholder="Username">
                        <label>Username</label>
                    </div>
                    <div class="form-floating-custom">
                        <input type="password" class="form-control" id="loginPassword" placeholder="Password" onkeydown="if(event.key==='Enter')doLogin()">
                        <label>Password</label>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label" for="rememberMe" style="font-size:.85rem">Remember me</label>
                        </div>
                        <a href="javascript:void(0)" onclick="openForgotModal()" style="color:var(--gold);font-size:.85rem;text-decoration:none">Forgot Password?</a>
                    </div>
                    <button class="btn btn-gold w-100 py-3" onclick="doLogin()" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-light);font-size:.9rem;margin-bottom:1.5rem">Enter your email address to receive a password reset link.</p>
                    <div class="form-floating-custom">
                        <input type="email" class="form-control" id="forgotEmail" placeholder="Email Address">
                        <label>Email Address</label>
                    </div>
                    <button class="btn btn-gold w-100 py-3" onclick="doForgotPassword()">
                        <i class="bi bi-send me-2"></i>Send Reset Link
                    </button>
                    <div class="text-center mt-3">
                        <a href="javascript:void(0)" onclick="closeForgotModal()" style="color:var(--gold);font-size:.85rem;text-decoration:none">
                            <i class="bi bi-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // Theme
    function toggleTheme(){const t=document.documentElement.getAttribute('data-bs-theme');const n=t==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',n);localStorage.setItem('theme',n);updateThemeIcon(n)}
    function updateThemeIcon(t){document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    (function(){const t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-bs-theme',t);updateThemeIcon(t)}})();

    // Navbar scroll
    window.addEventListener('scroll',()=>{const n=document.getElementById('mainNav');if(n)n.classList.toggle('scrolled',window.scrollY>50)});

    // Particles
    (function(){const c=document.getElementById('particles-canvas');if(!c)return;const ctx=c.getContext('2d');let particles=[];function resize(){c.width=window.innerWidth;c.height=window.innerHeight}resize();window.addEventListener('resize',resize);
    for(let i=0;i<60;i++){particles.push({x:Math.random()*c.width,y:Math.random()*c.height,vx:(Math.random()-.5)*.5,vy:(Math.random()-.5)*.5,r:Math.random()*2+1,a:Math.random()*.4+.1})}
    function draw(){ctx.clearRect(0,0,c.width,c.height);particles.forEach((p,i)=>{p.x+=p.vx;p.y+=p.vy;if(p.x<0||p.x>c.width)p.vx*=-1;if(p.y<0||p.y>c.height)p.vy*=-1;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle='rgba(201,151,63,'+p.a+')';ctx.fill();
    particles.forEach((p2,j)=>{if(i===j)return;const d=Math.hypot(p.x-p2.x,p.y-p2.y);if(d<120){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(p2.x,p2.y);ctx.strokeStyle='rgba(201,151,63,'+((1-d/120)*.15)+')';ctx.stroke()}})});requestAnimationFrame(draw)}draw()})();

    // Counter Animation
    function animateCounters(){document.querySelectorAll('.stat-number').forEach(el=>{const target=parseInt(el.dataset.target);const duration=2000;const start=Date.now();function update(){const elapsed=Date.now()-start;const progress=Math.min(elapsed/duration,1);const eased=1-Math.pow(1-progress,3);el.textContent=Math.floor(target*eased).toLocaleString();if(progress<1)requestAnimationFrame(update)}update()})}
    const heroObserver=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting){animateCounters();heroObserver.disconnect()}})},{threshold:.3});
    const heroEl=document.getElementById('hero');if(heroEl)heroObserver.observe(heroEl);

    // Scroll animations
    const scrollObserver=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible')})},{threshold:.1});
    document.querySelectorAll('.animate-on-scroll').forEach(el=>scrollObserver.observe(el));

    // Login
    function openLoginModal(){new bootstrap.Modal(document.getElementById('loginModal')).show()}
    function openForgotModal(){bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();new bootstrap.Modal(document.getElementById('forgotModal')).show()}
    function closeForgotModal(){bootstrap.Modal.getInstance(document.getElementById('forgotModal')).hide();setTimeout(openLoginModal,200)}

    async function doLogin(){
        const u=document.getElementById('loginUsername').value.trim();const p=document.getElementById('loginPassword').value;
        if(!u||!p){Swal.fire('Error','Please enter username and password','error');return}
        const btn=document.getElementById('loginBtn');btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split me-2"></i>Signing in...';
        try{const r=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'login',username:u,password:p})});
        const d=await r.json();if(d.success){const user=d.data.user;const token=d.data.token;
        localStorage.setItem('sazug_token',token);localStorage.setItem('sazug_user',JSON.stringify(user));
        const dashboards={superadmin:'admin_spa.php',admin:'admin_spa.php',lecturer:'lecturer_spa.php',student:'student_spa.php'};
        Swal.fire({icon:'success',title:'Welcome!',text:'Redirecting to dashboard...',timer:1500,showConfirmButton:false});
        setTimeout(()=>{window.location.href=dashboards[user.role]||'index.php'},1500)}else{Swal.fire('Error',d.error||'Login failed','error');btn.disabled=false;btn.innerHTML='<i class="bi bi-box-arrow-in-right me-2"></i>Sign In'}}
        catch(e){Swal.fire('Error','Network error. Please try again.','error');btn.disabled=false;btn.innerHTML='<i class="bi bi-box-arrow-in-right me-2"></i>Sign In'}
    }

    // Forgot Password
    async function doForgotPassword(){
        const email=document.getElementById('forgotEmail').value.trim();
        if(!email){Swal.fire('Error','Please enter your email','error');return}
        try{const r=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reset_password_request',email})});
        const d=await r.json();if(d.success){bootstrap.Modal.getInstance(document.getElementById('forgotModal')).hide();Swal.fire({icon:'success',title:'Check Your Email',text:d.message})}else{Swal.fire('Error',d.error,'error')}}
        catch(e){Swal.fire('Error','Network error','error')}
    }

    // Certificate Verification
    async function verifyCertificate(){
        const num=document.getElementById('certNumber').value.trim();if(!num){Swal.fire('Error','Please enter a certificate number','error');return}
        const btn=document.getElementById('verifyBtn');btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split me-2"></i>Verifying...';
        try{const r=await fetch('api.php?action=verify_certificate&certificate_number='+encodeURIComponent(num));const d=await r.json();
        const res=document.getElementById('verifyResult');res.style.display='block';
        if(d.success){const c=d.data;res.innerHTML='<div class="alert alert-success"><h5 class="alert-heading"><i class="bi bi-patch-check-fill me-2"></i>Certificate Verified!</h5><hr><div class="row g-2"><div class="col-sm-6"><strong>Name:</strong> '+c.full_name+'</div><div class="col-sm-6"><strong>Programme:</strong> '+c.programme_type+' in '+c.programme_name+'</div><div class="col-sm-6"><strong>Faculty:</strong> '+c.faculty_name+'</div><div class="col-sm-6"><strong>Department:</strong> '+c.department_name+'</div><div class="col-sm-6"><strong>Issued:</strong> '+c.certificate_issued_at+'</div><div class="col-sm-6"><strong>Code:</strong> '+c.certificate_number+'</div></div></div>'}
        else{res.innerHTML='<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>'+d.error+'</div>'}btn.disabled=false;btn.innerHTML='<i class="bi bi-search me-2"></i>Verify Certificate'}
        catch(e){document.getElementById('verifyResult').style.display='block';document.getElementById('verifyResult').innerHTML='<div class="alert alert-danger">Network error. Please try again.</div>';btn.disabled=false;btn.innerHTML='<i class="bi bi-search me-2"></i>Verify Certificate'}
    }

    // Password Reset
    async function resetPassword(){
        const token=document.getElementById('resetToken').value;const pass=document.getElementById('newPassword').value;const confirm=document.getElementById('confirmPassword').value;
        if(!pass||!confirm){Swal.fire('Error','Please fill all fields','error');return}
        if(pass!==confirm){Swal.fire('Error','Passwords do not match','error');return}
        if(pass.length<8){Swal.fire('Error','Password must be at least 8 characters','error');return}
        try{const r=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reset_password',token,password:pass})});
        const d=await r.json();if(d.success){Swal.fire({icon:'success',title:'Success!',text:'Password has been reset. You can now login.',timer:2000,showConfirmButton:false});setTimeout(()=>window.location.href='index.php',2000)}else{Swal.fire('Error',d.error,'error')}}
        catch(e){Swal.fire('Error','Network error','error')}
    }
    </script>
</body>
</html>
