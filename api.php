<?php
/**
 * SAZUG Student Record Management System - API Gateway
 * Central router for all SPA AJAX requests.
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once 'SystemCore.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $core = new SystemCore();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $_GET['action'] ?? $input['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'login') {
        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? $input['csrf_token'] ?? '';
        if (!$core->verifyCSRFToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'CSRF Token Validation Failed.']);
            exit;
        }
    }

    $response = ['status' => false, 'message' => 'Invalid action requested.'];

    switch ($action) {
        case 'login':
            $response = $core->login($input['username'] ?? '', $input['password'] ?? '');
            break;

        case 'logout':
            $response = $core->logout();
            break;

        case 'dashboard_stats':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer']);
            $response = ['status' => true, 'data' => $core->getDashboardStats()];
            break;

        // --- STUDENTS ---
        case 'get_students':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer']);
            $filters = [];
            if (!empty($_GET['department_id'])) $filters['department_id'] = (int)$_GET['department_id'];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            $response = ['status' => true, 'data' => $core->getStudents($filters)];
            break;

        case 'create_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $userData = [
                'username' => $input['admission_number'],
                'password' => $input['phone']
            ];
            $studentData = [
                'admission_number' => $input['admission_number'],
                'matric_number' => $input['matric_number'] ?? null,
                'full_name' => $input['full_name'],
                'dob' => $input['dob'],
                'gender' => $input['gender'],
                'phone' => $input['phone'],
                'address' => $input['address'] ?? '',
                'department_id' => $input['department_id'],
                'faculty_id' => $input['faculty_id'],
                'programme_id' => $input['programme_id'],
                'level' => $input['level'],
                'session_id' => $input['session_id'],
                'state' => $input['state'],
                'lga' => $input['lga'],
                'nationality' => $input['nationality'] ?? 'Nigerian'
            ];
            $response = $core->createStudent($userData, $studentData);
            break;

        case 'update_student_status':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);
            $success = $core->update('students', (int)$input['id'], ['status' => $input['status']]);
            $response = ['status' => $success, 'message' => $success ? 'Status updated successfully.' : 'Failed to update.'];
            break;

        case 'delete_student':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $success = $core->delete('students', (int)$input['id']);
            $response = ['status' => $success, 'message' => $success ? 'Student deleted.' : 'Failed to delete.'];
            break;

        // --- FACULTIES ---
        case 'get_faculties':
            $response = ['status' => true, 'data' => $core->fetchAll('faculties', [], 'name ASC')];
            break;
        case 'create_faculty':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('faculties', ['name' => $input['name'], 'code' => $input['code']]);
            $response = ['status' => true, 'message' => 'Faculty created.', 'id' => $id];
            break;

        // --- DEPARTMENTS ---
        case 'get_departments':
            $response = ['status' => true, 'data' => $core->fetchAll('departments', [], 'name ASC')];
            break;
        case 'create_department':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('departments', ['faculty_id' => $input['faculty_id'], 'name' => $input['name'], 'code' => $input['code']]);
            $response = ['status' => true, 'message' => 'Department created.', 'id' => $id];
            break;

        // --- PROGRAMMES ---
        case 'get_programmes':
            $response = ['status' => true, 'data' => $core->fetchAll('programmes', [], 'name ASC')];
            break;
        case 'create_programme':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('programmes', ['department_id' => $input['department_id'], 'name' => $input['name'], 'type' => $input['type']]);
            $response = ['status' => true, 'message' => 'Programme created.', 'id' => $id];
            break;

        // --- SESSIONS ---
        case 'get_sessions':
            $response = ['status' => true, 'data' => $core->fetchAll('sessions', [], 'id DESC')];
            break;
        case 'create_session':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('sessions', ['name' => $input['name'], 'semester' => $input['semester'], 'is_active' => $input['is_active'] ?? 0]);
            $response = ['status' => true, 'message' => 'Session created.', 'id' => $id];
            break;

        // --- COURSES ---
        case 'get_courses':
            $response = ['status' => true, 'data' => $core->fetchAll('courses', [], 'course_code ASC')];
            break;
        case 'create_course':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('courses', [
                'department_id' => $input['department_id'],
                'programme_id' => $input['programme_id'],
                'course_code' => $input['course_code'],
                'title' => $input['title'],
                'units' => $input['units'],
                'semester' => $input['semester']
            ]);
            $response = ['status' => true, 'message' => 'Course created.', 'id' => $id];
            break;

        // --- STAFF ---
        case 'get_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $response = ['status' => true, 'data' => $core->fetchAll('staff', [], 'full_name ASC')];
            break;
        case 'create_staff':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $userId = $core->insert('users', [
                'username' => $input['username'],
                'password_hash' => password_hash($input['password'], PASSWORD_BCRYPT),
                'role' => $input['role']
            ]);
            $staffId = $core->insert('staff', [
                'user_id' => $userId,
                'full_name' => $input['full_name'],
                'phone' => $input['phone'] ?? ''
            ]);
            $response = ['status' => true, 'message' => 'Staff created successfully.', 'id' => $staffId];
            break;

        // --- CERTIFICATES ---
        case 'generate_certificate':
            $response = $core->generateGraduationCertificate((int)$input['student_id']);
            break;
            
        // --- DOCUMENTS ---
        case 'upload_document':
            if (isset($_FILES['document']) && isset($_POST['student_id'])) {
                $response = $core->uploadDocument((int)$_POST['student_id'], $_FILES['document'], $_POST['doc_type']);
            } else {
                $response = ['status' => false, 'message' => 'No file uploaded.'];
            }
            break;

        default:
            $response = ['status' => false, 'message' => 'API endpoint not found.'];
            break;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
