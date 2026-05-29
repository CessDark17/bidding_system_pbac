<?php
/**
 * Admin Sidebar Template
 * File: includes/templates/admin-sidebar.php
 * 
 * This template contains the navigation sidebar for admin pages.
 * It is included by admin-header.php.
 * 
 * Note: This file assumes admin session is already active.
 */

// Get current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?php echo ADMIN_URL; ?>" class="sidebar-logo">
            <i class="fas fa-gavel me-2"></i>
            <span>FIBECO Admin</span>
            <small class="d-block">Bidding System v<?php echo APP_VERSION; ?></small>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">MAIN</div>
            <a href="<?php echo ADMIN_URL; ?>" class="sidebar-item <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
        </div>
        
        <!-- Management Section -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">MANAGEMENT</div>
            
            <!-- Users -->
            <a href="<?php echo ADMIN_URL; ?>/users.php" class="sidebar-item <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="sidebar-text">Users</span>
            </a>
            
            <!-- Public Bidding -->
            <a href="<?php echo ADMIN_URL; ?>/public-bidding.php" class="sidebar-item <?php echo $currentPage == 'public-bidding.php' ? 'active' : ''; ?>">
                <i class="fas fa-gavel"></i>
                <span class="sidebar-text">Public Bidding</span>
            </a>
            
            <!-- Sealed Bidding -->
            <a href="<?php echo ADMIN_URL; ?>/sealed-bidding.php" class="sidebar-item <?php echo $currentPage == 'sealed-bidding.php' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i>
                <span class="sidebar-text">Sealed Bidding</span>
            </a>
            
            <!-- Procurement Monitoring -->
            <a href="<?php echo ADMIN_URL; ?>/procurement-monitoring.php" class="sidebar-item <?php echo $currentPage == 'procurement-monitoring.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span class="sidebar-text">Procurement</span>
            </a>
        </div>
        
        <!-- Import/Export Section -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">DATA OPERATIONS</div>
            
            <!-- Upload Documents -->
            <div class="sidebar-item has-submenu <?php echo in_array($currentPage, ['upload.php', 'batch-import.php', 'review-extraction.php']) ? 'open' : ''; ?>">
                <i class="fas fa-upload"></i>
                <span class="sidebar-text">Import</span>
                <i class="fas fa-chevron-down chevron-icon ms-auto"></i>
            </div>
            <ul class="sidebar-submenu <?php echo in_array($currentPage, ['upload.php', 'batch-import.php', 'review-extraction.php']) ? 'open' : ''; ?>">
                <li><a href="<?php echo ADMIN_URL; ?>/upload.php" class="<?php echo $currentPage == 'upload.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-upload"></i> Single Upload
                </a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/batch-import.php" class="<?php echo $currentPage == 'batch-import.php' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i> Batch Import
                </a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/review-extraction.php" class="<?php echo $currentPage == 'review-extraction.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-double"></i> Review Extracted
                </a></li>
            </ul>
            
            <!-- Export Reports -->
            <a href="<?php echo ADMIN_URL; ?>/reports.php" class="sidebar-item <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span class="sidebar-text">Reports</span>
            </a>
            
            <!-- Field Mapping -->
            <a href="<?php echo ADMIN_URL; ?>/field-mapping.php" class="sidebar-item <?php echo $currentPage == 'field-mapping.php' ? 'active' : ''; ?>">
                <i class="fas fa-code-branch"></i>
                <span class="sidebar-text">Field Mapping</span>
            </a>
        </div>
        
        <!-- System Section -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">SYSTEM</div>
            
            <!-- Activity Logs -->
            <a href="#" class="sidebar-item">
                <i class="fas fa-history"></i>
                <span class="sidebar-text">Activity Logs</span>
            </a>
            
            <!-- System Settings -->
            <a href="#" class="sidebar-item">
                <i class="fas fa-cog"></i>
                <span class="sidebar-text">Settings</span>
            </a>
            
            <!-- Back to Site -->
            <a href="<?php echo BASE_URL; ?>" class="sidebar-item" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span class="sidebar-text">View Site</span>
            </a>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-item text-muted">
            <i class="fas fa-database"></i>
            <span class="sidebar-text">FIBECO Bidding System</span>
        </div>
        <div class="sidebar-item text-muted">
            <i class="far fa-clock"></i>
            <span class="sidebar-text" id="serverTime"></span>
        </div>
    </div>
