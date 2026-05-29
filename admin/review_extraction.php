<?php
/**
 * Review Extracted Data
 * File: admin/review-extraction.php
 * 
 * Review and confirm data extracted from uploaded documents
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/classes/ExcelImporter.php';

$pageTitle = 'Review Extracted Data';

$document_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$importer = new ExcelImporter();

// Get document details
$document = fetchOne("
    SELECT * FROM uploaded_documents WHERE id = ?
", [$document_id]);

if (!$document) {
    alertRedirect('Document not found.', 'danger', 'batch-import.php');
}

$extracted_data = json_decode($document['extracted_data'] ?? '{}', true);
$document_type = $document['document_type'];

// Handle approval/import
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'review-extraction.php?id=' . $document_id);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action == 'approve') {
        // Build data array from form
        $data = [];
        $field_mapping = [];
        
        if ($document_type == 'public_bidding') {
            $fields = ['bidding_date', 'project_title', 'fund_source', 'capex_project', 'approved_budget_contract', 
                      'winning_bidder', 'winning_bid_amount', 'participating_bidders', 'status',
                      'notice_of_award', 'contract_date', 'notice_to_proceed', 'purchase_order_ref'];
        } elseif ($document_type == 'sealed_bidding') {
            $fields = ['bidding_date', 'project_title', 'fund_source', 'winning_bidder', 'winning_bid_amount', 
                      'participating_bidders', 'contract_or_po_ref', 'confidential_notes', 'status'];
        } else {
            $fields = ['itb_no', 'particulars', 'abc', 'winning_bidder', 'winning_price', 'remarks',
                      'delivery_date_per_po', 'actual_delivery_date'];
            // Add bidders
            for ($i = 1; $i <= 5; $i++) {
                $fields[] = "bidder_$i";
                $fields[] = "bidder_{$i}_price";
            }
        }
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field] === '' ? null : $_POST[$field];
            }
        }
        
        $data['created_by'] = $_SESSION['user_id'];
        
        // Import to database
        $result = $importer->importArrayToDatabase($data, $document_type);
        
        if ($result['success']) {
            // Update document status
            executeQuery("UPDATE uploaded_documents SET upload_status = 'imported', linked_record_id = ? WHERE id = ?", 
                        [$result['record_id'], $document_id]);
            logActivity($_SESSION['user_id'], 'IMPORT_FROM_EXTRACTION', $document_type, $result['record_id']);
            alertRedirect('Data imported successfully.', 'success', 'batch-import.php');
        } else {
            alertRedirect('Failed to import: ' . ($result['error'] ?? 'Unknown error'), 'danger', 'review-extraction.php?id=' . $document_id);
        }
    } elseif ($action == 'reject') {
        executeQuery("UPDATE uploaded_documents SET upload_status = 'failed', error_message = ? WHERE id = ?", 
                    ['Rejected by user', $document_id]);
        alertRedirect('Document rejected.', 'info', 'batch-import.php');
    }
}

include '../includes/templates/admin-header.php';
?>

<style>
.preview-section {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
.extraction-confidence {
    font-size: 1.5rem;
    font-weight: 700;
}
.confidence-high { color: #198754; }
.confidence-medium { color: #ffc107; }
.confidence-low { color: #dc3545; }
.document-preview {
    max-height: 400px;
    overflow-y: auto;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    font-family: monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
}
</style>

<div class="admin-review-extraction">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-check-double me-2"></i>Review Extracted Data
        </h1>
        <a href="batch-import.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Batch Import
        </a>
    </div>
    
    <!-- Document Info -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-6">
                <strong>Document:</strong> <?php echo htmlspecialchars($document['original_filename']); ?><br>
                <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $document['document_type'])); ?>
            </div>
            <div class="col-md-6">
                <strong>Uploaded:</strong> <?php echo formatDate($document['created_at']); ?><br>
                <strong>Confidence Score:</strong> 
                <span class="badge <?php echo ($document['confidence_score'] ?? 0) >= 80 ? 'bg-success' : (($document['confidence_score'] ?? 0) >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                    <?php echo $document['confidence_score'] ?? 0; ?>%
                </span>
            </div>
        </div>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="row">
            <!-- Extracted Data Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Extracted Data (Review & Edit)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($document_type == 'public_bidding'): ?>
                            <!-- Public Bidding Fields -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bidding Date</label>
                                    <input type="date" class="form-control" name="bidding_date" 
                                           value="<?php echo $extracted_data['bidding_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fund Source</label>
                                    <select class="form-select" name="fund_source">
                                        <option value="CAPEX Project" <?php echo ($extracted_data['fund_source'] ?? '') == 'CAPEX Project' ? 'selected' : ''; ?>>CAPEX Project</option>
                                        <option value="RFSC" <?php echo ($extracted_data['fund_source'] ?? '') == 'RFSC' ? 'selected' : ''; ?>>RFSC</option>
                                        <option value="General Fund" <?php echo ($extracted_data['fund_source'] ?? '') == 'General Fund' ? 'selected' : ''; ?>>General Fund</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Title</label>
                                <textarea class="form-control" name="project_title" rows="3"><?php echo htmlspecialchars($extracted_data['project_title'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CAPEX Project</label>
                                    <input type="text" class="form-control" name="capex_project" value="<?php echo htmlspecialchars($extracted_data['capex_project'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo ($extracted_data['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="ongoing" <?php echo ($extracted_data['status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                        <option value="completed" <?php echo ($extracted_data['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="failed" <?php echo ($extracted_data['status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Approved Budget (ABC)</label>
                                    <input type="number" step="0.01" class="form-control" name="approved_budget_contract" 
                                           value="<?php echo $extracted_data['approved_budget_contract'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Winning Bid Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="winning_bid_amount" 
                                           value="<?php echo $extracted_data['winning_bid_amount'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Winning Bidder</label>
                                <input type="text" class="form-control" name="winning_bidder" 
                                       value="<?php echo htmlspecialchars($extracted_data['winning_bidder'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Participating Bidders</label>
                                <textarea class="form-control" name="participating_bidders" rows="3"><?php echo htmlspecialchars($extracted_data['participating_bidders'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Notice of Award</label>
                                    <input type="date" class="form-control" name="notice_of_award" 
                                           value="<?php echo $extracted_data['notice_of_award'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Contract Date</label>
                                    <input type="date" class="form-control" name="contract_date" 
                                           value="<?php echo $extracted_data['contract_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Notice to Proceed</label>
                                    <input type="date" class="form-control" name="notice_to_proceed" 
                                           value="<?php echo $extracted_data['notice_to_proceed'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Order Reference</label>
                                <input type="text" class="form-control" name="purchase_order_ref" 
                                       value="<?php echo htmlspecialchars($extracted_data['purchase_order_ref'] ?? ''); ?>">
                            </div>
                            
                        <?php elseif ($document_type == 'sealed_bidding'): ?>
                            <!-- Sealed Bidding Fields -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bidding Date</label>
                                    <input type="date" class="form-control" name="bidding_date" 
                                           value="<?php echo $extracted_data['bidding_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fund Source</label>
                                    <input type="text" class="form-control" name="fund_source" 
                                           value="<?php echo htmlspecialchars($extracted_data['fund_source'] ?? 'CAPEX Project'); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Title</label>
                                <textarea class="form-control" name="project_title" rows="3"><?php echo htmlspecialchars($extracted_data['project_title'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Winning Bidder</label>
                                    <input type="text" class="form-control" name="winning_bidder" 
                                           value="<?php echo htmlspecialchars($extracted_data['winning_bidder'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Winning Bid Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="winning_bid_amount" 
                                           value="<?php echo $extracted_data['winning_bid_amount'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Participating Bidders</label>
                                <textarea class="form-control" name="participating_bidders" rows="3"><?php echo htmlspecialchars($extracted_data['participating_bidders'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contract/PO Reference</label>
                                <input type="text" class="form-control" name="contract_or_po_ref" 
                                       value="<?php echo htmlspecialchars($extracted_data['contract_or_po_ref'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confidential Notes</label>
                                <textarea class="form-control" name="confidential_notes" rows="2"><?php echo htmlspecialchars($extracted_data['confidential_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo ($extracted_data['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="awarded" <?php echo ($extracted_data['status'] ?? '') == 'awarded' ? 'selected' : ''; ?>>Awarded</option>
                                    <option value="completed" <?php echo ($extracted_data['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($extracted_data['status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            
                        <?php elseif ($document_type == 'procurement_monitoring'): ?>
                            <!-- Procurement Monitoring Fields -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ITB Number</label>
                                    <input type="text" class="form-control" name="itb_no" 
                                           value="<?php echo htmlspecialchars($extracted_data['itb_no'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ABC Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="abc" 
                                           value="<?php echo $extracted_data['abc'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Particulars</label>
                                <textarea class="form-control" name="particulars" rows="3"><?php echo htmlspecialchars($extracted_data['particulars'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Winning Bidder</label>
                                    <input type="text" class="form-control" name="winning_bidder" 
                                           value="<?php echo htmlspecialchars($extracted_data['winning_bidder'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Winning Price</label>
                                    <input type="number" step="0.01" class="form-control" name="winning_price" 
                                           value="<?php echo $extracted_data['winning_price'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <h6 class="mt-3">Participating Bidders</h6>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="text" class="form-control form-control-sm" name="bidder_<?php echo $i; ?>" 
                                           placeholder="Bidder <?php echo $i; ?> Name"
                                           value="<?php echo htmlspecialchars($extracted_data["bidder_$i"] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <input type="number" step="0.01" class="form-control form-control-sm" name="bidder_<?php echo $i; ?>_price" 
                                           placeholder="Price (₱)"
                                           value="<?php echo $extracted_data["bidder_{$i}_price"] ?? ''; ?>">
                                </div>
                            </div>
                            <?php endfor; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Delivery Date (per PO)</label>
                                    <input type="date" class="form-control" name="delivery_date_per_po" 
                                           value="<?php echo $extracted_data['delivery_date_per_po'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Actual Delivery Date</label>
                                    <input type="date" class="form-control" name="actual_delivery_date" 
                                           value="<?php echo $extracted_data['actual_delivery_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"><?php echo htmlspecialchars($extracted_data['remarks'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Document Preview & Actions -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>Document Preview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="document-preview">
                            <?php
                            $file_ext = pathinfo($document['original_filename'], PATHINFO_EXTENSION);
                            $file_path = '../uploads/bidding-documents/' . $document['stored_filename'];
                            
                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                echo '<img src="' . $file_path . '" class="img-fluid" alt="Document preview">';
                            } elseif ($file_ext == 'pdf') {
                                echo '<embed src="' . $file_path . '" type="application/pdf" width="100%" height="400px">';
                            } else {
                                echo '<pre>' . htmlspecialchars(substr(file_get_contents($file_path), 0, 5000)) . '...</pre>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-gavel me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Please review all extracted data above. Edit any incorrect fields before approving.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Approve & Import
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                <i class="fas fa-times-circle me-2"></i>Reject Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/templates/admin-footer.php'; ?>