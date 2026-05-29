/**
 * Main JavaScript File
 * FIBECO Bidding System
 * File: main.js
 * 
 * Global functions, initialization, and common utilities
 */

// ======================================================
// DOM Ready Initialization
// ======================================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    initializeTooltips();
    
    // Initialize DataTables
    initializeDataTables();
    
    // Initialize auto-dismiss alerts
    initializeAutoDismissAlerts();
    
    // Initialize confirmation dialogs
    initializeConfirmDialogs();
    
    // Initialize date pickers
    initializeDatePickers();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize sidebar toggle (admin)
    initializeSidebar();
});

// ======================================================
// Tooltips
// ======================================================
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ======================================================
// DataTables
// ======================================================
function initializeDataTables() {
    var dataTables = document.querySelectorAll('.data-table');
    if (dataTables.length > 0 && typeof $ !== 'undefined' && $.fn.DataTable) {
        $(dataTables).each(function() {
            var $table = $(this);
            var options = {
                pageLength: 25,
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            };
            
            // Add custom options if specified
            if ($table.data('page-length')) {
                options.pageLength = $table.data('page-length');
            }
            if ($table.data('ordering') === false) {
                options.ordering = false;
            }
            
            $table.DataTable(options);
        });
    }
}

// ======================================================
// Auto-dismiss Alerts
// ======================================================
function initializeAutoDismissAlerts() {
    var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
}

// ======================================================
// Confirmation Dialogs
// ======================================================
function initializeConfirmDialogs() {
    // Delete confirmation
    var deleteButtons = document.querySelectorAll('.confirm-delete, [data-confirm="delete"]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Status change confirmation
    var statusButtons = document.querySelectorAll('[data-confirm="status"]');
    statusButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to change the status of this item?')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ======================================================
// Date Pickers
// ======================================================
function initializeDatePickers() {
    var dateInputs = document.querySelectorAll('input[type="date"]');
    if (dateInputs.length > 0 && typeof flatpickr !== 'undefined') {
        flatpickr(dateInputs, {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }
}

// ======================================================
// Search Functionality
// ======================================================
function initializeSearch() {
    var searchInputs = document.querySelectorAll('.search-input[data-search-delay]');
    searchInputs.forEach(function(input) {
        var delay = parseInt(input.dataset.searchDelay) || 300;
        var searchFunction = debounce(function() {
            var form = input.closest('form');
            if (form) {
                form.submit();
            }
        }, delay);
        
        input.addEventListener('input', searchFunction);
    });
}

// ======================================================
// Sidebar Toggle (Admin)
// ======================================================
function initializeSidebar() {
    var toggleBtn = document.querySelector('.navbar-toggle');
    var sidebar = document.querySelector('.admin-sidebar');
    var mainContent = document.querySelector('.admin-main');
    var overlay = document.querySelector('.sidebar-overlay');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContent) mainContent.classList.toggle('expanded');
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    
    // Submenu toggle
    var submenuItems = document.querySelectorAll('.sidebar-item.has-submenu');
    submenuItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('open');
            var submenu = this.nextElementSibling;
            if (submenu && submenu.classList.contains('sidebar-submenu')) {
                submenu.classList.toggle('open');
            }
        });
    });
}

// ======================================================
// Utility Functions
// ======================================================

/**
 * Format currency for display
 * @param {number} amount - Amount to format
 * @param {boolean} withSymbol - Include PHP symbol
 * @returns {string} Formatted currency
 */
function formatCurrency(amount, withSymbol = true) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return withSymbol ? '₱ 0.00' : '0.00';
    }
    var formatted = parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return withSymbol ? '₱ ' + formatted : formatted;
}

/**
 * Parse currency string to number
 * @param {string} currencyString - Currency string
 * @returns {number} Numeric value
 */
function parseCurrency(currencyString) {
    if (!currencyString) return 0;
    return parseFloat(currencyString.replace(/[^0-9.-]/g, ''));
}

/**
 * Format date for display
 * @param {string|Date} date - Date to format
 * @param {string} format - Format pattern
 * @returns {string} Formatted date
 */
function formatDate(date, format = 'MMM DD, YYYY') {
    if (!date) return 'N/A';
    var d = new Date(date);
    if (isNaN(d.getTime())) return 'Invalid Date';
    
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var month = months[d.getMonth()];
    var day = d.getDate();
    var year = d.getFullYear();
    
    return format
        .replace('MMM', month)
        .replace('DD', day.toString().padStart(2, '0'))
        .replace('YYYY', year);
}

/**
 * Show loading spinner on element
 * @param {HTMLElement|string} element - Element or ID
 */
function showLoading(element) {
    var el = typeof element === 'string' ? document.getElementById(element) : element;
    if (!el) return;
    
    var originalContent = el.innerHTML;
    el.dataset.originalContent = originalContent;
    el.disabled = true;
    el.innerHTML = '<span class="btn-spinner"></span> Loading...';
}

/**
 * Hide loading spinner and restore content
 * @param {HTMLElement|string} element - Element or ID
 */
function hideLoading(element) {
    var el = typeof element === 'string' ? document.getElementById(element) : element;
    if (!el || !el.dataset.originalContent) return;
    
    el.innerHTML = el.dataset.originalContent;
    el.disabled = false;
    delete el.dataset.originalContent;
}

/**
 * Debounce function for search inputs
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    var timeout;
    return function executedFunction() {
        var later = function() {
            clearTimeout(timeout);
            func();
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    var toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    container.appendChild(toast);
    var bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 * @returns {Promise}
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy', 'error');
    }
}

/**
 * Export table to CSV
 * @param {string} tableId - Table element ID
 * @param {string} filename - Export filename
 */
function exportToCSV(tableId, filename = 'export.csv') {
    var table = document.getElementById(tableId);
    if (!table) {
        showToast('Table not found', 'error');
        return;
    }
    
    var rows = table.querySelectorAll('tr');
    var csv = [];
    
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        var rowData = Array.from(cells).map(function(cell) {
            var text = cell.innerText.trim();
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = text.replace(/"/g, '""');
                text = `"${text}"`;
            }
            return text;
        }).filter(function(text) { return text !== ''; });
        
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    if (csv.length === 0) {
        showToast('No data to export', 'warning');
        return;
    }
    
    var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
    showToast('Export completed', 'success');
}

/**
 * Print current page or specific element
 * @param {string} elementId - Optional element ID to print
 */
function printPage(elementId) {
    if (elementId) {
        var content = document.getElementById(elementId);
        if (content) {
            var printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Print</title>');
            printWindow.document.write('<link rel="stylesheet" href="assets/css/style.css">');
            printWindow.document.write('<link rel="stylesheet" href="assets/css/responsive.css">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    } else {
        window.print();
    }
}

// ======================================================
// Auto-refresh for pending uploads
// ======================================================
if (document.querySelector('.auto-refresh')) {
    var refreshInterval = setInterval(function() {
        var container = document.querySelector('.pending-uploads-container');
        if (container && container.dataset.refreshUrl) {
            fetch(container.dataset.refreshUrl)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => console.error('Auto-refresh failed:', error));
        }
    }, 30000); // Refresh every 30 seconds
}

// ======================================================
// Export for module usage
// ======================================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatCurrency,
        parseCurrency,
        formatDate,
        showLoading,
        hideLoading,
        showToast,
        escapeHtml,
        copyToClipboard,
        exportToCSV,
        printPage,
        debounce
    };
}