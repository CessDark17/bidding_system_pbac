<?php
/**
 * Admin Middleware
 * File: includes/middleware/admin.php
 * 
 * This middleware ensures that a user is logged in AND has admin privileges.
 * Include this file at the top of any admin-only pages.
 * 
 * Usage:
 *   require_once 'includes/middleware/admin.php';
 * 
 * Note: This file automatically includes auth.php as admin access requires authentication first.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once __DIR__ . '/auth.php';

/**
 * Check if current user has admin role
 * @return bool True if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user has specific role
 * @param string|array $roles Allowed role(s)
 * @return bool True if user has allowed role
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'user';
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Require admin access - redirect to login or dashboard if not admin
 * @param string $redirectUrl Optional custom redirect URL
 */
function requireAdmin($redirectUrl = null) {
    // First, ensure user is authenticated
    if (!isAuthenticated()) {
        if ($redirectUrl === null) {
            $redirectUrl = $_SERVER['REQUEST_URI'];
        }
        $_SESSION['redirect_after_login'] = $redirectUrl;
        
        $_SESSION['flash_message'] = 'Please login to access the admin area.';
        $_SESSION['flash_type'] = 'warning';
        
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    // Then, check if user has admin role
    if (!isAdmin()) {
        $_SESSION['flash_message'] = 'You do not have permission to access the admin area.';
        $_SESSION['flash_type'] = 'danger';
        
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    
    // Update last activity for admin session
    $_SESSION['last_activity'] = time();
}

/**
 * Get admin-specific user data including permissions
 * @return array Admin user data
 */
function getAdminUser() {
    if (!isAdmin()) {
        return null;
    }
    
    global $db;
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT id, username, email, full_name, role, status, last_login, created_at 
            FROM users WHERE id = ?";
    $user = fetchOne($sql, [$userId]);
    
    if ($user) {
        // Add admin-specific permissions
        $user['permissions'] = [
            'view_users' => true,
            'create_users' => true,
            'edit_users' => true,
            'delete_users' => true,
            'view_reports' => true,
            'export_data' => true,
            'manage_bidding' => true,
            'manage_procurement' => true,
            'upload_documents' => true,
            'view_sealed_bidding' => true,
            'system_settings' => true
        ];
    }
    
    return $user;
}

/**
 * Log admin action for audit trail
 * @param string $action Action performed
 * @param string $details Additional details
 * @param int|null $entityId Related entity ID
 */
function logAdminAction($action, $details = null, $entityId = null) {
    if (!isAdmin()) {
        return;
    }
    
    logActivity($_SESSION['user_id'], $action, 'admin', $entityId, $details);
}

/**
 * Check if admin has specific permission (for granular access control)
 * @param string $permission Permission to check
 * @return bool True if has permission
 */
function adminHasPermission($permission) {
    // For now, all admins have all permissions
    // This can be extended later for role-based permissions
    if (!isAdmin()) {
        return false;
    }
    
    $adminPermissions = [
        'view_users', 'create_users', 'edit_users', 'delete_users',
        'view_reports', 'export_data', 'manage_bidding', 'manage_procurement',
        'upload_documents', 'view_sealed_bidding', 'system_settings'
    ];
    
    return in_array($permission, $adminPermissions);
}

/**
 * Get admin dashboard statistics (cached)
 * @param bool $forceRefresh Force refresh cache
 * @return array Dashboard statistics
 */
function getAdminStats($forceRefresh = false) {
    static $stats = null;
    
    if ($stats !== null && !$forceRefresh) {
        return $stats;
    }
    
    global $db;
    
    $stats = [];
    
    // User statistics
    $result = fetchOne("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result['total'] ?? 0;
    
    $result = fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stats['active_users'] = $result['total'] ?? 0;
    
    $result = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
    $stats['admin_users'] = $result['total'] ?? 0;
    
    // Bidding statistics
    $result = fetchOne("SELECT COUNT(*) as total FROM public_bidding");
    $stats['public_bidding'] = $result['total'] ?? 0;
    
    $result = fetchOne("SELECT COUNT(*) as total FROM sealed_bidding");
    $stats['sealed_bidding'] = $result['total'] ?? 0;
    
    // Procurement statistics
    $result = fetchOne("SELECT COUNT(*) as total FROM procurement_monitoring");
    $stats['procurement'] = $result['total'] ?? 0;
    
    // Document statistics
    $result = fetchOne("SELECT COUNT(*) as total FROM uploaded_documents");
    $stats['total_documents'] = $result['total'] ?? 0;
    
    $result = fetchOne("SELECT COUNT(*) as total FROM uploaded_documents WHERE upload_status = 'pending'");
    $stats['pending_documents'] = $result['total'] ?? 0;
    
    // Recent activity (last 7 days)
    $result = fetchOne("SELECT COUNT(*) as total FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_activity'] = $result['total'] ?? 0;
    
    return $stats;
}

/**
 * Generate admin activity report
 * @param int $days Number of days to look back
 * @return array Activity report
 */
function getAdminActivityReport($days = 30) {
    global $db;
    
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_activities,
                COUNT(DISTINCT user_id) as unique_users,
                action
            FROM activity_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at), action
            ORDER BY date DESC";
    
    return fetchAll($sql, [$days]);
}

// ======================================================
// Execute admin access check for admin-only pages
// ======================================================

// Uncomment the line below to enforce admin access on every page that includes this file
// requireAdmin();

// For pages that should check admin access conditionally, use requireAdmin() manually.
?>