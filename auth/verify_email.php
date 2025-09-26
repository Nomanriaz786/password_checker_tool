<?php
session_start();
require_once '../config/db.php';

// Check if user is in verification process
if (!isset($_SESSION['pending_verification'])) {
    header('Location: ../index.php?error=no_pending_verification');
    exit;
}

$user_data = $_SESSION['pending_verification'];

// Check if verification session has expired (30 minutes)
if (time() - $user_data['registration_time'] > 1800) {
    // Clean up expired user record
    $db = Database::getInstance();
    $db->query("DELETE FROM users WHERE id = ? AND is_verified = 0", [$user_data['user_id']]);
    
    unset($_SESSION['pending_verification']);
    header('Location: ../index.php?error=verification_expired');
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
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify') {
        $otp_input = sanitizeInput($_POST['otp'] ?? '');
        
        if (empty($otp_input) || !preg_match('/^\d{6}$/', $otp_input)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit OTP']);
            exit;
        }
        
        // Verify OTP
        $emailUtil = EmailUtility::getInstance();
        $verification_result = $emailUtil->verifyOTP($user_data['user_id'], $otp_input);
        
        if ($verification_result['success']) {
            try {
                $db = Database::getInstance();
                
                // Activate and verify user account
                $db->query(
                    "UPDATE users SET is_active = 1, is_verified = 1 WHERE id = ?",
                    [$user_data['user_id']]
                );
                
                // Clear OTP data
                $emailUtil->clearOTP($user_data['user_id']);
                
                // Log successful verification
                logLoginAttempt($user_data['user_id'], $user_data['username'], 'verification_success', getClientIP());
                
                // Auto-login after successful verification
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['email'] = $user_data['email'];
                $_SESSION['role'] = 'user';
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Clear verification session
                unset($_SESSION['pending_verification']);
                
                session_regenerate_id(true);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Email verified successfully! Welcome to Password Strength Checker.',
                    'redirect' => '../dashboard.php'
                ]);
                
            } catch (Exception $e) {
                logError('Email verification error: ' . $e->getMessage(), [
                    'user_id' => $user_data['user_id'],
                    'ip' => getClientIP()
                ]);
                
                echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
            }
        } else {
            // Log failed verification attempt
            logLoginAttempt($user_data['user_id'], $user_data['username'], 'verification_failed', getClientIP());
            
            echo json_encode(['success' => false, 'message' => $verification_result['message']]);
        }
        
    } elseif ($action === 'resend') {
        $emailUtil = EmailUtility::getInstance();
        $resend_result = $emailUtil->resendOTP(
            $user_data['user_id'], 
            $user_data['email'], 
            $user_data['username'], 
            'registration'
        );
        
        echo json_encode($resend_result);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .verify-email-container {
            max-width: 450px;
            margin: 2rem auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .verify-email-form {
            background: white;
            border-radius: 18px;
            padding: 2.5rem;
            text-align: center;
        }
        
        .verification-icon {
            font-size: 4rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .verify-title {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .verify-subtitle {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .email-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .otp-input {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: 0.5rem;
            padding: 1rem;
            border: 3px solid #e0e6ed;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }
        
        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .verify-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .back-btn {
            background: transparent;
            border: 2px solid #e0e6ed;
            color: #666;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .resend-section {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0 1rem 0;
            border-left: 4px solid #667eea;
        }
        
        .resend-text {
            color: #666;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .countdown-section {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .countdown-item {
            flex: 1;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border: 2px solid #f0f0f0;
            text-align: center;
        }
        
        .countdown-item i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .countdown-label {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .countdown-time {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .message.success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            color: white;
            border: none;
        }
        
        .message.error {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
            border: none;
        }
        
        @media (max-width: 768px) {
            .verify-email-container {
                margin: 1rem;
                max-width: none;
            }
            
            .verify-email-form {
                padding: 2rem 1.5rem;
            }
            
            .otp-input {
                font-size: 1.5rem;
                letter-spacing: 0.3rem;
            }
            
            .countdown-section {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verify-email-container">
            <div class="verify-email-form">
                <div class="form-header">
                    <i class="fas fa-envelope-open-text verification-icon"></i>
                    <h2 class="verify-title">Verify Your Email</h2>
                    <p class="verify-subtitle">We've sent a 6-digit verification code to:</p>
                    <div class="email-display"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>
                
                <form id="verifyEmailForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="form-group">
                        <label for="otp" class="otp-label">Enter Verification Code:</label>
                        <input type="text" 
                               id="otp" 
                               name="otp" 
                               maxlength="6" 
                               pattern="\d{6}" 
                               placeholder="000000"
                               class="otp-input"
                               autocomplete="one-time-code"
                               required>
                    </div>
                    
                    <div class="form-group verification-button-group">
                        <button type="submit" class="btn verify-btn mb-2">
                            <i class="fas fa-check"></i> Verify Email
                        </button>
                        <a href="../index.php" class="btn back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                    
                    <div id="message" class="message message-hidden"></div>
                </form>
            
            <div class="form-footer">
                <div class="resend-section">
                    <p class="resend-text">Didn't receive the code?</p>
                    <button id="resendOtp" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-redo"></i> Resend Code
                    </button>
                </div>
                
                <div class="countdown-section">
                    <div class="countdown-item">
                        <i class="fas fa-clock text-warning"></i>
                        <span class="countdown-label">Code expires in:</span>
                        <span id="countdown" class="countdown-time">10:00</span>
                    </div>
                    <div class="countdown-item">
                        <i class="fas fa-hourglass-half text-info"></i>
                        <span class="countdown-label">Session expires in:</span>
                        <span id="registration-countdown" class="countdown-time"><?php echo floor((1800 - (time() - $user_data['registration_time'])) / 60); ?> minutes</span>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verifyEmailForm');
            const messageDiv = document.getElementById('message');
            const otpInput = document.getElementById('otp');
            const countdownSpan = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendOtp');
            
            // Auto-focus on OTP input
            otpInput.focus();
            
            // Only allow digits in OTP input
            otpInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
            
            // OTP countdown timer (10 minutes)
            let timeLeft = 600;
            const countdown = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownSpan.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    showMessage('Verification code has expired. Please request a new one.', 'error');
                    resendBtn.style.display = 'inline-block';
                }
                timeLeft--;
            }, 1000);
            
            // Registration session countdown (30 minutes)
            let registrationTimeLeft = <?php echo 1800 - (time() - $user_data['registration_time']); ?>;
            const registrationCountdown = setInterval(function() {
                const minutes = Math.floor(registrationTimeLeft / 60);
                document.getElementById('registration-countdown').textContent = `${minutes} minutes`;
                
                if (registrationTimeLeft <= 0) {
                    clearInterval(registrationCountdown);
                    showMessage('Registration session expired. Please register again.', 'error');
                    setTimeout(() => {
                        window.location.href = '../index.php?error=registration_expired';
                    }, 3000);
                }
                registrationTimeLeft -= 60;
            }, 60000);
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                
                fetch('verify_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
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
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Verify Email';
                });
            });
            
            // Resend OTP functionality
            resendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const originalText = resendBtn.innerHTML;
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                const formData = new FormData();
                formData.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
                formData.append('action', 'resend');
                
                fetch('verify_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showMessage(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        // Reset countdown
                        timeLeft = 600;
                        resendBtn.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred while resending code.', 'error');
                })
                .finally(() => {
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = originalText;
                });
            });
            
            function showMessage(text, type) {
                messageDiv.textContent = text;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';
                
                setTimeout(() => {
                    if (type !== 'success' || !text.includes('verified successfully')) {
                        messageDiv.style.display = 'none';
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>