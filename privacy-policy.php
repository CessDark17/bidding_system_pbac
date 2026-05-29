<?php
// privacy-policy.php - Privacy Policy Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Privacy Policy - FIBECO Bidding System';
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
        
        .content-card ul, .content-card ol {
            margin-bottom: 20px;
            padding-left: 20px;
        }
        
        .content-card li {
            margin-bottom: 8px;
            color: #555;
        }
        
        .effective-date {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--electric-accent);
            margin-bottom: 30px;
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
                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                <h1 class="hero-title">Privacy Policy</h1>
                <p class="hero-subtitle">Your privacy matters to us</p>
            </div>
        </div>

        <!-- Content -->
        <div class="content-card">
            <div class="effective-date">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                <strong>Effective Date:</strong> January 1, 2024
                <br>
                <i class="fas fa-sync-alt me-2 text-primary mt-2"></i>
                <strong>Last Updated:</strong> <?php echo date('F d, Y'); ?>
            </div>

            <h2>Introduction</h2>
            <p>FIRST BUKIDNON ELECTRIC COOPERATIVE, INC. ("FIBECO", "we", "our", or "us") respects your privacy and is committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our website and tell you about your privacy rights and how the law protects you.</p>

            <h2>Information We Collect</h2>
            <p>We may collect, use, store and transfer different kinds of personal data about you, including:</p>
            <ul>
                <li><strong>Identity Data:</strong> includes first name, last name, username or similar identifier.</li>
                <li><strong>Contact Data:</strong> includes billing address, delivery address, email address and telephone numbers.</li>
                <li><strong>Technical Data:</strong> includes internet protocol (IP) address, browser type and version, time zone setting and location, browser plug-in types and versions, operating system and platform, and other technology on the devices you use to access this website.</li>
                <li><strong>Profile Data:</strong> includes your username and password, purchases or orders made by you, your interests, preferences, feedback and survey responses.</li>
                <li><strong>Usage Data:</strong> includes information about how you use our website, products and services.</li>
                <li><strong>Marketing and Communications Data:</strong> includes your preferences in receiving marketing from us and our third parties and your communication preferences.</li>
            </ul>

            <h2>How We Collect Your Data</h2>
            <p>We use different methods to collect data from and about you including through:</p>
            <ul>
                <li><strong>Direct interactions.</strong> You may give us your identity, contact and financial data by filling in forms or by corresponding with us by post, phone, email or otherwise.</li>
                <li><strong>Automated technologies or interactions.</strong> As you interact with our website, we may automatically collect technical data about your equipment, browsing actions and patterns.</li>
                <li><strong>Third parties or publicly available sources.</strong> We may receive personal data about you from various third parties as set out below.</li>
            </ul>

            <h2>How We Use Your Information</h2>
            <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
            <ul>
                <li>To register you as a new supplier or bidder</li>
                <li>To process and manage your bidding participation</li>
                <li>To manage our relationship with you</li>
                <li>To administer and protect our business and this website</li>
                <li>To deliver relevant website content and advertisements to you</li>
                <li>To use data analytics to improve our website, products/services, marketing, customer relationships and experiences</li>
                <li>To make suggestions and recommendations to you about goods or services that may be of interest to you</li>
            </ul>

            <h2>Disclosure of Your Personal Data</h2>
            <p>We may share your personal data with the parties set out below for the purposes set out above:</p>
            <ul>
                <li>Internal third parties within FIBECO</li>
                <li>External third parties such as service providers, professional advisers</li>
                <li>Regulators and other authorities who require reporting of processing activities in certain circumstances</li>
                <li>Third parties to whom we may choose to sell, transfer, or merge parts of our business or our assets</li>
            </ul>

            <h2>Data Security</h2>
            <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed. In addition, we limit access to your personal data to those employees, agents, contractors and other third parties who have a business need to know. They will only process your personal data on our instructions and they are subject to a duty of confidentiality.</p>

            <h2>Data Retention</h2>
            <p>We will only retain your personal data for as long as reasonably necessary to fulfill the purposes we collected it for, including for the purposes of satisfying any legal, regulatory, tax, accounting or reporting requirements. We may retain your personal data for a longer period in the event of a complaint or if we reasonably believe there is a prospect of litigation in respect to our relationship with you.</p>

            <h2>Your Legal Rights</h2>
            <p>Under certain circumstances, you have rights under data protection laws in relation to your personal data, including the right to:</p>
            <ul>
                <li><strong>Request access</strong> to your personal data.</li>
                <li><strong>Request correction</strong> of your personal data.</li>
                <li><strong>Request erasure</strong> of your personal data.</li>
                <li><strong>Object to processing</strong> of your personal data.</li>
                <li><strong>Request restriction of processing</strong> your personal data.</li>
                <li><strong>Request transfer</strong> of your personal data.</li>
                <li><strong>Right to withdraw consent.</strong></li>
            </ul>

            <h2>Cookies</h2>
            <p>Our website uses cookies to distinguish you from other users of our website. This helps us to provide you with a good experience when you browse our website and also allows us to improve our site. For detailed information on the cookies we use and the purposes for which we use them, please refer to our Cookie Policy.</p>

            <h2>Changes to This Privacy Policy</h2>
            <p>We may update our privacy policy from time to time. We will notify you of any changes by posting the new privacy policy on this page and updating the "Last Updated" date at the top of this privacy policy. You are advised to review this privacy policy periodically for any changes.</p>

            <h2>Contact Us</h2>
            <p>If you have any questions about this privacy policy or our privacy practices, please contact us at:</p>
            <p>
                <strong>FIBECO - Data Protection Officer</strong><br>
                Email: dpo@fibeco.ph<br>
                Phone: +63 0999 999 9999<br>
                Address: Anahawon, Maramag, Bukidnon, Philippines 8714
            </p>

            <div class="text-center mt-5">
                <a href="index.php" class="btn btn-electric">
                    <i class="fas fa-arrow-left me-2"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>