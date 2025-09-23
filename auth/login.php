<?php
// Prevent any output before JSON response
ob_start();
error_reporting(E_ERROR | E_PARSE); // Only show fatal errors
ini_set('display_errors', '0'); // Don't display errors to browser

session_start();
require_once '../config/db.php';

// Clear any output buffer and set JSON header
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$ip_address = getClientIP();

// Input validation
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Check rate limiting
if (!checkRateLimit($ip_address, $username)) {
    logLoginAttempt(null, $username, 'blocked', $ip_address);
    echo json_encode([
        'success' => false, 
        'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Find user by username or email
    $sql = "SELECT id, username, email, password_hash, role, is_2fa_enabled, otp_secret, is_active, is_verified 
            FROM users 
            WHERE (username = ? OR email = ?) AND is_active = 1";
    
    $user = $db->fetch($sql, [$username, $username]);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Log failed attempt
        logLoginAttempt($user['id'] ?? null, $username, 'failure', $ip_address);
        
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Check if email is verified
    if (!$user['is_verified']) {
        logLoginAttempt($user['id'], $username, 'unverified', $ip_address);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Please verify your email address before logging in. Check your inbox for the verification code.',
            'requires_verification' => true
        ]);
        exit;
    }
    
    // Check if 2FA is enabled
    if ($user['is_2fa_enabled']) {
        // Include EmailUtility
        require_once '../config/email.php';
        
        // Generate OTP for 2FA
        $emailUtility = EmailUtility::getInstance();
        $otp = $emailUtility->generateOTP();
        $otp_expiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY_TIME);
        
        // Store OTP in session for verification
        $_SESSION['2fa_user_id'] = $user['id'];
        $_SESSION['2fa_otp'] = password_hash($otp, PASSWORD_DEFAULT);
        $_SESSION['2fa_expiry'] = $otp_expiry;
        $_SESSION['pending_login'] = true;
        
        // Send OTP via email
        $emailSent = $emailUtility->sendOTP($user['email'], $user['username'], $otp, 'login');
        
        if (!$emailSent) {
            // Log email failure but don't block login - show the OTP for development
            logError('Failed to send 2FA OTP email', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);
        }
        
        // Response based on development mode
        $response = [
            'success' => true,
            'requires_2fa' => true,
            'message' => 'Please enter the OTP sent to your email address: ' . $user['email'],
            'redirect' => '2fa.php'
        ];
        
        // In development mode, also show OTP in logs
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            error_log("2FA Login OTP for {$user['username']} ({$user['email']}): $otp");
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Successful login without 2FA
    completeLogin($user, $ip_address);
    
} catch (Exception $e) {
    logError('Login error: ' . $e->getMessage(), [
        'username' => $username,
        'ip' => $ip_address
    ]);
    
    echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
}

function completeLogin($user, $ip_address) {
    // Log successful login
    logLoginAttempt($user['id'], $user['username'], 'success', $ip_address);
    
    // Update last login
    $db = Database::getInstance();
    $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Clear any 2FA session data
    unset($_SESSION['2fa_user_id'], $_SESSION['2fa_otp'], $_SESSION['2fa_expiry'], $_SESSION['pending_login']);
    
    $redirect = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Welcome back, ' . htmlspecialchars($user['username']),
        'redirect' => $redirect,
        'user' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// Global error handler to ensure JSON response
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'A system error occurred during login. Please try again.'
        ]);
    }
});
?>