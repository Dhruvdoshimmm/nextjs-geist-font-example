<?php
// Database Configuration - Using SQLite for demo
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_work_platform.db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('USE_SQLITE', true);

// Site Configuration
define('SITE_URL', 'http://localhost/college-work-platform');
define('SITE_NAME', 'College Work Helper');
define('ADMIN_EMAIL', 'admin@collegeworkhelper.com');

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 1800); // 30 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Payment Configuration (Stripe)
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session with security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Timezone
date_default_timezone_set('UTC');
?>
