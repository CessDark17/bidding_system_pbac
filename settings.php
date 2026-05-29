<?php
/**
 * User Settings Page
 * File: settings.php
 * 
 * Allows users to manage their account settings and preferences.
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

$pageTitle = 'Settings';
$error = '';
$success = '';
$user = [];
$userRole = $_SESSION['user_role'] ?? 'user';
$userName = $_SESSION['user_name'] ?? 'User';

// Get user data - only select columns that exist
try {
    // First check what columns exist
    $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build select query based on existing columns
    $selectFields = ['id', 'username', 'email', 'full_name', 'role', 'status'];
    
    if (in_array('notification_email', $columns)) {
        $selectFields[] = 'notification_email';
    }
    if (in_array('notification_bidding', $columns)) {
        $selectFields[] = 'notification_bidding';
    }
    if (in_array('two_factor_enabled', $columns)) {
        $selectFields[] = 'two_factor_enabled';
    }
    
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_preferences':
                // Only update if columns exist
                $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('notification_email', $columns) && in_array('notification_bidding', $columns)) {
                    $notification_email = isset($_POST['notification_email']) ? 1 : 0;
                    $notification_bidding = isset($_POST['notification_bidding']) ? 1 : 0;
                    
                    try {
                        $sql = "UPDATE users SET notification_email = ?, notification_bidding = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $result = $stmt->execute([$notification_email, $notification_bidding, $_SESSION['user_id']]);
                        
                        if ($result) {
                            $success = 'Preferences updated successfully!';
                            // Refresh user data
                            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();
                        } else {
                            $error = 'Failed to update preferences.';
                        }
                    } catch (PDOException $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Notification settings are not available. Please run database update.';
                }
                break;
                
            case 'update_security':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'Please fill in all password fields.';
                } elseif (strlen($new_password) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $userData = $stmt->fetch();
                    
                    if (password_verify($current_password, $userData['password_hash'])) {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
                            $success = 'Password changed successfully!';
                        } else {
                            $error = 'Failed to update password.';
                        }
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                }
                break;
                
            case 'toggle_2fa':
                // Check if column exists
                $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('two_factor_enabled', $columns)) {
                    $two_factor_enabled = isset($_POST['two_factor_enabled']) ? 1 : 0;
                    try {
                        $sql = "UPDATE users SET two_factor_enabled = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $result = $stmt->execute([$two_factor_enabled, $_SESSION['user_id']]);
                        
                        if ($result) {
                            $success = $two_factor_enabled ? 'Two-factor authentication enabled!' : 'Two-factor authentication disabled!';
                            // Refresh user data
                            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();
                        } else {
                            $error = 'Failed to update 2FA setting.';
                        }
                    } catch (PDOException $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Two-factor authentication is not available. Please run database update.';
                }
                break;
        }
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
        
        /* Settings Header */
        .settings-header {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .settings-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .settings-header::after {
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
        
        .settings-content {
            position: relative;
            z-index: 2;
        }
        
        /* Cards with Electric Theme */
        .settings-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .settings-card:hover::before {
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
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 10px 0 0 10px;
        }
        
        .btn-outline-secondary {
            border-radius: 0 10px 10px 0;
            border: 1px solid #e0e0e0;
            border-left: none;
        }
        
        .form-check-input:checked {
            background-color: var(--electric-glow);
            border-color: var(--electric-glow);
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
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
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
            .settings-card {
                margin-bottom: 20px;
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
        
        /* Switch Toggle */
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--electric-glow);
            border-color: var(--electric-glow);
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
        <!-- Settings Header -->
        <div class="settings-header text-white p-4">
            <div class="settings-content">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-cog fa-2x mb-2"></i>
                        <h1 class="mb-1">Account Settings</h1>
                        <p class="mb-0 opacity-75">Manage your preferences and security settings</p>
                    </div>
                    <div class="welcome-badge mt-3 mt-sm-0">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <!-- Change Password -->
                <div class="settings-card position-relative">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-key"></i>
                            Change Password
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_security">
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-lock me-1 text-primary"></i> Current Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-plus-circle me-1 text-primary"></i> New Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" name="new_password" required>
                                </div>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-check-circle me-1 text-primary"></i> Confirm New Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-electric">
                                <i class="fas fa-sync-alt me-2"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Danger Zone -->
                <div class="settings-card position-relative">
                    <div class="card-header-custom" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                        <h5>
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning!</strong> These actions cannot be undone.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="settings-card position-relative">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fas fa-info-circle"></i>
                            Account Information
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Username:</strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Role:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        <a href="profile.php" class="btn btn-outline-electric w-100">
                            <i class="fas fa-edit me-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="dashboard.php" class="btn btn-outline-electric me-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                </a>
                <a href="index.php" class="btn btn-electric">
                    <i class="fas fa-home me-2"></i> Home
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