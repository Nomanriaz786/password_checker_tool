<?php
/**
 * Email utility class for sending OTP and other notifications
 */
class EmailUtility {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate a 6-digit OTP
     */
    public function generateOTP() {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send OTP via email (using PHP mail function for simplicity)
     */
    public function sendOTP($email, $username, $otp, $type = 'registration') {
        $subjects = [
            'registration' => 'Verify Your Account - OTP Code',
            'login' => 'Login Verification - OTP Code',
            '2fa_setup' => 'Enable Two-Factor Authentication - OTP Code'
        ];
        
        $subject = $subjects[$type] ?? 'Verification Code';
        
        $content = '';
        switch ($type) {
            case 'registration':
                $content = "<h3>Welcome, $username!</h3>
                <p>Thank you for registering with Password Strength Checker. To complete your registration, please verify your email address using the OTP code below:</p>";
                break;
            case 'login':
                $content = "<h3>Login Verification</h3>
                <p>Hello $username, we received a login request for your account. Please use the OTP code below to complete your login:</p>";
                break;
            case '2fa_setup':
                $content = "<h3>Enable Two-Factor Authentication</h3>
                <p>Hello $username, you requested to enable Two-Factor Authentication for your account. Please use the OTP code below to confirm and enable 2FA:</p>";
                break;
            default:
                $content = "<h3>Verification Required</h3>
                <p>Hello $username, please use the OTP code below to complete your verification:</p>";
        }
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #667eea; text-align: center;'>Password Strength Checker</h2>
                
                $content
                
                <div style='text-align: center; margin: 30px 0;'>
                    <div style='display: inline-block; background: #667eea; color: white; padding: 20px 30px; border-radius: 8px; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>
                        $otp
                    </div>
                </div>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This OTP is valid for 10 minutes</li>
                    <li>Do not share this code with anyone</li>
                    <li>If you didn't request this, please ignore this email</li>
                </ul>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #666; text-align: center;'>
                    This is an automated email. Please do not reply to this message.
                </p>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Password Checker <noreply@passwordchecker.local>',
            'Reply-To: noreply@passwordchecker.local',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // In development, we'll log the OTP instead of actually sending email
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            error_log("OTP for $email ($username): $otp");
            return true;
        }
        
        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Store OTP in database with expiration
     */
    public function storeOTP($userId, $otp, $expirationMinutes = 10) {
        $db = Database::getInstance();
        $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
        $expiration = date('Y-m-d H:i:s', time() + ($expirationMinutes * 60));
        
        $sql = "UPDATE users SET verification_code = ?, verification_code_expires = ? WHERE id = ?";
        return $db->query($sql, [$hashedOtp, $expiration, $userId]);
    }
    
    /**
     * Verify OTP against stored hash
     */
    public function verifyOTP($userId, $inputOtp) {
        $db = Database::getInstance();
        
        $user = $db->fetch(
            "SELECT verification_code, verification_code_expires FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || empty($user['verification_code'])) {
            return ['success' => false, 'message' => 'No OTP found for this user'];
        }
        
        // Check if OTP has expired
        if (strtotime($user['verification_code_expires']) < time()) {
            return ['success' => false, 'message' => 'OTP has expired'];
        }
        
        // Verify OTP
        if (!password_verify($inputOtp, $user['verification_code'])) {
            return ['success' => false, 'message' => 'Invalid OTP'];
        }
        
        return ['success' => true, 'message' => 'OTP verified successfully'];
    }
    
    /**
     * Clear OTP data after successful verification
     */
    public function clearOTP($userId) {
        $db = Database::getInstance();
        $sql = "UPDATE users SET verification_code = NULL, verification_code_expires = NULL WHERE id = ?";
        return $db->query($sql, [$userId]);
    }
    
    /**
     * Resend OTP with rate limiting
     */
    public function resendOTP($userId, $email, $username, $type = 'registration') {
        $db = Database::getInstance();
        
        // Check if user has requested too many OTPs recently
        $recentAttempts = $db->fetch(
            "SELECT COUNT(*) as count FROM login_attempts 
             WHERE user_id = ? AND attempt_type = 'otp_request' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$userId]
        );
        
        if ($recentAttempts && $recentAttempts['count'] >= 3) {
            return ['success' => false, 'message' => 'Too many OTP requests. Please wait 5 minutes before requesting again.'];
        }
        
        // Generate and send new OTP
        $otp = $this->generateOTP();
        
        if ($this->sendOTP($email, $username, $otp, $type)) {
            $this->storeOTP($userId, $otp);
            
            // Log OTP request
            $db->query(
                "INSERT INTO login_attempts (user_id, username, ip_address, attempt_type) VALUES (?, ?, ?, 'otp_request')",
                [$userId, $username, getClientIP()]
            );
            
            return ['success' => true, 'message' => 'OTP sent successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to send OTP'];
    }
}

// Enable development mode for testing
define('DEVELOPMENT_MODE', true);
?>