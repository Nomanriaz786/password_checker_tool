<?php
/**
 * Bootstrap file for PHPUnit tests
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

// Include the main configuration file with correct path
require_once __DIR__ . '/../config/db.php';

echo "Bootstrap loaded for PHPUnit tests\n";
?>