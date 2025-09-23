<?php
session_start();
require_once '../config/db.php';
require_once '../config/email.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only handle POST requests
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

$action = sanitizeInput($_POST['action'] ?? '');
$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    $emailUtil = EmailUtility::getInstance();
    
    switch ($action) {
        case 'enable_2fa':
            // Generate and send OTP for 2FA setup
            $user = $db->fetch("SELECT email, username, is_2fa_enabled FROM users WHERE id = ?", [$user_id]);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            if ($user['is_2fa_enabled']) {
                echo json_encode(['success' => false, 'message' => '2FA is already enabled']);
                exit;
            }
            
            // Generate OTP
            $otp = $emailUtil->generateOTP();
            
            // Store OTP for 2FA setup
            $emailUtil->storeOTP($user_id, $otp, 10); // 10 minutes expiration
            
            // Send OTP email
            if ($emailUtil->sendOTP($user['email'], $user['username'], $otp, '2fa_setup')) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Verification code sent to your email',
                    'action' => 'verify_otp',
                    'step' => 'otp_verification'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send verification code']);
            }
            break;
            
        case 'verify_2fa_setup':
            $otp = sanitizeInput($_POST['otp'] ?? '');
            
            if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit code']);
                exit;
            }
            
            // Verify OTP
            $verification_result = $emailUtil->verifyOTP($user_id, $otp);
            
            if ($verification_result['success']) {
                // Enable 2FA for user
                $db->query("UPDATE users SET is_2fa_enabled = 1 WHERE id = ?", [$user_id]);
                
                // Clear OTP data
                $emailUtil->clearOTP($user_id);
                
                // Log the 2FA enablement
                logLoginAttempt($user_id, $_SESSION['username'], '2fa_enabled', getClientIP());
                
                echo json_encode([
                    'success' => true, 
                    'message' => '2FA has been successfully enabled for your account',
                    'status' => 'enabled'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $verification_result['message']]);
            }
            break;
            
        case 'disable_2fa':
            $password = $_POST['password'] ?? '';
            
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required to disable 2FA']);
                exit;
            }
            
            // Verify user password
            $user = $db->fetch("SELECT password, is_2fa_enabled FROM users WHERE id = ?", [$user_id]);
            
            if (!$user || !password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
                exit;
            }
            
            if (!$user['is_2fa_enabled']) {
                echo json_encode(['success' => false, 'message' => '2FA is not enabled']);
                exit;
            }
            
            // Disable 2FA
            $db->query("UPDATE users SET is_2fa_enabled = 0 WHERE id = ?", [$user_id]);
            
            // Clear any stored 2FA codes
            $emailUtil->clearOTP($user_id);
            
            // Log the 2FA disablement
            logLoginAttempt($user_id, $_SESSION['username'], '2fa_disabled', getClientIP());
            
            echo json_encode([
                'success' => true, 
                'message' => '2FA has been disabled for your account',
                'status' => 'disabled'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    logError('2FA management error: ' . $e->getMessage(), [
        'user_id' => $user_id,
        'action' => $action,
        'ip' => getClientIP()
    ]);
    
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>