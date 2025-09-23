# Password Strength Checker Web Application

A comprehensive, secure web-based password strength analysis tool built with PHP 8+, MySQL, and JavaScript. Features advanced password evaluation, user authentication with 2FA, role-based access control, and administrative monitoring.

## 🚀 Features

### Core Functionality
- **Advanced Password Analysis**: Entropy calculation, complexity checking, pattern detection
- **Dictionary Attack Simulation**: Comparison against 50+ common passwords with variations
- **Real-time Strength Meter**: Visual feedback with JavaScript progress bar
- **Smart Suggestions**: AI-powered password generation with multiple algorithms
- **Crack Time Estimation**: Security assessment based on computational complexity

### Security Features
- **User Authentication**: Secure login/registration with bcrypt password hashing
- **Two-Factor Authentication**: OTP verification system (email simulation)
- **Role-Based Access Control**: Admin and User roles with appropriate permissions
- **Rate Limiting**: Protection against brute force attacks (5 attempts per 15 minutes)
- **Session Security**: Secure session management with regeneration and timeouts
- **CSRF Protection**: Token-based protection against cross-site request forgery
- **Input Sanitization**: Comprehensive validation and sanitization of all inputs
- **SQL Injection Prevention**: Prepared statements throughout the application

### DevSecOps Features
- **SonarQube Integration**: Automated code quality and security analysis
- **GitHub Actions CI/CD**: Comprehensive DevSecOps pipeline with security scanning
- **OWASP Security Testing**: Automated vulnerability assessment with ZAP
- **Docker Environment**: Containerized development and security testing
- **Quality Gates**: Zero-tolerance security vulnerability policy
- **Custom Security Rules**: PHP and JavaScript security pattern detection

### Administrative Features
- **User Management**: Create, modify, activate/deactivate user accounts
- **Security Monitoring**: Real-time login attempt tracking and suspicious activity detection
- **Analytics Dashboard**: Password strength distribution and usage statistics
- **Audit Logging**: Comprehensive logging of all security-related events

## 📋 Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.4+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Extensions**: PDO, PDO_MySQL, OpenSSL, JSON
- **Composer**: For PHP dependency management
- **Git**: For version control (required for SonarCloud)
- **CI/CD**: GitHub Actions enabled repository

## 🛠️ Installation

### 1. Clone/Download the Project
```bash
git clone <repository-url>
# or download and extract the ZIP file
```

### 2. Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE password_checker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u username -p password_checker < database.sql
```

3. Update database configuration in `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'password_checker');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Hide server information
ServerTokens Prod
ServerSignature Off

# Prevent access to sensitive files
<Files ~ "\.(log|sql|md)$">
    Require all denied
