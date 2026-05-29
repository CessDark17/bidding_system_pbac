<?php
/**
 * User Dashboard
 * File: dashboard.php
 * 
 * Displays sealed bidding results and user-specific information.
 * Only accessible to authenticated users.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simple auth check - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Dashboard - Sealed Bidding';
$userRole = $_SESSION['user_role'] ?? 'user';
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get sealed bidding records directly
$sealedRecords = [];
$totalSealed = 0;
$awardedCount = 0;
$totalAmount = 0;
$error = '';

if ($db) {
    try {
        // First check if the sealed_bidding table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'sealed_bidding'");
        if ($tableCheck->rowCount() > 0) {
            
            // Check what columns exist in the table
            $columns = $db->query("DESCRIBE sealed_bidding")->fetchAll(PDO::FETCH_COLUMN);
            
            // Build query based on existing columns
            $selectFields = ['*'];
            $orderBy = "id DESC";
            
            // Check if bidding_date exists
            if (in_array('bidding_date', $columns)) {
                $orderBy = "bidding_date DESC";
            }
            
            // Get records
            $stmt = $db->query("SELECT * FROM sealed_bidding ORDER BY $orderBy LIMIT 20");
            $sealedRecords = $stmt->fetchAll();
            $totalSealed = count($sealedRecords);
            
            // Calculate awarded count and total amount if columns exist
            if (in_array('status', $columns)) {
                $stmt = $db->query("SELECT COUNT(*) as awarded FROM sealed_bidding WHERE status = 'awarded'");
                $awardedCount = $stmt->fetch()['awarded'] ?? 0;
            } else {
                $awardedCount = 0;
            }
            
            if (in_array('winning_bid_amount', $columns)) {
                $stmt = $db->query("SELECT COALESCE(SUM(winning_bid_amount), 0) as total FROM sealed_bidding");
                $totalAmount = $stmt->fetch()['total'] ?? 0;
            }
            
        } else {
            $error = "Sealed bidding table not found. Please run the database setup.";
        }
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --electric-blue: #0a2a4a;
            --electric-dark: #061a30;
            --electric-glow: #1a6dd4;
            --electric-light: #2d8cf0;
            --electric-accent: #00d4ff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
        }
        
        /* Electric Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--electric-blue) 0%, var(--electric-dark) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid var(--electric-accent);
        }
        
        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-icon {
            position: relative;
            width: 40px;
            height: 40px;
        }
        
        .electric-logo {
            position: relative;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .electric-logo i {
            font-size: 24px;
            color: var(--electric-dark);
        }
        
        .electric-logo::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ffd700, #ff8c00, #ffd700);
            border-radius: 50%;
            z-index: -1;
            animation: borderPulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(255, 215, 0, 0.5); }
            50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(255, 215, 0, 0.8); }
        }
        
        @keyframes borderPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .brand-text {
            background: linear-gradient(135deg, #ffffff, var(--electric-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .dashboard-header::after {
            content: '⚡';
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 80px;
            opacity: 0.1;
            animation: flicker 3s infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes flicker {
            0%, 100% { opacity: 0.1; text-shadow: none; }
            50% { opacity: 0.3; text-shadow: 0 0 20px rgba(0, 212, 255, 0.5); }
        }
        
        .dashboard-content {
            position: relative;
            z-index: 2;
        }
        
        /* Cards with Electric Theme */
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .stat-card:hover::before {
            left: 0;
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(26, 109, 212, 0.5);
        }
        
        .stat-icon i {
            font-size: 32px;
            color: white;
        }
        
        /* Table Styling */
        .bidding-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .bidding-table thead {
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            color: white;
        }
        
        .bidding-table thead th {
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .bidding-table tbody tr:hover {
            background-color: rgba(26, 109, 212, 0.05);
            cursor: pointer;
        }
        
        /* Button Styling */
        .btn-electric {
            background: linear-gradient(135deg, var(--electric-glow), var(--electric-accent));
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-electric:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
            color: white;
        }
        
        .btn-outline-electric {
            border: 2px solid var(--electric-glow);
            background: transparent;
            color: var(--electric-glow);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-electric:hover {
            background: var(--electric-glow);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Alert Styling */
        .alert-confidential {
            background: linear-gradient(135deg, #fff3cd, #ffe69e);
            border-left: 4px solid #ffc107;
            border-radius: 10px;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--electric-dark), var(--electric-blue));
            color: white;
            margin-top: 60px;
            padding: 30px 0;
            border-top: 2px solid var(--electric-accent);
        }
        
        .footer a {
            color: var(--electric-accent);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Badge Styling */
        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
        }
        
        /* Electricity animation lines */
        .electric-line {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--electric-accent), transparent);
            animation: electricLine 3s infinite;
            z-index: 999;
        }
        
        @keyframes electricLine {
            0% { opacity: 0; transform: translateX(-100%); }
            50% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(100%); }
        }
        
        /* Welcome Badge */
        .welcome-badge {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 8px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<body>
    <!-- Electric Line Animation -->
    <div class="electric-line"></div>
    
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="brand-icon">
                    <div class="electric-logo">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <div>
                    <span class="brand-text"><strong>FIBECO</strong></span>
                    <small class="d-block text-white-50" style="font-size: 10px;">Electric Cooperative</small>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bid-calendar.php">
                            <i class="fas fa-calendar-alt"></i> Bid Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </li>
                </ul>
                
                <div class="ms-lg-3 mt-3 mt-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center"
                                    type="button"
                                    id="userDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                                <span class="badge bg-warning text-dark ms-2"><?php echo $userRole; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item py-2" href="dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="summary.php">
                                        <i class="fas fa-chart-pie me-2 text-info"></i>
                                        Summary
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="bid-calendar.php">
                                        <i class="fas fa-calendar-alt me-2 text-info"></i>
                                        Bid Calendar
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="profile.php">
                                        <i class="fas fa-user me-2 text-success"></i>
                                        Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="settings.php">
                                        <i class="fas fa-cog me-2 text-warning"></i>
                                        Settings
                                    </a>
                                </li>
                                <?php if ($userRole == 'admin'): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="import-data.php">
                                        <i class="fas fa-upload me-2 text-info"></i>
                                        Import Data
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="add-record.php">
                                        <i class="fas fa-plus me-2 text-success"></i>
                                        Add Record
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger py-2" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column flex-lg-row gap-2">
                            <a href="login.php" class="btn btn-outline-light">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Login
                            </a>
                            <a href="register.php" class="btn btn-electric">
                                <i class="fas fa-user-plus me-1"></i>
                                Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header text-white p-4">
            <div class="dashboard-content">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
                        <p class="mb-0 opacity-75">Sealed Bidding Management Dashboard</p>
                    </div>
                    <div class="welcome-badge mt-3 mt-sm-0">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card text-center p-4">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h2 class="h1 mb-0 text-primary"><?php echo number_format($totalSealed); ?></h2>
                    <p class="text-muted mb-0">Total Sealed Bidding Records</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center p-4">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h2 class="h1 mb-0 text-success"><?php echo number_format($awardedCount); ?></h2>
                    <p class="text-muted mb-0">Awarded Contracts</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center p-4">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2 class="h1 mb-0 text-warning">₱ <?php echo number_format($totalAmount, 2); ?></h2>
                    <p class="text-muted mb-0">Total Awarded Amount</p>
                </div>
            </div>
        </div>
        
        <!-- Confidential Notice -->
        <div class="alert alert-confidential mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3" style="color: #856404;"></i>
                <div>
                    <strong><i class="fas fa-lock me-2"></i> Confidential Information Notice:</strong> 
                    The following sealed bidding records contain sensitive procurement information 
                    and are intended for authorized personnel only.
                </div>
            </div>
        </div>
        
        <!-- Sealed Bidding Records Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3" style="border-bottom: 2px solid var(--electric-glow);">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lock me-2" style="color: var(--electric-glow);"></i>
                    Sealed Bidding Results
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 bidding-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-alt"></i> Bidding Date</th>
                                <th><i class="fas fa-project-diagram"></i> Project Title</th>
                                <th><i class="fas fa-dollar-sign"></i> Fund Source</th>
                                <th><i class="fas fa-user-tie"></i> Winning Bidder</th>
                                <th><i class="fas fa-chart-line"></i> Winning Amount</th>
                                <th><i class="fas fa-tasks"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sealedRecords)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-0">No sealed bidding records found.</p>
                                        <p class="text-muted small mt-2">Please add some records to get started.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sealedRecords as $record): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            if (isset($record['bidding_date']) && $record['bidding_date']) {
                                                echo '<i class="far fa-calendar-alt text-muted me-1"></i> ' . date('M d, Y', strtotime($record['bidding_date']));
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-wrap" style="max-width: 300px;">
                                            <strong><?php echo htmlspecialchars($record['project_title'] ?? 'Untitled'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['fund_source'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['winning_bidder'] ?? 'N/A'); ?></td>
                                        <td class="text-end">
                                            <?php if (isset($record['winning_bid_amount']) && $record['winning_bid_amount']): ?>
                                                <strong>₱ <?php echo number_format($record['winning_bid_amount'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $record['status'] ?? 'pending';
                                            $badgeClass = 'secondary';
                                            $icon = 'stop';
                                            
                                            if ($status == 'awarded') {
                                                $badgeClass = 'success';
                                                $icon = 'trophy';
                                            } elseif ($status == 'pending') {
                                                $badgeClass = 'warning';
                                                $icon = 'clock';
                                            } elseif ($status == 'evaluating') {
                                                $badgeClass = 'info';
                                                $icon = 'search';
                                            } elseif ($status == 'cancelled') {
                                                $badgeClass = 'danger';
                                                $icon = 'times';
                                            } elseif ($status == 'active') {
                                                $badgeClass = 'primary';
                                                $icon = 'play';
                                            } elseif ($status == 'completed') {
                                                $badgeClass = 'success';
                                                $icon = 'check';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="index.php" class="btn btn-outline-electric">
                    <i class="fas fa-arrow-left me-2"></i> Back to Home
                </a>
                <a href="summary.php" class="btn btn-electric ms-2">
                    <i class="fas fa-chart-pie me-2"></i> View Summary
                </a>
                <?php if ($userRole == 'admin'): ?>
                    <a href="import-data.php" class="btn btn-info ms-2">
                        <i class="fas fa-upload me-2"></i> Import Data
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/templates/footer.php'; ?>

    <!-- Bootstrap JS - Moved to bottom for better performance -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize dropdowns script -->
    <script>
        // Ensure dropdowns work properly
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>
</html>