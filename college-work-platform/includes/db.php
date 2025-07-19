<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            if (defined('USE_SQLITE') && USE_SQLITE) {
                // Use SQLite for demo
                $this->conn = new PDO(
                    "sqlite:" . __DIR__ . "/../" . $this->db_name,
                    null,
                    null,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    )
                );
            } else {
                // Use MySQL
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    )
                );
            }
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Database Schema Creation (run once)
function createTables($pdo) {
    $queries = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            academic_level VARCHAR(20) DEFAULT 'undergraduate',
            role VARCHAR(20) DEFAULT 'student',
            status VARCHAR(20) DEFAULT 'active',
            email_verified INTEGER DEFAULT 0,
            verification_token VARCHAR(255),
            balance DECIMAL(10,2) DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            base_price DECIMAL(8,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Orders table
        "CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            writer_id INTEGER DEFAULT NULL,
            category_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            deadline DATE NOT NULL,
            word_count INTEGER NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'pending',
            special_instructions TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (writer_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )",

        // Order files table
        "CREATE TABLE IF NOT EXISTS order_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(20) DEFAULT 'reference',
            uploaded_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        )",

        // Messages table
        "CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // Payments table
        "CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(20) DEFAULT 'stripe',
            transaction_id VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // Notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
        } catch(PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
}

// Insert default categories
function insertDefaultCategories($pdo) {
    $categories = [
        ['Essays', 'Academic essays on any topic', 15.00],
        ['Research Papers', 'In-depth research papers with citations', 20.00],
        ['Coding Projects', 'Programming assignments and projects', 25.00],
        ['Case Studies', 'Detailed case study analysis', 18.00],
        ['Presentations', 'PowerPoint presentations with speaker notes', 12.00],
        ['Lab Reports', 'Scientific lab reports and analysis', 16.00]
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, base_price) VALUES (?, ?, ?)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
    }
}

// Create admin user if not exists
function createAdminUser($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, role, email_verified) 
                              VALUES (?, ?, ?, ?, 'admin', TRUE)");
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin@collegeworkhelper.com', $password, 'Admin', 'User']);
    }
}

// Initialize database
// Uncomment these lines to setup the database
// createTables($pdo);
// insertDefaultCategories($pdo);
// createAdminUser($pdo);
?>
