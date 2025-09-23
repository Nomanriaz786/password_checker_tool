/**
 * Password Strength Meter - JavaScript Module
 * Real-time password strength evaluation with visual feedback
 */

class PasswordStrengthMeter {
    constructor(passwordInput, options = {}) {
        this.passwordInput = typeof passwordInput === 'string' 
            ? document.querySelector(passwordInput) 
            : passwordInput;
        
        if (!this.passwordInput) {
            throw new Error('Password input element not found');
        }
        
        this.options = {
            showSuggestions: true,
            realTimeChecking: true,
            debounceDelay: 300,
            minLength: 8,
            ...options
        };
        
        this.debounceTimer = null;
        this.currentStrength = null;
        
        this.init();
    }
    
    init() {
        this.createMeterHTML();
        this.attachEventListeners();
    }
    
    createMeterHTML() {
        const container = document.createElement('div');
        container.className = 'password-strength-container';
        container.innerHTML = `
            <div class="strength-meter">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
            <div class="strength-text">
                <span class="strength-label" id="strengthLabel">Enter password</span>
                <span class="entropy-info" id="entropyInfo"></span>
            </div>
            <div class="password-feedback" id="passwordFeedback" style="display: none;">
                <ul class="feedback-list" id="feedbackList"></ul>
            </div>
            <div class="crack-time-info" id="crackTimeInfo" style="display: none;">
                <small>Estimated crack time: <strong id="crackTime">-</strong></small>
            </div>
        `;
        
        // Insert after the password input
        this.passwordInput.parentNode.insertBefore(container, this.passwordInput.nextSibling);
        
        // Store references to elements
        this.strengthBar = document.getElementById('strengthBar');
        this.strengthLabel = document.getElementById('strengthLabel');
        this.entropyInfo = document.getElementById('entropyInfo');
        this.feedbackContainer = document.getElementById('passwordFeedback');
        this.feedbackList = document.getElementById('feedbackList');
        this.crackTimeInfo = document.getElementById('crackTimeInfo');
        this.crackTime = document.getElementById('crackTime');
        
        // Create suggestions container if enabled
        if (this.options.showSuggestions) {
            this.createSuggestionsContainer();
        }
    }
    
    createSuggestionsContainer() {
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'suggestions-container';
        suggestionsContainer.innerHTML = `
            <div class="card-header">
                <h3>Password Suggestions</h3>
                <p>Click any suggestion to use it as your password</p>
            </div>
            <div id="suggestionsList" class="suggestions-list"></div>
            <button type="button" id="generateMore" class="btn btn-secondary" style="display: none;">
                Generate More Suggestions
            </button>
        `;
        
        // Insert after the strength container
        const strengthContainer = this.passwordInput.nextSibling;
        strengthContainer.parentNode.insertBefore(suggestionsContainer, strengthContainer.nextSibling);
        
        this.suggestionsList = document.getElementById('suggestionsList');
        this.generateMoreBtn = document.getElementById('generateMore');
        
        // Attach suggestion events
        this.generateMoreBtn.addEventListener('click', () => this.generateSuggestions());
    }
    
