<?php
/**
 * Fix and Import CSV with Mapped Columns
 * This script will map your Excel columns to the database columns
 */
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';

$error = '';
$success = '';
$imported = 0;
$skipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['data_file'])) {
    $file = $_FILES['data_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed.';
    } else {
        // Clear existing data if requested
        if (isset($_POST['clear_existing'])) {
            $db->exec("TRUNCATE TABLE sealed_bidding");
        }
        
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            // Get headers
            $headers = fgetcsv($handle);
            
            // Map your Excel columns to database columns
            $column_map = [
                'Bidding Date' => 'bidding_date',
                'Project Title' => 'project_title',
                'Fund Source' => 'fund_source',
                'Winning Bidder' => 'winning_bidder',
                'Winning Bid Amount' => 'winning_bid_amount'
            ];
            
            // Find which columns exist in your file
            $db_columns = [];
            foreach ($headers as $header) {
                $header_clean = trim($header);
                if (isset($column_map[$header_clean])) {
                    $db_columns[$column_map[$header_clean]] = array_search($header_clean, $headers);
                }
            }
            
            // Check if required columns are present
            $required = ['bidding_date', 'project_title', 'fund_source'];
            $missing = [];
            foreach ($required as $req) {
                if (!isset($db_columns[$req])) {
                    $missing[] = $req;
                }
            }
            
            if (!empty($missing)) {
                $error = "Missing required columns: " . implode(', ', $missing);
            } else {
                // Process each row
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        $skipped++;
                        continue;
                    }
                    
                    try {
                        // Get values using mapped columns
                        $bidding_date = !empty($data[$db_columns['bidding_date']]) ? 
                            date('Y-m-d', strtotime(str_replace('/', '-', $data[$db_columns['bidding_date']]))) : null;
                        
                        $project_title = $data[$db_columns['project_title']] ?? '';
                        $fund_source = $data[$db_columns['fund_source']] ?? '';
                        $winning_bidder = isset($db_columns['winning_bidder']) ? ($data[$db_columns['winning_bidder']] ?? '') : '';
                        
                        // Clean winning amount (remove commas and convert to number)
                        $winning_amount = 0;
                        if (isset($db_columns['winning_bid_amount']) && !empty($data[$db_columns['winning_bid_amount']])) {
                            $amount_str = str_replace(',', '', $data[$db_columns['winning_bid_amount']]);
                            $winning_amount = floatval($amount_str);
                        }
                        
                        // Set status based on whether there's a winning bidder or not
                        $status = !empty($winning_bidder) ? 'awarded' : 'pending';
                        
                        $sql = "INSERT INTO sealed_bidding 
                                (bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $bidding_date,
                            substr($project_title, 0, 500),
                            $fund_source,
                            $winning_bidder,
                            $winning_amount,
                            $status
                        ]);
                        $imported++;
                        
                    } catch (PDOException $e) {
                        $skipped++;
                        continue;
                    }
                }
                fclose($handle);
                $success = "Successfully imported {$imported} records! Skipped: {$skipped}";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix and Import CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --electric-blue: #0a2a4a;
            --electric-accent: #00d4ff;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #0a2a4a, #061a30);
            color: white;
            border-radius: 20px 20px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a6dd4, #00d4ff);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
        }
        .mapping-table {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center py-4">
                        <i class="fas fa-file-import fa-3x mb-2"></i>
                        <h3>Fix and Import Sealed Bidding CSV</h3>
                        <p class="mb-0">Automatically maps your Excel columns to database format</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Column Mapping:</strong>
                            <table class="table table-sm mt-2 mb-0">
                                <tr>
                                    <th>Your Excel Column</th>
                                    <th>→</th>
                                    <th>Database Column</th>
                                </tr>
                                <tr><td>Bidding Date</td><td>→</td><td>bidding_date</td></tr>
                                <tr><td>Project Title</td><td>→</td><td>project_title</td></tr>
                                <tr><td>Fund Source</td><td>→</td><td>fund_source</td></tr>
                                <tr><td>Winning Bidder</td><td>→</td><td>winning_bidder</td></tr>
                                <tr><td>Winning Bid Amount</td><td>→</td><td>winning_bid_amount</td></tr>
                                <tr><td>Status</td><td>→</td><td>Auto-set to 'awarded' if winning bidder exists</td></tr>
                            </table>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="clear_existing" class="form-check-input" id="clear_existing">
                                    <label class="form-check-label" for="clear_existing">
                                        <i class="fas fa-trash-alt me-1 text-danger"></i>
                                        Clear existing sealed bidding data before import
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Choose Your Sealed Bidding CSV File</label>
                                <input type="file" name="data_file" class="form-control" accept=".csv" required>
                                <small class="text-muted">Your file with columns: Bidding Date, Project Title, Fund Source, Winning Bidder, Winning Bid Amount</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-upload me-2"></i> Import Data
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <h5><i class="fas fa-file-alt me-2"></i>Sample of how your data will be imported:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Original (Your CSV)</th>
                                        <th>After Import (Database)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>12/11/2024<br>Vacuum Circuit Recloser<br>General Fund<br>Centrade Integrated Sales Corp<br>1,300,000.00</td>
                                        <td>2024-12-11<br>Vacuum Circuit Recloser<br>General Fund<br>Centrade Integrated Sales Corp<br>1,300,000.00<br>status: awarded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> The script will:
                            <ul class="mb-0 mt-2">
                                <li>Convert dates from MM/DD/YYYY to YYYY-MM-DD format</li>
                                <li>Remove commas from numbers (1,300,000 → 1300000)</li>
                                <li>Auto-set status to 'awarded' if winning bidder exists</li>
                                <li>Skip the "Participating Bidders" and "Contract Reference" columns</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>