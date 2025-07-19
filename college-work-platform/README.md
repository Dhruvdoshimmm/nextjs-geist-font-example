# College Work Booking Platform

A comprehensive PHP-based platform for students to book academic assistance services from qualified professionals. The system features both user and admin panels with complete order management, payment processing, and communication systems.

## Features

### User Panel
- **Homepage**: Hero section, service categories, testimonials, and guarantees
- **Registration/Login**: Secure authentication with email verification
- **Dashboard**: Order overview, statistics, and notifications
- **Order Placement**: Multi-step form with price calculation and file uploads
- **Order Tracking**: Real-time status updates and messaging
- **Payment System**: Secure payment processing integration
- **Profile Management**: Account settings and preferences

### Admin Panel
- **Dashboard**: Comprehensive analytics and system overview
- **Order Management**: Assign orders, monitor progress, handle revisions
- **User Management**: View users, activity logs, account management
- **Writer Management**: Onboard writers, performance metrics, payments
- **Financial System**: Revenue reports, payouts, refund processing
- **Content Management**: Service categories, pricing, system settings

## Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL with PDO
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Styling**: Custom CSS with Google Fonts
- **Security**: bcrypt hashing, prepared statements, CSRF protection
- **File Handling**: Secure upload system with validation

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for future dependencies)

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   git clone <repository-url>
   cd college-work-platform
   ```

2. **Configure Database**
   - Create a MySQL database
   - Update database credentials in `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. **Set File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/assignments/
   chmod 755 uploads/completed/
   ```

4. **Run Database Setup**
   - Navigate to `http://your-domain/college-work-platform/setup.php`
   - Click "Initialize Database" to create tables and default data

5. **Configure Site Settings**
   - Update `SITE_URL` in `includes/config.php`
   - Configure email settings for notifications
   - Set up payment gateway credentials (Stripe/PayPal)

### Default Admin Account
- **Email**: admin@collegeworkhelper.com
- **Password**: admin123

**Important**: Change the default admin password after first login!

## Directory Structure

```
college-work-platform/
├── includes/           # Core PHP files
│   ├── config.php     # Configuration settings
│   ├── db.php         # Database connection and schema
│   ├── auth.php       # Authentication system
│   └── functions.php  # Utility functions
├── public/            # Static assets
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript files
│   └── images/       # Image assets
├── user/             # User-facing pages
│   ├── index.php     # Homepage
│   ├── register.php  # User registration
│   ├── login.php     # User login
│   ├── dashboard.php # User dashboard
│   └── place-order.php # Order placement
├── admin/            # Admin panel
│   └── index.php     # Admin dashboard
├── api/              # API endpoints
├── uploads/          # File uploads
│   ├── assignments/  # Reference files
│   └── completed/    # Completed work
└── setup.php         # Database setup script
```

## Security Features

### Authentication Security
- bcrypt password hashing
- Session management with timeout
- Brute force protection
- Account lockout system
- CSRF token protection

### Data Protection
- Server-side input validation
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- File upload restrictions and validation
- Secure file storage

### System Hardening
- HTTPS enforcement ready
- Security headers implementation
- Error logging without sensitive data
- File permission restrictions

## Configuration

### Email Settings
Update email configuration in `includes/config.php`:
```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@domain.com');
define('SMTP_PASS', 'your-app-password');
```

### Payment Integration
Configure payment gateways:
```php
// Stripe
define('STRIPE_PUBLIC_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
```

### File Upload Settings
Adjust file upload limits:
```php
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png']);
```

## Usage

### For Students
1. Register an account with academic level
2. Browse available services and pricing
3. Place orders with detailed requirements
4. Upload reference materials
5. Track order progress and communicate with writers
6. Download completed work and request revisions

### For Administrators
1. Login to admin panel
2. Monitor system statistics and alerts
3. Assign orders to qualified writers
4. Manage user accounts and permissions
5. Process payments and handle refunds
6. Generate reports and analytics

## API Endpoints

The platform includes API endpoints for:
- Order processing
- Status updates
- Payment handling
- Notification management
- File operations

## Database Schema

### Main Tables
- `users`: User accounts and profiles
- `orders`: Order details and status
- `categories`: Service categories and pricing
- `payments`: Transaction records
- `messages`: Communication system
- `notifications`: User notifications
- `order_files`: File attachments

## Customization

### Styling
- Modify `public/css/style.css` for general styling
- Update `public/css/admin.css` for admin panel
- Colors and fonts can be changed via CSS variables

### Functionality
- Add new service categories via admin panel
- Customize pricing algorithms in `includes/functions.php`
- Extend user roles and permissions
- Add new notification types

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**
   - Check directory permissions (755)
   - Verify PHP upload limits
   - Ensure upload directories exist

3. **Email Not Working**
   - Configure SMTP settings
   - Check firewall/hosting restrictions
   - Verify email credentials

4. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable
   - Verify session timeout settings

### Debug Mode
Enable debug mode in `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations

### Production Deployment
1. Disable error reporting
2. Use HTTPS for all connections
3. Regular security updates
4. Database backups
5. Monitor access logs
6. Implement rate limiting
7. Use strong passwords
8. Regular security audits

### Recommended Security Headers
```apache
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000"
```

## Support

For technical support or questions:
- Check the troubleshooting section
- Review configuration settings
- Examine error logs
- Test with default settings

## License

This project is developed for educational and commercial use. Please ensure compliance with local regulations regarding academic assistance services.

## Contributing

When contributing to this project:
1. Follow existing code style
2. Test thoroughly before submitting
3. Document new features
4. Maintain security standards
5. Update this README as needed

---

**Note**: This platform is designed to facilitate legitimate academic assistance. Users and administrators should ensure compliance with educational institution policies and local regulations.
