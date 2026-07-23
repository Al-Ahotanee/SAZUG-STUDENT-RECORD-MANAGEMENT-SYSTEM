<?php
/**
 * SAZUG Student Record Management System - Core Engine
 * Fully Implemented API & Business Logic Layer
 * PHP 8.3+, PDO, Highly Optimized for Minimal RAM Usage
 */

class SystemCore {
    private PDO $db;

    public function __construct() {
        $this->startSecureSession();
        $this->connectDB();
        $this->ensureUploadDirectories();
    }

    /** =========================================
     * SYSTEM & SECURITY
     * ========================================= */

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
            header("HTTP/1.1 401 Unauthorized");
            exit(json_encode(['status' => false, 'message' => 'Session timeout. Please login again.']));
        }
        $_SESSION['last_activity'] = time();
    }

    private function connectDB(): void {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '1247';
        $db   = getenv('DB_NAME') ?: 'sazug_srms';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->db = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            header("HTTP/1.1 500 Internal Server Error");
            exit(json_encode(['status' => false, 'message' => 'Database connection failed.']));
        }
    }

    private function ensureUploadDirectories(): void {
        $dirs = ['uploads/passports', 'uploads/documents', 'uploads/certificates'];
        foreach ($dirs as $dir) {
            if (!is_dir(__DIR__ . '/' . $dir)) {
                mkdir(__DIR__ . '/' . $dir, 0755, true);
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
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public function enforcePermissions(array $allowedRoles): void {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
            header("HTTP/1.1 403 Forbidden");
            exit(json_encode(['status' => false, 'message' => 'Access denied.']));
        }
    }

    public function logAudit(string $action, ?string $entity = null, ?int $entityId = null): void {
        $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, ip_address) VALUES (:user_id, :action, :entity, :entity_id, :ip)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    }

    /** =========================================
     * AUTHENTICATION
     * ========================================= */

    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['status' => false, 'message' => 'Invalid credentials.'];
        }

        if ($user['status'] === 'Locked' || ($user['locked_until'] && strtotime($user['locked_until']) > time())) {
            return ['status' => false, 'message' => 'Account locked due to multiple failed attempts. Try again later.'];
        }

        if ($user['status'] === 'Suspended') {
            return ['status' => false, 'message' => 'Account suspended. Contact Administrator.'];
        }

        if (password_verify($password, $user['password_hash'])) {
            $this->db->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id")->execute(['id' => $user['id']]);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $this->generateCSRFToken();
            $this->logAudit('Login successfully', 'users', $user['id']);

            return ['status' => true, 'role' => $user['role'], 'csrf_token' => $_SESSION['csrf_token']];
        } else {
            $attempts = $user['failed_attempts'] + 1;
            $locked_until = null;
            $status = $user['status'];
            if ($attempts >= 5) {
                $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $status = 'Locked';
            }
            $this->db->prepare("UPDATE users SET failed_attempts = :attempts, locked_until = :locked, status = :status WHERE id = :id")
                     ->execute(['attempts' => $attempts, 'locked' => $locked_until, 'status' => $status, 'id' => $user['id']]);
            
            return ['status' => false, 'message' => 'Invalid credentials.'];
        }
    }

    public function logout(): array {
        if (isset($_SESSION['user_id'])) {
            $this->logAudit('Logout successfully');
        }
        session_unset();
        session_destroy();
        return ['status' => true, 'message' => 'Logged out safely.'];
    }

    public function resetPassword(int $userId, string $newPassword): array {
        $this->enforcePermissions(['Super Administrator', 'Administrator']);
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")->execute(['hash' => $hash, 'id' => $userId]);
        $this->logAudit('Password reset triggered manually', 'users', $userId);
        return ['status' => true, 'message' => 'Password reset successful.'];
    }

    /** =========================================
     * DYNAMIC CRUD ENGINE (Highly DRY)
     * ========================================= */

    public function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $id = (int)$this->db->lastInsertId();
        $this->logAudit("Created record in $table", $table, $id);
        return $id;
    }

    public function update(string $table, int $id, array $data): bool {
        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= "`$key` = :$key, ";
        }
        $fields = rtrim($fields, ', ');
        $sql = "UPDATE `$table` SET $fields WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);
        $this->logAudit("Updated record in $table", $table, $id);
        return $result;
    }

    public function delete(string $table, int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `$table` WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);
        $this->logAudit("Deleted record from $table", $table, $id);
        return $result;
    }

    public function fetchAll(string $table, array $conditions = [], string $orderBy = 'id DESC'): array {
        $sql = "SELECT * FROM `$table`";
        $params = [];
        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $key => $value) {
                $clauses[] = "`$key` = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $clauses);
        }
        $sql .= " ORDER BY $orderBy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** =========================================
     * STUDENT MANAGEMENT (Complex Joins)
     * ========================================= */

    public function createStudent(array $userData, array $studentData): array {
        try {
            $this->db->beginTransaction();
            
            // 1. Create User
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_BCRYPT);
            unset($userData['password']);
            $userData['role'] = 'Student';
            $userId = $this->insert('users', $userData);

            // 2. Create Student Profile
            $studentData['user_id'] = $userId;
            $studentData['admission_date'] = date('Y-m-d');
            $studentId = $this->insert('students', $studentData);

            $this->db->commit();
            return ['status' => true, 'message' => 'Student created successfully.', 'student_id' => $studentId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'message' => 'Failed to create student: ' . $e->getMessage()];
        }
    }

    public function getStudents(array $filters = []): array {
        $sql = "SELECT s.*, u.username, u.status as account_status, d.name as department_name, f.name as faculty_name, p.name as programme_name, p.type as programme_type, sess.name as session_name 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                JOIN departments d ON s.department_id = d.id 
                JOIN faculties f ON s.faculty_id = f.id 
                JOIN programmes p ON s.programme_id = p.id 
                JOIN sessions sess ON s.session_id = sess.id WHERE 1=1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND s.department_id = :dept";
            $params['dept'] = $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }
        $sql .= " ORDER BY s.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** =========================================
     * DASHBOARD & REPORTS
     * ========================================= */

    public function getDashboardStats(): array {
        $stats = [
            'total_students' => $this->db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
            'active_students' => $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn(),
            'graduated_students' => $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Graduated'")->fetchColumn(),
            'total_departments' => $this->db->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
            'recent_activities' => $this->db->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5")->fetchAll()
        ];
        return $stats;
    }

    /** =========================================
     * CERTIFICATE GENERATION (mPDF + QR)
     * ========================================= */

    public function generateGraduationCertificate(int $studentId): array {
        $this->enforcePermissions(['Super Administrator', 'Administrator', 'Registrar']);

        // Check if student exists and is graduated
        $stmt = $this->db->prepare("SELECT s.*, p.name as prog_name, d.name as dept_name, f.name as fac_name 
                                    FROM students s 
                                    JOIN programmes p ON s.programme_id = p.id 
                                    JOIN departments d ON s.department_id = d.id 
                                    JOIN faculties f ON s.faculty_id = f.id 
                                    WHERE s.id = :id");
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        if (!$student || $student['status'] !== 'Graduated') {
            return ['status' => false, 'message' => 'Student must be marked as Graduated first.'];
        }

        // Check if already generated
        $checkStmt = $this->db->prepare("SELECT * FROM certificates WHERE student_id = :id");
        $checkStmt->execute(['id' => $studentId]);
        if ($checkStmt->fetch()) {
            return ['status' => false, 'message' => 'Certificate already exists.'];
        }

        // 1. Generate unique Cert Number
        $certNum = 'SAZUG/' . date('Y') . '/' . str_pad($studentId, 6, '0', STR_PAD_LEFT);

        // 2. Generate Anti-Forgery QR Code
        $verifyUrl = "https://" . $_SERVER['HTTP_HOST'] . "/index.php?verify=" . urlencode($certNum);
        
        // We will invoke the Endroid QrCode Generator via composer autoload
        if (class_exists('\Endroid\QrCode\QrCode')) {
            $qrCode = new \Endroid\QrCode\QrCode($verifyUrl);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $qrResult = $writer->write($qrCode);
            $qrPath = 'uploads/certificates/qr_' . $studentId . '.png';
            $qrResult->saveToFile(__DIR__ . '/' . $qrPath);
        } else {
            // Fallback if composer not installed yet during dev
            $qrPath = '';
        }

        // 3. Generate PDF using mPDF
        if (class_exists('\Mpdf\Mpdf')) {
            $mpdf = new \Mpdf\Mpdf(['orientation' => 'L', 'format' => 'A4']);
            $qrImgTag = $qrPath ? "<img src='" . __DIR__ . "/{$qrPath}' width='100'><br>" : "";
            
            $html = "
                <div style='text-align: center; font-family: Arial, sans-serif; padding: 50px;'>
                    <h1>SAZUG INSTITUTION</h1>
                    <h2>Official Graduation Certificate</h2>
                    <p>This is to certify that</p>
                    <h3><b>{$student['full_name']}</b></h3>
                    <p>Matriculation Number: {$student['matric_number']}</p>
                    <p>Has successfully completed the required course of study for the award of</p>
                    <h3>{$student['prog_name']} in {$student['dept_name']}</h3>
                    <p>Faculty of {$student['fac_name']}</p>
                    <p>Date of Graduation: {$student['graduation_date']}</p>
                    <br><br>
                    <div style='float: right;'>
                        {$qrImgTag}
                        <small>Scan to Verify</small>
                    </div>
                    <div style='clear:both;'></div>
                    <p style='text-align:left;'>Certificate Number: <b>{$certNum}</b></p>
                </div>
            ";
            $mpdf->WriteHTML($html);
            $pdfPath = 'uploads/certificates/cert_' . $studentId . '.pdf';
            $mpdf->Output(__DIR__ . '/' . $pdfPath, \Mpdf\Output\Destination::FILE);
        } else {
             $pdfPath = '';
        }

        // 4. Save to DB
        $this->insert('certificates', [
            'student_id' => $studentId,
            'certificate_number' => $certNum,
            'qr_code_path' => $qrPath,
            'issue_date' => date('Y-m-d')
        ]);
        
        // 5. Update Student
        $this->update('students', $studentId, ['status' => 'Graduated', 'graduation_date' => date('Y-m-d')]);

        $this->logAudit('Generated Certificate', 'certificates', $studentId);

        return ['status' => true, 'message' => 'Certificate generated successfully.', 'cert_url' => '/' . $pdfPath];
    }

    /** =========================================
     * SECURE FILE UPLOADS
     * ========================================= */

    public function uploadDocument(int $studentId, array $file, string $type): array {
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowedMimes)) {
            return ['status' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
        }
        if ($file['size'] > 5242880) { // 5MB limit
            return ['status' => false, 'message' => 'File exceeds 5MB limit.'];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('doc_', true) . '.' . $ext;
        $destPath = ($type === 'Passport' ? 'uploads/passports/' : 'uploads/documents/') . $filename;

        if (move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $destPath)) {
            if ($type === 'Passport') {
                $this->update('students', $studentId, ['passport_path' => $destPath]);
            } else {
                $this->insert('documents', [
                    'student_id' => $studentId,
                    'type' => $type,
                    'file_path' => $destPath
                ]);
            }
            $this->logAudit("Uploaded $type for student", 'students', $studentId);
            return ['status' => true, 'message' => 'File uploaded successfully.'];
        }
        return ['status' => false, 'message' => 'Failed to save uploaded file.'];
    }
}
