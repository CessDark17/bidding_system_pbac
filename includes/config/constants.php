<?php
/**
 * Application Constants
 * File: includes/config/constants.php
 */

// ======================================================
// PATHS - UPDATE THIS TO YOUR ACTUAL PATH
// ======================================================

// IMPORTANT: Change this to match your local setup
// If your project is at: http://localhost/fibeco-bidding-system/
// Then BASE_URL should be: http://localhost/fibeco-bidding-system

define('BASE_PATH', dirname(dirname(__DIR__)));
define('BASE_URL', 'http://localhost/fibeco-bidding-system');  // CHANGE THIS
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('EXPORTS_PATH', BASE_PATH . '/exports');
define('LOGS_PATH', BASE_PATH . '/logs');

// ======================================================
// UPLOAD CONFIGURATION
// ======================================================
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_FILE_TYPES', serialize([
    'application/pdf',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'image/jpg'
]));

// ======================================================
// USER ROLES
// ======================================================
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');
define('ROLE_VIEWER', 'viewer');

// ======================================================
// DOCUMENT TYPES
// ======================================================
define('DOC_TYPE_PUBLIC_BIDDING', 'public_bidding');
define('DOC_TYPE_SEALED_BIDDING', 'sealed_bidding');
define('DOC_TYPE_PROCUREMENT_MONITORING', 'procurement_monitoring');

// ======================================================
// STATUS CODES
// ======================================================
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_COMPLETED', 'completed');
define('STATUS_FAILED', 'failed');
define('STATUS_CANCELLED', 'cancelled');
define('STATUS_ONGOING', 'ongoing');

// ======================================================
// PAGINATION
// ======================================================
define('ITEMS_PER_PAGE', 20);
define('ADMIN_ITEMS_PER_PAGE', 50);

// ======================================================
// SESSION & SECURITY
// ======================================================
define('SESSION_LIFETIME', 7200);
define('TOKEN_EXPIRY', 86400);
define('BCRYPT_COST', 12);

// ======================================================
// APPLICATION SETTINGS
// ======================================================
define('APP_NAME', 'FIBECO Bidding Management System');
define('APP_VERSION', '1.0.0');
define('COMPANY_NAME', 'First Bukidnon Electric Cooperative, Inc.');
define('COMPANY_ADDRESS', 'Maramag, Bukidnon');
define('TIMEZONE', 'Asia/Manila');

// Set timezone
date_default_timezone_set(TIMEZONE);

// ======================================================
// ERROR REPORTING (Development mode)
// ======================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create logs directory if not exists
if (!file_exists(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0777, true);
}
?>