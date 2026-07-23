<?php
/**
 * SAZUG Student Record Management System - API
 * All endpoint handlers for the application
 */

require_once __DIR__ . '/SystemCore.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $core = SystemCore::getInstance();
    $db = $core->getDB();
    $input = $core->getInput();
    $action = $input['action'] ?? ($_GET['action'] ?? '');
    
    if (empty($action)) {
        $core->jsonResponse(['success' => false, 'error' => 'No action specified'], 400);
    }
    
    // Auth-free actions
    $publicActions = ['login', 'verify_certificate', 'lookup', 'reset_password_request', 'reset_password'];
    
    // Authenticate for non-public actions
    $currentUser = null;
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? ($input['token'] ?? null);
    if ($token && !in_array($action, $publicActions)) {
        $token = str_replace('Bearer ', '', $token);
        $currentUser = $core->authenticate($token);
    }
    
    switch ($action) {
        
        // ==================== AUTH ====================
        
        case 'login':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            if (empty($username) || empty($password)) {
                $core->jsonResponse(['success' => false, 'error' => 'Username and password required'], 400);
            }
            $result = $core->login($username, $password);
            if ($result) {
                $authToken = $core->createAuthToken($result['user']['id']);
                $result['token'] = $authToken;
                $core->jsonResponse(['success' => true, 'data' => $result]);
            }
            $core->jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
            break;
        
        case 'reset_password_request':
            $email = $input['email'] ?? '';
            if (empty($email)) {
                $core->jsonResponse(['success' => false, 'error' => 'Email required'], 400);
            }
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?")->execute([$token, $expires, $user['id']]);
                // In production, send email here
                $core->jsonResponse(['success' => true, 'message' => 'Password reset link sent to your email']);
            } else {
                $core->jsonResponse(['success' => true, 'message' => 'If the email exists, a reset link has been sent']);
            }
            break;
        
        case 'reset_password':
            $token = $input['token'] ?? '';
            $newPassword = $input['password'] ?? '';
            if (empty($token) || empty($newPassword)) {
                $core->jsonResponse(['success' => false, 'error' => 'Token and new password required'], 400);
            }
            $stmt = $db->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?")->execute([$hashed, $user['id']]);
                $core->jsonResponse(['success' => true, 'message' => 'Password reset successful']);
            }
            $core->jsonResponse(['success' => false, 'error' => 'Invalid or expired token'], 400);
            break;
        
        // ==================== DASHBOARD ====================
        
        case 'dashboard':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            
            $data = [];
            if (in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $data['total_students'] = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
                $data['active_students'] = (int)$db->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
                $data['graduated_students'] = (int)$db->query("SELECT COUNT(*) FROM students WHERE status = 'graduated'")->fetchColumn();
                $data['suspended_students'] = (int)$db->query("SELECT COUNT(*) FROM students WHERE status = 'suspended'")->fetchColumn();
                $data['total_faculties'] = (int)$db->query("SELECT COUNT(*) FROM faculties WHERE is_active = 1")->fetchColumn();
                $data['total_departments'] = (int)$db->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn();
                $data['total_programmes'] = (int)$db->query("SELECT COUNT(*) FROM programmes WHERE is_active = 1")->fetchColumn();
                $data['total_staff'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','lecturer')")->fetchColumn();
                $data['total_courses'] = (int)$db->query("SELECT COUNT(*) FROM courses WHERE is_active = 1")->fetchColumn();
                
                // Gender distribution
                $data['gender_distribution'] = [
                    'male' => (int)$db->query("SELECT COUNT(*) FROM students WHERE gender = 'Male'")->fetchColumn(),
                    'female' => (int)$db->query("SELECT COUNT(*) FROM students WHERE gender = 'Female'")->fetchColumn()
                ];
                
                // Programme type distribution
                $progTypes = $db->query("SELECT programme_type, COUNT(*) as cnt FROM students GROUP BY programme_type")->fetchAll();
                $data['programme_distribution'] = $progTypes;
                
                // Faculty distribution
                $facData = $db->query("SELECT f.name, COUNT(s.id) as cnt FROM faculties f LEFT JOIN students s ON f.id = s.faculty_id WHERE f.is_active = 1 GROUP BY f.id")->fetchAll();
                $data['faculty_distribution'] = $facData;
                
                // Recent activities
                $recent = $db->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10")->fetchAll();
                $data['recent_activities'] = $recent;
                
                // Monthly enrollment trend (last 12 months)
                $monthly = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetchAll();
                $data['enrollment_trend'] = $monthly;
                
            } elseif ($currentUser['role'] === 'lecturer') {
                $data['my_students'] = (int)$db->prepare("SELECT COUNT(DISTINCT s.id) FROM students s JOIN courses c ON s.department_id = c.department_id WHERE c.lecturer_id = ?")->fetchColumn([$currentUser['id']]);
                $data['my_courses'] = (int)$db->prepare("SELECT COUNT(*) FROM courses WHERE lecturer_id = ?")->fetchColumn([$currentUser['id']]);
                $data['total_students'] = $data['my_students'];
                $data['active_students'] = $data['my_students'];
                $data['graduated_students'] = 0;
                $data['total_faculties'] = 0;
                $data['total_departments'] = 0;
                $data['total_programmes'] = 0;
                $data['total_staff'] = 0;
                $data['total_courses'] = $data['my_courses'];
            } elseif ($currentUser['role'] === 'student') {
                $student = $db->prepare("SELECT s.*, f.name as faculty_name, d.name as department_name, p.name as programme_name FROM students s LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id WHERE s.user_id = ?");
                $student->execute([$currentUser['id']]);
                $data['student'] = $student->fetch();
                $data['total_students'] = 0;
                $data['active_students'] = 0;
                $data['graduated_students'] = 0;
                $data['total_faculties'] = 0;
                $data['total_departments'] = 0;
                $data['total_programmes'] = 0;
                $data['total_staff'] = 0;
                $data['total_courses'] = 0;
            }
            
            $core->jsonResponse(['success' => true, 'data' => $data]);
            break;
        
        // ==================== STUDENTS ====================
        
        case 'students':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $gender = $_GET['gender'] ?? '';
            $faculty = $_GET['faculty'] ?? '';
            $department = $_GET['department'] ?? '';
            $programme = $_GET['programme'] ?? '';
            $level = $_GET['level'] ?? '';
            $progType = $_GET['programme_type'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(10, (int)($_GET['limit'] ?? 25));
            $offset = ($page - 1) * $limit;
            
            $where = "1=1";
            $params = [];
            
            if ($search) {
                $where .= " AND (s.student_id_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
            }
            if ($status) { $where .= " AND s.status = ?"; $params[] = $status; }
            if ($gender) { $where .= " AND s.gender = ?"; $params[] = $gender; }
            if ($faculty) { $where .= " AND s.faculty_id = ?"; $params[] = $faculty; }
            if ($department) { $where .= " AND s.department_id = ?"; $params[] = $department; }
            if ($programme) { $where .= " AND s.programme_id = ?"; $params[] = $programme; }
            if ($level) { $where .= " AND s.level = ?"; $params[] = $level; }
            if ($progType) { $where .= " AND s.programme_type = ?"; $params[] = $progType; }
            
            $countStmt = $db->prepare("SELECT COUNT(*) FROM students s JOIN users u ON s.user_id = u.id WHERE $where");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT s.*, u.full_name, u.email, u.phone, f.name as faculty_name, f.code as faculty_code, d.name as department_name, d.code as department_code, p.name as programme_name, p.code as programme_code, ses.name as session_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id LEFT JOIN sessions ses ON s.session_id = ses.id WHERE $where ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $students = $stmt->fetchAll();
            
            $core->jsonResponse(['success' => true, 'data' => $students, 'total' => $total, 'page' => $page, 'limit' => $limit, 'pages' => ceil($total / $limit)]);
            break;
        
        case 'create_student':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $required = ['full_name', 'email', 'username', 'gender'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    $core->jsonResponse(['success' => false, 'error' => "$field is required"], 400);
                }
            }
            
            $db->beginTransaction();
            try {
                // Check uniqueness
                $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $check->execute([$input['email'], $input['username']]);
                if ($check->fetch()) {
                    $db->rollBack();
                    $core->jsonResponse(['success' => false, 'error' => 'Email or username already exists'], 400);
                }
                
                $password = $input['password'] ?? 'Student@123';
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'student')");
                $stmt->execute([$input['username'], $input['email'], $hashedPassword, $input['full_name'], $input['phone'] ?? '']);
                $userId = $db->lastInsertId();
                
                $studentIdNumber = $core->generateStudentId();
                
                $stmt = $db->prepare("INSERT INTO students (user_id, student_id_number, faculty_id, department_id, programme_id, session_id, level, programme_type, date_of_birth, gender, nationality, state_of_origin, lga, home_address, guardian_name, guardian_phone, enrollment_date, expected_graduation, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $userId,
                    $studentIdNumber,
                    $input['faculty_id'] ?? null,
                    $input['department_id'] ?? null,
                    $input['programme_id'] ?? null,
                    $input['session_id'] ?? null,
                    $input['level'] ?? 100,
                    $input['programme_type'] ?? 'Degree',
                    $input['date_of_birth'] ?? null,
                    $input['gender'],
                    $input['nationality'] ?? 'Nigerian',
                    $input['state_of_origin'] ?? null,
                    $input['lga'] ?? null,
                    $input['home_address'] ?? null,
                    $input['guardian_name'] ?? null,
                    $input['guardian_phone'] ?? null,
                    $input['enrollment_date'] ?? date('Y-m-d'),
                    $input['expected_graduation'] ?? null
                ]);
                
                $core->audit('Student Created', "Created student: {$input['full_name']} ({$studentIdNumber})", $currentUser['id'], $currentUser['full_name']);
                $db->commit();
                $core->jsonResponse(['success' => true, 'message' => 'Student created successfully', 'student_id_number' => $studentIdNumber, 'password' => $password]);
            } catch (Exception $e) {
                $db->rollBack();
                $core->jsonResponse(['success' => false, 'error' => 'Failed to create student: ' . $e->getMessage()], 500);
            }
            break;
        
        case 'update_student':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            if (empty($id)) $core->jsonResponse(['success' => false, 'error' => 'Student ID required'], 400);
            
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = (SELECT user_id FROM students WHERE id = ?)");
            $stmt->execute([$input['full_name'] ?? '', $input['email'] ?? '', $input['phone'] ?? '', $id]);
            
            $stmt = $db->prepare("UPDATE students SET faculty_id = ?, department_id = ?, programme_id = ?, session_id = ?, level = ?, programme_type = ?, date_of_birth = ?, gender = ?, nationality = ?, state_of_origin = ?, lga = ?, home_address = ?, guardian_name = ?, guardian_phone = ?, expected_graduation = ? WHERE id = ?");
            $stmt->execute([
                $input['faculty_id'] ?? null, $input['department_id'] ?? null, $input['programme_id'] ?? null,
                $input['session_id'] ?? null, $input['level'] ?? 100, $input['programme_type'] ?? 'Degree',
                $input['date_of_birth'] ?? null, $input['gender'] ?? 'Male', $input['nationality'] ?? 'Nigerian',
                $input['state_of_origin'] ?? null, $input['lga'] ?? null, $input['home_address'] ?? null,
                $input['guardian_name'] ?? null, $input['guardian_phone'] ?? null, $input['expected_graduation'] ?? null,
                $id
            ]);
            
            $core->audit('Student Updated', "Updated student ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Student updated successfully']);
            break;
        
        case 'delete_student':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            if (empty($id)) $core->jsonResponse(['success' => false, 'error' => 'Student ID required'], 400);
            
            $student = $db->prepare("SELECT user_id, student_id_number FROM students WHERE id = ?");
            $student->execute([$id]);
            $s = $student->fetch();
            
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM student_documents WHERE student_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$s['user_id']]);
                $core->audit('Student Deleted', "Deleted student: {$s['student_id_number']}", $currentUser['id'], $currentUser['full_name']);
                $db->commit();
                $core->jsonResponse(['success' => true, 'message' => 'Student deleted successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                $core->jsonResponse(['success' => false, 'error' => 'Failed to delete student'], 500);
            }
            break;
        
        case 'student_action':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $actionType = $input['type'] ?? '';
            if (empty($id) || empty($actionType)) {
                $core->jsonResponse(['success' => false, 'error' => 'ID and action type required'], 400);
            }
            
            $validActions = ['suspend', 'withdraw', 'reinstate', 'graduate', 'promote'];
            if (!in_array($actionType, $validActions)) {
                $core->jsonResponse(['success' => false, 'error' => 'Invalid action type'], 400);
            }
            
            $statusMap = ['suspend' => 'suspended', 'withdraw' => 'withdrawn', 'reinstate' => 'active', 'graduate' => 'graduated'];
            
            $db->beginTransaction();
            try {
                if ($actionType === 'graduate') {
                    $certNumber = $core->generateCertificateNumber();
                    $db->prepare("UPDATE students SET status = 'graduated', certificate_number = ?, certificate_issued_at = NOW() WHERE id = ?")->execute([$certNumber, $id]);
                    $core->audit('Student Graduated', "Graduated student ID: {$id}, Certificate: {$certNumber}", $currentUser['id'], $currentUser['full_name']);
                    $core->jsonResponse(['success' => true, 'message' => 'Student graduated. Certificate: ' . $certNumber, 'certificate_number' => $certNumber]);
                } elseif ($actionType === 'promote') {
                    $db->prepare("UPDATE students SET level = level + 100 WHERE id = ?")->execute([$id]);
                    $core->audit('Student Promoted', "Promoted student ID: {$id}", $currentUser['id'], $currentUser['full_name']);
                    $core->jsonResponse(['success' => true, 'message' => 'Student promoted to next level']);
                } else {
                    $newStatus = $statusMap[$actionType];
                    $db->prepare("UPDATE students SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
                    $core->audit('Student Status Changed', "Changed student {$id} to {$newStatus}", $currentUser['id'], $currentUser['full_name']);
                    $core->jsonResponse(['success' => true, 'message' => "Student status changed to {$newStatus}"]);
                }
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $core->jsonResponse(['success' => false, 'error' => 'Action failed: ' . $e->getMessage()], 500);
            }
            break;
        
        // ==================== FACULTIES ====================
        
        case 'faculties':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $search = $_GET['search'] ?? '';
            $where = "1=1";
            $params = [];
            if ($search) { $where .= " AND (name LIKE ? OR code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            $stmt = $db->prepare("SELECT f.*, (SELECT COUNT(*) FROM departments WHERE faculty_id = f.id) as dept_count, (SELECT COUNT(*) FROM students WHERE faculty_id = f.id) as student_count FROM faculties f WHERE $where ORDER BY f.name");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_faculty':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['name']) || empty($input['code'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Name and code required'], 400);
            }
            $check = $db->prepare("SELECT id FROM faculties WHERE code = ?");
            $check->execute([$input['code']]);
            if ($check->fetch()) $core->jsonResponse(['success' => false, 'error' => 'Faculty code already exists'], 400);
            
            $stmt = $db->prepare("INSERT INTO faculties (name, code, description, dean_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['name'], $input['code'], $input['description'] ?? '', $input['dean_name'] ?? '']);
            $core->audit('Faculty Created', "Created faculty: {$input['name']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Faculty created successfully']);
            break;
        
        case 'update_faculty':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $stmt = $db->prepare("UPDATE faculties SET name = ?, code = ?, description = ?, dean_name = ? WHERE id = ?");
            $stmt->execute([$input['name'] ?? '', $input['code'] ?? '', $input['description'] ?? '', $input['dean_name'] ?? '', $id]);
            $core->audit('Faculty Updated', "Updated faculty ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Faculty updated successfully']);
            break;
        
        case 'delete_faculty':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $db->prepare("DELETE FROM faculties WHERE id = ?")->execute([$id]);
            $core->audit('Faculty Deleted', "Deleted faculty ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Faculty deleted successfully']);
            break;
        
        // ==================== DEPARTMENTS ====================
        
        case 'departments':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $search = $_GET['search'] ?? '';
            $facultyId = $_GET['faculty_id'] ?? '';
            $where = "1=1";
            $params = [];
            if ($search) { $where .= " AND (d.name LIKE ? OR d.code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($facultyId) { $where .= " AND d.faculty_id = ?"; $params[] = $facultyId; }
            $stmt = $db->prepare("SELECT d.*, f.name as faculty_name, (SELECT COUNT(*) FROM programmes WHERE department_id = d.id) as prog_count, (SELECT COUNT(*) FROM students WHERE department_id = d.id) as student_count FROM departments d JOIN faculties f ON d.faculty_id = f.id WHERE $where ORDER BY d.name");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_department':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['name']) || empty($input['code']) || empty($input['faculty_id'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Name, code, and faculty required'], 400);
            }
            $check = $db->prepare("SELECT id FROM departments WHERE code = ?");
            $check->execute([$input['code']]);
            if ($check->fetch()) $core->jsonResponse(['success' => false, 'error' => 'Department code already exists'], 400);
            
            $stmt = $db->prepare("INSERT INTO departments (faculty_id, name, code, description, head_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$input['faculty_id'], $input['name'], $input['code'], $input['description'] ?? '', $input['head_name'] ?? '']);
            $core->audit('Department Created', "Created department: {$input['name']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Department created successfully']);
            break;
        
        case 'update_department':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $stmt = $db->prepare("UPDATE departments SET faculty_id = ?, name = ?, code = ?, description = ?, head_name = ? WHERE id = ?");
            $stmt->execute([$input['faculty_id'] ?? '', $input['name'] ?? '', $input['code'] ?? '', $input['description'] ?? '', $input['head_name'] ?? '', $id]);
            $core->audit('Department Updated', "Updated department ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Department updated successfully']);
            break;
        
        case 'delete_department':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $db->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
            $core->audit('Department Deleted', "Deleted department ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Department deleted successfully']);
            break;
        
        // ==================== PROGRAMMES ====================
        
        case 'programmes':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? '';
            $departmentId = $_GET['department_id'] ?? '';
            $where = "1=1";
            $params = [];
            if ($search) { $where .= " AND (p.name LIKE ? OR p.code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($type) { $where .= " AND p.type = ?"; $params[] = $type; }
            if ($departmentId) { $where .= " AND p.department_id = ?"; $params[] = $departmentId; }
            $stmt = $db->prepare("SELECT p.*, d.name as department_name, f.name as faculty_name, (SELECT COUNT(*) FROM students WHERE programme_id = p.id) as student_count FROM programmes p JOIN departments d ON p.department_id = d.id JOIN faculties f ON d.faculty_id = f.id WHERE $where ORDER BY p.name");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_programme':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['name']) || empty($input['code']) || empty($input['department_id']) || empty($input['type'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Name, code, department, and type required'], 400);
            }
            $check = $db->prepare("SELECT id FROM programmes WHERE code = ?");
            $check->execute([$input['code']]);
            if ($check->fetch()) $core->jsonResponse(['success' => false, 'error' => 'Programme code already exists'], 400);
            
            $validTypes = ['Diploma', 'ND', 'HND', 'Degree', 'Masters'];
            if (!in_array($input['type'], $validTypes)) {
                $core->jsonResponse(['success' => false, 'error' => 'Invalid programme type'], 400);
            }
            
            $stmt = $db->prepare("INSERT INTO programmes (department_id, name, code, type, duration_years, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$input['department_id'], $input['name'], $input['code'], $input['type'], $input['duration_years'] ?? 4, $input['description'] ?? '']);
            $core->audit('Programme Created', "Created programme: {$input['name']} ({$input['type']})", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Programme created successfully']);
            break;
        
        case 'update_programme':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $stmt = $db->prepare("UPDATE programmes SET department_id = ?, name = ?, code = ?, type = ?, duration_years = ?, description = ? WHERE id = ?");
            $stmt->execute([$input['department_id'] ?? '', $input['name'] ?? '', $input['code'] ?? '', $input['type'] ?? 'Degree', $input['duration_years'] ?? 4, $input['description'] ?? '', $id]);
            $core->audit('Programme Updated', "Updated programme ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Programme updated successfully']);
            break;
        
        case 'delete_programme':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $db->prepare("DELETE FROM programmes WHERE id = ?")->execute([$id]);
            $core->audit('Programme Deleted', "Deleted programme ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Programme deleted successfully']);
            break;
        
        // ==================== COURSES ====================
        
        case 'courses':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $search = $_GET['search'] ?? '';
            $departmentId = $_GET['department_id'] ?? '';
            $where = "1=1";
            $params = [];
            if ($search) { $where .= " AND (c.title LIKE ? OR c.code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($departmentId) { $where .= " AND c.department_id = ?"; $params[] = $departmentId; }
            $stmt = $db->prepare("SELECT c.*, d.name as department_name, f.name as faculty_name, u.full_name as lecturer_name, pc.title as prerequisite_title FROM courses c JOIN departments d ON c.department_id = d.id JOIN faculties f ON d.faculty_id = f.id LEFT JOIN users u ON c.lecturer_id = u.id LEFT JOIN courses pc ON c.prerequisite_id = pc.id WHERE $where ORDER BY c.code");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_course':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['code']) || empty($input['title']) || empty($input['department_id'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Code, title, and department required'], 400);
            }
            $stmt = $db->prepare("INSERT INTO courses (department_id, code, title, description, credit_units, semester, level, is_elective, prerequisite_id, lecturer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['department_id'], $input['code'], $input['title'], $input['description'] ?? '',
                $input['credit_units'] ?? 2, $input['semester'] ?? 'First', $input['level'] ?? 100,
                $input['is_elective'] ?? 0, $input['prerequisite_id'] ?? null, $input['lecturer_id'] ?? null
            ]);
            $core->audit('Course Created', "Created course: {$input['code']} - {$input['title']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Course created successfully']);
            break;
        
        case 'update_course':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $stmt = $db->prepare("UPDATE courses SET department_id = ?, code = ?, title = ?, description = ?, credit_units = ?, semester = ?, level = ?, is_elective = ?, prerequisite_id = ?, lecturer_id = ? WHERE id = ?");
            $stmt->execute([
                $input['department_id'] ?? '', $input['code'] ?? '', $input['title'] ?? '',
                $input['description'] ?? '', $input['credit_units'] ?? 2, $input['semester'] ?? 'First',
                $input['level'] ?? 100, $input['is_elective'] ?? 0, $input['prerequisite_id'] ?? null,
                $input['lecturer_id'] ?? null, $id
            ]);
            $core->audit('Course Updated', "Updated course ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Course updated successfully']);
            break;
        
        case 'delete_course':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $db->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
            $core->audit('Course Deleted', "Deleted course ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Course deleted successfully']);
            break;
        
        // ==================== STAFF ====================
        
        case 'staff':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $where = "role IN ('admin', 'lecturer')";
            $params = [];
            if ($search) { $where .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($role) { $where .= " AND role = ?"; $params[] = $role; }
            $stmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM courses WHERE lecturer_id = u.id) as course_count FROM users u WHERE $where ORDER BY u.full_name");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_staff':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['full_name']) || empty($input['email']) || empty($input['username'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Name, email, and username required'], 400);
            }
            $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check->execute([$input['email'], $input['username']]);
            if ($check->fetch()) $core->jsonResponse(['success' => false, 'error' => 'Email or username already exists'], 400);
            
            $password = $input['password'] ?? 'Staff@123';
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $role = in_array($input['role'] ?? '', ['admin', 'lecturer']) ? $input['role'] : 'lecturer';
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$input['username'], $input['email'], $hashed, $input['full_name'], $input['phone'] ?? '', $role]);
            $core->audit('Staff Created', "Created {$role}: {$input['full_name']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Staff member created successfully', 'password' => $password]);
            break;
        
        case 'update_staff':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
            $stmt->execute([$input['full_name'] ?? '', $input['email'] ?? '', $input['phone'] ?? '', $input['role'] ?? 'lecturer', $id]);
            $core->audit('Staff Updated', "Updated staff ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Staff updated successfully']);
            break;
        
        case 'delete_staff':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            if ($id == $currentUser['id']) $core->jsonResponse(['success' => false, 'error' => 'Cannot delete yourself'], 400);
            $db->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin','lecturer')")->execute([$id]);
            $core->audit('Staff Deleted', "Deleted staff ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Staff deleted successfully']);
            break;
        
        // ==================== SESSIONS ====================
        
        case 'sessions':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $stmt = $db->query("SELECT ses.*, (SELECT COUNT(*) FROM students WHERE session_id = ses.id) as student_count FROM sessions ses ORDER BY ses.start_date DESC");
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'create_session':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            if (empty($input['name'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Session name required'], 400);
            }
            $check = $db->prepare("SELECT id FROM sessions WHERE name = ?");
            $check->execute([$input['name']]);
            if ($check->fetch()) $core->jsonResponse(['success' => false, 'error' => 'Session name already exists'], 400);
            
            $stmt = $db->prepare("INSERT INTO sessions (name, start_date, end_date, semester, status) VALUES (?, ?, ?, ?, 'upcoming')");
            $stmt->execute([$input['name'], $input['start_date'] ?? null, $input['end_date'] ?? null, $input['semester'] ?? 'First']);
            $core->audit('Session Created', "Created session: {$input['name']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Session created successfully']);
            break;
        
        case 'activate_session':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $id = $input['id'] ?? '';
            $db->prepare("UPDATE sessions SET is_current = 0, status = 'completed' WHERE is_current = 1")->execute();
            $db->prepare("UPDATE sessions SET is_current = 1, status = 'active' WHERE id = ?")->execute([$id]);
            $core->audit('Session Activated', "Activated session ID: {$id}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Session activated successfully']);
            break;
        
        // ==================== DOCUMENTS ====================
        
        case 'upload_document':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            if (empty($_FILES['file'])) {
                $core->jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
            }
            
            // Get student record
            $studentStmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
            $studentStmt->execute([$currentUser['id']]);
            $student = $studentStmt->fetch();
            if (!$student) $core->jsonResponse(['success' => false, 'error' => 'Student record not found'], 404);
            
            $settings = $core->getSettings();
            $maxSize = (int)$settings['max_upload_size_mb'];
            $allowedTypes = explode(',', $settings['allowed_file_types']);
            $allowedTypes = array_map('trim', $allowedTypes);
            
            $validation = $core->validateFileUpload($_FILES['file'], $maxSize, $allowedTypes);
            if (!$validation['success']) {
                $core->jsonResponse(['success' => false, 'error' => $validation['error']], 400);
            }
            
            $ext = $validation['extension'];
            $fileName = uniqid('doc_') . '.' . $ext;
            $uploadDir = dirname(__FILE__) . '/uploads/documents/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $targetPath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $core->jsonResponse(['success' => false, 'error' => 'Failed to save file'], 500);
            }
            
            $docType = $input['document_type'] ?? 'General';
            $stmt = $db->prepare("INSERT INTO student_documents (student_id, document_type, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student['id'], $docType, $_FILES['file']['name'], 'uploads/documents/' . $fileName, $_FILES['file']['size'], $_FILES['file']['type']]);
            
            $core->audit('Document Uploaded', "Uploaded: {$_FILES['file']['name']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Document uploaded successfully']);
            break;
        
        case 'student_documents':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $userId = $currentUser['role'] === 'student' ? $currentUser['id'] : ($input['user_id'] ?? null);
            if ($currentUser['role'] !== 'student' && !$userId) {
                $core->jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
            }
            $stmt = $db->prepare("SELECT d.* FROM student_documents d JOIN students s ON d.student_id = s.id WHERE s.user_id = ? ORDER BY d.uploaded_at DESC");
            $stmt->execute([$userId]);
            $docs = $stmt->fetchAll();
            // Format file sizes
            foreach ($docs as &$doc) {
                $doc['file_size_formatted'] = $core->formatBytes($doc['file_size']);
            }
            $core->jsonResponse(['success' => true, 'data' => $docs]);
            break;
        
        case 'delete_document':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $id = $input['id'] ?? '';
            $doc = $db->prepare("SELECT * FROM student_documents WHERE id = ? AND student_id = (SELECT id FROM students WHERE user_id = ?)");
            $doc->execute([$id, $currentUser['id']]);
            $document = $doc->fetch();
            if ($document) {
                $filePath = dirname(__FILE__) . '/' . $document['file_path'];
                if (file_exists($filePath)) unlink($filePath);
                $db->prepare("DELETE FROM student_documents WHERE id = ?")->execute([$id]);
                $core->jsonResponse(['success' => true, 'message' => 'Document deleted successfully']);
            }
            $core->jsonResponse(['success' => false, 'error' => 'Document not found'], 404);
            break;
        
        // ==================== CERTIFICATES ====================
        
        case 'download_certificate':
            $certNumber = $input['certificate_number'] ?? ($_GET['certificate_number'] ?? '');
            if (empty($certNumber)) {
                $core->jsonResponse(['success' => false, 'error' => 'Certificate number required'], 400);
            }
            $stmt = $db->prepare("SELECT s.*, u.full_name, f.name as faculty_name, d.name as department_name, p.name as programme_name, p.type as programme_type FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id WHERE s.certificate_number = ?");
            $stmt->execute([$certNumber]);
            $student = $stmt->fetch();
            
            if (!$student) {
                $core->jsonResponse(['success' => false, 'error' => 'Certificate not found'], 404);
            }
            
            $settings = $core->getSettings();
            
            // Generate certificate as downloadable file
            $certContent = generateCertificateHTML($student, $settings);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Certificate_' . str_replace('/', '-', $certNumber) . '.pdf"');
            header('Cache-Control: no-cache');
            echo $certContent;
            exit;
            break;
        
        case 'verify_certificate':
            $certNumber = $input['certificate_number'] ?? ($_GET['certificate_number'] ?? '');
            if (empty($certNumber)) {
                $core->jsonResponse(['success' => false, 'error' => 'Certificate number required'], 400);
            }
            $stmt = $db->prepare("SELECT s.certificate_number, s.certificate_issued_at, s.status, u.full_name, f.name as faculty_name, d.name as department_name, p.name as programme_name, p.type as programme_type FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id WHERE s.certificate_number = ? AND s.status = 'graduated'");
            $stmt->execute([$certNumber]);
            $cert = $stmt->fetch();
            
            if ($cert) {
                $core->jsonResponse(['success' => true, 'data' => $cert, 'message' => 'Certificate is valid']);
            }
            $core->jsonResponse(['success' => false, 'error' => 'Certificate not found or invalid', 'verified' => false]);
            break;
        
        // ==================== LECTURER ====================
        
        case 'lecturer_students':
            if (!$currentUser || $currentUser['role'] !== 'lecturer') {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $where = "s.department_id IN (SELECT DISTINCT department_id FROM courses WHERE lecturer_id = ?)";
            $params = [$currentUser['id']];
            if ($search) { $where .= " AND (u.full_name LIKE ? OR s.student_id_number LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($status) { $where .= " AND s.status = ?"; $params[] = $status; }
            
            $stmt = $db->prepare("SELECT s.*, u.full_name, u.email, u.phone, f.name as faculty_name, d.name as department_name, p.name as programme_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id WHERE $where ORDER BY u.full_name");
            $stmt->execute($params);
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        case 'student_profile':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $userId = $currentUser['role'] === 'student' ? $currentUser['id'] : ($input['user_id'] ?? null);
            if (!$userId) $core->jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
            
            $stmt = $db->prepare("SELECT s.*, u.full_name, u.email, u.phone, u.username, f.name as faculty_name, f.code as faculty_code, d.name as department_name, d.code as department_code, p.name as programme_name, p.code as programme_code, p.type as programme_type_name, ses.name as session_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id LEFT JOIN sessions ses ON s.session_id = ses.id WHERE s.user_id = ?");
            $stmt->execute([$userId]);
            $student = $stmt->fetch();
            if ($student) {
                $core->jsonResponse(['success' => true, 'data' => $student]);
            }
            $core->jsonResponse(['success' => false, 'error' => 'Student profile not found'], 404);
            break;
        
        case 'update_profile':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$input['full_name'] ?? '', $input['phone'] ?? '', $currentUser['id']]);
            
            if ($currentUser['role'] === 'student') {
                $stmt = $db->prepare("UPDATE students SET home_address = ?, guardian_name = ?, guardian_phone = ? WHERE user_id = ?");
                $stmt->execute([$input['home_address'] ?? '', $input['guardian_name'] ?? '', $input['guardian_phone'] ?? '', $currentUser['id']]);
            }
            
            $core->audit('Profile Updated', "Updated profile for user ID: {$currentUser['id']}", $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
            break;
        
        // ==================== AUDIT LOG ====================
        
        case 'audit_log':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $search = $_GET['search'] ?? '';
            $action = $_GET['action'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 25;
            $offset = ($page - 1) * $limit;
            
            $where = "1=1";
            $params = [];
            if ($search) { $where .= " AND (action LIKE ? OR details LIKE ? OR user_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($action) { $where .= " AND action LIKE ?"; $params[] = "%$action%"; }
            if ($dateFrom) { $where .= " AND created_at >= ?"; $params[] = $dateFrom; }
            if ($dateTo) { $where .= " AND created_at <= ?"; $params[] = $dateTo . ' 23:59:59'; }
            
            $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_log WHERE $where");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT * FROM audit_log WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
            break;
        
        // ==================== CSV EXPORT ====================
        
        case 'export_csv':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $type = $input['type'] ?? 'students';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            if ($type === 'students') {
                fputcsv($output, ['Student ID', 'Full Name', 'Email', 'Phone', 'Gender', 'Faculty', 'Department', 'Programme', 'Level', 'Type', 'Status', 'CGPA', 'Enrollment Date']);
                $rows = $db->query("SELECT s.student_id_number, u.full_name, u.email, u.phone, s.gender, f.name as faculty_name, d.name as dept_name, p.name as prog_name, s.level, s.programme_type, s.status, s.cgpa, s.enrollment_date FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN faculties f ON s.faculty_id = f.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN programmes p ON s.programme_id = p.id ORDER BY s.student_id_number")->fetchAll();
                foreach ($rows as $row) {
                    fputcsv($output, array_values($row));
                }
            } elseif ($type === 'staff') {
                fputcsv($output, ['Name', 'Email', 'Username', 'Phone', 'Role', 'Last Login']);
                $rows = $db->query("SELECT full_name, email, username, phone, role, last_login FROM users WHERE role IN ('admin','lecturer') ORDER BY full_name")->fetchAll();
                foreach ($rows as $row) {
                    fputcsv($output, array_values($row));
                }
            } elseif ($type === 'audit') {
                fputcsv($output, ['Date', 'User', 'Action', 'Details', 'IP']);
                $rows = $db->query("SELECT created_at, user_name, action, details, ip_address FROM audit_log ORDER BY created_at DESC LIMIT 1000")->fetchAll();
                foreach ($rows as $row) {
                    fputcsv($output, array_values($row));
                }
            }
            
            fclose($output);
            $core->audit('CSV Export', "Exported {$type} data", $currentUser['id'], $currentUser['full_name']);
            exit;
            break;
        
        // ==================== SETTINGS ====================
        
        case 'settings':
            if (!$currentUser) $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            $settings = $core->getSettings();
            $core->jsonResponse(['success' => true, 'data' => $settings]);
            break;
        
        case 'update_settings':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $fields = ['institution_name', 'institution_address', 'institution_email', 'institution_phone', 'institution_website', 'institution_motto', 'academic_year_format', 'currency_symbol', 'enable_certificate', 'enable_document_upload', 'max_upload_size_mb', 'allowed_file_types', 'session_prefix'];
            $setClauses = [];
            $params = [];
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $setClauses[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            if (!empty($setClauses)) {
                $sql = "UPDATE settings SET " . implode(', ', $setClauses) . " WHERE id = (SELECT MIN(id) FROM settings)";
                $db->prepare($sql)->execute($params);
            }
            $core->audit('Settings Updated', 'Updated system settings', $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Settings updated successfully']);
            break;
        
        case 'institution':
            $settings = $core->getSettings();
            $core->jsonResponse(['success' => true, 'data' => $settings]);
            break;
        
        case 'update_institution':
            if (!$currentUser || !in_array($currentUser['role'], ['superadmin', 'admin'])) {
                $core->jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            $fields = ['institution_name', 'institution_address', 'institution_email', 'institution_phone', 'institution_website', 'institution_logo', 'institution_motto'];
            $setClauses = [];
            $params = [];
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $setClauses[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            if (!empty($setClauses)) {
                $sql = "UPDATE settings SET " . implode(', ', $setClauses) . " WHERE id = (SELECT MIN(id) FROM settings)";
                $db->prepare($sql)->execute($params);
            }
            $core->audit('Institution Updated', 'Updated institution info', $currentUser['id'], $currentUser['full_name']);
            $core->jsonResponse(['success' => true, 'message' => 'Institution info updated']);
            break;
        
        // ==================== LOOKUP ====================
        
        case 'lookup':
            $type = $input['type'] ?? ($_GET['type'] ?? '');
            $search = $_GET['search'] ?? '';
            
            switch ($type) {
                case 'faculties':
                    $where = $search ? "WHERE name LIKE ?" : "";
                    $params = $search ? ["%$search%"] : [];
                    $stmt = $db->prepare("SELECT id, name, code FROM faculties $where ORDER BY name");
                    $stmt->execute($params);
                    break;
                case 'departments':
                    $facultyId = $_GET['faculty_id'] ?? '';
                    $where = "1=1";
                    $params = [];
                    if ($facultyId) { $where .= " AND faculty_id = ?"; $params[] = $facultyId; }
                    if ($search) { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
                    $stmt = $db->prepare("SELECT id, name, code, faculty_id FROM departments WHERE $where ORDER BY name");
                    $stmt->execute($params);
                    break;
                case 'programmes':
                    $deptId = $_GET['department_id'] ?? '';
                    $where = "1=1";
                    $params = [];
                    if ($deptId) { $where .= " AND department_id = ?"; $params[] = $deptId; }
                    if ($search) { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
                    $stmt = $db->prepare("SELECT id, name, code, type, department_id FROM programmes WHERE $where ORDER BY name");
                    $stmt->execute($params);
                    break;
                case 'courses':
                    $deptId = $_GET['department_id'] ?? '';
                    $where = "1=1";
                    $params = [];
                    if ($deptId) { $where .= " AND department_id = ?"; $params[] = $deptId; }
                    if ($search) { $where .= " AND (title LIKE ? OR code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
                    $stmt = $db->prepare("SELECT id, code, title, credit_units, lecturer_id FROM courses WHERE $where ORDER BY code");
                    $stmt->execute($params);
                    break;
                case 'lecturers':
                    $where = "role = 'lecturer'";
                    $params = [];
                    if ($search) { $where .= " AND full_name LIKE ?"; $params[] = "%$search%"; }
                    $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE $where ORDER BY full_name");
                    $stmt->execute($params);
                    break;
                case 'sessions':
                    $stmt = $db->query("SELECT id, name, status, is_current FROM sessions ORDER BY start_date DESC");
                    break;
                default:
                    $core->jsonResponse(['success' => false, 'error' => 'Invalid lookup type'], 400);
            }
            
            $core->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;
        
        // ==================== DEFAULT ====================
        
        default:
            $core->jsonResponse(['success' => false, 'error' => "Unknown action: {$action}"], 400);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// ==================== Certificate Generation Helper ====================

function generateCertificateHTML($student, $settings) {
    // Generate a simple PDF-like HTML certificate
    // In production, use a proper PDF library like TCPDF or Dompdf
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    $html .= '<style>';
    $html .= 'body{font-family:Georgia,serif;text-align:center;padding:60px;margin:0;background:#fff;}';
    $html .= '.cert{border:8px double #c9973f;padding:60px 40px;max-width:800px;margin:0 auto;}';
    $html .= '.header{margin-bottom:40px;}';
    $html .= '.header h1{color:#1a365d;font-size:28px;margin:0;}';
    $html .= '.header p{color:#666;font-size:14px;margin:5px 0;}';
    $html .= '.title{font-size:36px;color:#1a365d;margin:30px 0;font-weight:bold;text-transform:uppercase;}';
    $html .= '.subtitle{font-size:16px;color:#555;margin-bottom:30px;}';
    $html .= '.name{font-size:32px;color:#c9973f;margin:20px 0;border-bottom:2px solid #c9973f;display:inline-block;padding-bottom:10px;}';
    $html .= '.details{font-size:16px;color:#333;line-height:2;}';
    $html .= '.footer{margin-top:50px;font-size:12px;color:#888;}';
    $html .= '.seal{font-size:14px;color:#1a365d;margin-top:40px;font-style:italic;}';
    $html .= '</style></head><body>';
    $html .= '<div class="cert">';
    $html .= '<div class="header">';
    $html .= '<h1>' . htmlspecialchars($settings['institution_name'] ?? 'SAZUG University') . '</h1>';
    $html .= '<p>' . htmlspecialchars($settings['institution_motto'] ?? '') . '</p>';
    $html .= '</div>';
    $html .= '<div class="title">Certificate of Graduation</div>';
    $html .= '<div class="subtitle">This is to certify that</div>';
    $html .= '<div class="name">' . htmlspecialchars($student['full_name']) . '</div>';
    $html .= '<div class="details">';
    $html .= 'has successfully completed the requirements for the<br>';
    $html .= '<strong>' . htmlspecialchars($student['programme_type'] ?? '') . ' in ' . htmlspecialchars($student['programme_name'] ?? '') . '</strong><br>';
    $html .= 'Faculty of ' . htmlspecialchars($student['faculty_name'] ?? '') . '<br>';
    $html .= 'Department of ' . htmlspecialchars($student['department_name'] ?? '') . '<br>';
    $html .= 'and is awarded this certificate with all rights and privileges thereto.<br>';
    $html .= 'CGPA: <strong>' . number_format($student['cgpa'] ?? 0, 2) . '/5.00</strong>';
    $html .= '</div>';
    $html .= '<div class="seal">Certificate No: ' . htmlspecialchars($student['certificate_number']) . '</div>';
    $html .= '<div class="footer">';
    $html .= 'Date Issued: ' . date('F j, Y', strtotime($student['certificate_issued_at'])) . '<br>';
    $html .= '<em>This certificate can be verified online at ' . ($settings['institution_website'] ?? 'sazug.edu.ng') . '</em>';
    $html .= '</div>';
    $html .= '</div></body></html>';
    
    return $html;
}
