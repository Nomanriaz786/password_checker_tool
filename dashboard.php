<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'user';

// If email is not in session, fetch it from database
if (empty($email)) {
    try {
        $db = Database::getInstance();
        $user_data = $db->fetch("SELECT email, role, is_2fa_enabled FROM users WHERE id = ?", [$user_id]);
        if ($user_data) {
            $email = $user_data['email'];
            $role = $user_data['role'];
            $is_2fa_enabled = $user_data['is_2fa_enabled'];
            // Update session variables
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
        }
    } catch (Exception $e) {
        logError('Failed to fetch user data: ' . $e->getMessage(), ['user_id' => $user_id]);
        // Set default values if database fetch fails
        $email = 'Unknown';
        $role = 'user';
        $is_2fa_enabled = false;
    }
} else {
    // If email exists in session, still need to fetch 2FA status
    try {
        $db = Database::getInstance();
        $user_data = $db->fetch("SELECT is_2fa_enabled FROM users WHERE id = ?", [$user_id]);
        $is_2fa_enabled = $user_data ? $user_data['is_2fa_enabled'] : false;
    } catch (Exception $e) {
        logError('Failed to fetch 2FA status: ' . $e->getMessage(), ['user_id' => $user_id]);
        $is_2fa_enabled = false;
    }
}

