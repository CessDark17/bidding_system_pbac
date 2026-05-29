<?php
/**
 * Procurement Monitoring Management
 * File: admin/procurement-monitoring.php
 * 
 * Track and monitor procurement activities and deliveries
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Procurement Monitoring';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM procurement_monitoring WHERE id = ?";
    $result = executeQuery($sql, [$id]);
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'DELETE_PROCUREMENT', 'procurement_monitoring', $id);
        alertRedirect('Record deleted successfully.', 'success', 'procurement-monitoring.php');
    }
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'procurement-monitoring.php');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    $data = [
        'itb_no' => sanitize($_POST['itb_no'] ?? null),
        'particulars' => sanitize($_POST['particulars']),
        'abc' => !empty($_POST['abc']) ? (float)$_POST['abc'] : null,
        'bidder_1' => sanitize($_POST['bidder_1'] ?? null),
        'bidder_1_price' => !empty($_POST['bidder_1_price']) ? (float)$_POST['bidder_1_price'] : null,
        'bidder_2' => sanitize($_POST['bidder_2'] ?? null),
        'bidder_2_price' => !empty($_POST['bidder_2_price']) ? (float)$_POST['bidder_2_price'] : null,
        'bidder_3' => sanitize($_POST['bidder_3'] ?? null),
        'bidder_3_price' => !empty($_POST['bidder_3_price']) ? (float)$_POST['bidder_3_price'] : null,
        'bidder_4' => sanitize($_POST['bidder_4'] ?? null),
        'bidder_4_price' => !empty($_POST['bidder_4_price']) ? (float)$_POST['bidder_4_price'] : null,
        'bidder_5' => sanitize($_POST['bidder_5'] ?? null),
        'bidder_5_price' => !empty($_POST['bidder_5_price']) ? (float)$_POST['bidder_5_price'] : null,
        'winning_bidder' => sanitize($_POST['winning_bidder'] ?? null),
        'winning_price' => !empty($_POST['winning_price']) ? (float)$_POST['winning_price'] : null,
        'remarks' => sanitize($_POST['remarks'] ?? null),
        'delivery_date_per_po' => !empty($_POST['delivery_date_per_po']) ? $_POST['delivery_date_per_po'] : null,
        'actual_delivery_date' => !empty($_POST['actual_delivery_date']) ? $_POST['actual_delivery_date'] : null,
        'created_by' => $_SESSION['user_id']
    ];
    
    if ($id > 0) {
        // Update
        $set = [];
        $params = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE procurement_monitoring SET " . implode(', ', $set) . " WHERE id = ?";
        $result = executeQuery($sql, $params);
        
        if ($result) {
            logActivity($_SESSION['user_id'], 'UPDATE_PROCUREMENT', 'procurement_monitoring', $id);
            alertRedirect('Record updated successfully.', 'success', 'procurement-monitoring.php');
        }
    } else {
        // Insert
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO procurement_monitoring (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $result = executeQuery($sql, array_values($data));
        
        if ($result) {
            $newId = $db->lastInsertId();
            logActivity($_SESSION['user_id'], 'CREATE_PROCUREMENT', 'procurement_monitoring', $newId);
            alertRedirect('Record created successfully.', 'success', 'procurement-monitoring.php');
        }
    }
    
    if (!$result) {
        alertRedirect('Failed to save record.', 'danger', 'procurement-monitoring.php');
    }
}

// Handle AJAX quick update for delivery dates
if (isset($_POST['ajax_update']) && $_POST['ajax_update'] == 1) {
    header('Content-Type: application/json');
    $id = (int)$_POST['id'];
    $field = sanitize($_POST['field']);
    $value = sanitize($_POST['value']);
    
    $allowed_fields = ['delivery_date_per_po', 'actual_delivery_date', 'remarks'];
    
    if (in_array($field, $allowed_fields)) {
        $value = $value ?: null;
        $sql = "UPDATE procurement_monitoring SET $field = ? WHERE id = ?";
        $result = executeQuery($sql, [$value, $id]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid field']);
    }
    exit;
}

// Pagination and filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(particulars LIKE ? OR itb_no LIKE ? OR winning_bidder LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(' AND ', $whereConditions) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM procurement_monitoring $whereClause";
$countResult = fetchOne($countSql, $params);
$totalRecords = $countResult['total'] ?? 0;
$totalPages = ceil($totalRecords / ADMIN_ITEMS_PER_PAGE);

// Get records
$sql = "SELECT * FROM procurement_monitoring 
        $whereClause 
        ORDER BY id DESC 
        LIMIT ? OFFSET ?";
$params[] = ADMIN_ITEMS_PER_PAGE;
$params[] = $offset;
$procurementList = fetchAll($sql, $params);

// Get record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $editRecord = fetchOne("SELECT * FROM procurement_monitoring WHERE id = ?", [(int)$_GET['edit']]);
}

include '../includes/templates/admin-header.php';
?>

<style>
.delivery-date-input {
    min-width: 120px;
    cursor: pointer;
}
.delivery-date-input.editable:hover {
    background-color: #fff3cd;
    cursor: pointer;
}
.inline-edit {
    display: inline-block;
}
.inline-edit .edit-value {
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}
.inline-edit .edit-value:hover {
    background-color: #e9ecef;
}
.inline-edit input, .inline-edit select {
    min-width: 150px;
}
</style>

<div class="admin-procurement">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-chart-line me-2"></i>Procurement Monitoring
        </h1>
        <div>
            <a href="?action=add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#procurementModal">
                <i class="fas fa-plus me-2"></i>Add Record
            </a>
            <a href="batch-import.php?type=procurement_monitoring" class="btn btn-success">
                <i class="fas fa-upload me-2"></i>Batch Import
            </a>
            <a href="reports.php?type=procurement" class="btn btn-info">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by ITB No., Particulars, or Winning Bidder..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="procurement-monitoring.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <?php
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total,
            COALESCE(SUM(abc), 0) as total_abc,
            COALESCE(SUM(winning_price), 0) as total_awarded,
            COUNT(CASE WHEN actual_delivery_date IS NOT NULL THEN 1 END) as delivered,
            COUNT(CASE WHEN delivery_date_per_po IS NOT NULL AND actual_delivery_date IS NULL THEN 1 END) as pending,
            COALESCE(SUM(abc), 0) - COALESCE(SUM(winning_price), 0) as savings
        FROM procurement_monitoring
    ");
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Records</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                        </div>
                        <i class="fas fa-file-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total ABC</h6>
                            <h5 class="mb-0"><?php echo formatCurrency($stats['total_abc'] ?? 0); ?></h5>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Awarded</h6>
                            <h5 class="mb-0"><?php echo formatCurrency($stats['total_awarded'] ?? 0); ?></h5>
                        </div>
                        <i class="fas fa-trophy fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Savings</h6>
                            <h5 class="mb-0"><?php echo formatCurrency($stats['savings'] ?? 0); ?></h5>
                        </div>
                        <i class="fas fa-piggy-bank fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delivery Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Delivery Status</h6>
                    <div class="progress mb-2" style="height: 30px;">
                        <?php 
                        $total = $stats['total'] > 0 ? $stats['total'] : 1;
                        $deliveredPercent = ($stats['delivered'] / $total) * 100;
                        $pendingPercent = ($stats['pending'] / $total) * 100;
                        ?>
                        <div class="progress-bar bg-success" style="width: <?php echo $deliveredPercent; ?>%" role="progressbar">
                            Delivered: <?php echo $stats['delivered']; ?>
                        </div>
                        <div class="progress-bar bg-warning" style="width: <?php echo $pendingPercent; ?>%" role="progressbar">
                            Pending: <?php echo $stats['pending']; ?>
                        </div>
                        <div class="progress-bar bg-secondary" style="width: <?php echo 100 - $deliveredPercent - $pendingPercent; ?>%" role="progressbar">
                            Not Scheduled
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Quick Actions</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-success" onclick="exportToCSV('procurement-table', 'procurement_report.csv')">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Procurement Records Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="procurement-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>ITB No.</th>
                            <th>Particulars</th>
                            <th>ABC (₱)</th>
                            <th>Winning Bidder</th>
                            <th>Winning Price (₱)</th>
                            <th>Savings</th>
                            <th>Delivery (PO)</th>
                            <th>Actual Delivery</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($procurementList)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No procurement records found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($procurementList as $record): ?>
                                <?php
                                $savings = ($record['abc'] ?? 0) - ($record['winning_price'] ?? 0);
                                $deliveryStatus = '';
                                $deliveryBadge = '';
                                if ($record['actual_delivery_date']) {
                                    $deliveryStatus = 'Delivered on ' . formatDate($record['actual_delivery_date']);
                                    $deliveryBadge = 'success';
                                } elseif ($record['delivery_date_per_po']) {
                                    $deliveryStatus = 'Due: ' . formatDate($record['delivery_date_per_po']);
                                    $deliveryBadge = 'warning';
                                } else {
                                    $deliveryStatus = 'Not Scheduled';
                                    $deliveryBadge = 'secondary';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($record['itb_no'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="text-wrap" style="max-width: 300px;">
                                        <strong><?php echo htmlspecialchars(substr($record['particulars'], 0, 60)); ?></strong>
                                        <?php if (strlen($record['particulars']) > 60): ?>...<?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo formatCurrency($record['abc'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars($record['winning_bidder'] ?? '-'); ?></td>
                                    <td class="text-end"><?php echo $record['winning_price'] ? formatCurrency($record['winning_price']) : '-'; ?></td>
                                    <td class="text-end <?php echo $savings > 0 ? 'text-success' : ($savings < 0 ? 'text-danger' : ''); ?>">
                                        <?php echo $savings != 0 ? formatCurrency($savings) : '-'; ?>
                                    </td>
                                    <td class="delivery-cell" data-id="<?php echo $record['id']; ?>" data-field="delivery_date_per_po">
                                        <div class="inline-edit">
                                            <span class="edit-value" data-type="date" data-value="<?php echo $record['delivery_date_per_po']; ?>">
                                                <?php echo $record['delivery_date_per_po'] ? formatDate($record['delivery_date_per_po']) : '<span class="text-muted">Set date</span>'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="delivery-cell" data-id="<?php echo $record['id']; ?>" data-field="actual_delivery_date">
                                        <div class="inline-edit">
                                            <span class="edit-value" data-type="date" data-value="<?php echo $record['actual_delivery_date']; ?>">
                                                <?php echo $record['actual_delivery_date'] ? formatDate($record['actual_delivery_date']) : '<span class="text-muted">Not delivered</span>'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $record['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#procurementModal"
                                               onclick="loadEditData(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $record['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="?delete=<?php echo $record['id']; ?>" 
                                               class="btn btn-outline-danger confirm-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $record['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Procurement Details - ITB <?php echo htmlspecialchars($record['itb_no'] ?? 'N/A'); ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <h6>Project Particulars</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($record['particulars'])); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <strong>ITB Number:</strong><br>
                                                        <?php echo htmlspecialchars($record['itb_no'] ?? 'N/A'); ?>
                                                        <hr>
                                                        <strong>Approved Budget (ABC):</strong><br>
                                                        <?php echo formatCurrency($record['abc'] ?? 0); ?>
                                                        <hr>
                                                        <strong>Winning Price:</strong><br>
                                                        <?php echo $record['winning_price'] ? formatCurrency($record['winning_price']) : 'N/A'; ?>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Winning Bidder:</strong><br>
                                                        <?php echo htmlspecialchars($record['winning_bidder'] ?? 'N/A'); ?>
                                                        <hr>
                                                        <strong>Delivery Date (per PO):</strong><br>
                                                        <?php echo formatDate($record['delivery_date_per_po']); ?>
                                                        <hr>
                                                        <strong>Actual Delivery Date:</strong><br>
                                                        <?php echo formatDate($record['actual_delivery_date']); ?>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Remarks:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($record['remarks'] ?? 'N/A')); ?>
                                                    </div>
                                                </div>
                                                
                                                <hr>
                                                <h6>Participating Bidders</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr><th>Bidder</th><th>Price (₱)</th></tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <?php if ($record["bidder_$i"]): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($record["bidder_$i"]); ?></td>
                                                                        <td><?php echo $record["bidder_{$i}_price"] ? formatCurrency($record["bidder_{$i}_price"]) : '-'; ?></td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <?php echo getPaginationLinks($page, $totalPages, 'procurement-monitoring.php'); ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="procurementModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Procurement Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="id" id="record_id" value="0">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="itb_no" class="form-label">ITB Number</label>
                            <input type="text" class="form-control" id="itb_no" name="itb_no" placeholder="e.g., FPBAC-2024-PB-001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="abc" class="form-label">Approved Budget (ABC) (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="abc" name="abc">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="particulars" class="form-label required">Particulars / Project Description</label>
                        <textarea class="form-control" id="particulars" name="particulars" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="winning_bidder" class="form-label">Winning Bidder</label>
                            <input type="text" class="form-control" id="winning_bidder" name="winning_bidder">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="winning_price" class="form-label">Winning Price (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="winning_price" name="winning_price">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="delivery_date_per_po" class="form-label">Delivery Date (per PO)</label>
                            <input type="date" class="form-control" id="delivery_date_per_po" name="delivery_date_per_po">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="actual_delivery_date" class="form-label">Actual Delivery Date</label>
                            <input type="date" class="form-control" id="actual_delivery_date" name="actual_delivery_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3">Participating Bidders</h6>
                    <div class="row">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control form-control-sm" 
                                       id="bidder_<?php echo $i; ?>" name="bidder_<?php echo $i; ?>" 
                                       placeholder="Bidder <?php echo $i; ?> Name">
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="number" step="0.01" class="form-control form-control-sm" 
                                       id="bidder_<?php echo $i; ?>_price" name="bidder_<?php echo $i; ?>_price" 
                                       placeholder="Price (₱)">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Inline editing for delivery dates
document.querySelectorAll('.inline-edit .edit-value').forEach(function(element) {
    element.addEventListener('click', function() {
        const $this = $(this);
        const currentValue = $this.data('value');
        const type = $this.data('type');
        const cell = $this.closest('.delivery-cell');
        const recordId = cell.data('id');
        const field = cell.data('field');
        
        let inputHtml = '';
        if (type === 'date') {
            inputHtml = `<input type="date" class="form-control form-control-sm" value="${currentValue || ''}" style="min-width: 140px;">`;
        } else {
            inputHtml = `<input type="text" class="form-control form-control-sm" value="${currentValue || ''}" style="min-width: 200px;">`;
        }
        
        $this.hide();
        $this.after(`<div class="edit-input">${inputHtml}
            <div class="mt-1">
                <button class="btn btn-sm btn-success save-edit">Save</button>
                <button class="btn btn-sm btn-secondary cancel-edit">Cancel</button>
            </div>
        </div>`);
        
        const $editDiv = $this.next('.edit-input');
        
        $editDiv.find('.save-edit').click(function() {
            const newValue = $editDiv.find('input').val();
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax_update: 1,
                    id: recordId,
                    field: field,
                    value: newValue
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to update: ' + (response.error || 'Unknown error'));
                        $editDiv.remove();
                        $this.show();
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $editDiv.remove();
                    $this.show();
                }
            });
        });
        
        $editDiv.find('.cancel-edit').click(function() {
            $editDiv.remove();
            $this.show();
        });
    });
});

function loadEditData(data) {
    document.getElementById('modalTitle').innerHTML = 'Edit Procurement Record';
    document.getElementById('record_id').value = data.id;
    document.getElementById('itb_no').value = data.itb_no || '';
    document.getElementById('particulars').value = data.particulars;
    document.getElementById('abc').value = data.abc || '';
    document.getElementById('winning_bidder').value = data.winning_bidder || '';
    document.getElementById('winning_price').value = data.winning_price || '';
    document.getElementById('delivery_date_per_po').value = data.delivery_date_per_po || '';
    document.getElementById('actual_delivery_date').value = data.actual_delivery_date || '';
    document.getElementById('remarks').value = data.remarks || '';
    
    for (let i = 1; i <= 5; i++) {
        document.getElementById(`bidder_${i}`).value = data[`bidder_${i}`] || '';
        document.getElementById(`bidder_${i}_price`).value = data[`bidder_${i}_price`] || '';
    }
}

// Reset modal for new record
document.getElementById('procurementModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var isEdit = button && button.getAttribute('href') && button.getAttribute('href').includes('edit');
    
    if (!isEdit) {
        document.getElementById('modalTitle').innerHTML = 'Add New Procurement Record';
        document.getElementById('record_id').value = '0';
        document.getElementById('itb_no').value = '';
        document.getElementById('particulars').value = '';
        document.getElementById('abc').value = '';
        document.getElementById('winning_bidder').value = '';
        document.getElementById('winning_price').value = '';
        document.getElementById('delivery_date_per_po').value = '';
        document.getElementById('actual_delivery_date').value = '';
        document.getElementById('remarks').value = '';
        
        for (let i = 1; i <= 5; i++) {
            document.getElementById(`bidder_${i}`).value = '';
            document.getElementById(`bidder_${i}_price`).value = '';
        }
    }
});

function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            // Skip action buttons column
            if (cell.querySelector('.btn-group')) return '';
            let text = cell.innerText.trim();
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = text.replace(/"/g, '""');
                text = `"${text}"`;
            }
            return text;
        }).filter(text => text !== '');
        
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

<?php include '../includes/templates/admin-footer.php'; ?>