<?php
/**
 * Frontend Header Template
 * FIBECO Bidding System
 * File: includes/templates/header.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default page title if not provided
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
} else {
    $pageTitle = $pageTitle . ' | ' . APP_NAME;
}

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FIBECO Bidding Management System - Public Bidding Portal">
    <meta name="author" content="FIBECO">
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/responsive.css">
    
    <style>
        /* User Dropdown Styles */
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 50px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .user-dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #061a30;
            font-weight: bold;
            font-size: 16px;
        }
        
        .user-info {
            text-align: left;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 0;
            line-height: 1.2;
        }
        
        .user-role {
            font-size: 10px;
            opacity: 0.8;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            margin-top: 10px;
            min-width: 220px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: #6c757d;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            padding-left: 25px;
        }
        
        .dropdown-item:hover i {
            color: #0d6efd;
        }
        
        .dropdown-divider {
            margin: 8px 0;
        }
        
        .badge-role {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .user-info {
                display: none;
            }
            .user-dropdown-toggle {
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-bolt me-2"></i>
                <strong>FIBECO</strong> Bidding Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Public Bidding Link -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>">
                            <i class="fas fa-chart-line"></i> Public Bidding
                        </a>
                    </li>
                    
                    <!-- Authenticated User Links -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Sealed Bidding Link (requires login) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/dashboard.php">
                                <i class="fas fa-lock"></i> Sealed Bidding
                            </a>
                        </li>
                        
                        <!-- Admin Dropdown (only for admin users) -->
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/users.php">
                                        <i class="fas fa-users"></i> Manage Users
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/public-bidding.php">
                                        <i class="fas fa-gavel"></i> Public Bidding
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/sealed-bidding.php">
                                        <i class="fas fa-lock"></i> Sealed Bidding
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/procurement-monitoring.php">
                                        <i class="fas fa-chart-line"></i> Procurement
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/upload.php">
                                        <i class="fas fa-upload"></i> Upload Documents
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/reports.php">
                                        <i class="fas fa-chart-bar"></i> Reports
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <!-- User Dropdown Menu (visible to all logged-in users) -->
                        <li class="nav-item dropdown">
                            <div class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="user-info d-none d-md-block">
                                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                                    <div class="user-role">
                                        <?php echo ucfirst($_SESSION['user_role'] ?? 'user'); ?>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-down d-none d-md-block"></i>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">
                                        <i class="fas fa-user-circle"></i> My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/change-password.php">
                                        <i class="fas fa-key"></i> Change Password
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    
                    <?php else: ?>
                        <!-- Guest Links (not logged in) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container py-4">
            <!-- Flash Message Display -->
            <?php echo displayFlashMessage(); ?>