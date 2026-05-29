<?php
/**
 * Batch Export Handler
 * File: exports/export-batch.php
 * 
 * Handles exporting multiple data types in one file
 */

require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/middleware/auth.php';

// Check admin access
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'csv';
$date_range = isset($_GET['date_range']) ? sanitize($_GET['date_range']) : 'all';
$include_summary = isset($_GET['include_summary']) ? true : false;

// Set filename
$filename = "fibeco_full_export_" . date('Y-m-d');

// Set date range filter
$start_date = '';
$end_date = '';
if ($date_range != 'all') {
    switch ($date_range) {
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'quarter':
            $quarter = ceil(date('n') / 3);
            $start_date = date('Y-' . (($quarter - 1) * 3 + 1) . '-01');
            $end_date = date('Y-' . ($quarter * 3) . '-t');
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            break;
    }
}

// Export based on format
switch ($format) {
    case 'csv':
        exportBatchCSV($filename, $start_date, $end_date, $include_summary);
        break;
    case 'zip':
        exportBatchZip($filename, $start_date, $end_date, $include_summary);
        break;
    default:
        exportBatchCSV($filename, $start_date, $end_date, $include_summary);
}

/**
 * Export all data to a single CSV with multiple sheets (using multiple CSVs in ZIP)
 */
function exportBatchCSV($filename, $start_date, $end_date, $include_summary) {
    // For CSV, we'll output multiple sections in one file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    
    echo "\xEF\xBB\xBF";
    
    // Public Bidding Section
    echo "\n\n\"=== PUBLIC BIDDING ===\"\n";
    $publicData = getExportData('public_bidding', $start_date, $end_date, 'all');
    echo implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $publicData['headers'])) . "\n";
    foreach ($publicData['rows'] as $row) {
        echo implode(',', array_map(function($c) { return '"' . str_replace('"', '""', $c) . '"'; }, $row)) . "\n";
    }
    
    // Sealed Bidding Section
    echo "\n\n\"=== SEALED BIDDING ===\"\n";
    $sealedData = getExportData('sealed_bidding', $start_date, $end_date, 'all');
    echo implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $sealedData['headers'])) . "\n";
    foreach ($sealedData['rows'] as $row) {
        echo implode(',', array_map(function($c) { return '"' . str_replace('"', '""', $c) . '"'; }, $row)) . "\n";
    }
    
    // Procurement Monitoring Section
    echo "\n\n\"=== PROCUREMENT MONITORING ===\"\n";
    $procurementData = getExportData('procurement_monitoring', $start_date, $end_date, 'all');
    echo implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $procurementData['headers'])) . "\n";
    foreach ($procurementData['rows'] as $row) {
        echo implode(',', array_map(function($c) { return '"' . str_replace('"', '""', $c) . '"'; }, $row)) . "\n";
    }
    
    // Summary Section
    if ($include_summary) {
        echo "\n\n\"=== SUMMARY STATISTICS ===\"\n";
        $summaryData = getExportData('summary', '', '', '');
        echo implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $summaryData['headers'])) . "\n";
        foreach ($summaryData['rows'] as $row) {
            echo implode(',', array_map(function($c) { return '"' . str_replace('"', '""', $c) . '"'; }, $row)) . "\n";
        }
    }
    
    exit;
}

/**
 * Export all data as separate files in a ZIP archive
 */
