<?php
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

try {
    $db = Database::getInstance();
    
    // Log the logout
    if (isset($_SESSION['user_id'])) {
        $sql = "INSERT INTO login_attempts (user_id, username, attempt_type, ip_address, user_agent) 
                VALUES (?, ?, 'logout', ?, ?)";
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $db->query($sql, [
            $_SESSION['user_id'], 
            $_SESSION['username'], 
            getClientIP(), 
            $user_agent
        ]);
    }
    
    // Clear session data
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for flash messages
    session_start();
    $_SESSION['message'] = 'You have been successfully logged out.';
    $_SESSION['message_type'] = 'success';
    
    // Redirect to login page
    header('Location: ../index.php');
    exit;
    
} catch (Exception $e) {
    logError('Logout error: ' . $e->getMessage(), [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'ip' => getClientIP()
    ]);
    
    // Force logout even if logging fails
    session_destroy();
    header('Location: ../index.php');
    exit;
}
?>