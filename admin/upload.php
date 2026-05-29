<?php
/**
 * Single File Upload
 * File: admin/upload.php
 * 
 * Upload single document with auto-extraction
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/classes/FileUploader.php';
require_once '../includes/classes/DataExtractor.php';

$pageTitle = 'Upload Document';

$uploader = new FileUploader();
$extractor = new DataExtractor();

$message = '';
$error = '';
$document_id = null;
$extracted_data = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document_file'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $document_type = sanitize($_POST['document_type']);
        
        $result = $uploader->uploadFile($_FILES['document_file'], $document_type, $_SESSION['user_id']);
        
        if ($result['success']) {
            $document_id = $result['document_id'];
            
            // Extract data
            $extracted = $extractor->extractFromDocument(
                $document_id,
                $result['file_path'],
                $_FILES['document_file']['type']
            );
            
            $extracted_data = $extracted;
            $message = 'File uploaded and data extracted successfully!';
            
            // Redirect to review page after short delay
            header("Refresh: 2; url=review-extraction.php?id=" . $document_id);
        } else {
            $error = $result['errors'][0] ?? 'Upload failed';
        }
    }
}

include '../includes/templates/admin-header.php';
?>

<style>
.upload-container {
    max-width: 800px;
    margin: 0 auto;
}
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.drop-zone i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}
.file-info {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #e9ecef;
    border-radius: 0.5rem;
}
</style>

<div class="admin-upload">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-upload me-2"></i>Upload Document
        </h1>
        <a href="batch-import.php" class="btn btn-outline-primary">
            <i class="fas fa-layer-group me-2"></i>Batch Import
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="upload-container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Upload Instructions
                </h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Supported formats:</strong> PDF, Excel (.xlsx, .xls), Word (.doc, .docx), Images (.jpg, .png)</li>
                    <li><strong>Maximum file size:</strong> 10MB</li>
                    <li><strong>After upload:</strong> System will auto-extract data for review</li>
                    <li><strong>For best results:</strong> Use structured Excel files with headers</li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label required">Document Type</label>
                        <select class="form-select" name="document_type" required>
                            <option value="">Select document type...</option>
                            <option value="public_bidding">Public Bidding</option>
                            <option value="sealed_bidding">Sealed Bidding</option>
                            <option value="procurement_monitoring">Procurement Monitoring</option>
                        </select>
                    </div>
                    
                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5>Drag & Drop your file here</h5>
                        <p class="text-muted">or click to browse</p>
                        <input type="file" name="document_file" id="fileInput" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" style="display:none" required>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-folder-open"></i> Select File
                        </button>
                    </div>
                    
                    <div id="fileInfo" class="file-info" style="display:none;">
                        <strong>Selected File:</strong> <span id="fileName"></span>
                        <br>
                        <strong>Size:</strong> <span id="fileSize"></span>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-lg w-100" id="uploadBtn" disabled>
                            <i class="fas fa-upload"></i> Upload & Extract Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const uploadBtn = document.getElementById('uploadBtn');
let selectedFile = null;

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

dropZone.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    const validTypes = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 10485760;
    
    if (!validTypes.includes(file.type)) {
        alert('Invalid file type. Please upload PDF, Excel, Word, or image files.');
        return;
    }
    
    if (file.size > maxSize) {
        alert('File too large. Maximum size is 10MB.');
        return;
    }
    
    selectedFile = file;
    fileName.textContent = file.name;
    fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
    fileInfo.style.display = 'block';
    uploadBtn.disabled = false;
    
    // Update the file input with the selected file
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
}
</script>

<?php include '../includes/templates/admin-footer.php'; ?>