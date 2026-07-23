<?php
/**
 * SAZUG Student Record Management System — API Gateway v2.0
 * Central AJAX router for all SPA requests.
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once 'SystemCore.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

try {
    $core   = new SystemCore();
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $_GET['action'] ?? $input['action'] ?? $_POST['action'] ?? '';

    // CSRF enforcement (skip for login and public endpoints)
    $publicActions = ['login', 'forgot_password', 'reset_password', 'verify_certificate'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $publicActions)) {
        $headers   = getallheaders();
        $csrfToken = $headers['X-Csrf-Token'] ?? $headers['X-CSRF-Token'] ?? $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        if (!$core->verifyCSRFToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'CSRF Token Validation Failed. Please refresh and try again.']);
            exit;
        }
    }

    $response = ['status' => false, 'message' => 'Invalid action.'];

    switch ($action) {

        /* ---- AUTH ---- */
        case 'login':
            $response = $core->login($input['username'] ?? '', $input['password'] ?? '');
            break;

        case 'logout':
            $response = $core->logout();
            break;

        case 'forgot_password':
            $response = $core->forgotPassword($input['username'] ?? '');
            break;

        case 'reset_password':
            $response = $core->resetPassword($input['token'] ?? '', $input['password'] ?? '');
            break;

        case 'change_password':
            if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(json_encode(['status'=>false,'message'=>'Unauthorized'])); }
            $response = $core->changePassword((int)$_SESSION['user_id'], $input['current_password'] ?? '', $input['new_password'] ?? '');
            break;

        case 'get_csrf':
            $response = ['status' => true, 'token' => $core->generateCSRFToken()];
            break;

        /* ---- DASHBOARD ---- */
        case 'dashboard_stats':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer']);
            $response = ['status' => true, 'data' => $core->getDashboardStats()];
            break;

        /* ---- STUDENTS ---- */
        case 'get_students':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer']);
            $filters = [];
            if (!empty($_GET['department_id'])) $filters['department_id'] = (int)$_GET['department_id'];
            if (!empty($_GET['faculty_id']))    $filters['faculty_id']    = (int)$_GET['faculty_id'];
            if (!empty($_GET['status']))        $filters['status']        = $_GET['status'];
            if (!empty($_GET['level']))         $filters['level']         = (int)$_GET['level'];
            if (!empty($_GET['search']))        $filters['search']        = $_GET['search'];
            // POST body filters
            if (!empty($input['department_id'])) $filters['department_id'] = (int)$input['department_id'];
            if (!empty($input['status']))         $filters['status']        = $input['status'];
            $response = ['status' => true, 'data' => $core->getStudents($filters)];
            break;

        case 'get_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer']);
            $student = $core->getStudentById((int)($_GET['id'] ?? $input['id'] ?? 0));
            $response = $student
                ? ['status' => true, 'data' => $student]
                : ['status' => false, 'message' => 'Student not found.'];
            break;

        case 'get_my_profile':
            if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(json_encode(['status'=>false,'message'=>'Unauthorized'])); }
            $student = $core->getStudentByUserId((int)$_SESSION['user_id']);
            $response = $student
                ? ['status' => true, 'data' => $student]
                : ['status' => false, 'message' => 'Profile not found.'];
            break;

        case 'create_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $studentData = [
                'admission_number' => trim($input['admission_number'] ?? ''),
                'matric_number'    => !empty($input['matric_number']) ? trim($input['matric_number']) : null,
                'full_name'        => trim($input['full_name'] ?? ''),
                'dob'              => $input['dob'] ?? '',
                'gender'           => $input['gender'] ?? '',
                'phone'            => $input['phone'] ?? null,
                'address'          => $input['address'] ?? null,
                'department_id'    => (int)($input['department_id'] ?? 0),
                'faculty_id'       => (int)($input['faculty_id'] ?? 0),
                'programme_id'     => (int)($input['programme_id'] ?? 0),
                'level'            => (int)($input['level'] ?? 100),
                'session_id'       => (int)($input['session_id'] ?? 0),
                'state'            => $input['state'] ?? 'Not Set',
                'lga'              => $input['lga'] ?? null,
                'nationality'      => $input['nationality'] ?? 'Nigerian',
                'guardian_name'    => $input['guardian_name'] ?? null,
                'guardian_phone'   => $input['guardian_phone'] ?? null,
                'guardian_address' => $input['guardian_address'] ?? null,
                'admission_date'   => $input['admission_date'] ?? date('Y-m-d'),
            ];
            $userData = ['password' => !empty($input['phone']) ? $input['phone'] : 'Student@123'];
            $response = $core->createStudent($userData, $studentData);
            break;

        case 'update_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) { $response = ['status'=>false,'message'=>'Student ID required.']; break; }
            $fields = ['matric_number','full_name','dob','gender','phone','address','department_id',
                       'faculty_id','programme_id','level','session_id','state','lga','nationality',
                       'guardian_name','guardian_phone','guardian_address'];
            $data = [];
            foreach ($fields as $f) {
                if (array_key_exists($f, $input)) $data[$f] = $input[$f] ?: null;
            }
            $response = $core->updateStudent($id, $data);
            break;

        case 'update_student_status':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $response = $core->updateStudentStatus((int)($input['id'] ?? 0), $input['status'] ?? '');
            break;

        case 'delete_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = (int)($input['id'] ?? 0);
            $success = $core->delete('students', $id);
            if ($success) {
                $core->logAudit('Student Deleted', 'students', $id);
            }
            $response = ['status' => $success, 'message' => $success ? 'Student deleted.' : 'Failed to delete.'];
            break;

        /* ---- STAFF ---- */
        case 'get_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = ['status' => true, 'data' => $core->getStaff()];
            break;

        case 'create_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = $core->createStaff($input);
            break;

        case 'update_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = (int)($input['id'] ?? 0);
            unset($input['id'], $input['action'], $input['csrf_token']);
            $response = $core->updateStaff($id, $input);
            break;

        case 'delete_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $staff = $core->fetchOne('staff', (int)($input['id'] ?? 0));
            $success = false;
            if ($staff) {
                $core->delete('staff', (int)$staff['id']);
                $success = $core->delete('users', (int)$staff['user_id']);
                $core->logAudit('Staff Deleted', 'staff', (int)$staff['id']);
            }
            $response = ['status' => $success, 'message' => $success ? 'Staff deleted.' : 'Failed.'];
            break;

        /* ---- ACADEMICS: FACULTIES ---- */
        case 'get_faculties':
            $response = ['status' => true, 'data' => $core->getFaculties()];
            break;

        case 'create_faculty':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $id = $core->insert('faculties', [
                'name'        => trim($input['name'] ?? ''),
                'code'        => strtoupper(trim($input['code'] ?? '')),
                'hod_name'    => $input['hod_name'] ?? null,
                'description' => $input['description'] ?? null,
            ]);
            $core->logAudit('Faculty Created', 'faculties', $id, $input['name'] ?? '');
            $response = ['status' => true, 'message' => 'Faculty created.', 'id' => $id];
            break;

        case 'update_faculty':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $success = $core->update('faculties', (int)($input['id'] ?? 0), [
                'name'        => trim($input['name'] ?? ''),
                'code'        => strtoupper(trim($input['code'] ?? '')),
                'hod_name'    => $input['hod_name'] ?? null,
                'description' => $input['description'] ?? null,
            ]);
            $response = ['status' => $success, 'message' => $success ? 'Faculty updated.' : 'Failed.'];
            break;

        case 'delete_faculty':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $success = $core->delete('faculties', (int)($input['id'] ?? 0));
            $response = ['status' => $success, 'message' => $success ? 'Faculty deleted.' : 'Failed.'];
            break;

        /* ---- ACADEMICS: DEPARTMENTS ---- */
        case 'get_departments':
            $fid = !empty($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : (!empty($input['faculty_id']) ? (int)$input['faculty_id'] : null);
            $response = ['status' => true, 'data' => $core->getDepartments($fid)];
            break;

        case 'create_department':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $id = $core->insert('departments', [
                'faculty_id'  => (int)($input['faculty_id'] ?? 0),
                'name'        => trim($input['name'] ?? ''),
                'code'        => strtoupper(trim($input['code'] ?? '')),
                'hod_name'    => $input['hod_name'] ?? null,
                'description' => $input['description'] ?? null,
            ]);
            $core->logAudit('Department Created', 'departments', $id, $input['name'] ?? '');
            $response = ['status' => true, 'message' => 'Department created.', 'id' => $id];
            break;

        case 'update_department':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $success = $core->update('departments', (int)($input['id'] ?? 0), [
                'faculty_id'  => (int)($input['faculty_id'] ?? 0),
                'name'        => trim($input['name'] ?? ''),
                'code'        => strtoupper(trim($input['code'] ?? '')),
                'hod_name'    => $input['hod_name'] ?? null,
            ]);
            $response = ['status' => $success, 'message' => $success ? 'Department updated.' : 'Failed.'];
            break;

        case 'delete_department':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $success = $core->delete('departments', (int)($input['id'] ?? 0));
            $response = ['status' => $success, 'message' => $success ? 'Department deleted.' : 'Failed.'];
            break;

        /* ---- ACADEMICS: PROGRAMMES ---- */
        case 'get_programmes':
            $did = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : (!empty($input['department_id']) ? (int)$input['department_id'] : null);
            $response = ['status' => true, 'data' => $core->getProgrammes($did)];
            break;

        case 'create_programme':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $id = $core->insert('programmes', [
                'department_id'  => (int)($input['department_id'] ?? 0),
                'name'           => trim($input['name'] ?? ''),
                'type'           => $input['type'] ?? 'ND',
                'duration_years' => (int)($input['duration_years'] ?? 2),
            ]);
            $response = ['status' => true, 'message' => 'Programme created.', 'id' => $id];
            break;

        case 'delete_programme':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $success = $core->delete('programmes', (int)($input['id'] ?? 0));
            $response = ['status' => $success, 'message' => $success ? 'Programme deleted.' : 'Failed.'];
            break;

        /* ---- ACADEMICS: COURSES ---- */
        case 'get_courses':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer']);
            $filters = [];
            if (!empty($_GET['department_id'])) $filters['department_id'] = (int)$_GET['department_id'];
            if (!empty($input['department_id'])) $filters['department_id'] = (int)$input['department_id'];
            $response = ['status' => true, 'data' => $core->getCourses($filters)];
            break;

        case 'create_course':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $id = $core->insert('courses', [
                'programme_id'  => (int)($input['programme_id'] ?? 0),
                'department_id' => (int)($input['department_id'] ?? 0),
                'code'          => strtoupper(trim($input['code'] ?? '')),
                'title'         => trim($input['title'] ?? ''),
                'credit_units'  => (int)($input['credit_units'] ?? 3),
                'level'         => (int)($input['level'] ?? 100),
                'semester'      => $input['semester'] ?? 'First',
                'is_compulsory' => !empty($input['is_compulsory']) ? 1 : 0,
            ]);
            $core->logAudit('Course Created', 'courses', $id, $input['code'] ?? '');
            $response = ['status' => true, 'message' => 'Course created.', 'id' => $id];
            break;

        case 'delete_course':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $success = $core->delete('courses', (int)($input['id'] ?? 0));
            $response = ['status' => $success, 'message' => $success ? 'Course deleted.' : 'Failed.'];
            break;

        /* ---- SESSIONS ---- */
        case 'get_sessions':
            $response = ['status' => true, 'data' => $core->getSessions()];
            break;

        case 'create_session':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('sessions', [
                'name'       => trim($input['name'] ?? ''),
                'semester'   => $input['semester'] ?? 'First',
                'is_active'  => 0,
                'start_date' => $input['start_date'] ?? null,
                'end_date'   => $input['end_date'] ?? null,
            ]);
            $response = ['status' => true, 'message' => 'Session created.', 'id' => $id];
            break;

        case 'set_active_session':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = $core->setActiveSession((int)($input['id'] ?? 0));
            break;

        /* ---- CERTIFICATES ---- */
        case 'generate_certificate':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $response = $core->generateGraduationCertificate((int)($input['student_id'] ?? 0));
            break;

        case 'verify_certificate':
            $certNum = trim($_GET['cert'] ?? $input['cert'] ?? '');
            $data    = $core->verifyCertificate($certNum);
            $response = $data
                ? ['status' => true, 'data' => $data]
                : ['status' => false, 'message' => 'Certificate not found or invalid.'];
            break;

        /* ---- DOCUMENTS ---- */
        case 'upload_document':
            if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(json_encode(['status'=>false,'message'=>'Unauthorized'])); }
            if (isset($_FILES['document']) && isset($_POST['student_id'])) {
                $response = $core->uploadDocument((int)$_POST['student_id'], $_FILES['document'], $_POST['doc_type'] ?? 'Other');
            } else {
                $response = ['status' => false, 'message' => 'No file uploaded or student ID missing.'];
            }
            break;

        case 'get_documents':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer']);
            $sid  = (int)($_GET['student_id'] ?? $input['student_id'] ?? 0);
            $docs = $core->fetchAll('documents', ['student_id' => $sid], 'created_at DESC');
            $response = ['status' => true, 'data' => $docs];
            break;

        /* ---- AUDIT ---- */
        case 'get_audit_logs':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = ['status' => true, 'data' => $core->getAuditLogs(100)];
            break;

        /* ---- USER MANAGEMENT ---- */
        case 'get_system_users':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = ['status' => true, 'data' => $core->getSystemUsers()];
            break;

        case 'toggle_user_status':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = $core->toggleUserStatus((int)($input['user_id'] ?? 0), $input['status'] ?? 'Active');
            break;

        default:
            http_response_code(404);
            $response = ['status' => false, 'message' => "API endpoint '$action' not found."];
            break;
    }

    echo json_encode($response);

} catch (Throwable $e) {
    error_log('SAZUG API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => (getenv('APP_ENV') === 'production')
            ? 'An internal server error occurred.'
            : $e->getMessage()
    ]);
}
