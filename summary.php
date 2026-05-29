<?php
/**
 * Summary Dashboard Page
 * File: summary.php
 * 
 * Displays summary of Sealed Bidding, Public Bidding, and Procurement Monitoring
 * Role-based access: Admin sees all, Users see limited data
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Summary Dashboard';
$userRole = $_SESSION['user_role'] ?? 'user';
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'User';
$isAdmin = ($userRole === 'admin');

// Initialize data arrays
$sealedSummary = [];
$publicSummary = [];
$procurementSummary = [];
$error = '';

if ($db) {
    try {
        // ==================== SEALED BIDDING SUMMARY ====================
        $sealedTables = $db->query("SHOW TABLES LIKE 'sealed_bidding'");
        if ($sealedTables->rowCount() > 0) {
            $columns = $db->query("DESCRIBE sealed_bidding")->fetchAll(PDO::FETCH_COLUMN);
            
            // Get total count
            $stmt = $db->query("SELECT COUNT(*) as total FROM sealed_bidding");
            $sealedSummary['total'] = $stmt->fetch()['total'] ?? 0;
            
            // Get awarded count
            if (in_array('status', $columns)) {
                $stmt = $db->query("SELECT COUNT(*) as awarded FROM sealed_bidding WHERE status = 'awarded'");
                $sealedSummary['awarded'] = $stmt->fetch()['awarded'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as pending FROM sealed_bidding WHERE status = 'pending'");
                $sealedSummary['pending'] = $stmt->fetch()['pending'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as evaluating FROM sealed_bidding WHERE status = 'evaluating'");
                $sealedSummary['evaluating'] = $stmt->fetch()['evaluating'] ?? 0;
            } else {
                $sealedSummary['awarded'] = 0;
                $sealedSummary['pending'] = $sealedSummary['total'];
                $sealedSummary['evaluating'] = 0;
            }
            
            // Get total awarded amount
            if (in_array('winning_bid_amount', $columns)) {
                $stmt = $db->query("SELECT COALESCE(SUM(winning_bid_amount), 0) as total FROM sealed_bidding WHERE status = 'awarded'");
                $sealedSummary['total_amount'] = $stmt->fetch()['total'] ?? 0;
            } else {
                $sealedSummary['total_amount'] = 0;
            }
            
            // Get ALL records (not just 5) - change LIMIT to show all
            $orderBy = in_array('bidding_date', $columns) ? "bidding_date DESC" : "id DESC";
            $stmt = $db->query("SELECT * FROM sealed_bidding ORDER BY $orderBy");
            $sealedSummary['records'] = $stmt->fetchAll();
            $sealedSummary['recent'] = array_slice($sealedSummary['records'], 0, 10);
        } else {
            $sealedSummary['total'] = 0;
            $sealedSummary['awarded'] = 0;
            $sealedSummary['pending'] = 0;
            $sealedSummary['evaluating'] = 0;
            $sealedSummary['total_amount'] = 0;
            $sealedSummary['records'] = [];
            $sealedSummary['recent'] = [];
        }
        
        // ==================== PUBLIC BIDDING SUMMARY ====================
        $publicTables = $db->query("SHOW TABLES LIKE 'public_bidding'");
        if ($publicTables->rowCount() > 0) {
            $columns = $db->query("DESCRIBE public_bidding")->fetchAll(PDO::FETCH_COLUMN);
            
            // Get total count
            $stmt = $db->query("SELECT COUNT(*) as total FROM public_bidding");
            $publicSummary['total'] = $stmt->fetch()['total'] ?? 0;
            
            // Get status counts
            if (in_array('status', $columns)) {
                $stmt = $db->query("SELECT COUNT(*) as active FROM public_bidding WHERE status = 'active'");
                $publicSummary['active'] = $stmt->fetch()['active'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as ongoing FROM public_bidding WHERE status = 'ongoing'");
                $publicSummary['ongoing'] = $stmt->fetch()['ongoing'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as completed FROM public_bidding WHERE status = 'completed'");
                $publicSummary['completed'] = $stmt->fetch()['completed'] ?? 0;
            } else {
                $publicSummary['active'] = $publicSummary['total'];
                $publicSummary['ongoing'] = 0;
                $publicSummary['completed'] = 0;
            }
            
            // Get total ABC amount
            if (in_array('approved_budget_contract', $columns)) {
                $stmt = $db->query("SELECT COALESCE(SUM(approved_budget_contract), 0) as total FROM public_bidding");
                $publicSummary['total_abc'] = $stmt->fetch()['total'] ?? 0;
            } else {
                $publicSummary['total_abc'] = 0;
            }
            
            // Get ALL records
            $orderBy = in_array('bidding_date', $columns) ? "bidding_date DESC" : "id DESC";
            $stmt = $db->query("SELECT * FROM public_bidding ORDER BY $orderBy");
            $publicSummary['records'] = $stmt->fetchAll();
            $publicSummary['recent'] = array_slice($publicSummary['records'], 0, 10);
        } else {
            $publicSummary['total'] = 0;
            $publicSummary['active'] = 0;
            $publicSummary['ongoing'] = 0;
            $publicSummary['completed'] = 0;
            $publicSummary['total_abc'] = 0;
            $publicSummary['records'] = [];
            $publicSummary['recent'] = [];
        }
        
        // ==================== PROCUREMENT MONITORING SUMMARY ====================
        $procurementTables = $db->query("SHOW TABLES LIKE 'procurement_monitoring'");
        if ($procurementTables->rowCount() > 0) {
            $columns = $db->query("DESCRIBE procurement_monitoring")->fetchAll(PDO::FETCH_COLUMN);
            
            // Get total count
            $stmt = $db->query("SELECT COUNT(*) as total FROM procurement_monitoring");
            $procurementSummary['total'] = $stmt->fetch()['total'] ?? 0;
            
            // Get status counts
            if (in_array('status', $columns)) {
                $stmt = $db->query("SELECT COUNT(*) as ongoing FROM procurement_monitoring WHERE status = 'ongoing'");
                $procurementSummary['ongoing'] = $stmt->fetch()['ongoing'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as completed FROM procurement_monitoring WHERE status = 'completed'");
                $procurementSummary['completed'] = $stmt->fetch()['completed'] ?? 0;
                
                $stmt = $db->query("SELECT COUNT(*) as delayed FROM procurement_monitoring WHERE status = 'delayed'");
                $procurementSummary['delayed'] = $stmt->fetch()['delayed'] ?? 0;
            } else {
                $procurementSummary['ongoing'] = $procurementSummary['total'];
                $procurementSummary['completed'] = 0;
                $procurementSummary['delayed'] = 0;
            }
            
            // Get total contract amount
            if (in_array('contract_amount', $columns)) {
                $stmt = $db->query("SELECT COALESCE(SUM(contract_amount), 0) as total FROM procurement_monitoring");
                $procurementSummary['total_contract'] = $stmt->fetch()['total'] ?? 0;
            } else {
                $procurementSummary['total_contract'] = 0;
            }
            
            // Get ALL records
            $orderBy = in_array('submission_date', $columns) ? "submission_date DESC" : "id DESC";
            $stmt = $db->query("SELECT * FROM procurement_monitoring ORDER BY $orderBy");
            $procurementSummary['records'] = $stmt->fetchAll();
            $procurementSummary['recent'] = array_slice($procurementSummary['records'], 0, 10);
        } else {
            $procurementSummary['total'] = 0;
            $procurementSummary['ongoing'] = 0;
            $procurementSummary['completed'] = 0;
            $procurementSummary['delayed'] = 0;
            $procurementSummary['total_contract'] = 0;
            $procurementSummary['records'] = [];
            $procurementSummary['recent'] = [];
        }
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Calculate overall statistics
$totalProjects = ($sealedSummary['total'] ?? 0) + ($publicSummary['total'] ?? 0) + ($procurementSummary['total'] ?? 0);
$totalAmount = ($sealedSummary['total_amount'] ?? 0) + ($publicSummary['total_abc'] ?? 0) + ($procurementSummary['total_contract'] ?? 0);
$completedProjects = ($publicSummary['completed'] ?? 0) + ($sealedSummary['awarded'] ?? 0) + ($procurementSummary['completed'] ?? 0);
$completionRate = $totalProjects > 0 ? round($completedProjects / $totalProjects * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Summary Header */
        .summary-header {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .summary-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .summary-header::after {
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
        
        .summary-content {
            position: relative;
            z-index: 2;
        }
        
        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
        }
        
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
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(26, 109, 212, 0.5);
        }
        
        .stat-icon i {
            font-size: 28px;
            color: white;
        }
        
        .section-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .section-card:hover::before {
            left: 0;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-dark));
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .card-header-custom h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-table {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .summary-table thead {
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            color: white;
        }
        
        .summary-table thead th {
            font-weight: 600;
            border: none;
            padding: 12px;
        }
        
        .summary-table tbody tr:hover {
            background-color: rgba(26, 109, 212, 0.05);
        }
        
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
        
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .confidential-badge {
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
        }
        
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
        
        .welcome-badge {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 8px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-active { background-color: #0d6efd; }
        .status-ongoing { background-color: #ffc107; color: #000; }
        .status-completed { background-color: #198754; }
        .status-awarded { background-color: #198754; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-evaluating { background-color: #0dcaf0; color: #000; }
        .status-delayed { background-color: #dc3545; }
        
        .data-count {
            font-size: 2rem;
            font-weight: bold;
            color: var(--electric-glow);
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li class="nav-item"><a class="nav-link" href="bid-calendar.php"><i class="fas fa-calendar-alt"></i> Bid Calendar</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
                
                <div class="ms-lg-3 mt-3 mt-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center"
                                    type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                                <span class="badge bg-warning text-dark ms-2"><?php echo $userRole; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item py-2" href="dashboard.php"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="summary.php"><i class="fas fa-chart-pie me-2 text-info"></i> Summary</a></li>
                                <li><a class="dropdown-item py-2" href="bid-calendar.php"><i class="fas fa-calendar-alt me-2 text-info"></i> Bid Calendar</a></li>
                                <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user me-2 text-success"></i> Profile</a></li>
                                <li><a class="dropdown-item py-2" href="settings.php"><i class="fas fa-cog me-2 text-warning"></i> Settings</a></li>
                                <?php if ($isAdmin): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2" href="import-data.php"><i class="fas fa-upload me-2 text-info"></i> Import Data</a></li>
                                <li><a class="dropdown-item py-2" href="add-record.php"><i class="fas fa-plus me-2 text-success"></i> Add Record</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column flex-lg-row gap-2">
                            <a href="login.php" class="btn btn-outline-light"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                            <a href="register.php" class="btn btn-electric"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="summary-header text-white p-4">
            <div class="summary-content">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-chart-pie fa-2x mb-2"></i>
                        <h1 class="mb-1">Procurement Summary Dashboard</h1>
                        <p class="mb-0 opacity-75">Complete overview of all bidding and procurement activities</p>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="welcome-badge mt-3 mt-sm-0">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                        <div class="role-badge">
                            <i class="fas fa-<?php echo $isAdmin ? 'shield-alt' : 'user'; ?> me-1"></i>
                            Logged in as: <strong><?php echo $isAdmin ? 'Administrator' : ucfirst($userRole); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Overall Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="data-count"><?php echo number_format($totalProjects); ?></div>
                    <p class="text-muted mb-0">Total Projects</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="data-count text-success"><?php echo number_format($completionRate); ?>%</div>
                    <p class="text-muted mb-0">Completion Rate</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                    <div class="data-count text-warning">₱ <?php echo number_format($totalAmount, 0); ?></div>
                    <p class="text-muted mb-0">Total Contract Value</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="data-count text-info"><?php echo number_format($publicSummary['active'] ?? 0); ?></div>
                    <p class="text-muted mb-0">Active Bids</p>
                </div>
            </div>
        </div>
        
        <!-- Public Bidding Section -->
        <div class="section-card position-relative">
            <div class="card-header-custom">
                <h5>
                    <i class="fas fa-gavel"></i>
                    Public Bidding Summary
                    <span class="badge bg-success ms-2"><?php echo number_format($publicSummary['total'] ?? 0); ?> Total Records</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Total Projects</small>
                            <h3 class="mb-0 text-primary"><?php echo number_format($publicSummary['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Active</small>
                            <h3 class="mb-0 text-primary"><?php echo number_format($publicSummary['active'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Ongoing</small>
                            <h3 class="mb-0 text-warning"><?php echo number_format($publicSummary['ongoing'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Completed</small>
                            <h3 class="mb-0 text-success"><?php echo number_format($publicSummary['completed'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($publicSummary['records'])): ?>
                    <h6 class="mb-3">All Public Bidding Records (<?php echo number_format($publicSummary['total']); ?> total)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover summary-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project Title</th>
                                    <th>Fund Source</th>
                                    <th>ABC (₱)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publicSummary['records'] as $record): ?>
                                    <tr>
                                        <td><?php echo isset($record['bidding_date']) ? date('M d, Y', strtotime($record['bidding_date'])) : 'N/A'; ?></td>
                                        <td><strong><?php echo htmlspecialchars(substr($record['project_title'] ?? 'Untitled', 0, 60)); ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['fund_source'] ?? 'N/A'); ?></td>
                                        <td>₱ <?php echo number_format($record['approved_budget_contract'] ?? 0, 2); ?></td>
                                        <td><span class="badge status-<?php echo $record['status'] ?? 'active'; ?>"><?php echo ucfirst($record['status'] ?? 'Active'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No public bidding records found. Please import data.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sealed Bidding Section (Admin Only) -->
        <?php if ($isAdmin): ?>
        <div class="section-card position-relative">
            <div class="card-header-custom">
                <h5>
                    <i class="fas fa-lock"></i>
                    Sealed Bidding Summary
                    <span class="confidential-badge"><i class="fas fa-shield-alt me-1"></i> Confidential</span>
                    <span class="badge bg-danger ms-2"><?php echo number_format($sealedSummary['total'] ?? 0); ?> Total Records</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Total Records</small>
                            <h3 class="mb-0 text-primary"><?php echo number_format($sealedSummary['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Awarded</small>
                            <h3 class="mb-0 text-success"><?php echo number_format($sealedSummary['awarded'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Pending</small>
                            <h3 class="mb-0 text-warning"><?php echo number_format($sealedSummary['pending'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Total Awarded Amount</small>
                            <h3 class="mb-0 text-info">₱ <?php echo number_format($sealedSummary['total_amount'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($sealedSummary['records'])): ?>
                    <h6 class="mb-3">All Sealed Bidding Records (<?php echo number_format($sealedSummary['total']); ?> total)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover summary-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project Title</th>
                                    <th>Fund Source</th>
                                    <th>Winning Bidder</th>
                                    <th>Winning Amount (₱)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sealedSummary['records'] as $record): ?>
                                    <tr>
                                        <td><?php echo isset($record['bidding_date']) ? date('M d, Y', strtotime($record['bidding_date'])) : 'N/A'; ?></td>
                                        <td><strong><?php echo htmlspecialchars(substr($record['project_title'] ?? 'Untitled', 0, 60)); ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['fund_source'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['winning_bidder'] ?? 'N/A'); ?></td>
                                        <td>₱ <?php echo number_format($record['winning_bid_amount'] ?? 0, 2); ?></td>
                                        <td><span class="badge status-<?php echo $record['status'] ?? 'pending'; ?>"><?php echo ucfirst($record['status'] ?? 'Pending'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No sealed bidding records found. Please import data.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Procurement Monitoring Section (Admin Only) -->
        <?php if ($isAdmin): ?>
        <div class="section-card position-relative">
            <div class="card-header-custom">
                <h5>
                    <i class="fas fa-clipboard-list"></i>
                    Procurement Monitoring Summary
                    <span class="badge bg-danger ms-2">Admin Only</span>
                    <span class="badge bg-primary ms-2"><?php echo number_format($procurementSummary['total'] ?? 0); ?> Total Records</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Total Procurements</small>
                            <h3 class="mb-0 text-primary"><?php echo number_format($procurementSummary['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Ongoing</small>
                            <h3 class="mb-0 text-warning"><?php echo number_format($procurementSummary['ongoing'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Completed</small>
                            <h3 class="mb-0 text-success"><?php echo number_format($procurementSummary['completed'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted">Delayed</small>
                            <h3 class="mb-0 text-danger"><?php echo number_format($procurementSummary['delayed'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($procurementSummary['records'])): ?>
                    <h6 class="mb-3">All Procurement Records (<?php echo number_format($procurementSummary['total']); ?> total)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover summary-table">
                            <thead>
                                <tr>
                                    <th>Submission Date</th>
                                    <th>Project Title</th>
                                    <th>Supplier Name</th>
                                    <th>Contract Amount (₱)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($procurementSummary['records'] as $record): ?>
                                    <tr>
                                        <td><?php echo isset($record['submission_date']) ? date('M d, Y', strtotime($record['submission_date'])) : 'N/A'; ?></td>
                                        <td><strong><?php echo htmlspecialchars(substr($record['project_title'] ?? 'Untitled', 0, 60)); ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['supplier_name'] ?? 'N/A'); ?></td>
                                        <td>₱ <?php echo number_format($record['contract_amount'] ?? 0, 2); ?></td>
                                        <td><span class="badge status-<?php echo $record['status'] ?? 'ongoing'; ?>"><?php echo ucfirst($record['status'] ?? 'Ongoing'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No procurement records found. Please import data.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="dashboard.php" class="btn btn-outline-electric me-2"><i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard</a>
                <a href="index.php" class="btn btn-electric"><i class="fas fa-home me-2"></i> Back to Home</a>
                <?php if ($isAdmin): ?>
                    <a href="import-data.php" class="btn btn-outline-electric ms-2"><i class="fas fa-upload me-2"></i> Import More Data</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>
</html>