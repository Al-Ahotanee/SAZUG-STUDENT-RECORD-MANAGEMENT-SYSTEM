<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SAZUG SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root{--sidebar-w:260px;--primary:#1a365d;--primary-light:#2a4a7f;--primary-dark:#0f2440;--gold:#c9973f;--gold-light:#daa84e;--sidebar-bg:linear-gradient(180deg,#0d1b2a 0%,#1a365d 100%)}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f0f2f5;overflow-x:hidden}
        [data-bs-theme="dark"] body{background:#0a1628;color:#e0e0e0}
        [data-bs-theme="dark"] .card{background:#152238;border-color:rgba(255,255,255,.08)}
        [data-bs-theme="dark"] .table{color:#e0e0e0}
        [data-bs-theme="dark"] .table td,[data-bs-theme="dark"] .table th{background:#152238;border-color:rgba(255,255,255,.06)}
        [data-bs-theme="dark"] .topbar{background:#0d1b2a;border-color:rgba(255,255,255,.08)}
        [data-bs-theme="dark"] .form-control,[data-bs-theme="dark"] .form-select{background:#1a2a3d;color:#e0e0e0;border-color:rgba(255,255,255,.15)}
        [data-bs-theme="dark"] .document-item{background:#1a2a3d;border-color:rgba(255,255,255,.08)}
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
        .profile-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
        .profile-card .profile-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));padding:2rem;text-align:center;color:#fff;position:relative}
        .profile-card .profile-header .avatar{width:90px;height:90px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700;margin:0 auto 1rem;border:4px solid rgba(255,255,255,.3)}
        .profile-card .profile-header .status-badge{position:absolute;top:1rem;right:1rem}
        .profile-card .profile-body{padding:1.5rem}
        .info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem}
        .info-item{padding:.8rem;border-radius:10px;background:#f8f9fa}
        [data-bs-theme="dark"] .info-item{background:#1a2a3d}
        .info-item .label{font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
        [data-bs-theme="dark"] .info-item .label{color:#aaa}
        .info-item .value{font-size:.95rem;font-weight:600;color:var(--primary);margin-top:.2rem}
        [data-bs-theme="dark"] .info-item .value{color:var(--gold)}
        .document-item{background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;transition:all .2s}
        .document-item:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
        .document-item .doc-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
        .doc-pdf{background:#fee2e2;color:#dc2626}.doc-img{background:#dbeafe;color:#2563eb}.doc-doc{background:#f3f4f6;color:#374151}
        .upload-zone{border:2px dashed #d0d5dd;border-radius:16px;padding:2rem;text-align:center;cursor:pointer;transition:all .3s;background:#fafafa}
        [data-bs-theme="dark"] .upload-zone{background:#1a2a3d;border-color:rgba(255,255,255,.15)}
        .upload-zone:hover,.upload-zone.dragover{border-color:var(--gold);background:rgba(201,151,63,.03)}
        .upload-zone i{font-size:2.5rem;color:var(--gold);margin-bottom:.5rem}
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
            <div class="sidebar-link" data-page="documents"><i class="bi bi-file-earmark-text"></i>My Documents</div>
            <div class="sidebar-link" data-page="profile"><i class="bi bi-person-circle"></i>My Profile</div>
        </nav>
        <div class="sidebar-user" id="sidebarUser">
            <div class="sidebar-avatar" id="avatarInitial">S</div>
            <div class="sidebar-user-info">
                <div class="name" id="userName">Student</div>
                <div class="role" id="userRole">student</div>
            </div>
            <button class="btn btn-sm p-0 text-white-50" onclick="logout()" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
                <nav aria-label="breadcrumb"><ol class="breadcrumb breadcrumb-custom" id="breadcrumb"><li class="breadcrumb-item"><a href="#">Student</a></li><li class="breadcrumb-item active" id="breadcrumbPage">Dashboard</li></ol></nav>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle-sm" onclick="toggleTheme()" title="Toggle Theme"><i class="bi bi-moon-stars-fill" id="themeIcon"></i></button>
                <button class="btn btn-outline-danger btn-sm" onclick="logout()"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </div>
        </div>
        <div class="page-content" id="pageContent"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const API='api.php';let TOKEN='',USER=null,currentPage='dashboard',studentData=null;
    (function(){TOKEN=localStorage.getItem('sazug_token')||'';const u=localStorage.getItem('sazug_user');if(u)USER=JSON.parse(u);if(!TOKEN||!USER||USER.role!=='student'){window.location.href='index.php';return}
    document.getElementById('userName').textContent=USER.full_name||'Student';document.getElementById('userRole').textContent='student';document.getElementById('avatarInitial').textContent=(USER.full_name||'S').charAt(0).toUpperCase();
    const t=localStorage.getItem('theme');if(t){document.documentElement.setAttribute('data-bs-theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    loadPage('dashboard')})();

    function toggleTheme(){const t=document.documentElement.getAttribute('data-bs-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',t);localStorage.setItem('theme',t);document.getElementById('themeIcon').className=t==='dark'?'bi bi-sun-fill':'bi bi-moon-stars-fill'}
    function logout(){Swal.fire({title:'Logout?',icon:'question',showCancelButton:true,confirmButtonText:'Yes',confirmButtonColor:'#dc3545'}).then(r=>{if(r.isConfirmed){localStorage.removeItem('sazug_token');localStorage.removeItem('sazug_user');window.location.href='index.php'}})}
    async function apiCall(action,data={},method='POST'){const headers={'Content-Type':'application/json','Authorization':'Bearer '+TOKEN};if(method==='GET'){const p=new URLSearchParams({action,...data});const r=await fetch(API+'?'+p,{headers});return r.json()}const r=await fetch(API,{method:'POST',headers,body:JSON.stringify({action,...data})});return r.json()}

    document.querySelectorAll('.sidebar-link[data-page]').forEach(link=>{link.addEventListener('click',function(){document.querySelectorAll('.sidebar-link').forEach(l=>l.classList.remove('active'));this.classList.add('active');loadPage(this.dataset.page);if(window.innerWidth<992)document.getElementById('sidebar').classList.remove('show')})});

    async function loadPage(page){currentPage=page;document.getElementById('breadcrumbPage').textContent=page.charAt(0).toUpperCase()+page.slice(1);
    if(page==='dashboard')loadDashboard();else if(page==='documents')loadDocuments();else if(page==='profile')loadProfile()}

    // Dashboard
    async function loadDashboard(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        const r=await apiCall('student_profile',{},'GET');if(!r.success)return;
        studentData=r.data;const s=studentData;
        let h=`<div class="profile-card mb-4"><div class="profile-header">
        <span class="status-badge"><span class="badge ${s.status==='active'?'bg-success':s.status==='graduated'?'bg-primary':s.status==='suspended'?'bg-warning':'bg-danger'}">${s.status.toUpperCase()}</span></span>
        <div class="avatar">${(s.full_name||'S').charAt(0).toUpperCase()}</div>
        <h4 class="mb-1">${s.full_name||'Student'}</h4>
        <p class="mb-0 opacity-75">${s.student_id_number||''}</p>
        <p class="mb-0 opacity-50 small">${s.programme_name?s.programme_type_name+' in '+s.programme_name:''}</p></div>
        <div class="profile-body"><div class="info-grid">
        <div class="info-item"><div class="label">Student ID</div><div class="value">${s.student_id_number||'-'}</div></div>
        <div class="info-item"><div class="label">Faculty</div><div class="value">${s.faculty_name||'-'}</div></div>
        <div class="info-item"><div class="label">Department</div><div class="value">${s.department_name||'-'}</div></div>
        <div class="info-item"><div class="label">Programme</div><div class="value">${s.programme_name||'-'}</div></div>
        <div class="info-item"><div class="label">Level</div><div class="value">${s.level||'-'}</div></div>
        <div class="info-item"><div class="label">Type</div><div class="value">${s.programme_type||'-'}</div></div>
        <div class="info-item"><div class="label">Session</div><div class="value">${s.session_name||'-'}</div></div>
        <div class="info-item"><div class="label">CGPA</div><div class="value">${parseFloat(s.cgpa||0).toFixed(2)} / 5.00</div></div>
        <div class="info-item"><div class="label">Gender</div><div class="value">${s.gender||'-'}</div></div>
        <div class="info-item"><div class="label">Enrollment Date</div><div class="value">${s.enrollment_date||'-'}</div></div>
        <div class="info-item"><div class="label">Expected Graduation</div><div class="value">${s.expected_graduation||'-'}</div></div>
        <div class="info-item"><div class="label">Email</div><div class="value">${s.email||'-'}</div></div>
        </div>`;
        if(s.status==='graduated'&&s.certificate_number){
        h+=`<div class="mt-4 text-center"><a href="api.php?action=download_certificate&certificate_number=${s.certificate_number}" class="btn btn-gold btn-lg px-5" style="background:linear-gradient(135deg,var(--gold),var(--gold-light));color:#fff;font-weight:700;border-radius:12px;text-decoration:none"><i class="bi bi-download me-2"></i>Download Certificate</a><p class="mt-2 small text-muted">Certificate No: ${s.certificate_number}</p></div>`}
        h+=`</div></div>`;
        document.getElementById('pageContent').innerHTML=h;
    }

    // Documents
    async function loadDocuments(){
        document.getElementById('pageContent').innerHTML='<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        const r=await apiCall('student_documents',{},'GET');
        let h=`<div class="row g-4"><div class="col-md-5"><div class="card border-0 shadow-sm" style="border-radius:16px"><div class="card-header bg-transparent"><h6 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Upload Document</h6></div><div class="card-body">
        <div class="mb-3"><label class="form-label fw-semibold">Document Type</label><select class="form-select" id="docType">
        <option value="O'Level Result">O'Level Result</option><option value="JAMB Result">JAMB Result</option><option value="Birth Certificate">Birth Certificate</option>
        <option value="National ID">National ID</option><option value="Passport Photograph">Passport Photograph</option><option value="Admission Letter">Admission Letter</option>
        <option value="Transcript">Transcript</option><option value="Medical Certificate">Medical Certificate</option><option value="Other">Other</option></select></div>
        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('docFile').click()">
        <i class="bi bi-cloud-arrow-up d-block"></i>
        <p class="mb-1 fw-semibold">Click or drag file to upload</p>
        <small class="text-muted">PDF, JPG, PNG, DOC (Max 5MB)</small>
        <input type="file" id="docFile" style="display:none" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="handleFileSelect(this)">
        </div>
        <div id="filePreview" class="mt-3" style="display:none"><div class="d-flex align-items-center gap-2 p-2 bg-light rounded" style="background:#f8f9fa;border-radius:8px"><i class="bi bi-file-earmark text-primary"></i><span class="flex-grow-1 small" id="fileName"></span><button class="btn btn-sm text-danger p-0" onclick="clearFile()"><i class="bi bi-x"></i></button></div></div>
        <button class="btn btn-primary w-100 mt-3 py-2" onclick="uploadDocument()" id="uploadBtn"><i class="bi bi-upload me-2"></i>Upload Document</button>
        </div></div></div>`;
        h+=`<div class="col-md-7"><div class="card border-0 shadow-sm" style="border-radius:16px"><div class="card-header bg-transparent d-flex justify-content-between align-items-center"><h6 class="mb-0"><i class="bi bi-folder2-open me-2"></i>My Documents</h6><span class="badge bg-primary">${(r.data||[]).length} files</span></div><div class="card-body" id="docsList">`;
        if(r.data&&r.data.length>0){r.data.forEach(d=>{const ext=d.file_name.split('.').pop().toLowerCase();const iconClass=ext==='pdf'?'doc-pdf bi-file-earmark-pdf':ext==='jpg'||ext==='jpeg'||ext==='png'?'doc-img bi-file-earmark-image':'doc-doc bi-file-earmark-word';
        h+=`<div class="document-item"><div class="d-flex align-items-center gap-3"><div class="doc-icon ${iconClass}"><i class="bi"></i></div><div><div class="fw-semibold" style="font-size:.9rem">${d.file_name}</div><div class="small text-muted">${d.document_type} &bull; ${d.file_size_formatted} &bull; ${new Date(d.uploaded_at).toLocaleDateString()}</div></div></div>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteDoc(${d.id})" title="Delete"><i class="bi bi-trash"></i></button></div>`})}
        else{h+=`<div class="text-center py-4 text-muted"><i class="bi bi-inbox d-block fs-1 mb-2"></i><p>No documents uploaded yet</p></div>`}
        h+=`</div></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
        // Drag and drop
        const zone=document.getElementById('uploadZone');
        zone.addEventListener('dragover',e=>{e.preventDefault();zone.classList.add('dragover')});
        zone.addEventListener('dragleave',()=>zone.classList.remove('dragover'));
        zone.addEventListener('drop',e=>{e.preventDefault();zone.classList.remove('dragover');if(e.dataTransfer.files.length)handleFileSelect({files:e.dataTransfer.files})});
    }

    function handleFileSelect(input){const file=input.files[0];if(!file)return;
    const ext=file.name.split('.').pop().toLowerCase();const allowed=['pdf','jpg','jpeg','png','doc','docx'];
    if(!allowed.includes(ext)){Swal.fire('Error','File type not allowed','error');return}
    if(file.size>5*1024*1024){Swal.fire('Error','File exceeds 5MB limit','error');return}
    document.getElementById('fileName').textContent=file.name+' ('+(file.size/1024).toFixed(1)+' KB)';
    document.getElementById('filePreview').style.display='block'}
    function clearFile(){document.getElementById('docFile').value='';document.getElementById('filePreview').style.display='none'}

    async function uploadDocument(){const file=document.getElementById('docFile').files[0];if(!file){Swal.fire('Error','Please select a file','error');return}
    const type=document.getElementById('docType').value;const fd=new FormData();fd.append('file',file);fd.append('document_type',type);
    const btn=document.getElementById('uploadBtn');btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split me-2"></i>Uploading...';
    try{const r=await fetch(API+'?action=upload_document',{method:'POST',headers:{'Authorization':'Bearer '+TOKEN},body:fd});
    const d=await r.json();if(d.success){Swal.fire({icon:'success',title:'Uploaded!',timer:1000,showConfirmButton:false});loadDocuments()}else Swal.fire('Error',d.error,'error')}
    catch(e){Swal.fire('Error','Upload failed','error')}btn.disabled=false;btn.innerHTML='<i class="bi bi-upload me-2"></i>Upload Document'}

    async function deleteDoc(id){Swal.fire({title:'Delete this document?',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc3545',confirmButtonText:'Delete'}).then(async r=>{if(r.isConfirmed){const res=await apiCall('delete_document',{id});if(res.success){Swal.fire({icon:'success',title:'Deleted!',timer:1000,showConfirmButton:false});loadDocuments()}else Swal.fire('Error',res.error,'error')}})}

    // Profile
    async function loadProfile(){
        if(!studentData){const r=await apiCall('student_profile',{},'GET');if(r.success)studentData=r.data}
        const s=studentData||{};
        let h=`<div class="row g-4"><div class="col-md-5"><div class="profile-card"><div class="profile-header"><div class="avatar">${(USER.full_name||'S').charAt(0).toUpperCase()}</div><h5 class="mb-1">${USER.full_name||'Student'}</h5><p class="mb-0 opacity-75">${USER.email||''}</p><p class="mb-0 opacity-50 small">${s.student_id_number||''}</p></div>
        <div class="profile-body"><h6 class="mb-3">Academic Info</h6>
        <div class="info-grid"><div class="info-item"><div class="label">Programme</div><div class="value">${s.programme_name||'-'}</div></div>
        <div class="info-item"><div class="label">Faculty</div><div class="value">${s.faculty_name||'-'}</div></div>
        <div class="info-item"><div class="label">Department</div><div class="value">${s.department_name||'-'}</div></div>
        <div class="info-item"><div class="label">Level</div><div class="value">${s.level||'-'}</div></div>
        <div class="info-item"><div class="label">CGPA</div><div class="value">${parseFloat(s.cgpa||0).toFixed(2)}</div></div>
        <div class="info-item"><div class="label">Status</div><div class="value"><span class="badge ${s.status==='active'?'bg-success':s.status==='graduated'?'bg-primary':'bg-warning'}">${s.status||'-'}</span></div></div></div></div></div></div>`;
        h+=`<div class="col-md-7"><div class="card border-0 shadow-sm" style="border-radius:16px"><div class="card-header bg-transparent"><h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Profile</h6></div><div class="card-body">
        <form id="profileForm"><div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" id="profName" value="${USER.full_name||''}"></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" id="profPhone" value="${USER.phone||s.phone||''}"></div>
        <div class="mb-3"><label class="form-label">Home Address</label><textarea class="form-control" id="profAddress" rows="2">${s.home_address||''}</textarea></div>
        <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Guardian Name</label><input type="text" class="form-control" id="profGuardian" value="${s.guardian_name||''}"></div>
        <div class="col-md-6"><label class="form-label">Guardian Phone</label><input type="text" class="form-control" id="profGuardianPhone" value="${s.guardian_phone||''}"></div></div>
        <button type="button" class="btn btn-primary" onclick="saveProfile()"><i class="bi bi-check-lg me-1"></i>Save Changes</button></form></div></div></div></div>`;
        document.getElementById('pageContent').innerHTML=h;
    }

    async function saveProfile(){const fd={full_name:document.getElementById('profName').value,phone:document.getElementById('profPhone').value,home_address:document.getElementById('profAddress').value,guardian_name:document.getElementById('profGuardian').value,guardian_phone:document.getElementById('profGuardianPhone').value};
    const r=await apiCall('update_profile',fd);if(r.success){Swal.fire({icon:'success',title:'Profile Updated!',timer:1500,showConfirmButton:false});USER.full_name=fd.full_name;USER.phone=fd.phone;localStorage.setItem('sazug_user',JSON.stringify(USER));document.getElementById('userName').textContent=fd.full_name;document.getElementById('avatarInitial').textContent=fd.full_name.charAt(0).toUpperCase();studentData=null}
    else Swal.fire('Error',r.error||'Failed','error')}
    </script>
</body>
</html>
