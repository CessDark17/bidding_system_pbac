<?php
/**
 * Data Import Page
 * Upload Excel/CSV files to import data into database tables
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Import Data';
$error = '';
$success = '';
$userRole = $_SESSION['user_role'] ?? 'admin';
$userName = $_SESSION['user_name'] ?? 'Admin';
$isAdmin = true;

// Create upload directories if they don't exist
function createUploadDirectories() {
    $directories = [
        'uploads/',
        'uploads/imports/',
        'uploads/temp/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['data_file'])) {
    $table_type = $_POST['table_type'] ?? '';
    $file = $_FILES['data_file'];
    $clear_existing = isset($_POST['clear_existing']) ? true : false;
    
    // Create directories first
    createUploadDirectories();
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Error code: ' . $file['error'];
    } elseif (!in_array($table_type, ['sealed_bidding', 'public_bidding', 'procurement_monitoring'])) {
        $error = 'Invalid table selection.';
    } else {
        // Clear existing data if requested
        if ($clear_existing) {
            try {
                $db->exec("TRUNCATE TABLE $table_type");
                $success .= "Existing data cleared. ";
            } catch (PDOException $e) {
                // Table might be empty or doesn't exist
            }
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $table_type . '_' . date('Ymd_His') . '.' . $extension;
        $filepath = 'uploads/imports/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Process file based on extension
            if ($extension == 'csv') {
                $result = processCSV($filepath, $table_type);
            } elseif (in_array($extension, ['xls', 'xlsx'])) {
                $result = processExcel($filepath, $table_type);
            } else {
                $error = 'Unsupported file format. Please upload CSV or Excel (.xlsx) files.';
            }
            
            if (isset($result) && $result['success']) {
                $success .= "Successfully imported {$result['count']} records to " . str_replace('_', ' ', ucfirst($table_type)) . "!";
            } elseif (isset($result) && !$result['success']) {
                $error = $result['error'];
            }
        } else {
            $error = 'Failed to save uploaded file. Please check directory permissions.';
        }
    }
}

function processCSV($filepath, $table_type) {
    global $db;
    $count = 0;
    $errors = [];
    $skipped = 0;
    
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'File not found.'];
    }
    
    if (($handle = fopen($filepath, "r")) !== FALSE) {
        // Get headers and remove any 'id' column if present
        $headers = fgetcsv($handle);
        $headers = array_filter($headers, function($header) {
            return strtolower(trim($header)) !== 'id';
        });
        $headers = array_values($headers);
        
        $rowNumber = 1;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                $skipped++;
                continue;
            }
            
            // Ensure data array matches headers length
            while (count($data) < count($headers)) {
                $data[] = null;
            }
            
            $row = array_combine($headers, $data);
            
            // Skip if required fields are empty
            if (empty($row['project_title'] ?? '')) {
                $skipped++;
                continue;
            }
            
            try {
                switch ($table_type) {
                    case 'sealed_bidding':
                        $sql = "INSERT INTO sealed_bidding (bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($row['bidding_date']) ? date('Y-m-d', strtotime($row['bidding_date'])) : null,
                            substr($row['project_title'] ?? '', 0, 500),
                            $row['fund_source'] ?? '',
                            $row['winning_bidder'] ?? '',
                            floatval($row['winning_bid_amount'] ?? 0),
                            strtolower($row['status'] ?? 'pending')
                        ]);
                        break;
                        
                    case 'public_bidding':
                        $sql = "INSERT INTO public_bidding (bidding_date, project_title, fund_source, approved_budget_contract, status) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($row['bidding_date']) ? date('Y-m-d', strtotime($row['bidding_date'])) : null,
                            substr($row['project_title'] ?? '', 0, 500),
                            $row['fund_source'] ?? '',
                            floatval($row['approved_budget_contract'] ?? 0),
                            strtolower($row['status'] ?? 'active')
                        ]);
                        break;
                        
                    case 'procurement_monitoring':
                        $sql = "INSERT INTO procurement_monitoring (submission_date, project_title, supplier_name, contract_amount, status) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($row['submission_date']) ? date('Y-m-d', strtotime($row['submission_date'])) : null,
                            substr($row['project_title'] ?? '', 0, 500),
                            $row['supplier_name'] ?? '',
                            floatval($row['contract_amount'] ?? 0),
                            strtolower($row['status'] ?? 'ongoing')
                        ]);
                        break;
                }
                $count++;
            } catch (PDOException $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
                continue;
            }
        }
        fclose($handle);
    }
    
    $message = "Imported: $count records";
    if ($skipped > 0) {
        $message .= ", Skipped: $skipped rows";
    }
    
    if ($count > 0) {
        return ['success' => true, 'count' => $count, 'message' => $message];
    } else {
        $errorMsg = 'No valid records found. ' . implode('; ', array_slice($errors, 0, 5));
        if (count($errors) > 5) {
            $errorMsg .= '... and ' . (count($errors) - 5) . ' more errors';
        }
        return ['success' => false, 'error' => $errorMsg];
    }
}

function processExcel($filepath, $table_type) {
    global $db;
    $count = 0;
    $errors = [];
    $skipped = 0;
    
    // Check if PhpSpreadsheet is installed
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        return ['success' => false, 'error' => 'Excel processing requires PhpSpreadsheet library. Please install via composer or use CSV format.'];
    }
    
    try {
        require_once 'vendor/autoload.php'; // For Composer autoload
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if (empty($rows)) {
            return ['success' => false, 'error' => 'Excel file is empty.'];
        }
        
        // Get headers and remove any 'id' column
        $headers = array_shift($rows);
        $headers = array_filter($headers, function($header) {
            return strtolower(trim($header)) !== 'id';
        });
        $headers = array_values($headers);
        
        $rowNumber = 1;
        
        foreach ($rows as $row) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                $skipped++;
                continue;
            }
            
            // Ensure data array matches headers length
            while (count($row) < count($headers)) {
                $row[] = null;
            }
            
            $rowData = array_combine($headers, $row);
            
            // Skip if required fields are empty
            if (empty($rowData['project_title'] ?? '')) {
                $skipped++;
                continue;
            }
            
            try {
                switch ($table_type) {
                    case 'sealed_bidding':
                        $sql = "INSERT INTO sealed_bidding (bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($rowData['bidding_date']) ? date('Y-m-d', strtotime($rowData['bidding_date'])) : null,
                            substr($rowData['project_title'] ?? '', 0, 500),
                            $rowData['fund_source'] ?? '',
                            $rowData['winning_bidder'] ?? '',
                            floatval($rowData['winning_bid_amount'] ?? 0),
                            strtolower($rowData['status'] ?? 'pending')
                        ]);
                        break;
                        
                    case 'public_bidding':
                        $sql = "INSERT INTO public_bidding (bidding_date, project_title, fund_source, approved_budget_contract, status) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($rowData['bidding_date']) ? date('Y-m-d', strtotime($rowData['bidding_date'])) : null,
                            substr($rowData['project_title'] ?? '', 0, 500),
                            $rowData['fund_source'] ?? '',
                            floatval($rowData['approved_budget_contract'] ?? 0),
                            strtolower($rowData['status'] ?? 'active')
                        ]);
                        break;
                        
                    case 'procurement_monitoring':
                        $sql = "INSERT INTO procurement_monitoring (submission_date, project_title, supplier_name, contract_amount, status) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            !empty($rowData['submission_date']) ? date('Y-m-d', strtotime($rowData['submission_date'])) : null,
                            substr($rowData['project_title'] ?? '', 0, 500),
                            $rowData['supplier_name'] ?? '',
                            floatval($rowData['contract_amount'] ?? 0),
                            strtolower($rowData['status'] ?? 'ongoing')
                        ]);
                        break;
                }
                $count++;
            } catch (PDOException $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
                continue;
            }
        }
        
        $message = "Imported: $count records";
        if ($skipped > 0) {
            $message .= ", Skipped: $skipped rows";
        }
        
        if ($count > 0) {
            return ['success' => true, 'count' => $count, 'message' => $message];
        } else {
            $errorMsg = 'No valid records found. ' . implode('; ', array_slice($errors, 0, 5));
            return ['success' => false, 'error' => $errorMsg];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Excel processing error: ' . $e->getMessage()];
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
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(255, 215, 0, 0.5); }
            50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(255, 215, 0, 0.8); }
        }
        
        .brand-text {
            background: linear-gradient(135deg, #ffffff, var(--electric-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .import-header {
            background: linear-gradient(135deg, var(--electric-blue) 0%, #0a1a3a 100%);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .import-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .import-header::after {
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
        
        .import-content {
            position: relative;
            z-index: 2;
        }
        
        .upload-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .upload-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .upload-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--electric-glow), var(--electric-accent));
            transition: left 0.5s ease;
        }
        
        .upload-card:hover::before {
            left: 0;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--electric-blue), var(--electric-dark));
            color: white;
            padding: 20px;
            border-bottom: none;
        }
        
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
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .upload-card {
                margin-bottom: 20px;
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
        
        .form-check-input:checked {
            background-color: var(--electric-glow);
            border-color: var(--electric-glow);
        }
        
        .file-types {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        
        .file-type-badge {
            background: #f0f7ff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--electric-glow);
        }
    </style>
</head>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        <div class="import-header text-white p-4">
            <div class="import-content">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-upload fa-2x mb-2"></i>
                        <h1 class="mb-1">Data Import</h1>
                        <p class="mb-0 opacity-75">Upload CSV or Excel files to update bidding records</p>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="welcome-badge mt-3 mt-sm-0">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y'); ?></span>
                        </div>
                        <div class="role-badge">
                            <i class="fas fa-shield-alt me-1"></i>
                            Logged in as: <strong>Administrator</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="upload-card position-relative">
                    <div class="card-header-custom text-center">
                        <i class="fas fa-file-upload fa-3x mb-2"></i>
                        <h3 class="mb-0">Import Data</h3>
                        <p class="mb-0 opacity-75">Upload CSV or Excel files to update bidding records</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-table me-1 text-primary"></i> Select Table to Update
                                </label>
                                <select name="table_type" class="form-select" required>
                                    <option value="">-- Select Table --</option>
                                    <option value="sealed_bidding">Sealed Bidding</option>
                                    <option value="public_bidding">Public Bidding</option>
                                    <option value="procurement_monitoring">Procurement Monitoring</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="clear_existing" class="form-check-input" id="clear_existing">
                                    <label class="form-check-label" for="clear_existing">
                                        <i class="fas fa-trash-alt me-1 text-danger"></i>
                                        Clear existing data before import
                                    </label>
                                    <small class="text-muted d-block">Warning: This will delete all current records in the selected table.</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-file me-1 text-primary"></i> Choose File
                                </label>
                                <input type="file" name="data_file" class="form-control" accept=".csv,.xls,.xlsx" required>
                                <div class="file-types mt-2">
                                    <span class="file-type-badge"><i class="fas fa-file-csv"></i> CSV</span>
                                    <span class="file-type-badge"><i class="fas fa-file-excel"></i> Excel (.xlsx)</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i> 
                                    <strong>Do not include an 'id' column</strong> in your file.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-electric w-100">
                                <i class="fas fa-upload me-2"></i> Upload and Import
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <h5>
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Sample File Format (No ID column):
                        </h5>
                        
                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-lock me-1"></i> Sealed Bidding:</strong><br>
                            <code class="small">bidding_date,project_title,fund_source,winning_bidder,winning_bid_amount,status</code><br>
                            <code class="small">2024-01-15,Supply of Transformers,Cooperative Fund,ABC Company,2500000.00,awarded</code>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong><i class="fas fa-gavel me-1"></i> Public Bidding:</strong><br>
                            <code class="small">bidding_date,project_title,fund_source,approved_budget_contract,status</code><br>
                            <code class="small">2024-02-10,Rehabilitation Project,Development Fund,5000000.00,active</code>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong><i class="fas fa-clipboard-list me-1"></i> Procurement Monitoring:</strong><br>
                            <code class="small">submission_date,project_title,supplier_name,contract_amount,status</code><br>
                            <code class="small">2024-03-05,Line Maintenance,XYZ Corp,950000.00,ongoing</code>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important Notes:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Do NOT include an <code>id</code> column in your file</li>
                                <li>Date format should be YYYY-MM-DD (e.g., 2024-01-15)</li>
                                <li>Make sure column headers exactly match the sample above</li>
                                <li>Status values: active, ongoing, completed, awarded, pending, evaluating, delayed</li>
                                <li>For Excel files, make sure data starts from row 1 (headers)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="summary.php" class="btn btn-outline-electric me-2">
                            <i class="fas fa-chart-pie me-2"></i> View Summary
                        </a>
                        <a href="dashboard.php" class="btn btn-electric">
                            <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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