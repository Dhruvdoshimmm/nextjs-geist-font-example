<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "Initializing database...\n";

try {
    // Create tables
    createTables($pdo);
    echo "✓ Tables created successfully\n";
    
    // Insert default categories
    insertDefaultCategories($pdo);
    echo "✓ Default categories inserted\n";
    
    // Create admin user
    createAdminUser($pdo);
    echo "✓ Admin user created\n";
    
    // Add activity logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Activity logs table created\n";
    
    echo "\nDatabase initialization completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@collegeworkhelper.com\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