</aside>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                document.querySelector('.admin-main')?.classList.toggle('expanded');
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
    const submenuItems = document.querySelectorAll('.sidebar-item.has-submenu');
    submenuItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('open');
            const submenu = this.nextElementSibling;
            if (submenu && submenu.classList.contains('sidebar-submenu')) {
                submenu.classList.toggle('open');
            }
        });
    });
    
    // Update server time
    function updateServerTime() {
        const timeElement = document.getElementById('serverTime');
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('en-PH');
        }
    }
    updateServerTime();
    setInterval(updateServerTime, 1000);
    
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = window.location.pathname + '?search=' + encodeURIComponent(searchTerm);
                }
            }
        });
    }
    
    // Load notifications
    function loadNotifications() {
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        
        if (!notificationList) return;
        
        // This would be an API call in production
        // For now, show placeholder
        notificationList.innerHTML = `
            <div class="list-group-item text-center py-3 text-muted">
                No new notifications
            </div>
        `;
        
        if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }
    }
    
    loadNotifications();
    // Refresh notifications every 60 seconds
    setInterval(loadNotifications, 60000);
});

// Helper function for sidebar collapse state persistence
function setSidebarState(collapsed) {
    localStorage.setItem('sidebar_collapsed', collapsed);
}

function getSidebarState() {
    return localStorage.getItem('sidebar_collapsed') === 'true';
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth > 992 && getSidebarState()) {
        document.getElementById('adminSidebar')?.classList.add('collapsed');
        document.querySelector('.admin-main')?.classList.add('expanded');
    }
});
</script>

<style>
/* Sidebar footer styles */
.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.75rem 0;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: inherit;
}

.sidebar-footer .sidebar-item {
    padding: 0.5rem 1.5rem;
    font-size: 0.75rem;
}

.admin-sidebar.collapsed .sidebar-footer .sidebar-text {
    display: none;
}

/* Submenu styles */
.sidebar-submenu {
    list-style: none;
    padding-left: 3rem;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.sidebar-submenu.open {
    max-height: 300px;
}

.sidebar-submenu li a {
    display: block;
    padding: 0.5rem 0;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.sidebar-submenu li a:hover,
.sidebar-submenu li a.active {
    color: #fff;
    padding-left: 5px;
}

.sidebar-submenu li a i {
    width: 20px;
    margin-right: 8px;
    font-size: 0.75rem;
}

/* Dropdown notifications */
.dropdown-notifications {
    max-height: 400px;
    overflow-y: auto;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .admin-sidebar {
        transform: translateX(-100%);
        position: fixed;
        z-index: 1050;
        top: 0;
        left: 0;
        bottom: 0;
        width: 280px;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }
    
    .sidebar-overlay.show {
        display: block;
    }
    
    .admin-navbar .navbar-search {
        display: none !important;
    }
}

@media (min-width: 993px) {
    .admin-sidebar.collapsed {
        width: 70px;
    }
    
    .admin-sidebar.collapsed .sidebar-text,
    .admin-sidebar.collapsed .sidebar-nav-title,
    .admin-sidebar.collapsed .sidebar-footer .sidebar-text,
    .admin-sidebar.collapsed .chevron-icon {
        display: none;
    }
    
    .admin-sidebar.collapsed .sidebar-item {
        justify-content: center;
        padding: 0.75rem;
    }
    
    .admin-sidebar.collapsed .sidebar-item i {
        margin-right: 0;
        font-size: 1.25rem;
    }
    
    .admin-sidebar.collapsed .sidebar-submenu {
        display: none;
    }
    
    .admin-main.expanded {
        margin-left: 70px;
    }
}

.admin-main {
    margin-left: 280px;
    transition: margin-left 0.3s ease;
}
</style>