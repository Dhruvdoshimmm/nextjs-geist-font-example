<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Get categories for display
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

// Get some stats for display
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
$completed_orders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders");
$happy_clients = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title><?php echo SITE_NAME; ?> - Get Help With Your College Work</title>
    <meta name="description" content="Professional academic assistance for essays, research papers, coding projects, and more. Get expert help with your college assignments.">
    
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
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                    <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
                    <li><a href="#contact" class="nav-link">Contact</a></li>
                    
                    <?php if ($auth->isLoggedIn()): ?>
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="logout.php" class="btn btn-outline-primary">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="nav-link">Login</a></li>
                        <li><a href="register.php" class="btn btn-primary">Get Started</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Get Help With Your College Work</h1>
            <p>Professional academic assistance from qualified experts. Essays, research papers, coding projects, and more.</p>
            
            <div class="d-flex justify-content-center" style="gap: 1rem; margin-top: 2rem;">
                <?php if ($auth->isLoggedIn()): ?>
                    <a href="place-order.php" class="btn btn-primary btn-lg">Place New Order</a>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-lg" style="color: white; border-color: white;">View Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">Get Started Now</a>
                    <a href="#how-it-works" class="btn btn-outline-primary btn-lg" style="color: white; border-color: white;">Learn More</a>
                <?php endif; ?>
            </div>
            
            <!-- Trust Indicators -->
            <div class="d-flex justify-content-center" style="gap: 3rem; margin-top: 3rem; color: rgba(255,255,255,0.8);">
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700;"><?php echo number_format($completed_orders); ?>+</div>
                    <div style="font-size: 0.875rem;">Orders Completed</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700;"><?php echo number_format($happy_clients); ?>+</div>
                    <div style="font-size: 0.875rem;">Happy Students</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700;">24/7</div>
                    <div style="font-size: 0.875rem;">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" style="padding: 4rem 0; background-color: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Services</h2>
                <p class="text-secondary">We provide comprehensive academic assistance across various subjects and formats</p>
            </div>
            
            <div class="dashboard-stats">
                <?php foreach ($categories as $category): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="text-secondary"><?php echo htmlspecialchars($category['description']); ?></p>
                        <div class="text-primary" style="font-size: 1.25rem; font-weight: 600; margin: 1rem 0;">
                            Starting at <?php echo formatCurrency($category['base_price']); ?>
                        </div>
                        <?php if ($auth->isLoggedIn()): ?>
                            <a href="place-order.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">Order Now</a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary">Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" style="padding: 4rem 0;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>How It Works</h2>
                <p class="text-secondary">Get your academic work done in three simple steps</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="card">
                    <div class="card-body text-center">
                        <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">1</div>
                        <h5>Place Your Order</h5>
                        <p class="text-secondary">Fill out our simple order form with your requirements, deadline, and upload any reference materials.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">2</div>
                        <h5>Expert Works on It</h5>
                        <p class="text-secondary">Our qualified professionals work on your assignment, keeping you updated throughout the process.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: 700;">3</div>
                        <h5>Receive Your Work</h5>
                        <p class="text-secondary">Get your completed assignment on time with free revisions if needed.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" style="padding: 4rem 0; background-color: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>What Students Say</h2>
                <p class="text-secondary">Real feedback from students who have used our services</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--warning-color); font-size: 1.25rem; margin-bottom: 1rem;">★★★★★</div>
                        <p>"Amazing service! Got my research paper done perfectly and on time. The writer was very professional and followed all my instructions."</p>
                        <div style="font-weight: 600; color: var(--text-primary);">- Sarah M., Psychology Major</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--warning-color); font-size: 1.25rem; margin-bottom: 1rem;">★★★★★</div>
                        <p>"The coding project help was exactly what I needed. Clean code, well-commented, and delivered before the deadline."</p>
                        <div style="font-weight: 600; color: var(--text-primary);">- Mike T., Computer Science</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--warning-color); font-size: 1.25rem; margin-bottom: 1rem;">★★★★★</div>
                        <p>"Great communication throughout the process. The essay was well-researched and properly formatted. Highly recommend!"</p>
                        <div style="font-weight: 600; color: var(--text-primary);">- Emma L., English Literature</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Guarantees Section -->
    <section style="padding: 4rem 0;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Guarantees</h2>
                <p class="text-secondary">We stand behind our work with these commitments</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">100% Original Work</h5>
                        <p class="text-secondary">All work is written from scratch and checked for plagiarism.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">On-Time Delivery</h5>
                        <p class="text-secondary">We guarantee delivery by your specified deadline.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">Free Revisions</h5>
                        <p class="text-secondary">Unlimited revisions until you're completely satisfied.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">24/7 Support</h5>
                        <p class="text-secondary">Round-the-clock customer support for all your needs.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">Money Back</h5>
                        <p class="text-secondary">Full refund if we can't meet your requirements.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h5 style="color: var(--success-color);">Privacy Protected</h5>
                        <p class="text-secondary">Your personal information is completely confidential.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" style="padding: 4rem 0; background-color: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Get In Touch</h2>
                <p class="text-secondary">Have questions? We're here to help!</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="card">
                    <div class="card-body">
                        <h5>Contact Information</h5>
                        <div style="margin: 1rem 0;">
                            <strong>Email:</strong> <?php echo ADMIN_EMAIL; ?>
                        </div>
                        <div style="margin: 1rem 0;">
                            <strong>Support Hours:</strong> 24/7
                        </div>
                        <div style="margin: 1rem 0;">
                            <strong>Response Time:</strong> Within 1 hour
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5>Quick Contact</h5>
                        <form action="contact.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="contact_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="contact_name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact_email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_message" class="form-label">Message</label>
                                <textarea class="form-control" id="contact_message" name="message" rows="4" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background-color: var(--dark-color); color: white; padding: 2rem 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div>
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p style="color: rgba(255,255,255,0.8);">Professional academic assistance for students worldwide. Get expert help with your college assignments.</p>
                </div>
                
                <div>
                    <h6>Quick Links</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="#services" style="color: rgba(255,255,255,0.8);">Services</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#how-it-works" style="color: rgba(255,255,255,0.8);">How It Works</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="register.php" style="color: rgba(255,255,255,0.8);">Get Started</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="login.php" style="color: rgba(255,255,255,0.8);">Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h6>Support</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="#contact" style="color: rgba(255,255,255,0.8);">Contact Us</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="faq.php" style="color: rgba(255,255,255,0.8);">FAQ</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="privacy.php" style="color: rgba(255,255,255,0.8);">Privacy Policy</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="terms.php" style="color: rgba(255,255,255,0.8);">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 2rem 0;">
            
            <div class="text-center">
                <p style="color: rgba(255,255,255,0.6); margin: 0;">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script src="../public/js/main.js"></script>
</body>
</html>
