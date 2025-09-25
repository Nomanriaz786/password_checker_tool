<?php
/**
 * Security Headers Configuration
 * Implements comprehensive security headers to protect against common web vulnerabilities
 */

/**
 * Generate a cryptographic nonce for CSP
 * @return string Base64-encoded nonce
 */
function generateCSPNonce() {
    if (!isset($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(32));
    }
    return $_SESSION['csp_nonce'];
}

/**
 * Apply security headers to all responses
 * This function should be called at the beginning of all PHP files
 */
function applySecurityHeaders() {
    // Only apply headers if not already sent
    if (!headers_sent()) {
        
        // Generate nonce for this request
        $nonce = generateCSPNonce();
        
        // Content Security Policy (CSP) - Prevents XSS and code injection
        // More restrictive policy with specific allowed sources
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " . // Allow all inline scripts
               "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " . // Allow FontAwesome CSS and Google Fonts
               "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " . // Allow FontAwesome fonts and Google Fonts
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "media-src 'self'; " .
               "object-src 'none'; " .
               "child-src 'none'; " .
               "frame-src 'none'; " .
               "worker-src 'none'; " .
               "manifest-src 'self'; " .
               "form-action 'self'; " . // Restrict form submissions
               "frame-ancestors 'none'; " . // Additional clickjacking protection
               "base-uri 'self'; " . // Restrict base element
               "upgrade-insecure-requests"; // Force HTTPS when available
        
        header("Content-Security-Policy: $csp");
        
        // X-Frame-Options - Anti-clickjacking protection
        header("X-Frame-Options: DENY");
        
        // X-Content-Type-Options - Prevents MIME type sniffing
        header("X-Content-Type-Options: nosniff");
        
        // X-XSS-Protection - XSS filtering (legacy but still useful)
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer-Policy - Controls referrer information
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Permissions-Policy - Feature policy for modern browsers
        header("Permissions-Policy: geolocation=(), microphone=(), camera=(), fullscreen=(self), payment=(), usb=(), serial=(), bluetooth=(), magnetometer=(), gyroscope=(), accelerometer=()");
        
        // Cross-Origin-Embedder-Policy - Helps against Spectre attacks
        header("Cross-Origin-Embedder-Policy: require-corp");
        
        // Cross-Origin-Opener-Policy - Isolates browsing context
        header("Cross-Origin-Opener-Policy: same-origin");
        
        // Cross-Origin-Resource-Policy - Controls cross-origin resource sharing
        header("Cross-Origin-Resource-Policy: same-site");
        
        // Strict-Transport-Security - HTTPS enforcement (only if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
        
        // Remove server information leakage
        header_remove("X-Powered-By");
        header_remove("Server");
        
        // Cache-Control for sensitive pages
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_sensitive_page = strpos($request_uri, 'auth/') !== false || 
                           strpos($request_uri, 'admin/') !== false ||
                           strpos($request_uri, 'dashboard') !== false;
        
        if ($is_sensitive_page) {
            header("Cache-Control: no-cache, no-store, must-revalidate, private");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
    }
}

/**
 * Set secure cookie parameters
 * Call this before starting session or setting cookies
 */
function setSecureCookieParams() {
    // Set secure cookie parameters
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $secure ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    // Set additional secure session settings
    ini_set('session.use_strict_mode', 1);
    ini_set('session.hash_function', 'sha256');
    ini_set('session.hash_bits_per_character', 6);
}

/**
 * Apply headers and start secure session
 * Convenience function for common initialization
 */
function initializeSecureEnvironment() {
    // Apply security headers first
    applySecurityHeaders();
    
    // Set secure cookie parameters
    setSecureCookieParams();
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Auto-apply security headers when this file is included
applySecurityHeaders();
?>