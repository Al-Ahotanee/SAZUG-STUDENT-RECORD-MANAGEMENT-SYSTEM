<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root{--sidebar-w:260px;--primary:#1a365d;--primary-light:#2a4a7f;--primary-dark:#0f2440;--gold:#c9973f;--gold-light:#daa84e;--sidebar-bg:linear-gradient(180deg,#0d1b2a 0%,#1a365d 100%)}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f0f2f5;overflow-x:hidden}
        [data-bs-theme="dark"] body{background:#0a1628;color:#e0e0e0}
        [data-bs-theme="dark"] .card{background:#152238;border-color:rgba(255,255,255,.08)}
        [data-bs-theme="dark"] .table{color:#e0e0e0}
        [data-bs-theme="dark"] .table td,[data-bs-theme="dark"] .table th{background:#152238;border-color:rgba(255,255,255,.06)}
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter input,[data-bs-theme="dark"] .dataTables_wrapper .dataTables_length select{background:#1a2a3d;border-color:rgba(255,255,255,.15);color:#e0e0e0}
        [data-bs-theme="dark"] .topbar{background:#0d1b2a;border-color:rgba(255,255,255,.08)}
        [data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1a2a3d;color:#e0e0e0;border-color:rgba(255,255,255,.15)}
        .sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--sidebar-bg);z-index:1040;transition:transform .3s;overflow-y:auto;display:flex;flex-direction:column}
        .sidebar::-webkit-scrollbar{width:4px}.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:4px}
        .sidebar-brand{padding:1.5rem;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.08)}
        .sidebar-brand i{font-size:1.6rem;color:var(--gold)}.sidebar-brand span{font-size:1.1rem;font-weight:700;color:var(--white)}
        .sidebar-section{padding:1rem 1.2rem .3rem;font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.4);font-weight:600}
        .sidebar-nav{padding:0 8px;flex:1}
        .sidebar-link{display:flex;align-items:center;gap:12px;padding:.7rem 1rem;color:rgba(255,255,255,.7);text-decoration:none;border-radius:10px;font-size:.9rem;font-weight:500;transition:all .2s;margin-bottom:2px;cursor:pointer}
        .sidebar-link:hover{color:var(--white);background:rgba(255,255,255,.08)}
        .sidebar-link.active{color:var(--gold);background:rgba(201,151,63,.12);font-weight:600}
        .sidebar-link i{font-size:1.1rem;width:22px;text-align:center}
        .sidebar-user{padding:1.2rem;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px}
        .sidebar-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-light));display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:.85rem}
        .sidebar-user-info{flex:1}.sidebar-user-info .name{color:var(--white);font-size:.82rem;font-weight:600}.sidebar-user-info .role{color:rgba(255,255,255,.5);font-size:.72rem}
        .main-content{margin-left:var(--sidebar-w);min-height:100vh;transition:margin .3s}
        .topbar{background:var(--white);padding:.8rem 1.5rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e0e0e0;position:sticky;top:0;z-index:1030}
        .breadcrumb-custom{margin:0;font-size:.85rem}
        .topbar-actions{display:flex;align-items:center;gap:10px}
        .theme-toggle-sm{width:36px;height:36px;border-radius:50%;border:2px solid #e0e0e0;background:transparent;color:var(--primary);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .3s}
        .theme-toggle-sm:hover{background:rgba(201,151,63,.1);border-color:var(--gold)}
        .page-content{padding:1.5rem}
        .stat-card{border:none;border-radius:16px;padding:1.3rem;transition:transform .2s;box-shadow:0 2px 10px rgba(0,0,0,.06)}
        .stat-card:hover{transform:translateY(-3px)}.stat-card .icon-box{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
        .stat-card .value{font-size:1.6rem;font-weight:700;line-height:1.2;margin-top:.4rem}
        .stat-card .label{font-size:.82rem;opacity:.8}
        .stat-card-blue{background:linear-gradient(135deg,#1a365d,#2a4a7f);color:#fff}.stat-card-blue .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-green{background:linear-gradient(135deg,#0d6833,#16a34a);color:#fff}.stat-card-green .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-gold{background:linear-gradient(135deg,#a87d2e,#c9973f);color:#fff}.stat-card-gold .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-purple{background:linear-gradient(135deg,#5b21b6,#7c3aed);color:#fff}.stat-card-purple .icon-box{background:rgba(255,255,255,.2)}
        .table-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
        .table-card .card-header{background:transparent;border-bottom:1px solid #eee;padding:1rem 1.2rem;font-weight:600;font-size:.95rem}
        [data-bs-theme="dark"] .table-card .card-header{border-color:rgba(255,255,255,.08)}
        .badge-status{padding:.35em .7em;font-size:.75rem;font-weight:600}
        .status-active{background:#dcfce7;color:#166534}.status-suspended{background:#fef3c7;color:#92400e}
        .status-graduated{background:#dbeafe;color:#1e40af}.status-withdrawn{background:#fee2e2;color:#991b1b}
        .profile-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
        .profile-card .profile-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));padding:2rem;text-align:center;color:#fff}
        .profile-card .profile-header .avatar{width:80px;height:80px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto 1rem;border:4px solid rgba(255,255,255,.3)}
        .profile-card .profile-body{padding:1.5rem}
        .sidebar-toggle{display:none;background:none;border:none;color:var(--primary);font-size:1.3rem;cursor:pointer}
        @media(max-width:992px){.sidebar{transform:translateX(-100%)}.sidebar.show{transform:translateX(0)}.main-content{margin-left:0}.sidebar-toggle{display:block}}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand"><i class="bi bi-mortarboard-fill"></i><span>SAZUG SRMS</span></div>
        <div class="sidebar-section">Menu</div>
        <nav class="sidebar-nav">
            <div class="sidebar-link active" data-page="dashboard"><i class="bi bi-grid-1x2-fill"></i>Dashboard</div>
            <div class="sidebar-link" data-page="students"><i class="bi bi-people-fill"></i>My Students</div>
            <div class="sidebar-link" data-page="profile"><i class="bi bi-person-circle"></i>My Profile</div>
        </nav>
        <div class="sidebar-user" id="sidebarUser">
            <div class="sidebar-avatar" id="avatarInitial">L</div>
            <div class="sidebar-user-info">
                <div class="name" id="userName">Lecturer</div>
                <div class="role" id="userRole">lecturer</div>
            </div>
            <button class="btn btn-sm p-0 text-white-50" onclick="logout()" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
                <nav aria-label="breadcrumb"><ol class="breadcrumb breadcrumb-custom" id="breadcrumb"><li class="breadcrumb-item"><a href="#">Lecturer</a></li><li class="breadcrumb-item active" id="breadcrumbPage">Dashboard</li></ol></nav>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle-sm" onclick="toggleTheme()" title="Toggle Theme"><i class="bi bi-moon-stars-fill" id="themeIcon"></i></button>
                <button class="btn btn-outline-danger btn-sm" onclick="logout()"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </div>
        </div>
        <div class="page-content" id="pageContent"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
    const API='api.php';let TOKEN='',USER=null,currentPage='dashboard';
    (function(){TOKEN=localStorage.getItem('sazug_token')||'';const u=localStorage.getItem('sazug_user');if(u)USER=JSON.parse(u);if(!TOKEN||!USER||USER.role!=='lecturer'){window.location.href='index.php';return}
    document.getElementById('userName').textContent=USER.full_name||'Lecturer';document.getElementById('userRole').textContent='lecturer';document.getElementById('avatarInitial').textContent=(USER.full_name||'L').charAt(0).toUpperCase();
    const t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-bs-theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    loadPage('dashboard')})();

    function toggleTheme(){const t=document.documentElement.getAttribute('data-bs-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',t);localStorage.setItem('theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    function logout(){Swal.fire({title:'Logout?',icon:'question',showCancelButton:true,confirmButtonText:'Yes',confirmButtonColor:'#dc3545'}).then(r=>{if(r.isConfirmed){localStorage.removeItem('sazug_token');localStorage.removeItem('sazug_user');window.location.href='index.php'}})}
    async function apiCall(action,data={},method='POST'){const headers={'Content-Type':'application/json','Authorization':'Bearer '+TOKEN};if(method==='GET'){const p=new URLSearchParams({action,...data});const r=await fetch(API+'?'+p,{headers});return r.json()}const r=await fetch(API,{method:'POST',headers,body:JSON.stringify({action,...data})});return r.json()}

    document.querySelectorAll('.sidebar-link[data-page]').forEach(link=>{link.addEventListener('click',function(){document.querySelectorAll('.sidebar-link').forEach(l=>l.classList.remove('active'));this.classList.add('active');loadPage(this.dataset.page);if(window.innerWidth<992)document.getElementById('sidebar').classList.remove('show')})});

    async function loadPage(page){currentPage=page;document.getElementById('breadcrumbPage').textContent=page.charAt(0).toUpperCase()+page.slice(1);
    if(page==='dashboard')loadDashboard();else if(page==='students')loadStudents();else if(page==='profile')loadProfile()}

    // Dashboard
    async function loadDashboard(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        const d=await apiCall('dashboard',{},'GET');if(!d.success)return;
        const data=d.data;
        let h=`<div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-blue"><div class="d-flex justify-content-between"><div><div class="label">My Students</div><div class="value">${data.my_students||0}</div></div><div class="icon-box"><i class="bi bi-people-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-green"><div class="d-flex justify-content-between"><div><div class="label">My Courses</div><div class="value">${data.my_courses||0}</div></div><div class="icon-box"><i class="bi bi-book"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-gold"><div class="d-flex justify-content-between"><div><div class="label">Active</div><div class="value">${data.active_students||0}</div></div><div class="icon-box"><i class="bi bi-person-check-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-purple"><div class="d-flex justify-content-between"><div><div class="label">Total Enrolled</div><div class="value">${data.total_students||0}</div></div><div class="icon-box"><i class="bi bi-journal-check"></i></div></div></div></div>
        </div>`;
        h+=`<div class="profile-card mb-4"><div class="row"><div class="col-md-4"><div class="profile-header h-100 d-flex flex-column justify-content-center"><div class="avatar">${(USER.full_name||'L').charAt(0).toUpperCase()}</div><h5 class="mb-1">${USER.full_name||'Lecturer'}</h5><p class="mb-0 opacity-75">${USER.email||''}</p><p class="mb-0 opacity-50 small">${USER.phone||''}</p></div></div>
        <div class="col-md-8"><div class="profile-body"><h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Quick Information</h6>
        <div class="row g-2"><div class="col-sm-6"><small class="text-muted">Username</small><p class="mb-1 fw-semibold">${USER.username||'-'}</p></div>
        <div class="col-sm-6"><small class="text-muted">Role</small><p class="mb-1 fw-semibold text-capitalize">${USER.role||'lecturer'}</p></div>
        <div class="col-sm-6"><small class="text-muted">Last Login</small><p class="mb-1 fw-semibold">${USER.last_login||'N/A'}</p></div>
        <div class="col-sm-6"><small class="text-muted">Account Status</small><p class="mb-1"><span class="badge bg-success">Active</span></p></div></div></div></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
    }

    // Students
    async function loadStudents(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        const d=await apiCall('lecturer_students',{},'GET');if(!d.success)return;
        let h=`<div class="table-card"><div class="card-header"><i class="bi bi-people-fill me-2"></i>My Students Roster</div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="studentsTable"><thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Faculty</th><th>Department</th><th>Programme</th><th>Status</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#studentsTable').DataTable({responsive:true,pageLength:25});
        dt.clear().rows.add(d.data.map(s=>[s.student_id_number,s.full_name,s.email,s.phone||'-',s.gender,s.faculty_name||'-',s.department_name||'-',s.programme_name||'-',`<span class="badge badge-status status-${s.status}">${s.status}</span>`])).draw();
    }

    // Profile
    async function loadProfile(){
        let h=`<div class="row g-4"><div class="col-md-5"><div class="profile-card"><div class="profile-header"><div class="avatar">${(USER.full_name||'L').charAt(0).toUpperCase()}</div><h5 class="mb-1">${USER.full_name||'Lecturer'}</h5><p class="mb-0 opacity-75">Lecturer</p></div><div class="profile-body"><h6 class="mb-3">Account Details</h6>
        <div class="mb-2"><small class="text-muted">Email</small><p class="mb-1 fw-semibold">${USER.email||'-'}</p></div>
        <div class="mb-2"><small class="text-muted">Username</small><p class="mb-1 fw-semibold">${USER.username||'-'}</p></div>
        <div class="mb-2"><small class="text-muted">Phone</small><p class="mb-1 fw-semibold">${USER.phone||'-'}</p></div>
        <div class="mb-2"><small class="text-muted">Role</small><p class="mb-1"><span class="badge bg-primary">${USER.role}</span></p></div>
        <div><small class="text-muted">Last Login</small><p class="mb-0 fw-semibold">${USER.last_login||'N/A'}</p></div></div></div></div>`;
        h+=`<div class="col-md-7"><div class="card border-0 shadow-sm" style="border-radius:16px"><div class="card-header bg-transparent"><h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Profile</h6></div><div class="card-body">
        <form id="profileForm"><div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" id="profName" value="${USER.full_name||''}"></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" id="profPhone" value="${USER.phone||''}"></div>
        <button type="button" class="btn btn-primary" onclick="saveProfile()"><i class="bi bi-check-lg me-1"></i>Save Changes</button></form></div></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
    }

    async function saveProfile(){const fd={full_name:document.getElementById('profName').value,phone:document.getElementById('profPhone').value};
    const r=await apiCall('update_profile',fd);if(r.success){Swal.fire({icon:'success',title:'Profile Updated!',timer:1500,showConfirmButton:false});USER.full_name=fd.full_name;USER.phone=fd.phone;localStorage.setItem('sazug_user',JSON.stringify(USER));document.getElementById('userName').textContent=fd.full_name;document.getElementById('avatarInitial').textContent=fd.full_name.charAt(0).toUpperCase()}
    else Swal.fire('Error',r.error||'Failed','error')}
    </script>
</body>
</html>
