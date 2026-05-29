/**
 * Upload Manager
 * FIBECO Bidding System
 * File: upload-manager.js
 * 
 * Handles file uploads, drag-drop, progress tracking, and batch processing
 */

// Upload Manager Class
class UploadManager {
    constructor(options = {}) {
        this.options = {
            uploadUrl: '/fibeco-bidding-system/api/upload',
            batchUploadUrl: '/fibeco-bidding-system/api/upload/batch',
            maxFileSize: 10 * 1024 * 1024, // 10MB
            maxFiles: 50,
            allowedTypes: [
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/jpg'
            ],
            chunkSize: 1024 * 1024, // 1MB chunks for large files
            ...options
        };
        
        this.files = [];
        this.uploadQueue = [];
        this.currentUpload = null;
        this.uploading = false;
        this.uploadResults = [];
        
        this.init();
    }
    
    /**
     * Initialize upload manager
     */
    init() {
        this.setupDragAndDrop();
        this.setupFileInput();
        this.setupActionButtons();
        this.setupDocumentTypeChange();
        this.loadRecentUploads();
        this.startAutoRefresh();
    }
    
    /**
     * Setup drag and drop zone
     */
    setupDragAndDrop() {
        const dropZone = document.getElementById('uploadZone') || document.querySelector('.upload-zone');
        if (!dropZone) return;
        
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
            const files = Array.from(e.dataTransfer.files);
            this.addFiles(files);
        });
        
        // Click to select files
        dropZone.addEventListener('click', () => {
            const fileInput = document.getElementById('fileInput');
            if (fileInput) fileInput.click();
        });
    }
    
    /**
     * Setup file input
     */
    setupFileInput() {
        const fileInput = document.getElementById('fileInput');
        if (!fileInput) return;
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.addFiles(files);
            fileInput.value = ''; // Reset to allow re-selecting same files
        });
    }
    
    /**
     * Setup action buttons
     */
    setupActionButtons() {
        const uploadBtn = document.getElementById('uploadBtn');
        const clearBtn = document.getElementById('clearFilesBtn');
        const cancelBtn = document.getElementById('cancelUploadBtn');
        
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => this.startUpload());
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearAllFiles());
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.cancelUpload());
        }
    }
    
    /**
     * Setup document type change handler
     */
    setupDocumentTypeChange() {
        const docTypeSelect = document.getElementById('document_type');
        if (docTypeSelect) {
            docTypeSelect.addEventListener('change', () => {
                this.documentType = docTypeSelect.value;
                this.updateFileList();
            });
        }
    }
    
    /**
     * Add files to queue
     * @param {Array} files - Files to add
     */
    addFiles(files) {
        const validFiles = [];
        const errors = [];
        
        for (const file of files) {
            // Check file type
            if (!this.options.allowedTypes.includes(file.type)) {
                errors.push(`${file.name}: Invalid file type. Allowed: PDF, Excel, Word, Images`);
                continue;
            }
            
            // Check file size
            if (file.size > this.options.maxFileSize) {
                errors.push(`${file.name}: File too large. Maximum ${this.options.maxFileSize / 1024 / 1024}MB`);
                continue;
            }
            
            // Check for duplicates
            if (this.files.some(f => f.name === file.name && f.size === file.size)) {
                errors.push(`${file.name}: Duplicate file`);
                continue;
            }
            
            // Check max files limit
            if (this.files.length + validFiles.length >= this.options.maxFiles) {
                errors.push(`Maximum ${this.options.maxFiles} files allowed`);
                break;
            }
            
            validFiles.push(file);
        }
        
        if (validFiles.length > 0) {
            this.files.push(...validFiles);
            this.updateFileList();
            this.showToast(`${validFiles.length} file(s) added`, 'success');
        }
        
        if (errors.length > 0) {
            errors.forEach(error => this.showToast(error, 'error'));
        }
    }
    
    /**
     * Update file list display
     */
    updateFileList() {
        const fileListContainer = document.getElementById('fileListContainer');
        const fileList = document.getElementById('fileList');
        const fileCount = document.getElementById('fileCount');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (!fileListContainer) return;
        
        if (this.files.length === 0) {
            fileListContainer.style.display = 'none';
            if (uploadBtn) uploadBtn.disabled = true;
            return;
        }
        
        fileListContainer.style.display = 'block';
        if (fileCount) fileCount.textContent = this.files.length;
        if (uploadBtn) uploadBtn.disabled = false;
        
        const html = this.files.map((file, index) => `
            <div class="file-item" data-index="${index}">
                <div class="file-info">
                    <div class="file-name">
                        <i class="fas ${this.getFileIcon(file.type)}"></i>
                        <span class="filename">${this.escapeHtml(file.name)}</span>
                        <span class="file-size text-muted ms-2">(${(file.size / 1024).toFixed(1)} KB)</span>
                    </div>
                    <div class="file-progress" id="progress-${index}" style="display: none;">
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">Uploading...</small>
                    </div>
                    <div class="file-status" id="status-${index}"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        
        fileList.innerHTML = html;
        
        // Add remove handlers
        document.querySelectorAll('.remove-file').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(btn.dataset.index);
                this.removeFile(index);
            });
        });
    }
    
    /**
     * Remove file from queue
     * @param {number} index - File index
     */
    removeFile(index) {
        if (this.uploading) {
            this.showToast('Cannot remove files during upload', 'warning');
            return;
        }
        
        this.files.splice(index, 1);
        this.updateFileList();
        this.showToast('File removed', 'info');
    }
    
    /**
     * Clear all files
     */
    clearAllFiles() {
        if (this.uploading) {
            this.showToast('Cannot clear files during upload', 'warning');
            return;
        }
        
        if (this.files.length > 0 && confirm(`Remove all ${this.files.length} files?`)) {
            this.files = [];
            this.updateFileList();
            this.showToast('All files cleared', 'info');
        }
    }
    
    /**
     * Start upload process
     */
    async startUpload() {
        if (this.files.length === 0) {
            this.showToast('No files to upload', 'warning');
            return;
        }
        
        const documentType = this.getDocumentType();
        if (!documentType) {
            this.showToast('Please select document type', 'warning');
            return;
        }
        
        const autoImport = document.getElementById('auto_import')?.checked || false;
        
        this.uploading = true;
        this.uploadResults = [];
        this.updateUploadButtonState(true);
        
        // Show progress indicators
        this.files.forEach((_, index) => {
            const progressDiv = document.getElementById(`progress-${index}`);
            if (progressDiv) progressDiv.style.display = 'block';
        });
        
        // Upload files sequentially
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            this.currentUpload = { index: i, file };
            
            try {
                const result = await this.uploadSingleFile(file, documentType, autoImport);
                this.uploadResults.push({
                    file: file.name,
                    success: true,
                    result: result
                });
                this.updateFileStatus(i, 'success', 'Completed');
                this.showToast(`${file.name} uploaded successfully`, 'success');
            } catch (error) {
                console.error(`Upload failed for ${file.name}:`, error);
                this.uploadResults.push({
                    file: file.name,
                    success: false,
                    error: error.message
                });
                this.updateFileStatus(i, 'error', error.message);
                this.showToast(`${file.name}: ${error.message}`, 'error');
            }
        }
        
        this.uploading = false;
        this.currentUpload = null;
        this.updateUploadButtonState(false);
        
        this.showUploadSummary();
        
        // Refresh pending uploads list
        this.loadRecentUploads();
        
        // Ask to clear completed files
        if (this.uploadResults.every(r => r.success)) {
            setTimeout(() => {
                if (confirm('All files uploaded successfully. Clear the list?')) {
                    this.clearAllFiles();
                }
            }, 500);
        }
    }
    
    /**
     * Upload single file
     * @param {File} file - File to upload
     * @param {string} documentType - Document type
     * @param {boolean} autoImport - Auto-import after extraction
     * @returns {Promise} Upload result
     */
    async uploadSingleFile(file, documentType, autoImport) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('document_type', documentType);
        formData.append('auto_import', autoImport ? '1' : '0');
        
        // Get auth token
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    this.updateFileProgress(this.files.indexOf(file), percent);
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || 'Upload failed'));
                        }
                    } catch (e) {
                        reject(new Error('Invalid response from server'));
                    }
                } else {
                    reject(new Error(`Server error: ${xhr.status}`));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });
            
            xhr.open('POST', this.options.uploadUrl);
            xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            xhr.send(formData);
        });
    }
    
    /**
     * Cancel current upload
     */
    cancelUpload() {
        if (this.uploading && this.currentUpload) {
            if (confirm('Cancel ongoing upload?')) {
                // Note: XMLHttpRequest doesn't have a built-in cancel method
                // This would require aborting the XHR, which is complex with multiple files
                this.showToast('Upload will be cancelled after current file', 'warning');
                this.uploading = false;
            }
        }
    }
    
    /**
     * Update file progress
     * @param {number} index - File index
     * @param {number} percent - Progress percentage
     */
    updateFileProgress(index, percent) {
        const fileItem = document.querySelector(`.file-item[data-index="${index}"]`);
        if (fileItem) {
            const progressBar = fileItem.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = `${percent}%`;
            }
        }
    }
    
    /**
     * Update file status
     * @param {number} index - File index
     * @param {string} status - Status type
     * @param {string} message - Status message
     */
    updateFileStatus(index, status, message) {
        const statusDiv = document.getElementById(`status-${index}`);
        if (!statusDiv) return;
        
        const statusColors = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
        };
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        statusDiv.innerHTML = `
            <span class="badge bg-${statusColors[status] || 'secondary'}">
                <i class="fas ${icons[status] || 'fa-circle'} me-1"></i>
                ${this.escapeHtml(message)}
            </span>
        `;
        
        // Hide progress bar on completion
        if (status === 'success' || status === 'error') {
            const progressDiv = document.getElementById(`progress-${index}`);
            if (progressDiv) {
                setTimeout(() => {
                    progressDiv.style.display = 'none';
                }, 1000);
            }
        }
    }
    
    /**
     * Update upload button state
     * @param {boolean} uploading - Whether uploading is in progress
     */
    updateUploadButtonState(uploading) {
        const uploadBtn = document.getElementById('uploadBtn');
        const clearBtn = document.getElementById('clearFilesBtn');
        const cancelBtn = document.getElementById('cancelUploadBtn');
        
        if (uploadBtn) {
            uploadBtn.disabled = uploading;
            uploadBtn.innerHTML = uploading ? 
                '<span class="spinner-border spinner-border-sm me-2"></span> Uploading...' : 
                '<i class="fas fa-upload me-2"></i> Upload All Files';
        }
        
        if (clearBtn) clearBtn.disabled = uploading;
        if (cancelBtn) cancelBtn.disabled = !uploading;
    }
    
    /**
     * Show upload summary
     */
    showUploadSummary() {
        const successCount = this.uploadResults.filter(r => r.success).length;
        const failCount = this.uploadResults.filter(r => !r.success).length;
        
        let message = `Upload complete: ${successCount} successful`;
        if (failCount > 0) {
            message += `, ${failCount} failed`;
        }
        
        this.showToast(message, failCount > 0 ? 'warning' : 'success');
        
        // Show detailed summary in modal if there are failures
        if (failCount > 0) {
            const failures = this.uploadResults.filter(r => !r.success);
            let details = '<div class="alert alert-danger"><strong>Failed Uploads:</strong><ul>';
            failures.forEach(f => {
                details += `<li>${this.escapeHtml(f.file)}: ${this.escapeHtml(f.error)}</li>`;
            });
            details += '</ul></div>';
            
            // You could show this in a modal
            console.log('Upload failures:', failures);
        }
    }
    
    /**
     * Load recent uploads
     */
    async loadRecentUploads() {
        const container = document.getElementById('recentUploads');
        if (!container) return;
        
        try {
            const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            const response = await fetch('/fibeco-bidding-system/api/upload/pending?limit=10', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.renderRecentUploads(container, result.data);
            }
        } catch (error) {
            console.error('Failed to load recent uploads:', error);
        }
    }
    
    /**
     * Render recent uploads
     * @param {HTMLElement} container - Container element
     * @param {Array} uploads - Uploads list
     */
    renderRecentUploads(container, uploads) {
        if (!uploads || uploads.length === 0) {
            container.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No recent uploads</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        const statusBadges = {
            'pending': 'secondary',
            'processing': 'info',
            'extracted': 'warning',
            'reviewed': 'primary',
            'imported': 'success',
            'failed': 'danger'
        };
        
        container.innerHTML = uploads.map(upload => `
            <tr>
                <td>${this.escapeHtml(upload.original_filename)}</td>
                <td><span class="badge bg-secondary">${upload.document_type?.replace('_', ' ') || 'N/A'}</span></td>
                <td>
                    <span class="badge bg-${statusBadges[upload.upload_status] || 'secondary'}">
                        ${upload.upload_status || 'unknown'}
                    </span>
                </td>
                <td>${formatDate(upload.created_at)}</td>
                <td>
                    <a href="/fibeco-bidding-system/admin/review-extraction.php?id=${upload.id}" 
                       class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Review
                    </a>
                </td>
            </tr>
        `).join('');
    }
    
    /**
     * Start auto-refresh for pending uploads
     */
    startAutoRefresh() {
        // Refresh every 30 seconds
        setInterval(() => {
            if (!this.uploading) {
                this.loadRecentUploads();
            }
        }, 30000);
    }
    
    /**
     * Get selected document type
     * @returns {string|null} Document type
     */
    getDocumentType() {
        const select = document.getElementById('document_type');
        if (select && select.value) {
            return select.value;
        }
        
        const radio = document.querySelector('input[name="document_type"]:checked');
        if (radio) {
            return radio.value;
        }
        
        // Try to get from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const typeParam = urlParams.get('type');
        if (typeParam) {
            return typeParam;
        }
        
        return null;
    }
    
    /**
     * Get file icon based on MIME type
     * @param {string} mimeType - File MIME type
     * @returns {string} FontAwesome icon class
     */
    getFileIcon(mimeType) {
        if (mimeType.includes('pdf')) return 'fa-file-pdf text-danger';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel text-success';
        if (mimeType.includes('word')) return 'fa-file-word text-primary';
        if (mimeType.includes('image')) return 'fa-file-image text-info';
        return 'fa-file-alt text-secondary';
    }
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Show toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type (success, error, warning, info)
     */
    showToast(message, type = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${this.escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 5000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    /**
     * Get upload statistics
     * @returns {Promise} Statistics
     */
    async getStats() {
        try {
            const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            const response = await fetch('/fibeco-bidding-system/api/upload/stats', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const result = await response.json();
            return result.success ? result.data : null;
        } catch (error) {
            console.error('Failed to get upload stats:', error);
            return null;
        }
    }
}

// Initialize upload manager when DOM is ready
let uploadManager = null;

document.addEventListener('DOMContentLoaded', function() {
    const uploadContainer = document.getElementById('upload-container');
    if (uploadContainer) {
        uploadManager = new UploadManager();
    }
});

// Make UploadManager globally available
window.UploadManager = UploadManager;
window.uploadManager = uploadManager;