<?php
/**
 * Rate Limiting Middleware
 * File: includes/middleware/rate-limit.php
 * 
 * Prevents abuse by limiting request frequency.
 * Useful for login pages, API endpoints, and form submissions.
 * 
 * Usage:
 *   require_once 'includes/middleware/rate-limit.php';
 *   rate_limit_check('login_attempt', 5, 60); // 5 attempts per 60 seconds
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check rate limit for a specific action
 * @param string $action Action identifier (e.g., 'login_attempt', 'form_submit')
 * @param int $maxAttempts Maximum allowed attempts
 * @param int $timeWindow Time window in seconds
 * @param string $identifier Optional custom identifier (e.g., IP address)
 * @return bool True if within limit, false if exceeded
 */
function rate_limit_check($action, $maxAttempts = 10, $timeWindow = 60, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    $data = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data['count']++;
    $_SESSION[$key] = $data;
    
    return $data['count'] <= $maxAttempts;
}

/**
 * Get remaining attempts for rate-limited action
 * @param string $action Action identifier
 * @param int $maxAttempts Maximum allowed attempts
 * @param int $timeWindow Time window in seconds
 * @param string|null $identifier Custom identifier
 * @return int Remaining attempts
 */
function rate_limit_remaining($action, $maxAttempts = 10, $timeWindow = 60, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    $data = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
    
    if (time() - $data['first_attempt'] > $timeWindow) {
        return $maxAttempts;
    }
    
    return max(0, $maxAttempts - $data['count']);
}

/**
 * Get time until rate limit resets
 * @param string $action Action identifier
 * @param int $timeWindow Time window in seconds
 * @param string|null $identifier Custom identifier
 * @return int Seconds until reset
 */
function rate_limit_reset_time($action, $timeWindow = 60, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    $data = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
    
    $elapsed = time() - $data['first_attempt'];
    return max(0, $timeWindow - $elapsed);
}

/**
 * Clear rate limit for an action
 * @param string $action Action identifier
 * @param string|null $identifier Custom identifier
 */
function rate_limit_clear($action, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    unset($_SESSION[$key]);
}

/**
 * Require rate limit - exit with error if exceeded
 * @param string $action Action identifier
 * @param int $maxAttempts Maximum allowed attempts
 * @param int $timeWindow Time window in seconds
 * @param string $errorMessage Custom error message
 */
function require_rate_limit($action, $maxAttempts = 10, $timeWindow = 60, $errorMessage = null) {
    if (!rate_limit_check($action, $maxAttempts, $timeWindow)) {
        $resetTime = rate_limit_reset_time($action, $timeWindow);
        
        if ($errorMessage === null) {
            $errorMessage = "Too many attempts. Please try again in {$resetTime} seconds.";
        }
        
        http_response_code(429); // Too Many Requests
        header("Retry-After: {$resetTime}");
        
        if (isset($_SESSION['flash_message'])) {
            $_SESSION['flash_message'] = $errorMessage;
            $_SESSION['flash_type'] = 'danger';
        }
        
        die($errorMessage);
    }
}