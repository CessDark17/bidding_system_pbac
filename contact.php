<?php
// contact.php - Contact Information Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Contact Us - FIBECO Bidding System';
$userRole = $_SESSION['user_role'] ?? 'user';
$userName = $_SESSION['user_name'] ?? 'User';
$isAdmin = ($userRole === 'admin'); // Add this line to fix the error
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --electric-blue: #0a2a4a;
            --electric-dark: #061a30;
            --electric-glow: #1a6dd4;
            --electric-light: #2d8cf0;
            --electric-accent: #00d4ff;
            --globe-color: #00a650;
            --smart-color: #ff0000;
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
        
        /* Info Card */
        .info-card {
            text-align: center;
            padding: 25px;
        }
        
        .fibeco-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .fibeco-logo i {
            font-size: 40px;
            color: white;
        }
        
        /* Contact Row */
        .contact-row {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 20px;
            margin: 20px 0;
        }
        
        .contact-col {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .contact-col:hover {
            background: #f0f7ff;
            transform: translateY(-3px);
        }
        
        .operator-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .globe-logo {
            background: linear-gradient(135deg, #00a650, #008040);
        }
        
        .smart-logo {
            background: linear-gradient(135deg, #ff0000, #cc0000);
        }
        
        .operator-logo i {
            font-size: 24px;
            color: white;
        }
        
        .operator-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .phone-number {
            padding: 8px;
            margin: 5px 0;
            border-radius: 8px;
            background: white;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .phone-number.globe {
            color: #00a650;
            border-left: 3px solid #00a650;
        }
        
        .phone-number.smart {
            color: #ff0000;
            border-left: 3px solid #ff0000;
        }
        
        .phone-number:hover {
            transform: translateX(5px);
        }
        
        .divider {
            width: 1px;
            background: linear-gradient(180deg, transparent, #ddd, transparent);
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
        
        /* Social Cards */
        .social-card {
            text-align: center;
            padding: 20px;
            cursor: pointer;
        }
        
        .social-card:hover {
            transform: translateY(-5px);
        }
        
        .social-icon-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            transition: all 0.3s ease;
        }
        
        .social-card:hover .social-icon-large {
            transform: scale(1.1);
        }
        
        .facebook { background: #1877f2; }
        .instagram { background: linear-gradient(45deg, #f09433, #d62976, #962fbf); }
        .threads { background: #000000; }
        .linkedin { background: #0077b5; }
        .website { background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow)); }
        .tiktok { background: #000000; }
        
        .social-icon-large i {
            font-size: 28px;
            color: white;
        }
        
        /* Form Styling */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--electric-glow);
            box-shadow: 0 0 0 3px rgba(26, 109, 212, 0.2);
        }
        
        /* Map Container */
        .map-container {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .map-container iframe {
            width: 100%;
            height: 400px;
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
            .contact-row {
                flex-direction: column;
            }
            .divider {
                display: none;
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
                        <a class="nav-link active" href="contact.php">
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
                <i class="fas fa-headset fa-3x mb-3" style="color: #ffd700;"></i>
                <h1 class="hero-title">Contact Us</h1>
                <p class="hero-subtitle">We're here to help and answer any questions you may have</p>
                <div class="mt-4">
                    <span class="badge bg-warning text-dark me-2">
                        <i class="fas fa-phone"></i> 24/7 Support
                    </span>
                    <span class="badge bg-info text-dark me-2">
                        <i class="fas fa-envelope"></i> Quick Response
                    </span>
                    <span class="badge bg-success">
                        <i class="fas fa-headset"></i> Customer Care
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- FIBECO Logo and Info Card -->
            <div class="col-lg-4">
                <div class="card info-card h-100">
                    <div class="card-body text-center">
                        <div class="fibeco-logo">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h4>FIBECO, Incorporated</h4>
                        <p class="text-muted">First Bukidnon Electric Cooperative, Inc.</p>
                        <hr>
                        <p><strong><i class="fas fa-map-marker-alt me-2"></i>Office Address:</strong></p>
                        <p>FIBECO Main Building</p>
                        <p>Maramag, Bukidnon</p>
                        <p>Philippines 8714</p>
                        <hr>
                        <p class="text-muted small">
                            <i class="fas fa-clock me-1"></i> Mon-Fri: 8:00 AM - 5:00 PM
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Numbers Card -->
            <div class="col-lg-4">
                <div class="card info-card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="card-icon mx-auto">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h5>Contact Numbers</h5>
                        </div>
                        
                        <div class="text-center mb-3">
                            <strong><i class="fas fa-comment-dots me-1"></i>General Inquiries:</strong>
                        </div>
                        
                        <div class="contact-row">
                            <div class="contact-col">
                                <div class="text-center">
                                    <div class="operator-logo globe-logo mx-auto">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div class="operator-header">
                                        <span style="color: var(--globe-color);">GLOBE | TM</span>
                                    </div>
                                    <div class="phone-number globe">
                                        <i class="fas fa-phone"></i> 0917-795-1451
                                    </div>
                                    <div class="phone-number globe">
                                        <i class="fas fa-phone"></i> 0995-523-5651
                                    </div>
                                    <div class="phone-number globe">
                                        <i class="fas fa-phone"></i> 0995-523-5646
                                    </div>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="contact-col">
                                <div class="text-center">
                                    <div class="operator-logo smart-logo mx-auto">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="operator-header">
                                        <span style="color: var(--smart-color);">SMART | TNT</span>
                                    </div>
                                    <div class="phone-number smart">
                                        <i class="fas fa-phone"></i> 0950-768-6902
                                    </div>
                                    <div class="phone-number smart">
                                        <i class="fas fa-phone"></i> 0950-768-6903
                                    </div>
                                    <div class="phone-number smart">
                                        <i class="fas fa-phone"></i> 0962-990-7065
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="text-center">
                            <p class="mb-0"><strong><i class="fas fa-headset me-1"></i>TAWAG CENTER HOTLINE NUMBERS</strong></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Email Card -->
            <div class="col-lg-4">
                <div class="card info-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon mx-auto">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Addresses</h5>
                        <p><strong>General:</strong><br><a href="mailto:contact_us@fibeco.ph">contact_us@fibeco.ph</a></p>
                        <p><strong>BAC Secretariat:</strong><br><a href="mailto:bac@fibeco.ph">bac@fibeco.ph</a></p>
                        <p><strong>Procurement:</strong><br><a href="mailto:procurement@fibeco.ph">procurement@fibeco.ph</a></p>
                        <hr>
                        <p><strong>Bidding Support:</strong><br><a href="mailto:bidding@fibeco.ph">bidding@fibeco.ph</a></p>
                        <p><strong>Website:</strong><br><a href="https://www.fibeco.ph" target="_blank">www.fibeco.ph</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Connect Section -->
        <div class="row mt-5">
            <div class="col-12 text-center mb-4">
                <h2>
                    <i class="fas fa-share-alt me-2"></i>
                    Connect With Us
                </h2>
                <p class="text-muted">Follow us on social media for updates and announcements</p>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.facebook.com/fibecopro" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large facebook mx-auto">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        <h6 class="mb-1">Facebook</h6>
                        <p class="small text-muted mb-0">@fibecopro</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.instagram.com/fibecopro" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large instagram mx-auto">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <h6 class="mb-1">Instagram</h6>
                        <p class="small text-muted mb-0">@fibecopro</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.threads.net/@fibecopro" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large threads mx-auto">
                            <i class="fab fa-threads"></i>
                        </div>
                        <h6 class="mb-1">Threads</h6>
                        <p class="small text-muted mb-0">@fibecopro</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.linkedin.com/company/fibecopro" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large linkedin mx-auto">
                            <i class="fab fa-linkedin-in"></i>
                        </div>
                        <h6 class="mb-1">LinkedIn</h6>
                        <p class="small text-muted mb-0">fibecopro</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.fibeco.ph" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large website mx-auto">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h6 class="mb-1">Website</h6>
                        <p class="small text-muted mb-0">fibeco.ph</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-2 col-6 mb-3">
                <a href="https://www.tiktok.com/@fibecopro" target="_blank" class="text-decoration-none">
                    <div class="card social-card h-100">
                        <div class="social-icon-large tiktok mx-auto">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <h6 class="mb-1">TikTok</h6>
                        <p class="small text-muted mb-0">@fibecopro</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Department Contacts -->
        <div class="row mt-4 g-4">
            <div class="col-12 text-center mb-3">
                <h2>
                    <i class="fas fa-building me-2"></i>
                    Department Contacts
                </h2>
                <p class="text-muted">Reach out to our specialized teams</p>
            </div>
            
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="card-icon" style="width: 50px; height: 50px; margin: 0 15px 0 0;">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">BAC Chairman</h6>
                            <p class="mb-0 small">Engr. Antonio Obice Jr.</p>
                            <p class="mb-0 small text-muted">bac@fibeco.ph</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="card-icon" style="width: 50px; height: 50px; margin: 0 15px 0 0;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">BAC Secretariat</h6>
                            <p class="mb-0 small">Bernadeth B. Chavez</p>
                            <p class="mb-0 small text-muted">bac.secretariat@fibeco.ph</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="card-icon" style="width: 50px; height: 50px; margin: 0 15px 0 0;">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Procurement Head</h6>
                            <p class="mb-0 small">Jemilo L. Pelimer, CPA</p>
                            <p class="mb-0 small text-muted">procurement@fibeco.ph</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form and Map -->
        <div class="row mt-5 g-4">
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <h4 class="mb-4">
                        <i class="fas fa-paper-plane me-2"></i>
                        Send Us a Message
                    </h4>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Your message has been sent successfully! We'll get back to you soon.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="send-message.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Your Name</label>
                                <input type="text" class="form-control" name="name" required 
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject" required>
                                <option value="">Select Subject</option>
                                <option value="Bidding Inquiry">Bidding Inquiry</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Supplier Registration">Supplier Registration</option>
                                <option value="Document Request">Document Request</option>
                                <option value="General Inquiry">General Inquiry</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-electric w-100">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card p-0 overflow-hidden h-100">
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d252481.7208762704!2d124.900568!3d7.766667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f9b1c2c3d4e5f7%3A0x1a2b3c4d5e6f7a8b!2sMaramag%2C%20Bukidnon!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph" 
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                    <div class="card-body text-center bg-light">
                        <p class="mb-0">
                            <i class="fas fa-location-dot text-danger me-1"></i>
                            View our location on Google Maps
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12 text-center mb-4">
                <h2>
                    <i class="fas fa-question-circle me-2"></i>
                    Frequently Asked Questions
                </h2>
                <p class="text-muted">Quick answers to common questions</p>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-gavel fa-2x" style="color: var(--electric-glow);"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">How can I participate in bidding?</h6>
                            <p class="small text-muted mb-0">Register as a supplier, complete the requirements, and stay updated with our announcements.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-file-alt fa-2x" style="color: var(--electric-glow);"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">Where can I get bidding documents?</h6>
                            <p class="small text-muted mb-0">Bidding documents are available for download after registration on this portal.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-lock fa-2x" style="color: var(--electric-glow);"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">Is sealed bidding confidential?</h6>
                            <p class="small text-muted mb-0">Yes, sealed bidding results are only accessible to registered and authorized users.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card p-3 h-100">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-envelope fa-2x" style="color: var(--electric-glow);"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">Who do I contact for technical issues?</h6>
                            <p class="small text-muted mb-0">Contact our BAC Secretariat at bac@fibeco.ph or call +63 917-795-1451.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-4">
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