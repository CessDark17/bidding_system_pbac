<?php
/**
 * Public Bidding Management
 * File: admin/public-bidding.php
 * 
 * CRUD operations for public bidding records
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Manage Public Bidding';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM public_bidding WHERE id = ?";
    $result = executeQuery($sql, [$id]);
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'DELETE_PUBLIC_BIDDING', 'public_bidding', $id);
        alertRedirect('Record deleted successfully.', 'success', 'public-bidding.php');
    } else {
        alertRedirect('Failed to delete record.', 'danger', 'public-bidding.php');
    }
}

// Handle status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    
    $sql = "UPDATE public_bidding SET status = ? WHERE id = ?";
    $result = executeQuery($sql, [$status, $id]);
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'UPDATE_PUBLIC_BIDDING_STATUS', 'public_bidding', $id);
        alertRedirect('Status updated successfully.', 'success', 'public-bidding.php');
    }
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'public-bidding.php');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    $data = [
        'bidding_date' => sanitize($_POST['bidding_date']),
        'project_title' => sanitize($_POST['project_title']),
        'fund_source' => sanitize($_POST['fund_source']),
        'capex_project' => sanitize($_POST['capex_project'] ?? null),
        'approved_budget_contract' => (float)$_POST['approved_budget_contract'],
        'participating_bidders' => sanitize($_POST['participating_bidders'] ?? null),
        'winning_bidder' => sanitize($_POST['winning_bidder'] ?? null),
        'winning_bid_amount' => !empty($_POST['winning_bid_amount']) ? (float)$_POST['winning_bid_amount'] : null,
        'notice_of_award' => !empty($_POST['notice_of_award']) ? $_POST['notice_of_award'] : null,
        'contract_date' => !empty($_POST['contract_date']) ? $_POST['contract_date'] : null,
        'performance_bond_form' => sanitize($_POST['performance_bond_form'] ?? null),
        'performance_bond_amount' => !empty($_POST['performance_bond_amount']) ? (float)$_POST['performance_bond_amount'] : null,
        'notice_to_proceed' => !empty($_POST['notice_to_proceed']) ? $_POST['notice_to_proceed'] : null,
        'purchase_order_ref' => sanitize($_POST['purchase_order_ref'] ?? null),
        'status' => sanitize($_POST['status']),
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
        $sql = "UPDATE public_bidding SET " . implode(', ', $set) . " WHERE id = ?";
        $result = executeQuery($sql, $params);
        
        if ($result) {
            logActivity($_SESSION['user_id'], 'UPDATE_PUBLIC_BIDDING', 'public_bidding', $id);
            alertRedirect('Record updated successfully.', 'success', 'public-bidding.php');
        }
    } else {
        // Insert
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO public_bidding (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $result = executeQuery($sql, array_values($data));
        
        if ($result) {
            $newId = $db->lastInsertId();
            logActivity($_SESSION['user_id'], 'CREATE_PUBLIC_BIDDING', 'public_bidding', $newId);
            alertRedirect('Record created successfully.', 'success', 'public-bidding.php');
        }
    }
    
    if (!$result) {
        alertRedirect('Failed to save record.', 'danger', 'public-bidding.php');
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(project_title LIKE ? OR winning_bidder LIKE ? OR fund_source LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status) && $status != 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(' AND ', $whereConditions) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM public_bidding $whereClause";
$countResult = fetchOne($countSql, $params);
$totalRecords = $countResult['total'] ?? 0;
$totalPages = ceil($totalRecords / ADMIN_ITEMS_PER_PAGE);

// Get records
$sql = "SELECT * FROM public_bidding 
        $whereClause 
        ORDER BY bidding_date DESC 
        LIMIT ? OFFSET ?";
$params[] = ADMIN_ITEMS_PER_PAGE;
$params[] = $offset;
$biddingList = fetchAll($sql, $params);

// Get record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $editRecord = fetchOne("SELECT * FROM public_bidding WHERE id = ?", [(int)$_GET['edit']]);
}

include '../includes/templates/admin-header.php';
?>

<div class="admin-public-bidding">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-gavel me-2"></i>Public Bidding Management
        </h1>
        <div>
            <a href="?action=add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bidModal">
                <i class="fas fa-plus me-2"></i>Add New Bidding
            </a>
            <a href="upload.php?type=public_bidding" class="btn btn-success">
                <i class="fas fa-upload me-2"></i>Import from File
            </a>
        </div>
    </div>
    
    <!-- Search and Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by project, bidder, or fund source..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="ongoing" <?php echo $status == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="public-bidding.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bidding Records Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Bidding Date</th>
                            <th>Project Title</th>
                            <th>Fund Source</th>
                            <th>ABC (₱)</th>
                            <th>Winning Bidder</th>
                            <th>Winning Bid (₱)</th>
                            <th>Status</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biddingList)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No bidding records found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($biddingList as $bid): ?>
                                <tr>
                                    <td><?php echo $bid['id']; ?></td>
                                    <td><?php echo formatDate($bid['bidding_date']); ?></td>
                                    <td class="text-wrap" style="max-width: 300px;">
                                        <strong><?php echo htmlspecialchars($bid['project_title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($bid['fund_source']); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($bid['approved_budget_contract']); ?></td>
                                    <td><?php echo htmlspecialchars($bid['winning_bidder'] ?? '-'); ?></td>
                                    <td class="text-end"><?php echo $bid['winning_bid_amount'] ? formatCurrency($bid['winning_bid_amount']) : '-'; ?></td>
                                    <td><?php echo getStatusBadge($bid['status']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $bid['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#bidModal"
                                               onclick="loadEditData(<?php echo htmlspecialchars(json_encode($bid)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $bid['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="?delete=<?php echo $bid['id']; ?>" 
                                               class="btn btn-outline-danger confirm-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $bid['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Bidding Details</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Project Title:</strong><br>
                                                        <?php echo htmlspecialchars($bid['project_title']); ?>
                                                        <hr>
                                                        <strong>Bidding Date:</strong><br>
                                                        <?php echo formatDate($bid['bidding_date'], 'F d, Y'); ?>
                                                        <hr>
                                                        <strong>Fund Source:</strong><br>
                                                        <?php echo htmlspecialchars($bid['fund_source']); ?>
                                                        <?php if ($bid['capex_project']): ?>
                                                            <br><small>CAPEX: <?php echo htmlspecialchars($bid['capex_project']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Approved Budget (ABC):</strong><br>
                                                        <?php echo formatCurrency($bid['approved_budget_contract']); ?>
                                                        <hr>
                                                        <strong>Winning Bidder:</strong><br>
                                                        <?php echo htmlspecialchars($bid['winning_bidder'] ?? 'Not yet awarded'); ?>
                                                        <hr>
                                                        <strong>Winning Bid Amount:</strong><br>
                                                        <?php echo $bid['winning_bid_amount'] ? formatCurrency($bid['winning_bid_amount']) : 'Pending'; ?>
                                                    </div>
                                                </div>
                                                <?php if ($bid['participating_bidders']): ?>
                                                    <hr>
                                                    <strong>Participating Bidders:</strong><br>
                                                    <p><?php echo nl2br(htmlspecialchars($bid['participating_bidders'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($bid['notice_of_award'] || $bid['contract_date'] || $bid['notice_to_proceed']): ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <strong>Notice of Award:</strong><br>
                                                            <?php echo formatDate($bid['notice_of_award']); ?>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Contract Date:</strong><br>
                                                            <?php echo formatDate($bid['contract_date']); ?>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Notice to Proceed:</strong><br>
                                                            <?php echo formatDate($bid['notice_to_proceed']); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($bid['purchase_order_ref']): ?>
                                                    <hr>
                                                    <strong>Purchase Order Reference:</strong><br>
                                                    <?php echo htmlspecialchars($bid['purchase_order_ref']); ?>
                                                <?php endif; ?>
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
            <?php echo getPaginationLinks($page, $totalPages, 'public-bidding.php'); ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="bidModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Bidding Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="id" id="record_id" value="0">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bidding_date" class="form-label required">Bidding Date</label>
                            <input type="date" class="form-control" id="bidding_date" name="bidding_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label required">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_title" class="form-label required">Project Title</label>
                        <textarea class="form-control" id="project_title" name="project_title" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fund_source" class="form-label required">Fund Source</label>
                            <select class="form-select" id="fund_source" name="fund_source" required>
                                <option value="CAPEX Project">CAPEX Project</option>
                                <option value="RFSC">RFSC (Re-Investment Fund for Sustainable CAPEX)</option>
                                <option value="General Fund">General Fund</option>
                                <option value="Government Funds">Government Funds</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="capex_project" class="form-label">CAPEX Project Code</label>
                            <input type="text" class="form-control" id="capex_project" name="capex_project">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="approved_budget_contract" class="form-label required">Approved Budget (ABC) (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="approved_budget_contract" name="approved_budget_contract" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="winning_bid_amount" class="form-label">Winning Bid Amount (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="winning_bid_amount" name="winning_bid_amount">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="winning_bidder" class="form-label">Winning Bidder</label>
                        <input type="text" class="form-control" id="winning_bidder" name="winning_bidder">
                    </div>
                    
                    <div class="mb-3">
                        <label for="participating_bidders" class="form-label">Participating Bidders</label>
                        <textarea class="form-control" id="participating_bidders" name="participating_bidders" rows="3" placeholder="List all participating bidders, one per line"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="notice_of_award" class="form-label">Notice of Award Date</label>
                            <input type="date" class="form-control" id="notice_of_award" name="notice_of_award">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="contract_date" class="form-label">Contract Date</label>
                            <input type="date" class="form-control" id="contract_date" name="contract_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="notice_to_proceed" class="form-label">Notice to Proceed Date</label>
                            <input type="date" class="form-control" id="notice_to_proceed" name="notice_to_proceed">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="performance_bond_form" class="form-label">Performance Bond Form</label>
                            <select class="form-select" id="performance_bond_form" name="performance_bond_form">
                                <option value="">Select</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Guarantee">Bank Guarantee</option>
                                <option value="Surety Bond">Surety Bond</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="performance_bond_amount" class="form-label">Performance Bond Amount (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="performance_bond_amount" name="performance_bond_amount">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="purchase_order_ref" class="form-label">Purchase Order Reference</label>
                        <input type="text" class="form-control" id="purchase_order_ref" name="purchase_order_ref">
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
function loadEditData(data) {
    document.getElementById('modalTitle').innerHTML = 'Edit Bidding Record';
    document.getElementById('record_id').value = data.id;
    document.getElementById('bidding_date').value = data.bidding_date;
    document.getElementById('status').value = data.status;
    document.getElementById('project_title').value = data.project_title;
    document.getElementById('fund_source').value = data.fund_source;
    document.getElementById('capex_project').value = data.capex_project || '';
    document.getElementById('approved_budget_contract').value = data.approved_budget_contract;
    document.getElementById('winning_bid_amount').value = data.winning_bid_amount || '';
    document.getElementById('winning_bidder').value = data.winning_bidder || '';
    document.getElementById('participating_bidders').value = data.participating_bidders || '';
    document.getElementById('notice_of_award').value = data.notice_of_award || '';
    document.getElementById('contract_date').value = data.contract_date || '';
    document.getElementById('notice_to_proceed').value = data.notice_to_proceed || '';
    document.getElementById('performance_bond_form').value = data.performance_bond_form || '';
    document.getElementById('performance_bond_amount').value = data.performance_bond_amount || '';
    document.getElementById('purchase_order_ref').value = data.purchase_order_ref || '';
}

// Reset modal for new record
document.getElementById('bidModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var isEdit = button && button.getAttribute('href') && button.getAttribute('href').includes('edit');
    
    if (!isEdit) {
        document.getElementById('modalTitle').innerHTML = 'Add New Bidding Record';
        document.getElementById('record_id').value = '0';
        document.getElementById('bidding_date').value = '';
        document.getElementById('status').value = 'active';
        document.getElementById('project_title').value = '';
        document.getElementById('fund_source').value = 'CAPEX Project';
        document.getElementById('capex_project').value = '';
        document.getElementById('approved_budget_contract').value = '';
        document.getElementById('winning_bid_amount').value = '';
        document.getElementById('winning_bidder').value = '';
        document.getElementById('participating_bidders').value = '';
        document.getElementById('notice_of_award').value = '';
        document.getElementById('contract_date').value = '';
        document.getElementById('notice_to_proceed').value = '';
        document.getElementById('performance_bond_form').value = '';
        document.getElementById('performance_bond_amount').value = '';
        document.getElementById('purchase_order_ref').value = '';
    }
});
</script>

<?php include '../includes/templates/admin-footer.php'; ?>