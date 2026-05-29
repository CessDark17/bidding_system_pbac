<?php
/**
 * File Upload API Endpoints
 * File: api/upload.php
 * 
 * Handles file uploads, extraction, and processing
 */

require_once 'config.php';
require_once '../includes/classes/FileUploader.php';
require_once '../includes/classes/DataExtractor.php';
require_once '../includes/classes/ExcelImporter.php';

// Additional upload-specific routes
class UploadAPI {
    
    /**
     * Upload single file with extraction
     * POST /api/upload
     */
    public static function uploadFile() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        if (!isset($_FILES['file'])) {
            APIResponse::validationError(['file' => 'No file uploaded']);
        }
        
        $document_type = isset($_POST['document_type']) ? sanitize($_POST['document_type']) : 'public_bidding';
        $auto_import = isset($_POST['auto_import']) && $_POST['auto_import'] == '1';
        
        $uploader = new FileUploader();
        $result = $uploader->uploadFile($_FILES['file'], $document_type, $payload['user_id']);
        
        if (!$result['success']) {
            APIResponse::error($result['errors'][0] ?? 'Upload failed', 400);
        }
        
        // Extract data from uploaded file
        $extractor = new DataExtractor();
        $extracted = $extractor->extractFromDocument(
            $result['document_id'],
            $result['file_path'],
            $_FILES['file']['type']
        );
        
        $response = [
            'document_id' => $result['document_id'],
            'filename' => $_FILES['file']['name'],
            'file_size' => $_FILES['file']['size'],
            'document_type' => $document_type,
            'extracted_data' => $extracted,
            'confidence_score' => $extracted['confidence_score'] ?? 0
        ];
        
        // Auto-import if enabled and confidence is high
        if ($auto_import && ($extracted['confidence_score'] ?? 0) >= 80) {
            $importer = new ExcelImporter();
            $import_result = $importer->importFromDocument($result['document_id'], $document_type);
            
            if ($import_result['success']) {
                $response['auto_imported'] = true;
                $response['record_id'] = $import_result['record_id'];
            } else {
                $response['auto_imported'] = false;
                $response['import_error'] = $import_result['error'] ?? 'Import failed';
            }
        }
        
