<?php
/**
 * Helper Functions
 * FIBECO Bidding System
 * 
 * Common utility functions used throughout the application.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize input string
 * 
 * @param string $input Raw input string
 * @param bool $htmlEncode Whether to encode HTML entities
 * @return string Sanitized string
 */
function sanitize($input, $htmlEncode = true) {
    // Handle null or empty input
    if ($input === null) {
        return '';
    }
    
    $input = trim($input);
    $input = strip_tags($input);
    
    if ($htmlEncode) {
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

/**
 * Sanitize array input recursively
 * 
 * @param array $data Array to sanitize
 * @return array Sanitized array
 */
function sanitizeArray($data) {
    if (!is_array($data)) {
        return sanitize($data);
    }
    
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = is_array($value) ? sanitizeArray($value) : sanitize($value);
    }
    
    return $sanitized;
}

/**
 * Redirect to URL
 * 
 * @param string $url Target URL
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Show alert message and redirect
 * 
 * @param string $message Alert message
 * @param string $type Message type (success, error, warning, info)
 * @param string $redirectUrl URL to redirect to
 */
function alertRedirect($message, $type = 'info', $redirectUrl = '') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    if ($redirectUrl) {
        redirect($redirectUrl);
    }
}

/**
 * Display flash message
 * 
 * @return string HTML of flash message or empty string
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        // Map message type to Bootstrap alert class
        $alertClass = 'info';
        switch ($type) {
            case 'success':
                $alertClass = 'success';
                break;
            case 'error':
            case 'danger':
                $alertClass = 'danger';
                break;
            case 'warning':
                $alertClass = 'warning';
                break;
            default:
                $alertClass = 'info';
        }
        
        return '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    return '';
}

/**
 * Format currency
 * 
 * @param float $amount Amount to format
 * @param bool $withSymbol Include PHP currency symbol
 * @return string Formatted currency
 */
function formatCurrency($amount, $withSymbol = true) {
    if ($amount === null || $amount === '') {
        $amount = 0;
    }
    $formatted = number_format((float)$amount, 2, '.', ',');
    return $withSymbol ? '₱ ' . $formatted : $formatted;
}

/**
 * Format date for display
 * 
 * @param string|DateTime|int $date Date to format
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    $timestamp = is_string($date) ? strtotime($date) : (is_numeric($date) ? $date : null);
    if ($timestamp === false || $timestamp === null) {
        return 'N/A';
    }
    
    return date($format, $timestamp);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 * 
 * @return bool True if admin
 */
function isAdmin() {
    // Check if ROLE_ADMIN constant is defined, otherwise use string literal
    $adminRole = defined('ROLE_ADMIN') ? ROLE_ADMIN : 'admin';
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $adminRole;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null User role or null
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user name
 * 
 * @return string|null User name or null
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get pagination links
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $url Base URL
 * @param array $params Additional URL parameters
 * @return string HTML pagination links
 */
function getPaginationLinks($currentPage, $totalPages, $url, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    // Build query string from params
    $queryString = '';
    if (!empty($params)) {
        $queryString = '&' . http_build_query($params);
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevUrl = $currentPage > 1 ? $url . '?page=' . ($currentPage - 1) . $queryString : '#';
    $html .= '<li class="page-item ' . $prevDisabled . '">
                <a class="page-link" href="' . $prevUrl . '">&laquo; Previous</a>
              </li>';
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=1' . $queryString . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '">
                    <a class="page-link" href="' . $url . '?page=' . $i . $queryString . '">' . $i . '</a>
                  </li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $totalPages . $queryString . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextUrl = $currentPage < $totalPages ? $url . '?page=' . ($currentPage + 1) . $queryString : '#';
    $html .= '<li class="page-item ' . $nextDisabled . '">
                <a class="page-link" href="' . $nextUrl . '">Next &raquo;</a>
              </li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get status badge HTML
 * 
 * @param string $status Status value
 * @return string HTML badge
 */
function getStatusBadge($status) {
    $colors = [
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'completed' => 'info',
        'failed' => 'danger',
        'cancelled' => 'dark',
        'ongoing' => 'primary',
        'awarded' => 'success',
        'processing' => 'info',
        'extracted' => 'warning',
        'reviewed' => 'primary',
        'imported' => 'success'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

/**
 * Log user activity
 * 
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string $entityType Entity type (e.g., 'public_bidding')
 * @param int|null $entityId Entity ID
 * @param string|null $details Additional details
 */
function logActivity($userId, $action, $entityType = null, $entityId = null, $details = null) {
    global $db;
    
    if (!$db) {
        return;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip, $userAgent]);
    } catch (Exception $e) {
        // Silently fail logging - don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get time ago string (e.g., "2 hours ago")
 * 
 * @param string $datetime DateTime string
 * @return string Time ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Truncate text to a certain length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate slug from string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}
?>