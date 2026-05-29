<?php
/**
 * Excel Importer Class
 * File: includes/classes/ExcelImporter.php
 * 
 * Handles importing Excel files into database using field mapping templates
 */

class ExcelImporter {
    private $db;
    private $last_error;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Import from document ID using stored extracted data
     * @param int $document_id Document ID
     * @param string $document_type Document type
     * @return array Import result
     */
    public function importFromDocument($document_id, $document_type) {
        // Get document and extracted data
        $document = fetchOne("SELECT * FROM uploaded_documents WHERE id = ?", [$document_id]);
        
        if (!$document) {
            return ['success' => false, 'error' => 'Document not found'];
        }
        
        $extracted_data = json_decode($document['extracted_data'] ?? '{}', true);
        
        if (empty($extracted_data)) {
            return ['success' => false, 'error' => 'No extracted data found'];
        }
        
        // Get field mapping template
        $mapping = $this->getFieldMapping($document_type);
        
        // Map extracted data to database fields
        $db_data = $this->mapDataToDatabase($extracted_data, $mapping, $document_type);
        
        if (empty($db_data)) {
            return ['success' => false, 'error' => 'No data to import after mapping'];
        }
        
        // Import to appropriate table
        $record_id = $this->importToTable($db_data, $document_type);
        
        if ($record_id) {
            // Link document to imported record
            $this->linkDocumentToRecord($document_id, $document_type, $record_id);
            
            return ['success' => true, 'record_id' => $record_id];
        }
        
        return ['success' => false, 'error' => 'Failed to import data'];
    }
    
    /**
     * Import array data directly to database
     * @param array $data Data to import
     * @param string $document_type Document type
     * @return array Import result
     */
    public function importArrayToDatabase($data, $document_type) {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data to import'];
        }
        
        $record_id = $this->importToTable($data, $document_type);
        
        if ($record_id) {
            return ['success' => true, 'record_id' => $record_id];
        }
        
