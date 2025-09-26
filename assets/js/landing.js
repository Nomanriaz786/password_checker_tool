/**
 * Landing Page JavaScript
 * Handles authentication forms and demo interactions
 */

// Make functions globally available
window.openAuthModal = function(type = 'login') {
    const modal = document.getElementById('authModal');
    if (!modal) {
        console.error('Modal not found');
        return;
    }
    
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
    modal.style.display = 'flex';
    
    // Show the appropriate form
    showTab(type);
};

window.closeAuthModal = function() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

// Tab switching function
window.showTab = function(tabName) {
    // Hide all forms
    document.querySelectorAll('.auth-form').forEach(form => {
        form.style.display = 'none';
        form.classList.remove('active');
    });
    
    // Show the target form
    const targetForm = document.getElementById(tabName + 'Form');
    if (targetForm) {
        targetForm.style.display = 'block';
        targetForm.classList.add('active');
    }
};

// Close modal when clicking outside of it
document.addEventListener('click', function(event) {
    const modal = document.getElementById('authModal');
    if (event.target === modal) {
        closeAuthModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAuthModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {

    // Example password buttons
    document.querySelectorAll('.example-card').forEach(card => {
        card.addEventListener('click', function() {
            const password = this.dataset.password;
            const demoInput = document.getElementById('demo_password');
            demoInput.value = password;
            
            // Add visual feedback
            this.classList.add('clicked');
            setTimeout(() => this.classList.remove('clicked'), 200);
            
            // Trigger strength check
            const event = new Event('input', { bubbles: true });
            demoInput.dispatchEvent(event);
            
            // Scroll to input
            demoInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Form submissions
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(this, 'auth/login.php');
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(this, 'auth/register.php');
        });
    }

    function handleFormSubmission(form, endpoint) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalHTML = submitBtn.innerHTML;
        
        // Loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
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
            submitBtn.innerHTML = originalHTML;
        });
    }

    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${text}`;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
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
                this.style.borderColor = 'var(--danger-color)';
                this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading animation on page navigation
    document.querySelectorAll('a:not([href^="#"]):not([target="_blank"])').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.hostname === window.location.hostname) {
                document.body.classList.add('page-loading');
            }
        });
    });

    // Hero animation on scroll
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            if (scrolled < window.innerHeight) {
                heroSection.style.transform = `translateY(${rate}px)`;
            }
        });
    }
});