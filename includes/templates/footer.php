<?php
/**
 * Footer Template
 * FIBECO Bidding System
 * File: includes/templates/footer.php
 * 
 * This template is included at the bottom of all frontend pages.
 * It contains footer content with links to all major pages.
 */
?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-3">
                    <div class="electric-logo" style="width: 45px; height: 45px;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0 fw-bold">FIBECO, Incorporated</h5>
                        <small class="text-light-50">First Bukidnon Electric Cooperative, Inc.</small>
                    </div>
                </div>
                <p class="small text-light-50 text-center text-lg-start">
                    Powering Progress Through Transparent Procurement.<br>
                    Committed to reliable and affordable electricity for all.
                </p>
                <div class="d-flex justify-content-center justify-content-lg-start gap-3 mt-3">
                    <a href="https://www.facebook.com/fibecopro" target="_blank" class="text-light">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/fibecopro" target="_blank" class="text-light">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.threads.net/@fibecopro" target="_blank" class="text-light">
                        <i class="fab fa-threads"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/fibecopro" target="_blank" class="text-light">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="https://www.tiktok.com/@fibecopro" target="_blank" class="text-light">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    <li class="mb-2"><a href="privacy-policy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="terms.php" class="text-light text-decoration-none">Terms of Use</a></li>
                </ul>
            </div>
            
            <!-- Bidding Links -->
            <div class="col-lg-3 col-md-4 mb-4 mb-lg-0">
                <h6 class="fw-bold mb-3">Bidding</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Public Bidding</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="mb-2"><a href="dashboard.php" class="text-light text-decoration-none">Sealed Bidding</a></li>
                    <?php else: ?>
                        <li class="mb-2"><a href="login.php" class="text-light text-decoration-none">Login to Access Sealed Bidding</a></li>
                    <?php endif; ?>
                    <li class="mb-2"><a href="how-to-bid.php" class="text-light text-decoration-none">How to Bid</a></li>
                    <li class="mb-2"><a href="bid-calendar.php" class="text-light text-decoration-none">Bidding Calendar</a></li>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-3 col-md-4">
                <h6 class="fw-bold mb-3">Contact Us</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Anahawon, Maramag, Bukidnon
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        Globe: 0917-795-1451 | Smart: 0950-768-6902
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        contact_us@fibeco.ph
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-globe me-2"></i>
                        www.fibeco.ph
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Divider -->
        <hr class="border-light opacity-25 my-4">
        
        <!-- Bottom Footer -->
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="small mb-0">
                    &copy; <?php echo date('Y'); ?> FIBECO, Incorporated. All Rights Reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="small mb-0">
                    <i class="fas fa-shield-alt me-1"></i> Secured Bidding Portal | Version <?php echo APP_VERSION; ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        background: linear-gradient(135deg, var(--electric-dark, #061a30), var(--electric-blue, #0a2a4a));
        color: #ffffff;
        margin-top: 60px;
        padding: 40px 0 20px;
        border-top: 2px solid var(--electric-accent, #00d4ff);
    }
    
    .footer a {
        transition: all 0.3s ease;
    }
    
    .footer a:hover {
        color: var(--electric-accent, #00d4ff) !important;
        padding-left: 5px;
    }
    
    .footer .electric-logo {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #ffd700, #ff8c00);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: footerPulse 2s infinite;
    }
    
    .footer .electric-logo i {
        font-size: 24px;
        color: #061a30;
    }
    
    @keyframes footerPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .text-light-50 {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .footer h6 {
        position: relative;
        display: inline-block;
        padding-bottom: 8px;
    }
    
    .footer h6::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: var(--electric-accent, #00d4ff);
    }
    
    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }
        .footer h6::after {
            left: 50%;
            transform: translateX(-50%);
        }
        .footer .d-flex {
            justify-content: center !important;
        }
    }
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>