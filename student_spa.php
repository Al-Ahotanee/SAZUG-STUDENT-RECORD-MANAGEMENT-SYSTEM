<?php
/**
 * SAZUG SRMS — Student Single Page Application
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header('Location: index.php'); exit;
}
$CSRF   = $_SESSION['csrf_token'] ?? '';
$UNAME  = $_SESSION['username'];
$UID    = $_SESSION['user_id'];

// Load student record
require_once 'SystemCore.php';
$core    = new SystemCore();
$student = $core->getStudentByUserId((int)$UID);
if (!$student) { header('Location: index.php'); exit; }
$sName  = $student['full_name'];
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $sName), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal — SAZUG SRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--sb-bg:#0f2044;--sb-hover:#1a3060;--sb-active:#1e3a7a;--accent:#3b82f6;--gold:#f5a623;--body-bg:#f0f4f8;--card-shadow:0 4px 20px rgba(0,0,0,.06);--radius:14px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--body-bg);color:#1a202c;overflow-x:hidden}
.app-wrapper{display:flex;height:100vh;overflow:hidden}
/* SIDEBAR */
.sidebar{width:265px;background:var(--sb-bg);display:flex;flex-direction:column;flex-shrink:0;transition:width .3s;position:relative;z-index:100}
.sidebar.collapsed{width:72px}
.sidebar-logo{padding:20px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.07)}
.sidebar-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0}
.sidebar-logo-text{font-size:1.05rem;font-weight:800;color:#fff;white-space:nowrap;transition:opacity .2s}
.sidebar.collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}
/* Profile card in sidebar */
.sidebar-profile{padding:16px;border-bottom:1px solid rgba(255,255,255,.07)}
.profile-avatar{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#f5a623,#1d4ed8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;flex-shrink:0}
.profile-info{overflow:hidden;transition:opacity .2s}
.sidebar.collapsed .profile-info{opacity:0;width:0}
.profile-name{font-size:.83rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.profile-adm{font-size:.72rem;color:rgba(255,255,255,.45)}
/* Status pill */
.status-pill{padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
/* Nav */
.sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 0}
.sidebar-scroll::-webkit-scrollbar{width:3px}.sidebar-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px}
.sidebar-section{padding:14px 14px 4px;font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.28);white-space:nowrap;transition:opacity .2s}
.sidebar.collapsed .sidebar-section{opacity:0}
.nav-item-btn{display:flex;align-items:center;gap:13px;padding:10px 14px;color:rgba(255,255,255,.65);text-decoration:none;border-radius:10px;margin:2px 8px;cursor:pointer;transition:all .2s;white-space:nowrap;overflow:hidden;border:none;background:transparent;width:calc(100% - 16px);font-family:'Inter',sans-serif}
.nav-item-btn:hover{background:var(--sb-hover);color:rgba(255,255,255,.9)}
.nav-item-btn.active{background:var(--sb-active);color:#fff;box-shadow:inset 3px 0 0 var(--accent)}
.nav-icon{width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.nav-text{font-size:.845rem;font-weight:500;transition:opacity .2s}
.sidebar.collapsed .nav-text{opacity:0;width:0;overflow:hidden}
.sidebar-bottom{padding:12px 8px;border-top:1px solid rgba(255,255,255,.07)}
/* MAIN */
.main-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:#fff;padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8edf3;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.page-title{font-size:1.05rem;font-weight:700;color:#1a202c}
.topbar-btn{width:36px;height:36px;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;color:#64748b;font-size:.85rem}
.topbar-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.content-area{flex:1;overflow-y:auto;padding:26px;background:var(--body-bg)}
.content-area::-webkit-scrollbar{width:5px}.content-area::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:5px}
/* Views */
.spa-view{display:none;animation:fadeIn .3s ease}
.spa-view.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
/* Cards */
.info-card{background:#fff;border-radius:var(--radius);box-shadow:var(--card-shadow);border:1px solid #e8edf3;overflow:hidden}
.info-card-header{padding:16px 22px;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between}
.info-card-title{font-size:.9rem;font-weight:700;color:#1a202c}
.info-card-body{padding:22px}
/* Data rows */
.data-row{display:flex;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f8fafc}
.data-row:last-child{border-bottom:none}
.data-label{width:160px;font-size:.8rem;color:#94a3b8;font-weight:500;flex-shrink:0}
.data-value{font-size:.87rem;font-weight:600;color:#1a202c;flex:1}
/* Status badges */
.badge-active{background:#dcfce7;color:#15803d;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-suspended{background:#fef9c3;color:#a16207;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-graduated{background:#dbeafe;color:#1d4ed8;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
/* Cert card */
.cert-card{background:linear-gradient(135deg,#0a2540 0%,#0f3460 50%,#1e3a5f 100%);border-radius:20px;padding:32px;color:#fff;position:relative;overflow:hidden}
.cert-card::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.04)}
.cert-card::after{content:'';position:absolute;bottom:-60px;left:-20px;width:220px;height:220px;border-radius:50%;background:rgba(59,130,246,.08)}
.cert-number{font-size:.92rem;font-family:monospace;background:rgba(255,255,255,.12);padding:8px 16px;border-radius:8px;letter-spacing:1px;border:1px solid rgba(255,255,255,.18)}
/* Stat tiles */
.stat-tile{background:#fff;border-radius:12px;padding:20px;box-shadow:var(--card-shadow);border:1px solid #e8edf3;text-align:center;transition:all .25s}
.stat-tile:hover{transform:translateY(-3px);box-shadow:0 12px 35px rgba(0,0,0,.1)}
.stat-tile-icon{font-size:1.6rem;margin-bottom:10px}
.stat-tile-num{font-size:1.5rem;font-weight:800;color:#1a202c}
.stat-tile-label{font-size:.75rem;color:#94a3b8;font-weight:500}
/* Form */
.form-ctrl{border:2px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:.88rem;transition:border-color .2s;font-family:'Inter',sans-serif;width:100%}
.form-ctrl:focus{border-color:var(--accent);outline:none;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.form-label-sm{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px;display:block}
/* Responsive */
@media(max-width:900px){.sidebar{position:fixed;top:0;left:0;bottom:0;transform:translateX(-100%);transition:transform .3s}.sidebar.mobile-open{transform:translateX(0)}}
/* Course items */
.course-item{background:#fff;border-radius:var(--radius);box-shadow:var(--card-shadow);border:1px solid #e8edf3;padding:16px 20px;display:flex;align-items:center;gap:14px;transition:all .2s;cursor:pointer}
.course-item:hover{border-color:var(--accent);box-shadow:0 6px 25px rgba(59,130,246,.1)}
.course-item.selected{border-color:var(--accent);background:#eff6ff}
.course-item input[type=checkbox]{width:18px;height:18px;accent-color:var(--accent);flex-shrink:0;cursor:pointer}
.course-item-code{font-weight:700;font-size:.88rem;color:#1a202c;min-width:100px}
.course-item-title{font-size:.85rem;color:#374151;flex:1}
.course-item-units{font-size:.78rem;font-weight:700;color:var(--accent);background:#dbeafe;padding:3px 10px;border-radius:20px;white-space:nowrap}
.course-item-semester{font-size:.75rem;color:#64748b;margin-right:8px;white-space:nowrap}
.course-badge{font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap}
.course-badge-compulsory{background:#dcfce7;color:#15803d}
.course-badge-elective{background:#dbeafe;color:#1d4ed8}
/* Course table */
.course-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.87rem}
.course-table thead th{background:#f0f4f8;color:#64748b;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;border-bottom:2px solid #e2e8f0}
.course-table tbody td{padding:12px 16px;border-bottom:1px solid #f0f4f8;color:#1a202c;vertical-align:middle}
.course-table tbody tr:nth-child(even){background:#f8fafc}
.course-table tbody tr:hover{background:#eff6ff}
.course-table tfoot td{padding:14px 16px;border-top:2px solid #1d4ed8;font-weight:700;color:#1a202c;background:#eff6ff}
/* PDF header */
.pdf-header{text-align:center;border-bottom:2px solid #1d4ed8;padding-bottom:16px;margin-bottom:20px}
.pdf-header h4{font-size:1.1rem;font-weight:800;color:#1a202c;margin-bottom:4px}
.pdf-header h5{font-size:.95rem;font-weight:700;color:#1d4ed8;margin-bottom:2px}
.pdf-header p{font-size:.82rem;color:#64748b;margin:0}
/* Signature lines */
.signature-line{border-top:1px solid #1a202c;padding-top:8px;margin-top:40px;text-align:center;min-width:180px}
.signature-line .sig-label{font-size:.78rem;font-weight:600;color:#1a202c;display:block;margin-top:4px}
/* Biodata table */
.biodata-table{width:100%;border-collapse:collapse;font-size:.87rem}
.biodata-table th{background:#f0f4f8;color:#64748b;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;border:1px solid #e2e8f0;text-align:left;width:35%}
.biodata-table td{padding:10px 16px;border:1px solid #e2e8f0;color:#1a202c}
.biodata-table tr:nth-child(even) td{background:#f8fafc}
.biodata-section-title{font-size:.9rem;font-weight:700;color:#1d4ed8;padding:12px 16px;background:#eff6ff;border:1px solid #dbeafe;border-radius:8px;margin:20px 0 12px;display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<div class="app-wrapper">

<!-- ===== SIDEBAR ===== -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-university"></i></div>
        <div class="sidebar-logo-text">SAZUG SRMS</div>
    </div>
    <div class="sidebar-profile">
        <div class="d-flex align-items-center gap-3">
            <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($sName) ?></div>
                <div class="profile-adm"><?= htmlspecialchars($student['admission_number']) ?></div>
                <div class="mt-1">
                    <span class="status-pill"><?= htmlspecialchars($student['status']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-scroll">
        <div class="sidebar-section">My Portal</div>
        <button class="nav-item-btn active" data-view="overview" onclick="showView('overview',this)">
            <span class="nav-icon"><i class="fas fa-th-large"></i></span>
            <span class="nav-text">Overview</span>
        </button>
        <button class="nav-item-btn" data-view="profile" onclick="showView('profile',this)">
            <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
            <span class="nav-text">Academic Profile</span>
        </button>
        <button class="nav-item-btn" data-view="personal" onclick="showView('personal',this)">
            <span class="nav-icon"><i class="fas fa-id-card"></i></span>
            <span class="nav-text">Personal Details</span>
        </button>
        <button class="nav-item-btn" data-view="guardian" onclick="showView('guardian',this)">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span class="nav-text">Guardian Info</span>
        </button>
        <div class="sidebar-section">Documents</div>
        <button class="nav-item-btn" data-view="certificate" onclick="showView('certificate',this)">
            <span class="nav-icon"><i class="fas fa-certificate"></i></span>
            <span class="nav-text">Certificate</span>
        </button>
        <button class="nav-item-btn" data-view="documents" onclick="showView('documents',this)">
            <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
            <span class="nav-text">Upload Documents</span>
        </button>
        <div class="sidebar-section">Academics</div>
        <button class="nav-item-btn" data-view="courses" onclick="showView('courses',this)">
            <span class="nav-icon"><i class="fas fa-book"></i></span>
            <span class="nav-text">Course Registration</span>
        </button>
        <button class="nav-item-btn" data-view="biodata" onclick="showView('biodata',this)">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            <span class="nav-text">Biodata Form</span>
        </button>
        <button class="nav-item-btn" data-view="crf" onclick="showView('crf',this)">
            <span class="nav-icon"><i class="fas fa-print"></i></span>
            <span class="nav-text">Print CRF</span>
        </button>
        <div class="sidebar-section">Account</div>
        <button class="nav-item-btn" data-view="security" onclick="showView('security',this)">
            <span class="nav-icon"><i class="fas fa-lock"></i></span>
            <span class="nav-text">Change Password</span>
        </button>
    </div>
    <div class="sidebar-bottom">
        <button class="nav-item-btn" onclick="doLogout()" style="color:rgba(239,68,68,.8)">
            <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="nav-text">Logout</span>
        </button>
        <button onclick="document.getElementById('sidebar').classList.toggle('collapsed')" class="nav-item-btn mt-1" style="color:rgba(255,255,255,.4);justify-content:center">
            <span class="nav-icon"><i class="fas fa-bars"></i></span>
        </button>
    </div>
</nav>

<!-- ===== MAIN ===== -->
<div class="main-area">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="topbar-btn d-md-none" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')"><i class="fas fa-bars"></i></button>
            <div class="page-title" id="pageTitle">Overview</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:.8rem;color:#94a3b8">Level <?= htmlspecialchars($student['level']) ?> &bull; <?= htmlspecialchars($student['session_name']) ?></span>
            <a href="index.php" class="topbar-btn text-decoration-none"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <div class="content-area">

        <!-- ===== OVERVIEW ===== -->
        <div class="spa-view active" id="view-overview">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 style="font-size:1.15rem;font-weight:800;margin:0">Welcome, <?= htmlspecialchars(explode(' ',$sName)[0]) ?> 👋</h3>
                    <p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0"><?= date('l, F j, Y') ?></p>
                </div>
            </div>
            <!-- Stat Tiles -->
            <div class="row g-3 mb-4">
                <?php
                $statusColors = ['Active'=>'#22c55e','Suspended'=>'#f59e0b','Graduated'=>'#3b82f6','Withdrawn'=>'#f97316'];
                $sc = $statusColors[$student['status']] ?? '#94a3b8';
                $tiles = [
                    ['Level '.$student['level'],'Current Level','fa-layer-group','#dbeafe','#1d4ed8'],
                    [$student['status'],'Account Status','fa-circle-check',$sc.'22',$sc],
                    [$student['session_name'],'Active Session','fa-calendar','#dcfce7','#15803d'],
                    [$student['programme_type'],'Programme Type','fa-graduation-cap','#f3e8ff','#9333ea'],
                ];
                foreach($tiles as [$num,$label,$icon,$bg,$color]):?>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div class="stat-tile-icon" style="color:<?= $color ?>"><i class="fas <?= $icon ?>"></i></div>
                        <div class="stat-tile-num"><?= htmlspecialchars($num) ?></div>
                        <div class="stat-tile-label"><?= $label ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Profile Summary Card -->
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="info-card">
                        <div class="info-card-header">
                            <span class="info-card-title"><i class="fas fa-user-graduate me-2 text-primary"></i>Academic Summary</span>
                            <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="showView('profile',document.querySelector('[data-view=profile]'))">View Full Profile</button>
                        </div>
                        <div class="info-card-body">
                            <?php $rows=[
                                ['Admission No.',$student['admission_number']],
                                ['Matric No.',$student['matric_number']??'Not yet assigned'],
                                ['Programme',$student['programme_name'].' ('.$student['programme_type'].')'],
                                ['Department',$student['department_name']],
                                ['Faculty',$student['faculty_name']],
                                ['Admitted',date('F j, Y',strtotime($student['admission_date']))],
                            ]; foreach($rows as [$l,$v]): ?>
                            <div class="data-row">
                                <div class="data-label"><?= $l ?></div>
                                <div class="data-value"><?= htmlspecialchars($v) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <?php if($student['certificate_number']): ?>
                    <div class="cert-card mb-4" style="position:relative">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-certificate"></i></div>
                            <div>
                                <div style="font-weight:700;font-size:.95rem">Graduation Certificate</div>
                                <div style="font-size:.75rem;opacity:.65">Officially Issued</div>
                            </div>
                        </div>
                        <div class="cert-number mb-3"><?= htmlspecialchars($student['certificate_number']) ?></div>
                        <div style="font-size:.8rem;opacity:.7"><i class="fas fa-check-circle me-1" style="color:#4ade80"></i>Certificate issued — scan QR code to verify authenticity</div>
                    </div>
                    <?php else: ?>
                    <div class="info-card mb-4">
                        <div class="info-card-body text-center py-4">
                            <div style="font-size:3rem;color:#e2e8f0;margin-bottom:12px"><i class="fas fa-certificate"></i></div>
                            <div style="font-weight:700;color:#1a202c;margin-bottom:6px">No Certificate Yet</div>
                            <div style="font-size:.83rem;color:#94a3b8">Certificate will be available after graduation</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Quick Links -->
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title">Quick Actions</span></div>
                        <div class="info-card-body d-flex flex-column gap-2">
                            <button class="btn btn-outline-primary rounded-pill text-start" onclick="showView('documents',document.querySelector('[data-view=documents]'))">
                                <i class="fas fa-upload me-2"></i>Upload Document
                            </button>
                            <button class="btn btn-outline-secondary rounded-pill text-start" onclick="showView('security',document.querySelector('[data-view=security]'))">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </button>
                            <?php if($student['certificate_number']): ?>
                            <a href="index.php?verify=<?= urlencode($student['certificate_number']) ?>" target="_blank" class="btn btn-outline-success rounded-pill text-start">
                                <i class="fas fa-qrcode me-2"></i>Verify Certificate
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ACADEMIC PROFILE ===== -->
        <div class="spa-view" id="view-profile">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Academic Profile</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your full academic record</p></div>
            <div class="info-card">
                <div class="info-card-header"><span class="info-card-title"><i class="fas fa-university me-2 text-primary"></i>Academic Information</span></div>
                <div class="info-card-body">
                    <?php $rows=[
                        ['Admission Number',$student['admission_number']],
                        ['Matriculation Number',$student['matric_number']??'Not yet assigned'],
                        ['Full Name',$student['full_name']],
                        ['Gender',$student['gender']],
                        ['Date of Birth',date('F j, Y',strtotime($student['dob']))],
                        ['Faculty',$student['faculty_name']],
                        ['Department',$student['department_name']],
                        ['Programme',$student['programme_name'].' ('.$student['programme_type'].')'],
                        ['Current Level','Level '.$student['level']],
                        ['Academic Session',$student['session_name'].' — '.$student['semester'].' Semester'],
                        ['Admission Date',date('F j, Y',strtotime($student['admission_date']))],
                        ['Graduation Date',$student['graduation_date']?date('F j, Y',strtotime($student['graduation_date'])):'—'],
                        ['Status',$student['status']],
                        ['Nationality',$student['nationality']],
                    ]; foreach($rows as [$l,$v]): ?>
                    <div class="data-row">
                        <div class="data-label"><?= $l ?></div>
                        <div class="data-value"><?= $l==='Status' ? '<span class="badge-'.strtolower($v).'">'.htmlspecialchars($v).'</span>' : htmlspecialchars($v) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ===== PERSONAL DETAILS ===== -->
        <div class="spa-view" id="view-personal">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Personal Details</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your personal and contact information</p></div>
            <div class="info-card">
                <div class="info-card-header"><span class="info-card-title"><i class="fas fa-id-card me-2 text-primary"></i>Personal Information</span></div>
                <div class="info-card-body">
                    <?php $rows=[
                        ['Phone Number',$student['phone']??'—'],
                        ['Residential Address',$student['address']??'—'],
                        ['State of Origin',$student['state']??'—'],
                        ['LGA',$student['lga']??'—'],
                        ['Nationality',$student['nationality']??'—'],
                    ]; foreach($rows as [$l,$v]): ?>
                    <div class="data-row"><div class="data-label"><?= $l ?></div><div class="data-value"><?= htmlspecialchars($v) ?></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ===== GUARDIAN ===== -->
        <div class="spa-view" id="view-guardian">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Guardian Information</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your guardian / next of kin details</p></div>
            <div class="info-card">
                <div class="info-card-header"><span class="info-card-title"><i class="fas fa-users me-2 text-primary"></i>Guardian Details</span></div>
                <div class="info-card-body">
                    <?php $rows=[
                        ['Guardian Name',$student['guardian_name']??'—'],
                        ['Guardian Phone',$student['guardian_phone']??'—'],
                        ['Guardian Address',$student['guardian_address']??'—'],
                    ]; foreach($rows as [$l,$v]): ?>
                    <div class="data-row"><div class="data-label"><?= $l ?></div><div class="data-value"><?= htmlspecialchars($v) ?></div></div>
                    <?php endforeach; ?>
                    <?php if(!$student['guardian_name']): ?>
                    <div class="alert alert-warning rounded-3 mt-3 border-0" style="font-size:.85rem"><i class="fas fa-exclamation-circle me-2"></i>Guardian information not yet provided. Please contact the Registrar to update your record.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== CERTIFICATE ===== -->
        <div class="spa-view" id="view-certificate">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Graduation Certificate</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your official digital graduation certificate</p></div>
            <?php if($student['certificate_number']): ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="cert-card">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
                            <div style="width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem"><i class="fas fa-university"></i></div>
                            <div>
                                <div style="font-weight:800;font-size:1.1rem">Sa'adu Zungur University, Gadau</div>
                                <div style="font-size:.78rem;opacity:.65">Official Graduation Certificate</div>
                            </div>
                        </div>
                        <div style="font-size:.75rem;opacity:.55;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:4px">This certifies that</div>
                        <div style="font-size:1.4rem;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($student['full_name']) ?></div>
                        <div style="font-size:.85rem;opacity:.75;margin-bottom:20px">
                            having successfully completed all requirements for the
                            <strong><?= htmlspecialchars($student['programme_name']) ?> (<?= htmlspecialchars($student['programme_type']) ?>)</strong>
                            programme in the department of <strong><?= htmlspecialchars($student['department_name']) ?></strong>
                        </div>
                        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:14px 16px;border:1px solid rgba(255,255,255,.12);margin-bottom:20px">
                            <div style="font-size:.7rem;opacity:.5;margin-bottom:3px">CERTIFICATE NUMBER</div>
                            <div class="cert-number"><?= htmlspecialchars($student['certificate_number']) ?></div>
                        </div>
                        <div style="font-size:.8rem;opacity:.6">
                            <i class="fas fa-check-circle me-1" style="color:#4ade80"></i>
                            Issued: <?= $student['cert_issue_date'] ? date('F j, Y', strtotime($student['cert_issue_date'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="info-card h-100">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-qrcode me-2 text-primary"></i>Verification</span></div>
                        <div class="info-card-body text-center">
                            <?php if($student['qr_code_path'] && file_exists(__DIR__.'/'.$student['qr_code_path'])): ?>
                            <img src="<?= htmlspecialchars($student['qr_code_path']) ?>" alt="Certificate QR Code" class="img-fluid mb-3" style="max-width:160px;border-radius:12px;border:3px solid #e2e8f0">
                            <?php else: ?>
                            <div style="width:160px;height:160px;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2.5rem;color:#d1d5db"><i class="fas fa-qrcode"></i></div>
                            <?php endif; ?>
                            <p style="font-size:.83rem;color:#64748b;line-height:1.6">Scan this QR code or use the link below to verify the authenticity of this certificate online.</p>
                            <a href="index.php?verify=<?= urlencode($student['certificate_number']) ?>" target="_blank" class="btn btn-primary rounded-pill px-4 mt-2">
                                <i class="fas fa-external-link-alt me-2"></i>Verify Online
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="info-card">
                <div class="info-card-body text-center py-5">
                    <div style="font-size:4rem;color:#e2e8f0;margin-bottom:16px"><i class="fas fa-certificate"></i></div>
                    <h5 class="fw-bold" style="color:#1a202c;margin-bottom:8px">Certificate Not Yet Issued</h5>
                    <p style="color:#94a3b8;max-width:380px;margin:0 auto;font-size:.9rem;line-height:1.7">
                        Your graduation certificate will be available here once you complete your programme and it is officially issued by the Registrar.
                    </p>
                    <div class="mt-4 p-3 rounded-3 d-inline-block" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <span style="font-size:.82rem;color:#64748b">Current Status: </span>
                        <span style="font-weight:700;color:#1a202c"><?= htmlspecialchars($student['status']) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ===== DOCUMENTS ===== -->
        <div class="spa-view" id="view-documents">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Upload Documents</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Submit required documents to the institution</p></div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Upload Document</span></div>
                        <div class="info-card-body">
                            <div id="uploadAlert" class="alert d-none mb-3"></div>
                            <form id="uploadForm" enctype="multipart/form-data">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <input type="hidden" name="action" value="upload_document">
                                <div class="mb-3">
                                    <label class="form-label-sm">Document Type</label>
                                    <select name="doc_type" class="form-ctrl" required>
                                        <option value="Admission">Admission Letter</option>
                                        <option value="Passport">Passport Photograph</option>
                                        <option value="Certificate">Academic Certificate</option>
                                        <option value="Other">Other Document</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-sm">Select File</label>
                                    <input type="file" name="document" class="form-ctrl" accept=".jpg,.jpeg,.png,.pdf" required style="padding:8px">
                                    <div style="font-size:.75rem;color:#94a3b8;margin-top:5px"><i class="fas fa-info-circle me-1"></i>Accepted: PDF, JPG, PNG. Max 5MB.</div>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-5"><i class="fas fa-upload me-2"></i>Upload Document</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="info-card h-100">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-info-circle me-2 text-muted"></i>Upload Guidelines</span></div>
                        <div class="info-card-body">
                            <?php $tips=[
                                ['fa-file-pdf','PDF format preferred for official documents'],
                                ['fa-image','JPG or PNG for photographs (white background)'],
                                ['fa-weight-hanging','Maximum file size: 5MB per document'],
                                ['fa-check-circle','Ensure documents are clear and legible'],
                                ['fa-lock','All uploads are securely stored on the server'],
                            ]; foreach($tips as [$icon,$txt]): ?>
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div style="width:32px;height:32px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#1d4ed8;font-size:.85rem"><i class="fas <?= $icon ?>"></i></div>
                                <div style="font-size:.85rem;color:#374151;line-height:1.5;padding-top:6px"><?= $txt ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECURITY ===== -->
        <div class="spa-view" id="view-security">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Change Password</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Update your account password</p></div>
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-lock me-2 text-primary"></i>Password Update</span></div>
                        <div class="info-card-body">
                            <div id="secAlert" class="alert d-none mb-3"></div>
                            <form id="secForm">
                                <div class="mb-3"><label class="form-label-sm">Current Password</label><input type="password" class="form-ctrl" id="curPwd" required placeholder="Your current password"></div>
                                <div class="mb-3"><label class="form-label-sm">New Password</label><input type="password" class="form-ctrl" id="newPwd" required placeholder="New password (min. 6 characters)"></div>
                                <div class="mb-4"><label class="form-label-sm">Confirm New Password</label><input type="password" class="form-ctrl" id="cnfPwd" required placeholder="Confirm new password"></div>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 w-100">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== COURSE REGISTRATION ===== -->
        <div class="spa-view" id="view-courses">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 style="font-size:1.15rem;font-weight:800;margin:0">Course Registration</h3>
                    <p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Session: <?= htmlspecialchars($student['session_name']) ?> &bull; Level <?= htmlspecialchars($student['level']) ?></p>
                </div>
                <button class="btn btn-primary rounded-pill px-4" onclick="registerCourses()" id="btnRegisterCourses"><i class="fas fa-check-circle me-2"></i>Register Selected Courses</button>
            </div>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="info-card">
                        <div class="info-card-header">
                            <span class="info-card-title"><i class="fas fa-list me-2 text-primary"></i>Available Courses</span>
                            <span style="font-size:.78rem;color:#94a3b8" id="availableCount">Loading...</span>
                        </div>
                        <div class="info-card-body">
                            <div id="availableCoursesContainer" class="d-flex flex-column gap-3">
                                <div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading available courses...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="info-card">
                        <div class="info-card-header">
                            <span class="info-card-title"><i class="fas fa-check-double me-2 text-primary"></i>My Registered Courses</span>
                            <span style="font-size:.78rem;color:#94a3b8" id="registeredTotal"></span>
                        </div>
                        <div class="info-card-body p-0" style="overflow-x:auto">
                            <div id="registeredCoursesContainer">
                                <div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading registered courses...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BIODATA FORM ===== -->
        <div class="spa-view" id="view-biodata">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 style="font-size:1.15rem;font-weight:800;margin:0">Biodata Form</h3>
                    <p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your complete student biodata record</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary rounded-pill px-4" onclick="window.open('generate_pdf.php?type=biodata','_blank')"><i class="fas fa-file-pdf me-2"></i>Download as PDF</button>
                    <button class="btn btn-outline-primary rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
                </div>
            </div>
            <div class="info-card">
                <div id="biodataContainer">
                    <div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading biodata...</div>
                </div>
            </div>
        </div>

        <!-- ===== CRF PRINT ===== -->
        <div class="spa-view" id="view-crf">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 style="font-size:1.15rem;font-weight:800;margin:0">Course Registration Form (CRF)</h3>
                    <p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Preview and print your CRF</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary rounded-pill px-4" onclick="window.open('generate_pdf.php?type=crf','_blank')"><i class="fas fa-file-pdf me-2"></i>Download as PDF</button>
                    <button class="btn btn-outline-primary rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-2"></i>Print</button>
                </div>
            </div>
            <div class="info-card">
                <div id="crfContainer">
                    <div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading CRF preview...</div>
                </div>
            </div>
        </div>

    </div><!-- end content-area -->
</div><!-- end main-area -->
</div><!-- end app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF = '<?= $CSRF ?>';

function showView(viewId, btn) {
    document.querySelectorAll('.spa-view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.nav-item-btn').forEach(b => b.classList.remove('active'));
    const view = document.getElementById('view-' + viewId);
    if (view) view.classList.add('active');
    if (btn) btn.classList.add('active');
    document.getElementById('pageTitle').textContent = btn?.querySelector('.nav-text')?.textContent?.trim() || viewId;
    // Load data for specific views
    if (viewId === 'courses') { loadAvailableCourses(); loadRegisteredCourses(); }
    if (viewId === 'biodata') { loadBiodata(); }
    if (viewId === 'crf') { loadCRF(); }
}

// Document upload
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertEl = document.getElementById('uploadAlert');
    const fd = new FormData(this);
    fd.append('csrf_token', CSRF);
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
    try {
        const res  = await fetch('api.php?action=upload_document', {method:'POST',body:fd});
        const data = await res.json();
        alertEl.className = 'alert ' + (data.status ? 'alert-success' : 'alert-danger');
        alertEl.textContent = data.message;
        if (data.status) this.reset();
    } catch(err) {
        alertEl.className = 'alert alert-danger';
        alertEl.textContent = 'Upload failed. Network error.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Document';
});

// Change password
document.getElementById('secForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertEl = document.getElementById('secAlert');
    const np = document.getElementById('newPwd').value;
    const cp = document.getElementById('cnfPwd').value;
    if (np !== cp) {
        alertEl.className = 'alert alert-danger'; alertEl.textContent = 'New passwords do not match.'; return;
    }
    const res  = await fetch('api.php', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({action:'change_password',current_password:document.getElementById('curPwd').value,new_password:np})});
    const data = await res.json();
    alertEl.className = 'alert ' + (data.status ? 'alert-success' : 'alert-danger');
    alertEl.textContent = data.message;
    if (data.status) this.reset();
});

async function doLogout() {
    await fetch('api.php', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({action:'logout'})});
    window.location.href = 'index.php';
}

/* ========== COURSE REGISTRATION ========== */
async function loadAvailableCourses() {
    const container = document.getElementById('availableCoursesContainer');
    const countEl = document.getElementById('availableCount');
    container.innerHTML = '<div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading available courses...</div>';
    try {
        const res = await fetch('api.php?action=get_available_courses');
        const data = await res.json();
        if (!data.status || !Array.isArray(data.courses) || data.courses.length === 0) {
            container.innerHTML = '<div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-inbox me-2"></i>No courses available for registration at this time.</div>';
            countEl.textContent = '0 courses';
            return;
        }
        countEl.textContent = data.courses.length + ' course(s)';
        let html = '';
        data.courses.forEach(c => {
            const isCompulsory = (c.course_type || c.type || '').toLowerCase() === 'compulsory';
            html += `
            <div class="course-item" onclick="this.querySelector('input').click()">
                <input type="checkbox" value="${c.id || c.course_id}" id="course_${c.id || c.course_id}">
                <div class="course-item-code">${c.course_code || ''}</div>
                <div class="course-item-title">${c.course_title || c.title || ''}</div>
                <div class="course-item-semester">${c.semester || ''}</div>
                <span class="course-badge ${isCompulsory ? 'course-badge-compulsory' : 'course-badge-elective'}">${isCompulsory ? 'Compulsory' : 'Elective'}</span>
                <span class="course-item-units">${c.credit_units || c.units || 0} CU</span>
            </div>`;
        });
        container.innerHTML = html;
    } catch(err) {
        container.innerHTML = '<div class="text-center py-4" style="color:#f97316;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>Failed to load courses. Please try again.</div>';
    }
}

async function loadRegisteredCourses() {
    const container = document.getElementById('registeredCoursesContainer');
    const totalEl = document.getElementById('registeredTotal');
    container.innerHTML = '<div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading registered courses...</div>';
    try {
        const res = await fetch('api.php?action=get_registered_courses');
        const data = await res.json();
        if (!data.status || !Array.isArray(data.courses) || data.courses.length === 0) {
            container.innerHTML = '<div class="text-center py-4" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-inbox me-2"></i>You have not registered any courses yet.</div>';
            totalEl.textContent = '';
            return;
        }
        let totalUnits = 0;
        let html = '<table class="course-table"><thead><tr><th>S/N</th><th>Code</th><th>Title</th><th>Credit</th><th>Semester</th></tr></thead><tbody>';
        data.courses.forEach((c, i) => {
            const units = parseInt(c.credit_units || c.units || 0);
            totalUnits += units;
            html += `<tr><td>${i + 1}</td><td style="font-weight:700">${c.course_code || ''}</td><td>${c.course_title || c.title || ''}</td><td>${units}</td><td>${c.semester || ''}</td></tr>`;
        });
        html += `</tbody><tfoot><tr><td colspan="3">Total Credit Units</td><td>${totalUnits}</td><td></td></tr></tfoot></table>`;
        container.innerHTML = html;
        totalEl.textContent = totalUnits + ' Total CU';
    } catch(err) {
        container.innerHTML = '<div class="text-center py-4" style="color:#f97316;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>Failed to load registered courses.</div>';
    }
}

async function registerCourses() {
    const checkboxes = document.querySelectorAll('#availableCoursesContainer input[type=checkbox]:checked');
    if (checkboxes.length === 0) {
        Swal.fire({icon:'warning',title:'No Courses Selected',text:'Please select at least one course to register.',confirmButtonColor:'#3b82f6'});
        return;
    }
    const courseIds = Array.from(checkboxes).map(cb => cb.value);
    const btn = document.getElementById('btnRegisterCourses');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registering...';
    try {
        const res = await fetch('api.php?action=register_courses', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
            body:JSON.stringify({course_ids:courseIds})
        });
        const data = await res.json();
        if (data.status) {
            Swal.fire({icon:'success',title:'Courses Registered!',text:data.message || 'Your courses have been successfully registered.',confirmButtonColor:'#3b82f6'});
            checkboxes.forEach(cb => cb.checked = false);
            loadAvailableCourses();
            loadRegisteredCourses();
        } else {
            Swal.fire({icon:'error',title:'Registration Failed',text:data.message || 'Could not register courses. Please try again.',confirmButtonColor:'#3b82f6'});
        }
    } catch(err) {
        Swal.fire({icon:'error',title:'Network Error',text:'Failed to connect to server. Please try again.',confirmButtonColor:'#3b82f6'});
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Register Selected Courses';
}

/* ========== BIODATA ========== */
async function loadBiodata() {
    const container = document.getElementById('biodataContainer');
    container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading biodata...</div>';
    try {
        const res = await fetch('api.php?action=get_biodata');
        const data = await res.json();
        if (!data.status || !data.student) {
            container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>Biodata not available.</div>';
            return;
        }
        const s = data.student;
        let html = '<div class="info-card-body" style="padding:0">';

        // Photo + Personal Info
        html += '<div class="d-flex align-items-start gap-4 mb-4 p-4" style="background:#f8fafc;border-radius:var(--radius)  var(--radius) 0 0">';
        if (s.photo || s.passport) {
            html += `<img src="${s.photo || s.passport}" alt="Passport" style="width:100px;height:120px;object-fit:cover;border-radius:10px;border:3px solid #dbeafe">`;
        } else {
            html += `<div style="width:100px;height:120px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:800">${(s.full_name || '').split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase()}</div>`;
        }
        html += '<div style="flex:1">';
        html += '<div style="font-size:1.15rem;font-weight:800;color:#1a202c;margin-bottom:2px">' + (s.full_name || '—') + '</div>';
        html += '<div style="font-size:.82rem;color:#64748b">Student Biodata Record</div>';
        html += '</div></div>';

        // Section A: Personal Information
        html += '<div class="biodata-section-title"><i class="fas fa-user"></i> A. Personal Information</div>';
        html += '<table class="biodata-table">';
        const personalRows = [
            ['Date of Birth', s.dob ? new Date(s.dob).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}) : '—'],
            ['Gender', s.gender || '—'],
            ['Place of Birth', s.place_of_birth || s.home_town || '—'],
            ['Nationality', s.nationality || '—'],
            ['State of Origin', s.state || '—'],
            ['Local Government Area', s.lga || '—'],
            ['Home Town', s.home_town || '—'],
            ['Religion', s.religion || '—'],
            ['Marital Status', s.marital_status || '—'],
            ['Phone Number', s.phone || '—'],
            ['Email Address', s.email || '—'],
            ['Residential Address', s.address || '—'],
        ];
        personalRows.forEach(([label, value]) => {
            html += `<tr><th>${label}</th><td>${value}</td></tr>`;
        });
        html += '</table>';

        // Section B: Academic Information
        html += '<div class="biodata-section-title"><i class="fas fa-graduation-cap"></i> B. Academic Information</div>';
        html += '<table class="biodata-table">';
        const academicRows = [
            ['Admission Number', s.admission_number || '—'],
            ['Matriculation Number', s.matric_number || 'Not yet assigned'],
            ['Faculty', s.faculty_name || s.faculty || '—'],
            ['Department', s.department_name || s.department || '—'],
            ['Programme', (s.programme_name || s.programme || '—') + (s.programme_type ? ' (' + s.programme_type + ')' : '')],
            ['Current Level', s.level ? 'Level ' + s.level : '—'],
            ['Admission Date', s.admission_date ? new Date(s.admission_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}) : '—'],
            ['Session', s.session_name || '—'],
            ['Programme Duration', s.duration || '—'],
        ];
        academicRows.forEach(([label, value]) => {
            html += `<tr><th>${label}</th><td>${value}</td></tr>`;
        });
        html += '</table>';

        // Section C: Guardian/Next of Kin
        html += '<div class="biodata-section-title"><i class="fas fa-users"></i> C. Guardian / Next of Kin</div>';
        html += '<table class="biodata-table">';
        const guardianRows = [
            ['Guardian Name', s.guardian_name || '—'],
            ['Guardian Phone', s.guardian_phone || '—'],
        ];
        guardianRows.forEach(([label, value]) => {
            html += `<tr><th>${label}</th><td>${value}</td></tr>`;
        });
        html += '</table>';

        html += '</div>';
        container.innerHTML = html;
    } catch(err) {
        container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#f97316;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>Failed to load biodata.</div>';
    }
}

/* ========== CRF PREVIEW ========== */
async function loadCRF() {
    const container = document.getElementById('crfContainer');
    container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-spinner fa-spin me-2"></i>Loading CRF preview...</div>';
    try {
        const res = await fetch('api.php?action=get_courses_for_session');
        const data = await res.json();
        if (!data.status) {
            container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#94a3b8;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>No CRF data available. Please register courses first.</div>';
            return;
        }
        const s = data.student || {};
        const courses = data.courses || [];
        let html = '<div class="info-card-body" style="padding:24px">';

        // Header
        html += '<div class="pdf-header">';
        html += '<h4>Sa\'adu Zungur University, Gadau</h4>';
        html += '<h5>COURSE REGISTRATION FORM (CRF)</h5>';
        html += '<p>Session: ' + (s.session_name || data.session || '—') + ' &bull; Semester: ' + (s.semester || data.semester || '—') + '</p>';
        html += '</div>';

        // Student Info
        html += '<table class="biodata-table mb-4">';
        const studentInfo = [
            ['Student Name', s.full_name || '—'],
            ['Matric No', s.matric_number || 'Not yet assigned'],
            ['Faculty', s.faculty_name || s.faculty || '—'],
            ['Department', s.department_name || s.department || '—'],
            ['Programme', (s.programme_name || s.programme || '—') + (s.programme_type ? ' (' + s.programme_type + ')' : '')],
            ['Level', s.level ? 'Level ' + s.level : '—'],
        ];
        studentInfo.forEach(([label, value]) => {
            html += `<tr><th>${label}</th><td style="font-weight:600">${value}</td></tr>`;
        });
        html += '</table>';

        // Course Table
        if (courses.length === 0) {
            html += '<div class="text-center py-4" style="color:#94a3b8;font-size:.88rem;background:#f8fafc;border-radius:8px"><i class="fas fa-inbox me-2"></i>No courses registered for this session.</div>';
        } else {
            let totalUnits = 0;
            html += '<table class="course-table"><thead><tr><th>S/N</th><th>Course Code</th><th>Course Title</th><th>Credit Units</th><th>Semester</th><th>Status</th></tr></thead><tbody>';
            courses.forEach((c, i) => {
                const units = parseInt(c.credit_units || c.units || 0);
                totalUnits += units;
                const isCompulsory = (c.course_type || c.type || c.status || '').toLowerCase() === 'compulsory';
                html += `<tr><td>${i + 1}</td><td style="font-weight:700">${c.course_code || ''}</td><td>${c.course_title || c.title || ''}</td><td style="text-align:center">${units}</td><td>${c.semester || ''}</td><td><span class="course-badge ${isCompulsory ? 'course-badge-compulsory' : 'course-badge-elective'}">${isCompulsory ? 'Compulsory' : 'Elective'}</span></td></tr>`;
            });
            html += `</tbody><tfoot><tr><td colspan="3" style="text-align:right">Total Credit Units</td><td style="text-align:center">${totalUnits}</td><td colspan="2"></td></tr></tfoot></table>`;
        }

        // Signature lines
        html += '<div class="row mt-5">';
        html += '<div class="col-md-3"><div class="signature-line"><span class="sig-label">Student\'s Signature</span></div></div>';
        html += '<div class="col-md-3"><div class="signature-line"><span class="sig-label">Academic Adviser</span></div></div>';
        html += '<div class="col-md-3"><div class="signature-line"><span class="sig-label">Head of Department</span></div></div>';
        html += '<div class="col-md-3"><div class="signature-line"><span class="sig-label">Dean</span></div></div>';
        html += '</div>';

        html += '</div>';
        container.innerHTML = html;
    } catch(err) {
        container.innerHTML = '<div class="info-card-body text-center py-5" style="color:#f97316;font-size:.88rem"><i class="fas fa-exclamation-circle me-2"></i>Failed to load CRF preview.</div>';
    }
}
</script>
</body>
</html>
