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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Sanitize input
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $academic_level = sanitizeInput($_POST['academic_level'] ?? 'undergraduate');
        
        // Validate input
        if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error = 'All fields are required.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Attempt registration
            $result = $auth->register($email, $password, $first_name, $last_name, $academic_level);
            
            if ($result['success']) {
                $success = $result['message'];
                // Clear form data
                $email = $first_name = $last_name = '';
            } else {
                $error = $result['message'];
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Create your account to get started with professional academic assistance.">
    
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
                    <li><a href="login.php" class="nav-link">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Registration Form -->
    <section style="padding: 4rem 0; min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div style="max-width: 500px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">Create Your Account</h2>
                        <p class="text-secondary mt-2">Join thousands of students getting academic help</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-primary">Login Now</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your first name.</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your last name.</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="academic_level" class="form-label">Academic Level *</label>
                                    <select class="form-control form-select" id="academic_level" name="academic_level" required>
                                        <option value="">Select your academic level</option>
                                        <option value="high_school" <?php echo ($academic_level ?? '') === 'high_school' ? 'selected' : ''; ?>>High School</option>
                                        <option value="undergraduate" <?php echo ($academic_level ?? '') === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                                        <option value="graduate" <?php echo ($academic_level ?? '') === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
                                        <option value="phd" <?php echo ($academic_level ?? '') === 'phd' ? 'selected' : ''; ?>>PhD</option>
                                    </select>
                                    <div class="invalid-feedback">Please select your academic level.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                    <div class="invalid-feedback">Please enter a password (minimum 8 characters).</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your password.</div>
                                </div>
                                
                                <div class="form-group">
                                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                        <input type="checkbox" id="terms" name="terms" required style="margin-top: 0.25rem;">
                                        <label for="terms" style="font-size: 0.875rem; line-height: 1.4;">
                                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                                            <a href="privacy.php" target="_blank">Privacy Policy</a>
                                        </label>
                                    </div>
                                    <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                    Create Account
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$success): ?>
                    <div class="card-footer text-center">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Benefits Section -->
                <div class="mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-center mb-3">Why Choose Us?</h5>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
                                <div>
                                    <div style="color: var(--success-color); font-weight: 600; margin-bottom: 0.5rem;">✓ Expert Writers</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Qualified professionals in every field</div>
                                </div>
                                <div>
                                    <div style="color: var(--success-color); font-weight: 600; margin-bottom: 0.5rem;">✓ On-Time Delivery</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Never miss a deadline</div>
                                </div>
                                <div>
                                    <div style="color: var(--success-color); font-weight: 600; margin-bottom: 0.5rem;">✓ 24/7 Support</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Always here to help</div>
                                </div>
                                <div>
                                    <div style="color: var(--success-color); font-weight: 600; margin-bottom: 0.5rem;">✓ Free Revisions</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Until you're satisfied</div>
                                </div>
                            </div>
                        </div>
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
        // Additional validation for password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (confirmPassword) {
                    this.classList.add('is-valid');
                }
            }
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            
            // You can add a password strength indicator here
            if (password.length >= 8) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (password.length > 0) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });
        
        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
    </script>
</body>
</html>
