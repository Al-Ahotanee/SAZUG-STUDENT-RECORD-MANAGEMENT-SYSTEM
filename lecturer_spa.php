<?php
/**
 * SAZUG SRMS — Lecturer Single Page Application
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Lecturer') {
    header('Location: index.php'); exit;
}
$CSRF  = $_SESSION['csrf_token'] ?? '';
$UNAME = $_SESSION['username'];
$UID   = $_SESSION['user_id'];

// Get staff record for the lecturer
require_once 'SystemCore.php';
$core = new SystemCore();
$db   = $core->getDB();
$stmt = $db->prepare("SELECT st.*, d.name AS dept_name FROM staff st LEFT JOIN departments d ON st.department_id = d.id WHERE st.user_id = :uid LIMIT 1");
$stmt->execute(['uid' => $UID]);
$staffInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$fullName  = $staffInfo['full_name'] ?? $UNAME;
$initials  = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $fullName), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Portal — SAZUG SRMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--sb-bg:#0f2044;--sb-hover:#1a3060;--sb-active:#1e3a7a;--accent:#3b82f6;--body-bg:#f0f4f8;--card-shadow:0 4px 20px rgba(0,0,0,.06);--radius:14px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--body-bg);color:#1a202c;overflow-x:hidden}
.app-wrapper{display:flex;height:100vh;overflow:hidden}
.sidebar{width:265px;background:var(--sb-bg);display:flex;flex-direction:column;flex-shrink:0;transition:width .3s;position:relative;z-index:100}
.sidebar.collapsed{width:72px}
.sidebar-logo{padding:20px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.07)}
.sidebar-logo-icon{width:38px;height:38px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0}
.sidebar-logo-text{font-size:1.05rem;font-weight:800;color:#fff;white-space:nowrap;transition:opacity .2s}
.sidebar.collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}
.sidebar-profile{padding:16px;border-bottom:1px solid rgba(255,255,255,.07)}
.profile-avatar{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0}
.profile-info{overflow:hidden;transition:opacity .2s}
.sidebar.collapsed .profile-info{opacity:0;width:0}
.profile-name{font-size:.83rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.profile-sub{font-size:.72rem;color:rgba(255,255,255,.45)}
.sidebar-scroll{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 0}
.sidebar-scroll::-webkit-scrollbar{width:3px}.sidebar-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px}
.sidebar-section{padding:14px 14px 4px;font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.28);white-space:nowrap;transition:opacity .2s}
.sidebar.collapsed .sidebar-section{opacity:0}
.nav-item-btn{display:flex;align-items:center;gap:13px;padding:10px 14px;color:rgba(255,255,255,.65);border-radius:10px;margin:2px 8px;cursor:pointer;transition:all .2s;white-space:nowrap;overflow:hidden;border:none;background:transparent;width:calc(100% - 16px);font-family:'Inter',sans-serif}
.nav-item-btn:hover{background:var(--sb-hover);color:rgba(255,255,255,.9)}
.nav-item-btn.active{background:var(--sb-active);color:#fff;box-shadow:inset 3px 0 0 var(--accent)}
.nav-icon{width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.nav-text{font-size:.845rem;font-weight:500;transition:opacity .2s}
.sidebar.collapsed .nav-text{opacity:0;width:0;overflow:hidden}
.sidebar-bottom{padding:12px 8px;border-top:1px solid rgba(255,255,255,.07)}
.main-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:#fff;padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8edf3;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.page-title{font-size:1.05rem;font-weight:700;color:#1a202c}
.topbar-btn{width:36px;height:36px;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;color:#64748b;font-size:.85rem;text-decoration:none}
.topbar-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.content-area{flex:1;overflow-y:auto;padding:26px;background:var(--body-bg)}
.content-area::-webkit-scrollbar{width:5px}.content-area::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:5px}
.spa-view{display:none;animation:fadeIn .3s ease}
.spa-view.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.info-card{background:#fff;border-radius:var(--radius);box-shadow:var(--card-shadow);border:1px solid #e8edf3;overflow:hidden}
.info-card-header{padding:16px 22px;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between}
.info-card-title{font-size:.9rem;font-weight:700;color:#1a202c}
.info-card-body{padding:22px}
.stat-tile{background:#fff;border-radius:12px;padding:22px;box-shadow:var(--card-shadow);border:1px solid #e8edf3;transition:all .25s;text-align:center}
.stat-tile:hover{transform:translateY(-3px);box-shadow:0 12px 35px rgba(0,0,0,.1)}
.data-row{display:flex;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f8fafc}
.data-row:last-child{border-bottom:none}
.data-label{width:150px;font-size:.8rem;color:#94a3b8;font-weight:500;flex-shrink:0}
.data-value{font-size:.87rem;font-weight:600;color:#1a202c;flex:1}
.badge-active{background:#dcfce7;color:#15803d;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-suspended{background:#fef9c3;color:#a16207;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-graduated{background:#dbeafe;color:#1d4ed8;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.badge-withdrawn{background:#fee2e2;color:#b91c1c;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700}
.form-ctrl{border:2px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:.88rem;transition:border-color .2s;font-family:'Inter',sans-serif;width:100%}
.form-ctrl:focus{border-color:var(--accent);outline:none;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
@media(max-width:900px){.sidebar{position:fixed;top:0;left:0;bottom:0;transform:translateX(-100%);transition:transform .3s}.sidebar.mobile-open{transform:translateX(0)}}
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
                <div class="profile-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="profile-sub">Lecturer<?= !empty($staffInfo['dept_name']) ? ' · '.$staffInfo['dept_name'] : '' ?></div>
            </div>
        </div>
    </div>
    <div class="sidebar-scroll">
        <div class="sidebar-section">Portal</div>
        <button class="nav-item-btn active" data-view="overview" onclick="showView('overview',this)">
            <span class="nav-icon"><i class="fas fa-th-large"></i></span>
            <span class="nav-text">Overview</span>
        </button>
        <button class="nav-item-btn" data-view="students" onclick="showView('students',this)">
            <span class="nav-icon"><i class="fas fa-user-graduate"></i></span>
            <span class="nav-text">Student Roster</span>
        </button>
        <button class="nav-item-btn" data-view="courses" onclick="showView('courses',this)">
            <span class="nav-icon"><i class="fas fa-book-open"></i></span>
            <span class="nav-text">Courses</span>
        </button>
        <div class="sidebar-section">Account</div>
        <button class="nav-item-btn" data-view="profile" onclick="showView('profile',this)">
            <span class="nav-icon"><i class="fas fa-id-card"></i></span>
            <span class="nav-text">My Profile</span>
        </button>
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
            <span style="font-size:.8rem;color:#94a3b8;background:#f8fafc;border:1px solid #e2e8f0;padding:5px 12px;border-radius:20px;font-weight:600">Lecturer</span>
            <a href="index.php" class="topbar-btn"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <div class="content-area">

        <!-- ===== OVERVIEW ===== -->
        <div class="spa-view active" id="view-overview">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                <div>
                    <h3 style="font-size:1.15rem;font-weight:800;margin:0">Good day, <?= htmlspecialchars(explode(' ', $fullName)[0]) ?></h3>
                    <p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0"><?= date('l, F j, Y') ?> — SAZUG Lecturer Portal</p>
                </div>
            </div>
            <!-- Stat Tiles -->
            <div class="row g-3 mb-4" id="overviewStats">
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div style="font-size:1.6rem;color:#3b82f6;margin-bottom:10px"><i class="fas fa-user-graduate"></i></div>
                        <div style="font-size:2rem;font-weight:800" id="overviewTotalStudents">—</div>
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:500">Total Students</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div style="font-size:1.6rem;color:#22c55e;margin-bottom:10px"><i class="fas fa-user-check"></i></div>
                        <div style="font-size:2rem;font-weight:800" id="overviewActiveStudents">—</div>
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:500">Active Students</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div style="font-size:1.6rem;color:#8b5cf6;margin-bottom:10px"><i class="fas fa-graduation-cap"></i></div>
                        <div style="font-size:2rem;font-weight:800" id="overviewGraduated">—</div>
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:500">Graduated</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div style="font-size:1.6rem;color:#f59e0b;margin-bottom:10px"><i class="fas fa-book-open"></i></div>
                        <div style="font-size:2rem;font-weight:800" id="overviewCourses">—</div>
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:500">Courses Available</div>
                    </div>
                </div>
            </div>
            <!-- Quick access -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="info-card">
                        <div class="info-card-header">
                            <span class="info-card-title"><i class="fas fa-user-graduate me-2 text-primary"></i>Recent Students</span>
                            <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="showView('students',document.querySelector('[data-view=students]'))">View All</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="background:#f8fafc"><tr><th class="ps-4" style="font-size:.78rem;color:#94a3b8;font-weight:600">ADMISSION NO.</th><th style="font-size:.78rem;color:#94a3b8;font-weight:600">NAME</th><th style="font-size:.78rem;color:#94a3b8;font-weight:600">DEPT.</th><th style="font-size:.78rem;color:#94a3b8;font-weight:600">LEVEL</th><th style="font-size:.78rem;color:#94a3b8;font-weight:600">STATUS</th></tr></thead>
                                <tbody id="recentStudentsBody"><tr><td colspan="5" class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-id-card me-2 text-muted"></i>Lecturer Info</span></div>
                        <div class="info-card-body">
                            <?php $rows=[
                                ['Name',$fullName],
                                ['Username',$UNAME],
                                ['Department',$staffInfo['dept_name']??'—'],
                                ['Qualification',$staffInfo['qualification']??'—'],
                                ['Specialization',$staffInfo['specialization']??'—'],
                                ['Phone',$staffInfo['phone']??'—'],
                                ['Date Joined',$staffInfo['date_joined']?date('d M Y',strtotime($staffInfo['date_joined'])):'—'],
                            ]; foreach($rows as [$l,$v]): ?>
                            <div class="data-row"><div class="data-label"><?= $l ?></div><div class="data-value" style="font-size:.83rem"><?= htmlspecialchars($v) ?></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== STUDENTS ===== -->
        <div class="spa-view" id="view-students">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                <div><h3 style="font-size:1.1rem;font-weight:800">Student Roster</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">All enrolled students visible to lecturers</p></div>
            </div>
            <!-- Filters -->
            <div class="info-card mb-4">
                <div class="info-card-body" style="padding:16px">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" class="form-ctrl" id="lecFilterSearch" placeholder="Search name, admission no..." oninput="loadLecStudents()">
                        </div>
                        <div class="col-md-2">
                            <select class="form-ctrl" id="lecFilterStatus" onchange="loadLecStudents()">
                                <option value="">All Status</option><option>Active</option><option>Graduated</option><option>Suspended</option><option>Withdrawn</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-ctrl" id="lecFilterLevel" onchange="loadLecStudents()">
                                <option value="">All Levels</option><option>100</option><option>200</option><option>300</option><option>400</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill" onclick="document.getElementById('lecFilterSearch').value='';document.getElementById('lecFilterStatus').value='';document.getElementById('lecFilterLevel').value='';loadLecStudents()">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                        <div class="col-md-3 text-end">
                            <span id="studentCount" style="font-size:.82rem;color:#94a3b8"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="info-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="lecStudentsTable">
                        <thead style="background:#f8fafc"><tr>
                            <th class="ps-4" style="font-size:.78rem;color:#94a3b8;font-weight:600">ADMISSION NO.</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">NAME</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">DEPARTMENT</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">PROGRAMME</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">LEVEL</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">STATUS</th>
                        </tr></thead>
                        <tbody id="lecStudentsBody"><tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== COURSES ===== -->
        <div class="spa-view" id="view-courses">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Course Catalogue</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">All available courses in the system</p></div>
            <div class="info-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background:#f8fafc"><tr>
                            <th class="ps-4" style="font-size:.78rem;color:#94a3b8;font-weight:600">CODE</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">TITLE</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">DEPARTMENT</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">LEVEL</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">CREDITS</th>
                            <th style="font-size:.78rem;color:#94a3b8;font-weight:600">SEMESTER</th>
                        </tr></thead>
                        <tbody id="lecCoursesBody"><tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== MY PROFILE ===== -->
        <div class="spa-view" id="view-profile">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">My Profile</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Your staff profile details</p></div>
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-id-card me-2 text-primary"></i>Staff Details</span></div>
                        <div class="info-card-body">
                            <?php $prows=[
                                ['Full Name',$fullName],
                                ['Username',$UNAME],
                                ['Staff ID',$staffInfo['staff_id']??'—'],
                                ['Email',$staffInfo['email']??'—'],
                                ['Phone',$staffInfo['phone']??'—'],
                                ['Gender',$staffInfo['gender']??'—'],
                                ['Department',$staffInfo['dept_name']??'—'],
                                ['Qualification',$staffInfo['qualification']??'—'],
                                ['Specialization',$staffInfo['specialization']??'—'],
                                ['Date Joined',$staffInfo['date_joined']?date('F j, Y',strtotime($staffInfo['date_joined'])):'—'],
                                ['Status',$staffInfo['status']??'Active'],
                            ]; foreach($prows as [$l,$v]): ?>
                            <div class="data-row"><div class="data-label"><?= $l ?></div><div class="data-value"><?= htmlspecialchars($v) ?></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECURITY ===== -->
        <div class="spa-view" id="view-security">
            <div class="mb-4"><h3 style="font-size:1.1rem;font-weight:800">Change Password</h3><p style="color:#94a3b8;font-size:.85rem;margin:4px 0 0">Update your account security credentials</p></div>
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="info-card">
                        <div class="info-card-header"><span class="info-card-title"><i class="fas fa-lock me-2 text-primary"></i>Update Password</span></div>
                        <div class="info-card-body">
                            <div id="secAlert" class="alert d-none mb-3"></div>
                            <form id="secForm">
                                <div class="mb-3"><label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:5px">Current Password</label><input type="password" class="form-ctrl" id="curPwd" required placeholder="Current password"></div>
                                <div class="mb-3"><label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:5px">New Password</label><input type="password" class="form-ctrl" id="newPwd" required placeholder="New password (min. 6 chars)"></div>
                                <div class="mb-4"><label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:5px">Confirm New Password</label><input type="password" class="form-ctrl" id="cnfPwd" required placeholder="Confirm new password"></div>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 w-100">Update Password</button>
                            </form>
                        </div>
                    </div>
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
    document.getElementById('view-' + viewId)?.classList.add('active');
    if (btn) btn.classList.add('active');
    document.getElementById('pageTitle').textContent = btn?.querySelector('.nav-text')?.textContent?.trim() || viewId;
    if (viewId === 'students') loadLecStudents();
    if (viewId === 'courses')  loadLecCourses();
}

function statusBadge(s) {
    const m={Active:'badge-active',Suspended:'badge-suspended',Graduated:'badge-graduated',Withdrawn:'badge-withdrawn'};
    return `<span class="${m[s]||'badge-suspended'}">${s}</span>`;
}

async function loadOverview() {
    const res  = await fetch('api.php?action=get_students');
    const data = await res.json();
    if (!data.status) return;
    const students = data.data || [];
    document.getElementById('overviewTotalStudents').textContent = students.length;
    document.getElementById('overviewActiveStudents').textContent  = students.filter(s=>s.status==='Active').length;
    document.getElementById('overviewGraduated').textContent = students.filter(s=>s.status==='Graduated').length;

    // Recent 6 in table
    document.getElementById('recentStudentsBody').innerHTML = students.slice(0,6).map(s=>`
        <tr>
            <td class="ps-4"><code style="font-size:.78rem">${s.admission_number}</code></td>
            <td style="font-weight:600;font-size:.85rem">${s.full_name}</td>
            <td style="font-size:.83rem">${s.department_name}</td>
            <td><span class="badge bg-light text-dark border" style="font-size:.72rem">${s.level}</span></td>
            <td>${statusBadge(s.status)}</td>
        </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-3">No students found</td></tr>';
}

async function loadLecCourses() {
    const res  = await fetch('api.php?action=get_courses');
    const data = await res.json();
    document.getElementById('overviewCourses').textContent = (data.data||[]).length;
    document.getElementById('lecCoursesBody').innerHTML = (data.data||[]).map(c=>`
        <tr>
            <td class="ps-4"><code>${c.code}</code></td>
            <td style="font-weight:600;font-size:.87rem">${c.title}</td>
            <td style="font-size:.83rem">${c.department_name}</td>
            <td><span class="badge bg-light text-dark border" style="font-size:.72rem">Level ${c.level}</span></td>
            <td>${c.credit_units} units</td>
            <td>${c.semester}</td>
        </tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">No courses found</td></tr>';
}

async function loadLecStudents() {
    const search = document.getElementById('lecFilterSearch').value;
    const status = document.getElementById('lecFilterStatus').value;
    const level  = document.getElementById('lecFilterLevel').value;
    const params = new URLSearchParams({action:'get_students'});
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (level)  params.set('level',  level);
    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    const students = data.data || [];
    document.getElementById('studentCount').textContent = `${students.length} record(s) found`;
    document.getElementById('lecStudentsBody').innerHTML = students.map(s=>`
        <tr>
            <td class="ps-4"><code style="font-size:.78rem">${s.admission_number}</code></td>
            <td><div style="font-weight:600;font-size:.87rem">${s.full_name}</div><div style="font-size:.73rem;color:#94a3b8">${s.matric_number||'No matric'}</div></td>
            <td style="font-size:.83rem">${s.department_name}</td>
            <td style="font-size:.83rem">${s.programme_name} <span style="color:#94a3b8">(${s.programme_type})</span></td>
            <td><span class="badge bg-light text-dark border" style="font-size:.72rem">${s.level}</span></td>
            <td>${statusBadge(s.status)}</td>
        </tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-users-slash me-2"></i>No students found</td></tr>';
}

document.getElementById('secForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const alertEl = document.getElementById('secAlert');
    const np = document.getElementById('newPwd').value;
    const cp = document.getElementById('cnfPwd').value;
    if (np !== cp) {
        alertEl.className = 'alert alert-danger'; alertEl.textContent = 'Passwords do not match.'; return;
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

// Init
loadOverview();
loadLecCourses();
</script>
</body>
</html>