try {
    $db = Database::getInstance();
    
    // Get user statistics
    $user_stats = $db->fetch("
        SELECT 
            COUNT(pe.id) as total_checks,
            AVG(pe.strength_score) as avg_strength,
            MAX(pe.created_at) as last_check,
            SUM(CASE WHEN pe.strength_score >= 0.6 THEN 1 ELSE 0 END) as strong_passwords,
            SUM(CASE WHEN pe.strength_score < 0.4 THEN 1 ELSE 0 END) as weak_passwords
        FROM password_evaluations pe 
        WHERE pe.user_id = ?
    ", [$user_id]);
    
    // Get recent password evaluations
    $recent_checks = $db->fetchAll("
        SELECT 
            password_length,
            strength_score,
            has_uppercase,
            has_lowercase,
            has_digits,
            has_symbols,
            strength_category,
            created_at
        FROM password_evaluations 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ", [$user_id]);
    
    // Get login history
    $login_history = $db->fetchAll("
        SELECT 
            created_at,
            attempt_type,
            ip_address
        FROM login_attempts 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$user_id]);
    
} catch (Exception $e) {
    logError('Dashboard error: ' . $e->getMessage(), ['user_id' => $user_id]);
    $user_stats = ['total_checks' => 0, 'avg_strength' => 0, 'last_check' => null, 'strong_passwords' => 0, 'weak_passwords' => 0];
    $recent_checks = [];
    $login_history = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><i class="fas fa-lock"></i> <?php echo APP_NAME; ?></a>
            <div class="navbar-menu">
                <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <?php if ($role === 'admin'): ?>
                    <a href="admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Statistics Overview -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($user_stats['total_checks']); ?></div>
                <div class="stat-label">Password Checks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($user_stats['avg_strength'] * 100, 1); ?>%</div>
                <div class="stat-label">Average Strength</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($user_stats['strong_passwords']); ?></div>
                <div class="stat-label">Strong Passwords</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($user_stats['weak_passwords']); ?></div>
                <div class="stat-label">Weak Passwords</div>
            </div>
        </div>

        <!-- Password Strength Checker -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Password Strength Checker</h2>
                <p>Enter a password to analyze its security level and get improvement suggestions</p>
            </div>

            <div class="form-group">
                <label for="password_input">Password to Check:</label>
                <input type="password" 
                       id="password_input" 
                       placeholder="Enter password here..." 
                       data-strength="true"
                       style="font-family: monospace;">
            </div>

            <div class="form-group">
                <button type="button" id="generateBtn" class="btn btn-success">Generate Secure Password</button>
                <button type="button" id="generatePhraseBtn" class="btn btn-primary">Generate Passphrase</button>
                <button type="button" id="clearBtn" class="btn btn-secondary">Clear</button>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-cog"></i> Account Settings</h2>
                <p>Manage your account security and preferences</p>
            </div>

            <div class="settings-grid">
                <div class="setting-item">
                    <h3>Profile Information</h3>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst($role); ?></p>
                    <p><strong>Member Since:</strong> 
                        <?php 
                        $member_since = $db->fetch("SELECT created_at FROM users WHERE id = ?", [$user_id]);
                        if ($member_since && isset($member_since['created_at'])) {
                            echo date('F j, Y', strtotime($member_since['created_at']));
                        } else {
                            echo 'Unknown';
                        }
                        ?>
                    </p>
                </div>

                <div class="setting-item">
                    <h3>Security Settings</h3>
                    <p><strong>Two-Factor Authentication:</strong> 
                        <span id="2fa-status"><?php echo $is_2fa_enabled ? 'Enabled' : 'Disabled'; ?></span>
                    </p>
                    <button type="button" id="toggle2FA" class="btn btn-primary">
                        <?php echo $is_2fa_enabled ? 'Disable 2FA' : 'Enable 2FA'; ?>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($recent_checks)): ?>
        <!-- Recent Password Checks -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Recent Password Checks</h2>
                <p>Your last 10 password strength evaluations</p>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Length</th>
                            <th>Strength Score</th>
                            <th>Category</th>
                            <th>Character Types</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_checks as $check): ?>
                        <tr>
                            <td><?php echo date('M j, H:i', strtotime($check['created_at'])); ?></td>
                            <td><?php echo $check['password_length']; ?> chars</td>
                            <td>
                                <span class="strength-label <?php echo getStrengthClass($check['strength_score']); ?>">
                                    <?php echo number_format($check['strength_score'] * 100, 1); ?>%
                                </span>
                            </td>
                            <td><?php echo ucfirst($check['strength_category']); ?></td>
                            <td>
                                <?php echo ($check['has_uppercase'] ? 'A' : '-'); ?>
                                <?php echo ($check['has_lowercase'] ? 'a' : '-'); ?>
                                <?php echo ($check['has_digits'] ? '1' : '-'); ?>
                                <?php echo ($check['has_symbols'] ? '!' : '-'); ?>
                            </td>
                            <td>
                                <span class="badge badge-info">Analyzed</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($login_history)): ?>
        <!-- Login History -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-shield-alt"></i> Recent Login Activity</h2>
                <p>Monitor your account access history</p>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($login_history as $login): ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i:s', strtotime($login['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $login['attempt_type'] === 'success' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $login['attempt_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/strength.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password generation buttons
            document.getElementById('generateBtn').addEventListener('click', function() {
                const password = PasswordGenerator.generateSecure(16);
                updatePasswordInput(password);
            });

            document.getElementById('generatePhraseBtn').addEventListener('click', function() {
                const passphrase = PasswordGenerator.generatePassphrase(4);
                updatePasswordInput(passphrase);
            });

            document.getElementById('clearBtn').addEventListener('click', function() {
                document.getElementById('password_input').value = '';
                // Trigger input event to update meter
                const event = new Event('input', { bubbles: true });
                document.getElementById('password_input').dispatchEvent(event);
            });

            function updatePasswordInput(password) {
                const input = document.getElementById('password_input');
                input.value = password;
                
                // Trigger input event to update strength meter
                const event = new Event('input', { bubbles: true });
                input.dispatchEvent(event);
            }

            // 2FA Management
            document.getElementById('toggle2FA').addEventListener('click', function() {
                const status = document.getElementById('2fa-status');
                const currentStatus = status.textContent.trim();
                
                if (currentStatus === 'Disabled') {
                    // Enable 2FA - send OTP
                    enable2FA();
                } else {
                    // Disable 2FA - show password confirmation
                    showModal('disable2faModal');
                }
            });

            // Enable 2FA - Send OTP
            function enable2FA() {
                const btn = document.getElementById('toggle2FA');
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                const formData = new FormData();
                formData.append('action', 'enable_2fa');
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
                
                fetch('auth/manage_2fa.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        showModal('otpModal');
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            }

            // Handle OTP verification form
            document.getElementById('otp2faForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const otp = document.getElementById('otp2fa').value;
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                
                const formData = new FormData();
                formData.append('action', 'verify_2fa_setup');
                formData.append('otp', otp);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
                
                fetch('auth/manage_2fa.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        closeModal('otpModal');
                        update2FAStatus('Enabled', 'Disable 2FA');
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            });

            // Handle 2FA disable form
            document.getElementById('disable2faForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const password = document.getElementById('confirmPassword').value;
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Disabling...';
                
                const formData = new FormData();
                formData.append('action', 'disable_2fa');
                formData.append('password', password);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
                
                fetch('auth/manage_2fa.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'info');
                        closeModal('disable2faModal');
                        update2FAStatus('Disabled', 'Enable 2FA');
                        document.getElementById('confirmPassword').value = '';
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            });

            // Utility functions
            function update2FAStatus(status, buttonText) {
                document.getElementById('2fa-status').textContent = status;
                document.getElementById('toggle2FA').textContent = buttonText;
            }

            function showModal(modalId) {
                document.getElementById(modalId).style.display = 'block';
            }

            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
                // Clear form inputs
                const modal = document.getElementById(modalId);
                const inputs = modal.querySelectorAll('input');
                inputs.forEach(input => input.value = '');
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };

            function showMessage(text, type) {
                const message = document.createElement('div');
                message.className = `message ${type}`;
                message.textContent = text;
                
                const container = document.querySelector('.container');
                container.insertBefore(message, container.firstChild);
                
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 3000);
            }
        });
    </script>

    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .setting-item {
            padding: 1.5rem;
            background: var(--light-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .setting-item h3 {
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .setting-item p {
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: center;
            }
        }
    </style>

    <!-- 2FA Setup Modal -->
    <div id="otpModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Setup Two-Factor Authentication</h3>
                <span class="close" onclick="closeModal('otpModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>We've sent a verification code to your email. Please enter it below to enable 2FA:</p>
                <form id="otp2faForm">
                    <div class="form-group">
                        <label for="otp2fa">Verification Code:</label>
                        <input type="text" id="otp2fa" maxlength="6" pattern="\d{6}" placeholder="000000" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enable 2FA</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('otpModal')">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 2FA Disable Modal -->
    <div id="disable2faModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Disable Two-Factor Authentication</h3>
                <span class="close" onclick="closeModal('disable2faModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> Disabling 2FA will make your account less secure.</p>
                <p>Please enter your password to confirm:</p>
                <form id="disable2faForm">
                    <div class="form-group">
                        <label for="confirmPassword">Current Password:</label>
                        <input type="password" id="confirmPassword" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('disable2faModal')">Cancel</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>

<?php
function getStrengthClass($score) {
    if ($score >= 0.8) return 'very-strong';
    if ($score >= 0.6) return 'strong';
    if ($score >= 0.4) return 'medium';
    if ($score >= 0.2) return 'weak';
    return 'very-weak';
}
?>