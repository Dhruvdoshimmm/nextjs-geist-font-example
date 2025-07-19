<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if setup is already done
$setup_complete = false;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetchColumn();
    if ($admin_count > 0) {
        $setup_complete = true;
    }
} catch (Exception $e) {
    // Tables don't exist yet, continue with setup
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setup_complete) {
    try {
        // Create tables
        createTables($pdo);
        
        // Insert default categories
        insertDefaultCategories($pdo);
        
        // Create admin user
        createAdminUser($pdo);
        
        // Add activity logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        $setup_complete = true;
        $success_message = "Database setup completed successfully!";
        
    } catch (Exception $e) {
        $error_message = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width: 600px; margin: 4rem auto; padding: 2rem;">
        <div class="card">
            <div class="card-header text-center">
                <h1><?php echo SITE_NAME; ?></h1>
                <h2>Database Setup</h2>
            </div>
            
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                        <div class="mt-3">
                            <h5>Default Admin Account:</h5>
                            <p><strong>Email:</strong> admin@collegeworkhelper.com</p>
                            <p><strong>Password:</strong> admin123</p>
                            <div class="mt-3">
                                <a href="user/index.php" class="btn btn-primary">Go to Homepage</a>
                                <a href="user/login.php" class="btn btn-outline-primary">Admin Login</a>
                            </div>
                        </div>
                    </div>
                <?php elseif ($setup_complete): ?>
                    <div class="alert alert-info">
                        <h5>Setup Already Complete</h5>
                        <p>The database has already been set up. You can now use the application.</p>
                        <div class="mt-3">
                            <a href="user/index.php" class="btn btn-primary">Go to Homepage</a>
                            <a href="user/login.php" class="btn btn-outline-primary">Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <h4>Welcome to <?php echo SITE_NAME; ?>!</h4>
                        <p>This setup will create the necessary database tables and default data for your college work booking platform.</p>
                        
                        <h5>What will be created:</h5>
                        <ul>
                            <li>Database tables for users, orders, categories, payments, etc.</li>
                            <li>Default service categories (Essays, Research Papers, etc.)</li>
                            <li>Admin user account</li>
                            <li>System configuration</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <strong>Important:</strong> Make sure your database connection settings in <code>includes/config.php</code> are correct before proceeding.
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Initialize Database
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- System Requirements -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>System Requirements Check</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge <?php echo version_compare(PHP_VERSION, '8.0.0', '>=') ? 'badge-success' : 'badge-danger'; ?>">
                        PHP <?php echo PHP_VERSION; ?> (Required: 8.0+)
                    </span>
                </div>
                
                <div class="mb-2">
                    <span class="badge <?php echo extension_loaded('pdo') ? 'badge-success' : 'badge-danger'; ?>">
                        PDO Extension <?php echo extension_loaded('pdo') ? 'Available' : 'Missing'; ?>
                    </span>
                </div>
                
                <div class="mb-2">
                    <span class="badge <?php echo extension_loaded('pdo_mysql') ? 'badge-success' : 'badge-danger'; ?>">
                        PDO MySQL <?php echo extension_loaded('pdo_mysql') ? 'Available' : 'Missing'; ?>
                    </span>
                </div>
                
                <div class="mb-2">
                    <span class="badge <?php echo is_writable('uploads/') ? 'badge-success' : 'badge-warning'; ?>">
                        Uploads Directory <?php echo is_writable('uploads/') ? 'Writable' : 'Check Permissions'; ?>
                    </span>
                </div>
                
                <div class="mb-2">
                    <span class="badge <?php echo function_exists('mail') ? 'badge-success' : 'badge-warning'; ?>">
                        Mail Function <?php echo function_exists('mail') ? 'Available' : 'Not Available'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Configuration Info -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Configuration Information</h5>
            </div>
            <div class="card-body">
                <div style="font-size: 0.875rem;">
                    <div class="mb-2">
                        <strong>Database Host:</strong> <?php echo DB_HOST; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Database Name:</strong> <?php echo DB_NAME; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Site URL:</strong> <?php echo SITE_URL; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Upload Path:</strong> <?php echo UPLOAD_PATH; ?>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3" style="font-size: 0.875rem;">
                    <strong>Note:</strong> You can modify these settings in <code>includes/config.php</code>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
