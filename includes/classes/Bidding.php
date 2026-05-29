<?php
/**
 * Bidding Class
 * File: includes/classes/Bidding.php
 * 
 * Handles all bidding-related operations (public and sealed)
 */

class Bidding {
    private $db;
    private $public_table = 'public_bidding';
    private $sealed_table = 'sealed_bidding';
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    // ======================================================
    // PUBLIC BIDDING METHODS
    // ======================================================
    
    /**
     * Get public bidding list with filters
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search query
     * @param string $status Status filter
     * @param string $fund_source Fund source filter
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Bidding list with pagination
     */
    public function getPublicBiddingList($page = 1, $limit = 20, $search = '', $status = '', $fund_source = '', $start_date = '', $end_date = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = "WHERE 1=1";
        
        if (!empty($search)) {
            $where .= " AND (project_title LIKE ? OR winning_bidder LIKE ? OR fund_source LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status) && $status != 'all') {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        if (!empty($fund_source)) {
            $where .= " AND fund_source = ?";
            $params[] = $fund_source;
        }
        
        if (!empty($start_date) && !empty($end_date)) {
            $where .= " AND bidding_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->public_table} $where";
        $countResult = fetchOne($countSql, $params);
        $total = $countResult['total'] ?? 0;
        
        // Get records
        $sql = "SELECT * FROM {$this->public_table} $where ORDER BY bidding_date DESC LIMIT ? OFFSET ?";
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
     * Get single public bidding record
     * @param int $id Record ID
     * @return array|null Record or null
     */
    public function getPublicBiddingById($id) {
        return fetchOne("SELECT * FROM {$this->public_table} WHERE id = ?", [$id]);
    }
    
    /**
     * Create public bidding record
     * @param array $data Bidding data
     * @return array Result
     */
    public function createPublicBidding($data) {
        $required = ['bidding_date', 'project_title', 'fund_source', 'approved_budget_contract'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        $recordData = [
            'bidding_date' => $data['bidding_date'],
            'project_title' => $data['project_title'],
            'fund_source' => $data['fund_source'],
            'capex_project' => $data['capex_project'] ?? null,
            'approved_budget_contract' => $data['approved_budget_contract'],
            'participating_bidders' => $data['participating_bidders'] ?? null,
            'winning_bidder' => $data['winning_bidder'] ?? null,
            'winning_bid_amount' => $data['winning_bid_amount'] ?? null,
            'notice_of_award' => $data['notice_of_award'] ?? null,
            'contract_date' => $data['contract_date'] ?? null,
            'performance_bond_form' => $data['performance_bond_form'] ?? null,
            'performance_bond_amount' => $data['performance_bond_amount'] ?? null,
            'notice_to_proceed' => $data['notice_to_proceed'] ?? null,
            'purchase_order_ref' => $data['purchase_order_ref'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? null
        ];
        
        $id = insertRecord($this->public_table, $recordData);
        
        if ($id) {
            $recordData['id'] = $id;
            return ['success' => true, 'message' => 'Record created', 'record' => $recordData];
        }
        
        return ['success' => false, 'message' => 'Failed to create record'];
    }
    
    /**
     * Update public bidding record
     * @param int $id Record ID
     * @param array $data Update data
     * @return array Result
     */
    public function updatePublicBidding($id, $data) {
        $allowed = [
            'bidding_date', 'project_title', 'fund_source', 'capex_project',
            'approved_budget_contract', 'participating_bidders', 'winning_bidder',
            'winning_bid_amount', 'notice_of_award', 'contract_date',
            'performance_bond_form', 'performance_bond_amount', 'notice_to_proceed',
            'purchase_order_ref', 'status'
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
        
        $result = updateRecord($this->public_table, $updateData, 'id = ?', [$id]);
        
        if ($result) {
            $record = $this->getPublicBiddingById($id);
            return ['success' => true, 'message' => 'Record updated', 'record' => $record];
        }
        
        return ['success' => false, 'message' => 'Failed to update record'];
    }
    
    /**
     * Delete public bidding record
     * @param int $id Record ID
     * @return array Result
     */
    public function deletePublicBidding($id) {
        $result = deleteRecord($this->public_table, 'id = ?', [$id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Record deleted'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete record'];
    }
    
    /**
     * Get public bidding count
     * @return int Number of records
     */
    public function getPublicCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM {$this->public_table}");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get total ABC amount
     * @return float Total ABC
     */
    public function getTotalABC() {
        $result = fetchOne("SELECT COALESCE(SUM(approved_budget_contract), 0) as total FROM {$this->public_table}");
        return $result['total'] ?? 0;
    }
    
    /**
     * Get total awarded amount
     * @return float Total awarded
     */
    public function getTotalAwarded() {
        $result = fetchOne("SELECT COALESCE(SUM(winning_bid_amount), 0) as total FROM {$this->public_table}");
        return $result['total'] ?? 0;
    }
    
    /**
     * Get public bidding summary by year
     * @param int $year Year
     * @return array Summary data
     */
    public function getPublicBiddingSummary($year = null) {
        $year = $year ?: date('Y');
        
        $monthly = fetchAll("
            SELECT 
                MONTH(bidding_date) as month,
                COUNT(*) as count,
                COALESCE(SUM(approved_budget_contract), 0) as total_abc,
                COALESCE(SUM(winning_bid_amount), 0) as total_awarded
            FROM {$this->public_table}
            WHERE YEAR(bidding_date) = ?
            GROUP BY MONTH(bidding_date)
            ORDER BY month ASC
        ", [$year]);
        
        $status = fetchAll("
            SELECT status, COUNT(*) as count
            FROM {$this->public_table}
            GROUP BY status
        ");
        
        $total = fetchOne("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(approved_budget_contract), 0) as total_abc,
                COALESCE(SUM(winning_bid_amount), 0) as total_awarded
            FROM {$this->public_table}
            WHERE YEAR(bidding_date) = ?
        ", [$year]);
        
        return [
            'year' => $year,
            'monthly' => $monthly,
            'status_distribution' => $status,
            'totals' => $total
        ];
    }
    
    // ======================================================
    // SEALED BIDDING METHODS
    // ======================================================
    
    /**
     * Get sealed bidding list (requires authentication)
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search query
     * @param string $status Status filter
     * @param int $userId Current user ID
     * @param string $userRole User role
     * @return array Bidding list with pagination
     */
    public function getSealedBiddingList($page = 1, $limit = 20, $search = '', $status = '', $userId = null, $userRole = 'user') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = "WHERE 1=1";
        
        if (!empty($search)) {
            $where .= " AND (project_title LIKE ? OR winning_bidder LIKE ? OR fund_source LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status) && $status != 'all') {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->sealed_table} $where";
        $countResult = fetchOne($countSql, $params);
        $total = $countResult['total'] ?? 0;
        
        // Get records (exclude confidential notes for non-admin)
        $selectFields = ($userRole === 'admin') 
            ? "*" 
            : "id, bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status, created_at";
        
        $sql = "SELECT $selectFields FROM {$this->sealed_table} $where ORDER BY bidding_date DESC LIMIT ? OFFSET ?";
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
     * Get single sealed bidding record (with access control)
     * @param int $id Record ID
     * @param int $userId Current user ID
     * @param string $userRole User role
     * @return array|null Record or null
     */
    public function getSealedBiddingById($id, $userId = null, $userRole = 'user') {
        $record = fetchOne("SELECT * FROM {$this->sealed_table} WHERE id = ?", [$id]);
        
        if ($record && $userRole !== 'admin') {
            // Remove confidential notes for non-admin
            unset($record['confidential_notes']);
        }
        
        return $record;
    }
    
    /**
     * Create sealed bidding record
     * @param array $data Bidding data
     * @return array Result
     */
    public function createSealedBidding($data) {
        $required = ['bidding_date', 'project_title', 'fund_source'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        $recordData = [
            'bidding_date' => $data['bidding_date'],
            'project_title' => $data['project_title'],
            'fund_source' => $data['fund_source'],
            'participating_bidders' => $data['participating_bidders'] ?? null,
            'winning_bidder' => $data['winning_bidder'] ?? null,
            'winning_bid_amount' => $data['winning_bid_amount'] ?? null,
            'contract_or_po_ref' => $data['contract_or_po_ref'] ?? null,
            'confidential_notes' => $data['confidential_notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? null
        ];
        
        $id = insertRecord($this->sealed_table, $recordData);
        
        if ($id) {
            $recordData['id'] = $id;
            return ['success' => true, 'message' => 'Record created', 'record' => $recordData];
        }
        
        return ['success' => false, 'message' => 'Failed to create record'];
    }
    
    /**
     * Update sealed bidding record
     * @param int $id Record ID
     * @param array $data Update data
     * @return array Result
     */
    public function updateSealedBidding($id, $data) {
        $allowed = [
            'bidding_date', 'project_title', 'fund_source', 'participating_bidders',
            'winning_bidder', 'winning_bid_amount', 'contract_or_po_ref',
            'confidential_notes', 'status'
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
        
        $result = updateRecord($this->sealed_table, $updateData, 'id = ?', [$id]);
        
        if ($result) {
            $record = $this->getSealedBiddingById($id);
            return ['success' => true, 'message' => 'Record updated', 'record' => $record];
        }
        
        return ['success' => false, 'message' => 'Failed to update record'];
    }
    
    /**
     * Delete sealed bidding record
     * @param int $id Record ID
     * @return array Result
     */
    public function deleteSealedBidding($id) {
        $result = deleteRecord($this->sealed_table, 'id = ?', [$id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Record deleted'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete record'];
    }
    
    /**
     * Get sealed bidding count
     * @return int Number of records
     */
    public function getSealedCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM {$this->sealed_table}");
        return $result['count'] ?? 0;
    }
    
    // ======================================================
    // SHARED METHODS
    // ======================================================
    
    /**
     * Get monthly statistics for both bidding types
     * @param int $year Year
     * @return array Monthly stats
     */
    public function getMonthlyStats($year = null) {
        $year = $year ?: date('Y');
        
        $public = fetchAll("
            SELECT 
                MONTH(bidding_date) as month,
                COUNT(*) as count,
                COALESCE(SUM(approved_budget_contract), 0) as total_abc,
                COALESCE(SUM(winning_bid_amount), 0) as total_awarded
            FROM {$this->public_table}
            WHERE YEAR(bidding_date) = ?
            GROUP BY MONTH(bidding_date)
        ", [$year]);
        
        $sealed = fetchAll("
            SELECT 
                MONTH(bidding_date) as month,
                COUNT(*) as count,
                COALESCE(SUM(winning_bid_amount), 0) as total_awarded
            FROM {$this->sealed_table}
            WHERE YEAR(bidding_date) = ?
            GROUP BY MONTH(bidding_date)
        ", [$year]);
        
        // Combine results
        $months = range(1, 12);
        $result = [];
        
        foreach ($months as $month) {
            $publicMonth = $this->findMonthData($public, $month);
            $sealedMonth = $this->findMonthData($sealed, $month);
            
            $result[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'public_count' => $publicMonth['count'] ?? 0,
                'public_abc' => $publicMonth['total_abc'] ?? 0,
                'public_awarded' => $publicMonth['total_awarded'] ?? 0,
                'sealed_count' => $sealedMonth['count'] ?? 0,
                'sealed_awarded' => $sealedMonth['total_awarded'] ?? 0
            ];
        }
        
        return $result;
    }
    
    /**
     * Bulk import bidding records
     * @param array $records Array of records to import
     * @param string $type 'public' or 'sealed'
     * @param int $userId Current user ID
     * @return array Result
     */
    public function bulkImport($records, $type = 'public', $userId = null) {
        if (empty($records) || !is_array($records)) {
            return ['success' => false, 'message' => 'No records to import'];
        }
        
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        
        foreach ($records as $index => $record) {
            $record['created_by'] = $userId;
            
            if ($type === 'public') {
                $result = $this->createPublicBidding($record);
            } else {
                $result = $this->createSealedBidding($record);
            }
            
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
     * Export bidding data to CSV string
     * @param string $type 'public' or 'sealed'
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return string CSV content
     */
    public function exportToCSV($type = 'public', $start_date = '', $end_date = '') {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($start_date && $end_date) {
            $where .= " AND bidding_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        $table = ($type === 'public') ? $this->public_table : $this->sealed_table;
        $sql = "SELECT * FROM $table $where ORDER BY bidding_date DESC";
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
     * Get pending documents count
     * @return int Number of pending documents
     */
    public function getPendingDocumentsCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM uploaded_documents WHERE upload_status IN ('pending', 'processing', 'extracted')");
        return $result['count'] ?? 0;
    }
    
    // ======================================================
    // HELPER METHODS
    // ======================================================
    
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
    
    /**
     * Find month data in array
     * @param array $data Array of month data
     * @param int $month Month number
     * @return array|null Month data or null
     */
    private function findMonthData($data, $month) {
        foreach ($data as $item) {
            if ($item['month'] == $month) {
                return $item;
            }
        }
        return null;
    }
}