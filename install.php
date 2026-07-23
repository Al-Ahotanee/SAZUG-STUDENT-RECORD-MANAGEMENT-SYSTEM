<?php
/**
 * SAZUG SRMS - One-Click Database Installer
 * Designed to execute schema.sql directly from the Render environment.
 */

$lockFile = __DIR__ . '/install.lock';
$message = '';
$status = '';

// SECURITY: Prevent re-running if already installed
if (file_exists($lockFile)) {
    $status = 'error';
    $message = "Installation has already been completed. For security reasons, please delete this <b>install.php</b> file from your repository.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    
    $host = getenv('DB_HOST');
    $db   = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');

    if (!$host || !$db || !$user) {
        $status = 'error';
        $message = "Database credentials are missing. Please ensure DB_HOST, DB_NAME, DB_USER, and DB_PASS are set in Render's Environment Variables.";
    } else {
        try {
            // Aiven requires SSL. PDO will automatically negotiate SSL if the server demands it.
            // We ensure MYSQL_ATTR_MULTI_STATEMENTS is enabled to run the whole file at once.
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ];
            
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            // Read the schema file
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("<b>schema.sql</b> file not found in the root directory.");
            }
            
            $sql = file_get_contents($schemaFile);
            
            // Execute the entire schema
            $pdo->exec($sql);
            
            // Create a lock file to prevent this script from running again
            file_put_contents($lockFile, "Installed successfully on " . date('Y-m-d H:i:s'));
            
            $status = 'success';
            $message = "Database schema imported successfully! The Super Administrator account has been created.";
            
        } catch (PDOException $e) {
            $status = 'error';
            $message = "Database Connection Error: " . htmlspecialchars($e->getMessage());
        } catch (Exception $e) {
            $status = 'error';
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAZUG SRMS - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .install-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; width: 100%; padding: 40px; background: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="install-card mx-auto text-center">
        <i class="fas fa-database fa-4x text-primary mb-4"></i>
        <h2 class="fw-bold mb-3">Database Setup</h2>
        <p class="text-muted mb-4">This tool will connect to your Aiven MySQL database using Render's Environment Variables and execute your schema to create all tables.</p>
        
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?= $message ?>
            </div>
            <a href="index.php" class="btn btn-success w-100 py-2 mt-3">Go to Login Portal</a>
            <p class="text-danger mt-3 small"><i class="fas fa-exclamation-triangle"></i> Important: Delete <b>install.php</b> from your GitHub repository now to secure your system.</p>
        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-danger text-start">
                <i class="fas fa-times-circle me-2"></i> <?= $message ?>
            </div>
            <?php if (!file_exists($lockFile)): ?>
                <a href="install.php" class="btn btn-outline-primary mt-3">Try Again</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($status !== 'success' && !file_exists($lockFile)): ?>
            <form method="POST" action="">
                <button type="submit" name="install" value="1" class="btn btn-primary w-100 py-2 fs-5">
                    <i class="fas fa-play me-2"></i> Run Installer
                </button>
            </form>
            <div class="text-start mt-4 bg-light p-3 rounded small border">
                <strong>Current Environment Check:</strong><br>
                Host: <code><?= getenv('DB_HOST') ? 'Set' : 'Missing' ?></code><br>
                Database Name: <code><?= getenv('DB_NAME') ? 'Set' : 'Missing' ?></code><br>
                Username: <code><?= getenv('DB_USER') ? 'Set' : 'Missing' ?></code><br>
                Password: <code><?= getenv('DB_PASS') ? 'Set (Hidden)' : 'Missing' ?></code>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
