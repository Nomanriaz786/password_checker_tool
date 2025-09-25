<?php
require_once '../config/db.php';

// Check if user is in 2FA process
if (!isset($_SESSION['pending_login']) || !isset($_SESSION['2fa_user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if OTP has expired
if (isset($_SESSION['2fa_expiry']) && strtotime($_SESSION['2fa_expiry']) < time()) {
    unset($_SESSION['2fa_user_id'], $_SESSION['2fa_otp'], $_SESSION['2fa_expiry'], $_SESSION['pending_login']);
    header('Location: ../index.php?error=otp_expired');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $otp_input = sanitizeInput($_POST['otp'] ?? '');
    
    if (empty($otp_input) || !preg_match('/^\d{6}$/', $otp_input)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit OTP']);
        exit;
    }
    
    // Verify OTP
    if (!isset($_SESSION['2fa_otp']) || !password_verify($otp_input, $_SESSION['2fa_otp'])) {
        // Log failed 2FA attempt
        logLoginAttempt($_SESSION['2fa_user_id'], 'N/A', 'failure', getClientIP());
        
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        exit;
    }
    
    try {
        $db = Database::getInstance();
        
        // Get user details
        $user = $db->fetch(
            "SELECT id, username, email, role, is_active FROM users WHERE id = ? AND is_active = 1",
            [$_SESSION['2fa_user_id']]
        );
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Complete login process
        completeLogin($user, getClientIP());
        
    } catch (Exception $e) {
        logError('2FA verification error: ' . $e->getMessage(), [
            'user_id' => $_SESSION['2fa_user_id'],
            'ip' => getClientIP()
        ]);
        
        echo json_encode(['success' => false, 'message' => 'An error occurred during verification']);
    }
    
    exit;
}

function completeLogin($user, $ip_address) {
    // Log successful 2FA login
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
    
    // Clear 2FA session data
    unset($_SESSION['2fa_user_id'], $_SESSION['2fa_otp'], $_SESSION['2fa_expiry'], $_SESSION['pending_login']);
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $redirect = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
    
    echo json_encode([
        'success' => true,
        'message' => '2FA verification successful! Welcome back, ' . htmlspecialchars($user['username']),
        'redirect' => $redirect,
        'user' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <div class="form-header">
                <h2>Two-Factor Authentication</h2>
                <p>Please enter the 6-digit code sent to your email</p>
            </div>
            
            <form id="twoFactorForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="otp">Enter OTP Code:</label>
                    <input type="text" 
                           id="otp" 
                           name="otp" 
                           maxlength="6" 
                           pattern="\d{6}" 
                           placeholder="000000"
                           autocomplete="one-time-code"
                           required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Verify</button>
                    <a href="../index.php" class="btn btn-secondary">Cancel</a>
                </div>
                
                <div id="message" class="message" style="display: none;"></div>
            </form>
            
            <div class="form-footer">
                <p>Didn't receive the code? <a href="#" id="resendOtp">Resend OTP</a></p>
                <p><small>Code expires in <span id="countdown">5:00</span></small></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('twoFactorForm');
            const messageDiv = document.getElementById('message');
            const otpInput = document.getElementById('otp');
            const countdownSpan = document.getElementById('countdown');
            
            // Auto-focus on OTP input
            otpInput.focus();
            
            // Only allow digits in OTP input
            otpInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
            
            // Countdown timer
            let timeLeft = 300; // 5 minutes
            const countdown = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownSpan.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    showMessage('OTP has expired. Please login again.', 'error');
                    setTimeout(() => {
                        window.location.href = '../index.php?error=otp_expired';
                    }, 2000);
                }
                timeLeft--;
            }, 1000);
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Verifying...';
                
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = '../' + data.redirect;
                            }, 1500);
                        }
                    } else {
                        showMessage(data.message, 'error');
                        otpInput.value = '';
                        otpInput.focus();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Verify';
                });
            });
            
            // Resend OTP functionality (simulated)
            document.getElementById('resendOtp').addEventListener('click', function(e) {
                e.preventDefault();
                showMessage('OTP resent successfully!', 'success');
                // In production, this would trigger a new OTP email
            });
            
            function showMessage(text, type) {
                messageDiv.textContent = text;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';
                
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>