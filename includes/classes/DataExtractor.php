<?php
/**
 * Data Extractor Class
 * File: includes/classes/DataExtractor.php
 * 
 * Extracts data from uploaded documents (Excel, PDF, Word, Images) using
 * pattern matching, OCR, and structured data parsing
 */

// Include PhpSpreadsheet if available (for Excel processing)
// require_once __DIR__ . '/../vendor/autoload.php';
// At the top of DataExtractor.php
require_once __DIR__ . '/../libraries/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DataExtractor {
    private $patterns;
    private $confidence_threshold = 70;
    private $last_error;
    
    public function __construct() {
        $this->patterns = [
            'bidding_date' => [
                'pattern' => '/\b(\d{1,2}[-/]\d{1,2}[-/]\d{2,4})\b/',
                'type' => 'date',
                'priority' => 1
            ],
            'project_title' => [
                'pattern' => '/(?:Project|Procurement|Supply|Delivery)[\s:;]*([^\n]{10,200})/i',
                'type' => 'text',
                'priority' => 2
            ],
            'fund_source' => [
                'pattern' => '/Fund\s*Source[\s:;]*([^\n]{5,50})/i',
                'type' => 'text',
                'priority' => 1
            ],
            'capex_project' => [
                'pattern' => '/CAPEX[\s:;]*([A-Z0-9\-]+)/i',
                'type' => 'text',
                'priority' => 1
            ],
            'abc' => [
                'pattern' => '/(?:Approved\s*Budget|ABC)[\s:;]*₱?\s*([\d,]+\.?\d*)/i',
                'type' => 'currency',
                'priority' => 1
            ],
            'winning_bidder' => [
                'pattern' => '/(?:Winning\s*Bidder|Awarded\s*To)[\s:;]*([^\n]{5,100})/i',
                'type' => 'text',
                'priority' => 1
            ],
            'winning_bid_amount' => [
                'pattern' => '/(?:Winning\s*Bid\s*Amount|Awarded\s*Amount)[\s:;]*₱?\s*([\d,]+\.?\d*)/i',
                'type' => 'currency',
                'priority' => 1
            ],
            'purchase_order_ref' => [
                'pattern' => '/(?:PO|Purchase\s*Order)[\s:;]*([A-Z0-9\-]+)/i',
                'type' => 'text',
                'priority' => 1
            ],
            'itb_no' => [
                'pattern' => '/(?:ITB|Invitation\s*to\s*Bid)[\s:;]*([A-Z0-9\-]+)/i',
                'type' => 'text',
                'priority' => 1
            ]
        ];
    }
    
    /**
     * Extract data from uploaded document
     * @param int $document_id Document ID
     * @param string $file_path Path to file
     * @param string $file_type MIME type
     * @return array Extracted data
     */
    public function extractFromDocument($document_id, $file_path, $file_type) {
        $extracted_data = [];
        $confidence_scores = [];
        
        try {
            // Extract based on file type
            if (strpos($file_type, 'spreadsheet') !== false || strpos($file_type, 'excel') !== false) {
                $extracted_data = $this->extractFromExcel($file_path);
            } elseif (strpos($file_type, 'pdf') !== false) {
                $extracted_data = $this->extractFromPDF($file_path);
            } elseif (strpos($file_type, 'word') !== false) {
                $extracted_data = $this->extractFromWord($file_path);
            } elseif (strpos($file_type, 'image') !== false) {
                $extracted_data = $this->extractFromImage($file_path);
            } elseif (strpos($file_type, 'text') !== false || strpos($file_type, 'csv') !== false) {
                $extracted_data = $this->extractFromText($file_path);
            }
            
            // Apply pattern matching to extracted text
            if (isset($extracted_data['raw_text'])) {
                $pattern_data = $this->extractWithPatterns($extracted_data['raw_text']);
                $extracted_data = array_merge($extracted_data, $pattern_data);
            }
            
            // Calculate confidence scores
            foreach ($this->patterns as $field => $pattern) {
                if (isset($extracted_data[$field]) && !empty($extracted_data[$field])) {
                    $confidence_scores[$field] = $this->calculateConfidence($extracted_data[$field], $pattern);
                }
            }
            
            $avg_confidence = !empty($confidence_scores) ? array_sum($confidence_scores) / count($confidence_scores) : 0;
            
            // Save to database
            $this->saveExtractedData($document_id, $extracted_data, $avg_confidence);
            
            return [
                'document_id' => $document_id,
                'extracted_data' => $extracted_data,
                'confidence_score' => round($avg_confidence, 2),
                'field_confidence' => $confidence_scores,
                'raw_text' => $extracted_data['raw_text'] ?? null
            ];
            
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            $this->logExtractionError($document_id, $e->getMessage());
            
            return [
                'document_id' => $document_id,
                'extracted_data' => [],
                'confidence_score' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract data from Excel file
     * @param string $file_path Path to Excel file
     * @return array Extracted data
     */
    private function extractFromExcel($file_path) {
        $result = [
            'raw_text' => '',
            'rows' => [],
            'headers' => [],
            'structured_data' => []
        ];
        
        // Try to use PhpSpreadsheet if available
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                if (!empty($rows)) {
                    $result['headers'] = array_map('trim', $rows[0]);
                    $result['rows'] = array_slice($rows, 1);
                    $result['structured_data'] = $this->parseStructuredData($result['headers'], $result['rows']);
                    
                    // Convert to raw text for pattern matching
                    $text_lines = [];
                    foreach ($rows as $row) {
                        $text_lines[] = implode(' ', array_filter($row));
                    }
                    $result['raw_text'] = implode("\n", $text_lines);
                }
            } catch (Exception $e) {
                $this->last_error = "Excel parsing failed: " . $e->getMessage();
            }
        } else {
            // Fallback: Try to read as CSV
            $result = $this->extractFromCSV($file_path);
        }
        
        return $result;
    }
    
    /**
     * Extract data from CSV file
     * @param string $file_path Path to CSV file
     * @return array Extracted data
     */
    private function extractFromCSV($file_path) {
        $result = [
            'raw_text' => '',
            'rows' => [],
            'headers' => []
        ];
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            $row_count = 0;
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                if ($row_count === 0) {
                    $result['headers'] = array_map('trim', $data);
                } else {
                    $result['rows'][] = $data;
                }
                $result['raw_text'] .= implode(' ', $data) . "\n";
                $row_count++;
            }
            fclose($handle);
        }
        
        return $result;
    }
    
    /**
     * Extract data from PDF file
     * @param string $file_path Path to PDF file
     * @return array Extracted data
     */
    private function extractFromPDF($file_path) {
        $result = [
            'raw_text' => '',
            'pages' => []
        ];
        
        // Try using pdftotext (Linux)
        if (exec('which pdftotext')) {
            $output = shell_exec("pdftotext " . escapeshellarg($file_path) . " - 2>/dev/null");
            if ($output) {
                $result['raw_text'] = $output;
                $result['pages'] = explode("\f", $output);
            }
        }
        
        // Fallback: Try using PDFParser if available
        if (empty($result['raw_text']) && class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file_path);
                $result['raw_text'] = $pdf->getText();
                $result['pages'] = explode("\f", $result['raw_text']);
            } catch (Exception $e) {
                $this->last_error = "PDF parsing failed: " . $e->getMessage();
            }
        }
        
        // Final fallback: Try extracting using file_get_contents (poor quality)
        if (empty($result['raw_text'])) {
            $content = file_get_contents($file_path);
            // Remove binary data, keep readable text
            $result['raw_text'] = preg_replace('/[^\x20-\x7E\n\r]/', ' ', $content);
            $result['raw_text'] = preg_replace('/\s+/', ' ', $result['raw_text']);
        }
        
        return $result;
    }
    
    /**
     * Extract data from Word document
     * @param string $file_path Path to Word file
     * @return array Extracted data
     */
    private function extractFromWord($file_path) {
        $result = ['raw_text' => ''];
        
        // Try using antiword for .doc files
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($extension === 'doc' && exec('which antiword')) {
            $output = shell_exec("antiword " . escapeshellarg($file_path) . " 2>/dev/null");
            if ($output) {
                $result['raw_text'] = $output;
            }
        }
        
        // Try using catdoc for .doc files
        if (empty($result['raw_text']) && $extension === 'doc' && exec('which catdoc')) {
            $output = shell_exec("catdoc " . escapeshellarg($file_path) . " 2>/dev/null");
            if ($output) {
                $result['raw_text'] = $output;
            }
        }
        
        // For .docx files, try using unzip and reading XML
        if (empty($result['raw_text']) && $extension === 'docx') {
            $zip = new ZipArchive();
            if ($zip->open($file_path) === true) {
                $xml = $zip->getFromName('word/document.xml');
                if ($xml) {
                    // Remove XML tags
                    $result['raw_text'] = strip_tags($xml);
                    $result['raw_text'] = html_entity_decode($result['raw_text']);
                }
                $zip->close();
            }
        }
        
        return $result;
    }
    
    /**
     * Extract data from image using OCR
     * @param string $file_path Path to image file
     * @return array Extracted data
     */
    private function extractFromImage($file_path) {
        $result = ['raw_text' => ''];
        
        // Try using Tesseract OCR
        if (exec('which tesseract')) {
            $temp_file = tempnam(sys_get_temp_dir(), 'ocr_');
            exec("tesseract " . escapeshellarg($file_path) . " " . $temp_file . " 2>/dev/null");
            $output_file = $temp_file . '.txt';
            if (file_exists($output_file)) {
                $result['raw_text'] = file_get_contents($output_file);
                unlink($output_file);
            }
            unlink($temp_file);
        }
        
        return $result;
    }
    
    /**
     * Extract data from plain text file
     * @param string $file_path Path to text file
     * @return array Extracted data
     */
    private function extractFromText($file_path) {
        $result = ['raw_text' => file_get_contents($file_path)];
        return $result;
    }
    
    /**
     * Parse structured data from Excel headers and rows
     * @param array $headers Column headers
     * @param array $rows Data rows
     * @return array Structured data
     */
    private function parseStructuredData($headers, $rows) {
        $structured = [];
        
        // Map common headers to fields
        $header_map = [
            'bidding date' => 'bidding_date',
            'project title' => 'project_title',
            'project' => 'project_title',
            'fund source' => 'fund_source',
            'abc' => 'abc',
            'approved budget' => 'abc',
            'winning bidder' => 'winning_bidder',
            'bid winner' => 'winning_bidder',
            'amount' => 'winning_bid_amount',
            'bid amount' => 'winning_bid_amount',
            'status' => 'status',
            'itb no' => 'itb_no',
            'particulars' => 'particulars'
        ];
        
        // Create mapping from headers
        $mapping = [];
        foreach ($headers as $index => $header) {
            $header_lower = strtolower(trim($header));
            foreach ($header_map as $key => $field) {
                if (strpos($header_lower, $key) !== false) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }
        
        // Extract data from first row
        if (!empty($rows) && !empty($mapping)) {
            $first_row = $rows[0];
            foreach ($mapping as $field => $index) {
                if (isset($first_row[$index]) && !empty($first_row[$index])) {
                    $structured[$field] = trim($first_row[$index]);
                }
            }
        }
        
        return $structured;
    }
    
    /**
     * Extract data using regex patterns
     * @param string $text Raw text to search
     * @return array Extracted data
     */
    private function extractWithPatterns($text) {
        $extracted = [];
        
        foreach ($this->patterns as $field => $pattern_config) {
            if (preg_match($pattern_config['pattern'], $text, $matches)) {
                $value = trim($matches[1]);
                
                // Process based on type
                switch ($pattern_config['type']) {
                    case 'currency':
                        $value = floatval(str_replace(',', '', $value));
                        break;
                    case 'date':
                        $value = $this->normalizeDate($value);
                        break;
                    case 'text':
                    default:
                        $value = htmlspecialchars($value);
                        break;
                }
                
                if (!empty($value)) {
                    $extracted[$field] = $value;
                }
            }
        }
        
        return $extracted;
    }
    
    /**
     * Normalize date to YYYY-MM-DD format
     * @param string $date_string Date string
     * @return string Normalized date or original
     */
    private function normalizeDate($date_string) {
        $formats = ['m/d/Y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date && $date->format($format) === $date_string) {
                return $date->format('Y-m-d');
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return $date_string;
    }
    
    /**
     * Calculate confidence score for extracted value
     * @param mixed $value Extracted value
     * @param array $pattern_config Pattern configuration
     * @return float Confidence score (0-100)
     */
    private function calculateConfidence($value, $pattern_config) {
        $confidence = 50; // Base confidence
        
        // Increase confidence if value matches pattern well
        if (preg_match($pattern_config['pattern'], $value)) {
            $confidence += 20;
        }
        
        // Increase confidence for non-empty values of expected type
        if (!empty($value)) {
            switch ($pattern_config['type']) {
                case 'currency':
                    if (is_numeric($value) && $value > 0) {
                        $confidence += 20;
                    }
                    break;
                case 'date':
                    if (strtotime($value) !== false) {
                        $confidence += 20;
                    }
                    break;
                case 'text':
                    if (strlen($value) > 5) {
                        $confidence += 15;
                    }
                    break;
            }
        }
        
        return min($confidence, 100);
    }
    
    /**
     * Save extracted data to database
     * @param int $document_id Document ID
     * @param array $extracted_data Extracted data
     * @param float $confidence_score Average confidence score
     */
    private function saveExtractedData($document_id, $extracted_data, $confidence_score) {
        $json_data = json_encode($extracted_data);
        
        $sql = "UPDATE uploaded_documents 
                SET extracted_data = ?, confidence_score = ?, upload_status = 'extracted', processed_at = NOW() 
                WHERE id = ?";
        
        executeQuery($sql, [$json_data, $confidence_score, $document_id]);
        
        // Log extraction
        $this->logExtraction($document_id, $confidence_score);
    }
    
    /**
     * Log extraction attempt
     * @param int $document_id Document ID
     * @param float $confidence_score Confidence score
     */
    private function logExtraction($document_id, $confidence_score) {
        $sql = "INSERT INTO extraction_logs (document_id, extraction_method, confidence_score, status, created_at) 
                VALUES (?, 'auto', ?, 'success', NOW())";
        executeQuery($sql, [$document_id, $confidence_score]);
    }
    
    /**
     * Log extraction error
     * @param int $document_id Document ID
     * @param string $error Error message
     */
    private function logExtractionError($document_id, $error) {
        $sql = "INSERT INTO extraction_logs (document_id, extraction_method, status, error_details, created_at) 
                VALUES (?, 'auto', 'failed', ?, NOW())";
        executeQuery($sql, [$document_id, $error]);
        
        // Update document status
        $update_sql = "UPDATE uploaded_documents SET upload_status = 'failed', error_message = ? WHERE id = ?";
        executeQuery($update_sql, [$error, $document_id]);
    }
    
    /**
     * Get last error
     * @return string|null Last error
     */
    public function getLastError() {
        return $this->last_error;
    }
}