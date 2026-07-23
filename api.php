<?php
/**
 * SAZUG Student Record Management System - API Gateway
 * Central router for all SPA AJAX requests.
 */

// Autoload composer dependencies if they exist (for mPDF and QR)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once 'SystemCore.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $core = new SystemCore();
    
    // Parse input (Handle both JSON payloads and Form Data)
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $_GET['action'] ?? $input['action'] ?? '';

    // Enforce CSRF protection on state-changing requests
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
        // --- AUTHENTICATION ---
        case 'login':
            $response = $core->login($input['username'] ?? '', $input['password'] ?? '');
            break;

        case 'logout':
            $response = $core->logout();
            break;

        // --- DASHBOARD ---
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
                'password' => $input['phone'] // Default password is phone number
            ];
            $studentData = [
                'admission_number' => $input['admission_number'],
                'matric_number' => $input['matric_number'] ?? null,
                'full_name' => $input['full_name'],
                'dob' => $input['dob'],
                'gender' => $input['gender'],
                'phone' => $input['phone'],
                'address' => $input['address'],
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
            $response = ['status' => $success, 'message' => $success ? 'Status updated.' : 'Failed to update.'];
            break;

        // --- DEPARTMENTS ---
        case 'get_departments':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer']);
            $response = ['status' => true, 'data' => $core->fetchAll('departments', [], 'name ASC')];
            break;
            
        case 'create_department':
            $core->enforcePermissions(['Super Administrator', 'Administrator']);
            $id = $core->insert('departments', [
                'faculty_id' => $input['faculty_id'],
                'name' => $input['name'],
                'code' => $input['code']
            ]);
            $response = ['status' => true, 'message' => 'Department created.', 'id' => $id];
            break;

        // --- FACULTIES ---
        case 'get_faculties':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Department Officer']);
            $response = ['status' => true, 'data' => $core->fetchAll('faculties', [], 'name ASC')];
            break;

        // --- CERTIFICATES ---
        case 'generate_certificate':
            $response = $core->generateGraduationCertificate((int)$input['student_id']);
            break;
            
        // --- DOCUMENTS ---
        case 'upload_document':
            $core->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar', 'Student']);
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