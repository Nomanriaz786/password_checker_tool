<?php
require_once 'config/db.php';

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $redirect = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
    header("Location: $redirect");
    exit;
}

// Get flash messages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Handle error messages from URL
$error = $_GET['error'] ?? '';
if ($error === 'otp_expired') {
    $message = 'Your OTP has expired. Please login again.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Enterprise Password Security Platform</title>
    <meta name="description" content="Professional password strength analysis and cybersecurity platform. Advanced password evaluation, security recommendations, and enterprise-grade protection.">
    <meta name="keywords" content="password security, cybersecurity, password checker, enterprise security, authentication">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-shield-alt"></i> Enterprise Security Platform
            </div>
            <h1 class="hero-title"><?php echo APP_NAME; ?></h1>
            <p class="hero-subtitle">Advanced Password Security Analysis & Cybersecurity Intelligence Platform</p>
            <div class="hero-features">
                <span class="hero-feature"><i class="fas fa-check"></i> Real-time Analysis</span>
                <span class="hero-feature"><i class="fas fa-check"></i> Enterprise Security</span>
                <span class="hero-feature"><i class="fas fa-check"></i> Two-Factor Auth</span>
            </div>
            <div class="hero-actions">
                <button type="button" class="btn btn-primary btn-hero" onclick="openAuthModal('register')">
                    <i class="fas fa-user-plus"></i> Get Started Free
                </button>
                <button type="button" class="btn btn-secondary btn-hero" onclick="openAuthModal('login')">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                <a href="#demo" class="btn btn-outline btn-hero">
                    <i class="fas fa-play"></i> Try Demo
                </a>
            </div>
        </div>
    </section>

    <div class="container main-content">

        <!-- Interactive Password Demo -->
        <section id="demo" class="demo-section">
            <div class="demo-container">
                <div class="demo-header">
                    <h2><i class="fas fa-microscope"></i> Interactive Password Analysis</h2>
                    <p class="demo-subtitle">Experience our advanced password evaluation engine</p>
                    <div class="demo-badge">
                        <i class="fas fa-shield-alt"></i> Live Analysis • No Data Stored
                    </div>
                </div>

                <div class="demo-input-group">
                    <label for="demo_password" class="demo-label">
                        <i class="fas fa-key"></i> Test Password Strength
                    </label>
                    <input type="password" 
                           id="demo_password" 
                           class="demo-input"
                           placeholder="Enter any password to analyze..."
                           data-strength="true">
                </div>

                <div class="demo-examples">
                    <h4 class="examples-title">
                        <i class="fas fa-vials"></i> Sample Passwords to Test
                    </h4>
                    <div class="example-grid">
                        <button type="button" class="example-card weak" data-password="password123">
                            <div class="example-strength">Weak</div>
                            <div class="example-password">password123</div>
                            <div class="example-description">Common pattern</div>
                        </button>
                        <button type="button" class="example-card strong" data-password="MySecure2024!">
                            <div class="example-strength">Strong</div>
                            <div class="example-password">MySecure2024!</div>
                            <div class="example-description">Mixed complexity</div>
                        </button>
                        <button type="button" class="example-card very-strong" data-password="correct-horse-battery-staple">
                            <div class="example-strength">Very Strong</div>
                            <div class="example-password">correct-horse-battery-staple</div>
                            <div class="example-description">Passphrase method</div>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Enterprise Features -->
        <section class="features-section">
            <div class="features-header">
                <h2><i class="fas fa-rocket"></i> Enterprise Security Platform</h2>
                <p class="features-subtitle">Advanced cybersecurity tools designed for modern organizations</p>
            </div>

            <div class="features-grid">
                <div class="feature-card primary">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>AI-Powered Analysis</h3>
                    <p>Advanced machine learning algorithms analyze password patterns, entropy calculations, and vulnerability assessments in real-time.</p>
                    <div class="feature-tags">
                        <span class="tag">Entropy Analysis</span>
                        <span class="tag">Pattern Detection</span>
                    </div>
                </div>

                <div class="feature-card success">
                    <div class="feature-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3>Intelligent Generation</h3>
                    <p>Smart password and passphrase generation with customizable complexity, memorable options, and security compliance.</p>
                    <div class="feature-tags">
                        <span class="tag">Custom Rules</span>
                        <span class="tag">Passphrase Mode</span>
                    </div>
                </div>

                <div class="feature-card warning">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Multi-Factor Security</h3>
                    <p>Enhanced authentication with OTP verification, backup codes, and seamless mobile integration for maximum protection.</p>
                    <div class="feature-tags">
                        <span class="tag">OTP Codes</span>
                        <span class="tag">Backup Recovery</span>
                    </div>
                </div>

                <div class="feature-card info">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Security Analytics</h3>
                    <p>Comprehensive security dashboards with detailed metrics, trend analysis, and personalized improvement recommendations.</p>
                    <div class="feature-tags">
                        <span class="tag">Real-time Metrics</span>
                        <span class="tag">Trend Analysis</span>
                    </div>
                </div>

                <div class="feature-card danger">
                    <div class="feature-icon">
                        <i class="fas fa-shield-virus"></i>
                    </div>
                    <h3>Threat Intelligence</h3>
                    <p>Advanced threat detection with real-time monitoring, breach analysis, and proactive security recommendations.</p>
                    <div class="feature-tags">
                        <span class="tag">Breach Detection</span>
                        <span class="tag">Risk Assessment</span>
                    </div>
                </div>

                <div class="feature-card dark">
                    <div class="feature-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>Enterprise Controls</h3>
                    <p>Complete administrative suite with user management, audit logging, compliance reporting, and enterprise-grade security.</p>
                    <div class="feature-tags">
                        <span class="tag">Admin Panel</span>
                        <span class="tag">Compliance</span>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <!-- Professional Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3><i class="fas fa-shield-alt"></i> <?php echo APP_NAME; ?></h3>
                    <p>Enterprise Password Security Platform</p>
                    <p class="version-info">Version <?php echo APP_VERSION; ?> • Professional Edition</p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Security</h4>
                        <ul>
                            <li><a href="#"><i class="fas fa-lock"></i> Security Features</a></li>
                            <li><a href="#"><i class="fas fa-shield-virus"></i> Threat Analysis</a></li>
                            <li><a href="#"><i class="fas fa-chart-bar"></i> Security Reports</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Enterprise</h4>
                        <ul>
                            <li><a href="#"><i class="fas fa-building"></i> Business Solutions</a></li>
                            <li><a href="#"><i class="fas fa-users-cog"></i> Admin Tools</a></li>
                            <li><a href="#"><i class="fas fa-headset"></i> Support</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#"><i class="fas fa-book"></i> Documentation</a></li>
                            <li><a href="#"><i class="fas fa-code"></i> API Reference</a></li>
                            <li><a href="#"><i class="fas fa-graduation-cap"></i> Best Practices</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-legal">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                    <p class="footer-disclaimer">
                        <i class="fas fa-info-circle"></i>
                        Professional cybersecurity platform for demonstration and educational purposes.
                    </p>
                </div>
                
                <div class="footer-security">
                    <div class="security-badges">
                        <span class="security-badge">
                            <i class="fas fa-shield-alt"></i> HTTPS Secured
                        </span>
                        <span class="security-badge">
                            <i class="fas fa-lock"></i> Encrypted
                        </span>
                        <span class="security-badge">
                            <i class="fas fa-eye-slash"></i> Privacy First
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Authentication Modal -->
    <div id="authModal" class="modal modal-hidden">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeAuthModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?> modal-message">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form active" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-header">
                        <h3><i class="fas fa-shield-alt"></i> Secure Access</h3>
                        <p>Access your password security dashboard</p>
                    </div>

                    <div class="form-group">
                        <label for="login_username">
                            <i class="fas fa-user"></i> Username or Email
                        </label>
                        <input type="text" 
                               id="login_username" 
                               name="username" 
                               placeholder="Enter your username or email"
                               autocomplete="username"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="login_password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" 
                               id="login_password" 
                               name="password" 
                               placeholder="Enter your password"
                               autocomplete="current-password"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-sign-in-alt"></i> Sign In Securely
                        </button>
                    </div>

                    <div class="form-switch">
                        <p>New to our platform? <button type="button" onclick="showTab('register')">Create an account</button></p>
                    </div>
                </form>

                <!-- Register Form -->
                <form id="registerForm" class="auth-form form-hidden" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-header">
                        <h3><i class="fas fa-user-shield"></i> Join Our Platform</h3>
                        <p>Create your secure account in seconds</p>
                    </div>

                    <div class="form-group">
                        <label for="reg_username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" 
                               id="reg_username" 
                               name="username" 
                               placeholder="Choose a unique username"
                               minlength="3"
                               maxlength="50"
                               autocomplete="username"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" 
                               id="reg_email" 
                               name="email" 
                               placeholder="Enter your email address"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" 
                               id="reg_password" 
                               name="password" 
                               placeholder="Create a strong password"
                               data-strength="true"
                               autocomplete="new-password"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input type="password" 
                               id="reg_confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm your password"
                               autocomplete="new-password"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-user-plus"></i> Create Secure Account
                        </button>
                    </div>

                    <div class="form-switch">
                        <p>Already have an account? <button type="button" onclick="showTab('login')">Sign in here</button></p>
                    </div>
                </form>

                <div id="message" class="message message-hidden modal-message-bottom"></div>
        </div>
    </div>

    <script src="assets/js/strength.js"></script>
    <script src="assets/js/landing.js"></script>
</body>
</html>