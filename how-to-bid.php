<?php
// how-to-bid.php - How to Bid Guide Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'How to Bid - FIBECO Bidding System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
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
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .content-card h2 {
            color: var(--electric-blue);
            font-size: 1.5rem;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--electric-accent);
            display: inline-block;
        }
        
        .content-card h3 {
            color: var(--electric-glow);
            font-size: 1.2rem;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .content-card p {
            color: #444;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .step-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--electric-accent);
        }
        
        .step-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-glow));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        .requirements-list {
            list-style: none;
            padding-left: 0;
        }
        
        .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .requirements-list li i {
            color: var(--electric-glow);
            margin-right: 10px;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            .content-card {
                padding: 20px;
            }
        }
        
        /* Electric Line */
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
        
        .nav-link.active {
            color: var(--electric-accent) !important;
            font-weight: bold;
        }
        
        .btn-electric {
            background: linear-gradient(135deg, var(--electric-glow), var(--electric-accent));
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-electric:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
            color: white;
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
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </li>
                </ul>
                <div class="ms-lg-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-electric">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Hero Section -->
        <div class="hero-section text-white p-5 mb-5">
            <div class="hero-content text-center">
                <i class="fas fa-gavel fa-3x mb-3"></i>
                <h1 class="hero-title">How to Bid</h1>
                <p class="hero-subtitle">Your step-by-step guide to participating in FIBECO bidding opportunities</p>
            </div>
        </div>

        <!-- Content -->
        <div class="content-card">
            <h2>Bidding Process Overview</h2>
            <p>FIBECO conducts transparent and competitive bidding processes for all procurement projects. Follow these steps to become a qualified bidder and participate in our bidding opportunities.</p>

            <!-- Step 1 -->
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Register as a Supplier</h3>
                <p>Create an account on the FIBECO Bidding Portal. Provide accurate company information, contact details, and business permits. Registration is free and required for all bidders.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-electric mt-2">
                        <i class="fas fa-user-plus"></i> Register Now
                    </a>
                <?php endif; ?>
            </div>

            <!-- Step 2 -->
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Complete Eligibility Requirements</h3>
                <p>Prepare and submit the following documents for accreditation:</p>
                <ul class="requirements-list">
                    <li><i class="fas fa-check-circle"></i> SEC/DTE Registration Certificate</li>
                    <li><i class="fas fa-check-circle"></i> Mayor's/Business Permit</li>
                    <li><i class="fas fa-check-circle"></i> Tax Clearance Certificate</li>
                    <li><i class="fas fa-check-circle"></i> PhilGEPS Registration Number</li>
                    <li><i class="fas fa-check-circle"></i> Financial Statements (last 3 years)</li>
                    <li><i class="fas fa-check-circle"></i> List of completed similar projects</li>
                    <li><i class="fas fa-check-circle"></i> Technical specifications and certifications</li>
                </ul>
            </div>

            <!-- Step 3 -->
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Monitor Bidding Opportunities</h3>
                <p>Regularly check the Public Bidding section for available projects. Subscribe to notifications to receive updates on new bidding opportunities that match your expertise.</p>
                <a href="index.php" class="btn btn-electric mt-2">
                    <i class="fas fa-search"></i> View Public Bidding
                </a>
            </div>

            <!-- Step 4 -->
            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Purchase Bidding Documents</h3>
                <p>Once a relevant project is announced, purchase the bidding documents which contain detailed specifications, terms of reference, and submission requirements. Document fees are non-refundable.</p>
            </div>

            <!-- Step 5 -->
            <div class="step-card">
                <div class="step-number">5</div>
                <h3>Prepare Your Bid Proposal</h3>
                <p>Prepare a comprehensive bid proposal that includes:</p>
                <ul class="requirements-list">
                    <li><i class="fas fa-file-alt"></i> Technical Proposal</li>
                    <li><i class="fas fa-calculator"></i> Financial Proposal (Bill of Quantities)</li>
                    <li><i class="fas fa-shield-alt"></i> Bid Security (Bank Guarantee or Surety Bond)</li>
                    <li><i class="fas fa-certificate"></i> Omnibus Sworn Statement</li>
                    <li><i class="fas fa-chart-line"></i> Price Schedule</li>
                </ul>
            </div>

            <!-- Step 6 -->
            <div class="step-card">
                <div class="step-number">6</div>
                <h3>Submit Your Bid</h3>
                <p>Submit your bid proposal before the deadline. Bids can be submitted:</p>
                <ul>
                    <li><strong>Online:</strong> Through the FIBECO Bidding Portal (for sealed bidding)</li>
                    <li><strong>Physical:</strong> To the BAC Secretariat office during office hours</li>
                </ul>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Late submissions will not be accepted under any circumstances.
                </div>
            </div>

            <!-- Step 7 -->
            <div class="step-card">
                <div class="step-number">7</div>
                <h3>Bid Opening and Evaluation</h3>
                <p>Bids will be opened publicly in the presence of bidders or their representatives. The BAC will evaluate bids based on:</p>
                <ul>
                    <li><strong>Technical Compliance:</strong> Meeting all technical specifications</li>
                    <li><strong>Financial Capability:</strong> Ability to deliver the project</li>
                    <li><strong>Price Reasonability:</strong> Most advantageous bid to FIBECO</li>
                    <li><strong>Past Performance:</strong> Track record and reliability</li>
                </ul>
            </div>

            <!-- Step 8 -->
            <div class="step-card">
                <div class="step-number">8</div>
                <h3>Post-Qualification and Award</h3>
                <p>The Lowest Calculated and Responsive Bid (LCRB) will undergo post-qualification. If successful, the Notice of Award will be issued and contract signing will follow.</p>
            </div>

            <!-- Important Notes -->
            <h2 class="mt-4">Important Reminders</h2>
            <ul>
                <li>All bids must be valid for at least 120 days from the deadline of submission</li>
                <li>Collusion among bidders will result in automatic disqualification</li>
                <li>FIBECO reserves the right to reject any or all bids</li>
                <li>Blacklisting rules apply for failed or unsatisfactory performance</li>
                <li>For sealed bidding, information is confidential and only accessible to registered users</li>
            </ul>

            <!-- Contact for Assistance -->
            <div class="text-center mt-5 p-4" style="background: #f0f7ff; border-radius: 15px;">
                <i class="fas fa-headset fa-2x mb-3" style="color: var(--electric-glow);"></i>
                <h4>Need Assistance?</h4>
                <p>Contact the BAC Secretariat for any questions about the bidding process.</p>
                <a href="contact.php" class="btn btn-electric">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>