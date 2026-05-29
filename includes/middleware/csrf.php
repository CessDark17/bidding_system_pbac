<?php
/**
 * CSRF Protection Middleware
 * File: includes/middleware/csrf.php
 * 
 * Provides CSRF token generation and validation for forms.
 * Include this file on pages with forms that need CSRF protection.
 * 
 * Usage:
 *   require_once 'includes/middleware/csrf.php';
 *   
 *   // In form: <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
 *   // In processing: csrf_validate();
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate and store CSRF token
 * @return string CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 * @param string|null $token Token to validate (null to get from POST)
 * @return bool True if valid
 */
function csrf_validate($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token - die with error if invalid
 * @param string $errorMessage Custom error message
 */
function require_csrf($errorMessage = 'Invalid security token. Please refresh the page and try again.') {
    if (!csrf_validate()) {
        if (isset($_SESSION['flash_message'])) {
            $_SESSION['flash_message'] = $errorMessage;
            $_SESSION['flash_type'] = 'danger';
        }
        
        http_response_code(403);
        die($errorMessage);
    }
    
    // Regenerate token after successful validation to prevent reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Generate CSRF meta tag for AJAX requests
 * @return string HTML meta tag
 */
function csrf_meta_tag() {
    return '<meta name="csrf-token" content="' . csrf_token() . '">';
}

/**
 * Get CSRF header value for AJAX requests
 * @return array Header array
 */
function csrf_headers() {
    return ['X-CSRF-Token: ' . csrf_token()];
}

/**
 * Clean up old CSRF tokens (call periodically)
 */
function csrf_cleanup() {
    // Tokens are stored in session, which expires naturally
    // No additional cleanup needed
}