    attachEventListeners() {
        if (this.options.realTimeChecking) {
            this.passwordInput.addEventListener('input', (e) => {
                this.handlePasswordInput(e.target.value);
            });
            
            this.passwordInput.addEventListener('keyup', (e) => {
                this.handlePasswordInput(e.target.value);
            });
        }
        
        // Show/hide password toggle if present
        const toggleBtn = document.querySelector('[data-toggle="password"]');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', this.togglePasswordVisibility.bind(this));
        }
    }
    
    handlePasswordInput(password) {
        clearTimeout(this.debounceTimer);
        
        if (password.length === 0) {
            this.resetMeter();
            return;
        }
        
        this.debounceTimer = setTimeout(() => {
            this.checkPasswordStrength(password);
        }, this.options.debounceDelay);
    }
    
    async checkPasswordStrength(password) {
        try {
            const response = await fetch('password/check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `password=${encodeURIComponent(password)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateMeter(data.result);
                this.currentStrength = data.result;
                
                // Generate suggestions for weak passwords
                if (this.options.showSuggestions && data.result.score < 60) {
                    this.generateSuggestions(password);
                }
            } else {
                this.showError(data.message);
            }
            
        } catch (error) {
            console.error('Error checking password strength:', error);
            this.showError('Unable to check password strength');
        }
    }
    
    updateMeter(result) {
        const { score, strength_level, entropy, feedback, estimated_crack_time } = result;
        
        // Update strength bar
        this.strengthBar.style.width = `${score}%`;
        this.strengthBar.className = `strength-bar ${strength_level}`;
        
        // Update strength label
        this.strengthLabel.textContent = this.formatStrengthLabel(strength_level, score);
        this.strengthLabel.className = `strength-label ${strength_level}`;
        
        // Update entropy info
        this.entropyInfo.textContent = `${entropy} bits of entropy`;
        
        // Update crack time
        this.crackTime.textContent = estimated_crack_time;
        this.crackTimeInfo.style.display = 'block';
        
        // Update feedback
        this.updateFeedback(feedback);
        
        // Trigger custom event
        this.passwordInput.dispatchEvent(new CustomEvent('strengthUpdated', {
            detail: result
        }));
    }
    
    formatStrengthLabel(strengthLevel, score) {
        const labels = {
            'very-weak': 'Very Weak',
            'weak': 'Weak', 
            'medium': 'Medium',
            'strong': 'Strong',
            'very-strong': 'Very Strong'
        };
        
        return `${labels[strengthLevel]} (${score}%)`;
    }
    
    updateFeedback(feedback) {
        if (!feedback || feedback.length === 0) {
            this.feedbackContainer.style.display = 'none';
            return;
        }
        
        this.feedbackList.innerHTML = '';
        feedback.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item;
            this.feedbackList.appendChild(li);
        });
        
        this.feedbackContainer.style.display = 'block';
    }
    
    async generateSuggestions(currentPassword = '') {
        if (!this.suggestionsList) return;
        
        try {
            const response = await fetch('password/suggest.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `current_password=${encodeURIComponent(currentPassword)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displaySuggestions(data.suggestions);
            }
            
        } catch (error) {
            console.error('Error generating suggestions:', error);
        }
    }
    
    displaySuggestions(suggestions) {
        this.suggestionsList.innerHTML = '';
        
        if (!suggestions || suggestions.length === 0) {
            this.suggestionsList.innerHTML = '<p>No suggestions available</p>';
            return;
        }
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `
                <div class="suggestion-password">${this.escapeHtml(suggestion.password)}</div>
                <div class="suggestion-description">${this.escapeHtml(suggestion.description)}</div>
            `;
            
            // Add click handler to use suggestion
            item.addEventListener('click', () => {
                this.useSuggestion(suggestion.password);
            });
            
            this.suggestionsList.appendChild(item);
        });
        
        this.generateMoreBtn.style.display = 'block';
    }
    
    useSuggestion(password) {
        this.passwordInput.value = password;
        this.passwordInput.focus();
        
        // Trigger input event to update strength meter
        const event = new Event('input', { bubbles: true });
        this.passwordInput.dispatchEvent(event);
        
        // Show success message
        this.showMessage('Password suggestion applied!', 'success');
        
        // Hide suggestions temporarily
        if (this.suggestionsList) {
            this.suggestionsList.style.opacity = '0.5';
            setTimeout(() => {
                if (this.suggestionsList) {
                    this.suggestionsList.style.opacity = '1';
                }
            }, 1000);
        }
    }
    
    togglePasswordVisibility() {
        const type = this.passwordInput.type === 'password' ? 'text' : 'password';
        this.passwordInput.type = type;
        
        const toggleBtn = document.querySelector('[data-toggle="password"]');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (type === 'password') {
                    icon.className = 'fas fa-eye';
                    toggleBtn.setAttribute('aria-label', 'Show password');
                } else {
                    icon.className = 'fas fa-eye-slash';
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                }
            }
        }
    }
    
    resetMeter() {
        this.strengthBar.style.width = '0%';
        this.strengthBar.className = 'strength-bar';
        this.strengthLabel.textContent = 'Enter password';
        this.strengthLabel.className = 'strength-label';
        this.entropyInfo.textContent = '';
        this.feedbackContainer.style.display = 'none';
        this.crackTimeInfo.style.display = 'none';
        
        if (this.suggestionsList) {
            this.suggestionsList.innerHTML = '';
        }
    }
    
    showError(message) {
        this.showMessage(message, 'error');
    }
    
    showMessage(text, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.password-message');
        existingMessages.forEach(msg => msg.remove());
        
        const message = document.createElement('div');
        message.className = `message ${type} password-message`;
        message.textContent = text;
        
        // Insert after password input
        this.passwordInput.parentNode.insertBefore(message, this.passwordInput.nextSibling);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 3000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Public methods
    getStrength() {
        return this.currentStrength;
    }
    
    updatePassword(password) {
        this.passwordInput.value = password;
        this.handlePasswordInput(password);
    }
    
    destroy() {
        clearTimeout(this.debounceTimer);
        
        // Remove event listeners
        this.passwordInput.removeEventListener('input', this.handlePasswordInput);
        this.passwordInput.removeEventListener('keyup', this.handlePasswordInput);
        
        // Remove created elements
        const container = document.querySelector('.password-strength-container');
        if (container) container.remove();
        
        const suggestions = document.querySelector('.suggestions-container');
        if (suggestions) suggestions.remove();
    }
}

