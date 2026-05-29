<?php
/**
 * Export Interface
 * File: exports/index.php
 * 
 * User interface for exporting data
 */

require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/middleware/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Export Data';
$is_admin = isAdmin();

include '../includes/templates/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-download me-2"></i>Export Data
        </h1>
        <?php if ($is_admin): ?>
            <a href="export-batch.php?format=zip&include_summary=1" class="btn btn-success">
                <i class="fas fa-file-archive me-2"></i>Full Backup (ZIP)
            </a>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <!-- Export Options -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h me-2"></i>Export Options
                    </h5>
                </div>
                <div class="card-body">
                    <form id="exportForm" method="GET" action="export.php">
                        <div class="mb-3">
                            <label class="form-label">Data Type</label>
                            <select name="type" class="form-select" required>
                                <option value="public_bidding">Public Bidding</option>
                                <option value="sealed_bidding">Sealed Bidding</option>
                                <option value="procurement_monitoring">Procurement Monitoring</option>
                                <?php if ($is_admin): ?>
                                    <option value="summary">Summary Statistics</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select name="format" class="form-select" required>
                                <option value="csv">CSV (Excel Compatible)</option>
                                <option value="excel">Excel (XLS)</option>
                                <option value="pdf">PDF (Print)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" name="start_date" class="form-control" placeholder="Start Date">
                                </div>
                                <div class="col">
                                    <input type="date" name="end_date" class="form-control" placeholder="End Date">
                                </div>
                            </div>
                            <small class="text-muted">Leave empty for all records</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status Filter</label>
                            <select name="status" class="form-select">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Recent Exports -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Recent Exports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="exportsList">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No recent exports</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Export Cards -->
    <div class="row mt-3">
        <div class="col-md-12">
            <h5 class="mb-3">Quick Export Templates</h5>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-file-csv fa-3x text-success mb-3"></i>
                    <h6>Monthly Report</h6>
                    <p class="text-muted small">Current month's bidding data</p>
                    <a href="export.php?type=public_bidding&format=csv&start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>" 
                       class="btn btn-sm btn-outline-success">Download</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                    <h6>Yearly Summary</h6>
                    <p class="text-muted small">Full year statistics</p>
                    <a href="export.php?type=summary&format=csv" 
                       class="btn btn-sm btn-outline-primary">Download</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-print fa-3x text-secondary mb-3"></i>
                    <h6>Print Report</h6>
                    <p class="text-muted small">Printable format</p>
                    <a href="export.php?type=public_bidding&format=pdf" 
                       class="btn btn-sm btn-outline-secondary">Generate</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
    <!-- Admin Batch Export -->
    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools me-2"></i>Admin Batch Export
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Full Database Backup</h6>
                    <p class="text-muted small">Export all data including public bidding, sealed bidding, and procurement monitoring</p>
                    <a href="export-batch.php?format=zip&include_summary=1" class="btn btn-warning">
                        <i class="fas fa-database me-2"></i>Full Backup (ZIP)
                    </a>
                </div>
                <div class="col-md-6">
                    <h6>Annual Archive</h6>
                    <p class="text-muted small">Export data for a specific year</p>
                    <div class="input-group">
                        <input type="number" id="archiveYear" class="form-control" placeholder="Year" value="<?php echo date('Y'); ?>">
                        <button class="btn btn-outline-warning" onclick="downloadArchive()">
                            <i class="fas fa-archive me-2"></i>Archive Year
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Load recent exports from localStorage
function loadRecentExports() {
    const exports = JSON.parse(localStorage.getItem('fibeco_exports') || '[]');
    const tbody = document.getElementById('exportsList');
    
    if (exports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No recent exports</td></tr>';
        return;
    }
    
    tbody.innerHTML = exports.slice(0, 10).map(exp => `
        <tr>
            <td>${escapeHtml(exp.filename)}</td>
            <td><span class="badge bg-secondary">${exp.type}</span></td>
            <td>${exp.date}</td>
            <td>${exp.size}</td>
            <td>
                <a href="${exp.url}" class="btn btn-sm btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
            </td>
        </tr>
    `).join('');
}

// Save export to history
function saveExport(filename, type, size, url) {
    const exports = JSON.parse(localStorage.getItem('fibeco_exports') || '[]');
    exports.unshift({
        filename: filename,
        type: type,
        date: new Date().toLocaleString(),
        size: size,
        url: url
    });
    
    // Keep only last 20 exports
    while (exports.length > 20) exports.pop();
    
    localStorage.setItem('fibeco_exports', JSON.stringify(exports));
    loadRecentExports();
}

// Track form submission
document.getElementById('exportForm')?.addEventListener('submit', function(e) {
    // The actual download is handled by the server
    // We'll track it after a short delay
    setTimeout(() => {
        const formData = new FormData(this);
        const type = formData.get('type');
        const format = formData.get('format');
        const filename = `fibeco_${type}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.${format === 'excel' ? 'xls' : format}`;
        
        saveExport(filename, type, 'Pending', this.action + '?' + new URLSearchParams(formData).toString());
    }, 1000);
});

// Archive download function
function downloadArchive() {
    const year = document.getElementById('archiveYear')?.value;
    if (year) {
        window.location.href = `export-batch.php?format=zip&start_date=${year}-01-01&end_date=${year}-12-31&include_summary=1`;
    }
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load recent exports on page load
loadRecentExports();
</script>

<?php include '../includes/templates/footer.php'; ?>