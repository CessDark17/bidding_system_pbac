<?php
/**
 * File Uploader Class
 * File: includes/classes/FileUploader.php
 * 
 * Handles file uploads, validation, storage, and database recording
 */

class FileUploader {
    private $upload_dir;
    private $allowed_types;
    private $max_size;
    private $last_error;
    
    public function __construct() {
        $this->upload_dir = UPLOADS_PATH . '/bidding-documents/';
        $this->allowed_types = unserialize(ALLOWED_FILE_TYPES);
        $this->max_size = MAX_FILE_SIZE;
        
        // Create upload directory if not exists
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    /**
     * Upload a single file
     * @param array $file $_FILES array element
     * @param string $document_type Document type (public_bidding, sealed_bidding, procurement_monitoring)
     * @param int $user_id Uploading user ID
     * @return array Upload result
     */
    public function uploadFile($file, $document_type, $user_id) {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            $this->last_error = $validation['errors'];
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Generate safe filename
        $original_name = basename($file['name']);
        $stored_name = $this->generateSafeFilename($original_name);
        $file_path = $this->upload_dir . $stored_name;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            $this->last_error = ['Failed to save uploaded file'];
            return ['success' => false, 'errors' => ['Failed to save uploaded file']];
        }
        
        // Set proper permissions
        chmod($file_path, 0644);
        
        // Save to database
        $document_id = $this->saveDocumentRecord(
            $original_name,
            $stored_name,
            $file_path,
            $file['type'],
            $file['size'],
            $document_type,
            $user_id
        );
        
        if (!$document_id) {
            // Delete file if database save fails
            unlink($file_path);
            return ['success' => false, 'errors' => ['Failed to record file in database']];
        }
        
        return [
            'success' => true,
            'document_id' => $document_id,
            'file_path' => $file_path,
            'filename' => $original_name,
            'message' => 'File uploaded successfully'
        ];
    }
    
    /**
     * Upload multiple files (batch)
     * @param array $files $_FILES array for multiple files
     * @param string $document_type Document type
     * @param int $user_id Uploading user ID
     * @return array Batch upload results
     */
    public function uploadBatch($files, $document_type, $user_id) {
        $results = [];
        $success_count = 0;
        $fail_count = 0;
        
        // Handle both single file array and multiple files
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->uploadFile($file, $document_type, $user_id);
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        
        return [
            'success' => $success_count > 0,
            'total' => $file_count,
            'success_count' => $success_count,
            'fail_count' => $fail_count,
            'results' => $results
        ];
    }
    
