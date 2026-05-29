<?php
/**
 * Sealed Bidding Management
 * File: admin/sealed-bidding.php
 * 
 * CRUD operations for sealed/confidential bidding records
 * These records are only accessible to authenticated users
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Manage Sealed Bidding';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM sealed_bidding WHERE id = ?";
    $result = executeQuery($sql, [$id]);
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'DELETE_SEALED_BIDDING', 'sealed_bidding', $id);
        alertRedirect('Record deleted successfully.', 'success', 'sealed-bidding.php');
    }
}

// Handle status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    
    $sql = "UPDATE sealed_bidding SET status = ? WHERE id = ?";
    $result = executeQuery($sql, [$status, $id]);
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'UPDATE_SEALED_BIDDING_STATUS', 'sealed_bidding', $id);
        alertRedirect('Status updated successfully.', 'success', 'sealed-bidding.php');
    }
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'sealed-bidding.php');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    $data = [
        'bidding_date' => sanitize($_POST['bidding_date']),
        'project_title' => sanitize($_POST['project_title']),
        'fund_source' => sanitize($_POST['fund_source']),
        'participating_bidders' => sanitize($_POST['participating_bidders'] ?? null),
        'winning_bidder' => sanitize($_POST['winning_bidder'] ?? null),
        'winning_bid_amount' => !empty($_POST['winning_bid_amount']) ? (float)$_POST['winning_bid_amount'] : null,
        'contract_or_po_ref' => sanitize($_POST['contract_or_po_ref'] ?? null),
        'confidential_notes' => sanitize($_POST['confidential_notes'] ?? null),
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
        $sql = "UPDATE sealed_bidding SET " . implode(', ', $set) . " WHERE id = ?";
        $result = executeQuery($sql, $params);
        
        if ($result) {
            logActivity($_SESSION['user_id'], 'UPDATE_SEALED_BIDDING', 'sealed_bidding', $id);
            alertRedirect('Record updated successfully.', 'success', 'sealed-bidding.php');
        }
    } else {
        // Insert
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO sealed_bidding (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $result = executeQuery($sql, array_values($data));
        
        if ($result) {
            $newId = $db->lastInsertId();
            logActivity($_SESSION['user_id'], 'CREATE_SEALED_BIDDING', 'sealed_bidding', $newId);
            alertRedirect('Record created successfully.', 'success', 'sealed-bidding.php');
        }
    }
    
    if (!$result) {
        alertRedirect('Failed to save record.', 'danger', 'sealed-bidding.php');
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
$countSql = "SELECT COUNT(*) as total FROM sealed_bidding $whereClause";
$countResult = fetchOne($countSql, $params);
$totalRecords = $countResult['total'] ?? 0;
$totalPages = ceil($totalRecords / ADMIN_ITEMS_PER_PAGE);

// Get records
$sql = "SELECT * FROM sealed_bidding 
        $whereClause 
        ORDER BY bidding_date DESC 
        LIMIT ? OFFSET ?";
$params[] = ADMIN_ITEMS_PER_PAGE;
$params[] = $offset;
$biddingList = fetchAll($sql, $params);

// Get record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $editRecord = fetchOne("SELECT * FROM sealed_bidding WHERE id = ?", [(int)$_GET['edit']]);
}

include '../includes/templates/admin-header.php';
?>

<div class="admin-sealed-bidding">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-lock me-2"></i>Sealed Bidding Management
            <small class="text-muted fs-6">(Confidential - Restricted Access)</small>
        </h1>
        <div>
            <a href="?action=add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bidModal">
                <i class="fas fa-plus me-2"></i>Add New Sealed Bidding
            </a>
            <a href="upload.php?type=sealed_bidding" class="btn btn-success">
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
                        <option value="awarded" <?php echo $status == 'awarded' ? 'selected' : ''; ?>>Awarded</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="sealed-bidding.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Warning Banner -->
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Confidential Information:</strong> These records contain sensitive procurement information and should only be accessed by authorized personnel.
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
                            <th>Winning Bidder</th>
                            <th>Winning Bid (₱)</th>
                            <th>Status</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biddingList)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No sealed bidding records found.</p>
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
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-lock me-2"></i>Sealed Bidding Details (Confidential)
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Winning Bidder:</strong><br>
                                                        <?php echo htmlspecialchars($bid['winning_bidder'] ?? 'Not yet awarded'); ?>
                                                        <hr>
                                                        <strong>Winning Bid Amount:</strong><br>
                                                        <?php echo $bid['winning_bid_amount'] ? formatCurrency($bid['winning_bid_amount']) : 'Pending'; ?>
                                                        <hr>
                                                        <strong>Status:</strong><br>
                                                        <?php echo getStatusBadge($bid['status']); ?>
                                                    </div>
                                                </div>
                                                <?php if ($bid['participating_bidders']): ?>
                                                    <hr>
                                                    <strong>Participating Bidders:</strong><br>
                                                    <p><?php echo nl2br(htmlspecialchars($bid['participating_bidders'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($bid['contract_or_po_ref']): ?>
                                                    <hr>
                                                    <strong>Contract/PO Reference:</strong><br>
                                                    <?php echo htmlspecialchars($bid['contract_or_po_ref']); ?>
                                                <?php endif; ?>
                                                <?php if ($bid['confidential_notes']): ?>
                                                    <hr>
                                                    <strong>Confidential Notes:</strong><br>
                                                    <div class="alert alert-secondary">
                                                        <?php echo nl2br(htmlspecialchars($bid['confidential_notes'])); ?>
                                                    </div>
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
            <?php echo getPaginationLinks($page, $totalPages, 'sealed-bidding.php'); ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="bidModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-lock me-2"></i>Add New Sealed Bidding Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <option value="awarded">Awarded</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
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
                    
                    <div class="mb-3">
                        <label for="contract_or_po_ref" class="form-label">Contract / PO Reference</label>
                        <input type="text" class="form-control" id="contract_or_po_ref" name="contract_or_po_ref">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confidential_notes" class="form-label">Confidential Notes</label>
                        <textarea class="form-control" id="confidential_notes" name="confidential_notes" rows="3" 
                                  placeholder="Internal notes (only visible to admin)"></textarea>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle"></i> These notes are confidential and only visible to administrators.
                        </div>
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
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-lock me-2"></i>Edit Sealed Bidding Record';
    document.getElementById('record_id').value = data.id;
    document.getElementById('bidding_date').value = data.bidding_date;
    document.getElementById('status').value = data.status;
    document.getElementById('project_title').value = data.project_title;
    document.getElementById('fund_source').value = data.fund_source;
    document.getElementById('winning_bid_amount').value = data.winning_bid_amount || '';
    document.getElementById('winning_bidder').value = data.winning_bidder || '';
    document.getElementById('participating_bidders').value = data.participating_bidders || '';
    document.getElementById('contract_or_po_ref').value = data.contract_or_po_ref || '';
    document.getElementById('confidential_notes').value = data.confidential_notes || '';
}

// Reset modal for new record
document.getElementById('bidModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var isEdit = button && button.getAttribute('href') && button.getAttribute('href').includes('edit');
    
    if (!isEdit) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-lock me-2"></i>Add New Sealed Bidding Record';
        document.getElementById('record_id').value = '0';
        document.getElementById('bidding_date').value = '';
        document.getElementById('status').value = 'active';
        document.getElementById('project_title').value = '';
        document.getElementById('fund_source').value = 'CAPEX Project';
        document.getElementById('winning_bid_amount').value = '';
        document.getElementById('winning_bidder').value = '';
        document.getElementById('participating_bidders').value = '';
        document.getElementById('contract_or_po_ref').value = '';
        document.getElementById('confidential_notes').value = '';
    }
});
</script>

<?php include '../includes/templates/admin-footer.php'; ?>