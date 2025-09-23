<?php
/**
 * Database Configuration
 * Password Strength Checker Web App
 */

// Include security headers first
require_once __DIR__ . '/security_headers.php';

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'password_checker');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', '');     // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Password Strength Checker');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/Password_Checker_Tool');

// Security configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);

// Include email utility
require_once __DIR__ . '/email.php';
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('OTP_EXPIRY_TIME', 300); // 5 minutes

// Password policy
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 128);

class Database {
    private $pdo;
    private static $instance = null;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database operation failed");
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function rowCount($stmt) {
        return $stmt->rowCount();
    }
}

// Initialize session configuration with enhanced security
function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Use the secure session initialization from security_headers.php
        initializeSecureEnvironment();
        
        // Check for session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            session_unset();
            session_destroy();
            initializeSecureEnvironment(); // Restart with secure settings
        }
        $_SESSION['last_activity'] = time();
    }
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization functions
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Rate limiting for login attempts
function checkRateLimit($ip_address, $username = '') {
    $db = Database::getInstance();
    
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
            WHERE ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND attempt_type = 'failure'";
    
    $result = $db->fetch($sql, [$ip_address, LOGIN_LOCKOUT_TIME]);
    
    return $result['attempts'] < MAX_LOGIN_ATTEMPTS;
}

// Log login attempt
function logLoginAttempt($user_id, $username, $attempt_type, $ip_address) {
    $db = Database::getInstance();
    
    $sql = "INSERT INTO login_attempts (user_id, username, attempt_type, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    $db->query($sql, [$user_id, $username, $attempt_type, $ip_address, $user_agent]);
}

// Get client IP address
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Error logging
function logError($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context);
    }
    error_log($log_message, 3, __DIR__ . '/../logs/app.log');
}

// Initialize session when this file is included
initializeSession();
?>