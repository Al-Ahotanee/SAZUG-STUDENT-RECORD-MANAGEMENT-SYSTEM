<?php
/**
 * SAZUG SRMS — Database Installer & Reset Tool
 * One-click: wipe all tables, import schema.sql, seed default admin.
 * SECURITY: Delete this file after successful installation.
 */

$lockFile = __DIR__ . '/install.lock';
$message  = '';
$status   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['install'] ?? false)) {

    $host = trim(getenv('DB_HOST') ?: 'localhost');
    $port = trim(getenv('DB_PORT') ?: '12417');
    $db   = trim(getenv('DB_NAME') ?: 'sazug_srms');
    $user = trim(getenv('DB_USER') ?: 'root');
    $pass = trim(getenv('DB_PASS') ?: '');
    $ssl  = trim(getenv('DB_SSL')  ?: 'false');

    if (!$host || !$db || !$user) {
        $status  = 'error';
        $message = 'Database credentials are missing. Ensure DB_HOST, DB_NAME, DB_USER, and DB_PASS are set as environment variables.';
    } else {
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4" . ($ssl === 'true' ? ';sslmode=require' : '');
            $options = [
                PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Wipe existing tables
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `$t`");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // Import schema
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception('<b>schema.sql</b> not found in the root directory.');
            }
            $pdo->exec(file_get_contents($schemaFile));

            // Regenerate fresh admin hash for this PHP runtime
            $adminHash = password_hash('Admin@123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES ('superadmin', 'admin@sazug.edu.ng', ?, 'Super Administrator', 'Active') ON DUPLICATE KEY UPDATE password_hash = ?");
            $stmt->execute([$adminHash, $adminHash]);
            $pdo->prepare("INSERT IGNORE INTO staff (user_id, full_name, phone, status) SELECT id, 'System Super Administrator', '08000000000', 'Active' FROM users WHERE username='superadmin' LIMIT 1")->execute();

            file_put_contents($lockFile, 'Installed on ' . date('Y-m-d H:i:s'));
            $status  = 'success';
            $message = 'Database installed successfully! Super Administrator account is ready. <strong>Username:</strong> superadmin &nbsp;|&nbsp; <strong>Password:</strong> Admin@123';
        } catch (PDOException $e) {
            $status  = 'error';
            $message = 'Database Error: ' . htmlspecialchars($e->getMessage());
        } catch (Exception $e) {
            $status  = 'error';
            $message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SAZUG SRMS — Database Installer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.install-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.1);max-width:680px;width:100%;overflow:hidden}
.install-header{background:linear-gradient(135deg,#0a2540,#0f3460);padding:36px 40px;text-align:center;color:#fff}
.install-header .icon{width:72px;height:72px;background:rgba(255,255,255,.12);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px}
.install-body{padding:36px 40px}
.env-badge{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:.83rem}
.env-badge .env-key{color:#64748b;font-weight:500}
.env-badge .env-val{font-weight:700;color:#1a202c}
.env-badge .env-missing{color:#f97316;font-weight:700}
.btn-install{background:linear-gradient(135deg,#0f3460,#1d4ed8);color:#fff;border:none;border-radius:12px;padding:15px;font-weight:700;font-size:1rem;width:100%;transition:opacity .3s;margin-top:20px}
.btn-install:hover{opacity:.9;color:#fff}
</style>
</head>
<body>
<div class="install-card">
    <div class="install-header">
        <div class="icon"><i class="fas fa-database"></i></div>
        <h3 class="fw-bold mb-1">SAZUG SRMS Installer</h3>
        <p style="opacity:.7;font-size:.9rem;margin:0">Database setup and schema import tool</p>
    </div>
    <div class="install-body">

        <?php if ($status === 'success'): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i><?= $message ?>
        </div>
        <a href="index.php" class="btn btn-success w-100 rounded-pill py-3 fw-700 mb-3">
            <i class="fas fa-rocket me-2"></i>Go to Login Portal
        </a>
        <div class="alert alert-danger border-0 rounded-3" style="font-size:.85rem">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Security Warning:</strong> Delete or rename <code>install.php</code> immediately after installation to prevent unauthorized access.
        </div>
        <?php else: ?>

        <?php if ($status === 'error'): ?>
        <div class="alert alert-danger border-0 rounded-3 mb-4">
            <i class="fas fa-times-circle me-2"></i><?= $message ?>
        </div>
        <?php endif; ?>

        <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:24px">
            This tool will <strong>wipe all existing tables</strong> and reimport the full database schema fresh. 
            Use this for a clean installation or to reset the database. Ensure your environment variables are configured before proceeding.
        </p>

        <!-- Environment Check -->
        <h6 style="font-weight:700;font-size:.82rem;color:#374151;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Environment Variables</h6>
        <div class="d-flex flex-column gap-2 mb-4">
            <?php
            $envVars = [
                'DB_HOST' => getenv('DB_HOST') ?: null,
                'DB_PORT' => getenv('DB_PORT') ?: '12417 (default)',
                'DB_NAME' => getenv('DB_NAME') ?: null,
                'DB_USER' => getenv('DB_USER') ?: null,
                'DB_PASS' => getenv('DB_PASS') ? '****** (set)' : null,
                'DB_SSL'  => getenv('DB_SSL') ?: 'false (default)',
            ];
            foreach ($envVars as $key => $val): ?>
            <div class="env-badge">
                <span class="env-key"><?= $key ?></span>
                <?php if ($val): ?>
                    <span class="env-val"><?= htmlspecialchars($val) ?></span>
                <?php else: ?>
                    <span class="env-missing"><i class="fas fa-times me-1"></i>Not Set</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-warning border-0 rounded-3" style="font-size:.85rem;margin-bottom:20px">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> This action will permanently delete all existing data. It cannot be undone.
        </div>

        <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure? All existing data will be permanently deleted.')">
            <button type="submit" name="install" value="1" class="btn-install">
                <i class="fas fa-sync-alt me-2"></i>Wipe Database & Run Installer
            </button>
        </form>

        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" style="font-size:.82rem;color:#94a3b8;text-decoration:none"><i class="fas fa-arrow-left me-1"></i>Back to Login Portal</a>
        </div>
    </div>
</div>
</body>
</html>
