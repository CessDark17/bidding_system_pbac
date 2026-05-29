<?php
/**
 * Bidding API Endpoints
 * File: api/bidding.php
 * 
 * Handles all bidding-related operations including public and sealed bidding
 */

require_once 'config.php';

// Additional bidding-specific routes
class BiddingAPI {
    
    /**
     * Get public bidding with filters
     * GET /api/bidding/public
     */
    public static function getPublicBidding() {
        $bidding = new Bidding();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $fund_source = isset($_GET['fund_source']) ? sanitize($_GET['fund_source']) : '';
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        
        $result = $bidding->getPublicBiddingList($page, $limit, $search, $status, $fund_source, $start_date, $end_date);
        APIResponse::success($result, 'Public bidding records retrieved');
    }
    
    /**
     * Get single public bidding record
     * GET /api/bidding/public/:id
     */
    public static function getPublicBiddingById($id) {
        $bidding = new Bidding();
        $record = $bidding->getPublicBiddingById($id);
        
        if ($record) {
            APIResponse::success($record, 'Record retrieved');
        } else {
            APIResponse::error('Record not found', 404);
        }
    }
    
    /**
     * Get public bidding summary/stats
     * GET /api/bidding/public/summary
     */
    public static function getPublicBiddingSummary() {
        $bidding = new Bidding();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        
        $summary = $bidding->getPublicBiddingSummary($year);
        APIResponse::success($summary, 'Summary retrieved');
    }
    
    /**
     * Get sealed bidding (authentication required)
     * GET /api/bidding/sealed
     */
    public static function getSealedBidding() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required for sealed bidding');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload) {
            APIResponse::unauthorized('Invalid or expired token');
        }
        
        $bidding = new Bidding();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        
        $result = $bidding->getSealedBiddingList($page, $limit, $search, $status, $payload['user_id'], $payload['role']);
        APIResponse::success($result, 'Sealed bidding records retrieved');
    }
    
    /**
     * Get single sealed bidding record (authentication required)
     * GET /api/bidding/sealed/:id
     */
    public static function getSealedBiddingById($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required for sealed bidding');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload) {
            APIResponse::unauthorized('Invalid or expired token');
        }
        
        $bidding = new Bidding();
        $record = $bidding->getSealedBiddingById($id, $payload['user_id'], $payload['role']);
        
        if ($record) {
            APIResponse::success($record, 'Record retrieved');
        } else {
            APIResponse::error('Record not found or access denied', 404);
        }
    }
    
    /**
     * Create public bidding record (admin only)
     * POST /api/bidding/public
     */
    public static function createPublicBidding() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['bidding_date', 'project_title', 'fund_source', 'approved_budget_contract'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            APIResponse::validationError($errors);
        }
        
        $data['created_by'] = $payload['user_id'];
        
        $bidding = new Bidding();
        $result = $bidding->createPublicBidding($data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'CREATE_PUBLIC_BIDDING', 'public_bidding', $result['record']['id']);
            APIResponse::success($result['record'], 'Public bidding record created', 201);
        } else {
            APIResponse::error($result['message'], 400, $result['errors'] ?? []);
        }
    }
    
    /**
     * Update public bidding record (admin only)
     * PUT /api/bidding/public/:id
     */
    public static function updatePublicBidding($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $bidding = new Bidding();
        $result = $bidding->updatePublicBidding($id, $data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'UPDATE_PUBLIC_BIDDING', 'public_bidding', $id);
            APIResponse::success($result['record'], 'Public bidding record updated');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Delete public bidding record (admin only)
     * DELETE /api/bidding/public/:id
     */
    public static function deletePublicBidding($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $bidding = new Bidding();
        $result = $bidding->deletePublicBidding($id);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'DELETE_PUBLIC_BIDDING', 'public_bidding', $id);
            APIResponse::success(null, 'Public bidding record deleted');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Create sealed bidding record (admin only)
     * POST /api/bidding/sealed
     */
    public static function createSealedBidding() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['bidding_date', 'project_title', 'fund_source'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            APIResponse::validationError($errors);
        }
        
        $data['created_by'] = $payload['user_id'];
        
        $bidding = new Bidding();
        $result = $bidding->createSealedBidding($data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'CREATE_SEALED_BIDDING', 'sealed_bidding', $result['record']['id']);
            APIResponse::success($result['record'], 'Sealed bidding record created', 201);
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Update sealed bidding record (admin only)
     * PUT /api/bidding/sealed/:id
     */
    public static function updateSealedBidding($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $bidding = new Bidding();
        $result = $bidding->updateSealedBidding($id, $data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'UPDATE_SEALED_BIDDING', 'sealed_bidding', $id);
            APIResponse::success($result['record'], 'Sealed bidding record updated');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Delete sealed bidding record (admin only)
     * DELETE /api/bidding/sealed/:id
     */
    public static function deleteSealedBidding($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $bidding = new Bidding();
        $result = $bidding->deleteSealedBidding($id);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'DELETE_SEALED_BIDDING', 'sealed_bidding', $id);
            APIResponse::success(null, 'Sealed bidding record deleted');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Bulk import bidding records (admin only)
     * POST /api/bidding/bulk-import
     */
    public static function bulkImport() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['records']) || !is_array($data['records'])) {
            APIResponse::validationError(['records' => 'Records array is required']);
        }
        
        $type = $data['type'] ?? 'public';
        $bidding = new Bidding();
        $result = $bidding->bulkImport($data['records'], $type, $payload['user_id']);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'BULK_IMPORT_BIDDING', $type . '_bidding', $result['count']);
            APIResponse::success([
                'imported' => $result['count'],
                'failed' => $result['failed'] ?? 0
            ], 'Bulk import completed');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Export bidding records to CSV (admin only)
     * GET /api/bidding/export
     */
    public static function exportBidding() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $type = isset($_GET['type']) ? sanitize($_GET['type']) : 'public';
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        
        $bidding = new Bidding();
        $csv_data = $bidding->exportToCSV($type, $start_date, $end_date);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bidding_export_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        
        echo $csv_data;
        exit;
    }
}

// Register bidding routes
$router->register('GET', 'bidding/public', [BiddingAPI::class, 'getPublicBidding']);
$router->register('GET', 'bidding/public/summary', [BiddingAPI::class, 'getPublicBiddingSummary']);
$router->register('GET', 'bidding/public/:id', [BiddingAPI::class, 'getPublicBiddingById']);
$router->register('POST', 'bidding/public', [BiddingAPI::class, 'createPublicBidding']);
$router->register('PUT', 'bidding/public/:id', [BiddingAPI::class, 'updatePublicBidding']);
$router->register('DELETE', 'bidding/public/:id', [BiddingAPI::class, 'deletePublicBidding']);

$router->register('GET', 'bidding/sealed', [BiddingAPI::class, 'getSealedBidding']);
$router->register('GET', 'bidding/sealed/:id', [BiddingAPI::class, 'getSealedBiddingById']);
$router->register('POST', 'bidding/sealed', [BiddingAPI::class, 'createSealedBidding']);
$router->register('PUT', 'bidding/sealed/:id', [BiddingAPI::class, 'updateSealedBidding']);
$router->register('DELETE', 'bidding/sealed/:id', [BiddingAPI::class, 'deleteSealedBidding']);

$router->register('POST', 'bidding/bulk-import', [BiddingAPI::class, 'bulkImport']);
$router->register('GET', 'bidding/export', [BiddingAPI::class, 'exportBidding']);

// Dispatch the request
$router->dispatch($method, $request_uri);