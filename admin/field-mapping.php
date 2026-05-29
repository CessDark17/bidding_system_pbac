<?php
/**
 * Field Mapping Configuration
 * File: admin/field-mapping.php
 * 
 * Configure how Excel columns map to database fields for automatic import
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Field Mapping Configuration';

// Handle delete template
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM field_mapping_templates WHERE id = ?";
    $result = executeQuery($sql, [$id]);
    
    if ($result) {
        alertRedirect('Template deleted successfully.', 'success', 'field-mapping.php');
    }
}

// Handle set default
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    $document_type = sanitize($_GET['type']);
    
    // Reset default for this document type
    executeQuery("UPDATE field_mapping_templates SET is_default = 0 WHERE document_type = ?", [$document_type]);
    // Set new default
    executeQuery("UPDATE field_mapping_templates SET is_default = 1 WHERE id = ?", [$id]);
    
    alertRedirect('Default template updated successfully.', 'success', 'field-mapping.php');
}

// Handle save mapping
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_mapping'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'field-mapping.php');
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $template_name = sanitize($_POST['template_name']);
    $document_type = sanitize($_POST['document_type']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Build field mappings
    $mappings = [];
    $field_groups = [
        'public_bidding' => [
            'basic' => ['bidding_date', 'project_title', 'fund_source', 'capex_project', 'approved_budget_contract', 'status'],
            'bidders' => ['winning_bidder', 'winning_bid_amount', 'participating_bidders'],
            'dates' => ['notice_of_award', 'contract_date', 'notice_to_proceed'],
            'other' => ['performance_bond_form', 'performance_bond_amount', 'purchase_order_ref']
        ],
        'sealed_bidding' => [
            'basic' => ['bidding_date', 'project_title', 'fund_source', 'status'],
            'bidders' => ['winning_bidder', 'winning_bid_amount', 'participating_bidders'],
            'other' => ['contract_or_po_ref', 'confidential_notes']
        ],
        'procurement_monitoring' => [
            'basic' => ['itb_no', 'particulars', 'abc', 'winning_bidder', 'winning_price'],
            'bidders' => ['bidder_1', 'bidder_1_price', 'bidder_2', 'bidder_2_price', 'bidder_3', 'bidder_3_price', 'bidder_4', 'bidder_4_price', 'bidder_5', 'bidder_5_price'],
            'delivery' => ['delivery_date_per_po', 'actual_delivery_date'],
            'other' => ['remarks']
        ]
    ];
    
    $fields = $field_groups[$document_type] ?? [];
    $all_fields = [];
    foreach ($fields as $group) {
        $all_fields = array_merge($all_fields, $group);
    }
    
    foreach ($all_fields as $field) {
        if (isset($_POST["map_$field"])) {
            $mappings[$field] = $_POST["map_$field"];
        }
    }
    
    $mappings_json = json_encode($mappings);
    
    if ($id > 0) {
        $sql = "UPDATE field_mapping_templates SET template_name = ?, document_type = ?, field_mappings = ?, is_default = ? WHERE id = ?";
        $result = executeQuery($sql, [$template_name, $document_type, $mappings_json, $is_default, $id]);
    } else {
        if ($is_default) {
            executeQuery("UPDATE field_mapping_templates SET is_default = 0 WHERE document_type = ?", [$document_type]);
        }
        $sql = "INSERT INTO field_mapping_templates (template_name, document_type, field_mappings, is_default, created_by) VALUES (?, ?, ?, ?, ?)";
        $result = executeQuery($sql, [$template_name, $document_type, $mappings_json, $is_default, $_SESSION['user_id']]);
    }
    
    if ($result) {
        alertRedirect('Mapping template saved successfully.', 'success', 'field-mapping.php');
    } else {
        alertRedirect('Failed to save mapping template.', 'danger', 'field-mapping.php');
    }
}

// Get templates
$templates = fetchAll("SELECT * FROM field_mapping_templates ORDER BY document_type, is_default DESC, created_at DESC");

// Get template for editing
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editTemplate = fetchOne("SELECT * FROM field_mapping_templates WHERE id = ?", [(int)$_GET['edit']]);
}

// Get default templates for preview
$defaultMappings = [];
foreach (['public_bidding', 'sealed_bidding', 'procurement_monitoring'] as $doc_type) {
    $defaultMappings[$doc_type] = fetchOne("SELECT * FROM field_mapping_templates WHERE document_type = ? AND is_default = 1", [$doc_type]);
}

$current_doc_type = $editTemplate ? $editTemplate['document_type'] : ($_GET['type'] ?? 'public_bidding');

include '../includes/templates/admin-header.php';
?>

<style>
.mapping-card {
    border-left: 4px solid #0d6efd;
}
.mapping-group {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
.mapping-group h6 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}
.excel-column-hint {
    font-family: monospace;
    font-size: 0.875rem;
    color: #6c757d;
}
</style>

<div class="admin-field-mapping">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-code-branch me-2"></i>Field Mapping Configuration
        </h1>
        <a href="?action=add" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mappingModal">
            <i class="fas fa-plus me-2"></i>Create New Template
        </a>
    </div>
    
    <!-- Existing Templates -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-database me-2"></i>Mapping Templates
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Template Name</th>
                            <th>Document Type</th>
                            <th>Default</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No mapping templates created yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($template['template_name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo str_replace('_', ' ', $template['document_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($template['is_default']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Default</span>
                                        <?php else: ?>
                                            <a href="?set_default=<?php echo $template['id']; ?>&type=<?php echo $template['document_type']; ?>" 
                                               class="btn btn-sm btn-outline-secondary confirm-set-default">
                                                Set as Default
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($template['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $template['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#mappingModal"
                                               onclick="loadTemplateData(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $template['id']; ?>" 
                                               class="btn btn-outline-danger confirm-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Example of Excel Expected Format -->
    <div class="alert alert-secondary">
        <h6><i class="fas fa-table"></i> Expected Excel Format</h6>
        <p>Your Excel file should have headers in the first row. Map these headers to database fields below.</p>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#exampleFormat">
            <i class="fas fa-eye"></i> Show Example
        </button>
        
        <div class="collapse mt-3" id="exampleFormat">
            <div class="card card-body">
                <h6>Example Public Bidding Excel Headers:</h6>
                <code class="excel-column-hint d-block mb-2">
                    Bidding Date | Project Title | Fund Source | CAPEX Project | Approved Budget | Winning Bidder | Winning Bid Amount | Status
                </code>
                <h6 class="mt-3">Example Sealed Bidding Excel Headers:</h6>
                <code class="excel-column-hint d-block mb-2">
                    Bidding Date | Project Title | Fund Source | Winning Bidder | Winning Bid Amount | Contract Reference | Status
                </code>
                <h6 class="mt-3">Example Procurement Monitoring Excel Headers:</h6>
                <code class="excel-column-hint d-block">
                    ITB No | Particulars | ABC | Winning Bidder | Winning Price | Bidder 1 | Bidder 1 Price | Bidder 2 | Bidder 2 Price | Delivery Date | Actual Delivery | Remarks
                </code>
            </div>
        </div>
    </div>
    
    <!-- Default Templates Summary -->
    <div class="row">
        <?php foreach ($defaultMappings as $doc_type => $template): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <?php echo str_replace('_', ' ', ucfirst($doc_type)); ?>
                            <span class="badge bg-success float-end">Default</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($template): ?>
                            <p><strong>Template:</strong> <?php echo htmlspecialchars($template['template_name']); ?></p>
                            <p><strong>Mappings:</strong> <?php echo count(json_decode($template['field_mappings'] ?? '{}', true)); ?> fields</p>
                            <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mappingModal" onclick="loadTemplateData(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                                View/Edit
                            </a>
                        <?php else: ?>
                            <p class="text-muted">No default template configured.</p>
                            <a href="?action=add&type=<?php echo $doc_type; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mappingModal">
                                Create Template
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="mappingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-code-branch me-2"></i>Create Mapping Template
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="id" id="template_id" value="0">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="template_name" class="form-label required">Template Name</label>
                            <input type="text" class="form-control" id="template_name" name="template_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="document_type" class="form-label required">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type" required>
                                <option value="public_bidding">Public Bidding</option>
                                <option value="sealed_bidding">Sealed Bidding</option>
                                <option value="procurement_monitoring">Procurement Monitoring</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1">
                            <label class="form-check-label" for="is_default">
                                Set as default template for this document type
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Field Mappings</h6>
                    <p class="text-muted small">Map Excel column headers to database fields. Leave blank to skip.</p>
                    
                    <div id="mappingsContainer">
                        <!-- Dynamic content will be loaded via JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_mapping" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const fieldGroups = {
    'public_bidding': {
        'Basic Information': ['bidding_date', 'project_title', 'fund_source', 'capex_project', 'approved_budget_contract', 'status'],
        'Bidder Information': ['winning_bidder', 'winning_bid_amount', 'participating_bidders'],
        'Award Dates': ['notice_of_award', 'contract_date', 'notice_to_proceed'],
        'Bond & PO': ['performance_bond_form', 'performance_bond_amount', 'purchase_order_ref']
    },
    'sealed_bidding': {
        'Basic Information': ['bidding_date', 'project_title', 'fund_source', 'status'],
        'Bidder Information': ['winning_bidder', 'winning_bid_amount', 'participating_bidders'],
        'Contract Information': ['contract_or_po_ref', 'confidential_notes']
    },
    'procurement_monitoring': {
        'Basic Information': ['itb_no', 'particulars', 'abc', 'winning_bidder', 'winning_price'],
        'Participating Bidders': ['bidder_1', 'bidder_1_price', 'bidder_2', 'bidder_2_price', 'bidder_3', 'bidder_3_price', 'bidder_4', 'bidder_4_price', 'bidder_5', 'bidder_5_price'],
        'Delivery Information': ['delivery_date_per_po', 'actual_delivery_date'],
        'Other Information': ['remarks']
    }
};

const fieldLabels = {
    'bidding_date': 'Bidding Date', 'project_title': 'Project Title', 'fund_source': 'Fund Source',
    'capex_project': 'CAPEX Project', 'approved_budget_contract': 'Approved Budget (ABC)', 'status': 'Status',
    'winning_bidder': 'Winning Bidder', 'winning_bid_amount': 'Winning Bid Amount', 'participating_bidders': 'Participating Bidders',
    'notice_of_award': 'Notice of Award Date', 'contract_date': 'Contract Date', 'notice_to_proceed': 'Notice to Proceed Date',
    'performance_bond_form': 'Performance Bond Form', 'performance_bond_amount': 'Performance Bond Amount', 'purchase_order_ref': 'Purchase Order Reference',
    'contract_or_po_ref': 'Contract/PO Reference', 'confidential_notes': 'Confidential Notes',
    'itb_no': 'ITB Number', 'particulars': 'Particulars/Description', 'abc': 'ABC Amount',
    'winning_price': 'Winning Price', 'remarks': 'Remarks', 'delivery_date_per_po': 'Delivery Date (per PO)',
    'actual_delivery_date': 'Actual Delivery Date',
    'bidder_1': 'Bidder 1 Name', 'bidder_1_price': 'Bidder 1 Price', 'bidder_2': 'Bidder 2 Name', 'bidder_2_price': 'Bidder 2 Price',
    'bidder_3': 'Bidder 3 Name', 'bidder_3_price': 'Bidder 3 Price', 'bidder_4': 'Bidder 4 Name', 'bidder_4_price': 'Bidder 4 Price',
    'bidder_5': 'Bidder 5 Name', 'bidder_5_price': 'Bidder 5 Price'
};

function loadTemplateData(template) {
    document.getElementById('template_id').value = template.id;
    document.getElementById('template_name').value = template.template_name;
    document.getElementById('document_type').value = template.document_type;
    document.getElementById('is_default').checked = template.is_default == 1;
    
    const mappings = JSON.parse(template.field_mappings || '{}');
    generateMappingsForm(template.document_type, mappings);
}

function generateMappingsForm(documentType, existingMappings = {}) {
    const groups = fieldGroups[documentType] || {};
    const container = document.getElementById('mappingsContainer');
    
    let html = '';
    for (const [groupName, fields] of Object.entries(groups)) {
        html += `<div class="mapping-group"><h6>${groupName}</h6><div class="row">`;
        
        for (const field of fields) {
            const label = fieldLabels[field] || field;
            const currentValue = existingMappings[field] || '';
            html += `
                <div class="col-md-6 mb-2">
                    <label class="form-label small">${label}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light" style="width: 130px;">DB: ${field}</span>
                        <input type="text" class="form-control" name="map_${field}" 
                               placeholder="Excel column header name" value="${currentValue.replace(/"/g, '&quot;')}">
                    </div>
                </div>
            `;
        }
        html += `</div></div>`;
    }
    
    container.innerHTML = html;
}

// Setup document type change handler
document.getElementById('document_type').addEventListener('change', function() {
    generateMappingsForm(this.value);
});

// Modal show handler
document.getElementById('mappingModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const isEdit = button && button.getAttribute('href') && button.getAttribute('href').includes('edit');
    const urlParams = new URLSearchParams(window.location.search);
    const typeParam = urlParams.get('type');
    
    if (!isEdit && typeParam) {
        // New template with preset document type
        document.getElementById('template_id').value = '0';
        document.getElementById('template_name').value = '';
        document.getElementById('document_type').value = typeParam;
        document.getElementById('is_default').checked = false;
        generateMappingsForm(typeParam);
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-code-branch me-2"></i>Create New Template';
    } else if (!isEdit) {
        // New template
        document.getElementById('template_id').value = '0';
        document.getElementById('template_name').value = '';
        document.getElementById('document_type').value = 'public_bidding';
        document.getElementById('is_default').checked = false;
        generateMappingsForm('public_bidding');
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-code-branch me-2"></i>Create New Template';
    } else {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Template';
    }
});

// Confirm set default
document.querySelectorAll('.confirm-set-default').forEach(el => {
    el.addEventListener('click', function(e) {
        if (!confirm('Set this template as the default? It will replace the current default for this document type.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/templates/admin-footer.php'; ?>