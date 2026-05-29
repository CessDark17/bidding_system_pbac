/**
 * Admin Panel Functionality
 * FIBECO Bidding System
 * File: admin.js
 * 
 * Handles admin-specific features: CRUD operations, form validation, dashboard charts
 */

// Admin Module
const AdminModule = {
    /**
     * Initialize admin panel
     */
    init: function() {
        this.initCharts();
        this.initFormValidation();
        this.initInlineEditing();
        this.initBulkActions();
        this.initExportButtons();
    },
    
    /**
     * Initialize dashboard charts
     */
    initCharts: function() {
        // Status distribution chart
        const statusCtx = document.getElementById('status-chart');
        if (statusCtx && window.statusChartData) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: window.statusChartData.labels,
                    datasets: [{
                        data: window.statusChartData.data,
                        backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        
        // Monthly trend chart
        const monthlyCtx = document.getElementById('monthly-chart');
        if (monthlyCtx && window.monthlyChartData) {
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: window.monthlyChartData.months,
                    datasets: [
                        {
                            label: 'Number of Bids',
                            data: window.monthlyChartData.counts,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Total Amount (₱ Millions)',
                            data: window.monthlyChartData.amounts,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { title: { display: true, text: 'Number of Bids' } },
                        y1: { position: 'right', title: { display: true, text: 'Amount (₱ Millions)' } }
                    }
                }
            });
        }
    },
    
    /**
     * Initialize form validation
     */
    initFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },
    
    /**
     * Initialize inline editing for tables
     */
    initInlineEditing: function() {
        const editableCells = document.querySelectorAll('.editable-cell');
        
        editableCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const field = this.dataset.field;
                const recordId = this.dataset.id;
                const currentValue = this.dataset.value;
                const type = this.dataset.type || 'text';
                
                let inputHtml = '';
                if (type === 'date') {
                    inputHtml = `<input type="date" class="form-control form-control-sm" value="${currentValue || ''}">`;
                } else if (type === 'select') {
                    const options = this.dataset.options ? JSON.parse(this.dataset.options) : [];
                    inputHtml = `<select class="form-select form-select-sm">
                        ${options.map(opt => `<option value="${opt}" ${opt === currentValue ? 'selected' : ''}>${opt}</option>`).join('')}
                    </select>`;
                } else {
                    inputHtml = `<input type="text" class="form-control form-control-sm" value="${escapeHtml(currentValue || '')}">`;
                }
                
                const originalContent = this.innerHTML;
                this.innerHTML = `
                    <div class="inline-edit-form">
                        ${inputHtml}
                        <div class="mt-1">
                            <button class="btn btn-sm btn-success save-edit">Save</button>
                            <button class="btn btn-sm btn-secondary cancel-edit">Cancel</button>
                        </div>
                    </div>
                `;
                
                const formDiv = this.querySelector('.inline-edit-form');
                const input = formDiv.querySelector('input, select');
                input.focus();
                
                formDiv.querySelector('.save-edit').addEventListener('click', async () => {
                    const newValue = input.value;
                    this.innerHTML = originalContent;
                    
                    try {
                        const result = await this.saveInlineEdit(recordId, field, newValue);
                        if (result.success) {
                            this.dataset.value = newValue;
                            this.innerHTML = type === 'date' ? formatDate(newValue) : escapeHtml(newValue || '-');
                            showToast('Updated successfully', 'success');
                        } else {
                            showToast(result.message || 'Update failed', 'error');
                        }
                    } catch (error) {
                        showToast('Update failed', 'error');
                    }
                });
                
                formDiv.querySelector('.cancel-edit').addEventListener('click', () => {
                    this.innerHTML = originalContent;
                });
            });
        });
    },
    
    /**
     * Save inline edit via API
     * @param {number} id - Record ID
     * @param {string} field - Field name
     * @param {*} value - New value
     * @returns {Promise} API response
     */
    saveInlineEdit: async function(id, field, value) {
        const table = document.querySelector('.data-table').dataset.entity;
        
        if (table === 'procurement') {
            return await FIBECOAPI.procurement.update(id, { [field]: value });
        } else if (table === 'public_bidding') {
            return await FIBECOAPI.bidding.updatePublic(id, { [field]: value });
        } else if (table === 'sealed_bidding') {
            return await FIBECOAPI.bidding.updateSealed(id, { [field]: value });
        }
        
        return { success: false, message: 'Unknown entity' };
    },
    
    /**
     * Initialize bulk actions
     */
    initBulkActions: function() {
        const selectAll = document.getElementById('select-all');
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.record-select');
                checkboxes.forEach(cb => cb.checked = this.checked);
                AdminModule.updateBulkActionButton();
            });
        }
        
        const checkboxes = document.querySelectorAll('.record-select');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => AdminModule.updateBulkActionButton());
        });
        
        if (bulkActionBtn) {
            bulkActionBtn.addEventListener('click', () => this.executeBulkAction());
        }
    },
    
    /**
     * Update bulk action button state
     */
    updateBulkActionButton: function() {
        const selected = document.querySelectorAll('.record-select:checked').length;
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        
        if (bulkActionBtn) {
            bulkActionBtn.disabled = selected === 0;
            bulkActionBtn.innerHTML = `<i class="fas fa-tasks"></i> Bulk Action (${selected})`;
        }
    },
    
    /**
     * Execute bulk action
     */
    executeBulkAction: async function() {
        const selected = [];
        document.querySelectorAll('.record-select:checked').forEach(cb => {
            selected.push(cb.value);
        });
        
        if (selected.length === 0) return;
        
        const action = prompt('Enter action (delete/export):', 'delete');
        if (!action) return;
        
        if (action === 'delete') {
            if (confirm(`Are you sure you want to delete ${selected.length} records?`)) {
                const table = document.querySelector('.data-table').dataset.entity;
                let successCount = 0;
                
                for (const id of selected) {
                    try {
                        if (table === 'procurement') {
                            await FIBECOAPI.procurement.delete(id);
                        } else if (table === 'public_bidding') {
                            await FIBECOAPI.bidding.deletePublic(id);
                        } else if (table === 'sealed_bidding') {
                            await FIBECOAPI.bidding.deleteSealed(id);
                        }
                        successCount++;
                    } catch (error) {
                        console.error(`Failed to delete record ${id}:`, error);
                    }
                }
                
                showToast(`Deleted ${successCount} of ${selected.length} records`, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        } else if (action === 'export') {
            // Implement export logic
            showToast('Export feature coming soon', 'info');
        }
    },
    
    /**
     * Initialize export buttons
     */
    initExportButtons: function() {
        const exportBtn = document.querySelector('.export-csv-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                const table = document.querySelector('.data-table');
                if (table) {
                    exportToCSV(table.id || 'data-table', 'export.csv');
                }
            });
        }
    },
    
    /**
     * Initialize delete confirmation
     */
    confirmDelete: function(url, message = 'Are you sure you want to delete this item?') {
        if (confirm(message)) {
            window.location.href = url;
        }
        return false;
    },
    
    /**
     * Toggle sidebar on mobile
     */
    toggleSidebar: function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar) {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        }
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    AdminModule.init();
});

// Make AdminModule globally available
window.AdminModule = AdminModule;