// Utility functions for password generation
const PasswordGenerator = {
    generateSecure(length = 16) {
        const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        
        // Ensure at least one character from each type
        password += this.getRandomChar('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        password += this.getRandomChar('abcdefghijklmnopqrstuvwxyz');
        password += this.getRandomChar('0123456789');
        password += this.getRandomChar('!@#$%^&*');
        
        // Fill the rest randomly
        for (let i = password.length; i < length; i++) {
            password += this.getRandomChar(charset);
        }
        
        // Shuffle the password
        return password.split('').sort(() => Math.random() - 0.5).join('');
    },
    
    generatePassphrase(wordCount = 4) {
        const words = [
            'correct', 'horse', 'battery', 'staple', 'mountain', 'river', 'sunset', 'coffee',
            'guitar', 'travel', 'ocean', 'forest', 'rainbow', 'thunder', 'whisper', 'journey',
            'mystery', 'treasure', 'magic', 'wonder', 'castle', 'dragon', 'phoenix', 'crystal'
        ];
        
        const selectedWords = [];
        for (let i = 0; i < wordCount; i++) {
            const word = words[Math.floor(Math.random() * words.length)];
            selectedWords.push(word.charAt(0).toUpperCase() + word.slice(1));
        }
        
        const separator = ['-', '_', '.'][Math.floor(Math.random() * 3)];
        const number = Math.floor(Math.random() * 100);
        
        return selectedWords.join(separator) + separator + number;
    },
    
    getRandomChar(charset) {
        return charset.charAt(Math.floor(Math.random() * charset.length));
    }
};

// Form validation helpers
const FormHelpers = {
    validateForm(formElement) {
        const inputs = formElement.querySelectorAll('input[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    validateField(input) {
        const value = input.value.trim();
        let isValid = true;
        let message = '';
        
        // Remove existing validation messages
        const existingMessage = input.parentNode.querySelector('.field-error');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Required validation
        if (input.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Email validation
        if (input.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        // Password confirmation
        if (input.name === 'confirm_password') {
            const passwordInput = input.form.querySelector('input[name="password"]');
            if (passwordInput && value !== passwordInput.value) {
                isValid = false;
                message = 'Passwords do not match';
            }
        }
        
        // Show validation message
        if (!isValid) {
            this.showFieldError(input, message);
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
        
        return isValid;
    },
    
    showFieldError(input, message) {
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        error.style.color = '#ef4444';
        error.style.fontSize = '0.875rem';
        error.style.marginTop = '0.25rem';
        
        input.parentNode.appendChild(error);
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PasswordStrengthMeter, PasswordGenerator, FormHelpers };
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize password strength meters
    const passwordInputs = document.querySelectorAll('input[type="password"][data-strength="true"]');
    passwordInputs.forEach(input => {
        new PasswordStrengthMeter(input);
    });
    
    // Add show/hide password toggles
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(input => {
        if (!input.dataset.noToggle) {
            addPasswordToggle(input);
        }
    });
});

function addPasswordToggle(input) {
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';
    toggle.style.position = 'absolute';
    toggle.style.right = '10px';
    toggle.style.top = '50%';
    toggle.style.transform = 'translateY(-50%)';
    toggle.style.background = 'none';
    toggle.style.border = 'none';
    toggle.style.cursor = 'pointer';
    toggle.style.fontSize = '1.2rem';
    toggle.style.color = '#64748b';
    toggle.style.padding = '0.25rem';
    toggle.style.borderRadius = '4px';
    toggle.style.transition = 'color 0.2s ease';
    toggle.dataset.toggle = 'password';
    toggle.setAttribute('aria-label', 'Show password');
    
    // Add hover effect
    toggle.addEventListener('mouseenter', function() {
        toggle.style.color = '#2563eb';
    });
    
    toggle.addEventListener('mouseleave', function() {
        toggle.style.color = '#64748b';
    });
    
    toggle.addEventListener('click', function() {
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        const icon = toggle.querySelector('i');
        
        if (type === 'password') {
            icon.className = 'fas fa-eye';
            toggle.setAttribute('aria-label', 'Show password');
        } else {
            icon.className = 'fas fa-eye-slash';
            toggle.setAttribute('aria-label', 'Hide password');
        }
    });
    
    wrapper.appendChild(toggle);
}