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
    <title><?php echo APP_NAME; ?> - Secure Your Digital Life</title>
    <meta name="description" content="Professional password strength checker and security tool. Evaluate password strength, get suggestions, and learn about cybersecurity best practices.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo APP_NAME; ?></h1>
            <p>Professional Password Security Analysis & Enhancement Tool</p>
        </div>

        <div class="auth-form mb-4">
            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Login/Register Tabs -->
            <div class="form-tabs">
                <button type="button" class="tab-btn active" onclick="showTab('login')"><i class="fas fa-sign-in-alt"></i> Login</button>
                <button type="button" class="tab-btn" onclick="showTab('register')"><i class="fas fa-user-plus"></i> Register</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="tab-content active" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to access your password security dashboard</p>
                </div>

                <div class="form-group">
                    <label for="login_username">Username or Email:</label>
                    <input type="text" 
                           id="login_username" 
                           name="username" 
                           autocomplete="username"
                           required>
                </div>

                <div class="form-group">
                    <label for="login_password">Password:</label>
                    <input type="password" 
                           id="login_password" 
                           name="password" 
                           autocomplete="current-password"
                           required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Sign In</button>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="tab-content" method="POST" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Join us to start securing your passwords</p>
                </div>

                <div class="form-group">
                    <label for="reg_username">Username:</label>
                    <input type="text" 
                           id="reg_username" 
                           name="username" 
                           minlength="3"
                           maxlength="50"
                           autocomplete="username"
                           required>
                </div>

                <div class="form-group">
                    <label for="reg_email">Email:</label>
                    <input type="email" 
                           id="reg_email" 
                           name="email" 
                           autocomplete="email"
                           required>
                </div>

                <div class="form-group">
                    <label for="reg_password">Password:</label>
                    <input type="password" 
                           id="reg_password" 
                           name="password" 
                           data-strength="true"
                           autocomplete="new-password"
                           required>
                </div>

                <div class="form-group">
                    <label for="reg_confirm_password">Confirm Password:</label>
                    <input type="password" 
                           id="reg_confirm_password" 
                           name="confirm_password" 
                           autocomplete="new-password"
                           required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</button>
                </div>
            </form>

            <div id="message" class="message" style="display: none;"></div>
        </div>

        <!-- Demo Password Checker (Public Access) -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Try Our Password Checker</h2>
                <p>Test any password to see its strength rating (no data is stored)</p>
            </div>

            <div class="form-group">
                <label for="demo_password">Enter a password to test:</label>
                <input type="password" 
                       id="demo_password" 
                       placeholder="Type a password here..."
                       data-strength="true">
            </div>

            <div class="demo-examples">
                <h4><i class="fas fa-play-circle"></i> Try these examples:</h4>
                <div class="example-passwords">
                    <button type="button" class="btn btn-secondary example-btn" data-password="password123">password123</button>
                    <button type="button" class="btn btn-secondary example-btn" data-password="MySecure2024!">MySecure2024!</button>
                    <button type="button" class="btn btn-secondary example-btn" data-password="correct-horse-battery-staple">correct-horse-battery-staple</button>
                </div>
            </div>
        </div>

        <!-- Features Overview -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-shield-alt"></i> Security Features</h2>
                <p>Professional-grade password analysis and security tools</p>
            </div>

            <div class="features-grid">
                <div class="feature-item">
                    <h3><i class="fas fa-microscope"></i> Advanced Analysis</h3>
                    <p>Comprehensive password evaluation including entropy calculation, pattern detection, and dictionary attack simulation.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-lightbulb"></i> Smart Suggestions</h3>
                    <p>AI-powered password generation with customizable complexity levels and memorable passphrase options.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-mobile-alt"></i> Two-Factor Auth</h3>
                    <p>Optional 2FA protection with OTP verification for enhanced account security.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-tachometer-alt"></i> Security Dashboard</h3>
                    <p>Track your password security journey with detailed analytics and improvement recommendations.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-users-cog"></i> Admin Controls</h3>
                    <p>Comprehensive user management and security monitoring for administrators.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-building"></i> Enterprise Security</h3>
                    <p>Rate limiting, session management, and comprehensive audit logging for business use.</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
            <p><small>For demonstration purposes. In production, always use HTTPS and proper email verification.</small></p>
        </div>
    </div>

    <script src="assets/js/strength.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            window.showTab = function(tabName) {
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                    content.classList.remove('active');
                });
                
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                const targetForm = document.getElementById(tabName + 'Form');
                if (targetForm) {
                    targetForm.style.display = 'block';
                    targetForm.classList.add('active');
                }
                
                event.target.classList.add('active');
            };

            // Example password buttons
            document.querySelectorAll('.example-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const password = this.dataset.password;
                    const demoInput = document.getElementById('demo_password');
                    demoInput.value = password;
                    
                    // Trigger strength check
                    const event = new Event('input', { bubbles: true });
                    demoInput.dispatchEvent(event);
                });
            });

            // Form submissions
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmission(this, 'auth/login.php');
            });

            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmission(this, 'auth/register.php');
            });

            function handleFormSubmission(form, endpoint) {
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Please wait...';
                
                fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        
                        if (data.requires_2fa) {
                            // Redirect to 2FA page
                            setTimeout(() => {
                                window.location.href = 'auth/2fa.php';
                            }, 1500);
                        } else if (data.redirect) {
                            // Regular login redirect
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500);
                        }
                    } else {
                        showMessage(data.message, 'error');
                        
                        // Clear password fields on error
                        form.querySelectorAll('input[type="password"]').forEach(input => {
                            input.value = '';
                        });
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
            }

            function showMessage(text, type) {
                const messageDiv = document.getElementById('message');
                messageDiv.textContent = text;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';
                
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
                
                // Scroll to message
                messageDiv.scrollIntoView({ behavior: 'smooth' });
            }

            // Real-time password confirmation validation
            const confirmPasswordInput = document.getElementById('reg_confirm_password');
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    const password = document.getElementById('reg_password').value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword && password !== confirmPassword) {
                        this.style.borderColor = '#ef4444';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            }
        });
    </script>

    <style>
        .form-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 500;
            color: var(--secondary-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .feature-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--light-color);
        }

        .feature-item h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .feature-item h3 .fas {
            color: var(--primary-color);
            margin-right: 0.5rem;
            width: 1.2em;
            text-align: center;
        }

        .tab-btn .fas {
            margin-right: 0.5rem;
        }

        .demo-examples h4 .fas {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .demo-examples {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .example-passwords {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .example-btn {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        .footer {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .example-passwords {
                flex-direction: column;
            }
            
            .example-btn {
                width: 100%;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>