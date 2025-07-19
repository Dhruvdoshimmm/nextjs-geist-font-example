<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$user_id = $user['id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user can access this order
if (!$auth->hasRole('admin') && !canAccessOrder($order_id, $user_id, $user['role'])) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, c.name as category_name, 
           u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
           w.first_name as writer_first_name, w.last_name as writer_last_name
    FROM orders o 
    JOIN categories c ON o.category_id = c.id 
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users w ON o.writer_id = w.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: dashboard.php?error=order_not_found');
    exit;
}

// Get order files
$stmt = $pdo->prepare("
    SELECT * FROM order_files 
    WHERE order_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$order_id]);
$files = $stmt->fetchAll();

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, u.first_name, u.last_name, u.role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.order_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

// Mark notifications as read
if (isset($_GET['new'])) {
    sendNotification($user_id, 'order_viewed', 'Order Viewed', "You viewed order #{$order_id}");
}

// Handle form submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Handle message submission
        if (isset($_POST['message'])) {
            $message = sanitizeInput($_POST['message']);
            if (!empty($message)) {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (order_id, sender_id, message) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$order_id, $user_id, $message]);
                
                // Notify other party
                $notify_user_id = ($user['role'] === 'student') ? $order['writer_id'] : $order['user_id'];
                if ($notify_user_id) {
                    sendNotification(
                        $notify_user_id,
                        'new_message',
                        'New Message',
                        "New message on order #{$order_id}"
                    );
                }
                
                $success = 'Message sent successfully!';
                header("Location: order-details.php?id=$order_id&success=1");
                exit;
            }
        }
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleFileUpload($_FILES['file'], $order_id, 'reference');
            if ($upload_result['success']) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_files (order_id, file_name, file_path, uploaded_by) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $upload_result['original_name'],
                    $upload_result['file_path'],
                    $user_id
                ]);
                
                $success = 'File uploaded successfully!';
                header("Location: order-details.php?id=$order_id&success=1");
                exit;
            } else {
                $error = $upload_result['message'];
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
    <title>Order Details #<?php echo $order['id']; ?> - <?php echo SITE_NAME; ?></title>
    
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
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li><a href="orders.php" class="nav-link">My Orders</a></li>
                    <li><a href="place-order.php" class="nav-link">Place Order</a></li>
                    <li><a href="profile.php" class="nav-link">Profile</a></li>
                    <li><a href="logout.php" class="btn btn-outline-primary">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main style="padding: 2rem 0;">
        <div class="container">
            <div class="mb-4">
                <h1>Order Details #<?php echo $order['id']; ?></h1>
                <nav aria-label="breadcrumb">
                    <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: 0.5rem; font-size: 0.875rem;">
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li>/</li>
                        <li><a href="orders.php">Orders</a></li>
                        <li>/</li>
                        <li>Order #<?php echo $order['id']; ?></li>
                    </ol>
                </nav>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Action completed successfully!</div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="order-details-grid">
                <!-- Order Information -->
                <div>
                    <!-- Order Details Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">Order Information</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <strong>Order ID:</strong> #<?php echo $order['id']; ?>
                                </div>
                                <div>
                                    <strong>Status:</strong> 
                                    <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Category:</strong> <?php echo htmlspecialchars($order['category_name']); ?>
                                </div>
                                <div>
                                    <strong>Deadline:</strong> <?php echo formatDate($order['deadline']); ?>
                                </div>
                                <div>
                                    <strong>Word Count:</strong> <?php echo number_format($order['word_count']); ?> words
                                </div>
                                <div>
                                    <strong>Total Price:</strong> <?php echo formatCurrency($order['total_price']); ?>
                                </div>
                                <div>
                                    <strong>Payment Status:</strong> 
                                    <span class="badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Created:</strong> <?php echo formatDate($order['created_at']); ?>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <strong>Title:</strong>
                                <p><?php echo htmlspecialchars($order['title']); ?></p>
                            </div>
                            
                            <div class="mt-3">
                                <strong>Description:</strong>
                                <p><?php echo nl2br(htmlspecialchars($order['description'])); ?></p>
                            </div>
                            
                            <?php if ($order['special_instructions']): ?>
                            <div class="mt-3">
                                <strong>Special Instructions:</strong>
                                <p><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Files Section -->
                    <?php if (!empty($files)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">Files</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Type</th>
                                            <th>Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($files as $file): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                            <td><?php echo ucfirst($file['file_type']); ?></td>
                                            <td><?php echo formatDate($file['created_at']); ?></td>
                                            <td>
                                                <a href="../<?php echo $file['file_path']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                    Download
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Messages Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">Messages</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($messages)): ?>
                                <p class="text-muted">No messages yet.</p>
                            <?php else: ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($messages as $message): ?>
                                    <div class="mb-3 p-3 border rounded" style="background-color: #f8fafc;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></strong>
                                            <small class="text-muted"><?php echo timeAgo($message['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Message Form -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <label for="message" class="form-label">Add Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" 
                                              placeholder="Type your message here..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Order Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column" style="gap: 0.5rem;">
                                <?php if ($order['status'] === 'pending' && $user['role'] === 'admin'): ?>
                                    <button class="btn btn-primary">Assign to Writer</button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'completed'): ?>
                                    <button class="btn btn-success">Download Work</button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'revision'): ?>
                                    <button class="btn btn-warning">Request Revision</button>
                                <?php endif; ?>
                                
                                <?php if ($order['payment_status'] === 'pending'): ?>
                                    <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">Pay Now</a>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-secondary">Print Order</button>
                            </div>
                        </div>
                    </div>

                    <!-- Upload File -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Upload File</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <input type="file" class="form-control" name="file" required>
                                    <div class="form-text">Max size: 10MB</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                            </form>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Client:</strong><br>
                                <?php echo htmlspecialchars($order['user_first_name'] . ' ' . $order['user_last_name']); ?>
                            </div>
                            
                            <?php if ($order['writer_first_name']): ?>
                            <div class="mb-2">
                                <strong>Writer:</strong><br>
                                <?php echo htmlspecialchars($order['writer_first_name'] . ' ' . $order['writer_last_name']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <strong>Total:</strong><br>
                                <span style="font-size: 1.25rem; font-weight: 600; color: var(--primary-color);">
                                    <?php echo formatCurrency($order['total_price']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="background-color: var(--light-color); padding: 2rem 0; margin-top: 3rem;">
        <div class="container text-center">
            <p style="color: var(--text-muted); margin: 0;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="../public/js/main.js"></script>
</body>
</html>
