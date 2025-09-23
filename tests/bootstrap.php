<?php
/**
 * Bootstrap file for PHPUnit tests
 * Sets up comprehensive test environment with mocked functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test constants if not already defined
if (!defined('MAX_PASSWORD_LENGTH')) {
    define('MAX_PASSWORD_LENGTH', 128);
}
if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

// Mock global variables for testing if they don't exist
if (!isset($_SESSION)) {
    $_SESSION = [];
}

if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
}

if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

// Include composer autoloader if it exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Try to include the main configuration file with error handling
$config_file = __DIR__ . '/../config/db.php';
if (file_exists($config_file)) {
    try {
        require_once $config_file;
    } catch (Exception $e) {
        echo "Warning: Could not load config file: " . $e->getMessage() . "\n";
    }
}

// Mock essential functions if they don't exist
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            default:
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('getClientIP')) {
    function getClientIP() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        
        return '127.0.0.1';
    }
}

if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($event, $details = '') {
        return true; // Mock logging for tests
    }
}

// Mock Database class if not already defined
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function __construct() {
            // Mock database connection for testing
        }
    }
}

echo "🚀 Bootstrap loaded successfully for PHPUnit tests with mocked functions\n";
?>