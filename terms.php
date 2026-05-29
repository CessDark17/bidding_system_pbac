<?php
// terms.php - Terms of Use Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Terms of Use - FIBECO Bidding System';
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
                <i class="fas fa-file-contract fa-3x mb-3"></i>
                <h1 class="hero-title">Terms of Use</h1>
                <p class="hero-subtitle">Please read these terms carefully before using our services</p>
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

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using the FIBECO Bidding System website ("the Website"), you agree to be bound by these Terms of Use and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>

            <h2>2. Description of Services</h2>
            <p>FIBECO provides an online bidding platform that allows registered users to:</p>
            <ul>
                <li>View public bidding opportunities</li>
                <li>Access sealed bidding information (for authorized users)</li>
                <li>Submit bids for procurement projects</li>
                <li>Track bidding status and results</li>
                <li>Access procurement-related documents</li>
            </ul>

            <h2>3. User Registration and Account Security</h2>
            <p>To access certain features of the Website, you must register for an account. You agree to:</p>
            <ul>
                <li>Provide accurate, current, and complete information during registration</li>
                <li>Maintain and promptly update your account information</li>
                <li>Maintain the security of your password and accept all risks of unauthorized access</li>
                <li>Notify us immediately of any unauthorized use of your account</li>
                <li>Be fully responsible for all activities that occur under your account</li>
            </ul>

            <h2>4. User Conduct and Obligations</h2>
            <p>You agree not to use the Website to:</p>
            <ul>
                <li>Violate any applicable local, national, or international law or regulation</li>
                <li>Transmit any unsolicited or unauthorized advertising or promotional material</li>
                <li>Impersonate any person or entity or falsely state or otherwise misrepresent your affiliation with a person or entity</li>
                <li>Interfere with or disrupt the operation of the Website or servers</li>
                <li>Attempt to gain unauthorized access to any portion of the Website</li>
                <li>Use any robot, spider, or other automatic device to monitor or copy our web pages</li>
            </ul>

            <h2>5. Bidding Rules and Procedures</h2>
            <p>All bids submitted through the FIBECO Bidding System must comply with the following:</p>
            <ul>
                <li>Bids must be submitted before the specified deadline</li>
                <li>All information provided in bids must be accurate and complete</li>
                <li>Bidders must meet all eligibility requirements</li>
                <li>FIBECO reserves the right to reject any non-compliant bid</li>
                <li>All bids become the property of FIBECO upon submission</li>
                <li>Confidential bidding information must not be disclosed to third parties</li>
            </ul>

            <h2>6. Intellectual Property Rights</h2>
            <p>The Website and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio, and the design, selection, and arrangement thereof) are owned by FIBECO, its licensors, or other providers of such material and are protected by Philippine and international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>

            <h2>7. Disclaimer of Warranties</h2>
            <p>The Website is provided on an "as is" and "as available" basis. FIBECO makes no representations or warranties of any kind, express or implied, regarding the operation or availability of the Website, or the information, content, materials, or products included on the Website. You expressly agree that your use of the Website is at your sole risk.</p>

            <h2>8. Limitation of Liability</h2>
            <p>To the fullest extent permitted by applicable law, FIBECO shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses, resulting from:</p>
            <ul>
                <li>Your use or inability to use the Website</li>
                <li>Any conduct or content of any third party on the Website</li>
                <li>Any content obtained from the Website</li>
                <li>Unauthorized access, use, or alteration of your transmissions or content</li>
            </ul>

            <h2>9. Indemnification</h2>
            <p>You agree to defend, indemnify, and hold harmless FIBECO, its affiliates, licensors, and service providers, and its and their respective officers, directors, employees, contractors, agents, licensors, suppliers, successors, and assigns from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising out of or relating to your violation of these Terms of Use or your use of the Website.</p>

            <h2>10. Termination</h2>
            <p>We may terminate or suspend your account and bar access to the Website immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever, including without limitation a breach of the Terms. If you wish to terminate your account, you may simply discontinue using the Website.</p>

            <h2>11. Governing Law</h2>
            <p>These Terms shall be governed and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions. Any legal suit, action, or proceeding arising out of, or related to, these Terms of Use or the Website shall be instituted exclusively in the courts of Bukidnon, Philippines.</p>

            <h2>12. Changes to Terms</h2>
            <p>FIBECO reserves the right, at its sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>

            <h2>13. Severability</h2>
            <p>If any provision of these Terms is held to be unenforceable or invalid, such provision will be changed and interpreted to accomplish the objectives of such provision to the greatest extent possible under applicable law, and the remaining provisions will continue in full force and effect.</p>

            <h2>14. Entire Agreement</h2>
            <p>These Terms constitute the entire agreement between you and FIBECO regarding the use of the Website, superseding any prior agreements between you and FIBECO relating to your use of the Website.</p>

            <h2>15. Contact Information</h2>
            <p>If you have any questions about these Terms, please contact us at:</p>
            <p>
                <strong>FIBECO - Legal Department</strong><br>
                Email: legal@fibeco.ph<br>
                Phone: (088) 123-4567<br>
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