</Files>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/password-checker;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ \.(log|sql|md)$ {
        deny all;
    }
}
```

### 4. File Permissions
```bash
# Set appropriate permissions
chmod 755 /path/to/password-checker
chmod 644 /path/to/password-checker/*.php
chmod 700 /path/to/password-checker/logs
chmod 600 /path/to/password-checker/config/db.php
```

### 5. SSL/TLS Setup (Production)
For production environments, configure SSL/TLS:
```bash
# Using Certbot for Let's Encrypt
certbot --apache -d your-domain.com
```

Update `config/db.php` for HTTPS:
```php
ini_set('session.cookie_secure', 1); // Enable for HTTPS
define('BASE_URL', 'https://your-domain.com/password-checker');
```

### 6. DevSecOps Setup (Optional)

For comprehensive security analysis and CI/CD integration with SonarCloud:

#### GitHub Actions Setup
1. Add repository secrets:
   - `SONAR_TOKEN`: Your SonarCloud project token
2. Push code to trigger automated security analysis

For detailed setup, see [SONARCLOUD-SETUP.md](SONARCLOUD-SETUP.md)

## 🎯 Usage

### Default Admin Account
- **Username**: `admin`
- **Password**: `Admin123!`
- **Role**: Administrator

### User Registration
1. Visit the homepage
2. Click "Register" tab
3. Fill in username, email, and secure password
4. Account is automatically activated

### Password Checking
1. **Public Demo**: Available on homepage without login
2. **Authenticated Users**: Full features including suggestions and history
3. **Real-time Analysis**: Strength meter updates as you type

### Admin Features
1. Access admin panel via "Admin Panel" link (admin users only)
2. Monitor user activity and system statistics
3. Manage user accounts (activate/deactivate, reset passwords)
4. View security analytics and suspicious activity

## 🔧 Configuration Options

### Password Policy (`config/db.php`)
```php
define('MIN_PASSWORD_LENGTH', 8);        // Minimum password length
define('MAX_PASSWORD_LENGTH', 128);      // Maximum password length
define('SESSION_LIFETIME', 3600);        // Session timeout (seconds)
define('MAX_LOGIN_ATTEMPTS', 5);         // Max failed attempts before lockout
define('LOGIN_LOCKOUT_TIME', 900);       // Lockout duration (seconds)
define('OTP_EXPIRY_TIME', 300);          // 2FA OTP expiry (seconds)
```

### Security Features
- **Rate Limiting**: Automatic IP-based blocking after failed attempts
- **Session Management**: Secure session handling with regeneration
- **CSRF Protection**: All forms protected with CSRF tokens
- **Input Validation**: Server-side validation and sanitization
- **Password Hashing**: bcrypt with automatic salt generation

## 📊 API Endpoints

### Password Evaluation
```http
POST /password/check.php
Content-Type: application/x-www-form-urlencoded

password=your_password_here
```

Response:
```json
{
    "success": true,
    "result": {
        "score": 85,
        "strength_level": "strong",
        "entropy": 52.3,
        "feedback": ["Add more characters for extra security"],
        "estimated_crack_time": "2.5 years"
    }
}
```

### Password Suggestions
```http
POST /password/suggest.php
Content-Type: application/x-www-form-urlencoded

current_password=weak123
```

## 🛡️ Security Considerations

### Production Deployment Checklist
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure proper file permissions (644 for PHP files, 600 for config)
- [ ] Set up regular database backups
- [ ] Enable error logging but disable error display
- [ ] Configure firewall rules (allow only HTTP/HTTPS)
- [ ] Set up monitoring for suspicious activity
- [ ] Regular security updates for PHP and MySQL
- [ ] Configure email for 2FA OTP delivery
- [ ] Set strong database passwords
- [ ] Enable MySQL slow query log monitoring

### Security Headers
The application includes security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security` (HTTPS only)
- `Referrer-Policy: strict-origin-when-cross-origin`

## 📝 Database Schema

### Tables
- **users**: User accounts with authentication data
- **common_passwords**: Dictionary for weak password detection
- **login_attempts**: Security monitoring and rate limiting
- **password_evaluations**: Analytics and usage tracking

### Indexes
- Optimized queries with appropriate indexes on frequently accessed columns
- Foreign key constraints for data integrity
- Full-text search on password dictionary

## 🔍 Monitoring & Logging

### Application Logs
```bash
tail -f logs/app.log
```

### Security Events Logged
- Login attempts (successful/failed)
- Password strength evaluations
- Administrative actions
- Suspicious activity (rate limiting triggers)
- System errors and exceptions

## 🚀 Development

### Adding New Features
1. Follow PSR-4 autoloading standards
2. Use prepared statements for all database queries
3. Implement CSRF protection for all forms
4. Validate and sanitize all inputs
5. Log security-relevant events

### Testing
- Test password evaluation with various complexity levels
- Verify rate limiting functionality
- Test 2FA workflow
- Validate admin permissions and access controls
- Security testing for XSS, SQL injection, CSRF

## 📞 Support & Contributing

### Common Issues
1. **Database Connection**: Check credentials in `config/db.php`
2. **Permission Denied**: Ensure proper file permissions
3. **Session Issues**: Check session configuration and storage
4. **2FA Not Working**: Verify OTP generation and session handling

### Contributing
1. Follow existing code style and security practices
2. Test all changes thoroughly
3. Update documentation for new features
4. Ensure backward compatibility

## 📄 License

This project is provided for educational and demonstration purposes. For production use, ensure proper security auditing and compliance with applicable regulations.

---

**⚠️ Security Notice**: This is a demonstration application. For production deployment, implement additional security measures including proper email delivery for 2FA, comprehensive input validation, and regular security audits.

**Version**: 1.0.0  
**PHP Version**: 8.0+  
**Last Updated**: December 2024