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

// Redirect admin users to admin panel
if ($auth->hasRole('admin')) {
    header('Location: ../admin/index.php');
    exit;
}

$user = $auth->getCurrentUser();
$user_id = $user['id'];

// Get user statistics
$stats = getOrderStats($user_id, 'student');

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, c.name as category_name 
    FROM orders o 
    JOIN categories c ON o.category_id = c.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Get recent notifications
$notifications = getUserNotifications($user_id, 5);

// Get active orders (pending or in progress)
$stmt = $pdo->prepare("
    SELECT o.*, c.name as category_name 
    FROM orders o 
    JOIN categories c ON o.category_id = c.id 
    WHERE o.user_id = ? AND o.status IN ('pending', 'in_progress') 
    ORDER BY o.deadline ASC
");
$stmt->execute([$user_id]);
$active_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Manage your orders and track progress on your academic assignments.">
    
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="logged-in">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="navbar-brand"><?php echo SITE_NAME; ?></a>
                
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                    <li><a href="place-order.php" class="nav-link">Place Order</a></li>
                    <li><a href="orders.php" class="nav-link">My Orders</a></li>
                    <li><a href="profile.php" class="nav-link">Profile</a></li>
                    <li>
                        <div style="position: relative;">
                            <a href="notifications.php" class="nav-link">
                                Notifications
                                <?php if (count(array_filter($notifications, fn($n) => !$n['is_read'])) > 0): ?>
                                    <span class="notification-badge badge badge-danger" style="position: absolute; top: -5px; right: -10px; font-size: 0.7rem;">
                                        <?php echo count(array_filter($notifications, fn($n) => !$n['is_read'])); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </li>
                    <li><a href="logout.php" class="btn btn-outline-primary">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main style="padding: 2rem 0;">
        <div class="container">
            <!-- Welcome Section -->
            <div class="mb-4">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p class="text-secondary">Here's an overview of your account and recent activity.</p>
            </div>

            <!-- Quick Actions -->
            <div class="mb-4">
                <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                    <a href="place-order.php" class="btn btn-primary">
                        Place New Order
                    </a>
                    <a href="orders.php" class="btn btn-outline-primary">
                        View All Orders
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        Edit Profile
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-stats mb-5">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_orders']; ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed_orders']; ?></div>
                    <div class="stat-label">Completed Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatCurrency($user['balance']); ?></div>
                    <div class="stat-label">Account Balance</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="dashboard-grid">
                <!-- Active Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Active Orders</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_orders)): ?>
                            <div class="text-center" style="padding: 2rem;">
                                <p class="text-muted">No active orders at the moment.</p>
                                <a href="place-order.php" class="btn btn-primary">Place Your First Order</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Deadline</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['title']); ?></strong>
                                                    <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                        <?php echo formatCurrency($order['total_price']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['category_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $deadline = new DateTime($order['deadline']);
                                                $now = new DateTime();
                                                $diff = $now->diff($deadline);
                                                $is_urgent = $diff->days <= 1 && !$diff->invert;
                                                ?>
                                                <div style="<?php echo $is_urgent ? 'color: var(--danger-color); font-weight: 600;' : ''; ?>">
                                                    <?php echo formatDate($order['deadline']); ?>
                                                    <?php if ($is_urgent): ?>
                                                        <div style="font-size: 0.75rem;">URGENT</div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Account Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Academic Level:</strong><br>
                                <?php echo ucfirst(str_replace('_', ' ', $user['academic_level'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Account Balance:</strong><br>
                                <span style="font-size: 1.25rem; font-weight: 600; color: var(--success-color);">
                                    <?php echo formatCurrency($user['balance']); ?>
                                </span>
                            </div>
                            <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Notifications</h5>
                            <a href="notifications.php" style="font-size: 0.875rem;">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted text-center">No notifications yet.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div style="flex: 1;">
                                            <h6 class="mb-1" style="font-size: 0.875rem; <?php echo !$notification['is_read'] ? 'font-weight: 600;' : ''; ?>">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h6>
                                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">
                                                <?php echo timeAgo($notification['created_at']); ?>
                                            </div>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <div style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; margin-left: 0.5rem; margin-top: 0.25rem;"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <?php if (!empty($recent_orders)): ?>
            <div class="mt-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Recent Orders</h3>
                        <a href="orders.php">View All Orders</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['title']); ?></strong>
                                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                <?php echo $order['word_count']; ?> words
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['category_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($order['created_at']); ?></td>
                                        <td><?php echo formatCurrency($order['total_price']); ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            checkNotifications();
        }, 30000);
        
        // Responsive dashboard grid
        function adjustDashboardLayout() {
            const grid = document.querySelector('.dashboard-grid');
            if (window.innerWidth <= 768) {
                grid.style.gridTemplateColumns = '1fr';
            } else {
                grid.style.gridTemplateColumns = '2fr 1fr';
            }
        }
        
        window.addEventListener('resize', adjustDashboardLayout);
        adjustDashboardLayout();
    </script>
    
    <style>
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr !important;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .navbar-nav {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
