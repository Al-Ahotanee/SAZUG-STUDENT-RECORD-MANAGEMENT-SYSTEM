<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root{--sidebar-w:270px;--primary:#1a365d;--primary-light:#2a4a7f;--primary-dark:#0f2440;--gold:#c9973f;--gold-light:#daa84e;--sidebar-bg:linear-gradient(180deg,#0d1b2a 0%,#1a365d 100%)}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f0f2f5;overflow-x:hidden}
        [data-bs-theme="dark"] body{background:#0a1628;color:#e0e0e0}
        [data-bs-theme="dark"] .card{background:#152238;border-color:rgba(255,255,255,.08)}
        [data-bs-theme="dark"] .table{color:#e0e0e0}
        [data-bs-theme="dark"] .table td,[data-bs-theme="dark"] .table th{background:#152238;border-color:rgba(255,255,255,.06)}
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter input,[data-bs-theme="dark"] .dataTables_wrapper .dataTables_length select,[data-bs-theme="dark"] .dt-buttons .btn{background:#1a2a3d;border-color:rgba(255,255,255,.15);color:#e0e0e0}
        [data-bs-theme="dark"] .modal-content{background:#152238}
        [data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1a2a3d;color:#e0e0e0;border-color:rgba(255,255,255,.15)}

        /* Sidebar */
        .sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--sidebar-bg);z-index:1040;transition:transform .3s;overflow-y:auto;overflow-x:hidden}
        .sidebar::-webkit-scrollbar{width:4px}.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:4px}
        .sidebar-brand{padding:1.5rem;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.08)}
        .sidebar-brand i{font-size:1.8rem;color:var(--gold)}.sidebar-brand span{font-size:1.15rem;font-weight:700;color:var(--white);letter-spacing:.5px}
        .sidebar-section{padding:1rem 1.2rem .3rem;font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.4);font-weight:600}
        .sidebar-nav{padding:0 8px}
        .sidebar-link{display:flex;align-items:center;gap:12px;padding:.7rem 1rem;color:rgba(255,255,255,.7);text-decoration:none;border-radius:10px;font-size:.9rem;font-weight:500;transition:all .2s;margin-bottom:2px;cursor:pointer}
        .sidebar-link:hover{color:var(--white);background:rgba(255,255,255,.08)}
        .sidebar-link.active{color:var(--gold);background:rgba(201,151,63,.12);font-weight:600}
        .sidebar-link i{font-size:1.15rem;width:22px;text-align:center}
        .sidebar-user{padding:1.2rem;border-top:1px solid rgba(255,255,255,.08);margin-top:auto;display:flex;align-items:center;gap:12px}
        .sidebar-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-light));display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:.9rem}
        .sidebar-user-info{flex:1}.sidebar-user-info .name{color:var(--white);font-size:.85rem;font-weight:600}.sidebar-user-info .role{color:rgba(255,255,255,.5);font-size:.75rem;text-transform:capitalize}

        /* Main */
        .main-content{margin-left:var(--sidebar-w);min-height:100vh;transition:margin .3s}
        .topbar{background:var(--white);padding:.8rem 1.5rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e0e0e0;position:sticky;top:0;z-index:1030}
        [data-bs-theme="dark"] .topbar{background:#0d1b2a;border-color:rgba(255,255,255,.08)}
        .breadcrumb-custom{margin:0;font-size:.85rem}.breadcrumb-custom .breadcrumb-item a{color:var(--primary);text-decoration:none}
        .topbar-actions{display:flex;align-items:center;gap:10px}
        .theme-toggle-sm{width:36px;height:36px;border-radius:50%;border:2px solid #e0e0e0;background:transparent;color:var(--primary);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .3s;font-size:1rem}
        .theme-toggle-sm:hover{background:rgba(201,151,63,.1);border-color:var(--gold);color:var(--gold)}
        .page-content{padding:1.5rem}

        /* Cards */
        .stat-card{border:none;border-radius:16px;padding:1.3rem;transition:transform .2s;box-shadow:0 2px 10px rgba(0,0,0,.06)}
        .stat-card:hover{transform:translateY(-3px)}.stat-card .icon-box{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem}
        .stat-card .value{font-size:1.7rem;font-weight:700;line-height:1.2;margin-top:.5rem}
        .stat-card .label{font-size:.82rem;color:#666;font-weight:500}
        [data-bs-theme="dark"] .stat-card .label{color:#aaa}
        .stat-card-blue{background:linear-gradient(135deg,#1a365d,#2a4a7f);color:#fff}.stat-card-blue .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-green{background:linear-gradient(135deg,#0d6833,#16a34a);color:#fff}.stat-card-green .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-gold{background:linear-gradient(135deg,#a87d2e,#c9973f);color:#fff}.stat-card-gold .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-red{background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff}.stat-card-red .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-purple{background:linear-gradient(135deg,#5b21b6,#7c3aed);color:#fff}.stat-card-purple .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-teal{background:linear-gradient(135deg,#0d6e6e,#14b8a6);color:#fff}.stat-card-teal .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-orange{background:linear-gradient(135deg,#c2410c,#f97316);color:#fff}.stat-card-orange .icon-box{background:rgba(255,255,255,.2)}
        .stat-card-cyan{background:linear-gradient(135deg,#0e7490,#06b6d4);color:#fff}.stat-card-cyan .icon-box{background:rgba(255,255,255,.2)}

        /* Charts */
        .chart-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
        .chart-card .card-header{background:transparent;border-bottom:1px solid #eee;padding:1rem 1.2rem;font-weight:600;font-size:.95rem}
        [data-bs-theme="dark"] .chart-card .card-header{border-color:rgba(255,255,255,.08)}

        /* Table */
        .table-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
        .table-card .card-header{background:transparent;border-bottom:1px solid #eee;padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
        [data-bs-theme="dark"] .table-card .card-header{border-color:rgba(255,255,255,.08)}
        .badge-status{padding:.35em .7em;font-size:.75rem;font-weight:600}
        .btn-action{width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;font-size:.8rem;transition:all .2s}

        /* Forms */
        .modal-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border:none}
        .modal-header .btn-close{filter:brightness(0) invert(1)}
        .form-label{font-weight:500;font-size:.88rem;margin-bottom:.3rem}

        /* Status badges */
        .status-active{background:#dcfce7;color:#166534}.status-suspended{background:#fef3c7;color:#92400e}
        .status-withdrawn{background:#fee2e2;color:#991b1b}.status-graduated{background:#dbeafe;color:#1e40af}
        .status-expelled{background:#fce7f3;color:#9d174d}.status-upcoming{background:#f3f4f6;color:#374151}
        .status-completed{background:#dcfce7;color:#166534}

        /* Responsive */
        .sidebar-toggle{display:none;background:none;border:none;color:var(--primary);font-size:1.3rem;cursor:pointer}
        @media(max-width:992px){.sidebar{transform:translateX(-100%)}.sidebar.show{transform:translateX(0)}.main-content{margin-left:0}.sidebar-toggle{display:block}}
        .dataTables_wrapper{overflow-x:auto}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand"><i class="bi bi-mortarboard-fill"></i><span>SAZUG SRMS</span></div>
        <div class="sidebar-section">Main</div>
        <nav class="sidebar-nav">
            <div class="sidebar-link active" data-page="dashboard"><i class="bi bi-grid-1x2-fill"></i>Dashboard</div>
            <div class="sidebar-section">Academic</div>
            <div class="sidebar-link" data-page="students"><i class="bi bi-people-fill"></i>Students</div>
            <div class="sidebar-link" data-page="faculties"><i class="bi bi-building"></i>Faculties</div>
            <div class="sidebar-link" data-page="departments"><i class="bi bi-diagram-3"></i>Departments</div>
            <div class="sidebar-link" data-page="programmes"><i class="bi bi-journal-bookmark-fill"></i>Programmes</div>
            <div class="sidebar-link" data-page="courses"><i class="bi bi-book"></i>Courses</div>
            <div class="sidebar-link" data-page="sessions"><i class="bi bi-calendar-event"></i>Sessions</div>
            <div class="sidebar-section">Management</div>
            <div class="sidebar-link" data-page="staff"><i class="bi bi-person-badge"></i>Staff</div>
            <div class="sidebar-link" data-page="audit"><i class="bi bi-clock-history"></i>Audit Log</div>
            <div class="sidebar-section">System</div>
            <div class="sidebar-link" data-page="settings"><i class="bi bi-gear-fill"></i>Settings</div>
        </nav>
        <div class="sidebar-user" id="sidebarUser">
            <div class="sidebar-avatar" id="avatarInitial">A</div>
            <div class="sidebar-user-info">
                <div class="name" id="userName">Admin</div>
                <div class="role" id="userRole">superadmin</div>
            </div>
            <button class="btn btn-sm p-0 text-white-50" onclick="logout()" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
                <nav aria-label="breadcrumb"><ol class="breadcrumb breadcrumb-custom" id="breadcrumb"><li class="breadcrumb-item"><a href="#">Admin</a></li><li class="breadcrumb-item active" id="breadcrumbPage">Dashboard</li></ol></nav>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle-sm" onclick="toggleTheme()" title="Toggle Theme"><i class="bi bi-moon-stars-fill" id="themeIcon"></i></button>
                <button class="btn btn-outline-danger btn-sm" onclick="logout()"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </div>
        </div>
        <div class="page-content" id="pageContent">
            <!-- Pages injected here by JS -->
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
    const API='api.php';let TOKEN='',USER=null,currentPage='dashboard';
    (function(){TOKEN=localStorage.getItem('sazug_token')||'';const u=localStorage.getItem('sazug_user');if(u)USER=JSON.parse(u);if(!TOKEN||!USER){window.location.href='index.php';return}
    if(USER){document.getElementById('userName').textContent=USER.full_name||'Admin';document.getElementById('userRole').textContent=USER.role||'superadmin';document.getElementById('avatarInitial').textContent=(USER.full_name||'A').charAt(0).toUpperCase()}
    const t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-bs-theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    loadPage('dashboard')})();

    function toggleTheme(){const t=document.documentElement.getAttribute('data-bs-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',t);localStorage.setItem('theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    function logout(){Swal.fire({title:'Logout?',icon:'question',showCancelButton:true,confirmButtonText:'Yes, logout',confirmButtonColor:'#dc3545'}).then(r=>{if(r.isConfirmed){localStorage.removeItem('sazug_token');localStorage.removeItem('sazug_user');window.location.href='index.php'}})}

    async function apiCall(action,data={},method='POST'){const opts={method,'Content-Type':'application/json'};if(TOKEN)opts.headers={'Content-Type':'application/json','Authorization':'Bearer '+TOKEN};if(method==='GET'){const params=new URLSearchParams({action,...data});const r=await fetch(API+'?'+params,{headers:{Authorization:'Bearer '+TOKEN}});return r.json()}
    opts.body=JSON.stringify({action,...data});const r=await fetch(API,opts);return r.json()}

    // Navigation
    document.querySelectorAll('.sidebar-link[data-page]').forEach(link=>{link.addEventListener('click',function(){document.querySelectorAll('.sidebar-link').forEach(l=>l.classList.remove('active'));this.classList.add('active');loadPage(this.dataset.page);if(window.innerWidth<992)document.getElementById('sidebar').classList.remove('show')})});

    async function loadPage(page){currentPage=page;document.getElementById('breadcrumbPage').textContent=page.charAt(0).toUpperCase()+page.slice(1);
    const pages={dashboard:loadDashboard,students:loadStudents,faculties:loadFaculties,departments:loadDepartments,programmes:loadProgrammes,courses:loadCourses,staff:loadStaff,sessions:loadSessions,audit:loadAudit,settings:loadSettings};
    if(pages[page])pages[page]();}

    // ============ DASHBOARD ============
    async function loadDashboard(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading dashboard...</p></div>';
        const d=await apiCall('dashboard',{},'GET');if(!d.success)return document.getElementById('pageContent').innerHTML='<p class="text-danger">Failed to load dashboard</p>';
        const data=d.data;let h=`<div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-blue"><div class="d-flex justify-content-between"><div><div class="label">Total Students</div><div class="value">${data.total_students}</div></div><div class="icon-box"><i class="bi bi-people-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-green"><div class="d-flex justify-content-between"><div><div class="label">Active Students</div><div class="value">${data.active_students}</div></div><div class="icon-box"><i class="bi bi-person-check-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-gold"><div class="d-flex justify-content-between"><div><div class="label">Graduated</div><div class="value">${data.graduated_students}</div></div><div class="icon-box"><i class="bi bi-award-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-red"><div class="d-flex justify-content-between"><div><div class="label">Suspended</div><div class="value">${data.suspended_students}</div></div><div class="icon-box"><i class="bi bi-pause-circle-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-purple"><div class="d-flex justify-content-between"><div><div class="label">Faculties</div><div class="value">${data.total_faculties}</div></div><div class="icon-box"><i class="bi bi-building"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-teal"><div class="d-flex justify-content-between"><div><div class="label">Departments</div><div class="value">${data.total_departments}</div></div><div class="icon-box"><i class="bi bi-diagram-3"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-orange"><div class="d-flex justify-content-between"><div><div class="label">Programmes</div><div class="value">${data.total_programmes}</div></div><div class="icon-box"><i class="bi bi-journal-bookmark-fill"></i></div></div></div></div>
        <div class="col-xl-3 col-sm-6"><div class="stat-card stat-card-cyan"><div class="d-flex justify-content-between"><div><div class="label">Total Staff</div><div class="value">${data.total_staff}</div></div><div class="icon-box"><i class="bi bi-person-badge"></i></div></div></div></div>
        </div>`;
        h+=`<div class="row g-3 mb-4"><div class="col-lg-4"><div class="chart-card"><div class="card-header"><i class="bi bi-pie-fill me-2"></i>Gender Distribution</div><div class="card-body p-3"><canvas id="chartGender" height="250"></canvas></div></div></div>`;
        h+=`<div class="col-lg-4"><div class="chart-card"><div class="card-header"><i class="bi bi-bar-chart-fill me-2"></i>Faculty Distribution</div><div class="card-body p-3"><canvas id="chartFaculty" height="250"></canvas></div></div></div>`;
        h+=`<div class="col-lg-4"><div class="chart-card"><div class="card-header"><i class="bi bi-graph-up me-2"></i>Enrollment Trend</div><div class="card-body p-3"><canvas id="chartEnroll" height="250"></canvas></div></div></div></div>`;
        h+=`<div class="table-card"><div class="card-header"><span><i class="bi bi-clock-history me-2"></i>Recent Activities</span></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>`;
        (data.recent_activities||[]).forEach(a=>{h+=`<tr><td>${a.created_at||''}</td><td>${a.user_name||'-'}</td><td><span class="badge bg-primary bg-opacity-10 text-primary">${a.action||''}</span></td><td>${(a.details||'').substring(0,60)}</td><td><small>${a.ip_address||''}</small></td></tr>`});
        h+=`</tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        // Charts
        setTimeout(()=>{try{
        new Chart(document.getElementById('chartGender'),{type:'doughnut',data:{labels:['Male','Female'],datasets:[{data:[data.gender_distribution?.male||0,data.gender_distribution?.female||0],backgroundColor:['#1a365d','#c9973f'],borderWidth:0}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
        const facLabels=(data.faculty_distribution||[]).map(f=>f.name);const facData=(data.faculty_distribution||[]).map(f=>parseInt(f.cnt));
        new Chart(document.getElementById('chartFaculty'),{type:'bar',data:{labels:facLabels,datasets:[{label:'Students',data:facData,backgroundColor:'rgba(201,151,63,.7)',borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
        const mLabels=(data.enrollment_trend||[]).map(m=>m.month);const mData=(data.enrollment_trend||[]).map(m=>parseInt(m.cnt));
        new Chart(document.getElementById('chartEnroll'),{type:'line',data:{labels:mLabels,datasets:[{label:'Enrollments',data:mData,borderColor:'#c9973f',backgroundColor:'rgba(201,151,63,.1)',fill:true,tension:.4}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});
        }catch(e){}},300)}

    // ============ STUDENTS ============
    async function loadStudents(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        const d=await apiCall('students',{},'GET');if(!d.success)return;
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-people-fill me-2"></i>Students Management</span><div class="d-flex gap-2"><button class="btn btn-primary btn-sm" onclick="openStudentModal()"><i class="bi bi-plus-lg me-1"></i>Add Student</button></div></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="studentsTable"><thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Gender</th><th>Faculty</th><th>Department</th><th>Programme</th><th>Level</th><th>Status</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#studentsTable').DataTable({responsive:true,dom:'Bfrtip',buttons:[{extend:'csv',className:'btn btn-sm btn-outline-primary'},{extend:'print',className:'btn btn-sm btn-outline-primary'}],pageLength:25});
        dt.clear().rows.add(d.data.map(s=>[s.student_id_number,s.full_name,s.email,s.gender,s.faculty_name||'-',s.department_name||'-',s.programme_name||'-',s.level,`<span class="badge badge-status status-${s.status}">${s.status}</span>`,`<div class="d-flex gap-1">${actionBtns('student',s.id,s.status)}</div>`])).draw();
        // Load filters
        const facs=await apiCall('lookup',{type:'faculties'},'GET');const depts=await apiCall('lookup',{type:'departments'},'GET');const progs=await apiCall('lookup',{type:'programmes'},'GET');
        // Search filters
        let filterHtml=`<div class="row g-2 mb-3"><div class="col"><select class="form-select form-select-sm" id="filterStatus" onchange="filterStudents()"><option value="">All Status</option><option>active</option><option>suspended</option><option>withdrawn</option><option>graduated</option></select></div>`;
        filterHtml+=`<div class="col"><select class="form-select form-select-sm" id="filterGender" onchange="filterStudents()"><option value="">All Gender</option><option>Male</option><option>Female</option></select></div>`;
        filterHtml+=`<div class="col"><select class="form-select form-select-sm" id="filterFaculty" onchange="loadDeptFilter()"><option value="">All Faculties</option>${(facs.data||[]).map(f=>`<option value="${f.id}">${f.name}</option>`).join('')}</select></div>`;
        filterHtml+=`<div class="col"><select class="form-select form-select-sm" id="filterDept"><option value="">All Departments</option></select></div>`;
        filterHtml+=`<div class="col"><select class="form-select form-select-sm" id="filterProgType"><option value="">All Types</option><option>Diploma</option><option>ND</option><option>HND</option><option>Degree</option><option>Masters</option></select></div></div>`;
        $('.table-card .card-header').after(filterHtml);
    }

    async function filterStudents(){let q={};const s=$('#filterStatus').val();if(s)q.status=s;const g=$('#filterGender').val();if(g)q.gender=g;const f=$('#filterFaculty').val();if(f)q.faculty=f;const d=$('#filterDept').val();if(d)q.department=d;const p=$('#filterProgType').val();if(p)q.programme_type=p;const r=await apiCall('students',q,'GET');if(r.success){$('#studentsTable').DataTable().clear().rows.add(r.data.map(s=>[s.student_id_number,s.full_name,s.email,s.gender,s.faculty_name||'-',s.department_name||'-',s.programme_name||'-',s.level,`<span class="badge badge-status status-${s.status}">${s.status}</span>`,`<div class="d-flex gap-1">${actionBtns('student',s.id,s.status)}</div>`])).draw()}}
    async function loadDeptFilter(){const fid=$('#filterFaculty').val();const r=await apiCall('lookup',{type:'departments',faculty_id:fid},'GET');$('#filterDept').html('<option value="">All Departments</option>'+(r.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join(''));filterStudents()}

    function actionBtns(type,id,status=''){let b=`<button class="btn btn-action btn-outline-primary" onclick="openEditModal('${type}',${id})" title="Edit"><i class="bi bi-pencil"></i></button>`;
    if(type==='student'){b+=`<button class="btn btn-action btn-outline-warning" onclick="studentAction(${id},'suspend')" title="Suspend"><i class="bi bi-pause-circle"></i></button>`;
    b+=`<button class="btn btn-action btn-outline-danger" onclick="studentAction(${id},'withdraw')" title="Withdraw"><i class="bi bi-x-circle"></i></button>`;
    if(status==='suspended'||status==='withdrawn')b+=`<button class="btn btn-action btn-outline-success" onclick="studentAction(${id},'reinstate')" title="Reinstate"><i class="bi bi-arrow-counterclockwise"></i></button>`;
    b+=`<button class="btn btn-action btn-outline-info" onclick="studentAction(${id},'graduate')" title="Graduate"><i class="bi bi-mortarboard"></i></button>`;
    b+=`<button class="btn btn-action btn-outline-secondary" onclick="studentAction(${id},'promote')" title="Promote"><i class="bi bi-arrow-up-circle"></i></button>`}
    b+=`<button class="btn btn-action btn-outline-danger" onclick="deleteItem('${type}',${id})" title="Delete"><i class="bi bi-trash"></i></button>`;return b}

    function openStudentModal(data=null){showCrudModal('Student',data,`
    <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="full_name" required value="${data?.full_name||''}"></div>
    <div class="col-md-6"><label class="form-label">Username *</label><input type="text" class="form-control" name="username" required value="${data?.student_id_number?'':''}"></div>
    <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required value="${data?.email||''}"></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="${data?.phone||''}"></div>
    <div class="col-md-6"><label class="form-label">Gender *</label><select class="form-select" name="gender" required><option value="Male" ${data?.gender==='Male'?'selected':''}>Male</option><option value="Female" ${data?.gender==='Female'?'selected':''}>Female</option></select></div>
    <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="${data?.date_of_birth||''}"></div>
    <div class="col-md-4"><label class="form-label">Faculty</label><select class="form-select" name="faculty_id" id="modalFaculty" onchange="loadModalDepts()"><option value="">Select</option></select></div>
    <div class="col-md-4"><label class="form-label">Department</label><select class="form-select" name="department_id" id="modalDept" onchange="loadModalProgs()"><option value="">Select</option></select></div>
    <div class="col-md-4"><label class="form-label">Programme</label><select class="form-select" name="programme_id" id="modalProg"><option value="">Select</option></select></div>
    <div class="col-md-4"><label class="form-label">Programme Type</label><select class="form-select" name="programme_type"><option ${data?.programme_type==='Diploma'?'selected':''}>Diploma</option><option ${data?.programme_type==='ND'?'selected':''}>ND</option><option ${data?.programme_type==='HND'?'selected':''}>HND</option><option ${data?.programme_type==='Degree'?'selected':''}>Degree</option><option ${data?.programme_type==='Masters'?'selected':''}>Masters</option></select></div>
    <div class="col-md-4"><label class="form-label">Level</label><select class="form-select" name="level">${[100,200,300,400,500,600].map(l=>`<option value="${l}" ${data?.level==l?'selected':''}>${l}</option>`).join('')}</select></div>
    <div class="col-md-4"><label class="form-label">Session</label><select class="form-select" name="session_id" id="modalSession"><option value="">Select</option></select></div>
    <div class="col-md-6"><label class="form-label">State of Origin</label><input type="text" class="form-control" name="state_of_origin" value="${data?.state_of_origin||''}"></div>
    <div class="col-md-6"><label class="form-label">Guardian Name</label><input type="text" class="form-control" name="guardian_name" value="${data?.guardian_name||''}"></div>
    <div class="col-12"><label class="form-label">Home Address</label><textarea class="form-control" name="home_address" rows="2">${data?.home_address||''}</textarea></div>
    </div>`,async(formData)=>{
        if(data){formData.id=data.id;const r=await apiCall('update_student',formData);return r}
        const r=await apiCall('create_student',formData);return r},
    async()=>{
        const facs=await apiCall('lookup',{type:'faculties'},'GET');$('#modalFaculty').html('<option value="">Select</option>'+(facs.data||[]).map(f=>`<option value="${f.id}">${f.name}</option>`).join(''));
        const sess=await apiCall('lookup',{type:'sessions'},'GET');$('#modalSession').html('<option value="">Select</option>'+(sess.data||[]).map(s=>`<option value="${s.id}">${s.name}</option>`).join(''));
        if(data&&data.faculty_id){$('#modalFaculty').val(data.faculty_id);await loadModalDepts(data.department_id);if(data.department_id)await loadModalProgs(data.programme_id)}
    })}

    async function loadModalDepts(sel=null){const f=$('#modalFaculty').val();const r=await apiCall('lookup',{type:'departments',faculty_id:f},'GET');$('#modalDept').html('<option value="">Select</option>'+(r.data||[]).map(d=>`<option value="${d.id}" ${d.id==sel?'selected':''}>${d.name}</option>`).join(''))}
    async function loadModalProgs(sel=null){const d=$('#modalDept').val();const r=await apiCall('lookup',{type:'programmes',department_id:d},'GET');$('#modalProg').html('<option value="">Select</option>'+(r.data||[]).map(p=>`<option value="${p.id}" ${p.id==sel?'selected':''}>${p.name} (${p.type})</option>`).join(''))}

    async function studentAction(id,type){const label=type.charAt(0).toUpperCase()+type.slice(1);Swal.fire({title:`${label} Student?`,icon:'question',showCancelButton:true,confirmButtonText:`Yes, ${label}`,confirmButtonColor:type==='reinstate'?'#198754':'#dc3545'}).then(async r=>{if(r.isConfirmed){const res=await apiCall('student_action',{id,type});if(res.success){Swal.fire({icon:'success',title:'Success!',text:res.message,timer:1500,showConfirmButton:false});loadStudents()}else{Swal.fire('Error',res.error,'error')}}})}

    // ============ GENERIC CRUD ============
    function showCrudModal(title,data,formHtml,saveFn,onLoad=null){
        const id=data?'edit-'+title.toLowerCase():'create-'+title.toLowerCase();
        const modalHtml=`<div class="modal fade" id="crudModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-${data?'pencil':'plus-circle'} me-2"></i>${data?'Edit':'Create'} ${title}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="crudForm">${formHtml}</form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="crudSaveBtn" onclick="saveCrud()"><i class="bi bi-check-lg me-1"></i>Save</button></div></div></div></div>`;
        $('#crudModal').remove();$('body').append(modalHtml);const modal=new bootstrap.Modal(document.getElementById('crudModal'));modal.show();
        window._crudSaveFn=saveFn;if(onLoad)onLoad();
        window.saveCrud=async function(){const fd={};$('#crudForm [name]').each(function(){fd[this.name]=this.value});const r=await saveFn(fd);if(r&&r.success){Swal.fire({icon:'success',title:'Success!',text:r.message||'Saved successfully',timer:1500,showConfirmButton:false});modal.hide();loadPage(currentPage)}else{Swal.fire('Error',r?.error||'Save failed','error')}}
    }

    async function openEditModal(type,id){
        let actionMap={student:'students',faculty:'faculties',department:'departments',programme:'programmes',course:'courses',staff:'staff'};
        const r=await apiCall(actionMap[type]||type+'s',{},'GET');
        const item=(r.data||[]).find(i=>i.id==id);
        if(type==='student')openStudentModal(item);
        else if(type==='faculty')openFacultyModal(item);
        else if(type==='department')openDepartmentModal(item);
        else if(type==='programme')openProgrammeModal(item);
        else if(type==='course')openCourseModal(item);
        else if(type==='staff')openStaffModal(item);
    }

    async function deleteItem(type,id){Swal.fire({title:'Delete this item?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc3545',confirmButtonText:'Delete'}).then(async r=>{if(r.isConfirmed){const actionMap={student:'delete_student',faculty:'delete_faculty',department:'delete_department',programme:'delete_programme',course:'delete_course',staff:'delete_staff'};
    const res=await apiCall(actionMap[type]||'delete_'+type+'s',{id});if(res.success){Swal.fire({icon:'success',title:'Deleted!',timer:1000,showConfirmButton:false});loadPage(currentPage)}else Swal.fire('Error',res.error,'error')}})}

    // ============ FACULTIES ============
    async function loadFaculties(){
        const d=await apiCall('faculties',{},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-building me-2"></i>Faculties</span><button class="btn btn-primary btn-sm" onclick="openFacultyModal()"><i class="bi bi-plus-lg me-1"></i>Add Faculty</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="facultiesTable"><thead><tr><th>Code</th><th>Name</th><th>Dean</th><th>Departments</th><th>Students</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#facultiesTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print'],pageLength:25});
        dt.clear().rows.add(d.data.map(f=>[f.code,f.name,f.dean_name||'-',f.dept_count||0,f.student_count||0,`<div class="d-flex gap-1">${actionBtns('faculty',f.id)}</div>`])).draw();
    }

    function openFacultyModal(data=null){showCrudModal('Faculty',data,`
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required value="${data?.name||''}"></div>
    <div class="col-md-6"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required value="${data?.code||''}"></div>
    <div class="col-md-6"><label class="form-label">Dean Name</label><input type="text" class="form-control" name="dean_name" value="${data?.dean_name||''}"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3">${data?.description||''}</textarea></div></div>`,
    async fd=>data?await apiCall('update_faculty',{...fd,id:data.id}):await apiCall('create_faculty',fd))}

    // ============ DEPARTMENTS ============
    async function loadDepartments(){
        const d=await apiCall('departments',{},'GET');const facs=await apiCall('lookup',{type:'faculties'},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-diagram-3 me-2"></i>Departments</span><button class="btn btn-primary btn-sm" onclick="openDepartmentModal()"><i class="bi bi-plus-lg me-1"></i>Add Department</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="deptsTable"><thead><tr><th>Code</th><th>Name</th><th>Faculty</th><th>Head</th><th>Programmes</th><th>Students</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#deptsTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print']});
        dt.clear().rows.add(d.data.map(de=>[de.code,de.name,de.faculty_name||'-',de.head_name||'-',de.prog_count||0,de.student_count||0,`<div class="d-flex gap-1">${actionBtns('department',de.id)}</div>`])).draw();
    }

    function openDepartmentModal(data=null){showCrudModal('Department',data,`
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required value="${data?.name||''}"></div>
    <div class="col-md-6"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required value="${data?.code||''}"></div>
    <div class="col-md-6"><label class="form-label">Faculty *</label><select class="form-select" name="faculty_id" required id="deptFacultySelect"><option value="">Select</option></select></div>
    <div class="col-md-6"><label class="form-label">Head Name</label><input type="text" class="form-control" name="head_name" value="${data?.head_name||''}"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3">${data?.description||''}</textarea></div></div>`,
    async fd=>data?await apiCall('update_department',{...fd,id:data.id}):await apiCall('create_department',fd),
    async()=>{const facs=await apiCall('lookup',{type:'faculties'},'GET');$('#deptFacultySelect').html('<option value="">Select</option>'+(facs.data||[]).map(f=>`<option value="${f.id}" ${data?.faculty_id==f.id?'selected':''}>${f.name}</option>`).join(''))})}

    // ============ PROGRAMMES ============
    async function loadProgrammes(){
        const d=await apiCall('programmes',{},'GET');
        let h=`<div class="row g-3 mb-3"><div class="col-md-3"><select class="form-select form-select-sm" id="progTypeFilter" onchange="filterProgrammes()"><option value="">All Types</option><option>Diploma</option><option>ND</option><option>HND</option><option>Degree</option><option>Masters</option></select></div></div>`;
        h+=`<div class="table-card"><div class="card-header"><span><i class="bi bi-journal-bookmark-fill me-2"></i>Programmes</span><button class="btn btn-primary btn-sm" onclick="openProgrammeModal()"><i class="bi bi-plus-lg me-1"></i>Add Programme</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="progsTable"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Duration</th><th>Department</th><th>Faculty</th><th>Students</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#progsTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print']});
        dt.clear().rows.add(d.data.map(p=>[p.code,p.name,`<span class="badge bg-primary bg-opacity-10 text-primary">${p.type}</span>`,p.duration_years+' years',p.department_name||'-',p.faculty_name||'-',p.student_count||0,`<div class="d-flex gap-1">${actionBtns('programme',p.id)}</div>`])).draw();
    }

    async function filterProgrammes(){const t=$('#progTypeFilter').val();const r=await apiCall('lookup',{type:'programmes',...t?{department_id:''}:{}},'GET');
    const d=await apiCall('programmes',t?{type:t}:{},'GET');
    $('#progsTable').DataTable().clear().rows.add(d.data.map(p=>[p.code,p.name,`<span class="badge bg-primary bg-opacity-10 text-primary">${p.type}</span>`,p.duration_years+' years',p.department_name||'-',p.faculty_name||'-',p.student_count||0,`<div class="d-flex gap-1">${actionBtns('programme',p.id)}</div>`])).draw()}

    function openProgrammeModal(data=null){showCrudModal('Programme',data,`
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" required value="${data?.name||''}"></div>
    <div class="col-md-6"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required value="${data?.code||''}"></div>
    <div class="col-md-4"><label class="form-label">Type *</label><select class="form-select" name="type" required><option ${data?.type==='Diploma'?'selected':''}>Diploma</option><option ${data?.type==='ND'?'selected':''}>ND</option><option ${data?.type==='HND'?'selected':''}>HND</option><option ${data?.type==='Degree'?'selected':''}>Degree</option><option ${data?.type==='Masters'?'selected':''}>Masters</option></select></div>
    <div class="col-md-4"><label class="form-label">Duration (years)</label><input type="number" class="form-control" name="duration_years" min="1" max="10" value="${data?.duration_years||4}"></div>
    <div class="col-md-4"><label class="form-label">Department *</label><select class="form-select" name="department_id" required id="progDeptSelect"><option value="">Select</option></select></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3">${data?.description||''}</textarea></div></div>`,
    async fd=>data?await apiCall('update_programme',{...fd,id:data.id}):await apiCall('create_programme',fd),
    async()=>{const depts=await apiCall('lookup',{type:'departments'},'GET');$('#progDeptSelect').html('<option value="">Select</option>'+(depts.data||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join(''));if(data?.department_id)$('#progDeptSelect').val(data.department_id)})}

    // ============ COURSES ============
    async function loadCourses(){
        const d=await apiCall('courses',{},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-book me-2"></i>Courses</span><button class="btn btn-primary btn-sm" onclick="openCourseModal()"><i class="bi bi-plus-lg me-1"></i>Add Course</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="coursesTable"><thead><tr><th>Code</th><th>Title</th><th>Department</th><th>Units</th><th>Semester</th><th>Level</th><th>Lecturer</th><th>Prerequisite</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#coursesTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print']});
        dt.clear().rows.add(d.data.map(c=>[c.code,c.title,c.department_name||'-',c.credit_units,c.semester,c.level,c.lecturer_name||'-',c.prerequisite_title||'-',`<div class="d-flex gap-1">${actionBtns('course',c.id)}</div>`])).draw();
    }

    function openCourseModal(data=null){showCrudModal('Course',data,`
    <div class="row g-3"><div class="col-md-4"><label class="form-label">Code *</label><input type="text" class="form-control" name="code" required value="${data?.code||''}"></div>
    <div class="col-md-8"><label class="form-label">Title *</label><input type="text" class="form-control" name="title" required value="${data?.title||''}"></div>
    <div class="col-md-6"><label class="form-label">Department *</label><select class="form-select" name="department_id" required id="courseDeptSelect"><option value="">Select</option></select></div>
    <div class="col-md-3"><label class="form-label">Credit Units</label><input type="number" class="form-control" name="credit_units" min="1" max="12" value="${data?.credit_units||2}"></div>
    <div class="col-md-3"><label class="form-label">Semester</label><select class="form-select" name="semester"><option ${data?.semester==='First'?'selected':''}>First</option><option ${data?.semester==='Second'?'selected':''}>Second</option><option ${data?.semester==='Both'?'selected':''}>Both</option></select></div>
    <div class="col-md-3"><label class="form-label">Level</label><select class="form-select" name="level">${[100,200,300,400,500].map(l=>`<option value="${l}" ${data?.level==l?'selected':''}>${l}</option>`).join('')}</select></div>
    <div class="col-md-3"><label class="form-label">Lecturer</label><select class="form-select" name="lecturer_id" id="courseLectSelect"><option value="">None</option></select></div>
    <div class="col-md-3"><label class="form-label">Elective</label><select class="form-select" name="is_elective"><option value="0" ${!data?.is_elective?'selected':''}>No</option><option value="1" ${data?.is_elective?'selected':''}>Yes</option></select></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2">${data?.description||''}</textarea></div></div>`,
    async fd=>data?await apiCall('update_course',{...fd,id:data.id}):await apiCall('create_course',fd),
    async()=>{const depts=await apiCall('lookup',{type:'departments'},'GET');const lecs=await apiCall('lookup',{type:'lecturers'},'GET');const courses=await apiCall('courses',{},'GET');
    $('#courseDeptSelect').html('<option value="">Select</option>'+(depts.data||[]).map(d=>`<option value="${d.id}" ${data?.department_id==d.id?'selected':''}>${d.name}</option>`).join(''));
    $('#courseLectSelect').html('<option value="">None</option>'+(lecs.data||[]).map(l=>`<option value="${l.id}" ${data?.lecturer_id==l.id?'selected':''}>${l.full_name}</option>`).join(''))})}

    // ============ STAFF ============
    async function loadStaff(){
        const d=await apiCall('staff',{},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-person-badge me-2"></i>Staff</span><button class="btn btn-primary btn-sm" onclick="openStaffModal()"><i class="bi bi-plus-lg me-1"></i>Add Staff</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="staffTable"><thead><tr><th>Name</th><th>Email</th><th>Username</th><th>Phone</th><th>Role</th><th>Courses</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#staffTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print']});
        dt.clear().rows.add(d.data.map(s=>[s.full_name,s.email,s.username,s.phone||'-',`<span class="badge bg-primary bg-opacity-10 text-primary">${s.role}</span>`,s.course_count||0,`<div class="d-flex gap-1">${actionBtns('staff',s.id)}</div>`])).draw();
    }

    function openStaffModal(data=null){showCrudModal('Staff',data,`
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="full_name" required value="${data?.full_name||''}"></div>
    <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required value="${data?.email||''}"></div>
    <div class="col-md-6"><label class="form-label">Username *</label><input type="text" class="form-control" name="username" required value="${data?.username||''}"></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="${data?.phone||''}"></div>
    <div class="col-md-6"><label class="form-label">Role *</label><select class="form-select" name="role" required><option value="admin" ${data?.role==='admin'?'selected':''}>Admin</option><option value="lecturer" ${data?.role==='lecturer'||!data?'selected':''}>Lecturer</option></select></div>
    <div class="col-md-6"><label class="form-label">${data?'':'Password'}</label><input type="text" class="form-control" name="password" placeholder="Default: Staff@123"></div></div>`,
    async fd=>data?await apiCall('update_staff',{...fd,id:data.id}):await apiCall('create_staff',fd))}

    // ============ SESSIONS ============
    async function loadSessions(){
        const d=await apiCall('sessions',{},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-calendar-event me-2"></i>Academic Sessions</span><button class="btn btn-primary btn-sm" onclick="openSessionModal()"><i class="bi bi-plus-lg me-1"></i>Create Session</button></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="sessionsTable"><thead><tr><th>Name</th><th>Semester</th><th>Start</th><th>End</th><th>Status</th><th>Students</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#sessionsTable').DataTable({responsive:true,dom:'Bfrtip',buttons:['csv','print']});
        dt.clear().rows.add(d.data.map(s=>{const statusClass=s.is_current?'status-active':s.status==='completed'?'status-completed':'status-upcoming';
        return[s.name,s.semester,s.start_date||'-',s.end_date||'-',`<span class="badge badge-status ${statusClass}">${s.is_current?'Active':s.status}</span>`,s.student_count||0,`<div class="d-flex gap-1">${s.is_current?'':`<button class="btn btn-action btn-outline-success" onclick="activateSession(${s.id})" title="Activate"><i class="bi bi-lightning"></i></button>`}</div>`]})).draw();
    }

    function openSessionModal(data=null){showCrudModal('Session',data,`
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Session Name *</label><input type="text" class="form-control" name="name" required value="${data?.name||''}" placeholder="e.g. 2024/2025 First Semester"></div>
    <div class="col-md-6"><label class="form-label">Semester</label><select class="form-select" name="semester"><option ${data?.semester==='First'?'selected':''}>First</option><option ${data?.semester==='Second'?'selected':''}>Second</option></select></div>
    <div class="col-md-6"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" value="${data?.start_date||''}"></div>
    <div class="col-md-6"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date" value="${data?.end_date||''}"></div></div>`,
    async fd=>await apiCall('create_session',fd))}

    async function activateSession(id){Swal.fire({title:'Activate this session?',text:'The current active session will be deactivated.',icon:'question',showCancelButton:true,confirmButtonText:'Activate'}).then(async r=>{if(r.isConfirmed){const res=await apiCall('activate_session',{id});if(res.success){Swal.fire({icon:'success',title:'Session Activated!',timer:1500,showConfirmButton:false});loadSessions()}else Swal.fire('Error',res.error,'error')}})}

    // ============ AUDIT LOG ============
    async function loadAudit(){
        const d=await apiCall('audit_log',{},'GET');
        let h=`<div class="table-card"><div class="card-header"><span><i class="bi bi-clock-history me-2"></i>Audit Log</span></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" id="auditTable"><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead><tbody></tbody></table></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        const dt=$('#auditTable').DataTable({responsive:true,dom:'Bfrtip',buttons:[{extend:'csv',className:'btn btn-sm btn-outline-primary'},{extend:'print',className:'btn btn-sm btn-outline-primary'}],pageLength:25,order:[[0,'desc']]});
        dt.clear().rows.add(d.data.map(a=>[a.created_at,a.user_name||'-',`<span class="badge bg-primary bg-opacity-10 text-primary">${a.action}</span>`,(a.details||'').substring(0,80),a.ip_address||'-'])).draw();
    }

    // ============ SETTINGS ============
    async function loadSettings(){
        const d=await apiCall('settings',{},'GET');const s=d.data||{};
        let h=`<div class="row g-4"><div class="col-lg-6"><div class="card border-0 shadow-sm rounded-16" style="border-radius:16px"><div class="card-header bg-transparent border-bottom"><h6 class="mb-0"><i class="bi bi-building me-2"></i>Institution Information</h6></div><div class="card-body">
        <form id="institutionForm"><div class="mb-3"><label class="form-label">Institution Name</label><input type="text" class="form-control" name="institution_name" value="${s.institution_name||''}"></div>
        <div class="mb-3"><label class="form-label">Motto</label><input type="text" class="form-control" name="institution_motto" value="${s.institution_motto||''}"></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="institution_email" value="${s.institution_email||''}"></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="institution_phone" value="${s.institution_phone||''}"></div>
        <div class="mb-3"><label class="form-label">Website</label><input type="url" class="form-control" name="institution_website" value="${s.institution_website||''}"></div>
        <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="institution_address" rows="2">${s.institution_address||''}</textarea></div>
        <button type="button" class="btn btn-primary" onclick="saveSettings('institution')"><i class="bi bi-check-lg me-1"></i>Save Institution Info</button></form></div></div></div>`;
        h+=`<div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:16px"><div class="card-header bg-transparent border-bottom"><h6 class="mb-0"><i class="bi bi-gear me-2"></i>System Settings</h6></div><div class="card-body">
        <form id="sysForm"><div class="mb-3"><label class="form-label">Session Prefix</label><input type="text" class="form-control" name="session_prefix" value="${s.session_prefix||'SAZUG'}"></div>
        <div class="mb-3"><label class="form-label">Academic Year Format</label><input type="text" class="form-control" name="academic_year_format" value="${s.academic_year_format||'%Y/%Y'}"></div>
        <div class="mb-3"><label class="form-label">Currency Symbol</label><input type="text" class="form-control" name="currency_symbol" value="${s.currency_symbol||'₦'}"></div>
        <div class="mb-3"><label class="form-label">Max Upload Size (MB)</label><input type="number" class="form-control" name="max_upload_size_mb" min="1" max="50" value="${s.max_upload_size_mb||5}"></div>
        <div class="mb-3"><label class="form-label">Allowed File Types</label><input type="text" class="form-control" name="allowed_file_types" value="${s.allowed_file_types||'pdf,jpg,jpeg,png,doc,docx'}"></div>
        <div class="mb-3 form-check form-switch"><input class="form-check-input" type="checkbox" name="enable_certificate" id="enableCert" ${s.enable_certificate?'checked':''}><label class="form-check-label" for="enableCert">Enable Certificate Generation</label></div>
        <div class="mb-3 form-check form-switch"><input class="form-check-input" type="checkbox" name="enable_document_upload" id="enableDocs" ${s.enable_document_upload?'checked':''}><label class="form-check-label" for="enableDocs">Enable Document Upload</label></div>
        <button type="button" class="btn btn-primary" onclick="saveSettings('system')"><i class="bi bi-check-lg me-1"></i>Save System Settings</button></form></div></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
    }

    async function saveSettings(type){const fd={};if(type==='institution'){document.querySelectorAll('#institutionForm [name]').forEach(e=>fd[e.name]=e.value);const r=await apiCall('update_institution',fd)}
    else{document.querySelectorAll('#sysForm [name]').forEach(e=>fd[e.name]=e.type==='checkbox'?e.checked?1:0:e.value);const r=await apiCall('update_settings',fd)}
    if(r&&r.success)Swal.fire({icon:'success',title:'Saved!',timer:1000,showConfirmButton:false});else Swal.fire('Error',r?.error||'Failed','error')}
    </script>
</body>
</html>
