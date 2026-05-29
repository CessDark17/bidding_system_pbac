/**
 * Data Review Interface
 * FIBECO Bidding System
 * File: data-review.js
 * 
 * Handles review of extracted data, field editing, and approval/rejection
 */

// Data Review Module
const DataReviewModule = {
    // Current state
    documentId: null,
    documentType: null,
    extractedData: {},
    confidenceScore: 0,
    
    // DOM Elements
    elements: {},
    
    /**
     * Initialize data review module
     * @param {number} documentId - Document ID
     * @param {object} data - Extracted data
     */
    init: function(documentId, data) {
        this.documentId = documentId;
        this.extractedData = data.extracted_data || {};
        this.documentType = data.document_type;
        this.confidenceScore = data.confidence_score || 0;
        
        this.cacheElements();
        this.bindEvents();
        this.renderExtractedData();
        this.updateConfidenceDisplay();
        this.loadDocumentPreview();
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.elements = {
            form: document.getElementById('review-form'),
            extractedDataContainer: document.getElementById('extracted-data-fields'),
            confidenceBadge: document.getElementById('confidence-badge'),
            confidenceBar: document.getElementById('confidence-bar'),
            approveBtn: document.getElementById('approve-btn'),
            rejectBtn: document.getElementById('reject-btn'),
            rejectReason: document.getElementById('reject-reason'),
            rejectModal: document.getElementById('rejectModal'),
            documentPreview: document.getElementById('document-preview')
        };
    },
    
    /**
     * Bind event listeners
     */
    bindEvents: function() {
        if (this.elements.approveBtn) {
            this.elements.approveBtn.addEventListener('click', () => this.approveDocument());
        }
        
        if (this.elements.rejectBtn) {
            this.elements.rejectBtn.addEventListener('click', () => this.showRejectModal());
        }
        
        // Field change tracking for auto-save
        const fieldContainer = this.elements.extractedDataContainer;
        if (fieldContainer) {
            fieldContainer.addEventListener('change', (e) => {
                if (e.target.matches('input, select, textarea')) {
                    this.markAsEdited(e.target);
                }
            });
        }
    },
    
    /**
     * Render extracted data form fields
     */
    renderExtractedData: function() {
        if (!this.elements.extractedDataContainer) return;
        
        const fieldGroups = this.getFieldGroups();
        let html = '';
        
        for (const [groupName, fields] of Object.entries(fieldGroups)) {
            html += `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">${this.escapeHtml(groupName)}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
            `;
            
            for (const field of fields) {
                const value = this.extractedData[field.name] || '';
                const label = field.label;
                const type = field.type || 'text';
                const required = field.required ? 'required' : '';
                const options = field.options || [];
                
                html += `
                    <div class="col-md-6 mb-3">
                        <label class="form-label ${required}">${this.escapeHtml(label)}</label>
                `;
                
                if (type === 'textarea') {
                    html += `<textarea class="form-control" name="${field.name}" rows="2" ${required}>${this.escapeHtml(value)}</textarea>`;
                } else if (type === 'select' && options.length > 0) {
                    html += `<select class="form-select" name="${field.name}" ${required}>`;
                    html += `<option value="">Select...</option>`;
                    for (const opt of options) {
                        html += `<option value="${this.escapeHtml(opt)}" ${value === opt ? 'selected' : ''}>${this.escapeHtml(opt)}</option>`;
                    }
                    html += `</select>`;
                } else if (type === 'date') {
                    html += `<input type="date" class="form-control" name="${field.name}" value="${value}" ${required}>`;
                } else if (type === 'number') {
                    html += `<input type="number" step="0.01" class="form-control" name="${field.name}" value="${value}" ${required}>`;
                } else {
                    html += `<input type="text" class="form-control" name="${field.name}" value="${this.escapeHtml(value)}" ${required}>`;
                }
                
                if (field.help) {
                    html += `<small class="form-text text-muted">${this.escapeHtml(field.help)}</small>`;
                }
                
                html += `</div>`;
            }
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        }
        
        this.elements.extractedDataContainer.innerHTML = html;
    },
    
    /**
     * Get field groups based on document type
     * @returns {object} Field groups
     */
    getFieldGroups: function() {
        const commonFields = [
            { name: 'bidding_date', label: 'Bidding Date', type: 'date', required: true },
            { name: 'project_title', label: 'Project Title', type: 'textarea', required: true },
            { name: 'fund_source', label: 'Fund Source', type: 'select', required: true, 
              options: ['CAPEX Project', 'RFSC', 'General Fund', 'Government Funds'] },
            { name: 'status', label: 'Status', type: 'select', required: true,
              options: ['active', 'ongoing', 'completed', 'failed', 'cancelled'] }
        ];
        
        const publicFields = [
            { name: 'capex_project', label: 'CAPEX Project Code', type: 'text' },
            { name: 'approved_budget_contract', label: 'Approved Budget (ABC)', type: 'number', required: true },
            { name: 'winning_bidder', label: 'Winning Bidder', type: 'text' },
            { name: 'winning_bid_amount', label: 'Winning Bid Amount', type: 'number' },
            { name: 'participating_bidders', label: 'Participating Bidders', type: 'textarea', help: 'One bidder per line' },
            { name: 'notice_of_award', label: 'Notice of Award Date', type: 'date' },
            { name: 'contract_date', label: 'Contract Date', type: 'date' },
            { name: 'notice_to_proceed', label: 'Notice to Proceed Date', type: 'date' },
            { name: 'performance_bond_form', label: 'Performance Bond Form', type: 'select',
              options: ['', 'Cash', 'Bank Guarantee', 'Surety Bond'] },
            { name: 'performance_bond_amount', label: 'Performance Bond Amount', type: 'number' },
            { name: 'purchase_order_ref', label: 'Purchase Order Reference', type: 'text' }
        ];
        
        const sealedFields = [
            { name: 'winning_bidder', label: 'Winning Bidder', type: 'text' },
            { name: 'winning_bid_amount', label: 'Winning Bid Amount', type: 'number' },
            { name: 'participating_bidders', label: 'Participating Bidders', type: 'textarea', help: 'One bidder per line' },
            { name: 'contract_or_po_ref', label: 'Contract/PO Reference', type: 'text' },
            { name: 'confidential_notes', label: 'Confidential Notes', type: 'textarea', help: 'Internal notes (admin only)' }
        ];
        
        const procurementFields = [
            { name: 'itb_no', label: 'ITB Number', type: 'text' },
            { name: 'particulars', label: 'Particulars/Description', type: 'textarea', required: true },
            { name: 'abc', label: 'Approved Budget (ABC)', type: 'number' },
            { name: 'winning_bidder', label: 'Winning Bidder', type: 'text' },
            { name: 'winning_price', label: 'Winning Price', type: 'number' },
            { name: 'remarks', label: 'Remarks', type: 'textarea' },
            { name: 'delivery_date_per_po', label: 'Delivery Date (per PO)', type: 'date' },
            { name: 'actual_delivery_date', label: 'Actual Delivery Date', type: 'date' }
        ];
        
        // Add bidder fields for procurement
        if (this.documentType === 'procurement_monitoring') {
            for (let i = 1; i <= 5; i++) {
                procurementFields.push(
                    { name: `bidder_${i}`, label: `Bidder ${i} Name`, type: 'text' },
                    { name: `bidder_${i}_price`, label: `Bidder ${i} Price`, type: 'number' }
                );
            }
        }
        
        if (this.documentType === 'public_bidding') {
            return {
                'Basic Information': commonFields,
                'Budget & Bidding': publicFields
            };
        } else if (this.documentType === 'sealed_bidding') {
            return {
                'Basic Information': commonFields,
                'Confidential Bidding Information': sealedFields
            };
        } else {
            return {
                'Procurement Information': procurementFields
            };
        }
    },
    
    /**
     * Update confidence display
     */
    updateConfidenceDisplay: function() {
        if (this.elements.confidenceBadge) {
            let badgeClass = 'bg-secondary';
            if (this.confidenceScore >= 80) badgeClass = 'bg-success';
            else if (this.confidenceScore >= 60) badgeClass = 'bg-warning';
            else if (this.confidenceScore >= 40) badgeClass = 'bg-info';
            else badgeClass = 'bg-danger';
            
            this.elements.confidenceBadge.className = `badge ${badgeClass} fs-6`;
            this.elements.confidenceBadge.textContent = `${this.confidenceScore}%`;
        }
        
        if (this.elements.confidenceBar) {
            let barClass = 'bg-success';
            if (this.confidenceScore < 80) barClass = 'bg-warning';
            if (this.confidenceScore < 60) barClass = 'bg-info';
            if (this.confidenceScore < 40) barClass = 'bg-danger';
            
            this.elements.confidenceBar.className = `progress-bar ${barClass}`;
            this.elements.confidenceBar.style.width = `${this.confidenceScore}%`;
            this.elements.confidenceBar.textContent = `${this.confidenceScore}%`;
        }
    },
    
    /**
     * Load document preview
     */
    loadDocumentPreview: function() {
        if (!this.elements.documentPreview) return;
        
        // The preview is loaded via iframe or embed from the server
        // This is handled by the PHP template
    },
    
    /**
     * Mark field as edited (for tracking changes)
     * @param {HTMLElement} field - Field element
     */
    markAsEdited: function(field) {
        field.classList.add('field-edited');
        
        // Show save indicator
        const formGroup = field.closest('.col-md-6');
        if (formGroup && !formGroup.querySelector('.save-indicator')) {
            const indicator = document.createElement('small');
            indicator.className = 'save-indicator text-warning d-block mt-1';
            indicator.innerHTML = '<i class="fas fa-edit"></i> Modified - pending save';
            formGroup.appendChild(indicator);
            
            // Auto-save after 2 seconds of no changes
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => this.autoSave(), 2000);
        }
    },
    
    /**
     * Auto-save edited fields
     */
    autoSave: async function() {
        const formData = this.getFormData();
        
        try {
            const response = await fetch(`/fibeco-bidding-system/api/upload/${this.documentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify({
                    status: 'extracted',
                    extracted_data: formData
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSaveSuccess();
            }
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    },
    
    /**
     * Get form data
     * @returns {object} Form data
     */
    getFormData: function() {
        const form = this.elements.form;
        if (!form) return {};
        
        const formData = new FormData(form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    },
    
    /**
     * Show save success indicator
     */
    showSaveSuccess: function() {
        const indicators = document.querySelectorAll('.save-indicator');
        indicators.forEach(indicator => {
            indicator.innerHTML = '<i class="fas fa-check-circle text-success"></i> Saved';
            setTimeout(() => {
                indicator.remove();
            }, 2000);
        });
        
        const editedFields = document.querySelectorAll('.field-edited');
        editedFields.forEach(field => {
            field.classList.remove('field-edited');
        });
    },
    
    /**
     * Approve document and import to database
     */
    approveDocument: async function() {
        if (!confirm('Are you sure you want to approve and import this data?')) return;
        
        this.showLoading(true);
        
        const formData = this.getFormData();
        
        try {
            const response = await fetch(`/fibeco-bidding-system/admin/review-extraction.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: new URLSearchParams({
                    action: 'approve',
                    document_id: this.documentId,
                    ...formData,
                    csrf_token: document.querySelector('input[name="csrf_token"]')?.value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('Document approved and imported successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/fibeco-bidding-system/admin/batch-import.php';
                }, 1500);
            } else {
                this.showToast(result.message || 'Failed to import', 'error');
            }
        } catch (error) {
            console.error('Approval failed:', error);
            this.showToast('Failed to approve document', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    /**
     * Show reject modal
     */
    showRejectModal: function() {
        if (this.elements.rejectModal) {
            const modal = new bootstrap.Modal(this.elements.rejectModal);
            modal.show();
        }
    },
    
    /**
     * Reject document with reason
     */
    rejectDocument: async function() {
        const reason = this.elements.rejectReason?.value || 'No reason provided';
        
        if (!reason.trim()) {
            this.showToast('Please provide a reason for rejection', 'warning');
            return;
        }
        
        this.showLoading(true);
        
        try {
            const response = await fetch(`/fibeco-bidding-system/admin/review-extraction.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: new URLSearchParams({
                    action: 'reject',
                    document_id: this.documentId,
                    reason: reason,
                    csrf_token: document.querySelector('input[name="csrf_token"]')?.value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('Document rejected', 'warning');
                setTimeout(() => {
                    window.location.href = '/fibeco-bidding-system/admin/batch-import.php';
                }, 1500);
            } else {
                this.showToast(result.message || 'Failed to reject', 'error');
            }
        } catch (error) {
            console.error('Rejection failed:', error);
            this.showToast('Failed to reject document', 'error');
        } finally {
            this.showLoading(false);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(this.elements.rejectModal);
            if (modal) modal.hide();
        }
    },
    
    /**
     * Show/hide loading overlay
     * @param {boolean} show - Show or hide
     */
    showLoading: function(show) {
        let overlay = document.getElementById('loading-overlay');
        if (!overlay && show) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'spinner-overlay';
            overlay.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
            document.body.appendChild(overlay);
        }
        
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    },
    
    /**
     * Escape HTML
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Show toast notification
     * @param {string} message - Message
     * @param {string} type - Type
     */
    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toast.setAttribute('role', 'alert');
        toast.style.zIndex = '9999';
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const reviewContainer = document.getElementById('data-review-container');
    if (reviewContainer && reviewContainer.dataset.documentId) {
        const documentId = parseInt(reviewContainer.dataset.documentId);
        const documentData = window.extractedData || {};
        
        DataReviewModule.init(documentId, documentData);
    }
});

// Make DataReviewModule globally available
window.DataReviewModule = DataReviewModule;