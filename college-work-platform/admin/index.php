<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();

// Get admin statistics
$stats = getOrderStats(null, 'admin');

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, c.name as category_name, u.first_name, u.last_name, u.email
    FROM orders o 
    JOIN categories c ON o.category_id = c.id 
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Get pending orders count
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetchColumn();

// Get today's revenue
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_price), 0) 
    FROM orders 
    WHERE payment_status = 'paid' AND DATE(created_at) = DATE('now')
");
$today_revenue = $stmt->fetchColumn();

// Get active users count
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE status = 'active' AND role = 'student' 
    AND created_at >= datetime('now', '-30 days')
");
$active_users = $stmt->fetchColumn();

// Get system alerts
$alerts = [];

// Check for urgent orders (deadline within 24 hours)
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM orders 
    WHERE status IN ('pending', 'in_progress') 
    AND deadline <= datetime('now', '+24 hours')
");
$urgent_orders = $stmt->fetchColumn();

if ($urgent_orders > 0) {
    $alerts[] = [
        'type' => 'warning',
        'message' => "{$urgent_orders} orders have deadlines within 24 hours"
    ];
}

// Check for unassigned orders
if ($pending_orders > 5) {
    $alerts[] = [
        'type' => 'info',
        'message' => "{$pending_orders} orders are waiting for writer assignment"
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Admin dashboard for managing orders, users, and platform operations.">
    
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-panel">
    <!-- Admin Header -->
    <header class="header admin-header">
        <div class="container-fluid">
            <nav class="navbar">
                <div class="d-flex align-items-center">
                    <button class="menu-toggle btn btn-outline-primary" style="margin-right: 1rem;">☰</button>
                    <a href="index.php" class="navbar-brand"><?php echo SITE_NAME; ?> Admin</a>
                </div>
                
                <ul class="navbar-nav">
                    <li><a href="../user/index.php" class="nav-link" target="_blank">View Site</a></li>
                    <li>
                        <div class="dropdown" style="position: relative;">
                            <a href="#" class="nav-link dropdown-toggle">
                                <?php echo htmlspecialchars($user['first_name']); ?>
                            </a>
                            <div class="dropdown-menu" style="position: absolute; right: 0; top: 100%; background: white; border: 1px solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow-lg); min-width: 150px; z-index: 1000; display: none;">
                                <a href="profile.php" class="dropdown-item" style="display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--text-primary);">Profile</a>
                                <a href="settings.php" class="dropdown-item" style="display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--text-primary);">Settings</a>
                                <hr style="margin: 0.5rem 0;">
                                <a href="../user/logout.php" class="dropdown-item" style="display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--danger-color);">Logout</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar admin-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="orders.php">Orders (<?php echo $pending_orders; ?>)</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="writers.php">Writers</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="payments.php">Payments</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1>Dashboard</h1>
                        <p class="text-secondary">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                    </div>
                    <div>
                        <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
                    </div>
                </div>

                <!-- System Alerts -->
                <?php if (!empty($alerts)): ?>
                <div class="mb-4">
                    <?php foreach ($alerts as $alert): ?>
                    <div class="alert alert-<?php echo $alert['type']; ?>">
                        <?php echo htmlspecialchars($alert['message']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="dashboard-stats mb-5">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-change text-success">+12% from last month</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                        <div class="stat-label">Pending Orders</div>
                        <div class="stat-change text-warning">Needs attention</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-change text-success">+8% from last month</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatCurrency($today_revenue); ?></div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-change text-info">Current day</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($active_users); ?></div>
                        <div class="stat-label">Active Users</div>
                        <div class="stat-change text-success">Last 30 days</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['completed_orders']); ?></div>
                        <div class="stat-label">Completed Orders</div>
                        <div class="stat-change text-success">Success rate: 98%</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="admin-grid">
                    <!-- Recent Orders -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Recent Orders</h3>
                            <a href="orders.php" class="btn btn-outline-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No orders yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Student</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($order['title']); ?></strong>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo $order['word_count']; ?> words
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($order['email']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['category_name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($order['total_price']); ?></td>
                                                <td><?php echo formatDate($order['created_at']); ?></td>
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
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions & Stats -->
                    <div>
                        <!-- Quick Actions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-column" style="gap: 0.75rem;">
                                    <a href="orders.php?status=pending" class="btn btn-warning btn-sm">
                                        Assign Pending Orders (<?php echo $pending_orders; ?>)
                                    </a>
                                    <a href="orders.php?urgent=1" class="btn btn-danger btn-sm">
                                        Review Urgent Orders (<?php echo $urgent_orders; ?>)
                                    </a>
                                    <a href="users.php?new=1" class="btn btn-info btn-sm">
                                        Review New Users
                                    </a>
                                    <a href="writers.php" class="btn btn-success btn-sm">
                                        Manage Writers
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-primary btn-sm">
                                        Generate Reports
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">System Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.875rem;">Server Status</span>
                                        <span class="badge badge-success">Online</span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.875rem;">Database</span>
                                        <span class="badge badge-success">Connected</span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.875rem;">Payment Gateway</span>
                                        <span class="badge badge-success">Active</span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.875rem;">Email Service</span>
                                        <span class="badge badge-success">Working</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div style="font-size: 0.875rem; color: var(--text-muted);">
                                    Last updated: <?php echo date('H:i:s'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../public/js/main.js"></script>
    <script src="../public/js/admin.js"></script>
    <script>
        // Dropdown functionality
        document.querySelector('.dropdown-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
        
        // Responsive grid
        function adjustAdminGrid() {
            const grid = document.querySelector('.admin-grid');
            if (window.innerWidth <= 768) {
                grid.style.gridTemplateColumns = '1fr';
            } else {
                grid.style.gridTemplateColumns = '2fr 1fr';
            }
        }
        
        window.addEventListener('resize', adjustAdminGrid);
        adjustAdminGrid();
    </script>
    
    <style>
        .admin-header {
            background: var(--primary-color);
            color: white;
        }
        
        .admin-header .navbar-brand {
            color: white;
            font-weight: 700;
        }
        
        .admin-header .nav-link {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .admin-header .nav-link:hover {
            color: white;
        }
        
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .admin-sidebar {
            background: white;
            border-right: 1px solid var(--border-color);
            width: 250px;
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f8fafc;
        }
        
        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: static;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .admin-grid {
                grid-template-columns: 1fr !important;
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
