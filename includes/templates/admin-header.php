<?php
/**
 * Admin Header Template
 * File: includes/templates/admin-header.php
 * 
 * This template is included at the top of all admin pages.
 * It contains the HTML head, admin navigation bar, and sidebar.
 * 
 * Variables expected:
 *   $pageTitle - Optional page title (defaults to 'Admin Dashboard')
 * 
 * Note: This template assumes admin middleware has already been called.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default page title if not provided
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Dashboard';
}
$fullTitle = $pageTitle . ' | ' . APP_NAME . ' (Admin)';

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FIBECO Bidding System - Administration Panel">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/upload.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <style>
        /* Ensure sidebar is visible on admin pages */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .admin-main {
            flex: 1;
            background-color: #f0f2f5;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar will be included here -->
        <?php include 'admin-sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="admin-main">
            <!-- Top Navbar -->
            <nav class="admin-navbar">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle" id="sidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="navbar-search d-none d-md-block">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control bg-transparent border-0" 
                                   placeholder="Search..." id="globalSearch">
                        </div>
                    </div>
                </div>
                
                <div class="navbar-user">
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark position-relative" type="button" 
                                data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end dropdown-notifications" style="width: 300px;">
                            <div class="dropdown-header bg-light">
                                <h6 class="mb-0">Notifications</h6>
                            </div>
                            <div id="notificationList" class="list-group list-group-flush">
                                <div class="text-center py-3 text-muted">Loading...</div>
                            </div>
                            <div class="dropdown-footer text-center">
                                <a href="#" class="small">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <div class="user-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                            </div>
                            <div class="user-info d-none d-sm-block">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                                <div class="user-role"><?php echo ucfirst($_SESSION['user_role'] ?? 'Admin'); ?></div>
                            </div>
                            <i class="fas fa-chevron-down d-none d-sm-block ms-2 text-muted"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-user-circle me-2"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="fas fa-key me-2"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> User Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="container-fluid px-4 py-3">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Home</a></li>
                        <?php if (isset($breadcrumb)): ?>
                            <?php foreach ($breadcrumb as $item): ?>
                                <?php if (isset($item['url'])): ?>
                                    <li class="breadcrumb-item"><a href="<?php echo $item['url']; ?>"><?php echo $item['label']; ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $item['label']; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </nav>
                
                <!-- Flash Message Display -->
                <?php echo displayFlashMessage(); ?>