<?php
/**
 * Reports Generation
 * File: admin/reports.php
 * 
 * Generate and export various reports (PDF, Excel, CSV)
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Generate Reports';

// Get parameters
$report_type = isset($_GET['report_type']) ? sanitize($_GET['report_type']) : 'overview';
$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'html';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-t');
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Generate report data based on type
function getReportData($type, $start_date, $end_date, $status) {
    global $db;
    
    $data = [];
    
    switch ($type) {
        case 'overview':
            // Summary statistics
            $data['summary'] = fetchOne("
                SELECT 
                    (SELECT COUNT(*) FROM public_bidding) as total_public,
                    (SELECT COUNT(*) FROM sealed_bidding) as total_sealed,
                    (SELECT COUNT(*) FROM procurement_monitoring) as total_procurement,
                    (SELECT COALESCE(SUM(approved_budget_contract), 0) FROM public_bidding) as total_abc,
                    (SELECT COALESCE(SUM(winning_bid_amount), 0) FROM public_bidding) as total_awarded,
                    (SELECT COALESCE(SUM(winning_bid_amount), 0) FROM sealed_bidding) as total_sealed_awarded,
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM uploaded_documents) as total_documents
            ");
            
            // Monthly trend
            $data['monthly_trend'] = fetchAll("
                SELECT 
                    DATE_FORMAT(bidding_date, '%Y-%m') as month,
                    COUNT(*) as count,
                    COALESCE(SUM(approved_budget_contract), 0) as total_abc,
                    COALESCE(SUM(winning_bid_amount), 0) as total_awarded
                FROM public_bidding
                WHERE bidding_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(bidding_date, '%Y-%m')
                ORDER BY month DESC
            ", [$start_date, $end_date]);
            
            // Status distribution
            $data['status_distribution'] = fetchAll("
                SELECT status, COUNT(*) as count 
                FROM public_bidding 
                GROUP BY status
            ");
            
            break;
            
        case 'public_bidding':
            $where = "WHERE 1=1";
            $params = [];
            if ($start_date && $end_date) {
                $where .= " AND bidding_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            if ($status != 'all') {
                $where .= " AND status = ?";
                $params[] = $status;
            }
            $data['records'] = fetchAll("
                SELECT * FROM public_bidding $where ORDER BY bidding_date DESC
            ", $params);
            $data['summary'] = fetchOne("
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(approved_budget_contract), 0) as total_abc,
                    COALESCE(SUM(winning_bid_amount), 0) as total_awarded,
                    COUNT(DISTINCT winning_bidder) as unique_bidders
                FROM public_bidding $where
            ", $params);
            break;
            
        case 'sealed_bidding':
            $where = "WHERE 1=1";
            $params = [];
            if ($start_date && $end_date) {
                $where .= " AND bidding_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            if ($status != 'all') {
                $where .= " AND status = ?";
                $params[] = $status;
            }
            $data['records'] = fetchAll("
                SELECT * FROM sealed_bidding $where ORDER BY bidding_date DESC
            ", $params);
            $data['summary'] = fetchOne("
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(winning_bid_amount), 0) as total_awarded
                FROM sealed_bidding $where
            ", $params);
            break;
            
        case 'procurement':
            $where = "WHERE 1=1";
            $params = [];
            if ($start_date && $end_date) {
                $where .= " AND created_at BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            $data['records'] = fetchAll("
                SELECT * FROM procurement_monitoring $where ORDER BY id DESC
            ", $params);
            $data['summary'] = fetchOne("
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(abc), 0) as total_abc,
                    COALESCE(SUM(winning_price), 0) as total_awarded,
                    COUNT(CASE WHEN actual_delivery_date IS NOT NULL THEN 1 END) as delivered
                FROM procurement_monitoring $where
            ", $params);
            break;
    }
    
    return $data;
}

// Handle export
if (isset($_GET['export'])) {
    $data = getReportData($report_type, $start_date, $end_date, $status);
    $filename = "fibeco_report_{$report_type}_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers based on report type
    switch ($report_type) {
        case 'public_bidding':
            fputcsv($output, ['ID', 'Bidding Date', 'Project Title', 'Fund Source', 'ABC', 'Winning Bidder', 'Winning Bid Amount', 'Status', 'Notice of Award', 'Contract Date', 'PO Reference']);
            foreach ($data['records'] as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['bidding_date'],
                    $row['project_title'],
                    $row['fund_source'],
                    $row['approved_budget_contract'],
                    $row['winning_bidder'],
                    $row['winning_bid_amount'],
                    $row['status'],
                    $row['notice_of_award'],
                    $row['contract_date'],
                    $row['purchase_order_ref']
                ]);
            }
            break;
        case 'sealed_bidding':
            fputcsv($output, ['ID', 'Bidding Date', 'Project Title', 'Fund Source', 'Winning Bidder', 'Winning Bid Amount', 'Status']);
            foreach ($data['records'] as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['bidding_date'],
                    $row['project_title'],
                    $row['fund_source'],
                    $row['winning_bidder'],
                    $row['winning_bid_amount'],
                    $row['status']
                ]);
            }
            break;
        case 'procurement':
            fputcsv($output, ['ID', 'ITB No', 'Particulars', 'ABC', 'Winning Bidder', 'Winning Price', 'Delivery Date (PO)', 'Actual Delivery', 'Remarks']);
            foreach ($data['records'] as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['itb_no'],
                    $row['particulars'],
                    $row['abc'],
                    $row['winning_bidder'],
                    $row['winning_price'],
                    $row['delivery_date_per_po'],
                    $row['actual_delivery_date'],
                    $row['remarks']
                ]);
            }
            break;
        default:
            fputcsv($output, ['Report Type', 'Value']);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($output, [$key, $value]);
            }
    }
    
    fclose($output);
    exit;
}

// Get data for display
$reportData = getReportData($report_type, $start_date, $end_date, $status);

include '../includes/templates/admin-header.php';
?>

<style>
.report-card {
    transition: transform 0.2s;
}
.report-card:hover {
    transform: translateY(-3px);
}
.stat-number {
    font-size: 2rem;
    font-weight: 700;
}
.preview-table {
    max-height: 500px;
    overflow-y: auto;
}
</style>

<div class="admin-reports">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-chart-bar me-2"></i>Generate Reports
        </h1>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print me-2"></i>Print
        </button>
    </div>
    
    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3" id="reportForm">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview Dashboard</option>
                        <option value="public_bidding" <?php echo $report_type == 'public_bidding' ? 'selected' : ''; ?>>Public Bidding</option>
                        <option value="sealed_bidding" <?php echo $report_type == 'sealed_bidding' ? 'selected' : ''; ?>>Sealed Bidding</option>
                        <option value="procurement" <?php echo $report_type == 'procurement' ? 'selected' : ''; ?>>Procurement Monitoring</option>
                    </select>
                </div>
                
                <?php if ($report_type != 'overview'): ?>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="ongoing" <?php echo $status == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="?export=1&report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&status=<?php echo $status; ?>" 
                       class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if ($report_type == 'overview'): ?>
        <!-- Overview Dashboard -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card report-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">Public Bidding</h6>
                                <div class="stat-number"><?php echo number_format($reportData['summary']['total_public'] ?? 0); ?></div>
                            </div>
                            <i class="fas fa-gavel fa-3x opacity-50"></i>
                        </div>
                        <small class="text-white-50">Total ABC: <?php echo formatCurrency($reportData['summary']['total_abc'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card report-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Sealed Bidding</h6>
                                <div class="stat-number"><?php echo number_format($reportData['summary']['total_sealed'] ?? 0); ?></div>
                            </div>
                            <i class="fas fa-lock fa-3x opacity-50"></i>
                        </div>
                        <small>Awarded: <?php echo formatCurrency($reportData['summary']['total_sealed_awarded'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card report-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">Procurement</h6>
                                <div class="stat-number"><?php echo number_format($reportData['summary']['total_procurement'] ?? 0); ?></div>
                            </div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                        <small class="text-white-50">Total ABC: <?php echo formatCurrency($reportData['summary']['total_abc'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card report-card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-white-50">System Users</h6>
                                <div class="stat-number"><?php echo number_format($reportData['summary']['total_users'] ?? 0); ?></div>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                        <small class="text-white-50">Documents: <?php echo number_format($reportData['summary']['total_documents'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Trend Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Monthly Bidding Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Status Distribution -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="reports.php?report_type=public_bidding" class="list-group-item list-group-item-action">
                                <i class="fas fa-gavel me-2"></i> Generate Public Bidding Report
                            </a>
                            <a href="reports.php?report_type=sealed_bidding" class="list-group-item list-group-item-action">
                                <i class="fas fa-lock me-2"></i> Generate Sealed Bidding Report
                            </a>
                            <a href="reports.php?report_type=procurement" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-line me-2"></i> Generate Procurement Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Monthly trend chart
        const monthlyData = <?php 
            $months = [];
            $counts = [];
            $awarded = [];
            foreach ($reportData['monthly_trend'] as $trend) {
                $months[] = date('M Y', strtotime($trend['month'] . '-01'));
                $counts[] = $trend['count'];
                $awarded[] = $trend['total_awarded'];
            }
            echo json_encode(['months' => $months, 'counts' => $counts, 'awarded' => $awarded]);
        ?>;
        
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: monthlyData.months,
                datasets: [
                    {
                        label: 'Number of Bids',
                        data: monthlyData.counts,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Total Awarded (₱ Millions)',
                        data: monthlyData.awarded.map(v => v / 1000000),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw;
                                if (context.dataset.label.includes('Awarded')) {
                                    return label + ': ₱' + value.toFixed(2) + 'M';
                                }
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                scales: {
                    y: { title: { display: true, text: 'Number of Bids' } },
                    y1: { position: 'right', title: { display: true, text: 'Amount (₱ Millions)' } }
                }
            }
        });
        
        // Status chart
        const statusData = <?php 
            $labels = [];
            $counts = [];
            foreach ($reportData['status_distribution'] as $status) {
                $labels[] = ucfirst($status['status']);
                $counts[] = $status['count'];
            }
            echo json_encode(['labels' => $labels, 'counts' => $counts]);
        ?>;
        
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.counts,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d']
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
        </script>
        
    <?php else: ?>
        <!-- Data Table View -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo ucfirst(str_replace('_', ' ', $report_type)); ?> Report
                    <small class="text-muted"><?php echo date('F d, Y', strtotime($start_date)); ?> - <?php echo date('F d, Y', strtotime($end_date)); ?></small>
                </h5>
            </div>
            <div class="card-body">
                <!-- Summary Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="alert alert-info">
                            <strong>Total Records:</strong><br>
                            <?php echo number_format($reportData['summary']['total'] ?? 0); ?>
                        </div>
                    </div>
                    <?php if (isset($reportData['summary']['total_abc'])): ?>
                    <div class="col-md-3">
                        <div class="alert alert-success">
                            <strong>Total ABC:</strong><br>
                            <?php echo formatCurrency($reportData['summary']['total_abc'] ?? 0); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($reportData['summary']['total_awarded'])): ?>
                    <div class="col-md-3">
                        <div class="alert alert-warning">
                            <strong>Total Awarded:</strong><br>
                            <?php echo formatCurrency($reportData['summary']['total_awarded'] ?? 0); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($reportData['summary']['delivered'])): ?>
                    <div class="col-md-3">
                        <div class="alert alert-success">
                            <strong>Delivered:</strong><br>
                            <?php echo number_format($reportData['summary']['delivered'] ?? 0); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Data Table -->
                <div class="table-responsive preview-table">
                    <?php if ($report_type == 'public_bidding' && !empty($reportData['records'])): ?>
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Date</th><th>Project Title</th><th>Fund Source</th><th>ABC</th><th>Winning Bidder</th><th>Winning Bid</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['records'] as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo formatDate($row['bidding_date']); ?></td>
                                        <td class="text-wrap" style="max-width: 300px;"><?php echo htmlspecialchars(substr($row['project_title'], 0, 60)); ?></td>
                                        <td><?php echo htmlspecialchars($row['fund_source']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['approved_budget_contract']); ?></td>
                                        <td><?php echo htmlspecialchars($row['winning_bidder'] ?? '-'); ?></td>
                                        <td class="text-end"><?php echo $row['winning_bid_amount'] ? formatCurrency($row['winning_bid_amount']) : '-'; ?></td>
                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'sealed_bidding' && !empty($reportData['records'])): ?>
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Date</th><th>Project Title</th><th>Fund Source</th><th>Winning Bidder</th><th>Winning Bid</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['records'] as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo formatDate($row['bidding_date']); ?></td>
                                        <td class="text-wrap" style="max-width: 300px;"><?php echo htmlspecialchars(substr($row['project_title'], 0, 60)); ?></td>
                                        <td><?php echo htmlspecialchars($row['fund_source']); ?></td>
                                        <td><?php echo htmlspecialchars($row['winning_bidder'] ?? '-'); ?></td>
                                        <td class="text-end"><?php echo $row['winning_bid_amount'] ? formatCurrency($row['winning_bid_amount']) : '-'; ?></td>
                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'procurement' && !empty($reportData['records'])): ?>
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>ITB No</th><th>Particulars</th><th>ABC</th><th>Winning Bidder</th><th>Winning Price</th><th>Delivery Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['records'] as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['itb_no'] ?? '-'); ?></td>
                                        <td class="text-wrap" style="max-width: 300px;"><?php echo htmlspecialchars(substr($row['particulars'], 0, 60)); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['abc'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($row['winning_bidder'] ?? '-'); ?></td>
                                        <td class="text-end"><?php echo $row['winning_price'] ? formatCurrency($row['winning_price']) : '-'; ?></td>
                                        <td><?php echo $row['actual_delivery_date'] ? 'Delivered' : ($row['delivery_date_per_po'] ? 'Pending' : 'Not Scheduled'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No records found for the selected criteria.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/templates/admin-footer.php'; ?>