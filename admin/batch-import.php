<?php
/**
 * Batch Import Multiple Files
 * File: admin/batch-import.php
 * 
 * Upload multiple files at once and process them in batch
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/classes/FileUploader.php';
require_once '../includes/classes/DataExtractor.php';
require_once '../includes/classes/ExcelImporter.php';

$pageTitle = 'Batch Import Documents';

$uploader = new FileUploader();
$extractor = new DataExtractor();
$excelImporter = new ExcelImporter();

$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'public_bidding';
$import_log = [];

// Handle multiple file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['batch_files'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        alertRedirect('Invalid security token.', 'danger', 'batch-import.php?type=' . $type);
    }
    
    $document_type = sanitize($_POST['document_type']);
    $auto_import = isset($_POST['auto_import']) ? true : false;
    $files = $_FILES['batch_files'];
    $total_files = count($files['name']);
    $success_count = 0;
    $fail_count = 0;
    
    for ($i = 0; $i < $total_files; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        // Upload file
        $result = $uploader->uploadFile($file, $document_type, $_SESSION['user_id']);
        
        if ($result['success']) {
            // Extract data
            $extracted = $extractor->extractFromDocument(
                $result['document_id'],
                $result['file_path'],
                $file['type']
            );
            
            $import_log[] = [
                'file' => $file['name'],
                'document_id' => $result['document_id'],
                'status' => 'extracted',
                'message' => 'File uploaded and extracted successfully'
            ];
            $success_count++;
            
            // Auto-import if enabled and extraction confidence is high
            if ($auto_import && isset($extracted['confidence_score']) && $extracted['confidence_score'] >= 80) {
                $import_result = $excelImporter->importToDatabase($result['document_id'], $document_type, $extracted);
                if ($import_result['success']) {
                    $import_log[count($import_log) - 1]['status'] = 'imported';
                    $import_log[count($import_log) - 1]['message'] = 'Auto-imported successfully';
                }
            }
        } else {
            $import_log[] = [
                'file' => $file['name'],
                'status' => 'failed',
                'message' => $result['errors'][0] ?? 'Upload failed'
            ];
            $fail_count++;
        }
    }
    
    alertRedirect("Batch import complete: $success_count successful, $fail_count failed.", 'success', 'batch-import.php?type=' . $type);
}

// Get pending uploads for review
$pendingUploads = fetchAll("
    SELECT * FROM uploaded_documents 
    WHERE document_type = ? AND upload_status IN ('extracted', 'processing')
    ORDER BY created_at DESC 
    LIMIT 20
", [$type]);

include '../includes/templates/admin-header.php';
?>

<style>
.upload-zone {
    border: 2px dashed #dee2e6;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}
.upload-zone:hover, .upload-zone.drag-over {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.upload-zone i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}
.file-list {
    max-height: 300px;
    overflow-y: auto;
}
.file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}
.file-item .file-name {
    flex: 1;
}
.file-item .file-status {
    font-size: 0.875rem;
}
.status-success { color: #198754; }
.status-warning { color: #ffc107; }
.status-danger { color: #dc3545; }
</style>

<div class="admin-batch-import">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h3">
            <i class="fas fa-layer-group me-2"></i>Batch Import Documents
        </h1>
        <div>
            <a href="upload.php?type=<?php echo $type; ?>" class="btn btn-outline-primary">
                <i class="fas fa-upload me-2"></i>Single Upload
            </a>
            <a href="review-extraction.php" class="btn btn-outline-warning">
                <i class="fas fa-check-double me-2"></i>Review Pending
            </a>
        </div>
    </div>
    
    <!-- Document Type Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group w-100" role="group">
                <a href="?type=public_bidding" class="btn <?php echo $type == 'public_bidding' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-gavel"></i> Public Bidding
                </a>
                <a href="?type=sealed_bidding" class="btn <?php echo $type == 'sealed_bidding' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-lock"></i> Sealed Bidding
                </a>
                <a href="?type=procurement_monitoring" class="btn <?php echo $type == 'procurement_monitoring' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-chart-line"></i> Procurement Monitoring
                </a>
            </div>
        </div>
    </div>
    
    <!-- Upload Zone -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-cloud-upload-alt me-2"></i>Batch Upload Files
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data" id="batchUploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="document_type" value="<?php echo $type; ?>">
                
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h5>Drag & Drop files here</h5>
                    <p class="text-muted">or click to select files</p>
                    <input type="file" name="batch_files[]" id="fileInput" multiple accept=".xlsx,.xls,.pdf,.doc,.docx,.jpg,.png" style="display:none">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open"></i> Select Files
                    </button>
                </div>
                
                <!-- Selected Files List -->
                <div id="fileListContainer" style="display:none;" class="mt-4">
                    <h6>Selected Files (<span id="fileCount">0</span>)</h6>
                    <div id="fileList" class="file-list border rounded"></div>
                </div>
                
                <!-- Options -->
                <div class="mt-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="auto_import" name="auto_import" value="1">
                        <label class="form-check-label" for="auto_import">
                            Auto-import to database when confidence score is high (≥80%)
                        </label>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success btn-lg" id="uploadBtn" disabled>
                        <i class="fas fa-upload"></i> Upload All Files
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg" id="clearFilesBtn">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Upload Instructions -->
    <div class="alert alert-info mb-4">
        <h6><i class="fas fa-info-circle"></i> Instructions for Batch Upload</h6>
        <ul class="mb-0">
            <li><strong>Supported formats:</strong> Excel (.xlsx, .xls), PDF, Word (.doc, .docx), Images (.jpg, .png)</li>
            <li><strong>Excel files:</strong> System will auto-detect columns and map to database fields</li>
            <li><strong>PDF/Images:</strong> OCR will extract text where possible</li>
            <li><strong>Maximum file size:</strong> 10MB per file</li>
            <li><strong>After upload:</strong> Files will be queued for extraction and review</li>
        </ul>
    </div>
    
    <!-- Pending Uploads Queue -->
    <?php if (!empty($pendingUploads)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-clock me-2"></i>Pending Reviews (<?php echo count($pendingUploads); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Filename</th><th>Type</th><th>Extracted Date</th><th>Confidence</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUploads as $upload): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($upload['original_filename']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $upload['document_type'])); ?></td>
                                <td><?php echo formatDate($upload['created_at']); ?></td>
                                <td>
                                    <?php 
                                    $confidence = $upload['confidence_score'] ?? 0;
                                    $badgeClass = $confidence >= 80 ? 'success' : ($confidence >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $confidence; ?>%</span>
                                 </td>
                                <td>
                                    <a href="review-extraction.php?id=<?php echo $upload['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Review
                                    </a>
                                 </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Import Log -->
    <?php if (!empty($import_log)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>Last Import Results
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>File</th><th>Status</th><th>Message</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($import_log as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['file']); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'extracted' => 'warning',
                                        'imported' => 'success',
                                        'failed' => 'danger'
                                    ][$log['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Drag and drop file upload
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const fileListContainer = document.getElementById('fileListContainer');
const fileList = document.getElementById('fileList');
const fileCount = document.getElementById('fileCount');
const uploadBtn = document.getElementById('uploadBtn');
const clearFilesBtn = document.getElementById('clearFilesBtn');

let selectedFiles = [];

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('drag-over');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const files = Array.from(e.dataTransfer.files);
    addFiles(files);
});

uploadZone.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    addFiles(files);
    fileInput.value = ''; // Reset to allow re-selecting same files
});

function addFiles(files) {
    const validTypes = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 10485760; // 10MB
    
    for (const file of files) {
        if (!validTypes.includes(file.type)) {
            alert(`Invalid file type: ${file.name}. Please upload PDF, Excel, Word, or image files.`);
            continue;
        }
        if (file.size > maxSize) {
            alert(`File too large: ${file.name}. Maximum size is 10MB.`);
            continue;
        }
        if (!selectedFiles.some(f => f.name === file.name)) {
            selectedFiles.push(file);
        }
    }
    
    updateFileList();
}

function updateFileList() {
    if (selectedFiles.length === 0) {
        fileListContainer.style.display = 'none';
        uploadBtn.disabled = true;
        return;
    }
    
    fileListContainer.style.display = 'block';
    fileCount.textContent = selectedFiles.length;
    uploadBtn.disabled = false;
    
    fileList.innerHTML = selectedFiles.map((file, index) => `
        <div class="file-item">
            <div class="file-name">
                <i class="fas fa-file-${getFileIcon(file.type)} me-2"></i>
                ${file.name}
                <small class="text-muted ms-2">(${(file.size / 1024).toFixed(1)} KB)</small>
            </div>
            <div class="file-status">
                <button type="button" class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    // Add remove handlers
    document.querySelectorAll('.remove-file').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = parseInt(btn.dataset.index);
            selectedFiles.splice(index, 1);
            updateFileList();
            
            // Update the file input with remaining files
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        });
    });
}

function getFileIcon(type) {
    if (type.includes('pdf')) return 'pdf';
    if (type.includes('excel') || type.includes('spreadsheet')) return 'excel';
    if (type.includes('word')) return 'word';
    if (type.includes('image')) return 'image';
    return 'alt';
}

clearFilesBtn.addEventListener('click', () => {
    selectedFiles = [];
    updateFileList();
    fileInput.value = '';
});
</script>

<?php include '../includes/templates/admin-footer.php'; ?>