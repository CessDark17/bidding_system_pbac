<?php
/**
 * Admin Footer Template
 * File: includes/templates/admin-footer.php
 * 
 * This template is included at the bottom of all admin pages.
 * It contains closing tags and scripts.
 */
            </div> <!-- .container-fluid -->
        </div> <!-- .admin-main -->
    </div> <!-- .admin-wrapper -->
    
    <!-- Vendor Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/api.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/admin.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/upload-manager.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/data-review.js"></script>
    
    <!-- Page Specific Scripts -->
    <?php if (isset($additionalScripts)): ?>
        <?php echo $additionalScripts; ?>
    <?php endif; ?>
    
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>
    
    <script>
        // Initialize date pickers on admin pages
        document.querySelectorAll('input[type="date"]').forEach(function(el) {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(el, {
                    dateFormat: "Y-m-d",
                    allowInput: true
                });
            }
        });
        
        // Confirm delete for admin actions
        document.querySelectorAll('.confirm-delete, [data-confirm="delete"]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>