        logActivity($payload['user_id'], 'UPLOAD_DOCUMENT', 'uploaded_documents', $result['document_id']);
        APIResponse::success($response, 'File uploaded and processed successfully');
    }
    
    /**
     * Upload multiple files (batch)
     * POST /api/upload/batch
     */
    public static function uploadBatch() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        if (!isset($_FILES['files'])) {
            APIResponse::validationError(['files' => 'No files uploaded']);
        }
        
        $document_type = isset($_POST['document_type']) ? sanitize($_POST['document_type']) : 'public_bidding';
        $auto_import = isset($_POST['auto_import']) && $_POST['auto_import'] == '1';
        
        $uploader = new FileUploader();
        $extractor = new DataExtractor();
        
        $results = [];
        $success_count = 0;
        $fail_count = 0;
        
        // Handle multiple files with same name pattern
        $files = $_FILES['files'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $uploader->uploadFile($file, $document_type, $payload['user_id']);
            
            if ($result['success']) {
                $extracted = $extractor->extractFromDocument(
                    $result['document_id'],
                    $result['file_path'],
                    $file['type']
                );
                
                $success_count++;
                $results[] = [
                    'filename' => $file['name'],
                    'document_id' => $result['document_id'],
                    'status' => 'success',
                    'extracted' => !empty($extracted)
                ];
                
                // Auto-import if enabled
                if ($auto_import && ($extracted['confidence_score'] ?? 0) >= 80) {
                    $importer = new ExcelImporter();
                    $import_result = $importer->importFromDocument($result['document_id'], $document_type);
                    $results[count($results) - 1]['auto_imported'] = $import_result['success'] ?? false;
                }
            } else {
                $fail_count++;
                $results[] = [
                    'filename' => $file['name'],
                    'status' => 'failed',
                    'error' => $result['errors'][0] ?? 'Upload failed'
                ];
            }
        }
        
        logActivity($payload['user_id'], 'BATCH_UPLOAD', 'uploaded_documents', $success_count);
        
        APIResponse::success([
            'total' => $file_count,
            'success' => $success_count,
            'failed' => $fail_count,
            'results' => $results
        ], 'Batch upload completed');
    }
    
    /**
     * Get upload status
     * GET /api/upload/status/:id
     */
    public static function getUploadStatus($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $sql = "SELECT * FROM uploaded_documents WHERE id = ?";
        $document = fetchOne($sql, [$id]);
        
        if (!$document) {
            APIResponse::error('Document not found', 404);
        }
        
        $response = [
            'id' => $document['id'],
            'filename' => $document['original_filename'],
            'document_type' => $document['document_type'],
            'status' => $document['upload_status'],
            'confidence_score' => $document['confidence_score'],
            'extracted_data' => json_decode($document['extracted_data'] ?? '{}', true),
            'created_at' => $document['created_at'],
            'processed_at' => $document['processed_at']
        ];
        
        if ($document['linked_record_id']) {
            $response['linked_record_id'] = $document['linked_record_id'];
        }
        
        if ($document['error_message']) {
            $response['error_message'] = $document['error_message'];
        }
        
        APIResponse::success($response, 'Upload status retrieved');
    }
    
    /**
     * Get pending uploads for review
     * GET /api/upload/pending
     */
    public static function getPendingUploads() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
        
        $where = "upload_status IN ('extracted', 'processing')";
        $params = [];
        
        if ($type) {
            $where .= " AND document_type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT * FROM uploaded_documents WHERE $where ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $uploads = fetchAll($sql, $params);
        
        foreach ($uploads as &$upload) {
            $upload['extracted_data'] = json_decode($upload['extracted_data'] ?? '{}', true);
        }
        
        APIResponse::success($uploads, 'Pending uploads retrieved');
    }
    
    /**
     * Update upload status (approve/reject)
     * PUT /api/upload/:id/status
     */
    public static function updateUploadStatus($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['status'])) {
            APIResponse::validationError(['status' => 'Status is required']);
        }
        
        $allowed_statuses = ['reviewed', 'imported', 'failed'];
        if (!in_array($data['status'], $allowed_statuses)) {
            APIResponse::validationError(['status' => 'Invalid status value']);
        }
        
        $update_data = ['upload_status' => $data['status']];
        
        if ($data['status'] === 'failed' && isset($data['error_message'])) {
            $update_data['error_message'] = $data['error_message'];
        }
        
        if ($data['status'] === 'imported' && isset($data['record_id'])) {
            $update_data['linked_record_id'] = $data['record_id'];
        }
        
        $result = updateRecord('uploaded_documents', $update_data, 'id = ?', [$id]);
        
        if ($result) {
            logActivity($payload['user_id'], 'UPDATE_UPLOAD_STATUS', 'uploaded_documents', $id);
            APIResponse::success(null, 'Upload status updated');
        } else {
            APIResponse::error('Failed to update status', 400);
        }
    }
    
    /**
     * Delete uploaded file
     * DELETE /api/upload/:id
     */
    public static function deleteUpload($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        // Get file path first
        $sql = "SELECT file_path FROM uploaded_documents WHERE id = ?";
        $document = fetchOne($sql, [$id]);
        
        if ($document) {
            // Delete physical file
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            
            // Delete database record
            $result = deleteRecord('uploaded_documents', 'id = ?', [$id]);
            
            if ($result) {
                logActivity($payload['user_id'], 'DELETE_UPLOAD', 'uploaded_documents', $id);
                APIResponse::success(null, 'Upload deleted successfully');
            } else {
                APIResponse::error('Failed to delete record', 400);
            }
        } else {
            APIResponse::error('Document not found', 404);
        }
    }
    
    /**
     * Get upload statistics
     * GET /api/upload/stats
     */
    public static function getUploadStats() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('Authentication required');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $stats = [];
        
        // Total uploads
        $result = fetchOne("SELECT COUNT(*) as total FROM uploaded_documents");
        $stats['total_uploads'] = $result['total'] ?? 0;
        
        // By status
        $result = fetchAll("SELECT upload_status, COUNT(*) as count FROM uploaded_documents GROUP BY upload_status");
        $stats['by_status'] = $result;
        
        // By document type
        $result = fetchAll("SELECT document_type, COUNT(*) as count FROM uploaded_documents GROUP BY document_type");
        $stats['by_type'] = $result;
        
        // Recent uploads (last 30 days)
        $result = fetchOne("SELECT COUNT(*) as count FROM uploaded_documents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent_30_days'] = $result['count'] ?? 0;
        
        // Average confidence score
        $result = fetchOne("SELECT AVG(confidence_score) as avg_confidence FROM uploaded_documents WHERE confidence_score IS NOT NULL");
        $stats['avg_confidence'] = round($result['avg_confidence'] ?? 0, 2);
        
        APIResponse::success($stats, 'Upload statistics retrieved');
    }
}

// Register upload routes
$router->register('POST', 'upload', [UploadAPI::class, 'uploadFile']);
$router->register('POST', 'upload/batch', [UploadAPI::class, 'uploadBatch']);
$router->register('GET', 'upload/status/:id', [UploadAPI::class, 'getUploadStatus']);
$router->register('GET', 'upload/pending', [UploadAPI::class, 'getPendingUploads']);
$router->register('PUT', 'upload/:id/status', [UploadAPI::class, 'updateUploadStatus']);
$router->register('DELETE', 'upload/:id', [UploadAPI::class, 'deleteUpload']);
$router->register('GET', 'upload/stats', [UploadAPI::class, 'getUploadStats']);

// Dispatch the request
$router->dispatch($method, $request_uri);