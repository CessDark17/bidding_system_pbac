<?php
/**
 * Procurement Class
 * File: includes/classes/Procurement.php
 * 
 * Handles procurement monitoring operations
 */

class Procurement {
    private $db;
    private $table = 'procurement_monitoring';
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Get procurement list with pagination
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search query
     * @return array List with pagination
     */
    public function getList($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = "WHERE 1=1";
        
        if (!empty($search)) {
            $where .= " AND (particulars LIKE ? OR itb_no LIKE ? OR winning_bidder LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} $where";
        $countResult = fetchOne($countSql, $params);
        $total = $countResult['total'] ?? 0;
        
        // Get records
        $sql = "SELECT * FROM {$this->table} $where ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $records = fetchAll($sql, $params);
        
        return [
            'records' => $records,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Get single procurement record
     * @param int $id Record ID
     * @return array|null Record or null
     */
    public function getById($id) {
        return fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }
    
    /**
     * Create procurement record
     * @param array $data Procurement data
     * @return array Result
     */
    public function create($data) {
        $required = ['particulars'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        $recordData = [
            'itb_no' => $data['itb_no'] ?? null,
            'particulars' => $data['particulars'],
            'abc' => $data['abc'] ?? null,
            'bidder_1' => $data['bidder_1'] ?? null,
            'bidder_1_price' => $data['bidder_1_price'] ?? null,
            'bidder_2' => $data['bidder_2'] ?? null,
            'bidder_2_price' => $data['bidder_2_price'] ?? null,
            'bidder_3' => $data['bidder_3'] ?? null,
            'bidder_3_price' => $data['bidder_3_price'] ?? null,
            'bidder_4' => $data['bidder_4'] ?? null,
            'bidder_4_price' => $data['bidder_4_price'] ?? null,
            'bidder_5' => $data['bidder_5'] ?? null,
            'bidder_5_price' => $data['bidder_5_price'] ?? null,
            'winning_bidder' => $data['winning_bidder'] ?? null,
            'winning_price' => $data['winning_price'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'delivery_date_per_po' => $data['delivery_date_per_po'] ?? null,
            'actual_delivery_date' => $data['actual_delivery_date'] ?? null,
            'created_by' => $data['created_by'] ?? null
        ];
        
        $id = insertRecord($this->table, $recordData);
        
        if ($id) {
            $recordData['id'] = $id;
            return ['success' => true, 'message' => 'Record created', 'record' => $recordData];
        }
        
        return ['success' => false, 'message' => 'Failed to create record'];
    }
    
    /**
     * Update procurement record
     * @param int $id Record ID
     * @param array $data Update data
     * @return array Result
     */
    public function update($id, $data) {
        $allowed = [
            'itb_no', 'particulars', 'abc', 'bidder_1', 'bidder_1_price',
            'bidder_2', 'bidder_2_price', 'bidder_3', 'bidder_3_price',
            'bidder_4', 'bidder_4_price', 'bidder_5', 'bidder_5_price',
            'winning_bidder', 'winning_price', 'remarks',
            'delivery_date_per_po', 'actual_delivery_date'
        ];
        
        $updateData = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'message' => 'No data to update'];
        }
        
        $result = updateRecord($this->table, $updateData, 'id = ?', [$id]);
        
        if ($result) {
            $record = $this->getById($id);
            return ['success' => true, 'message' => 'Record updated', 'record' => $record];
        }
        
        return ['success' => false, 'message' => 'Failed to update record'];
    }
    
    /**
     * Delete procurement record
     * @param int $id Record ID
     * @return array Result
     */
    public function delete($id) {
        $result = deleteRecord($this->table, 'id = ?', [$id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Record deleted'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete record'];
    }
    
    /**
     * Get procurement count
     * @return int Number of records
     */
    public function getCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM {$this->table}");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get summary statistics
     * @return array Summary data
     */
    public function getSummary() {
        $summary = fetchOne("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(abc), 0) as total_abc,
                COALESCE(SUM(winning_price), 0) as total_awarded,
                COUNT(CASE WHEN actual_delivery_date IS NOT NULL THEN 1 END) as delivered,
                COUNT(CASE WHEN delivery_date_per_po IS NOT NULL AND actual_delivery_date IS NULL THEN 1 END) as pending_delivery
            FROM {$this->table}
        ");
        
        return $summary;
    }
    
    /**
     * Get delivery performance statistics
     * @return array Performance data
     */
    public function getDeliveryPerformance() {
        $stats = fetchAll("
            SELECT 
                CASE 
                    WHEN actual_delivery_date <= delivery_date_per_po THEN 'On Time'
                    WHEN actual_delivery_date > delivery_date_per_po THEN 'Late'
                    ELSE 'Not Delivered'
                END as status,
                COUNT(*) as count
            FROM {$this->table}
            WHERE delivery_date_per_po IS NOT NULL
            GROUP BY 
                CASE 
                    WHEN actual_delivery_date <= delivery_date_per_po THEN 'On Time'
                    WHEN actual_delivery_date > delivery_date_per_po THEN 'Late'
                    ELSE 'Not Delivered'
                END
        ");
        
        return $stats;
    }
    
    /**
     * Get top winning bidders
     * @param int $limit Number of top bidders
     * @return array Top bidders
     */
    public function getTopBidders($limit = 10) {
        $bidders = fetchAll("
            SELECT 
                winning_bidder,
                COUNT(*) as contract_count,
                COALESCE(SUM(winning_price), 0) as total_amount
            FROM {$this->table}
            WHERE winning_bidder IS NOT NULL AND winning_bidder != ''
            GROUP BY winning_bidder
            ORDER BY total_amount DESC
            LIMIT ?
        ", [$limit]);
        
        return $bidders;
    }
    
    /**
     * Bulk import procurement records
     * @param array $records Array of records to import
     * @param int $userId Current user ID
     * @return array Result
     */
    public function bulkImport($records, $userId = null) {
        if (empty($records) || !is_array($records)) {
            return ['success' => false, 'message' => 'No records to import'];
        }
        
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        
        foreach ($records as $index => $record) {
            $record['created_by'] = $userId;
            $result = $this->create($record);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": " . ($result['message'] ?? 'Unknown error');
            }
        }
        
        return [
            'success' => true,
            'count' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors
        ];
    }
    
    /**
     * Export procurement data to CSV
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return string CSV content
     */
    public function exportToCSV($start_date = '', $end_date = '') {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($start_date && $end_date) {
            $where .= " AND created_at BETWEEN ? AND ?";
            $params[] = $start_date . ' 00:00:00';
            $params[] = $end_date . ' 23:59:59';
        }
        
        $sql = "SELECT * FROM {$this->table} $where ORDER BY id DESC";
        $records = fetchAll($sql, $params);
        
        if (empty($records)) {
            return "";
        }
        
        // Get headers
        $headers = array_keys($records[0]);
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($records as $record) {
            fputcsv($output, $record);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Validate required fields
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array Validation errors
     */
    private function validateRequired($data, $required) {
        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }
}