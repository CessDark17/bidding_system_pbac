<?php
// dashboard_simple.php - Simplified test version
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';
require_once 'includes/config/constants.php';
require_once 'includes/config/functions.php';

$pageTitle = 'Dashboard - Sealed Bidding';
$userRole = $_SESSION['user_role'] ?? 'user';
$userName = $_SESSION['user_name'] ?? 'User';

// Get sealed bidding records directly with simple query
$sealedRecords = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM sealed_bidding ORDER BY bidding_date DESC LIMIT 10");
        $sealedRecords = $stmt->fetchAll();
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
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gavel me-2"></i>
                <strong>FIBECO</strong> Bidding Portal
            </a>
            <div class="ms-auto">
                <span class="text-white me-3">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Sealed Bidding Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($userName); ?>! (Role: <?php echo $userRole; ?>)</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Sealed Bidding Records</h5>
            </div>
            <div class="card-body">
                <?php if (empty($sealedRecords)): ?>
                    <div class="alert alert-info">No sealed bidding records found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project Title</th>
                                    <th>Fund Source</th>
                                    <th>Winning Bidder</th>
                                    <th>Winning Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sealedRecords as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['bidding_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['project_title']); ?></td>
                                        <td><?php echo htmlspecialchars($record['fund_source']); ?></td>
                                        <td><?php echo htmlspecialchars($record['winning_bidder'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($record['winning_bid_amount'] ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>