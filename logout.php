<?php
/**
 * Logout Handler
 * File: logout.php
 * 
 * Logs out the current user and destroys session
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store user info before destroying session (for logging)
$user_id = $_SESSION['user_id'] ?? null;

// Log the logout activity if user was logged in
if ($user_id) {
    try {
        require_once 'includes/config/database.php';
        require_once 'includes/config/functions.php';
        
        // Only log if database connection exists
        if (isset($db) && $db) {
            logActivity($user_id, 'LOGOUT', 'user', $user_id);
        }
    } catch (Exception $e) {
        // Silently fail logging - don't break logout
        error_log("Logout logging failed: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a new session for flash message
session_start();
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to index page
header('Location: index.php');
exit;
?>