<?php
/**
 * Admin Dashboard
 * File: admin/index.php
 * 
 * Main administration dashboard with statistics and overview
 */

// Require admin access
require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';

$pageTitle = 'Admin Dashboard';

// Get dashboard statistics
$stats = [];

// Total public bidding records
$result = fetchOne("SELECT COUNT(*) as count FROM public_bidding");
$stats['public_bidding'] = $result['count'] ?? 0;

// Total sealed bidding records
$result = fetchOne("SELECT COUNT(*) as count FROM sealed_bidding");
$stats['sealed_bidding'] = $result['count'] ?? 0;

// Total procurement monitoring records
$result = fetchOne("SELECT COUNT(*) as count FROM procurement_monitoring");
$stats['procurement_monitoring'] = $result['count'] ?? 0;

// Total users
$result = fetchOne("SELECT COUNT(*) as count FROM users");
$stats['users'] = $result['count'] ?? 0;

// Total uploaded documents
$result = fetchOne("SELECT COUNT(*) as count FROM uploaded_documents");
$stats['documents'] = $result['count'] ?? 0;

// Recent activity
$recentActivities = fetchAll("
    SELECT * FROM activity_logs 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Bidding by status
$biddingByStatus = fetchAll("
    SELECT status, COUNT(*) as count 
    FROM public_bidding 
    GROUP BY status
");

// Monthly bidding activity (last 12 months)
$monthlyActivity = fetchAll("
    SELECT DATE_FORMAT(bidding_date, '%Y-%m') as month, 
           COUNT(*) as count,
           SUM(winning_bid_amount) as total_amount
    FROM public_bidding 
    WHERE bidding_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(bidding_date, '%Y-%m')
    ORDER BY month DESC
");

// Recent uploads pending review
$pendingUploads = fetchAll("
    SELECT * FROM uploaded_documents 
    WHERE upload_status IN ('pending', 'processing', 'extracted')
    ORDER BY created_at DESC 
    LIMIT 5
");

include '../includes/templates/admin-header.php';
?>

<div class="admin-dashboard">
    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card bg-primary text-white">
                <div class="stat-icon">
                    <i class="fas fa-gavel fa-3x"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['public_bidding']); ?></h3>
                    <p class="stat-label">Public Bidding Records</p>
                </div>
                <div class="stat-footer">
                    <a href="public-bidding.php" class="text-white">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-card bg-warning text-dark">
                <div class="stat-icon">
                    <i class="fas fa-lock fa-3x"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['sealed_bidding']); ?></h3>
                    <p class="stat-label">Sealed Bidding Records</p>
                </div>
                <div class="stat-footer">
                    <a href="sealed-bidding.php" class="text-dark">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-card bg-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['procurement_monitoring']); ?></h3>
                    <p class="stat-label">Procurement Monitoring</p>
                </div>
                <div class="stat-footer">
                    <a href="procurement-monitoring.php" class="text-white">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="stat-card bg-info text-white">
                <div class="stat-icon">
                    <i class="fas fa-users fa-3x"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['users']); ?></h3>
                    <p class="stat-label">System Users</p>
                </div>
                <div class="stat-footer">
                    <a href="users.php" class="text-white">
                        Manage <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Bidding by Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Monthly Activity
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <a href="upload.php" class="quick-action-card text-center d-block p-3 rounded border">
                                <i class="fas fa-upload fa-2x text-primary mb-2"></i>
                                <h6 class="mb-0">Upload Documents</h6>
                                <small class="text-muted">Import bidding files</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="public-bidding.php?action=add" class="quick-action-card text-center d-block p-3 rounded border">
                                <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                                <h6 class="mb-0">Add Public Bidding</h6>
                                <small class="text-muted">Manual entry</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="sealed-bidding.php?action=add" class="quick-action-card text-center d-block p-3 rounded border">
                                <i class="fas fa-plus-circle fa-2x text-warning mb-2"></i>
                                <h6 class="mb-0">Add Sealed Bidding</h6>
                                <small class="text-muted">Restricted access</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="reports.php" class="quick-action-card text-center d-block p-3 rounded border">
                                <i class="fas fa-chart-bar fa-2x text-info mb-2"></i>
                                <h6 class="mb-0">Generate Reports</h6>
                                <small class="text-muted">Export data</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity and Pending Uploads -->
    <div class="row g-4">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentActivities)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-inbox text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No recent activity</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td class="text-nowrap">
                                                <small><?php echo formatDate($activity['created_at'], 'M d, H:i'); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['user_id'] ?? 'System'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($activity['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['entity_type'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-upload me-2"></i>Pending Document Reviews
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($pendingUploads)): ?>
                            <div class="list-group-item text-center py-4">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <p class="text-muted mb-0">No pending uploads</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingUploads as $upload): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <strong><?php echo htmlspecialchars(substr($upload['original_filename'], 0, 40)); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo ucfirst($upload['document_type']); ?> | 
                                                Status: <?php echo $upload['upload_status']; ?>
                                            </small>
                                        </div>
                                        <a href="review-extraction.php?id=<?php echo $upload['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Review
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php 
    $labels = [];
    $data = [];
    foreach ($biddingByStatus as $status) {
        $labels[] = ucfirst($status['status']);
        $data[] = $status['count'];
    }
    echo json_encode(['labels' => $labels, 'data' => $data]);
?>;

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.labels,
        datasets: [{
            data: statusData.data,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Activity Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyData = <?php 
    $months = [];
    $counts = [];
    $amounts = [];
    foreach (array_reverse($monthlyActivity) as $activity) {
        $months[] = date('M Y', strtotime($activity['month'] . '-01'));
        $counts[] = $activity['count'];
        $amounts[] = $activity['total_amount'] ?? 0;
    }
    echo json_encode(['months' => $months, 'counts' => $counts, 'amounts' => $amounts]);
?>;

new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.months,
        datasets: [{
            label: 'Number of Bids',
            data: monthlyData.counts,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top'
            }
        }
    }
});
</script>

<style>
.stat-card {
    border-radius: 1rem;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    position: absolute;
    right: 1rem;
    top: 1rem;
    opacity: 0.3;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    margin-bottom: 0;
    opacity: 0.9;
}

.stat-footer {
    margin-top: 1rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.stat-footer a {
    text-decoration: none;
    font-size: 0.875rem;
}

.quick-action-card {
    text-decoration: none;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.quick-action-card:hover {
    background-color: #e9ecef;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<?php include '../includes/templates/admin-footer.php'; ?>