/**
 * Bidding Functionality
 * FIBECO Bidding System
 * File: bidding.js
 * 
 * Handles bidding list display, filtering, and interactions
 */

// Bidding Module
const BiddingModule = {
    // Current state
    currentPage: 1,
    currentType: 'public',
    filters: {
        search: '',
        status: '',
        fund_source: '',
        start_date: '',
        end_date: ''
    },
    
    // DOM Elements
    elements: {},
    
    /**
     * Initialize bidding module
     * @param {string} type - 'public' or 'sealed'
     */
    init: function(type = 'public') {
        this.currentType = type;
        this.cacheElements();
        this.bindEvents();
        this.loadData();
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.elements = {
            container: document.getElementById('bidding-container'),
            tableBody: document.getElementById('bidding-table-body'),
            pagination: document.getElementById('bidding-pagination'),
            searchInput: document.getElementById('search-input'),
            statusFilter: document.getElementById('status-filter'),
            fundSourceFilter: document.getElementById('fund-source-filter'),
            dateRange: document.getElementById('date-range'),
            refreshBtn: document.getElementById('refresh-btn'),
            exportBtn: document.getElementById('export-btn'),
            loadingOverlay: document.getElementById('loading-overlay')
        };
    },
    
    /**
     * Bind event listeners
     */
    bindEvents: function() {
        // Search with debounce
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', debounce(() => {
                this.filters.search = this.elements.searchInput.value;
                this.currentPage = 1;
                this.loadData();
            }, 500));
        }
        
        // Status filter
        if (this.elements.statusFilter) {
            this.elements.statusFilter.addEventListener('change', () => {
                this.filters.status = this.elements.statusFilter.value;
                this.currentPage = 1;
                this.loadData();
            });
        }
        
        // Fund source filter
        if (this.elements.fundSourceFilter) {
            this.elements.fundSourceFilter.addEventListener('change', () => {
                this.filters.fund_source = this.elements.fundSourceFilter.value;
                this.currentPage = 1;
                this.loadData();
            });
        }
        
        // Refresh button
        if (this.elements.refreshBtn) {
            this.elements.refreshBtn.addEventListener('click', () => {
                this.loadData();
            });
        }
        
        // Export button
        if (this.elements.exportBtn) {
            this.elements.exportBtn.addEventListener('click', () => {
                this.exportData();
            });
        }
    },
    
    /**
     * Load bidding data from API
     */
    loadData: async function() {
        this.showLoading(true);
        
        try {
            const params = {
                page: this.currentPage,
                limit: 20,
                search: this.filters.search,
                status: this.filters.status,
                fund_source: this.filters.fund_source,
                start_date: this.filters.start_date,
                end_date: this.filters.end_date
            };
            
            let result;
            if (this.currentType === 'public') {
                result = await FIBECOAPI.bidding.getPublicList(params);
            } else {
                result = await FIBECOAPI.bidding.getSealedList(params);
            }
            
            if (result.success) {
                this.renderTable(result.data.records);
                this.renderPagination(result.data.pagination);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Failed to load bidding data:', error);
            this.showError('Failed to load data. Please try again.');
        } finally {
            this.showLoading(false);
        }
    },
    
    /**
     * Render bidding table
     * @param {array} records - Bidding records
     */
    renderTable: function(records) {
        if (!this.elements.tableBody) return;
        
        if (!records || records.length === 0) {
            this.elements.tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No bidding records found.</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        const html = records.map(record => this.renderTableRow(record)).join('');
        this.elements.tableBody.innerHTML = html;
        
        // Re-initialize tooltips for new elements
        initializeTooltips();
    },
    
    /**
     * Render single table row
     * @param {object} record - Bidding record
     * @returns {string} HTML string
     */
    renderTableRow: function(record) {
        const isPublic = this.currentType === 'public';
        const statusBadge = this.getStatusBadge(record.status);
        
        return `
            <tr>
                <td>${formatDate(record.bidding_date)}</td>
                <td class="text-wrap" style="max-width: 300px;">
                    <strong>${escapeHtml(record.project_title)}</strong>
                    ${isPublic && record.capex_project ? `<br><small class="text-muted">CAPEX: ${escapeHtml(record.capex_project)}</small>` : ''}
                </td>
                <td>${escapeHtml(record.fund_source)}</td>
                ${isPublic ? `<td class="text-end">${formatCurrency(record.approved_budget_contract)}</td>` : ''}
                <td>${escapeHtml(record.winning_bidder || 'N/A')}</td>
                <td class="text-end">${record.winning_bid_amount ? formatCurrency(record.winning_bid_amount) : 'N/A'}</td>
                <td>${statusBadge}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary view-details" 
                            data-id="${record.id}" data-type="${this.currentType}"
                            data-bs-toggle="modal" data-bs-target="#bidDetailsModal">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    },
    
    /**
     * Get status badge HTML
     * @param {string} status - Status value
     * @returns {string} HTML badge
     */
    getStatusBadge: function(status) {
        const badges = {
            'active': 'success',
            'ongoing': 'primary',
            'completed': 'info',
            'failed': 'danger',
            'cancelled': 'secondary',
            'awarded': 'success'
        };
        
        const color = badges[status] || 'secondary';
        return `<span class="badge bg-${color}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    },
    
    /**
     * Render pagination
     * @param {object} pagination - Pagination data
     */
    renderPagination: function(pagination) {
        if (!this.elements.pagination) return;
        
        if (!pagination || pagination.total_pages <= 1) {
            this.elements.pagination.innerHTML = '';
            return;
        }
        
        let html = '<ul class="pagination justify-content-center">';
        
        // Previous button
        const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
                 </li>`;
        
        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            html += `<li class="page-item ${active}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }
        
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`;
        }
        
        // Next button
        const nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
                 </li>`;
        
        html += '</ul>';
        this.elements.pagination.innerHTML = html;
        
        // Bind pagination click events
        this.elements.pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                if (!isNaN(page) && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadData();
                }
            });
        });
    },
    
    /**
     * Show/hide loading overlay
     * @param {boolean} show - Show or hide
     */
    showLoading: function(show) {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    },
    
    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError: function(message) {
        showToast(message, 'error');
    },
    
    /**
     * Export data to CSV
     */
    exportData: function() {
        const url = FIBECOAPI.bidding.getExportUrl(this.currentType);
        window.open(url, '_blank');
    },
    
    /**
     * Show bidding details modal
     * @param {number} id - Record ID
     * @param {string} type - 'public' or 'sealed'
     */
    showDetails: async function(id, type) {
        try {
            let record;
            if (type === 'public') {
                record = await FIBECOAPI.bidding.getPublicDetail(id);
            } else {
                record = await FIBECOAPI.bidding.getSealedDetail(id);
            }
            
            if (record.success) {
                this.renderDetailsModal(record.data);
            }
        } catch (error) {
            console.error('Failed to load details:', error);
            showToast('Failed to load details', 'error');
        }
    },
    
    /**
     * Render details modal content
     * @param {object} record - Bidding record
     */
    renderDetailsModal: function(record) {
        const modalContent = document.getElementById('details-modal-content');
        if (!modalContent) return;
        
        const isPublic = record.hasOwnProperty('approved_budget_contract');
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Bidding Date:</strong><br>
                    ${formatDate(record.bidding_date, 'MMMM DD, YYYY')}
                    <hr>
                    <strong>Fund Source:</strong><br>
                    ${escapeHtml(record.fund_source)}
                    ${isPublic && record.capex_project ? `<br><small>CAPEX: ${escapeHtml(record.capex_project)}</small>` : ''}
                </div>
                <div class="col-md-6">
                    <strong>Status:</strong><br>
                    ${this.getStatusBadge(record.status)}
                    <hr>
                    <strong>Winning Bidder:</strong><br>
                    ${escapeHtml(record.winning_bidder || 'Not yet awarded')}
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    <strong>Project Title:</strong><br>
                    <p>${escapeHtml(record.project_title)}</p>
                </div>
            </div>
        `;
        
        if (isPublic) {
            html += `
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Approved Budget (ABC):</strong><br>
                        ${formatCurrency(record.approved_budget_contract)}
                    </div>
                    <div class="col-md-6">
                        <strong>Winning Bid Amount:</strong><br>
                        ${record.winning_bid_amount ? formatCurrency(record.winning_bid_amount) : 'N/A'}
                    </div>
                </div>
            `;
        } else {
            html += `
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong>Winning Bid Amount:</strong><br>
                        ${record.winning_bid_amount ? formatCurrency(record.winning_bid_amount) : 'N/A'}
                    </div>
                </div>
            `;
        }
        
        if (record.participating_bidders) {
            html += `
                <hr>
                <strong>Participating Bidders:</strong><br>
                <p>${escapeHtml(record.participating_bidders).replace(/\n/g, '<br>')}</p>
            `;
        }
        
        if (isPublic && (record.notice_of_award || record.contract_date || record.notice_to_proceed)) {
            html += `
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Notice of Award:</strong><br>
                        ${formatDate(record.notice_of_award)}
                    </div>
                    <div class="col-md-4">
                        <strong>Contract Date:</strong><br>
                        ${formatDate(record.contract_date)}
                    </div>
                    <div class="col-md-4">
                        <strong>Notice to Proceed:</strong><br>
                        ${formatDate(record.notice_to_proceed)}
                    </div>
                </div>
            `;
        }
        
        if (record.confidential_notes) {
            html += `
                <hr>
                <div class="alert alert-secondary">
                    <strong><i class="fas fa-lock"></i> Confidential Notes:</strong><br>
                    ${escapeHtml(record.confidential_notes)}
                </div>
            `;
        }
        
        modalContent.innerHTML = html;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('bidDetailsModal'));
        modal.show();
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const biddingContainer = document.getElementById('bidding-container');
    if (biddingContainer) {
        const type = biddingContainer.dataset.type || 'public';
        BiddingModule.init(type);
    }
    
    // View details button delegation
    document.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.view-details');
        if (viewBtn) {
            const id = parseInt(viewBtn.dataset.id);
            const type = viewBtn.dataset.type;
            BiddingModule.showDetails(id, type);
        }
    });
});