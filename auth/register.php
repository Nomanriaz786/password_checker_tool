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
$email = sanitizeInput($_POST['email'] ?? '', 'email');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Input validation
$errors = [];

if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
    $errors[] = 'Username must be between 3 and 50 characters';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($password) || strlen($password) < MIN_PASSWORD_LENGTH) {
    $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Check password strength
if (!empty($password)) {
    $strength_result = checkPasswordStrength($password);
    if ($strength_result['score'] < 0.3) {
        $errors[] = 'Password is too weak. Please choose a stronger password.';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if username already exists
    $existing_user = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    
    if ($existing_user) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user (inactive until verification)
    $sql = "INSERT INTO users (username, email, password_hash, role, is_active, is_verified, created_at) VALUES (?, ?, ?, 'user', 0, 0, NOW())";
    $stmt = $db->query($sql, [$username, $email, $password_hash]);
    
    if ($stmt->rowCount() > 0) {
        $user_id = $db->lastInsertId();
        
        // Generate and send OTP for email verification
        $emailUtil = EmailUtility::getInstance();
        $otp = $emailUtil->generateOTP();
        
        if ($emailUtil->sendOTP($email, $username, $otp, 'registration')) {
            $emailUtil->storeOTP($user_id, $otp);
            
            // Store user data in session for verification process
            $_SESSION['pending_verification'] = [
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email,
                'registration_time' => time()
            ];
            
            // Log registration attempt
            logLoginAttempt($user_id, $username, 'registration', getClientIP());
            
            echo json_encode([
                'success' => true, 
                'message' => 'Registration successful! Please check your email for the verification code.',
                'redirect' => 'auth/verify_email.php'
            ]);
        } else {
            // If email sending fails, delete the user record
            $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
            echo json_encode(['success' => false, 'message' => 'Registration failed. Could not send verification email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    
} catch (Exception $e) {
    logError('Registration error: ' . $e->getMessage(), [
        'username' => $username,
        'email' => $email,
        'ip' => getClientIP()
    ]);
    
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration']);
}

// Basic password strength check function
function checkPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    $length = strlen($password);
    $has_lowercase = preg_match('/[a-z]/', $password);
    $has_uppercase = preg_match('/[A-Z]/', $password);
    $has_digits = preg_match('/\d/', $password);
    $has_symbols = preg_match('/[^a-zA-Z0-9]/', $password);
    
    // Length scoring
    if ($length >= 8) $score += 0.2;
    if ($length >= 12) $score += 0.2;
    if ($length >= 16) $score += 0.1;
    
    // Character variety scoring
    if ($has_lowercase) $score += 0.1;
    if ($has_uppercase) $score += 0.1;
    if ($has_digits) $score += 0.1;
    if ($has_symbols) $score += 0.2;
    
    // Feedback generation
    if (!$has_lowercase) $feedback[] = 'Add lowercase letters';
    if (!$has_uppercase) $feedback[] = 'Add uppercase letters';
    if (!$has_digits) $feedback[] = 'Add numbers';
    if (!$has_symbols) $feedback[] = 'Add special characters';
    if ($length < 12) $feedback[] = 'Make it longer (12+ characters)';
    
    return [
        'score' => min($score, 1.0),
        'feedback' => $feedback,
        'strength' => $score < 0.3 ? 'weak' : ($score < 0.7 ? 'medium' : 'strong')
    ];
}

// Global error handler to ensure JSON response
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'A system error occurred during registration. Please try again.'
        ]);
    }
});
?>