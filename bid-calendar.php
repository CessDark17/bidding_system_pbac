<?php
/**
 * Bid Calendar Page
 * File: bid-calendar.php
 * 
 * Displays upcoming and past bidding events
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Bid Calendar';
$userRole = $_SESSION['user_role'] ?? 'user';
$userName = $_SESSION['user_name'] ?? 'User';
$isAdmin = ($userRole === 'admin');

// Get bidding records
$upcomingBids = [];
$pastBids = [];
$error = '';

if ($db) {
    try {
        // Check if public_bidding table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'public_bidding'");
        if ($tableCheck->rowCount() > 0) {
            
            // Check what columns exist
            $columns = $db->query("DESCRIBE public_bidding")->fetchAll(PDO::FETCH_COLUMN);
            
            // Check if status column exists
            $hasStatus = in_array('status', $columns);
            $hasBiddingDate = in_array('bidding_date', $columns);
            
            if ($hasBiddingDate) {
                $currentDate = date('Y-m-d');
                
                // Get upcoming bids (bidding_date >= today)
                if ($hasStatus) {
                    $stmt = $db->prepare("SELECT * FROM public_bidding WHERE bidding_date >= ? AND status != 'completed' ORDER BY bidding_date ASC LIMIT 10");
                    $stmt->execute([$currentDate]);
                    $upcomingBids = $stmt->fetchAll();
                    
                    // Get past bids
                    $stmt = $db->prepare("SELECT * FROM public_bidding WHERE bidding_date < ? OR status = 'completed' ORDER BY bidding_date DESC LIMIT 10");
                    $stmt->execute([$currentDate]);
                    $pastBids = $stmt->fetchAll();
                } else {
                    // No status column, just use date
                    $stmt = $db->prepare("SELECT * FROM public_bidding WHERE bidding_date >= ? ORDER BY bidding_date ASC LIMIT 10");
                    $stmt->execute([$currentDate]);
                    $upcomingBids = $stmt->fetchAll();
                    
                    $stmt = $db->prepare("SELECT * FROM public_bidding WHERE bidding_date < ? ORDER BY bidding_date DESC LIMIT 10");
                    $stmt->execute([$currentDate]);
                    $pastBids = $stmt->fetchAll();
                }
            } else {
                // No bidding_date column, just get all records
                $stmt = $db->query("SELECT * FROM public_bidding ORDER BY id DESC LIMIT 20");
                $allBids = $stmt->fetchAll();
                $upcomingBids = $allBids;
                $pastBids = [];
            }
        } else {
            // Create sample data if table doesn't exist
            $error = "Public bidding table not found. Please run database setup.";
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
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .hero-section::after {
            content: '⚡';
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 100px;
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
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }
        
        /* Calendar Cards */
        .calendar-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
            position: relative;
        }
        
        .calendar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .calendar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .calendar-card:hover::before {
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
        
        /* Bid Item */
        .bid-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .bid-item:last-child {
            border-bottom: none;
        }
        
        .bid-item:hover {
            background-color: rgba(26, 109, 212, 0.05);
            transform: translateX(5px);
        }
        
        .bid-date {
            font-size: 1rem;
            font-weight: bold;
            color: var(--electric-glow);
        }
        
        .bid-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .bid-fund {
            font-size: 0.85rem;
            color: #6c757d;
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
        
        /* Badge */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            .bid-date {
                margin-bottom: 8px;
            }
        }
        
        /* Electricity animation */
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
        
        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
        }
        
        .alert-info {
            background: #e8f4fd;
            border-left: 4px solid var(--electric-glow);
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
                        <a class="nav-link active" href="bid-calendar.php">
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
                                <?php if ($isAdmin): ?>
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
        <!-- Hero Section -->
        <div class="hero-section text-white p-5">
            <div class="hero-content text-center">
                <i class="fas fa-calendar-alt fa-3x mb-3" style="color: #ffd700;"></i>
                <h1 class="hero-title">Bidding Calendar</h1>
                <p class="lead mb-0">Stay updated with upcoming and past bidding opportunities</p>
                <div class="mt-4">
                    <span class="badge bg-warning text-dark me-2">
                        <i class="fas fa-clock"></i> Upcoming Bids
                    </span>
                    <span class="badge bg-info text-dark me-2">
                        <i class="fas fa-history"></i> Past Records
                    </span>
                    <span class="badge bg-success">
                        <i class="fas fa-gavel"></i> Transparent Process
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <!-- Upcoming Bids -->
            <div class="col-lg-6">
                <div class="calendar-card position-relative">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-clock"></i>
                            Upcoming Bidding Events
                            <span class="badge bg-success ms-2"><?php echo count($upcomingBids); ?> Active</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($upcomingBids)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No upcoming bidding events at this time.</p>
                                <p class="text-muted small">Please check back later for updates.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingBids as $bid): ?>
                                <div class="bid-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="bid-date">
                                                <i class="far fa-calendar-alt me-1"></i>
                                                <?php 
                                                if (isset($bid['bidding_date']) && $bid['bidding_date']) {
                                                    echo date('M d, Y', strtotime($bid['bidding_date']));
                                                } else {
                                                    echo 'Date TBA';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bid-title">
                                                <?php echo htmlspecialchars($bid['project_title'] ?? 'Untitled Project'); ?>
                                            </div>
                                            <div class="bid-fund">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                Fund Source: <?php echo htmlspecialchars($bid['fund_source'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                            <?php if (isset($bid['status'])): ?>
                                                <span class="badge bg-<?php 
                                                    echo $bid['status'] == 'active' ? 'primary' : 
                                                        ($bid['status'] == 'ongoing' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $bid['status'] == 'active' ? 'play' : 
                                                            ($bid['status'] == 'ongoing' ? 'sync' : 'stop'); 
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($bid['status'] ?? 'Active'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-play me-1"></i> Active
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Past Bids -->
            <div class="col-lg-6">
                <div class="calendar-card position-relative">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-history"></i>
                            Past Bidding Events
                            <span class="badge bg-secondary ms-2"><?php echo count($pastBids); ?> Completed</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pastBids)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No past bidding records available.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pastBids as $bid): ?>
                                <div class="bid-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="bid-date">
                                                <i class="far fa-calendar-alt me-1"></i>
                                                <?php 
                                                if (isset($bid['bidding_date']) && $bid['bidding_date']) {
                                                    echo date('M d, Y', strtotime($bid['bidding_date']));
                                                } else {
                                                    echo 'Date N/A';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bid-title">
                                                <?php echo htmlspecialchars($bid['project_title'] ?? 'Untitled Project'); ?>
                                            </div>
                                            <div class="bid-fund">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                ABC: ₱ <?php echo number_format($bid['approved_budget_contract'] ?? 0, 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                            <?php if (isset($bid['status'])): ?>
                                                <span class="badge bg-<?php 
                                                    echo $bid['status'] == 'completed' ? 'success' : 'secondary'; 
                                                ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $bid['status'] == 'completed' ? 'check' : 'stop'; 
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($bid['status'] ?? 'Completed'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i> Completed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <strong>How to Participate:</strong><br>
                            <small>To join our bidding events, please register as a supplier and wait for the bid announcements. 
                            All qualified suppliers are welcome to participate in our transparent bidding process.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="index.php" class="btn btn-outline-electric me-2">
                    <i class="fas fa-home me-2"></i> Back to Home
                </a>
                <a href="dashboard.php" class="btn btn-electric">
                    <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/templates/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize dropdowns script -->
    <script>
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