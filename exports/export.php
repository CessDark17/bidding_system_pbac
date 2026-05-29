<?php
/**
 * Export Handler
 * File: exports/export.php
 * 
 * Handles data export to various formats (CSV, Excel, PDF)
 */

require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/middleware/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get export parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'public_bidding';
$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'csv';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Set filename
$filename = "fibeco_{$type}_" . date('Y-m-d_H-i-s');

// Export based on format
switch ($format) {
    case 'csv':
        exportCSV($type, $filename, $start_date, $end_date, $status);
        break;
    case 'excel':
        exportExcel($type, $filename, $start_date, $end_date, $status);
        break;
    case 'pdf':
        exportPDF($type, $filename, $start_date, $end_date, $status);
        break;
    default:
        exportCSV($type, $filename, $start_date, $end_date, $status);
}

/**
 * Export to CSV format
 */
function exportCSV($type, $filename, $start_date, $end_date, $status) {
    global $db;
    
    $data = getExportData($type, $start_date, $end_date, $status);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    
    // Add UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $data['headers']);
    
    // Write data rows
    foreach ($data['rows'] as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export to Excel format (using simple HTML table)
 */
function exportExcel($type, $filename, $start_date, $end_date, $status) {
    $data = getExportData($type, $start_date, $end_date, $status);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>FIBECO Bidding System Export</title>';
    echo '<style>';
    echo 'th { background-color: #0d6efd; color: white; padding: 8px; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h2>FIBECO Bidding System - ' . ucfirst(str_replace('_', ' ', $type)) . ' Report</h2>';
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    
    // Headers
    echo '<tr>';
    foreach ($data['headers'] as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Data rows
    foreach ($data['rows'] as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export to PDF format
 */
function exportPDF($type, $filename, $start_date, $end_date, $status) {
    $data = getExportData($type, $start_date, $end_date, $status);
    
    // Use HTML to PDF conversion (requires additional library like dompdf)
    // For now, we'll output HTML that can be printed to PDF
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>FIBECO Bidding System Export</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'h1 { color: #0d6efd; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th { background-color: #0d6efd; color: white; padding: 10px; text-align: left; }';
    echo 'td { padding: 8px; border-bottom: 1px solid #ddd; }';
    echo 'tr:hover { background-color: #f5f5f5; }';
    echo '.footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }';
    echo '@media print { body { margin: 0; } .no-print { display: none; } }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<button class="no-print" onclick="window.print()" style="padding: 10px 20px; margin-bottom: 20px; cursor: pointer;">Print / Save as PDF</button>';
    echo '<h1>FIBECO Bidding System</h1>';
    echo '<h2>' . ucfirst(str_replace('_', ' ', $type)) . ' Report</h2>';
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo '<table>';
    echo '<thead><tr>';
    foreach ($data['headers'] as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr></thead><tbody>';
    
    foreach ($data['rows'] as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<div class="footer">';
    echo 'First Bukidnon Electric Cooperative, Inc. (FIBECO) - ' . COMPANY_ADDRESS;
    echo '</div>';
    echo '</body></html>';
    exit;
}

/**
 * Get export data based on type
 */
function getExportData($type, $start_date, $end_date, $status) {
    global $db;
    
    $where = "WHERE 1=1";
    $params = [];
    
    if ($start_date && $end_date) {
        $where .= " AND created_at BETWEEN ? AND ?";
        $params[] = $start_date . ' 00:00:00';
        $params[] = $end_date . ' 23:59:59';
    }
    
    if ($status != 'all') {
        $where .= " AND status = ?";
        $params[] = $status;
    }
    
    switch ($type) {
        case 'public_bidding':
            $sql = "SELECT id, bidding_date, project_title, fund_source, capex_project, 
                           approved_budget_contract, winning_bidder, winning_bid_amount, 
                           participating_bidders, status, notice_of_award, contract_date, 
                           purchase_order_ref, created_at 
                    FROM public_bidding $where 
                    ORDER BY bidding_date DESC";
            $result = fetchAll($sql, $params);
            
            $headers = [
                'ID', 'Bidding Date', 'Project Title', 'Fund Source', 'CAPEX Project',
                'ABC (₱)', 'Winning Bidder', 'Winning Bid Amount (₱)', 'Participating Bidders',
                'Status', 'Notice of Award', 'Contract Date', 'PO Reference', 'Date Created'
            ];
            
            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    $row['id'],
                    $row['bidding_date'],
                    $row['project_title'],
                    $row['fund_source'],
                    $row['capex_project'] ?? '',
                    number_format($row['approved_budget_contract'], 2),
                    $row['winning_bidder'] ?? '',
                    $row['winning_bid_amount'] ? number_format($row['winning_bid_amount'], 2) : '',
                    $row['participating_bidders'] ?? '',
                    $row['status'],
                    $row['notice_of_award'] ?? '',
                    $row['contract_date'] ?? '',
                    $row['purchase_order_ref'] ?? '',
                    $row['created_at']
                ];
            }
            break;
            
        case 'sealed_bidding':
            $sql = "SELECT id, bidding_date, project_title, fund_source, 
                           winning_bidder, winning_bid_amount, participating_bidders,
                           contract_or_po_ref, status, created_at 
                    FROM sealed_bidding $where 
                    ORDER BY bidding_date DESC";
            $result = fetchAll($sql, $params);
            
            $headers = [
                'ID', 'Bidding Date', 'Project Title', 'Fund Source',
                'Winning Bidder', 'Winning Bid Amount (₱)', 'Participating Bidders',
                'Contract/PO Reference', 'Status', 'Date Created'
            ];
            
            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    $row['id'],
                    $row['bidding_date'],
                    $row['project_title'],
                    $row['fund_source'],
                    $row['winning_bidder'] ?? '',
                    $row['winning_bid_amount'] ? number_format($row['winning_bid_amount'], 2) : '',
                    $row['participating_bidders'] ?? '',
                    $row['contract_or_po_ref'] ?? '',
                    $row['status'],
                    $row['created_at']
                ];
            }
            break;
            
        case 'procurement_monitoring':
            $sql = "SELECT id, itb_no, particulars, abc, winning_bidder, winning_price,
                           bidder_1, bidder_1_price, bidder_2, bidder_2_price, 
                           bidder_3, bidder_3_price, delivery_date_per_po, actual_delivery_date,
                           remarks, created_at 
                    FROM procurement_monitoring $where 
                    ORDER BY id DESC";
            $result = fetchAll($sql, $params);
            
            $headers = [
                'ID', 'ITB No', 'Particulars', 'ABC (₱)', 'Winning Bidder', 'Winning Price (₱)',
                'Bidder 1', 'Bidder 1 Price', 'Bidder 2', 'Bidder 2 Price',
                'Bidder 3', 'Bidder 3 Price', 'Delivery Date (PO)', 'Actual Delivery',
                'Remarks', 'Date Created'
            ];
            
            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    $row['id'],
                    $row['itb_no'] ?? '',
                    $row['particulars'],
                    $row['abc'] ? number_format($row['abc'], 2) : '',
                    $row['winning_bidder'] ?? '',
                    $row['winning_price'] ? number_format($row['winning_price'], 2) : '',
                    $row['bidder_1'] ?? '',
                    $row['bidder_1_price'] ? number_format($row['bidder_1_price'], 2) : '',
                    $row['bidder_2'] ?? '',
                    $row['bidder_2_price'] ? number_format($row['bidder_2_price'], 2) : '',
                    $row['bidder_3'] ?? '',
                    $row['bidder_3_price'] ? number_format($row['bidder_3_price'], 2) : '',
                    $row['delivery_date_per_po'] ?? '',
                    $row['actual_delivery_date'] ?? '',
                    $row['remarks'] ?? '',
                    $row['created_at']
                ];
            }
            break;
            
        case 'summary':
            // Get summary statistics
            $summary = [];
            
            // Public bidding stats
            $public = fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(approved_budget_contract), 0) as total_abc, 
                                       COALESCE(SUM(winning_bid_amount), 0) as total_awarded 
                                FROM public_bidding");
            
            // Sealed bidding stats
            $sealed = fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(winning_bid_amount), 0) as total_awarded 
                                FROM sealed_bidding");
            
            // Procurement stats
            $procurement = fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(abc), 0) as total_abc,
                                           COALESCE(SUM(winning_price), 0) as total_awarded 
                                    FROM procurement_monitoring");
            
            // User stats
            $users = fetchOne("SELECT COUNT(*) as count FROM users");
            
            $headers = ['Metric', 'Value'];
            $rows = [
                ['Public Bidding - Total Records', $public['count']],
                ['Public Bidding - Total ABC', number_format($public['total_abc'], 2)],
                ['Public Bidding - Total Awarded', number_format($public['total_awarded'], 2)],
                ['Sealed Bidding - Total Records', $sealed['count']],
                ['Sealed Bidding - Total Awarded', number_format($sealed['total_awarded'], 2)],
                ['Procurement - Total Records', $procurement['count']],
                ['Procurement - Total ABC', number_format($procurement['total_abc'], 2)],
                ['Procurement - Total Awarded', number_format($procurement['total_awarded'], 2)],
                ['Total System Users', $users['count']],
                ['Report Generated', date('Y-m-d H:i:s')]
            ];
            break;
            
        default:
            $headers = ['Error', 'Invalid export type'];
            $rows = [];
    }
    
    return ['headers' => $headers, 'rows' => $rows];
}