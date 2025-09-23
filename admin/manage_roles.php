<?php
require_once '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

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

$action = sanitizeInput($_POST['action'] ?? '');
$target_user_id = intval($_POST['user_id'] ?? 0);
$admin_user_id = $_SESSION['user_id'];

if (empty($action) || $target_user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Prevent admin from modifying their own role (safety measure)
if ($target_user_id === $admin_user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot modify your own role']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get target user details
    $target_user = $db->fetch(
        "SELECT id, username, email, role, is_active, is_verified FROM users WHERE id = ?",
        [$target_user_id]
    );
    
    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $old_value = '';
    $new_value = '';
    $success_message = '';
    
    switch ($action) {
        case 'promote_admin':
            if ($target_user['role'] === 'admin') {
                echo json_encode(['success' => false, 'message' => 'User is already an admin']);
                exit;
            }
            
            $old_value = $target_user['role'];
            $new_value = 'admin';
            
            $db->query("UPDATE users SET role = 'admin' WHERE id = ?", [$target_user_id]);
            $success_message = "User {$target_user['username']} promoted to admin successfully";
            break;
            
        case 'demote_user':
            if ($target_user['role'] === 'user') {
                echo json_encode(['success' => false, 'message' => 'User is already a regular user']);
                exit;
            }
            
            $old_value = $target_user['role'];
            $new_value = 'user';
            
            $db->query("UPDATE users SET role = 'user' WHERE id = ?", [$target_user_id]);
            $success_message = "User {$target_user['username']} demoted to regular user successfully";
            break;
            
        case 'activate_user':
            if ($target_user['is_active']) {
                echo json_encode(['success' => false, 'message' => 'User is already active']);
                exit;
            }
            
            $old_value = 'inactive';
            $new_value = 'active';
            
            $db->query("UPDATE users SET is_active = 1 WHERE id = ?", [$target_user_id]);
            $success_message = "User {$target_user['username']} activated successfully";
            break;
            
        case 'deactivate_user':
            if (!$target_user['is_active']) {
                echo json_encode(['success' => false, 'message' => 'User is already inactive']);
                exit;
            }
            
            $old_value = 'active';
            $new_value = 'inactive';
            
            $db->query("UPDATE users SET is_active = 0 WHERE id = ?", [$target_user_id]);
            $success_message = "User {$target_user['username']} deactivated successfully";
            break;
            
        case 'verify_user':
            if ($target_user['is_verified']) {
                echo json_encode(['success' => false, 'message' => 'User is already verified']);
                exit;
            }
            
            $old_value = 'unverified';
            $new_value = 'verified';
            
            $db->query("UPDATE users SET is_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE id = ?", [$target_user_id]);
            $success_message = "User {$target_user['username']} manually verified successfully";
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    // Log admin action
    $db->query(
        "INSERT INTO admin_logs (admin_user_id, target_user_id, action, old_value, new_value, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?)",
        [
            $admin_user_id, 
            $target_user_id, 
            $action, 
            $old_value, 
            $new_value, 
            getClientIP()
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => $success_message,
        'user_id' => $target_user_id,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    logError('Role management error: ' . $e->getMessage(), [
        'admin_id' => $admin_user_id,
        'target_user_id' => $target_user_id,
        'action' => $action,
        'ip' => getClientIP()
    ]);
    
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating user role']);
}
?>