        return ['success' => false, 'error' => 'Failed to import data'];
    }
    
    /**
     * Get field mapping template for document type
     * @param string $document_type Document type
     * @return array Field mapping
     */
    private function getFieldMapping($document_type) {
        // Get default mapping template
        $template = fetchOne(
            "SELECT field_mappings FROM field_mapping_templates WHERE document_type = ? AND is_default = 1",
            [$document_type]
        );
        
        if ($template) {
            return json_decode($template['field_mappings'], true);
        }
        
        // Return default mappings if no template exists
        return $this->getDefaultMappings($document_type);
    }
    
    /**
     * Get default field mappings
     * @param string $document_type Document type
     * @return array Default mappings
     */
    private function getDefaultMappings($document_type) {
        $mappings = [
            'public_bidding' => [
                'bidding_date' => 'bidding_date',
                'project_title' => 'project_title',
                'fund_source' => 'fund_source',
                'capex_project' => 'capex_project',
                'approved_budget_contract' => 'abc',
                'winning_bidder' => 'winning_bidder',
                'winning_bid_amount' => 'winning_bid_amount',
                'participating_bidders' => 'participating_bidders',
                'status' => 'status'
            ],
            'sealed_bidding' => [
                'bidding_date' => 'bidding_date',
                'project_title' => 'project_title',
                'fund_source' => 'fund_source',
                'winning_bidder' => 'winning_bidder',
                'winning_bid_amount' => 'winning_bid_amount',
                'participating_bidders' => 'participating_bidders',
                'contract_or_po_ref' => 'contract_or_po_ref',
                'status' => 'status'
            ],
            'procurement_monitoring' => [
                'itb_no' => 'itb_no',
                'particulars' => 'particulars',
                'abc' => 'abc',
                'winning_bidder' => 'winning_bidder',
                'winning_price' => 'winning_price',
                'delivery_date_per_po' => 'delivery_date_per_po',
                'actual_delivery_date' => 'actual_delivery_date',
                'remarks' => 'remarks'
            ]
        ];
        
        return $mappings[$document_type] ?? [];
    }
    
    /**
     * Map extracted data to database fields
     * @param array $extracted_data Extracted data
     * @param array $mapping Field mapping
     * @param string $document_type Document type
     * @return array Mapped data for database
     */
    private function mapDataToDatabase($extracted_data, $mapping, $document_type) {
        $db_data = [];
        
        foreach ($mapping as $db_field => $source_field) {
            // Check if source field exists in extracted data (as key)
            if (isset($extracted_data[$source_field])) {
                $db_data[$db_field] = $extracted_data[$source_field];
            }
            // Check if source field exists in structured data
            elseif (isset($extracted_data['structured_data'][$source_field])) {
                $db_data[$db_field] = $extracted_data['structured_data'][$source_field];
            }
            // Check if source field exists in table_data
            elseif (isset($extracted_data['table_data']) && isset($extracted_data['table_data'][0])) {
                // Try to find by column header
                $headers = $extracted_data['headers'] ?? [];
                $row = $extracted_data['rows'][0] ?? [];
                
                foreach ($headers as $index => $header) {
                    if (stripos($header, $source_field) !== false) {
                        if (isset($row[$index]) && !empty($row[$index])) {
                            $db_data[$db_field] = $row[$index];
                            break;
                        }
                    }
                }
            }
        }
        
        // Set default values for required fields
        if ($document_type === 'public_bidding' && empty($db_data['status'])) {
            $db_data['status'] = 'active';
        }
        
        if ($document_type === 'sealed_bidding' && empty($db_data['status'])) {
            $db_data['status'] = 'active';
        }
        
        return $db_data;
    }
    
    /**
     * Import mapped data to appropriate table
     * @param array $data Mapped data
     * @param string $document_type Document type
     * @return int|false Record ID or false
     */
    private function importToTable($data, $document_type) {
        $table = '';
        
        switch ($document_type) {
            case 'public_bidding':
                $table = 'public_bidding';
                break;
            case 'sealed_bidding':
                $table = 'sealed_bidding';
                break;
            case 'procurement_monitoring':
                $table = 'procurement_monitoring';
                break;
            default:
                $this->last_error = 'Invalid document type';
                return false;
        }
        
        // Add timestamps if not present
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        $record_id = insertRecord($table, $data);
        
        if ($record_id) {
            return $record_id;
        }
        
        $this->last_error = 'Failed to insert record';
        return false;
    }
    
    /**
     * Link document to imported record
     * @param int $document_id Document ID
     * @param string $document_type Document type
     * @param int $record_id Record ID
     */
    private function linkDocumentToRecord($document_id, $document_type, $record_id) {
        $column = '';
        
        switch ($document_type) {
            case 'public_bidding':
                $column = 'bidding_id';
                break;
            case 'sealed_bidding':
                $column = 'sealed_bidding_id';
                break;
            case 'procurement_monitoring':
                $column = 'procurement_id';
                break;
        }
        
        if ($column) {
            $sql = "UPDATE uploaded_documents SET $column = ?, upload_status = 'imported' WHERE id = ?";
            executeQuery($sql, [$record_id, $document_id]);
        }
    }
    
    /**
     * Bulk import from Excel file
     * @param string $file_path Path to Excel file
     * @param string $document_type Document type
     * @param int $user_id User ID
     * @return array Bulk import result
     */
    public function bulkImportFromExcel($file_path, $document_type, $user_id) {
        $result = [
            'success' => false,
            'total_rows' => 0,
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Read Excel file
        $excel_data = $this->readExcelFile($file_path);
        
        if (empty($excel_data['rows'])) {
            $result['errors'][] = 'No data found in Excel file';
            return $result;
        }
        
        $result['total_rows'] = count($excel_data['rows']);
        $mapping = $this->getFieldMapping($document_type);
        
        foreach ($excel_data['rows'] as $index => $row) {
            // Map row data to database fields
            $db_data = [];
            
            foreach ($mapping as $db_field => $column_name) {
                // Find column index
                $col_index = array_search($column_name, $excel_data['headers']);
                if ($col_index !== false && isset($row[$col_index]) && !empty($row[$col_index])) {
                    $db_data[$db_field] = trim($row[$col_index]);
                }
            }
            
            if (!empty($db_data)) {
                $db_data['created_by'] = $user_id;
                $record_id = $this->importToTable($db_data, $document_type);
                
                if ($record_id) {
                    $result['imported']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Row " . ($index + 2) . ": " . ($this->last_error ?: 'Import failed');
                }
            } else {
                $result['failed']++;
                $result['errors'][] = "Row " . ($index + 2) . ": No valid data found";
            }
        }
        
        $result['success'] = $result['imported'] > 0;
        
        return $result;
    }
    
    /**
     * Read Excel file and extract data
     * @param string $file_path Path to Excel file
     * @return array Excel data
     */
    private function readExcelFile($file_path) {
        $result = [
            'headers' => [],
            'rows' => []
        ];
        
        // Try using PhpSpreadsheet
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                if (!empty($rows)) {
                    $result['headers'] = array_map('trim', $rows[0]);
                    $result['rows'] = array_slice($rows, 1);
                }
            } catch (Exception $e) {
                $this->last_error = "Excel parsing failed: " . $e->getMessage();
            }
        } else {
            // Fallback: Try CSV
            $result = $this->readCSVFile($file_path);
        }
        
        return $result;
    }
    
    /**
     * Read CSV file
     * @param string $file_path Path to CSV file
     * @return array CSV data
     */
    private function readCSVFile($file_path) {
        $result = [
            'headers' => [],
            'rows' => []
        ];
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            $row_count = 0;
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                if ($row_count === 0) {
                    $result['headers'] = array_map('trim', $data);
                } else {
                    $result['rows'][] = $data;
                }
                $row_count++;
            }
            fclose($handle);
        }
        
        return $result;
    }
    
    /**
     * Get last error
     * @return string|null Last error
     */
    public function getLastError() {
        return $this->last_error;
    }
}