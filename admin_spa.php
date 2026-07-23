<?php
/**
 * SAZUG SRMS — Admin Single Page Application
 * Roles: Super Administrator, Administrator, Registrar, Department Officer
 */
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Administrator','Administrator','Registrar','Department Officer'])) {
    header('Location: index.php'); exit;
}
$CSRF  = $_SESSION['csrf_token'] ?? '';
$ROLE  = $_SESSION['role'];
$UNAME = $_SESSION['username'];

$isSuperAdmin = ($ROLE === 'Super Administrator');
$isAdmin      = in_array($ROLE, ['Super Administrator','Administrator']);
$isRegistrar  = in_array($ROLE, ['Super Administrator','Administrator','Registrar']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Portal — SAZUG SRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--sb-bg:#0a1628;--sb-hover:#1a2e4a;--sb-active:#1e3a5f;--sb-border:rgba(255,255,255,.07);--sb-text:rgba(255,255,255,.65);--sb-active-text:#fff;--accent:#3b82f6;--accent-dark:#1d4ed8;--danger:#f97316;--success:#22c55e;--warning:#f59e0b;--gold:#f5a623;--body-bg:#f0f4f8;--card-shadow:0 4px 20px rgba(0,0,0,.06);--radius:14px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--body-bg);color:#1a202c;overflow-x:hidden}
/* ===== LAYOUT ===== */
.app-wrapper{display:flex;height:100vh;overflow:hidden}
/* ===== SIDEBAR ===== */
.sidebar{width:268px;background:var(--sb-bg);display:flex;flex-direction:column;flex-shrink:0;transition:width .3s cubic-bezier(.4,0,.2,1);position:relative;z-index:100;overflow:hidden}
.sidebar.collapsed{width:72px}
.sidebar-logo{padding:20px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--sb-border);flex-shrink:0}
.sidebar-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0}
.sidebar-logo-text{font-size:1.05rem;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;opacity:1;transition:opacity .2s}
.sidebar.collapsed .sidebar-logo-text{opacity:0;width:0}
.sidebar-user{padding:14px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--sb-border);flex-shrink:0}
.sidebar-avatar{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.95rem;flex-shrink:0}
.sidebar-user-info{overflow:hidden;transition:opacity .2s}
.sidebar.collapsed .sidebar-user-info{opacity:0;width:0}
.sidebar-user-name{font-size:.82rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sidebar-user-role{font-size:.72rem;color:rgba(255,255,255,.45);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 0}
.sidebar-scroll::-webkit-scrollbar{width:4px}.sidebar-scroll::-webkit-scrollbar-track{background:transparent}.sidebar-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:4px}
.sidebar-section{padding:14px 14px 4px;font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.28);white-space:nowrap;overflow:hidden;transition:opacity .2s}
.sidebar.collapsed .sidebar-section{opacity:0}
.nav-item-btn{display:flex;align-items:center;gap:13px;padding:10px 14px;color:var(--sb-text);text-decoration:none;border-radius:10px;margin:2px 8px;cursor:pointer;transition:all .2s;white-space:nowrap;overflow:hidden;border:none;background:transparent;width:calc(100% - 16px);font-family:'Inter',sans-serif}
.nav-item-btn:hover{background:var(--sb-hover);color:rgba(255,255,255,.9)}
.nav-item-btn.active{background:var(--sb-active);color:var(--sb-active-text);box-shadow:inset 3px 0 0 var(--accent)}
.nav-item-btn .nav-icon{width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;transition:transform .2s}
.nav-item-btn:hover .nav-icon{transform:scale(1.1)}
.nav-item-btn .nav-text{font-size:.845rem;font-weight:500;transition:opacity .2s;overflow:hidden}
.sidebar.collapsed .nav-item-btn .nav-text{opacity:0;width:0}
.nav-badge{background:var(--accent);color:#fff;font-size:.62rem;padding:2px 7px;border-radius:20px;font-weight:700;flex-shrink:0;margin-left:auto;transition:opacity .2s}
.sidebar.collapsed .nav-badge{opacity:0}
.sidebar-bottom{padding:12px 8px;border-top:1px solid var(--sb-border);flex-shrink:0}
.toggle-btn{width:100%;padding:9px;background:rgba(255,255,255,.05);border:none;border-radius:8px;color:rgba(255,255,255,.5);cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.toggle-btn:hover{background:rgba(255,255,255,.1);color:#fff}
/* ===== MAIN ===== */
.main-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:#fff;padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8edf3;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.topbar-left{display:flex;align-items:center;gap:16px}
.page-title{font-size:1.05rem;font-weight:700;color:#1a202c}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-btn{width:36px;height:36px;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;color:#64748b;font-size:.85rem}
.topbar-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.topbar-role-badge{background:#eef2ff;color:#3730a3;padding:5px 12px;border-radius:20px;font-size:.75rem;font-weight:700}
.content-area{flex:1;overflow-y:auto;padding:26px;background:var(--body-bg)}
.content-area::-webkit-scrollbar{width:6px}.content-area::-webkit-scrollbar-track{background:transparent}.content-area::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:6px}
/* ===== VIEWS ===== */
.spa-view{display:none;animation:fadeIn .3s ease}
.spa-view.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
/* ===== CARDS ===== */
.stat-card{background:#fff;border-radius:var(--radius);padding:22px 24px;box-shadow:var(--card-shadow);border:1px solid #e8edf3;display:flex;align-items:center;gap:18px;transition:all .3s;overflow:hidden;position:relative}
.stat-card::before{content:'';position:absolute;top:0;right:0;bottom:0;width:4px}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.1)}
.stat-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.stat-num{font-size:2rem;font-weight:800;line-height:1}
.stat-label{font-size:.8rem;color:#94a3b8;font-weight:500;margin-top:4px}
.stat-trend{font-size:.72rem;display:flex;align-items:center;gap:4px;margin-top:6px}
/* ===== TABLE CARD ===== */
.table-card{background:#fff;border-radius:var(--radius);box-shadow:var(--card-shadow);border:1px solid #e8edf3;overflow:hidden}
.table-card-header{padding:18px 24px;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.table-card-title{font-size:.95rem;font-weight:700;color:#1a202c}
.table-card-body{padding:0}
/* ===== CHART CARD ===== */
.chart-card{background:#fff;border-radius:var(--radius);box-shadow:var(--card-shadow);border:1px solid #e8edf3;padding:22px}
.chart-title{font-size:.9rem;font-weight:700;color:#1a202c;margin-bottom:4px}
.chart-sub{font-size:.76rem;color:#94a3b8}
/* ===== STATUS BADGES ===== */
.badge-active{background:#dcfce7;color:#15803d;padding:4px 11px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-suspended{background:#fef9c3;color:#a16207;padding:4px 11px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-graduated{background:#dbeafe;color:#1d4ed8;padding:4px 11px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-withdrawn{background:#fff7ed;color:#f97316;padding:4px 11px;border-radius:20px;font-size:.73rem;font-weight:700}
/* ===== FORMS ===== */
.form-ctrl{border:2px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:.88rem;transition:border-color .2s;font-family:'Inter',sans-serif}
.form-ctrl:focus{border-color:var(--accent);outline:none;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.form-label-sm{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px}
/* ===== MODALS ===== */
.modal-content{border:none;border-radius:20px;box-shadow:0 25px 80px rgba(0,0,0,.2)}
.modal-header{border-bottom:1px solid #f0f4f8;padding:20px 24px}
.modal-footer{border-top:1px solid #f0f4f8;padding:16px 24px}
/* ===== QUICK ACTIONS ===== */
.quick-action{background:#fff;border:1px solid #e8edf3;border-radius:12px;padding:18px 16px;text-align:center;cursor:pointer;transition:all .25s;text-decoration:none;display:block}
.quick-action:hover{background:var(--accent);border-color:var(--accent);transform:translateY(-4px);box-shadow:0 12px 30px rgba(59,130,246,.25)}
.quick-action:hover .qa-icon,.quick-action:hover .qa-label{color:#fff!important}
.qa-icon{font-size:1.6rem;margin-bottom:10px;display:block}
.qa-label{font-size:.8rem;font-weight:600;color:#374151}
/* ===== ACTIVITY FEED ===== */
.activity-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f8fafc}
.activity-item:last-child{border-bottom:none}
.activity-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:5px}
/* ===== SECTION HEADER ===== */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.section-header-left h3{font-size:1.1rem;font-weight:800;color:#1a202c;margin:0}
.section-header-left p{font-size:.82rem;color:#94a3b8;margin:4px 0 0}
/* ===== RESPONSIVE ===== */
@media(max-width:900px){.sidebar{position:fixed;top:0;left:0;bottom:0;z-index:1000;transform:translateX(-100%);transition:transform .3s}.sidebar.mobile-open{transform:translateX(0)}.sidebar.collapsed{width:268px;transform:translateX(-100%)}.sidebar.collapsed.mobile-open{transform:translateX(0)}.main-area{width:100%}}
/* ===== REQUEST TABLE ===== */
.request-table{font-size:.85rem;border-collapse:collapse}
.request-table thead th{font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;white-space:nowrap}
.request-table tbody td{vertical-align:middle;padding:12px 14px;border-bottom:1px solid #f0f4f8}
.request-table tbody tr:hover{background:#f8fafc}
/* ===== TYPE BADGE ===== */
.type-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:.74rem;font-weight:700;white-space:nowrap}
/* ===== CERT TABLE ===== */
.cert-table{font-size:.85rem;border-collapse:collapse}
.cert-table thead th{font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;white-space:nowrap}
.cert-table tbody td{vertical-align:middle;padding:12px 14px;border-bottom:1px solid #f0f4f8}
.cert-table tbody tr:hover{background:#f8fafc}
</style>
</head>
<body>

<div class="app-wrapper">
<!-- ===================== SIDEBAR ===================== -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-university"></i></div>
        <div class="sidebar-logo-text">SAZUG SRMS</div>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr($UNAME,0,2)) ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= htmlspecialchars($UNAME) ?></div>
            <div class="sidebar-user-role"><?= htmlspecialchars($ROLE) ?></div>
        </div>
    </div>
    <div class="sidebar-scroll">
        <div class="sidebar-section">Main</div>
        <button class="nav-item-btn active" data-view="dashboard" onclick="showView('dashboard',this)">
            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
            <span class="nav-text">Dashboard</span>
        </button>
        <div class="sidebar-section">Academic</div>
        <button class="nav-item-btn" data-view="students" onclick="showView('students',this)">
            <span class="nav-icon"><i class="fas fa-user-graduate"></i></span>
            <span class="nav-text">Students</span>
        </button>
        <?php if($isRegistrar): ?>
        <button class="nav-item-btn" data-view="faculties" onclick="showView('faculties',this)">
            <span class="nav-icon"><i class="fas fa-building-columns"></i></span>
            <span class="nav-text">Faculties</span>
        </button>
        <button class="nav-item-btn" data-view="departments" onclick="showView('departments',this)">
            <span class="nav-icon"><i class="fas fa-sitemap"></i></span>
            <span class="nav-text">Departments</span>
        </button>
        <button class="nav-item-btn" data-view="programmes" onclick="showView('programmes',this)">
            <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span>
            <span class="nav-text">Programmes</span>
        </button>
        <button class="nav-item-btn" data-view="courses" onclick="showView('courses',this)">
            <span class="nav-icon"><i class="fas fa-book-open"></i></span>
            <span class="nav-text">Courses</span>
        </button>
        <button class="nav-item-btn" data-view="sessions" onclick="showView('sessions',this)">
            <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>
            <span class="nav-text">Sessions</span>
        </button>
        <?php endif; ?>
        <div class="sidebar-section">Manage</div>
        <button class="nav-item-btn" data-view="regrequests" onclick="showView('regrequests',this)">
            <span class="nav-icon"><i class="fas fa-user-check"></i></span>
            <span class="nav-text">Registration Approvals</span>
            <span class="nav-badge" id="pendingCountBadge" style="display:none">0</span>
        </button>
        <div class="sidebar-section">Management</div>
        <button class="nav-item-btn" data-view="certmanage" onclick="showView('certmanage',this)">
            <span class="nav-icon"><i class="fas fa-certificate"></i></span>
            <span class="nav-text">Certificate Management</span>
        </button>
        <?php if($isAdmin): ?>
        <div class="sidebar-section">Administration</div>
        <button class="nav-item-btn" data-view="staff" onclick="showView('staff',this)">
            <span class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></span>
            <span class="nav-text">Staff Directory</span>
        </button>
        <button class="nav-item-btn" data-view="users" onclick="showView('users',this)">
            <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
            <span class="nav-text">User Accounts</span>
        </button>
        <?php endif; ?>
        <?php if($isSuperAdmin): ?>
        <button class="nav-item-btn" data-view="audit" onclick="showView('audit',this)">
            <span class="nav-icon"><i class="fas fa-history"></i></span>
            <span class="nav-text">Audit Logs</span>
        </button>
        <div class="sidebar-section">System</div>
        <button class="nav-item-btn" data-view="settings" onclick="showView('settings',this)">
            <span class="nav-icon"><i class="fas fa-cog"></i></span>
            <span class="nav-text">Settings</span>
        </button>
        <?php endif; ?>
    </div>
    <div class="sidebar-bottom">
        <button class="nav-item-btn" onclick="doLogout()" style="color:rgba(239,68,68,.8)">
            <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="nav-text">Logout</span>
        </button>
        <button class="toggle-btn mt-2" id="sidebarToggle">
            <i class="fas fa-bars" id="toggleIcon"></i>
            <span class="nav-text" style="font-size:.8rem">Collapse</span>
        </button>
    </div>
</nav>

<!-- ===================== MAIN ===================== -->
<div class="main-area">
    <div class="topbar">
        <div class="topbar-left">
            <button class="topbar-btn d-md-none" id="mobileSidebarBtn"><i class="fas fa-bars"></i></button>
            <div>
                <div class="page-title" id="pageTitle">Dashboard</div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-role-badge"><?= htmlspecialchars($ROLE) ?></span>
            <div class="topbar-btn" title="Refresh" onclick="refreshCurrentView()"><i class="fas fa-sync-alt"></i></div>
            <a href="index.php" class="topbar-btn text-decoration-none" title="Home"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <div class="content-area">

        <!-- ========== DASHBOARD ========== -->
        <div class="spa-view active" id="view-dashboard">
            <div class="section-header">
                <div class="section-header-left">
                    <h3>Welcome back, <?= htmlspecialchars($UNAME) ?></h3>
                    <p id="dashDate"></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="loadDashboard()"><i class="fas fa-sync me-1"></i>Refresh</button>
                </div>
            </div>
            <!-- Stat Cards -->
            <div class="row g-3 mb-4" id="statCards">
                <?php $cards=[
                    ['key'=>'total_students','label'=>'Total Students','icon'=>'fa-users','c1'=>'#dbeafe','c2'=>'#3b82f6','bc'=>'#3b82f6'],
                    ['key'=>'active_students','label'=>'Active Students','icon'=>'fa-user-check','c1'=>'#dcfce7','c2'=>'#22c55e','bc'=>'#22c55e'],
                    ['key'=>'graduated_students','label'=>'Graduated','icon'=>'fa-graduation-cap','c1'=>'#fef9c3','c2'=>'#f59e0b','bc'=>'#f59e0b'],
                    ['key'=>'total_staff','label'=>'Staff Members','icon'=>'fa-chalkboard-teacher','c1'=>'#f3e8ff','c2'=>'#a855f7','bc'=>'#a855f7'],
                    ['key'=>'total_departments','label'=>'Departments','icon'=>'fa-sitemap','c1'=>'#ffedd5','c2'=>'#f97316','bc'=>'#f97316'],
                    ['key'=>'total_certificates','label'=>'Certificates Issued','icon'=>'fa-certificate','c1'=>'#fce7f3','c2'=>'#ec4899','bc'=>'#ec4899'],
                ]; foreach($cards as $i=>$c): ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="stat-card" style="--bc:<?= $c['bc'] ?>">
                        <style>.stat-card:nth-child(<?= $i+1 ?>)::before{background:var(--bc)}</style>
                        <div class="stat-icon" style="background:<?= $c['c1'] ?>;color:<?= $c['c2'] ?>"><i class="fas <?= $c['icon'] ?>"></i></div>
                        <div>
                            <div class="stat-num text-primary" id="stat-<?= $c['key'] ?>">—</div>
                            <div class="stat-label"><?= $c['label'] ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Active Session Banner -->
            <div class="alert d-flex align-items-center gap-3 mb-4 border-0 rounded-3" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;padding:14px 20px">
                <i class="fas fa-calendar-check fa-lg"></i>
                <div><strong>Active Session:</strong> <span id="activeSessionBadge">Loading...</span></div>
            </div>
            <!-- Charts + Activity -->
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-title">Enrollment by Status</div>
                        <div class="chart-sub mb-3">Current student distribution</div>
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-title">Students by Level</div>
                        <div class="chart-sub mb-3">Distribution across levels</div>
                        <canvas id="levelChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-title">Recent Activity</div>
                        <div class="chart-sub mb-3">Latest system actions</div>
                        <div id="activityFeed" style="max-height:250px;overflow-y:auto"></div>
                    </div>
                </div>
            </div>
            <!-- Dept Chart + Quick Actions -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="chart-card">
                        <div class="chart-title">Students by Department</div>
                        <div class="chart-sub mb-3">Enrollment distribution across departments</div>
                        <canvas id="deptChart" height="120"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-title mb-3">Quick Actions</div>
                        <div class="row g-2">
                            <?php $qa=[
                                ['fa-user-plus','Add Student','students','showAddStudent'],
                                ['fa-id-card','Issue Cert','students',''],
                                ['fa-building-columns','Add Faculty','faculties','showAddFaculty'],
                                ['fa-calendar-plus','New Session','sessions','showAddSession'],
                            ]; foreach($qa as [$icon,$label,$view,$fn]): ?>
                            <div class="col-6">
                                <a class="quick-action" href="#" onclick="<?= $view ? "showView('$view',document.querySelector('[data-view=\'$view\']'))" : '' ?><?= $fn ? ";$fn()" : '' ?>;return false">
                                    <span class="qa-icon" style="color:var(--accent)"><i class="fas <?= $icon ?>"></i></span>
                                    <span class="qa-label"><?= $label ?></span>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STUDENTS ========== -->
        <div class="spa-view" id="view-students">
            <div class="section-header">
                <div class="section-header-left">
                    <h3>Student Registry</h3>
                    <p>Manage all student records, statuses, and documents</p>
                </div>
                <?php if($isRegistrar): ?>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" id="addStudentBtn" onclick="showAddStudent()">
                    <i class="fas fa-plus me-1"></i> New Student
                </button>
                <?php endif; ?>
            </div>
            <!-- Filters -->
            <div class="table-card mb-4">
                <div class="table-card-header">
                    <span class="table-card-title"><i class="fas fa-filter me-2 text-muted"></i>Filter Records</span>
                </div>
                <div class="p-3 pb-2">
                    <div class="row g-2">
                        <div class="col-md-3"><select class="form-ctrl w-100" id="filterDept" onchange="reloadStudentsTable()"><option value="">All Departments</option></select></div>
                        <div class="col-md-2"><select class="form-ctrl w-100" id="filterStatus" onchange="reloadStudentsTable()"><option value="">All Status</option><option>Active</option><option>Suspended</option><option>Graduated</option><option>Withdrawn</option></select></div>
                        <div class="col-md-2"><select class="form-ctrl w-100" id="filterLevel" onchange="reloadStudentsTable()"><option value="">All Levels</option><option>100</option><option>200</option><option>300</option><option>400</option><option>500</option></select></div>
                        <div class="col-md-3"><input type="text" class="form-ctrl w-100" id="filterSearch" placeholder="Search name, admission no..." oninput="debounce(reloadStudentsTable,400)()"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100 rounded-pill" onclick="clearFilters()"><i class="fas fa-times me-1"></i>Clear</button></div>
                    </div>
                </div>
            </div>
            <div class="table-card">
                <div class="table-card-body">
                    <div class="table-responsive">
                        <table id="studentsTable" class="table table-hover mb-0" style="width:100%">
                            <thead style="background:#f8fafc"><tr>
                                <th class="ps-4">Admission No.</th><th>Name</th><th>Department</th>
                                <th>Programme</th><th>Level</th><th>Status</th><th class="text-end pe-4">Actions</th>
                            </tr></thead>
                            <tbody id="studentsBody"><tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== FACULTIES ========== -->
        <div class="spa-view" id="view-faculties">
            <div class="section-header">
                <div class="section-header-left"><h3>Faculties</h3><p>Manage institutional faculties</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddFaculty()"><i class="fas fa-plus me-1"></i> New Faculty</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="facultiesTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Code</th><th>Name</th><th>HOD</th><th>Departments</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="facultiesBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== DEPARTMENTS ========== -->
        <div class="spa-view" id="view-departments">
            <div class="section-header">
                <div class="section-header-left"><h3>Departments</h3><p>Manage departments within faculties</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddDepartment()"><i class="fas fa-plus me-1"></i> New Department</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="depsTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Code</th><th>Name</th><th>Faculty</th><th>Students</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="depsBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== PROGRAMMES ========== -->
        <div class="spa-view" id="view-programmes">
            <div class="section-header">
                <div class="section-header-left"><h3>Programmes</h3><p>Academic programmes offered</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddProgramme()"><i class="fas fa-plus me-1"></i> New Programme</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="progsTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Programme</th><th>Type</th><th>Department</th><th>Faculty</th><th>Duration</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="progsBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== COURSES ========== -->
        <div class="spa-view" id="view-courses">
            <div class="section-header">
                <div class="section-header-left"><h3>Courses</h3><p>Course catalogue management</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddCourse()"><i class="fas fa-plus me-1"></i> New Course</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="coursesTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Code</th><th>Title</th><th>Department</th><th>Level</th><th>Credits</th><th>Semester</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="coursesBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== SESSIONS ========== -->
        <div class="spa-view" id="view-sessions">
            <div class="section-header">
                <div class="section-header-left"><h3>Academic Sessions</h3><p>Manage academic year sessions</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddSession()"><i class="fas fa-plus me-1"></i> New Session</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="sessionsTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Session</th><th>Semester</th><th>Start</th><th>End</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="sessionsBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== STAFF ========== -->
        <div class="spa-view" id="view-staff">
            <div class="section-header">
                <div class="section-header-left"><h3>Staff Directory</h3><p>All staff members and their details</p></div>
                <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="showAddStaff()"><i class="fas fa-plus me-1"></i> New Staff</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="staffTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Name</th><th>Username</th><th>Role</th><th>Department</th><th>Phone</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="staffBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== USERS ========== -->
        <div class="spa-view" id="view-users">
            <div class="section-header">
                <div class="section-header-left"><h3>User Accounts</h3><p>System user management and access control</p></div>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="usersTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Last Login</th><th class="text-end pe-4">Actions</th></tr></thead>
                    <tbody id="usersBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== AUDIT ========== -->
        <div class="spa-view" id="view-audit">
            <div class="section-header">
                <div class="section-header-left"><h3>Audit Logs</h3><p>Complete system activity history</p></div>
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="loadAuditLogs()"><i class="fas fa-sync me-1"></i>Refresh</button>
            </div>
            <div class="table-card"><div class="table-responsive">
                <table id="auditTable" class="table table-hover mb-0" style="width:100%">
                    <thead style="background:#f8fafc"><tr><th class="ps-4">Action</th><th>User</th><th>Entity</th><th>IP Address</th><th>Date & Time</th></tr></thead>
                    <tbody id="auditBody"></tbody>
                </table>
            </div></div>
        </div>

        <!-- ========== SETTINGS ========== -->
        <div class="spa-view" id="view-settings">
            <div class="section-header">
                <div class="section-header-left"><h3>System Settings</h3><p>Account, security, and system configuration</p></div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="chart-card">
                        <div class="chart-title mb-1">Change Password</div>
                        <div class="chart-sub mb-3">Update your account password</div>
                        <div id="pwdAlert" class="alert d-none mb-3"></div>
                        <form id="changePwdForm">
                            <div class="mb-3"><label class="form-label-sm">Current Password</label><input type="password" class="form-ctrl w-100" id="currentPwd" placeholder="Current password" required></div>
                            <div class="mb-3"><label class="form-label-sm">New Password</label><input type="password" class="form-ctrl w-100" id="newPwd" placeholder="New password (min. 6 chars)" required></div>
                            <div class="mb-3"><label class="form-label-sm">Confirm New Password</label><input type="password" class="form-ctrl w-100" id="confirmPwd" placeholder="Confirm new password" required></div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Update Password</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-card mb-4">
                        <div class="chart-title mb-1">System Information</div>
                        <div class="chart-sub mb-3">Current system state overview</div>
                        <div class="d-flex flex-column gap-2">
                            <?php $info=[['PHP Version',PHP_VERSION],['Server OS',PHP_OS],['Current Role',$ROLE],['Session ID',substr(session_id(),0,16).'...']]; foreach($info as [$k,$v]): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span style="font-size:.85rem;color:#64748b"><?= $k ?></span>
                                <span style="font-size:.85rem;font-weight:600;color:#1a202c"><?= htmlspecialchars($v) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title mb-1">System Links</div>
                        <div class="chart-sub mb-3">Quick access tools</div>
                        <div class="d-flex flex-column gap-2">
                            <a href="install.php" target="_blank" class="btn btn-sm btn-outline-warning rounded-pill"><i class="fas fa-database me-2"></i>Database Installer</a>
                            <a href="index.php?verify=" target="_blank" class="btn btn-sm btn-outline-info rounded-pill"><i class="fas fa-qrcode me-2"></i>Certificate Verification Portal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== REGISTRATION APPROVALS ========== -->
        <div class="spa-view" id="view-regrequests">
            <div class="section-header">
                <div class="section-header-left"><h3>Registration Approvals</h3><p>Review and manage pending registration requests</p></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="loadRegistrationRequests(currentRegFilter)"><i class="fas fa-sync me-1"></i>Refresh</button>
                </div>
            </div>
            <div class="table-card mb-4">
                <div class="table-card-header">
                    <div class="d-flex gap-2 flex-wrap" id="regFilterTabs">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="filterRegRequests('Pending',this)">Pending</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="filterRegRequests('Approved',this)">Approved</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="filterRegRequests('Rejected',this)">Rejected</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="filterRegRequests('All',this)">All</button>
                    </div>
                    <span class="text-muted" style="font-size:.8rem" id="regRequestCount"></span>
                </div>
            </div>
            <div class="table-card">
                <div class="table-card-body">
                    <div class="table-responsive">
                        <table class="request-table table table-hover mb-0" style="width:100%">
                            <thead style="background:#f8fafc"><tr>
                                <th class="ps-4">Type</th><th>Full Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Department</th><th>Programme</th><th>Date Applied</th><th class="text-end pe-4">Actions</th>
                            </tr></thead>
                            <tbody id="regRequestsBody"><tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== CERTIFICATE MANAGEMENT ========== -->
        <div class="spa-view" id="view-certmanage">
            <div class="section-header">
                <div class="section-header-left"><h3>Certificate Management</h3><p>View and manage all issued certificates</p></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="loadCertificates()"><i class="fas fa-sync me-1"></i>Refresh</button>
                </div>
            </div>
            <div class="table-card mb-4">
                <div class="table-card-header">
                    <span class="table-card-title"><i class="fas fa-filter me-2 text-muted"></i>Filter Certificates</span>
                    <span class="text-muted" style="font-size:.8rem" id="certTotalCount">Loading count...</span>
                </div>
                <div class="p-3 pb-2">
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" class="form-ctrl w-100" id="certSearch" placeholder="Search name, cert number, matric..." oninput="debounce(loadCertificates,400)()"></div>
                        <div class="col-md-3"><select class="form-ctrl w-100" id="certFilterFaculty" onchange="loadCertificates()"><option value="">All Faculties</option></select></div>
                        <div class="col-md-3"><select class="form-ctrl w-100" id="certFilterDept" onchange="loadCertificates()"><option value="">All Departments</option></select></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100 rounded-pill" onclick="document.getElementById('certSearch').value='';document.getElementById('certFilterFaculty').value='';document.getElementById('certFilterDept').value='';loadCertificates()"><i class="fas fa-times me-1"></i>Clear</button></div>
                    </div>
                </div>
            </div>
            <div class="table-card">
                <div class="table-card-body">
                    <div class="table-responsive">
                        <table class="cert-table table table-hover mb-0" style="width:100%">
                            <thead style="background:#f8fafc"><tr>
                                <th class="ps-4">Certificate No</th><th>Student Name</th><th>Matric No</th><th>Programme</th><th>Department</th><th>Faculty</th><th>Issue Date</th><th class="text-end pe-4">Actions</th>
                            </tr></thead>
                            <tbody id="certsBody"><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- end content-area -->
</div><!-- end main-area -->
</div><!-- end app-wrapper -->

<!-- ========== STUDENT MODAL ========== -->
<div class="modal fade" id="studentModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header" style="background:linear-gradient(135deg,#0a2540,#1e3a5f);color:#fff">
    <h5 class="modal-title fw-bold"><i class="fas fa-user-graduate me-2"></i><span id="studentModalTitle">Add New Student</span></h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0">
<form id="studentForm" class="p-4">
<input type="hidden" id="studentId">
<div class="row g-3">
    <div class="col-12"><h6 class="fw-bold text-primary mb-0" style="font-size:.82rem;text-transform:uppercase;letter-spacing:1px"><i class="fas fa-address-card me-2"></i>Academic Identity</h6><hr class="mt-2"></div>
    <div class="col-md-4"><label class="form-label-sm">Admission Number *</label><input class="form-ctrl w-100" id="s_adm" placeholder="e.g. SAZUG/2024/001" required></div>
    <div class="col-md-4"><label class="form-label-sm">Matric Number</label><input class="form-ctrl w-100" id="s_matric" placeholder="e.g. CSC/24/001"></div>
    <div class="col-md-4"><label class="form-label-sm">Admission Date *</label><input type="date" class="form-ctrl w-100" id="s_admdate" required></div>

    <div class="col-12 mt-2"><h6 class="fw-bold text-primary mb-0" style="font-size:.82rem;text-transform:uppercase;letter-spacing:1px"><i class="fas fa-user me-2"></i>Personal Information</h6><hr class="mt-2"></div>
    <div class="col-md-6"><label class="form-label-sm">Full Name *</label><input class="form-ctrl w-100" id="s_name" placeholder="Full legal name" required></div>
    <div class="col-md-3"><label class="form-label-sm">Date of Birth *</label><input type="date" class="form-ctrl w-100" id="s_dob" required></div>
    <div class="col-md-3"><label class="form-label-sm">Gender *</label>
        <select class="form-ctrl w-100" id="s_gender" required><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select>
    </div>
    <div class="col-md-4"><label class="form-label-sm">Phone Number</label><input class="form-ctrl w-100" id="s_phone" placeholder="e.g. 08012345678"></div>
    <div class="col-md-4"><label class="form-label-sm">State</label><input class="form-ctrl w-100" id="s_state" placeholder="State of origin"></div>
    <div class="col-md-4"><label class="form-label-sm">LGA</label><input class="form-ctrl w-100" id="s_lga" placeholder="Local Government Area"></div>
    <div class="col-12"><label class="form-label-sm">Residential Address</label><textarea class="form-ctrl w-100" id="s_address" rows="2" placeholder="Full residential address"></textarea></div>

    <div class="col-12 mt-2"><h6 class="fw-bold text-primary mb-0" style="font-size:.82rem;text-transform:uppercase;letter-spacing:1px"><i class="fas fa-university me-2"></i>Academic Details</h6><hr class="mt-2"></div>
    <div class="col-md-3"><label class="form-label-sm">Faculty *</label><select class="form-ctrl w-100" id="s_faculty" required onchange="loadProgrammesByFaculty(this.value)"><option value="">Select Faculty</option></select></div>
    <div class="col-md-3"><label class="form-label-sm">Department *</label><select class="form-ctrl w-100" id="s_dept" required><option value="">Select Dept</option></select></div>
    <div class="col-md-3"><label class="form-label-sm">Programme *</label><select class="form-ctrl w-100" id="s_prog" required><option value="">Select Programme</option></select></div>
    <div class="col-md-1"><label class="form-label-sm">Level *</label><select class="form-ctrl w-100" id="s_level" required><option>100</option><option>200</option><option>300</option><option>400</option><option>500</option></select></div>
    <div class="col-md-2"><label class="form-label-sm">Session *</label><select class="form-ctrl w-100" id="s_session" required><option value="">Select</option></select></div>
    <div class="col-md-3"><label class="form-label-sm">Nationality</label><input class="form-ctrl w-100" id="s_nation" value="Nigerian"></div>

    <div class="col-12 mt-2"><h6 class="fw-bold text-primary mb-0" style="font-size:.82rem;text-transform:uppercase;letter-spacing:1px"><i class="fas fa-users me-2"></i>Guardian Information</h6><hr class="mt-2"></div>
    <div class="col-md-4"><label class="form-label-sm">Guardian Name</label><input class="form-ctrl w-100" id="s_gname" placeholder="Guardian full name"></div>
    <div class="col-md-4"><label class="form-label-sm">Guardian Phone</label><input class="form-ctrl w-100" id="s_gphone" placeholder="Guardian phone number"></div>
    <div class="col-md-4"><label class="form-label-sm">Guardian Address</label><input class="form-ctrl w-100" id="s_gaddress" placeholder="Guardian address"></div>
</div>
</form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary rounded-pill px-5" onclick="saveStudent()"><i class="fas fa-save me-2"></i>Save Student</button>
</div>
</div></div></div>

<!-- ========== STUDENT DETAIL MODAL ========== -->
<div class="modal fade" id="studentDetailModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header" style="background:linear-gradient(135deg,#0a2540,#1e3a5f);color:#fff">
    <h5 class="modal-title fw-bold"><i class="fas fa-id-card me-2"></i>Student Profile</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0" id="studentDetailContent"></div>
<div class="modal-footer">
    <div id="studentDetailActions" class="d-flex gap-2 flex-wrap"></div>
    <button type="button" class="btn btn-outline-secondary rounded-pill ms-auto px-4" data-bs-dismiss="modal">Close</button>
</div>
</div></div></div>

<!-- ========== CERTIFICATE DETAIL MODAL ========== -->
<div class="modal fade" id="certDetailModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header" style="background:linear-gradient(135deg,#0a2540,#1e3a5f);color:#fff">
    <h5 class="modal-title fw-bold"><i class="fas fa-certificate me-2"></i>Certificate Details</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0" id="certDetailContent"></div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary rounded-pill ms-auto px-4" data-bs-dismiss="modal">Close</button>
</div>
</div></div></div>

<!-- ========== GENERIC MODALS ========== -->
<div class="modal fade" id="facultyModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold" id="facultyModalTitle">Add Faculty</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="facultyId">
    <div class="mb-3"><label class="form-label-sm">Faculty Name *</label><input class="form-ctrl w-100" id="fac_name" placeholder="e.g. Faculty of Engineering" required></div>
    <div class="mb-3"><label class="form-label-sm">Code *</label><input class="form-ctrl w-100" id="fac_code" placeholder="e.g. FET" style="text-transform:uppercase" required></div>
    <div class="mb-3"><label class="form-label-sm">HOD Name</label><input class="form-ctrl w-100" id="fac_hod" placeholder="Head of Faculty"></div>
    <div class="mb-3"><label class="form-label-sm">Description</label><textarea class="form-ctrl w-100" id="fac_desc" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveFaculty()">Save</button></div>
</div></div></div>

<div class="modal fade" id="deptModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold" id="deptModalTitle">Add Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="deptId">
    <div class="mb-3"><label class="form-label-sm">Faculty *</label><select class="form-ctrl w-100" id="dept_faculty" required><option value="">Select Faculty</option></select></div>
    <div class="mb-3"><label class="form-label-sm">Department Name *</label><input class="form-ctrl w-100" id="dept_name" placeholder="e.g. Computer Engineering" required></div>
    <div class="mb-3"><label class="form-label-sm">Code *</label><input class="form-ctrl w-100" id="dept_code" placeholder="e.g. CEN" style="text-transform:uppercase" required></div>
    <div class="mb-3"><label class="form-label-sm">HOD Name</label><input class="form-ctrl w-100" id="dept_hod" placeholder="Head of Department"></div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveDepartment()">Save</button></div>
</div></div></div>

<div class="modal fade" id="progModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold" id="progModalTitle">Add Programme</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" id="progId">
    <div class="mb-3"><label class="form-label-sm">Department *</label><select class="form-ctrl w-100" id="prog_dept" required><option value="">Select Dept</option></select></div>
    <div class="mb-3"><label class="form-label-sm">Programme Name *</label><input class="form-ctrl w-100" id="prog_name" placeholder="Programme name" required></div>
    <div class="mb-3"><label class="form-label-sm">Type *</label><select class="form-ctrl w-100" id="prog_type" required><option>Undergraduate</option><option>Postgraduate</option><option>Masters</option><option>PhD</option></select></div>
    <div class="mb-3"><label class="form-label-sm">Duration (years) *</label><input type="number" class="form-ctrl w-100" id="prog_dur" value="4" min="1" max="7" required></div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveProgramme()">Save</button></div>
</div></div></div>

<div class="modal fade" id="courseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold">Add Course</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label-sm">Department *</label><select class="form-ctrl w-100" id="crs_dept" required onchange="loadProgrammesForCourse(this.value)"><option value="">Select Dept</option></select></div>
    <div class="mb-3"><label class="form-label-sm">Programme *</label><select class="form-ctrl w-100" id="crs_prog" required><option value="">Select Programme</option></select></div>
    <div class="mb-3"><label class="form-label-sm">Course Code *</label><input class="form-ctrl w-100" id="crs_code" placeholder="e.g. CSC101" style="text-transform:uppercase" required></div>
    <div class="mb-3"><label class="form-label-sm">Course Title *</label><input class="form-ctrl w-100" id="crs_title" placeholder="Full course title" required></div>
    <div class="row g-2 mb-3">
        <div class="col-4"><label class="form-label-sm">Level *</label><select class="form-ctrl w-100" id="crs_level" required><option>100</option><option>200</option><option>300</option><option>400</option></select></div>
        <div class="col-4"><label class="form-label-sm">Credits *</label><input type="number" class="form-ctrl w-100" id="crs_units" value="3" min="1" max="6"></div>
        <div class="col-4"><label class="form-label-sm">Semester</label><select class="form-ctrl w-100" id="crs_sem"><option>First</option><option>Second</option></select></div>
    </div>
    <div class="form-check"><input type="checkbox" class="form-check-input" id="crs_compulsory" checked><label class="form-check-label small">Compulsory Course</label></div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveCourse()">Save</button></div>
</div></div></div>

<div class="modal fade" id="sessionModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold">New Academic Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label-sm">Session Name *</label><input class="form-ctrl w-100" id="ses_name" placeholder="e.g. 2026/2027" required></div>
    <div class="mb-3"><label class="form-label-sm">Semester *</label><select class="form-ctrl w-100" id="ses_sem" required><option>First</option><option>Second</option><option>Third</option></select></div>
    <div class="row g-2">
        <div class="col-6"><label class="form-label-sm">Start Date</label><input type="date" class="form-ctrl w-100" id="ses_start"></div>
        <div class="col-6"><label class="form-label-sm">End Date</label><input type="date" class="form-ctrl w-100" id="ses_end"></div>
    </div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveSession()">Save</button></div>
</div></div></div>

<div class="modal fade" id="staffModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title fw-bold" id="staffModalTitle">Add Staff Member</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="staffId">
<div class="row g-3">
    <div class="col-md-6"><label class="form-label-sm">Full Name *</label><input class="form-ctrl w-100" id="sf_name" required></div>
    <div class="col-md-6"><label class="form-label-sm">Staff ID</label><input class="form-ctrl w-100" id="sf_staffid" placeholder="Official staff ID"></div>
    <div class="col-md-6"><label class="form-label-sm">Username *</label><input class="form-ctrl w-100" id="sf_username" required></div>
    <div class="col-md-6"><label class="form-label-sm">Password *</label><input type="password" class="form-ctrl w-100" id="sf_password" placeholder="Min. 6 characters" required></div>
    <div class="col-md-6"><label class="form-label-sm">Email</label><input type="email" class="form-ctrl w-100" id="sf_email" placeholder="Staff email address"></div>
    <div class="col-md-6"><label class="form-label-sm">Phone</label><input class="form-ctrl w-100" id="sf_phone"></div>
    <div class="col-md-6"><label class="form-label-sm">Role *</label>
        <select class="form-ctrl w-100" id="sf_role" required>
            <option>Lecturer</option><option>Department Officer</option><option>Registrar</option>
            <?php if($isAdmin): ?><option>Administrator</option><?php endif; ?>
        </select>
    </div>
    <div class="col-md-6"><label class="form-label-sm">Gender</label><select class="form-ctrl w-100" id="sf_gender"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
    <div class="col-md-6"><label class="form-label-sm">Department</label><select class="form-ctrl w-100" id="sf_dept"><option value="">Select Dept</option></select></div>
    <div class="col-md-6"><label class="form-label-sm">Qualification</label><input class="form-ctrl w-100" id="sf_qual" placeholder="e.g. M.Sc Computer Science"></div>
    <div class="col-md-6"><label class="form-label-sm">Specialization</label><input class="form-ctrl w-100" id="sf_spec" placeholder="Area of specialization"></div>
    <div class="col-md-6"><label class="form-label-sm">Date Joined</label><input type="date" class="form-ctrl w-100" id="sf_joined"></div>
</div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary rounded-pill px-4" onclick="saveStaff()">Save Staff</button></div>
</div></div></div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF = '<?= $CSRF ?>';
const ROLE = '<?= $ROLE ?>';
let charts = {};
let studentsTable;

// ===== SIDEBAR TOGGLE =====
const sidebar = document.getElementById('sidebar');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});
document.getElementById('mobileSidebarBtn').addEventListener('click', () => {
    sidebar.classList.toggle('mobile-open');
});

// ===== VIEW NAVIGATION =====
function showView(viewId, btn) {
    document.querySelectorAll('.spa-view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.nav-item-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('view-' + viewId)?.classList.add('active');
    btn?.classList.add('active');
    document.getElementById('pageTitle').textContent = btn?.querySelector('.nav-text')?.textContent?.trim() || viewId;
    loadView(viewId);
}

function loadView(viewId) {
    switch(viewId) {
        case 'dashboard':   loadDashboard(); break;
        case 'students':    loadStudents(); break;
        case 'faculties':   loadFaculties(); break;
        case 'departments': loadDepartments(); break;
        case 'programmes':  loadProgrammes(); break;
        case 'courses':     loadCourses(); break;
        case 'sessions':    loadSessions(); break;
        case 'staff':       loadStaff(); break;
        case 'users':       loadUsers(); break;
        case 'audit':       loadAuditLogs(); break;
        case 'regrequests': loadRegistrationRequests('Pending'); updatePendingCount(); break;
        case 'certmanage':  loadCertificates(); break;
    }
}
function refreshCurrentView() {
    const active = document.querySelector('.nav-item-btn.active');
    if (active) loadView(active.getAttribute('data-view'));
}

// ===== API HELPER =====
async function api(action, data = {}, method = 'POST') {
    const res = await fetch('api.php', {
        method,
        headers: {'Content-Type':'application/json','X-CSRF-Token': CSRF},
        body: method === 'POST' ? JSON.stringify({action, ...data}) : undefined,
    });
    return res.json();
}
async function apiGet(action, params = {}) {
    const qs = new URLSearchParams({action, ...params}).toString();
    const res = await fetch('api.php?' + qs);
    return res.json();
}

// ===== DEBOUNCE =====
function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ===== TOAST =====
function toast(icon, title) {
    Swal.fire({toast:true,position:'top-end',icon,title,showConfirmButton:false,timer:2500,timerProgressBar:true});
}

// ===== DATE =====
document.getElementById('dashDate').textContent = new Date().toLocaleDateString('en-GB',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

// ===== DASHBOARD =====
async function loadDashboard() {
    const res = await apiGet('dashboard_stats');
    if (!res.status) return;
    const d = res.data;
    const statMap = {
        total_students: d.total_students, active_students: d.active_students,
        graduated_students: d.graduated_students, total_staff: d.total_staff,
        total_departments: d.total_departments, total_certificates: d.total_certificates
    };
    Object.entries(statMap).forEach(([k,v]) => {
        const el = document.getElementById('stat-'+k);
        if (el) { el.textContent = parseInt(v||0).toLocaleString(); }
    });
    document.getElementById('activeSessionBadge').textContent = d.active_session || 'No active session';

    // Activity Feed
    const feed = document.getElementById('activityFeed');
    feed.innerHTML = (d.recent_activities||[]).slice(0,8).map(a => `
        <div class="activity-item">
            <div class="activity-dot"></div>
            <div>
                <div style="font-size:.82rem;font-weight:600;color:#1a202c">${a.action}</div>
                <div style="font-size:.74rem;color:#94a3b8">${a.username||'System'} &middot; ${new Date(a.created_at).toLocaleString()}</div>
            </div>
        </div>`).join('') || '<p class="text-muted small p-2">No recent activity</p>';

    // Charts
    renderStatusChart(d.by_status||[]);
    renderLevelChart(d.by_level||[]);
    renderDeptChart(d.by_department||[]);
}

function renderChart(id, config) {
    if (charts[id]) { charts[id].destroy(); }
    const ctx = document.getElementById(id)?.getContext('2d');
    if (ctx) charts[id] = new Chart(ctx, config);
}

function renderStatusChart(data) {
    const labels = data.map(d=>d.status), vals = data.map(d=>parseInt(d.count));
    renderChart('statusChart', {
        type: 'doughnut',
        data: { labels, datasets:[{data:vals, backgroundColor:['#22c55e','#f59e0b','#3b82f6','#f97316'], borderWidth:2, borderColor:'#fff'}] },
        options: { plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}, cutout:'68%' }
    });
}
function renderLevelChart(data) {
    const labels = data.map(d=>'Level '+d.level), vals = data.map(d=>parseInt(d.count));
    renderChart('levelChart', {
        type: 'bar',
        data: { labels, datasets:[{label:'Students',data:vals,backgroundColor:'rgba(59,130,246,.8)',borderRadius:6,borderSkipped:false}] },
        options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}} }
    });
}
function renderDeptChart(data) {
    const labels = data.map(d=>d.dept), vals = data.map(d=>parseInt(d.count));
    renderChart('deptChart', {
        type: 'bar',
        data: { labels, datasets:[{label:'Students',data:vals,backgroundColor:['#3b82f6','#22c55e','#f59e0b','#f97316','#8b5cf6','#ec4899','#14b8a6','#f97316'].slice(0,labels.length),borderRadius:6}] },
        options: { indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true,grid:{color:'rgba(0,0,0,.04)'}},y:{grid:{display:false}}} }
    });
}

// ===== STUDENTS =====
function statusBadge(s) {
    const map={Active:'badge-active',Suspended:'badge-suspended',Graduated:'badge-graduated',Withdrawn:'badge-withdrawn'};
    return `<span class="${map[s]||'badge-withdrawn'}">${s}</span>`;
}

async function loadStudents() {
    const dept   = document.getElementById('filterDept')?.value   || '';
    const status = document.getElementById('filterStatus')?.value || '';
    const level  = document.getElementById('filterLevel')?.value  || '';
    const search = document.getElementById('filterSearch')?.value || '';
    const params = {};
    if (dept)   params.department_id = dept;
    if (status) params.status        = status;
    if (level)  params.level         = level;
    if (search) params.search        = search;
    const res = await apiGet('get_students', params);
    if (!res.status) return;
    const body = document.getElementById('studentsBody');
    body.innerHTML = res.data.length === 0
        ? '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-users-slash me-2"></i>No records found</td></tr>'
        : res.data.map(s => `
            <tr>
                <td class="ps-4"><code style="font-size:.8rem">${s.admission_number}</code></td>
                <td><span class="fw-600">${s.full_name}</span><br><small class="text-muted">${s.matric_number||'No matric'}</small></td>
                <td>${s.department_name}</td>
                <td>${s.programme_name} <small class="text-muted">(${s.programme_type})</small></td>
                <td><span class="badge bg-light text-dark border">${s.level}</span></td>
                <td>${statusBadge(s.status)}</td>
                <td class="text-end pe-4">
                    <div class="d-flex gap-1 justify-content-end">
                        <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;padding:4px 10px" onclick="viewStudent(${s.id})"><i class="fas fa-eye"></i></button>
                        <?php if($isRegistrar): ?>
                        <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:4px 10px" onclick="editStudent(${s.id})"><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>`).join('');
    // Populate dept filter if empty
    if (!document.getElementById('filterDept').options.length > 1) return;
}

function reloadStudentsTable() { loadStudents(); }
function clearFilters() {
    ['filterDept','filterStatus','filterLevel','filterSearch'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    loadStudents();
}

async function viewStudent(id) {
    const res = await apiGet('get_student', {id});
    if (!res.status) { toast('error','Student not found'); return; }
    const s = res.data;
    const content = document.getElementById('studentDetailContent');
    content.innerHTML = `
    <div style="padding:24px">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#1e40af,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;margin:0 auto 12px">
                    ${s.full_name.charAt(0)}
                </div>
                <div class="fw-bold">${s.full_name}</div>
                ${statusBadge(s.status)}
            </div>
            <div class="col-md-9">
                <div class="row g-2">
                    ${[
                        ['Admission No.',s.admission_number],['Matric No.',s.matric_number||'Not Assigned'],
                        ['Gender',s.gender],['D.O.B',s.dob],['Phone',s.phone||'—'],
                        ['Department',s.department_name],['Faculty',s.faculty_name],
                        ['Programme',s.programme_name+' ('+s.programme_type+')'],
                        ['Level','Level '+s.level],['Session',s.session_name],
                        ['Admission Date',s.admission_date],['State',s.state],
                        ['Nationality',s.nationality],['Guardian',s.guardian_name||'—'],
                    ].map(([l,v])=>`<div class="col-6"><div class="p-2 rounded" style="background:#f8fafc"><div style="font-size:.72rem;color:#94a3b8">${l}</div><div style="font-size:.88rem;font-weight:600">${v||'—'}</div></div></div>`).join('')}
                </div>
                ${s.certificate_number ? `<div class="mt-3 p-3 rounded-3" style="background:#dbeafe;border:1px solid #93c5fd"><i class="fas fa-certificate me-2 text-primary"></i><strong>Certificate:</strong> ${s.certificate_number} &nbsp;&nbsp; <small>Issued: ${s.cert_issue_date}</small></div>` : ''}
            </div>
        </div>
    </div>`;
    const actions = document.getElementById('studentDetailActions');
    actions.innerHTML = '';
    <?php if($isRegistrar): ?>
    const statusBtns = ['Active','Suspended','Withdrawn','Graduated'].filter(st => st !== s.status).map(st =>
        `<button class="btn btn-sm btn-outline-${st==='Graduated'?'primary':st==='Active'?'success':'danger'} rounded-pill" onclick="updateStatus(${s.id},'${st}')">${st}</button>`
    ).join('');
    actions.innerHTML += statusBtns;
    if (s.status === 'Graduated' && !s.certificate_number) {
        actions.innerHTML += `<button class="btn btn-sm btn-warning rounded-pill" onclick="genCert(${s.id})"><i class="fas fa-certificate me-1"></i>Issue Cert</button>`;
    }
    <?php endif; ?>
    new bootstrap.Modal(document.getElementById('studentDetailModal')).show();
}

async function updateStatus(id, status) {
    const res = await api('update_student_status', {id, status});
    toast(res.status ? 'success' : 'error', res.message);
    if (res.status) { bootstrap.Modal.getInstance(document.getElementById('studentDetailModal'))?.hide(); loadStudents(); }
}
async function genCert(studentId) {
    Swal.fire({title:'Generate Certificate?',text:'This will issue an official graduation certificate with QR code.',icon:'question',showCancelButton:true,confirmButtonText:'Yes, Generate'}).then(async r => {
        if (r.isConfirmed) {
            const res = await api('generate_certificate', {student_id: studentId});
            toast(res.status ? 'success' : 'error', res.message || res.cert_number || 'Error');
            if (res.status) { bootstrap.Modal.getInstance(document.getElementById('studentDetailModal'))?.hide(); loadStudents(); }
        }
    });
}

function showAddStudent() {
    document.getElementById('studentId').value = '';
    document.getElementById('studentModalTitle').textContent = 'Add New Student';
    document.getElementById('studentForm').reset();
    document.getElementById('s_admdate').value = new Date().toISOString().slice(0,10);
    loadFacultiesIntoSelect('s_faculty');
    loadSessionsIntoSelect('s_session');
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}
async function editStudent(id) {
    const res = await apiGet('get_student', {id});
    if (!res.status) { toast('error','Not found'); return; }
    const s = res.data;
    document.getElementById('studentId').value      = s.id;
    document.getElementById('studentModalTitle').textContent = 'Edit Student';
    document.getElementById('s_adm').value         = s.admission_number;
    document.getElementById('s_matric').value      = s.matric_number || '';
    document.getElementById('s_name').value        = s.full_name;
    document.getElementById('s_dob').value         = s.dob;
    document.getElementById('s_gender').value      = s.gender;
    document.getElementById('s_phone').value       = s.phone || '';
    document.getElementById('s_state').value       = s.state || '';
    document.getElementById('s_lga').value         = s.lga || '';
    document.getElementById('s_address').value     = s.address || '';
    document.getElementById('s_nation').value      = s.nationality || 'Nigerian';
    document.getElementById('s_admdate').value     = s.admission_date;
    document.getElementById('s_gname').value       = s.guardian_name || '';
    document.getElementById('s_gphone').value      = s.guardian_phone || '';
    document.getElementById('s_gaddress').value    = s.guardian_address || '';
    await loadFacultiesIntoSelect('s_faculty', s.faculty_id);
    await loadProgrammesByFaculty(s.faculty_id, s.department_id, s.programme_id);
    loadSessionsIntoSelect('s_session', s.session_id);
    document.getElementById('s_level').value       = s.level;
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

async function saveStudent() {
    const id = document.getElementById('studentId').value;
    const data = {
        admission_number: document.getElementById('s_adm').value,
        matric_number:    document.getElementById('s_matric').value,
        full_name:        document.getElementById('s_name').value,
        dob:              document.getElementById('s_dob').value,
        gender:           document.getElementById('s_gender').value,
        phone:            document.getElementById('s_phone').value,
        address:          document.getElementById('s_address').value,
        faculty_id:       document.getElementById('s_faculty').value,
        department_id:    document.getElementById('s_dept').value,
        programme_id:     document.getElementById('s_prog').value,
        level:            document.getElementById('s_level').value,
        session_id:       document.getElementById('s_session').value,
        state:            document.getElementById('s_state').value,
        lga:              document.getElementById('s_lga').value,
        nationality:      document.getElementById('s_nation').value,
        admission_date:   document.getElementById('s_admdate').value,
        guardian_name:    document.getElementById('s_gname').value,
        guardian_phone:   document.getElementById('s_gphone').value,
        guardian_address: document.getElementById('s_gaddress').value,
    };
    const action = id ? 'update_student' : 'create_student';
    if (id) data.id = id;
    const res = await api(action, data);
    toast(res.status ? 'success' : 'error', res.message);
    if (res.status) { bootstrap.Modal.getInstance(document.getElementById('studentModal'))?.hide(); loadStudents(); }
}

// ===== FACULTIES =====
async function loadFaculties() {
    const res = await apiGet('get_faculties');
    if (!res.status) return;
    document.getElementById('facultiesBody').innerHTML = res.data.map(f => `
        <tr>
            <td class="ps-4"><code>${f.code}</code></td>
            <td class="fw-600">${f.name}</td>
            <td>${f.hod_name||'—'}</td>
            <td><span class="badge bg-light text-dark border">${f.dept_count} dept(s)</span></td>
            <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-danger" style="border-radius:8px" onclick="deleteFaculty(${f.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('') || '<tr><td colspan="5" class="text-center py-4 text-muted">No faculties found</td></tr>';
}
function showAddFaculty() {
    document.getElementById('facultyId').value='';document.getElementById('facultyModalTitle').textContent='Add Faculty';
    document.getElementById('fac_name').value='';document.getElementById('fac_code').value='';document.getElementById('fac_hod').value='';document.getElementById('fac_desc').value='';
    new bootstrap.Modal(document.getElementById('facultyModal')).show();
}
async function saveFaculty() {
    const id = document.getElementById('facultyId').value;
    const data = {name:document.getElementById('fac_name').value,code:document.getElementById('fac_code').value,hod_name:document.getElementById('fac_hod').value,description:document.getElementById('fac_desc').value};
    const res = await api(id?'update_faculty':'create_faculty', id?{...data,id}:data);
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('facultyModal'))?.hide();loadFaculties();}
}
async function deleteFaculty(id) {
    Swal.fire({title:'Delete Faculty?',icon:'warning',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#f97316'}).then(async r=>{
        if(r.isConfirmed){const res=await api('delete_faculty',{id});toast(res.status?'success':'error',res.message);if(res.status)loadFaculties();}
    });
}

// ===== DEPARTMENTS =====
async function loadDepartments() {
    const res = await apiGet('get_departments');
    if (!res.status) return;
    document.getElementById('depsBody').innerHTML = res.data.map(d => `
        <tr>
            <td class="ps-4"><code>${d.code}</code></td>
            <td class="fw-600">${d.name}</td>
            <td>${d.faculty_name}</td>
            <td><span class="badge bg-light text-dark border">${d.student_count} student(s)</span></td>
            <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-danger" style="border-radius:8px" onclick="deleteDepartment(${d.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('') || '<tr><td colspan="5" class="text-center py-4 text-muted">No departments</td></tr>';
}
async function showAddDepartment() {
    await loadFacultiesIntoSelect('dept_faculty');
    document.getElementById('deptId').value='';document.getElementById('deptModalTitle').textContent='Add Department';
    document.getElementById('dept_name').value='';document.getElementById('dept_code').value='';document.getElementById('dept_hod').value='';
    new bootstrap.Modal(document.getElementById('deptModal')).show();
}
async function saveDepartment() {
    const id=document.getElementById('deptId').value;
    const data={faculty_id:document.getElementById('dept_faculty').value,name:document.getElementById('dept_name').value,code:document.getElementById('dept_code').value,hod_name:document.getElementById('dept_hod').value};
    const res=await api(id?'update_department':'create_department',id?{...data,id}:data);
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('deptModal'))?.hide();loadDepartments();}
}
async function deleteDepartment(id){Swal.fire({title:'Delete Department?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f97316'}).then(async r=>{if(r.isConfirmed){const res=await api('delete_department',{id});toast(res.status?'success':'error',res.message);if(res.status)loadDepartments();}});}

// ===== PROGRAMMES =====
async function loadProgrammes() {
    const res = await apiGet('get_programmes');
    if (!res.status) return;
    document.getElementById('progsBody').innerHTML = res.data.map(p => `
        <tr>
            <td class="ps-4 fw-600">${p.name}</td>
            <td><span class="badge bg-light text-dark border">${p.type}</span></td>
            <td>${p.department_name}</td>
            <td>${p.faculty_name}</td>
            <td>${p.duration_years} yr(s)</td>
            <td class="text-end pe-4">
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;padding:4px 10px" onclick="editProgramme(${p.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;padding:4px 10px" onclick="deleteProgramme(${p.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('') || '<tr><td colspan="6" class="text-center py-4 text-muted">No programmes</td></tr>';
}
async function showAddProgramme() {
    const deps=await apiGet('get_departments');
    const sel=document.getElementById('prog_dept');
    sel.innerHTML='<option value="">Select Department</option>'+(deps.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
    document.getElementById('progId').value='';
    document.getElementById('progModalTitle').textContent='Add Programme';
    document.getElementById('prog_name').value='';
    document.getElementById('prog_type').value='Undergraduate';
    document.getElementById('prog_dur').value='4';
    new bootstrap.Modal(document.getElementById('progModal')).show();
}
async function editProgramme(id) {
    const res = await apiGet('get_programmes');
    if (!res.status) return;
    const p = (res.data||[]).find(x => x.id == id);
    if (!p) { toast('error','Programme not found'); return; }
    const deps=await apiGet('get_departments');
    const sel=document.getElementById('prog_dept');
    sel.innerHTML='<option value="">Select Department</option>'+(deps.data||[]).map(d=>`<option value="${d.id}"${d.id==p.department_id?' selected':''}>${d.name}</option>`).join('');
    document.getElementById('progId').value = p.id;
    document.getElementById('progModalTitle').textContent = 'Edit Programme';
    document.getElementById('prog_name').value = p.name;
    document.getElementById('prog_type').value = p.type;
    document.getElementById('prog_dur').value = p.duration_years;
    new bootstrap.Modal(document.getElementById('progModal')).show();
}
async function saveProgramme(){
    const id = document.getElementById('progId').value;
    const data = {department_id:document.getElementById('prog_dept').value,name:document.getElementById('prog_name').value,type:document.getElementById('prog_type').value,duration_years:document.getElementById('prog_dur').value};
    const action = id ? 'update_programme' : 'create_programme';
    if (id) data.id = id;
    const res = await api(action, data);
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('progModal'))?.hide();loadProgrammes();}
}
async function deleteProgramme(id){Swal.fire({title:'Delete Programme?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f97316'}).then(async r=>{if(r.isConfirmed){const res=await api('delete_programme',{id});toast(res.status?'success':'error',res.message);if(res.status)loadProgrammes();}});}

// ===== COURSES =====
async function loadCourses(){
    const res=await apiGet('get_courses');
    if(!res.status)return;
    document.getElementById('coursesBody').innerHTML=res.data.map(c=>`
        <tr>
            <td class="ps-4"><code>${c.code}</code></td>
            <td class="fw-600">${c.title}</td>
            <td>${c.department_name}</td>
            <td>Level ${c.level}</td>
            <td>${c.credit_units} units</td>
            <td>${c.semester}</td>
            <td class="text-end pe-4"><button class="btn btn-sm btn-outline-danger" style="border-radius:8px" onclick="deleteCourse(${c.id})"><i class="fas fa-trash"></i></button></td>
        </tr>`).join('')||'<tr><td colspan="7" class="text-center py-4 text-muted">No courses</td></tr>';
}
async function showAddCourse(){
    const deps=await apiGet('get_departments');
    const sel=document.getElementById('crs_dept');
    sel.innerHTML='<option value="">Select Department</option>'+(deps.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
    new bootstrap.Modal(document.getElementById('courseModal')).show();
}
async function loadProgrammesForCourse(deptId){
    if(!deptId)return;
    const res=await apiGet('get_programmes',{department_id:deptId});
    document.getElementById('crs_prog').innerHTML='<option value="">Select Programme</option>'+(res.data||[]).map(p=>`<option value="${p.id}">${p.name} (${p.type})</option>`).join('');
}
async function saveCourse(){
    const res=await api('create_course',{department_id:document.getElementById('crs_dept').value,programme_id:document.getElementById('crs_prog').value,code:document.getElementById('crs_code').value,title:document.getElementById('crs_title').value,level:document.getElementById('crs_level').value,credit_units:document.getElementById('crs_units').value,semester:document.getElementById('crs_sem').value,is_compulsory:document.getElementById('crs_compulsory').checked?1:0});
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('courseModal'))?.hide();loadCourses();}
}
async function deleteCourse(id){Swal.fire({title:'Delete Course?',icon:'warning',showCancelButton:true,confirmButtonColor:'#f97316'}).then(async r=>{if(r.isConfirmed){const res=await api('delete_course',{id});toast(res.status?'success':'error',res.message);if(res.status)loadCourses();}});}

// ===== SESSIONS =====
async function loadSessions(){
    const res=await apiGet('get_sessions');
    if(!res.status)return;
    document.getElementById('sessionsBody').innerHTML=res.data.map(s=>`
        <tr>
            <td class="ps-4 fw-600">${s.name}</td>
            <td>${s.semester} Semester</td>
            <td>${s.start_date||'—'}</td>
            <td>${s.end_date||'—'}</td>
            <td>${s.is_active?'<span class="badge-active">Active</span>':'<span class="badge-suspended">Inactive</span>'}</td>
            <td class="text-end pe-4">${!s.is_active?`<button class="btn btn-sm btn-outline-success rounded-pill" onclick="setActiveSession(${s.id})"><i class="fas fa-check me-1"></i>Set Active</button>`:''}</td>
        </tr>`).join('');
}
function showAddSession(){new bootstrap.Modal(document.getElementById('sessionModal')).show();}
async function saveSession(){
    const res=await api('create_session',{name:document.getElementById('ses_name').value,semester:document.getElementById('ses_sem').value,start_date:document.getElementById('ses_start').value,end_date:document.getElementById('ses_end').value});
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('sessionModal'))?.hide();loadSessions();}
}
async function setActiveSession(id){
    const res=await api('set_active_session',{id});
    toast(res.status?'success':'error',res.message);
    if(res.status)loadSessions();
}

// ===== STAFF =====
async function loadStaff(){
    const res=await apiGet('get_staff');
    if(!res.status)return;
    document.getElementById('staffBody').innerHTML=res.data.map(s=>`
        <tr>
            <td class="ps-4 fw-600">${s.full_name}</td>
            <td><code>${s.username}</code></td>
            <td>${s.role}</td>
            <td>${s.department_name||'—'}</td>
            <td>${s.phone||'—'}</td>
            <td><span class="${s.account_status==='Active'?'badge-active':'badge-suspended'}">${s.account_status}</span></td>
            <td class="text-end pe-4"><button class="btn btn-sm btn-outline-danger" style="border-radius:8px" onclick="deleteStaff(${s.id})"><i class="fas fa-trash"></i></button></td>
        </tr>`).join('')||'<tr><td colspan="7" class="text-center py-4 text-muted">No staff found</td></tr>';
}
async function showAddStaff(){
    await loadFacultiesIntoSelect('');
    const deps=await apiGet('get_departments');
    document.getElementById('sf_dept').innerHTML='<option value="">Select Dept (optional)</option>'+(deps.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
    document.getElementById('staffId').value='';document.getElementById('staffModalTitle').textContent='Add Staff Member';
    document.getElementById('sf_name').value='';document.getElementById('sf_username').value='';document.getElementById('sf_password').value='';
    new bootstrap.Modal(document.getElementById('staffModal')).show();
}
async function saveStaff(){
    const id=document.getElementById('staffId').value;
    const data={full_name:document.getElementById('sf_name').value,staff_id:document.getElementById('sf_staffid').value,username:document.getElementById('sf_username').value,password:document.getElementById('sf_password').value,email:document.getElementById('sf_email').value,phone:document.getElementById('sf_phone').value,role:document.getElementById('sf_role').value,gender:document.getElementById('sf_gender').value,department_id:document.getElementById('sf_dept').value,qualification:document.getElementById('sf_qual').value,specialization:document.getElementById('sf_spec').value,date_joined:document.getElementById('sf_joined').value};
    const res=await api(id?'update_staff':'create_staff',id?{...data,id}:data);
    toast(res.status?'success':'error',res.message);
    if(res.status){bootstrap.Modal.getInstance(document.getElementById('staffModal'))?.hide();loadStaff();}
}
async function deleteStaff(id){Swal.fire({title:'Delete Staff?',text:'This removes their account.',icon:'warning',showCancelButton:true,confirmButtonColor:'#f97316'}).then(async r=>{if(r.isConfirmed){const res=await api('delete_staff',{id});toast(res.status?'success':'error',res.message);if(res.status)loadStaff();}});}

// ===== USERS =====
async function loadUsers(){
    const res=await apiGet('get_system_users');
    if(!res.status)return;
    document.getElementById('usersBody').innerHTML=res.data.map(u=>`
        <tr>
            <td class="ps-4"><code>${u.username}</code></td>
            <td>${u.full_name||'—'}</td>
            <td>${u.role}</td>
            <td><span class="${u.status==='Active'?'badge-active':'badge-suspended'}">${u.status}</span></td>
            <td>${u.last_login?new Date(u.last_login).toLocaleString():'Never'}</td>
            <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-${u.status==='Active'?'warning':'success'} rounded-pill" onclick="toggleUser(${u.id},'${u.status==='Active'?'Suspended':'Active'}')">
                    ${u.status==='Active'?'Suspend':'Activate'}
                </button>
            </td>
        </tr>`).join('');
}
async function toggleUser(userId,status){
    const res=await api('toggle_user_status',{user_id:userId,status});
    toast(res.status?'success':'error',res.message);
    if(res.status)loadUsers();
}

// ===== AUDIT =====
async function loadAuditLogs(){
    const res=await apiGet('get_audit_logs');
    if(!res.status)return;
    document.getElementById('auditBody').innerHTML=res.data.map(a=>`
        <tr>
            <td class="ps-4">${a.action}</td>
            <td><code>${a.username||'System'}</code></td>
            <td>${a.entity||'—'}${a.entity_id?' #'+a.entity_id:''}</td>
            <td><code style="font-size:.75rem">${a.ip_address||'—'}</code></td>
            <td style="font-size:.82rem">${new Date(a.created_at).toLocaleString()}</td>
        </tr>`).join('');
}

// ===== SETTINGS =====
document.getElementById('changePwdForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const np=document.getElementById('newPwd').value, cp=document.getElementById('confirmPwd').value;
    if(np!==cp){const a=document.getElementById('pwdAlert');a.className='alert alert-danger';a.textContent='Passwords do not match.';return;}
    const res=await api('change_password',{current_password:document.getElementById('currentPwd').value,new_password:np});
    const a=document.getElementById('pwdAlert');
    a.className='alert '+(res.status?'alert-success':'alert-danger');
    a.textContent=res.message;
});

// ===== LOGOUT =====
async function doLogout(){
    Swal.fire({title:'Logout?',icon:'question',showCancelButton:true,confirmButtonText:'Logout'}).then(async r=>{
        if(r.isConfirmed){await api('logout');window.location.href='index.php';}
    });
}

// ===== HELPER: Load selects =====
async function loadFacultiesIntoSelect(selId, selectedVal=''){
    if(!selId)return;
    const res=await apiGet('get_faculties');
    const sel=document.getElementById(selId);
    if(!sel)return;
    sel.innerHTML='<option value="">Select Faculty</option>'+(res.data||[]).map(f=>`<option value="${f.id}"${f.id==selectedVal?' selected':''}>${f.name}</option>`).join('');
}
async function loadProgrammesByFaculty(facultyId, deptVal='', progVal=''){
    if(!facultyId)return;
    const deps=await apiGet('get_departments',{faculty_id:facultyId});
    const dSel=document.getElementById('s_dept');
    dSel.innerHTML='<option value="">Select Dept</option>'+(deps.data||[]).map(d=>`<option value="${d.id}"${d.id==deptVal?' selected':''}>${d.name}</option>`).join('');
    if(deptVal) await loadProgrammesForStudent(deptVal, progVal);
    dSel.onchange=()=>loadProgrammesForStudent(dSel.value);
}
async function loadProgrammesForStudent(deptId, progVal=''){
    const progs=await apiGet('get_programmes',{department_id:deptId});
    document.getElementById('s_prog').innerHTML='<option value="">Select Programme</option>'+(progs.data||[]).map(p=>`<option value="${p.id}"${p.id==progVal?' selected':''}>${p.name} (${p.type})</option>`).join('');
}
async function loadSessionsIntoSelect(selId, selectedVal=''){
    const res=await apiGet('get_sessions');
    const sel=document.getElementById(selId);
    if(!sel)return;
    sel.innerHTML='<option value="">Select Session</option>'+(res.data||[]).map(s=>`<option value="${s.id}"${s.id==selectedVal?' selected':''}>${s.name} - ${s.semester}</option>`).join('');
}

// ===== DEPT FILTER POPULATION =====
async function populateFilterDept(){
    const res=await apiGet('get_departments');
    const sel=document.getElementById('filterDept');
    if(sel&&res.data) sel.innerHTML='<option value="">All Departments</option>'+(res.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
}

// ===== REGISTRATION APPROVALS =====
let currentRegFilter = 'Pending';

function filterRegRequests(status, btn) {
    currentRegFilter = status;
    document.querySelectorAll('#regFilterTabs button').forEach(b => {
        b.className = 'btn btn-sm btn-outline-secondary rounded-pill px-3';
    });
    if (btn) btn.className = 'btn btn-sm btn-primary rounded-pill px-3';
    loadRegistrationRequests(status);
}

async function loadRegistrationRequests(status) {
    const param = status && status !== 'All' ? {status} : {};
    const res = await apiGet('get_registration_requests', param);
    if (!res.status) return;
    const data = res.data || [];
    document.getElementById('regRequestCount').textContent = data.length + ' request(s)';
    const body = document.getElementById('regRequestsBody');
    if (data.length === 0) {
        body.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-inbox me-2"></i>No requests found</td></tr>';
        return;
    }
    body.innerHTML = data.map(r => {
        const typeBadge = r.request_type === 'Student'
            ? '<span class="type-badge" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-user-graduate me-1"></i>Student</span>'
            : '<span class="type-badge" style="background:#dcfce7;color:#15803d"><i class="fas fa-chalkboard-teacher me-1"></i>Lecturer</span>';
        let actions = '';
        if (r.status === 'Pending') {
            actions = `<button class="btn btn-sm btn-primary rounded-pill px-3" onclick="approveRequest(${r.id})"><i class="fas fa-check me-1"></i>Approve</button>
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="rejectRequest(${r.id})"><i class="fas fa-times me-1"></i>Reject</button>`;
        } else if (r.status === 'Rejected' && r.rejection_reason) {
            actions = `<small class="text-muted" title="${r.rejection_reason}"><i class="fas fa-comment-slash me-1"></i>${r.rejection_reason.length > 30 ? r.rejection_reason.substring(0,30)+'...' : r.rejection_reason}</small>`;
        } else {
            actions = `<span class="text-muted" style="font-size:.8rem">${r.status}</span>`;
        }
        return `<tr>
            <td class="ps-4">${typeBadge}</td>
            <td class="fw-600">${r.full_name}</td>
            <td><code>${r.username}</code></td>
            <td>${r.email||'—'}</td>
            <td>${r.phone||'—'}</td>
            <td>${r.department_name||'—'}</td>
            <td>${r.programme_name||'—'}</td>
            <td style="font-size:.82rem">${new Date(r.created_at).toLocaleDateString('en-GB')}</td>
            <td class="text-end pe-4"><div class="d-flex gap-1 justify-content-end">${actions}</div></td>
        </tr>`;
    }).join('');
}

async function approveRequest(id) {
    const res = await api('approve_registration', {id});
    toast(res.status ? 'success' : 'error', res.message || 'Approval failed');
    if (res.status) {
        loadRegistrationRequests(currentRegFilter);
        updatePendingCount();
    }
}

async function rejectRequest(id) {
    const {value: reason} = await Swal.fire({
        title: 'Reject Registration',
        input: 'textarea',
        inputLabel: 'Please provide a reason for rejection',
        inputPlaceholder: 'Enter rejection reason...',
        inputValidator: (val) => !val && 'A reason is required',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#f97316',
    });
    if (!reason) return;
    const res = await api('reject_registration', {id, reason});
    toast(res.status ? 'success' : 'error', res.message || 'Rejection failed');
    if (res.status) {
        loadRegistrationRequests(currentRegFilter);
        updatePendingCount();
    }
}

async function updatePendingCount() {
    try {
        const res = await apiGet('get_registration_requests', {status: 'Pending'});
        const count = (res.data || []).length;
        const badge = document.getElementById('pendingCountBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    } catch(e) {}
}

// ===== CERTIFICATE MANAGEMENT =====
async function loadCertificates() {
    const search = document.getElementById('certSearch')?.value || '';
    const faculty = document.getElementById('certFilterFaculty')?.value || '';
    const dept = document.getElementById('certFilterDept')?.value || '';
    const params = {};
    if (search) params.search = search;
    if (faculty) params.faculty_id = faculty;
    if (dept) params.department_id = dept;
    const res = await apiGet('get_all_certificates', params);
    if (!res.status) return;
    const data = res.data || [];
    document.getElementById('certTotalCount').textContent = data.length + ' certificate(s)';
    const body = document.getElementById('certsBody');
    if (data.length === 0) {
        body.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-file-alt me-2"></i>No certificates found</td></tr>';
        return;
    }
    body.innerHTML = data.map(c => `
        <tr>
            <td class="ps-4"><code>${c.certificate_number||'—'}</code></td>
            <td class="fw-600">${c.student_name||c.full_name||'—'}</td>
            <td><code style="font-size:.8rem">${c.matric_number||'—'}</code></td>
            <td>${c.programme_name||'—'}</td>
            <td>${c.department_name||'—'}</td>
            <td>${c.faculty_name||'—'}</td>
            <td style="font-size:.82rem">${c.issue_date||'—'}</td>
            <td class="text-end pe-4">
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;padding:4px 10px" onclick="viewCertificate(${c.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:4px 10px" onclick="window.open('generate_pdf.php?type=certificate&cert_id=${c.id}','_blank')"><i class="fas fa-print"></i></button>
                    <a class="btn btn-sm btn-outline-info" style="border-radius:8px;padding:4px 10px" href="generate_pdf.php?type=certificate&cert_id=${c.id}" target="_blank"><i class="fas fa-download"></i></a>
                </div>
            </td>
        </tr>`).join('');
    populateCertFilters(data);
}

function populateCertFilters(data) {
    const faculties = [...new Set(data.map(c => c.faculty_name).filter(Boolean))];
    const facSel = document.getElementById('certFilterFaculty');
    if (facSel && facSel.options.length <= 1) {
        facSel.innerHTML = '<option value="">All Faculties</option>' + faculties.map(f => `<option value="${f}">${f}</option>`).join('');
    }
    facSel?.removeEventListener?.('change', certFacultyChangeHandler);
    facSel?.addEventListener('change', certFacultyChangeHandler);
    const deptSel = document.getElementById('certFilterDept');
    if (deptSel && deptSel.options.length <= 1) {
        const depts = [...new Set(data.map(c => c.department_name).filter(Boolean))];
        deptSel.innerHTML = '<option value="">All Departments</option>' + depts.map(d => `<option value="${d}">${d}</option>`).join('');
    }
}
function certFacultyChangeHandler() {
    const faculty = document.getElementById('certFilterFaculty').value;
    const deptSel = document.getElementById('certFilterDept');
    if (!deptSel) return;
    deptSel.innerHTML = '<option value="">All Departments</option>';
    if (!faculty) return;
    document.querySelectorAll('.cert-table tbody tr').forEach(row => {
        const facCell = row.cells[5];
        if (facCell && facCell.textContent.trim() === faculty) {
            const deptCell = row.cells[4];
            if (deptCell) {
                const dept = deptCell.textContent.trim();
                if (dept && !deptSel.querySelector(`option[value="${dept}"]`)) {
                    deptSel.innerHTML += `<option value="${dept}">${dept}</option>`;
                }
            }
        }
    });
}

async function viewCertificate(id) {
    const res = await apiGet('get_certificate_detail', {id});
    if (!res.status) { toast('error', 'Certificate not found'); return; }
    const c = res.data;
    const content = document.getElementById('certDetailContent');
    content.innerHTML = `
    <div style="padding:24px">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#1e40af,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;margin:0 auto 12px">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="fw-bold">${c.certificate_number||'—'}</div>
                <div class="text-muted" style="font-size:.8rem">Certificate</div>
            </div>
            <div class="col-md-9">
                <div class="row g-2">
                    ${[
                        ['Certificate No.',c.certificate_number||'—'],
                        ['Student Name',c.student_name||c.full_name||'—'],
                        ['Matric Number',c.matric_number||'—'],
                        ['Admission No.',c.admission_number||'—'],
                        ['Programme',c.programme_name||'—'],
                        ['Department',c.department_name||'—'],
                        ['Faculty',c.faculty_name||'—'],
                        ['Issue Date',c.issue_date||'—'],
                        ['Class of Degree',c.class_of_degree||'—'],
                    ].map(([l,v])=>`<div class="col-6"><div class="p-2 rounded" style="background:#f8fafc"><div style="font-size:.72rem;color:#94a3b8">${l}</div><div style="font-size:.88rem;font-weight:600">${v}</div></div></div>`).join('')}
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary rounded-pill" onclick="window.open('generate_pdf.php?type=certificate&cert_id=${c.id}','_blank')"><i class="fas fa-print me-1"></i>Print</button>
                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="generate_pdf.php?type=certificate&cert_id=${c.id}" target="_blank"><i class="fas fa-download me-1"></i>Download PDF</a>
                </div>
            </div>
        </div>
    </div>`;
    new bootstrap.Modal(document.getElementById('certDetailModal')).show();
}

// ===== INIT =====
populateFilterDept();
loadDashboard();
updatePendingCount();
</script>
</body>
</html>
