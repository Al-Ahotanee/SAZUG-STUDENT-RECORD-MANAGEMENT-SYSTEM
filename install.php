<?php
/**
 * SAZUG Student Record Management System - Web Installer
 * Handles database setup and initial configuration
 */

$installLock = __DIR__ . '/install.lock';
if (file_exists($installLock)) {
    header('Location: index.php');
    exit;
}

$step = $_GET['step'] ?? '1';
$error = '';
$success = '';

// Step 2: Process form and create .env + database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? 'sazug_srms');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $appUrl = trim($_POST['app_url'] ?? '');

    // Validate
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = 'Database host, name, and user are required.';
    } else {
        // Test connection
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Run schema
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                $error = 'schema.sql not found. Please ensure it exists in the same directory.';
            } else {
                $schema = file_get_contents($schemaFile);
                $pdo->exec($schema);

                // Create .env file
                $appKey = bin2hex(random_bytes(32));
                $envContent = "DB_HOST={$dbHost}\n";
                $envContent .= "DB_PORT={$dbPort}\n";
                $envContent .= "DB_NAME={$dbName}\n";
                $envContent .= "DB_USER={$dbUser}\n";
                $envContent .= "DB_PASS={$dbPass}\n";
                $envContent .= "APP_KEY={$appKey}\n";
                $envContent .= "APP_URL={$appUrl}\n";
                file_put_contents(__DIR__ . '/.env', $envContent);

                // Create superadmin user
                $hashedPassword = password_hash('Admin@123', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, 'superadmin', 1)");
                $stmt->execute(['superadmin', 'admin@sazug.edu.ng', $hashedPassword, 'System Administrator']);

                // Create install lock
                file_put_contents($installLock, date('Y-m-d H:i:s'));

                $success = true;
                $step = '3';
            }
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG SRMS - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#1a365d;--primary-light:#2a4a7f;--gold:#c9973f;--gold-light:#daa84e}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a365d 50%,#2a4a7f 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
        .installer{background:#fff;border-radius:24px;max-width:620px;width:100%;box-shadow:0 30px 80px rgba(0,0,0,.3);overflow:hidden}
        .installer-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));padding:2.5rem;text-align:center;color:#fff;position:relative;overflow:hidden}
        .installer-header::before{content:'';position:absolute;top:-50%;right:-20%;width:200px;height:200px;border-radius:50%;background:rgba(201,151,63,.15)}
        .installer-header::after{content:'';position:absolute;bottom:-30%;left:-10%;width:150px;height:150px;border-radius:50%;background:rgba(201,151,63,.1)}
        .installer-header i{font-size:3rem;color:var(--gold);margin-bottom:1rem;display:block;position:relative;z-index:1}
        .installer-header h1{font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;margin-bottom:.3rem;position:relative;z-index:1}
        .installer-header p{opacity:.75;font-size:.95rem;position:relative;z-index:1}
        .installer-body{padding:2rem 2.5rem}
        .step-indicator{display:flex;justify-content:center;gap:.5rem;margin-bottom:2rem}
        .step-dot{width:40px;height:5px;border-radius:3px;background:#e0e0e0;transition:all .3s}
        .step-dot.active{background:var(--gold);width:60px}
        .step-dot.done{background:var(--primary)}
        .form-floating{margin-bottom:1.2rem}
        .form-control{border:2px solid #e0e0e0;border-radius:12px;padding:1rem .75rem .5rem;font-size:.95rem;transition:all .3s}
        .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,151,63,.15)}
        .form-floating>label{color:#888;font-size:.82rem;font-weight:500;padding-left:.75rem}
        .btn-install{background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--primary-dark);font-weight:700;padding:.85rem;border-radius:12px;border:none;font-size:1rem;transition:all .3s;width:100%}
        .btn-install:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(201,151,63,.3)}
        .btn-install:disabled{opacity:.6;transform:none}
        .requirements{list-style:none;padding:0}
        .requirements li{padding:.5rem 0;display:flex;align-items:center;gap:10px;font-size:.9rem;border-bottom:1px solid #f0f0f0}
        .requirements li:last-child{border-bottom:none}
        .req-ok{color:#16a34a}.req-fail{color:#dc2626}.req-icon{font-size:1.1rem}
        .success-card{text-align:center;padding:2rem 0}
        .success-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#16a34a,#22c55e);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem;color:#fff}
        .cred-box{background:#f8f9fa;border-radius:12px;padding:1.2rem;margin:1.5rem 0;text-align:left;border:2px dashed #e0e0e0}
        .cred-box h6{color:var(--primary);font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:.8rem}
        .cred-row{display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem}
        .cred-row .label{color:#888;font-weight:500}
        .cred-row .value{font-weight:600;color:var(--primary)}
        .warning-alert{background:#fef3c7;border:1px solid #fbbf24;border-radius:12px;padding:1rem;display:flex;align-items:center;gap:10px;font-size:.85rem;color:#92400e;margin-bottom:1.5rem}
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <i class="bi bi-mortarboard-fill"></i>
            <h1>SAZUG SRMS Installation</h1>
            <p>Student Record Management System Setup Wizard</p>
        </div>
        <div class="installer-body">
            <div class="step-indicator">
                <div class="step-dot <?php echo $step >= '1' ? ($step > '1' ? 'done' : 'active') : ''; ?>"></div>
                <div class="step-dot <?php echo $step >= '2' ? ($step > '2' ? 'done' : 'active') : ''; ?>"></div>
                <div class="step-dot <?php echo $step >= '3' ? 'active' : ''; ?>"></div>
            </div>

            <?php if ($step === '1'): ?>
            <!-- Step 1: Requirements Check -->
            <h5 class="mb-3"><i class="bi bi-check-circle me-2" style="color:var(--gold)"></i>System Requirements</h5>
            <ul class="requirements mb-4">
                <?php
                $checks = [
                    ['PHP Version >= 7.4', version_compare(PHP_VERSION, '7.4', '>=')],
                    ['PDO MySQL Extension', extension_loaded('pdo_mysql')],
                    ['PDO Extension', extension_loaded('pdo')],
                    ['JSON Extension', extension_loaded('json')],
                    ['MBString Extension', extension_loaded('mbstring')],
                    ['File Upload Support', ini_get('file_uploads')],
                    ['Writable uploads/ directory', is_writable(__DIR__ . '/uploads')],
                    ['Writable root directory', is_writable(__DIR__)],
                ];
                $allOk = true;
                foreach ($checks as $check) {
                    $ok = $check[1];
                    if (!$ok) $allOk = false;
                    echo '<li><i class="bi bi-' . ($ok ? 'check-circle-fill req-ok' : 'x-circle-fill req-fail') . ' req-icon"></i><span>' . $check[0] . '</span><span class="ms-auto">' . ($ok ? '<strong style="color:#16a34a">OK</strong>' : '<strong style="color:#dc2626">Missing</strong>') . '</span></li>';
                }
                ?>
            </ul>
            <p class="text-muted small mb-4">PHP Version: <?php echo PHP_VERSION; ?></p>
            <?php if ($allOk): ?>
            <a href="?step=2" class="btn btn-install"><i class="bi bi-arrow-right me-2"></i>Continue to Database Setup</a>
            <?php else: ?>
            <div class="alert alert-danger">Please fix the missing requirements above before continuing.</div>
            <?php endif; ?>

            <?php elseif ($step === '2'): ?>
            <!-- Step 2: Database Configuration -->
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <h5 class="mb-3"><i class="bi bi-database me-2" style="color:var(--gold)"></i>Database Configuration</h5>
            <form method="POST" action="?step=2">
                <div class="form-floating">
                    <input type="text" class="form-control" name="db_host" id="dbHost" value="localhost" required>
                    <label for="dbHost">Database Host</label>
                </div>
                <div class="form-floating">
                    <input type="text" class="form-control" name="db_port" id="dbPort" value="3306" required>
                    <label for="dbPort">Database Port</label>
                </div>
                <div class="form-floating">
                    <input type="text" class="form-control" name="db_name" id="dbName" value="sazug_srms" required>
                    <label for="dbName">Database Name</label>
                </div>
                <div class="form-floating">
                    <input type="text" class="form-control" name="db_user" id="dbUser" value="root" required>
                    <label for="dbUser">Database Username</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" name="db_pass" id="dbPass" placeholder="Leave empty if no password">
                    <label for="dbPass">Database Password</label>
                </div>
                <div class="form-floating">
                    <input type="url" class="form-control" name="app_url" id="appUrl" placeholder="https://your-domain.com">
                    <label for="appUrl">Application URL (optional)</label>
                </div>
                <button type="submit" class="btn btn-install mt-2"><i class="bi bi-gear-fill me-2"></i>Install & Setup Database</button>
            </form>

            <?php elseif ($step === '3'): ?>
            <!-- Step 3: Success -->
            <div class="success-card">
                <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                <h3 style="color:var(--primary);font-family:'Playfair Display',serif">Installation Complete!</h3>
                <p class="text-muted mt-2 mb-0">SAZUG SRMS has been successfully installed and configured.</p>
            </div>
            <div class="cred-box">
                <h6><i class="bi bi-key me-2"></i>Default Login Credentials</h6>
                <div class="cred-row"><span class="label">Username:</span><span class="value">superadmin</span></div>
                <div class="cred-row"><span class="label">Password:</span><span class="value">Admin@123</span></div>
                <div class="cred-row"><span class="label">Role:</span><span class="value">Super Administrator</span></div>
                <div class="cred-row"><span class="label">Email:</span><span class="value">admin@sazug.edu.ng</span></div>
            </div>
            <div class="warning-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><strong>Important:</strong> Please change the default password immediately after your first login. Delete the <code>install.php</code> file for security.</div>
            </div>
            <div class="d-flex gap-3">
                <a href="index.php" class="btn btn-install flex-grow-1"><i class="bi bi-box-arrow-in-right me-2"></i>Go to Login Page</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
