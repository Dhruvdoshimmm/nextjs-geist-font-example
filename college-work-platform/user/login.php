


<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle password reset request
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset link has been sent to your email.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a login or password reset request
    if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        // Password reset request
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $email = sanitizeInput($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = 'Email address is required.';
            } elseif (!validateEmail($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                $result = $auth->generatePasswordResetToken($email);
                if ($result['success']) {
                    header('Location: login.php?reset=success');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    } else {
        // Login request
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            if (empty($email) || empty($password)) {
                $error = 'Email and password are required.';
            } elseif (!validateEmail($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                $result = $auth->login($email, $password);
                
                if ($result['success']) {
                    // Set remember me cookie if requested
                    if ($remember) {
                        setcookie('remember_token', bin2hex(random_bytes(32)), time() + (86400 * 30), '/', '', true, true);
                    }
                    
                    // Redirect based on user role
                    $redirect_url = 'dashboard.php';
                    if ($result['user']['role'] === 'admin') {
                        $redirect_url = '../admin/index.php';
                    }
                    
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Login to your account to access your dashboard and manage your orders.">
    
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="navbar-brand"><?php echo SITE_NAME; ?></a>
                
                <ul class="navbar-nav">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="register.php" class="nav-link">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Login Form -->
    <section style="padding: 4rem 0; min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div style="max-width: 450px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">Welcome Back</h2>
                        <p class="text-secondary mt-2">Sign in to your account</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" class="needs-validation" novalidate id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            
                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <input type="checkbox" id="remember" name="remember">
                                        <label for="remember" style="margin-left: 0.5rem; font-size: 0.875rem;">Remember me</label>
                                    </div>
                                    <a href="#" onclick="showPasswordReset()" style="font-size: 0.875rem;">Forgot password?</a>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                Sign In
                            </button>
                        </form>
                        
                        <!-- Password Reset Form (Hidden by default) -->
                        <form method="POST" class="needs-validation" novalidate id="resetForm" style="display: none;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="reset_password">
                            
                            <div class="text-center mb-3">
                                <h5>Reset Password</h5>
                                <p class="text-secondary">Enter your email to receive a reset link</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="reset_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="reset_email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            
                            <div class="d-flex" style="gap: 1rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    Send Reset Link
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="showLogin()" style="flex: 1;">
                                    Back to Login
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-footer text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php">Create one here</a></p>
                    </div>
                </div>
                
                <!-- Demo Accounts Info -->
                <div class="mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-center mb-3">Demo Accounts</h6>
                            <div style="font-size: 0.875rem;">
                                <div class="mb-2">
                                    <strong>Admin:</strong> admin@collegeworkhelper.com / admin123
                                </div>
                                <div class="text-muted">
                                    Use these credentials to test the admin panel functionality
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Notice -->
                <div class="mt-3">
                    <div class="alert alert-info" style="font-size: 0.875rem;">
                        <strong>Security Notice:</strong> Your account will be automatically locked after 5 failed login attempts. 
                        We use industry-standard encryption to protect your data.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background-color: var(--dark-color); color: white; padding: 2rem 0;">
        <div class="container text-center">
            <p style="color: rgba(255,255,255,0.6); margin: 0;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="../public/js/main.js"></script>
    <script>
        function showPasswordReset() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('resetForm').style.display = 'block';
            document.getElementById('reset_email').focus();
        }
        
        function showLogin() {
            document.getElementById('resetForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('email').focus();
        }
        
        // Auto-fill demo credentials
        function fillDemoCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
        
        // Add click handlers for demo credentials
        document.addEventListener('DOMContentLoaded', function() {
            // You can add buttons to auto-fill demo credentials if needed
            const demoInfo = document.querySelector('.card-body');
            if (demoInfo) {
                const adminDemo = demoInfo.querySelector('div:contains("Admin:")');
                if (adminDemo) {
                    adminDemo.style.cursor = 'pointer';
                    adminDemo.addEventListener('click', function() {
                        fillDemoCredentials('admin@collegeworkhelper.com', 'admin123');
                    });
                }
            }
        });
        
        // Enhanced form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'danger');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                showAlert('Please enter a valid email address.', 'danger');
                return false;
            }
        });
        
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const email = document.getElementById('reset_email').value.trim();
            
            if (!email) {
                e.preventDefault();
                showAlert('Please enter your email address.', 'danger');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                showAlert('Please enter a valid email address.', 'danger');
                return false;
            }
        });
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>
