<?php
/**
 * SAZUG Student Record Management System - SystemCore
 * Core system functionality: database connection, authentication, utilities
 */

class SystemCore {
    private static $instance = null;
    private $pdo = null;
    private $config = [];
    
    private function __construct() {
        $this->loadConfig();
        $this->connectDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        $envFile = dirname(__FILE__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $this->config[trim($key)] = trim($value);
            }
        }
        
        // Defaults
        $this->config['DB_HOST'] = $this->config['DB_HOST'] ?? 'localhost';
        $this->config['DB_PORT'] = $this->config['DB_PORT'] ?? '3306';
        $this->config['DB_NAME'] = $this->config['DB_NAME'] ?? 'sazug_srms';
        $this->config['DB_USER'] = $this->config['DB_USER'] ?? 'root';
        $this->config['DB_PASS'] = $this->config['DB_PASS'] ?? '';
        $this->config['APP_KEY'] = $this->config['APP_KEY'] ?? $this->generateKey();
        $this->config['APP_URL'] = $this->config['APP_URL'] ?? '';
    }
    
    private function connectDatabase() {
        try {
            $dsn = "mysql:host={$this->config['DB_HOST']};port={$this->config['DB_PORT']};dbname={$this->config['DB_NAME']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->config['DB_USER'], $this->config['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getDB() {
        return $this->pdo;
    }
    
    public function getConfig($key = null) {
        if ($key) return $this->config[$key] ?? null;
        return $this->config;
    }
    
    // ==================== Authentication ====================
    
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $token = $this->generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            return [
                'token' => $token,
                'expires' => $expires,
                'user' => $this->sanitizeUser($user)
            ];
        }
        return false;
    }
    
    public function authenticate($token) {
        if (!$token) return false;
        // Simple token validation - in production use JWT
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $userId = $this->extractUserId($token);
        if (!$userId) return false;
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            return $this->sanitizeUser($user);
        }
        return false;
    }
    
    public function requireRole($roles, $currentUser) {
        if (!$currentUser) return false;
        $allowed = is_array($roles) ? $roles : [$roles];
        return in_array($currentUser['role'], $allowed);
    }
    
    // ==================== User Helpers ====================
    
    private function sanitizeUser($user) {
        unset($user['password']);
        unset($user['password_reset_token']);
        unset($user['password_reset_expires']);
        return $user;
    }
    
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function extractUserId($token) {
        // Simple token-to-user mapping via session table or just decode
        // For this system, token is stored client-side with user ID
        $parts = explode('.', $token);
        if (count($parts) >= 2) {
            $decoded = base64_decode($parts[0]);
            if (is_numeric($decoded)) return (int) $decoded;
        }
        return null;
    }
    
    public function createAuthToken($userId) {
        $random = bin2hex(random_bytes(16));
        return base64_encode($userId) . '.' . $random;
    }
    
    private function generateKey() {
        return bin2hex(random_bytes(32));
    }
    
    // ==================== Settings ====================
    
    public function getSettings() {
        $stmt = $this->pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            // Create default
            $this->pdo->exec("INSERT INTO settings (institution_name) VALUES ('SAZUG University')");
            $stmt = $this->pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch();
        }
        return $settings;
    }
    
    // ==================== Audit Log ====================
    
    public function audit($action, $details = null, $userId = null, $userName = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $this->pdo->prepare("INSERT INTO audit_log (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $userName, $action, $details, $ip]);
    }
    
    // ==================== Utilities ====================
    
    public function generateStudentId($prefix = 'SAZUG') {
        $year = date('Y');
        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM students WHERE student_id_number LIKE '{$prefix}/{$year}/%'");
        $row = $stmt->fetch();
        $next = str_pad(($row['cnt'] + 1), 4, '0', STR_PAD_LEFT);
        return "{$prefix}/{$year}/{$next}";
    }
    
    public function generateCertificateNumber() {
        $year = date('Y');
        $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM students WHERE certificate_number LIKE 'CERT/{$year}/%'");
        $row = $stmt->fetch();
        $next = str_pad(($row['cnt'] + 1), 4, '0', STR_PAD_LEFT);
        return "CERT/{$year}/{$next}";
    }
    
    public function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        return strtolower($text);
    }
    
    public function validateFileUpload($file, $maxSizeMB = 5, $allowedTypes = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSizeMB * 1024 * 1024) {
            return ['success' => false, 'error' => "File exceeds maximum size of {$maxSizeMB}MB"];
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            return ['success' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowedTypes)];
        }
        
        return ['success' => true, 'extension' => $ext];
    }
    
    public function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
    
    public function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public function getInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!$data) {
            $data = $_POST;
        }
        return $data;
    }
}