    /**
     * Validate uploaded file
     * @param array $file $_FILES array element
     * @return array Validation result
     */
    private function validateFile($file) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowed_types)) {
            $allowed_extensions = ['PDF', 'Excel (XLS/XLSX)', 'Word (DOC/DOCX)', 'Images (JPG/PNG)'];
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions);
        }
        
        // Check file size
        if ($file['size'] > $this->max_size) {
            $max_mb = $this->max_size / 1024 / 1024;
            $errors[] = "File too large. Maximum size is {$max_mb}MB";
        }
        
        // Security: Check for malicious content (basic)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Verify MIME type matches declared type
        if ($mime_type !== $file['type']) {
            $errors[] = 'File type mismatch - possible manipulation detected';
        }
        
        // Additional security for images
        if (strpos($file['type'], 'image/') === 0) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $errors[] = 'Invalid image file';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generate safe filename to prevent collisions and directory traversal
     * @param string $original_name Original filename
     * @return string Safe filename
     */
    private function generateSafeFilename($original_name) {
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $safe_name = uniqid() . '_' . time() . '_' . bin2hex(random_bytes(8));
        
        if ($extension) {
            $safe_name .= '.' . $extension;
        }
        
        return $safe_name;
    }
    
    /**
     * Save document record to database
     * @param string $original_name Original filename
     * @param string $stored_name Stored filename
     * @param string $file_path Full file path
     * @param string $file_type MIME type
     * @param int $file_size File size in bytes
     * @param string $document_type Document type
     * @param int $user_id User ID
     * @return int|false Document ID or false
     */
    private function saveDocumentRecord($original_name, $stored_name, $file_path, $file_type, $file_size, $document_type, $user_id) {
        $sql = "INSERT INTO uploaded_documents (original_filename, stored_filename, file_path, file_type, file_size, document_type, upload_status, uploaded_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        
        $result = executeQuery($sql, [
            $original_name,
            $stored_name,
            $file_path,
            $file_type,
            $file_size,
            $document_type,
            $user_id
        ]);
        
        if ($result) {
            global $db;
            return $db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get document by ID
     * @param int $document_id Document ID
     * @return array|null Document record or null
     */
    public function getDocument($document_id) {
        $sql = "SELECT * FROM uploaded_documents WHERE id = ?";
        return fetchOne($sql, [$document_id]);
    }
    
    /**
     * Update document status
     * @param int $document_id Document ID
     * @param string $status New status (pending, processing, extracted, reviewed, imported, failed)
     * @param string|null $error_message Optional error message
     * @return bool Success
     */
    public function updateDocumentStatus($document_id, $status, $error_message = null) {
        $data = ['upload_status' => $status];
        
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
        }
        
        if ($status === 'imported' || $status === 'reviewed') {
            $data['processed_at'] = date('Y-m-d H:i:s');
        }
        
        return updateRecord('uploaded_documents', $data, 'id = ?', [$document_id]);
    }
    
    /**
     * Update document with extracted data
     * @param int $document_id Document ID
     * @param array $extracted_data Extracted data array
     * @param float $confidence_score Confidence score (0-100)
     * @return bool Success
     */
    public function updateExtractedData($document_id, $extracted_data, $confidence_score = null) {
        $data = [
            'extracted_data' => json_encode($extracted_data),
            'upload_status' => 'extracted'
        ];
        
        if ($confidence_score !== null) {
            $data['confidence_score'] = $confidence_score;
        }
        
        return updateRecord('uploaded_documents', $data, 'id = ?', [$document_id]);
    }
    
    /**
     * Link document to a record (after successful import)
     * @param int $document_id Document ID
     * @param string $record_type Record type (public_bidding, sealed_bidding, procurement_monitoring)
     * @param int $record_id Record ID
     * @return bool Success
     */
    public function linkToRecord($document_id, $record_type, $record_id) {
        $column = '';
        switch ($record_type) {
            case 'public_bidding':
                $column = 'bidding_id';
                break;
            case 'sealed_bidding':
                $column = 'sealed_bidding_id';
                break;
            case 'procurement_monitoring':
                $column = 'procurement_id';
                break;
            default:
                return false;
        }
        
        return updateRecord('uploaded_documents', [$column => $record_id, 'upload_status' => 'imported'], 'id = ?', [$document_id]);
    }
    
    /**
     * Delete document and its file
     * @param int $document_id Document ID
     * @return bool Success
     */
    public function deleteDocument($document_id) {
        $document = $this->getDocument($document_id);
        
        if (!$document) {
            $this->last_error = 'Document not found';
            return false;
        }
        
        // Delete physical file
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        // Delete database record
        return deleteRecord('uploaded_documents', 'id = ?', [$document_id]);
    }
    
    /**
     * Get pending documents for review
     * @param string $document_type Optional document type filter
     * @param int $limit Maximum records
     * @return array Pending documents
     */
    public function getPendingDocuments($document_type = null, $limit = 20) {
        $sql = "SELECT * FROM uploaded_documents WHERE upload_status IN ('pending', 'processing', 'extracted')";
        $params = [];
        
        if ($document_type) {
            $sql .= " AND document_type = ?";
            $params[] = $document_type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get documents by status
     * @param string $status Document status
     * @param int $limit Maximum records
     * @return array Documents
     */
    public function getDocumentsByStatus($status, $limit = 50) {
        $sql = "SELECT * FROM uploaded_documents WHERE upload_status = ? ORDER BY created_at DESC LIMIT ?";
        return fetchAll($sql, [$status, $limit]);
    }
    
    /**
     * Get upload statistics for dashboard
     * @return array Statistics
     */
    public function getStatistics() {
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
        
        // Recent uploads (last 7 days)
        $result = fetchOne("SELECT COUNT(*) as count FROM uploaded_documents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['last_7_days'] = $result['count'] ?? 0;
        
        // Average file size
        $result = fetchOne("SELECT AVG(file_size) as avg_size FROM uploaded_documents");
        $stats['avg_file_size'] = round(($result['avg_size'] ?? 0) / 1024, 2); // KB
        
        // Pending review count
        $result = fetchOne("SELECT COUNT(*) as count FROM uploaded_documents WHERE upload_status IN ('pending', 'processing', 'extracted')");
        $stats['pending_review'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get upload error message based on error code
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Get last error
     * @return array|string|null Last error
     */
    public function getLastError() {
        return $this->last_error;
    }
    
    /**
     * Check if a file is an image
     * @param string $file_path Path to file
     * @return bool True if image
     */
    public function isImage($file_path) {
        $image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        return in_array($mime, $image_types);
    }
    
    /**
     * Get file icon class for display
     * @param string $file_type MIME type
     * @return string FontAwesome icon class
     */
    public function getFileIcon($file_type) {
        if (strpos($file_type, 'pdf') !== false) {
            return 'fa-file-pdf text-danger';
        }
        if (strpos($file_type, 'excel') !== false || strpos($file_type, 'spreadsheet') !== false) {
            return 'fa-file-excel text-success';
        }
        if (strpos($file_type, 'word') !== false) {
            return 'fa-file-word text-primary';
        }
        if (strpos($file_type, 'image') !== false) {
            return 'fa-file-image text-info';
        }
        return 'fa-file-alt text-secondary';
    }
    
    /**
     * Get human-readable file size
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}