<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/config/database.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'] ?? '';
    $data = $_POST;
    unset($data['table']);
    
    try {
        switch ($table) {
            case 'sealed_bidding':
                $sql = "INSERT INTO sealed_bidding (bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$data['bidding_date'], $data['project_title'], $data['fund_source'], 
                               $data['winning_bidder'], $data['winning_bid_amount'], $data['status']]);
                $success = "Record added to Sealed Bidding!";
                break;
                
            case 'public_bidding':
                $sql = "INSERT INTO public_bidding (bidding_date, project_title, fund_source, approved_budget_contract, status) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$data['bidding_date'], $data['project_title'], $data['fund_source'], 
                               $data['approved_budget_contract'], $data['status']]);
                $success = "Record added to Public Bidding!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4>Add New Bidding Record</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>Select Table</label>
                        <select name="table" class="form-select" required onchange="toggleForm(this.value)">
                            <option value="">-- Select --</option>
                            <option value="sealed_bidding">Sealed Bidding</option>
                            <option value="public_bidding">Public Bidding</option>
                        </select>
                    </div>
                    
                    <div id="sealed_bidding_form" style="display:none;">
                        <h5>Sealed Bidding Fields</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Bidding Date</label>
                                <input type="date" name="bidding_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Fund Source</label>
                                <input type="text" name="fund_source" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Project Title</label>
                                <input type="text" name="project_title" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Winning Bidder</label>
                                <input type="text" name="winning_bidder" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Winning Amount</label>
                                <input type="number" step="0.01" name="winning_bid_amount" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="awarded">Awarded</option>
                                    <option value="evaluating">Evaluating</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div id="public_bidding_form" style="display:none;">
                        <h5>Public Bidding Fields</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Bidding Date</label>
                                <input type="date" name="bidding_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Fund Source</label>
                                <input type="text" name="fund_source" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Project Title</label>
                                <input type="text" name="project_title" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>ABC (Approved Budget)</label>
                                <input type="number" step="0.01" name="approved_budget_contract" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Record</button>
                    <a href="summary.php" class="btn btn-secondary">View Summary</a>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function toggleForm(table) {
        document.getElementById('sealed_bidding_form').style.display = 'none';
        document.getElementById('public_bidding_form').style.display = 'none';
        
        if (table === 'sealed_bidding') {
            document.getElementById('sealed_bidding_form').style.display = 'block';
        } else if (table === 'public_bidding') {
            document.getElementById('public_bidding_form').style.display = 'block';
        }
    }
    </script>
</body>
</html>