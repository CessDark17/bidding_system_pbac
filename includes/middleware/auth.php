<?php
/**
 * Authentication Middleware
 * File: includes/middleware/auth.php
 * 
 * This middleware ensures that a user is logged in before accessing protected pages.
 * Include this file at the top of any page that requires authentication.
 * 
 * Usage:
 *   require_once 'includes/middleware/auth.php';
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current authenticated user
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'username' => $_SESSION['user_username'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

// Require authentication - redirect to login if not authenticated
function requireAuth($redirectUrl = null) {
    if (!isAuthenticated()) {
        // Set redirect URL to return after login
        if ($redirectUrl === null) {
            $redirectUrl = $_SERVER['REQUEST_URI'];
        }
        
        $_SESSION['redirect_after_login'] = $redirectUrl;
        
        // Redirect to login page
        $loginUrl = BASE_URL . '/login.php';
        header("Location: $loginUrl");
        exit;
    }
    
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}

// Check session timeout (optional, 30 minutes default)
function checkSessionTimeout($timeoutMinutes = 30) {
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        if ($inactiveTime > ($timeoutMinutes * 60)) {
            // Session expired
            session_unset();
            session_destroy();
            
            $_SESSION['flash_message'] = 'Your session has expired. Please login again.';
            $_SESSION['flash_type'] = 'warning';
            
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

// Optional: Regenerate session ID periodically for security
function regenerateSessionIfNeeded() {
    if (!isset($_SESSION['regenerated_at'])) {
        $_SESSION['regenerated_at'] = time();
    } elseif (time() - $_SESSION['regenerated_at'] > 300) { // Every 5 minutes
        session_regenerate_id(true);
        $_SESSION['regenerated_at'] = time();
    }
}

// ======================================================
// Execute authentication check for protected pages
// ======================================================

// Uncomment the line below to enforce authentication on every page that includes this file
// requireAuth();

// For pages that should be accessible to both guests and authenticated users,
// you can call requireAuth() conditionally or use isAuthenticated() check.
?>