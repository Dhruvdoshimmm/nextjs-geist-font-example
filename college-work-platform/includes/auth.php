<?php
require_once 'config.php';
require_once 'db.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Register new user
    public function register($email, $password, $first_name, $last_name, $academic_level = 'undergraduate') {
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, HASH_ALGO);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password, first_name, last_name, academic_level, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $email, 
                $hashed_password, 
                $first_name, 
                $last_name, 
                $academic_level, 
                $verification_token
            ]);
            
            // Send verification email (implement later)
            // $this->sendVerificationEmail($email, $verification_token);
            
            return [
                'success' => true, 
                'message' => 'Registration successful. Please check your email for verification.',
                'user_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    // Login user
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("
            SELECT id, email, password, first_name, last_name, role, status, email_verified 
            FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is suspended or inactive'];
        }
        
        if (!password_verify($password, $user['password'])) {
            // Log failed login attempt
            $this->logFailedLogin($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Check if account is locked due to failed attempts
        if ($this->isAccountLocked($email)) {
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed login attempts'];
        }
        
        // Create session
        $this->createSession($user);
        
        // Clear failed login attempts
        $this->clearFailedLogins($email);
        
        return [
            'success' => true, 
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role']
            ]
        ];
    }
    
    // Create user session
    private function createSession($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Check if user has specific role
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    // Get current user info
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, email, first_name, last_name, role, balance, academic_level 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    // Log failed login attempt
    private function logFailedLogin($email) {
        if (!isset($_SESSION['failed_logins'])) {
            $_SESSION['failed_logins'] = [];
        }
        
        if (!isset($_SESSION['failed_logins'][$email])) {
            $_SESSION['failed_logins'][$email] = [];
        }
        
        $_SESSION['failed_logins'][$email][] = time();
        
        // Keep only last 5 attempts
        $_SESSION['failed_logins'][$email] = array_slice(
            $_SESSION['failed_logins'][$email], -5
        );
    }
    
    // Check if account is locked
    private function isAccountLocked($email) {
        if (!isset($_SESSION['failed_logins'][$email])) {
            return false;
        }
        
        $attempts = $_SESSION['failed_logins'][$email];
        $recent_attempts = array_filter($attempts, function($time) {
            return (time() - $time) < 900; // 15 minutes
        });
        
        return count($recent_attempts) >= 5;
    }
    
    // Clear failed login attempts
    private function clearFailedLogins($email) {
        if (isset($_SESSION['failed_logins'][$email])) {
            unset($_SESSION['failed_logins'][$email]);
        }
    }
    
    // Verify email token
    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET email_verified = TRUE, verification_token = NULL 
            WHERE verification_token = ? AND email_verified = FALSE
        ");
        $stmt->execute([$token]);
        
        return $stmt->rowCount() > 0;
    }
    
    // Generate password reset token
    public function generatePasswordResetToken($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->pdo->prepare("
            UPDATE users SET verification_token = ?, updated_at = ? WHERE email = ?
        ");
        $stmt->execute([$token, $expires, $email]);
        
        // Send reset email (implement later)
        // $this->sendPasswordResetEmail($email, $token);
        
        return ['success' => true, 'message' => 'Password reset link sent to your email'];
    }
    
    // Reset password
    public function resetPassword($token, $new_password) {
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        $hashed_password = password_hash($new_password, HASH_ALGO);
        
        $stmt = $this->pdo->prepare("
            UPDATE users SET password = ?, verification_token = NULL 
            WHERE verification_token = ?
        ");
        $stmt->execute([$hashed_password, $token]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Password reset successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
    }
}

// Initialize Auth class
$auth = new Auth($pdo);

// CSRF Token validation function
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate new CSRF token
function generateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>
