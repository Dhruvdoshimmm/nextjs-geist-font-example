<?php
require_once 'config.php';
require_once 'db.php';

// File upload handler
function handleFileUpload($file, $order_id, $upload_type = 'reference') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit (10MB)'];
    }
    
    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_dir = UPLOAD_PATH . $upload_type . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $file_path,
            'original_name' => $file['name']
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

// Calculate order price
function calculateOrderPrice($category_id, $word_count, $deadline_days, $academic_level) {
    global $pdo;
    
    // Get base price from category
    $stmt = $pdo->prepare("SELECT base_price FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        return 0;
    }
    
    $base_price = $category['base_price'];
    
    // Price per word
    $price_per_word = $base_price / 250; // Assuming 250 words per page
    $total_price = $price_per_word * $word_count;
    
    // Academic level multiplier
    $level_multipliers = [
        'high_school' => 1.0,
        'undergraduate' => 1.2,
        'graduate' => 1.5,
        'phd' => 2.0
    ];
    
    $total_price *= $level_multipliers[$academic_level] ?? 1.0;
    
    // Deadline urgency multiplier
    if ($deadline_days <= 1) {
        $total_price *= 2.0; // 24 hours or less
    } elseif ($deadline_days <= 3) {
        $total_price *= 1.5; // 3 days or less
    } elseif ($deadline_days <= 7) {
        $total_price *= 1.2; // 1 week or less
    }
    
    return round($total_price, 2);
}

// Send notification
function sendNotification($user_id, $type, $title, $message) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message) 
        VALUES (?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $type, $title, $message]);
}

// Get user notifications
function getUserNotifications($user_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    
    return $stmt->fetchAll();
}

// Mark notification as read
function markNotificationAsRead($notification_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = TRUE 
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$notification_id, $user_id]);
}

// Get order statistics for dashboard
function getOrderStats($user_id = null, $role = 'student') {
    global $pdo;
    
    $stats = [];
    
    if ($role === 'admin') {
        // Admin stats
        $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
        $stats['total_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as completed_orders FROM orders WHERE status = 'completed'");
        $stats['completed_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(total_price) as total_revenue FROM orders WHERE payment_status = 'paid'");
        $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
        
    } else {
        // Student/Writer stats
        $where_clause = $role === 'writer' ? 'writer_id = ?' : 'user_id = ?';
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE $where_clause");
        $stmt->execute([$user_id]);
        $stats['total_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_orders FROM orders WHERE $where_clause AND status IN ('pending', 'in_progress')");
        $stmt->execute([$user_id]);
        $stats['active_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM orders WHERE $where_clause AND status = 'completed'");
        $stmt->execute([$user_id]);
        $stats['completed_orders'] = $stmt->fetchColumn();
    }
    
    return $stats;
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Generate order number
function generateOrderNumber() {
    return 'CWH-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

// Get order status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'in_progress' => 'badge-info',
        'completed' => 'badge-success',
        'revision' => 'badge-secondary',
        'cancelled' => 'badge-danger'
    ];
    
    return $classes[$status] ?? 'badge-secondary';
}

// Validate order data
function validateOrderData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Title is required';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'Description is required';
    }
    
    if (empty($data['deadline']) || strtotime($data['deadline']) <= time()) {
        $errors[] = 'Valid deadline is required';
    }
    
    if (empty($data['word_count']) || $data['word_count'] < 100) {
        $errors[] = 'Word count must be at least 100';
    }
    
    if (empty($data['category_id'])) {
        $errors[] = 'Category is required';
    }
    
    return $errors;
}

// Send email function (basic implementation)
function sendEmail($to, $subject, $message, $headers = '') {
    if (empty($headers)) {
        $headers = "From: " . ADMIN_EMAIL . "\r\n";
        $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Check if user can access order
function canAccessOrder($order_id, $user_id, $user_role) {
    global $pdo;
    
    if ($user_role === 'admin') {
        return true;
    }
    
    $stmt = $pdo->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND (user_id = ? OR writer_id = ?)
    ");
    $stmt->execute([$order_id, $user_id, $user_id]);
    
    return $stmt->rowCount() > 0;
}

// Pagination helper
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}
?>
