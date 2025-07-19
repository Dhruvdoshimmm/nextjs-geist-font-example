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
$error = '';
$success = '';

// Get categories
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

// Pre-select category if provided
$selected_category = $_GET['category'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate input
        $order_data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'word_count' => (int)($_POST['word_count'] ?? 0),
            'deadline' => sanitizeInput($_POST['deadline'] ?? ''),
            'special_instructions' => sanitizeInput($_POST['special_instructions'] ?? ''),
            'academic_level' => sanitizeInput($_POST['academic_level'] ?? $user['academic_level'])
        ];
        
        // Validate order data
        $validation_errors = validateOrderData($order_data);
        
        if (!empty($validation_errors)) {
            $error = implode('<br>', $validation_errors);
        } else {
            // Calculate price
            $deadline_date = new DateTime($order_data['deadline']);
            $now = new DateTime();
            $deadline_days = $now->diff($deadline_date)->days;
            
            $total_price = calculateOrderPrice(
                $order_data['category_id'],
                $order_data['word_count'],
                $deadline_days,
                $order_data['academic_level']
            );
            
            if ($total_price <= 0) {
                $error = 'Unable to calculate price. Please check your inputs.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert order
                    $stmt = $pdo->prepare("
                        INSERT INTO orders (user_id, category_id, title, description, deadline, word_count, 
                                          total_price, special_instructions, status, payment_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
                    ");
                    
                    $stmt->execute([
                        $user['id'],
                        $order_data['category_id'],
                        $order_data['title'],
                        $order_data['description'],
                        $order_data['deadline'],
                        $order_data['word_count'],
                        $total_price,
                        $order_data['special_instructions']
                    ]);
                    
                    $order_id = $pdo->lastInsertId();
                    
                    // Handle file uploads
                    if (isset($_FILES['reference_files']) && !empty($_FILES['reference_files']['name'][0])) {
                        $upload_errors = [];
                        
                        for ($i = 0; $i < count($_FILES['reference_files']['name']); $i++) {
                            if ($_FILES['reference_files']['error'][$i] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['reference_files']['name'][$i],
                                    'type' => $_FILES['reference_files']['type'][$i],
                                    'tmp_name' => $_FILES['reference_files']['tmp_name'][$i],
                                    'error' => $_FILES['reference_files']['error'][$i],
                                    'size' => $_FILES['reference_files']['size'][$i]
                                ];
                                
                                $upload_result = handleFileUpload($file, $order_id, 'assignments');
                                
                                if ($upload_result['success']) {
                                    // Save file info to database
                                    $stmt = $pdo->prepare("
                                        INSERT INTO order_files (order_id, file_name, file_path, file_type, uploaded_by) 
                                        VALUES (?, ?, ?, 'reference', ?)
                                    ");
                                    $stmt->execute([
                                        $order_id,
                                        $upload_result['original_name'],
                                        $upload_result['file_path'],
                                        $user['id']
                                    ]);
                                } else {
                                    $upload_errors[] = $upload_result['message'];
                                }
                            }
                        }
                        
                        if (!empty($upload_errors)) {
                            throw new Exception('File upload errors: ' . implode(', ', $upload_errors));
                        }
                    }
                    
                    // Send notification
                    sendNotification(
                        $user['id'],
                        'order_created',
                        'Order Created Successfully',
                        "Your order '{$order_data['title']}' has been created and is pending assignment to a writer."
                    );
                    
                    $pdo->commit();
                    
                    // Redirect to payment or order details
                    header("Location: order-details.php?id={$order_id}&new=1");
                    exit;
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to create order. Please try again.';
                    error_log("Order creation error: " . $e->getMessage());
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
    <title>Place Order - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Place a new order for academic assistance. Get expert help with your assignments.">
    
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
                    <li><a href="place-order.php" class="nav-link active">Place Order</a></li>
                    <li><a href="orders.php" class="nav-link">My Orders</a></li>
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
                <h1>Place New Order</h1>
                <p class="text-secondary">Fill out the form below to get started with your assignment</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="order-grid">
                <!-- Order Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Order Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <!-- Step 1: Basic Information -->
                            <div class="mb-4">
                                <h5 style="color: var(--primary-color); margin-bottom: 1rem;">
                                    Step 1: Basic Information
                                </h5>
                                
                                <div class="form-group">
                                    <label for="title" class="form-label">Assignment Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           placeholder="e.g., Research Paper on Climate Change" required>
                                    <div class="form-text">Provide a clear, descriptive title for your assignment</div>
                                    <div class="invalid-feedback">Please enter a title for your assignment.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-control form-select" id="category_id" name="category_id" required>
                                        <option value="">Select assignment type</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($selected_category == $category['id'] || ($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?> 
                                                (Starting at <?php echo formatCurrency($category['base_price']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select an assignment category.</div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label for="word_count" class="form-label">Word Count *</label>
                                        <input type="number" class="form-control" id="word_count" name="word_count" 
                                               value="<?php echo htmlspecialchars($_POST['word_count'] ?? ''); ?>" 
                                               min="100" max="50000" placeholder="e.g., 1500" required>
                                        <div class="form-text">Minimum 100 words</div>
                                        <div class="invalid-feedback">Please enter a valid word count (100-50000).</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="deadline" class="form-label">Deadline *</label>
                                        <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                               value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" 
                                               min="<?php echo date('Y-m-d\TH:i', strtotime('+2 hours')); ?>" required>
                                        <div class="form-text">Minimum 2 hours from now</div>
                                        <div class="invalid-feedback">Please select a valid deadline.</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="academic_level" class="form-label">Academic Level *</label>
                                    <select class="form-control form-select" id="academic_level" name="academic_level" required>
                                        <option value="high_school" <?php echo ($user['academic_level'] === 'high_school') ? 'selected' : ''; ?>>High School</option>
                                        <option value="undergraduate" <?php echo ($user['academic_level'] === 'undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                                        <option value="graduate" <?php echo ($user['academic_level'] === 'graduate') ? 'selected' : ''; ?>>Graduate</option>
                                        <option value="phd" <?php echo ($user['academic_level'] === 'phd') ? 'selected' : ''; ?>>PhD</option>
                                    </select>
                                    <div class="form-text">Higher levels may have additional charges</div>
                                </div>
                            </div>
                            
                            <!-- Step 2: Assignment Details -->
                            <div class="mb-4">
                                <h5 style="color: var(--primary-color); margin-bottom: 1rem;">
                                    Step 2: Assignment Details
                                </h5>
                                
                                <div class="form-group">
                                    <label for="description" class="form-label">Assignment Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="6" 
                                              placeholder="Provide detailed instructions for your assignment..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <div class="form-text">Be as specific as possible about your requirements</div>
                                    <div class="invalid-feedback">Please provide a detailed description.</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="special_instructions" class="form-label">Special Instructions</label>
                                    <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3" 
                                              placeholder="Any additional requirements, formatting guidelines, etc."><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                                    <div class="form-text">Optional: Citation style, specific sources, formatting requirements</div>
                                </div>
                            </div>
                            
                            <!-- Step 3: File Upload -->
                            <div class="mb-4">
                                <h5 style="color: var(--primary-color); margin-bottom: 1rem;">
                                    Step 3: Reference Materials (Optional)
                                </h5>
                                
                                <div class="form-group">
                                    <label for="reference_files" class="form-label">Upload Files</label>
                                    <input type="file" class="form-control" id="reference_files" name="reference_files[]" 
                                           multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                                    <div class="form-text">
                                        Upload any reference materials, assignment guidelines, or related documents. 
                                        Supported formats: PDF, DOC, DOCX, TXT, JPG, PNG. Max size: 10MB per file.
                                    </div>
                                </div>
                                <div class="file-list"></div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Create Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="card" id="order-summary">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Estimated Price:</strong>
                                <div id="price_display" style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                    $0.00
                                </div>
                            </div>
                            
                            <div class="mb-3" style="font-size: 0.875rem; color: var(--text-secondary);">
                                <div class="d-flex justify-content-between">
                                    <span>Base Price:</span>
                                    <span id="base_price">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Academic Level:</span>
                                    <span id="level_multiplier">1.0x</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Urgency:</span>
                                    <span id="urgency_multiplier">1.0x</span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <h6>What's Included:</h6>
                                <ul style="font-size: 0.875rem; margin: 0; padding-left: 1.2rem;">
                                    <li>Original, plagiarism-free content</li>
                                    <li>Professional writing and research</li>
                                    <li>On-time delivery guarantee</li>
                                    <li>Free revisions until satisfied</li>
                                    <li>24/7 customer support</li>
                                    <li>Direct communication with writer</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-info" style="font-size: 0.875rem;">
                                <strong>Payment:</strong> You'll be redirected to secure payment after order creation.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guarantees -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="text-center mb-3">Our Guarantees</h6>
                            <div style="font-size: 0.875rem; text-align: center;">
                                <div class="mb-2">
                                    <span style="color: var(--success-color);">✓</span> 100% Original Work
                                </div>
                                <div class="mb-2">
                                    <span style="color: var(--success-color);">✓</span> On-Time Delivery
                                </div>
                                <div class="mb-2">
                                    <span style="color: var(--success-color);">✓</span> Money Back Guarantee
                                </div>
                                <div class="mb-2">
                                    <span style="color: var(--success-color);">✓</span> 24/7 Support
                                </div>
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
    <script>
        // Enhanced price calculator with detailed breakdown
        function updatePriceBreakdown() {
            const categorySelect = document.getElementById('category_id');
            const wordCountInput = document.getElementById('word_count');
            const deadlineInput = document.getElementById('deadline');
            const academicLevelSelect = document.getElementById('academic_level');
            
            if (!categorySelect.value || !wordCountInput.value || !deadlineInput.value) {
                document.getElementById('price_display').textContent = '$0.00';
                document.getElementById('base_price').textContent = '$0.00';
                document.getElementById('level_multiplier').textContent = '1.0x';
                document.getElementById('urgency_multiplier').textContent = '1.0x';
                return;
            }
            
            // Base prices (from PHP)
            const basePrices = {
                <?php foreach ($categories as $category): ?>
                '<?php echo $category['id']; ?>': <?php echo $category['base_price']; ?>,
                <?php endforeach; ?>
            };
            
            const levelMultipliers = {
                'high_school': 1.0,
                'undergraduate': 1.2,
                'graduate': 1.5,
                'phd': 2.0
            };
            
            const categoryId = categorySelect.value;
            const wordCount = parseInt(wordCountInput.value) || 0;
            const deadline = new Date(deadlineInput.value);
            const academicLevel = academicLevelSelect.value;
            
            const basePrice = basePrices[categoryId] || 15.00;
            const pricePerWord = basePrice / 250;
            let totalPrice = pricePerWord * wordCount;
            
            // Academic level multiplier
            const levelMult = levelMultipliers[academicLevel] || 1.0;
            totalPrice *= levelMult;
            
            // Deadline urgency multiplier
            const now = new Date();
            const timeDiff = deadline.getTime() - now.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            let urgencyMult = 1.0;
            if (daysDiff <= 1) {
                urgencyMult = 2.0;
            } else if (daysDiff <= 3) {
                urgencyMult = 1.5;
            } else if (daysDiff <= 7) {
                urgencyMult = 1.2;
            }
            
            totalPrice *= urgencyMult;
            
            // Update display
            document.getElementById('price_display').textContent = '$' + totalPrice.toFixed(2);
            document.getElementById('base_price').textContent = '$' + (pricePerWord * wordCount).toFixed(2);
            document.getElementById('level_multiplier').textContent = levelMult.toFixed(1) + 'x';
            document.getElementById('urgency_multiplier').textContent = urgencyMult.toFixed(1) + 'x';
        }
        
        // Add event listeners for price calculation
        ['category_id', 'word_count', 'deadline', 'academic_level'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', updatePriceBreakdown);
                element.addEventListener('input', updatePriceBreakdown);
            }
        });
        
        // Set minimum deadline
        document.getElementById('deadline').min = new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString().slice(0, 16);
        
        // Responsive layout
        function adjustOrderLayout() {
            const grid = document.querySelector('.order-grid');
            if (window.innerWidth <= 768) {
                grid.style.gridTemplateColumns = '1fr';
            } else {
                grid.style.gridTemplateColumns = '2fr 1fr';
            }
        }
        
        window.addEventListener('resize', adjustOrderLayout);
        adjustOrderLayout();
        
        // Initial price calculation
        updatePriceBreakdown();
    </script>
    
    <style>
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr !important;
            }
            
            .navbar-nav {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>
