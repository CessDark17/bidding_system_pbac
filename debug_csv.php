<?php
/**
 * Debug CSV Import Issues
 * Run this script to see what's wrong with your CSV file
 */
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';

$debug_info = '';
$csv_preview = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['debug_file'])) {
    $file = $_FILES['debug_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Error code: ' . $file['error'];
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension != 'csv') {
            $error = 'Please upload a CSV file.';
        } else {
            // Read the CSV file
            $csv_content = file_get_contents($file['tmp_name']);
            $lines = explode("\n", $csv_content);
            
            // Get headers
            $headers = str_getcsv(array_shift($lines));
            
            $debug_info = "<h5>File Analysis:</h5>";
            $debug_info .= "<ul>";
            $debug_info .= "<li><strong>File Name:</strong> " . htmlspecialchars($file['name']) . "</li>";
            $debug_info .= "<li><strong>File Size:</strong> " . round($file['size'] / 1024, 2) . " KB</li>";
            $debug_info .= "<li><strong>Total Rows:</strong> " . count($lines) . "</li>";
            $debug_info .= "<li><strong>Headers Found:</strong> " . count($headers) . "</li>";
            $debug_info .= "</ul>";
            
            // Check for ID column
            $debug_info .= "<h5>Headers:</h5>";
            $debug_info .= "<div style='background:#f5f5f5; padding:10px; border-radius:5px; margin-bottom:15px;'>";
            foreach ($headers as $i => $header) {
                $header_clean = trim($header);
                $warning = '';
                if (strtolower($header_clean) == 'id') {
                    $warning = " <span style='color:red;'>⚠️ WARNING: Remove 'id' column from CSV!</span>";
                }
                $debug_info .= "<code>" . htmlspecialchars($header_clean) . "</code>" . $warning . "<br>";
            }
            $debug_info .= "</div>";
            
            // Check for required headers based on selection
            $table_type = $_POST['table_type'] ?? '';
            $required_headers = [];
            
            switch ($table_type) {
                case 'sealed_bidding':
                    $required_headers = ['bidding_date', 'project_title', 'fund_source', 'winning_bidder', 'winning_bid_amount', 'status'];
                    break;
                case 'public_bidding':
                    $required_headers = ['bidding_date', 'project_title', 'fund_source', 'approved_budget_contract', 'status'];
                    break;
                case 'procurement_monitoring':
                    $required_headers = ['submission_date', 'project_title', 'supplier_name', 'contract_amount', 'status'];
                    break;
            }
            
            $debug_info .= "<h5>Required Headers for {$table_type}:</h5>";
            $debug_info .= "<div style='background:#e8f4fd; padding:10px; border-radius:5px; margin-bottom:15px;'>";
            $headers_clean = array_map('trim', $headers);
            $missing = [];
            foreach ($required_headers as $required) {
                if (in_array($required, $headers_clean)) {
                    $debug_info .= "<span style='color:green;'>✓</span> " . htmlspecialchars($required) . "<br>";
                } else {
                    $debug_info .= "<span style='color:red;'>✗</span> " . htmlspecialchars($required) . " <span style='color:red;'>(MISSING!)</span><br>";
                    $missing[] = $required;
                }
            }
            $debug_info .= "</div>";
            
            if (!empty($missing)) {
                $debug_info .= "<div class='alert alert-danger'>Missing required columns: " . implode(', ', $missing) . "</div>";
            }
            
            // Preview first 3 rows of data
            $debug_info .= "<h5>Data Preview (First 3 rows):</h5>";
            $debug_info .= "<div style='background:#f5f5f5; padding:10px; border-radius:5px; overflow-x:auto;'>";
            $debug_info .= "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            $debug_info .= "<tr style='background:#ddd;'>";
            foreach ($headers as $header) {
                $debug_info .= "<th>" . htmlspecialchars(trim($header)) . "</th>";
            }
            $debug_info .= "</tr>";
            
            $row_count = 0;
            foreach ($lines as $line) {
                if (trim($line) == '') continue;
                $row = str_getcsv($line);
                $debug_info .= "<tr>";
                foreach ($row as $cell) {
                    $debug_info .= "<td>" . htmlspecialchars($cell) . "</td>";
                }
                $debug_info .= "</tr>";
                $row_count++;
                if ($row_count >= 3) break;
            }
            $debug_info .= "</table>";
            $debug_info .= "</div>";
            
            // Check for ID column values
            $id_index = array_search('id', array_map('strtolower', $headers));
            if ($id_index !== false) {
                $debug_info .= "<div class='alert alert-danger mt-3'>";
                $debug_info .= "<strong>❌ ID Column Detected!</strong><br>";
                $debug_info .= "Your CSV file has an 'id' column. This causes duplicate key errors.<br>";
                $debug_info .= "<strong>Solution:</strong> Remove the 'id' column from your CSV file and try again.";
                $debug_info .= "</div>";
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
    <title>Debug CSV Import</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #0a2a4a, #061a30); color: white; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bug"></i> CSV Import Debug Tool</h3>
                <p>Upload your CSV file to see what's wrong</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select Table Type</label>
                        <select name="table_type" class="form-select" required>
                            <option value="">-- Select Table --</option>
                            <option value="sealed_bidding">Sealed Bidding</option>
                            <option value="public_bidding">Public Bidding</option>
                            <option value="procurement_monitoring">Procurement Monitoring</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Upload CSV File to Debug</label>
                        <input type="file" name="debug_file" class="form-control" accept=".csv" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Analyze CSV File</button>
                </form>
                
                <?php if ($debug_info): ?>
                    <hr>
                    <?php echo $debug_info; ?>
                    
                    <div class="alert alert-info mt-3">
                        <strong>💡 Solutions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Remove any <strong>'id' column</strong> from your CSV file</li>
                            <li>Make sure column headers <strong>exactly match</strong> the required format</li>
                            <li>Check that dates are in <strong>YYYY-MM-DD format</strong> (e.g., 2024-01-15)</li>
                            <li>Ensure numeric fields don't have currency symbols or commas</li>
                            <li>Save your CSV with <strong>UTF-8 encoding</strong></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-question-circle"></i> How to Create a Proper CSV File</h4>
            </div>
            <div class="card-body">
                <h5>Step 1: Remove the ID column</h5>
                <p>Open your Excel file and <strong>delete the 'id' column</strong> if it exists. The database will auto-generate IDs.</p>
                
                <h5>Step 2: Check your column headers</h5>
                <p>Your first row must have EXACTLY these headers:</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <strong>Public Bidding:</strong><br>
                        <code>bidding_date,project_title,fund_source,approved_budget_contract,status</code>
                    </div>
                    <div class="col-md-4">
                        <strong>Sealed Bidding:</strong><br>
                        <code>bidding_date,project_title,fund_source,winning_bidder,winning_bid_amount,status</code>
                    </div>
                    <div class="col-md-4">
                        <strong>Procurement Monitoring:</strong><br>
                        <code>submission_date,project_title,supplier_name,contract_amount,status</code>
                    </div>
                </div>
                
                <h5 class="mt-3">Step 3: Save as CSV correctly</h5>
                <ol>
                    <li>In Excel, go to <strong>File → Save As</strong></li>
                    <li>Choose <strong>CSV UTF-8 (Comma delimited) (*.csv)</strong> if available</li>
                    <li>Or choose <strong>CSV (Comma delimited) (*.csv)</strong></li>
                    <li>Click <strong>Save</strong></li>
                    <li>If prompted about compatibility, click <strong>Yes</strong></li>
                </ol>
                
                <h5>Step 4: Sample correct CSV content</h5>
                <pre style="background:#f5f5f5; padding:10px; border-radius:5px;">
bidding_date,project_title,fund_source,approved_budget_contract,status
2024-01-15,Supply of Distribution Transformers,Cooperative Fund,2500000.00,active
2024-02-10,Rehabilitation of Substation No. 2,Development Fund,5000000.00,ongoing
2024-03-05,Procurement of Service Vehicles,General Fund,3200000.00,completed</pre>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>