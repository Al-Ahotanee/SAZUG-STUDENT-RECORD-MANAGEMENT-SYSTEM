<?php
/**
 * SAZUG Student Record Management System — Core Engine v2.0
 * Fully Implemented API & Business Logic Layer
 * PHP 8.3+, PDO, Optimized for Minimal RAM Usage on Render Free Tier
 */

class SystemCore {
    private PDO $db;

    public function __construct() {
        $this->startSecureSession();
        $this->connectDB();
        $this->ensureUploadDirectories();
    }

    /* =========================================================
     * SYSTEM & SECURITY
     * ========================================================= */

    private function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            if (getenv('SESSION_SECURE') === 'true') {
                ini_set('session.cookie_secure', '1');
            }
            session_start();
        }
        $this->checkSessionTimeout();
    }

    private function checkSessionTimeout(): void {
        $timeoutDuration = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutDuration) {
            session_unset();
            session_destroy();
            header('HTTP/1.1 401 Unauthorized');
            exit(json_encode(['status' => false, 'message' => 'Session timeout. Please login again.']));
        }
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    private function connectDB(): void {
        $host    = getenv('DB_HOST') ?: 'localhost';
        $port    = getenv('DB_PORT') ?: '3306';
        $db      = getenv('DB_NAME') ?: 'sazug_srms';
        $user    = getenv('DB_USER') ?: 'root';
        $pass    = getenv('DB_PASS') ?: '';
        $sslMode = getenv('DB_SSL')  ?: 'false';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        if ($sslMode === 'true') {
            $dsn .= ';sslmode=require';
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ];

        try {
            $this->db = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            header('HTTP/1.1 500 Internal Server Error');
            exit(json_encode(['status' => false, 'message' => 'Database connection failed. Please try again later.']));
        }
    }

    private function ensureUploadDirectories(): void {
        $dirs = ['uploads/passports', 'uploads/documents', 'uploads/certificates'];
        foreach ($dirs as $dir) {
            $full = __DIR__ . '/' . $dir;
            if (!is_dir($full)) {
                mkdir($full, 0755, true);
            }
        }
    }

    public function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function enforcePermissions(array $allowedRoles): void {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
            header('HTTP/1.1 403 Forbidden');
            exit(json_encode(['status' => false, 'message' => 'Access denied. Insufficient privileges.']));
        }
    }

    public function logAudit(string $action, ?string $entity = null, ?int $entityId = null, ?string $details = null): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address, user_agent) 
                 VALUES (:uid, :action, :entity, :entity_id, :details, :ip, :ua)'
            );
            $stmt->execute([
                'uid'       => $_SESSION['user_id'] ?? null,
                'action'    => $action,
                'entity'    => $entity,
                'entity_id' => $entityId,
                'details'   => $details,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    private function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /* =========================================================
     * GENERIC CRUD HELPERS
     * ========================================================= */

    public function insert(string $table, array $data): int {
        $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $params = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $stmt   = $this->db->prepare("INSERT INTO `$table` ($cols) VALUES ($params)");
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(string $table, int $id, array $data): bool {
        $set  = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE `$table` SET $set WHERE id = :id");
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete(string $table, int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `$table` WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function fetchAll(string $table, array $where = [], string $order = 'id ASC', int $limit = 0): array {
        $sql = "SELECT * FROM `$table`";
        $params = [];
        if (!empty($where)) {
            $conds = implode(' AND ', array_map(fn($k) => "`$k` = :$k", array_keys($where)));
            $sql  .= " WHERE $conds";
            $params = $where;
        }
        $sql .= " ORDER BY $order";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchOne(string $table, int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `$table` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getDB(): PDO { return $this->db; }

    /* =========================================================
     * AUTHENTICATION
     * ========================================================= */

    public function login(string $username, string $password): array {
        if (empty($username) || empty($password)) {
            return ['status' => false, 'message' => 'Username and password are required.'];
        }

        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, role, status, failed_attempts, locked_until 
             FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute(['u' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['status' => false, 'message' => 'Invalid credentials. Please try again.'];
        }

        // Check lock
        if ($user['status'] === 'Locked') {
            if ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                $until = (new DateTime($user['locked_until']))->format('H:i');
                return ['status' => false, 'message' => "Account locked until $until. Contact admin."];
            } else {
                // Unlock
                $this->db->prepare("UPDATE users SET status='Active', failed_attempts=0, locked_until=NULL WHERE id=:id")
                          ->execute(['id' => $user['id']]);
                $user['status'] = 'Active';
            }
        }

        if ($user['status'] === 'Suspended') {
            return ['status' => false, 'message' => 'Account suspended. Contact administrator.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = $user['failed_attempts'] + 1;
            $lockUntil = null;
            $newStatus = $user['status'];
            if ($attempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $newStatus = 'Locked';
            }
            $this->db->prepare(
                "UPDATE users SET failed_attempts=:att, status=:st, locked_until=:lu WHERE id=:id"
            )->execute(['att' => $attempts, 'st' => $newStatus, 'lu' => $lockUntil, 'id' => $user['id']]);

            $remaining = max(0, 5 - $attempts);
            $msg = $remaining > 0
                ? "Invalid credentials. $remaining attempt(s) remaining before lockout."
                : 'Account locked for 30 minutes due to too many failed attempts.';
            return ['status' => false, 'message' => $msg];
        }

        // Successful login
        $this->db->prepare(
            "UPDATE users SET failed_attempts=0, status='Active', locked_until=NULL, last_login=NOW() WHERE id=:id"
        )->execute(['id' => $user['id']]);

        // Regenerate session ID
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();
        $this->generateCSRFToken();

        $this->logAudit('User Login', 'users', (int)$user['id']);

        return [
            'status'   => true,
            'message'  => 'Login successful.',
            'role'     => $user['role'],
            'username' => $user['username'],
        ];
    }

    public function logout(): array {
        $userId = $_SESSION['user_id'] ?? null;
        $this->logAudit('User Logout', 'users', $userId);
        session_unset();
        session_destroy();
        return ['status' => true, 'message' => 'Logged out successfully.'];
    }

    public function forgotPassword(string $username): array {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :u OR email = :u LIMIT 1");
        $stmt->execute(['u' => trim($username)]);
        $user = $stmt->fetch();
        // Always return success to prevent user enumeration
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->db->prepare("UPDATE users SET reset_token=:t, reset_token_expires=:e WHERE id=:id")
                     ->execute(['t' => $token, 'e' => $expires, 'id' => $user['id']]);
            $this->logAudit('Password Reset Requested', 'users', (int)$user['id']);
        }
        return ['status' => true, 'message' => 'If the account exists, a reset token has been generated. Contact admin with your username for the token.', 'token' => $user ? $token ?? '' : ''];
    }

    public function resetPassword(string $token, string $newPassword): array {
        if (strlen($newPassword) < 6) {
            return ['status' => false, 'message' => 'Password must be at least 6 characters.'];
        }
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE reset_token = :t AND reset_token_expires > NOW() LIMIT 1"
        );
        $stmt->execute(['t' => $token]);
        $user = $stmt->fetch();
        if (!$user) {
            return ['status' => false, 'message' => 'Invalid or expired reset token.'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->prepare(
            "UPDATE users SET password_hash=:h, reset_token=NULL, reset_token_expires=NULL, status='Active', failed_attempts=0 WHERE id=:id"
        )->execute(['h' => $hash, 'id' => $user['id']]);
        $this->logAudit('Password Reset', 'users', (int)$user['id']);
        return ['status' => true, 'message' => 'Password reset successfully. You can now login.'];
    }

    public function changePassword(int $userId, string $current, string $new): array {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return ['status' => false, 'message' => 'Current password is incorrect.'];
        }
        if (strlen($new) < 6) {
            return ['status' => false, 'message' => 'New password must be at least 6 characters.'];
        }
        $this->db->prepare("UPDATE users SET password_hash=:h WHERE id=:id")
                 ->execute(['h' => password_hash($new, PASSWORD_BCRYPT), 'id' => $userId]);
        $this->logAudit('Password Changed', 'users', $userId);
        return ['status' => true, 'message' => 'Password changed successfully.'];
    }

    /* =========================================================
     * DASHBOARD STATISTICS
     * ========================================================= */

    public function getDashboardStats(): array {
        $stats = [];

        // Core counts — single query for performance
        $row = $this->db->query("
            SELECT 
                COUNT(*) AS total_students,
                SUM(status = 'Active') AS active_students,
                SUM(status = 'Graduated') AS graduated_students,
                SUM(status = 'Suspended') AS suspended_students,
                SUM(status = 'Withdrawn') AS withdrawn_students
            FROM students
        ")->fetch();
        $stats = array_merge($stats, $row);

        $stats['total_staff']       = (int)$this->db->query("SELECT COUNT(*) FROM staff")->fetchColumn();
        $stats['total_faculties']   = (int)$this->db->query("SELECT COUNT(*) FROM faculties")->fetchColumn();
        $stats['total_departments'] = (int)$this->db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
        $stats['total_programmes']  = (int)$this->db->query("SELECT COUNT(*) FROM programmes")->fetchColumn();
        $stats['total_courses']     = (int)$this->db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $stats['total_certificates']= (int)$this->db->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

        // Active session
        $activeSession = $this->db->query("SELECT name, semester FROM sessions WHERE is_active=1 LIMIT 1")->fetch();
        $stats['active_session'] = $activeSession ? $activeSession['name'] . ' (' . $activeSession['semester'] . ' Semester)' : 'None';

        // Students by department (for chart)
        $deptStmt = $this->db->query("
            SELECT d.name AS dept, COUNT(s.id) AS count 
            FROM departments d LEFT JOIN students s ON s.department_id = d.id 
            GROUP BY d.id, d.name ORDER BY count DESC LIMIT 8
        ");
        $stats['by_department'] = $deptStmt->fetchAll();

        // Students by level (for chart)
        $levelStmt = $this->db->query("
            SELECT level, COUNT(*) AS count FROM students GROUP BY level ORDER BY level
        ");
        $stats['by_level'] = $levelStmt->fetchAll();

        // Students by status (for chart)
        $statusStmt = $this->db->query("
            SELECT status, COUNT(*) AS count FROM students GROUP BY status
        ");
        $stats['by_status'] = $statusStmt->fetchAll();

        // Monthly admissions (last 6 months)
        $monthStmt = $this->db->query("
            SELECT DATE_FORMAT(admission_date,'%b %Y') AS month, COUNT(*) AS count 
            FROM students WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
            GROUP BY DATE_FORMAT(admission_date,'%b %Y') ORDER BY MIN(admission_date)
        ");
        $stats['monthly_admissions'] = $monthStmt->fetchAll();

        // Recent activities
        $actStmt = $this->db->query("
            SELECT a.action, a.entity, a.created_at, u.username 
            FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC LIMIT 10
        ");
        $stats['recent_activities'] = $actStmt->fetchAll();

        return $stats;
    }

    /* =========================================================
     * STUDENTS
     * ========================================================= */

    public function getStudents(array $filters = [], int $page = 1, int $perPage = 100): array {
        $where  = [];
        $params = [];

        if (!empty($filters['department_id'])) {
            $where[]                = 's.department_id = :dept_id';
            $params['dept_id']      = (int)$filters['department_id'];
        }
        if (!empty($filters['faculty_id'])) {
            $where[]                = 's.faculty_id = :fac_id';
            $params['fac_id']       = (int)$filters['faculty_id'];
        }
        if (!empty($filters['status'])) {
            $where[]                = 's.status = :status';
            $params['status']       = $filters['status'];
        }
        if (!empty($filters['level'])) {
            $where[]                = 's.level = :level';
            $params['level']        = (int)$filters['level'];
        }
        if (!empty($filters['search'])) {
            $where[]                = '(s.full_name LIKE :search OR s.admission_number LIKE :search OR s.matric_number LIKE :search)';
            $params['search']       = '%' . $filters['search'] . '%';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($page - 1) * $perPage;

        $sql = "
            SELECT s.id, s.admission_number, s.matric_number, s.full_name, s.gender, s.phone,
                   s.level, s.status, s.admission_date, s.graduation_date, s.passport_path,
                   d.name AS department_name, f.name AS faculty_name, p.name AS programme_name, p.type AS programme_type,
                   ses.name AS session_name
            FROM students s
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN sessions ses ON s.session_id = ses.id
            $whereSQL
            ORDER BY s.full_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStudentById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, d.name AS department_name, f.name AS faculty_name,
                   p.name AS programme_name, p.type AS programme_type,
                   ses.name AS session_name, ses.semester,
                   c.certificate_number, c.qr_code_path, c.issue_date AS cert_issue_date
            FROM students s
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN sessions ses ON s.session_id = ses.id
            LEFT JOIN certificates c ON s.id = c.student_id
            WHERE s.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getStudentByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, d.name AS department_name, f.name AS faculty_name,
                   p.name AS programme_name, p.type AS programme_type,
                   ses.name AS session_name,
                   c.certificate_number, c.qr_code_path, c.issue_date AS cert_issue_date
            FROM students s
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN sessions ses ON s.session_id = ses.id
            LEFT JOIN certificates c ON s.id = c.student_id
            WHERE s.user_id = :uid LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function createStudent(array $userData, array $studentData): array {
        // Validate required fields
        $required = ['admission_number', 'full_name', 'dob', 'gender', 'department_id', 'faculty_id', 'programme_id', 'level', 'session_id'];
        foreach ($required as $field) {
            if (empty($studentData[$field])) {
                return ['status' => false, 'message' => "Field '$field' is required."];
            }
        }

        // Check duplicate admission number
        $check = $this->db->prepare("SELECT id FROM students WHERE admission_number = :an LIMIT 1");
        $check->execute(['an' => $studentData['admission_number']]);
        if ($check->fetch()) {
            return ['status' => false, 'message' => 'Admission number already exists.'];
        }

        // Check duplicate matric number (if provided)
        if (!empty($studentData['matric_number'])) {
            $chkM = $this->db->prepare("SELECT id FROM students WHERE matric_number = :mn LIMIT 1");
            $chkM->execute(['mn' => $studentData['matric_number']]);
            if ($chkM->fetch()) {
                return ['status' => false, 'message' => 'Matriculation number already exists.'];
            }
        }

        try {
            $this->db->beginTransaction();

            // Create user account (username = admission_number, password = phone or default)
            $defaultPassword = !empty($userData['password']) ? $userData['password'] : 'Student@123';
            $userId = $this->insert('users', [
                'username'      => $studentData['admission_number'],
                'password_hash' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                'role'          => 'Student',
                'status'        => 'Active',
            ]);

            // Create student record
            $studentData['user_id']       = $userId;
            $studentData['admission_date'] = $studentData['admission_date'] ?? date('Y-m-d');
            $studentData['nationality']   = $studentData['nationality'] ?? 'Nigerian';
            $studentData['state']         = $studentData['state'] ?? 'Not Set';

            $studentId = $this->insert('students', array_filter($studentData, fn($v) => $v !== null && $v !== ''));

            $this->db->commit();
            $this->logAudit('Student Created', 'students', $studentId, $studentData['full_name']);

            return ['status' => true, 'message' => 'Student record created successfully.', 'id' => $studentId, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'message' => 'Error creating student: ' . $e->getMessage()];
        }
    }

    public function updateStudent(int $id, array $data): array {
        // Prevent overwriting protected fields
        unset($data['user_id'], $data['id'], $data['created_at']);

        // Check uniqueness of matric number (if changed)
        if (!empty($data['matric_number'])) {
            $chk = $this->db->prepare("SELECT id FROM students WHERE matric_number=:mn AND id != :id LIMIT 1");
            $chk->execute(['mn' => $data['matric_number'], 'id' => $id]);
            if ($chk->fetch()) {
                return ['status' => false, 'message' => 'Matriculation number already used.'];
            }
        }

        $success = $this->update('students', $id, $data);
        if ($success) {
            $this->logAudit('Student Updated', 'students', $id);
        }
        return ['status' => $success, 'message' => $success ? 'Student record updated.' : 'Update failed.'];
    }

    public function updateStudentStatus(int $id, string $status): array {
        $allowed = ['Active', 'Suspended', 'Withdrawn', 'Graduated'];
        if (!in_array($status, $allowed)) {
            return ['status' => false, 'message' => 'Invalid status value.'];
        }
        $extra = [];
        if ($status === 'Graduated') {
            $extra['graduation_date'] = date('Y-m-d');
        }
        $data = array_merge(['status' => $status], $extra);
        $success = $this->update('students', $id, $data);
        $this->logAudit("Student Status Changed to $status", 'students', $id);
        return ['status' => $success, 'message' => $success ? "Student status updated to $status." : 'Failed to update status.'];
    }

    /* =========================================================
     * STAFF
     * ========================================================= */

    public function getStaff(): array {
        return $this->db->query("
            SELECT st.*, u.username, u.role, u.status AS account_status, u.last_login,
                   d.name AS department_name
            FROM staff st
            JOIN users u ON st.user_id = u.id
            LEFT JOIN departments d ON st.department_id = d.id
            ORDER BY st.full_name ASC
        ")->fetchAll();
    }

    public function createStaff(array $data): array {
        if (empty($data['username']) || empty($data['password']) || empty($data['full_name']) || empty($data['role'])) {
            return ['status' => false, 'message' => 'Username, password, full name, and role are required.'];
        }
        // Check username uniqueness
        $chk = $this->db->prepare("SELECT id FROM users WHERE username=:u LIMIT 1");
        $chk->execute(['u' => $data['username']]);
        if ($chk->fetch()) {
            return ['status' => false, 'message' => 'Username already exists.'];
        }
        try {
            $this->db->beginTransaction();
            $userId = $this->insert('users', [
                'username'      => $data['username'],
                'email'         => $data['email'] ?? null,
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'role'          => $data['role'],
                'status'        => 'Active',
            ]);
            $staffId = $this->insert('staff', [
                'user_id'        => $userId,
                'staff_id'       => $data['staff_id'] ?? null,
                'full_name'      => $data['full_name'],
                'email'          => $data['email'] ?? null,
                'phone'          => $data['phone'] ?? null,
                'department_id'  => !empty($data['department_id']) ? (int)$data['department_id'] : null,
                'qualification'  => $data['qualification'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'gender'         => $data['gender'] ?? null,
                'date_joined'    => $data['date_joined'] ?? date('Y-m-d'),
                'status'         => 'Active',
            ]);
            $this->db->commit();
            $this->logAudit('Staff Created', 'staff', $staffId, $data['full_name']);
            return ['status' => true, 'message' => 'Staff member created successfully.', 'id' => $staffId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateStaff(int $id, array $data): array {
        unset($data['user_id'], $data['id'], $data['created_at']);
        // Separate user fields
        $userFields  = [];
        $staffFields = [];
        foreach ($data as $k => $v) {
            if (in_array($k, ['role', 'status'])) {
                $userFields[$k] = $v;
            } elseif ($k === 'password' && !empty($v)) {
                $userFields['password_hash'] = password_hash($v, PASSWORD_BCRYPT);
            } else {
                $staffFields[$k] = $v;
            }
        }
        $staff = $this->fetchOne('staff', $id);
        if (!$staff) {
            return ['status' => false, 'message' => 'Staff not found.'];
        }
        if (!empty($userFields)) {
            $this->update('users', (int)$staff['user_id'], $userFields);
        }
        if (!empty($staffFields)) {
            $this->update('staff', $id, $staffFields);
        }
        $this->logAudit('Staff Updated', 'staff', $id);
        return ['status' => true, 'message' => 'Staff updated successfully.'];
    }

    /* =========================================================
     * ACADEMICS: FACULTIES, DEPARTMENTS, PROGRAMMES, COURSES
     * ========================================================= */

    public function getFaculties(): array {
        return $this->db->query("
            SELECT f.*, COUNT(d.id) AS dept_count 
            FROM faculties f LEFT JOIN departments d ON d.faculty_id = f.id 
            GROUP BY f.id ORDER BY f.name
        ")->fetchAll();
    }

    public function getDepartments(?int $facultyId = null): array {
        $sql    = "SELECT d.*, f.name AS faculty_name, COUNT(s.id) AS student_count
                   FROM departments d 
                   JOIN faculties f ON d.faculty_id = f.id
                   LEFT JOIN students s ON s.department_id = d.id";
        $params = [];
        if ($facultyId) {
            $sql   .= ' WHERE d.faculty_id = :fid';
            $params = ['fid' => $facultyId];
        }
        $sql .= ' GROUP BY d.id ORDER BY d.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getProgrammes(?int $deptId = null): array {
        $sql    = "SELECT p.*, d.name AS department_name, f.name AS faculty_name
                   FROM programmes p 
                   JOIN departments d ON p.department_id = d.id
                   JOIN faculties f ON d.faculty_id = f.id";
        $params = [];
        if ($deptId) {
            $sql   .= ' WHERE p.department_id = :did';
            $params = ['did' => $deptId];
        }
        $sql .= ' ORDER BY p.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCourses(array $filters = []): array {
        $where  = [];
        $params = [];
        if (!empty($filters['department_id'])) {
            $where[]                   = 'c.department_id = :did';
            $params['did']             = (int)$filters['department_id'];
        }
        if (!empty($filters['level'])) {
            $where[]                   = 'c.level = :level';
            $params['level']           = (int)$filters['level'];
        }
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("
            SELECT c.*, d.name AS department_name, p.name AS programme_name
            FROM courses c
            JOIN departments d ON c.department_id = d.id
            JOIN programmes p ON c.programme_id = p.id
            $whereSQL
            ORDER BY c.level, c.code
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSessions(): array {
        return $this->db->query("SELECT * FROM sessions ORDER BY name DESC, semester")->fetchAll();
    }

    public function setActiveSession(int $sessionId): array {
        $this->db->exec("UPDATE sessions SET is_active = 0");
        $this->db->prepare("UPDATE sessions SET is_active = 1 WHERE id = :id")->execute(['id' => $sessionId]);
        $this->logAudit('Active Session Changed', 'sessions', $sessionId);
        return ['status' => true, 'message' => 'Active session updated.'];
    }

    /* =========================================================
     * CERTIFICATES
     * ========================================================= */

    public function generateGraduationCertificate(int $studentId): array {
        // Check existing cert
        $stmt = $this->db->prepare("SELECT id FROM certificates WHERE student_id = :sid LIMIT 1");
        $stmt->execute(['sid' => $studentId]);
        if ($stmt->fetch()) {
            return ['status' => false, 'message' => 'Certificate already generated for this student.'];
        }

        // Get student info
        $student = $this->getStudentById($studentId);
        if (!$student) {
            return ['status' => false, 'message' => 'Student not found.'];
        }

        if ($student['status'] !== 'Graduated') {
            return ['status' => false, 'message' => 'Student must be marked as Graduated first.'];
        }

        // Generate certificate number
        $certNum = 'SAZUG-CERT-' . date('Y') . '-' . strtoupper(substr(md5($studentId . time()), 0, 8));

        // Generate QR code data URL
        $qrPath = null;
        $verifyUrl = 'https://sazug-srms.onrender.com/index.php?verify=' . urlencode($certNum);

        if (class_exists('\Endroid\QrCode\QrCode')) {
            try {
                $qrCode = \Endroid\QrCode\QrCode::create($verifyUrl)
                    ->setSize(200)->setMargin(10);
                $qrWriter = new \Endroid\QrCode\Writer\PngWriter();
                $result = $qrWriter->write($qrCode);
                $qrFile = 'uploads/certificates/qr_' . $studentId . '.png';
                $result->saveToFile(__DIR__ . '/' . $qrFile);
                $qrPath = $qrFile;
            } catch (Exception $e) {
                error_log('QR generation error: ' . $e->getMessage());
            }
        }

        // Save certificate
        $certId = $this->insert('certificates', [
            'student_id'         => $studentId,
            'certificate_number' => $certNum,
            'qr_code_path'       => $qrPath,
            'issue_date'         => date('Y-m-d'),
            'issued_by'          => $_SESSION['user_id'] ?? null,
        ]);

        $this->logAudit('Certificate Generated', 'certificates', $certId, "Student: {$student['full_name']}");
        return [
            'status'      => true,
            'message'     => 'Certificate generated successfully.',
            'cert_number' => $certNum,
            'cert_id'     => $certId,
            'verify_url'  => $verifyUrl,
        ];
    }

    public function verifyCertificate(string $certNumber): ?array {
        $stmt = $this->db->prepare("
            SELECT c.certificate_number, c.issue_date, c.qr_code_path,
                   s.full_name, s.matric_number, s.admission_number, s.graduation_date,
                   p.name AS programme, p.type AS programme_type,
                   d.name AS department, f.name AS faculty
            FROM certificates c
            JOIN students s ON c.student_id = s.id
            JOIN programmes p ON s.programme_id = p.id
            JOIN departments d ON s.department_id = d.id
            JOIN faculties f ON s.faculty_id = f.id
            WHERE c.certificate_number = :cert LIMIT 1
        ");
        $stmt->execute(['cert' => $certNumber]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /* =========================================================
     * FILE UPLOADS
     * ========================================================= */

    public function uploadDocument(int $studentId, array $file, string $type): array {
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($detectedMime, $allowedMimes)) {
            return ['status' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
        }
        if ($file['size'] > 5242880) {
            return ['status' => false, 'message' => 'File exceeds 5MB limit.'];
        }

        $ext      = match ($detectedMime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'application/pdf' => 'pdf',
            default           => 'bin'
        };
        $filename = uniqid('doc_' . $studentId . '_', true) . '.' . $ext;
        $dir      = ($type === 'Passport') ? 'uploads/passports/' : 'uploads/documents/';
        $destPath = $dir . $filename;

        if (move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $destPath)) {
            if ($type === 'Passport') {
                $this->update('students', $studentId, ['passport_path' => $destPath]);
            } else {
                $this->insert('documents', [
                    'student_id' => $studentId,
                    'type'       => $type,
                    'file_path'  => $destPath,
                ]);
            }
            $this->logAudit("Uploaded $type", 'students', $studentId);
            return ['status' => true, 'message' => 'File uploaded successfully.', 'path' => $destPath];
        }
        return ['status' => false, 'message' => 'Failed to save uploaded file.'];
    }

    /* =========================================================
     * AUDIT & SETTINGS
     * ========================================================= */

    public function getAuditLogs(int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC LIMIT :lim
        ");
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSystemUsers(): array {
        return $this->db->query("
            SELECT u.id, u.username, u.email, u.role, u.status, u.failed_attempts, u.last_login, u.created_at,
                   COALESCE(st.full_name, '') AS full_name
            FROM users u
            LEFT JOIN staff st ON st.user_id = u.id
            ORDER BY u.role, u.username
        ")->fetchAll();
    }

    public function toggleUserStatus(int $userId, string $status): array {
        $allowed = ['Active', 'Suspended'];
        if (!in_array($status, $allowed)) {
            return ['status' => false, 'message' => 'Invalid status.'];
        }
        $this->db->prepare("UPDATE users SET status=:s, failed_attempts=0, locked_until=NULL WHERE id=:id")
                 ->execute(['s' => $status, 'id' => $userId]);
        $this->logAudit("User Account $status", 'users', $userId);
        return ['status' => true, 'message' => "User account set to $status."];
    }
}
