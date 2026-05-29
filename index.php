<?php
// index.php - Main landing page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'FIBECO Bidding System';
$userRole = $_SESSION['user_role'] ?? 'user';
$userName = $_SESSION['user_name'] ?? 'User';
$isAdmin = ($userRole === 'admin');

// Get public bidding records
$public_bidding = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM public_bidding ORDER BY bidding_date DESC LIMIT 10");
    $public_bidding = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
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
        
        /* Hero Section with Electric Effect */
        .hero-section {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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
        
        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        /* Cards with Electric Theme */
        .card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .card:hover::before {
            left: 0;
        }
        
        .card-icon {
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
        
        .card:hover .card-icon {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(26, 109, 212, 0.5);
        }
        
        .card-icon i {
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
            .hero-title {
                font-size: 2rem;
            }
            .brand-text {
                font-size: 1rem;
            }
            .navbar-brand {
                font-size: 1.2rem;
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
        
        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
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
                        <a class="nav-link active" href="index.php">
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
        <div class="hero-section text-white p-5 mb-5">
            <div class="hero-content text-center">
                <i class="fas fa-bolt fa-3x mb-3" style="color: #ffd700;"></i>
                <h1 class="hero-title">First Bukidnon Electric Cooperative, Incorporated.</h1>
                <h3><strong>FIBECO, Inc.</strong></h3>
                <p class="hero-subtitle">Powering Progress Through Transparent Procurement</p>
                <div class="mt-4">
                    <span class="badge bg-warning text-dark me-2">
                        <i class="fas fa-plug"></i> Reliable Power
                    </span>
                    <span class="badge bg-info text-dark me-2">
                        <i class="fas fa-handshake"></i> Transparent Bidding
                    </span>
                    <span class="badge bg-success">
                        <i class="fas fa-chart-line"></i> Community First
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6">
                <div class="card text-center p-3">
                    <div class="card-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                        <i class="fas fa-plug"></i>
                    </div>
                    <h3 class="mb-0 text-primary">50+</h3>
                    <p class="text-muted small">MW Generated</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center p-3">
                    <div class="card-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="mb-0 text-primary">100K+</h3>
                    <p class="text-muted small">Happy Consumers</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center p-3">
                    <div class="card-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <h3 class="mb-0 text-primary">₱ 50M+</h3>
                    <p class="text-muted small">Bid Projects</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center p-3">
                    <div class="card-icon" style="width: 50px; height: 50px; margin: 0 auto 15px;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="mb-0 text-primary">100%</h3>
                    <p class="text-muted small">Transparent</p>
                </div>
            </div>
        </div>

        <!-- Public Bidding Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-gavel me-2"></i>
                Public Bidding Results
            </h2>
            <span class="badge bg-primary p-2">
                <i class="fas fa-sync-alt"></i> Updated Monthly
            </span>
        </div>
        
        <?php if (empty($public_bidding)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No bidding records found. Please check back later for updates.
            </div>
        <?php else: ?>
            <div class="table-responsive bidding-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Date</th>
                            <th><i class="fas fa-project-diagram"></i> Project Title</th>
                            <th><i class="fas fa-dollar-sign"></i> Fund Source</th>
                            <th><i class="fas fa-chart-line"></i> ABC</th>
                            <th><i class="fas fa-tasks"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($public_bidding as $bid): ?>
                            <tr>
                                <td><i class="far fa-calendar-alt text-muted me-1"></i> <?php echo date('M d, Y', strtotime($bid['bidding_date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($bid['project_title'], 0, 60)); ?></strong>
                                    <?php if (strlen($bid['project_title']) > 60): ?>...<?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($bid['fund_source']); ?></td>
                                <td class="text-end">₱ <?php echo number_format($bid['approved_budget_contract'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $bid['status'] == 'active' ? 'primary' : 
                                            ($bid['status'] == 'completed' ? 'success' : 
                                            ($bid['status'] == 'ongoing' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <i class="fas fa-<?php 
                                            echo $bid['status'] == 'active' ? 'play' : 
                                                ($bid['status'] == 'completed' ? 'check' : 
                                                ($bid['status'] == 'ongoing' ? 'sync' : 'stop')); 
                                        ?> me-1"></i>
                                        <?php echo ucfirst($bid['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Feature Cards -->
        <div class="row mt-5 g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h5 class="mb-3">Power Supply Procurement</h5>
                    <p class="text-muted">Transparent bidding for power supply agreements ensuring reliable electricity for all consumers.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-tower-broadcast"></i>
                    </div>
                    <h5 class="mb-3">Infrastructure Projects</h5>
                    <p class="text-muted">Substation upgrades, transmission lines, and distribution network improvements.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5 class="mb-3">Document Access</h5>
                    <p class="text-muted">Complete bidding documents available for download by registered suppliers.</p>
                </div>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white text-center p-5" style="background: linear-gradient(135deg, var(--electric-blue), var(--electric-dark));">
                    <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                    <h3>Become a Supplier Partner</h3>
                    <p>Register now to participate in our bidding opportunities and be part of FIBECO's growth.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div>
                            <a href="register.php" class="btn btn-electric btn-lg mt-3">
                                <i class="fas fa-user-plus"></i> Register Now
                            </a>
                        </div>
                    <?php else: ?>
                        <div>
                            <a href="dashboard.php" class="btn btn-electric btn-lg mt-3">
                                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="dashboard.php" class="btn btn-outline-electric me-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                </a>
                <a href="register.php" class="btn btn-electric">
                    <i class="fas fa-user-plus me-2"></i> Register as Supplier
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