function exportBatchZip($filename, $start_date, $end_date, $include_summary) {
    // Create temporary directory
    $temp_dir = sys_get_temp_dir() . '/fibeco_export_' . uniqid();
    mkdir($temp_dir, 0777, true);
    
    $files = [];
    
    // Export Public Bidding
    $publicData = getExportData('public_bidding', $start_date, $end_date, 'all');
    $publicFile = $temp_dir . '/public_bidding.csv';
    $fp = fopen($publicFile, 'w');
    fputcsv($fp, $publicData['headers']);
    foreach ($publicData['rows'] as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    $files[] = $publicFile;
    
    // Export Sealed Bidding
    $sealedData = getExportData('sealed_bidding', $start_date, $end_date, 'all');
    $sealedFile = $temp_dir . '/sealed_bidding.csv';
    $fp = fopen($sealedFile, 'w');
    fputcsv($fp, $sealedData['headers']);
    foreach ($sealedData['rows'] as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    $files[] = $sealedFile;
    
    // Export Procurement Monitoring
    $procurementData = getExportData('procurement_monitoring', $start_date, $end_date, 'all');
    $procurementFile = $temp_dir . '/procurement_monitoring.csv';
    $fp = fopen($procurementFile, 'w');
    fputcsv($fp, $procurementData['headers']);
    foreach ($procurementData['rows'] as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    $files[] = $procurementFile;
    
    // Export Summary
    if ($include_summary) {
        $summaryData = getExportData('summary', '', '', '');
        $summaryFile = $temp_dir . '/summary.csv';
        $fp = fopen($summaryFile, 'w');
        fputcsv($fp, $summaryData['headers']);
        foreach ($summaryData['rows'] as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $files[] = $summaryFile;
    }
    
    // Create README file
    $readmeFile = $temp_dir . '/README.txt';
    $readmeContent = "FIBECO Bidding System - Data Export\n";
    $readmeContent .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $readmeContent .= "Date Range: " . ($start_date ?: 'All') . " to " . ($end_date ?: 'All') . "\n";
    $readmeContent .= "\nFiles included:\n";
    foreach ($files as $file) {
        $readmeContent .= "- " . basename($file) . "\n";
    }
    $readmeContent .= "\nFor support, contact: admin@fibeco.gov.ph\n";
    file_put_contents($readmeFile, $readmeContent);
    $files[] = $readmeFile;
    
    // Create ZIP file
    $zipFile = $temp_dir . '/' . $filename . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        die("Could not create ZIP file");
    }
    
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    
    // Send ZIP file to browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Pragma: no-cache');
    
    readfile($zipFile);
    
    // Clean up
    foreach ($files as $file) {
        unlink($file);
    }
    unlink($zipFile);
    rmdir($temp_dir);
    
    exit;
}

/**
 * Get export data (reuse from export.php)
 */
function getExportData($type, $start_date, $end_date, $status) {
    global $db;
    
    $where = "WHERE 1=1";
    $params = [];
    
    if ($start_date && $end_date) {
        if ($type == 'public_bidding' || $type == 'sealed_bidding') {
            $where .= " AND bidding_date BETWEEN ? AND ?";
        } else {
            $where .= " AND created_at BETWEEN ? AND ?";
        }
        $params[] = $start_date;
        $params[] = $end_date;
    }
    
    if ($status != 'all') {
        $where .= " AND status = ?";
        $params[] = $status;
    }
    
    switch ($type) {
        case 'public_bidding':
            $sql = "SELECT id, bidding_date, project_title, fund_source, capex_project, 
                           approved_budget_contract, winning_bidder, winning_bid_amount, 
                           status, created_at 
                    FROM public_bidding $where 
                    ORDER BY bidding_date DESC";
            $result = fetchAll($sql, $params);
            
            $headers = ['ID', 'Bidding Date', 'Project Title', 'Fund Source', 'CAPEX', 'ABC', 'Winning Bidder', 'Winning Amount', 'Status', 'Created'];
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
                    $row['status'],
                    $row['created_at']
                ];
            }
            break;
            
        case 'sealed_bidding':
            $sql = "SELECT id, bidding_date, project_title, fund_source, 
                           winning_bidder, winning_bid_amount, status, created_at 
                    FROM sealed_bidding $where 
                    ORDER BY bidding_date DESC";
            $result = fetchAll($sql, $params);
            
            $headers = ['ID', 'Bidding Date', 'Project Title', 'Fund Source', 'Winning Bidder', 'Winning Amount', 'Status', 'Created'];
            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    $row['id'],
                    $row['bidding_date'],
                    $row['project_title'],
                    $row['fund_source'],
                    $row['winning_bidder'] ?? '',
                    $row['winning_bid_amount'] ? number_format($row['winning_bid_amount'], 2) : '',
                    $row['status'],
                    $row['created_at']
                ];
            }
            break;
            
        case 'procurement_monitoring':
            $sql = "SELECT id, itb_no, particulars, abc, winning_bidder, winning_price,
                           delivery_date_per_po, actual_delivery_date, remarks, created_at 
                    FROM procurement_monitoring $where 
                    ORDER BY id DESC";
            $result = fetchAll($sql, $params);
            
            $headers = ['ID', 'ITB No', 'Particulars', 'ABC', 'Winning Bidder', 'Winning Price', 'Delivery Date', 'Actual Delivery', 'Remarks', 'Created'];
            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    $row['id'],
                    $row['itb_no'] ?? '',
                    $row['particulars'],
                    $row['abc'] ? number_format($row['abc'], 2) : '',
                    $row['winning_bidder'] ?? '',
                    $row['winning_price'] ? number_format($row['winning_price'], 2) : '',
                    $row['delivery_date_per_po'] ?? '',
                    $row['actual_delivery_date'] ?? '',
                    $row['remarks'] ?? '',
                    $row['created_at']
                ];
            }
            break;
            
        case 'summary':
        default:
            $public = fetchOne("SELECT COUNT(*) as count, COALESCE(SUM(approved_budget_contract), 0) as total_abc FROM public_bidding");
            $sealed = fetchOne("SELECT COUNT(*) as count FROM sealed_bidding");
            $procurement = fetchOne("SELECT COUNT(*) as count FROM procurement_monitoring");
            $users = fetchOne("SELECT COUNT(*) as count FROM users");
            
            $headers = ['Metric', 'Value'];
            $rows = [
                ['Public Bidding Records', $public['count']],
                ['Public Bidding Total ABC', number_format($public['total_abc'], 2)],
                ['Sealed Bidding Records', $sealed['count']],
                ['Procurement Records', $procurement['count']],
                ['System Users', $users['count']]
            ];
    }
    
    return ['headers' => $headers, 'rows' => $rows];
}