<?php
// Check if function exists before defining it
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

$baseUrl = getBaseUrl();
$currentYear = date('Y');
?>

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Brand and About -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="mb-3 text-primary"><i class="fas fa-plane-departure me-2"></i>SkyWay Airlines</h5>
                <p class="text-light">Book your flights easily, travel comfortably, and explore new destinations with SkyWay Airlines.</p>
                <div class="social-links mt-3">
                    <a href="#" class="text-decoration-none me-3 text-light"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-decoration-none me-3 text-light"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-decoration-none me-3 text-light"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-decoration-none text-light"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-uppercase mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>" class="text-decoration-none text-light">Home</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>flights/search.php" class="text-decoration-none text-light">Search Flights</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/manage.php" class="text-decoration-none text-light">Manage Booking</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/check-in.php" class="text-decoration-none text-light">Web Check-in</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/status.php" class="text-decoration-none text-light">Flight Status</a></li>
                </ul>
            </div>
            
            <!-- Information -->
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-uppercase mb-3">Information</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/about.php" class="text-decoration-none text-light">About Us</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/contact.php" class="text-decoration-none text-light">Contact Us</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/faq.php" class="text-decoration-none text-light">FAQ</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/baggage.php" class="text-decoration-none text-light">Baggage Information</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/terms.php" class="text-decoration-none text-light">Terms & Conditions</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/privacy.php" class="text-decoration-none text-light">Privacy Policy</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase mb-3">Contact Us</h6>
                <ul class="list-unstyled text-light">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Airport Road, Metro Manila, Philippines</li>
                    <li class="mb-2"><i class="fas fa-phone-alt me-2"></i> +63 (2) 8123 4567</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@skywayairlines.com</li>
                    <li class="mt-3">
                        <a href="<?php echo $baseUrl; ?>pages/contact.php" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-paper-plane me-1"></i> Send Message
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Footer -->
        <div class="border-top border-secondary pt-4 mt-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-light">&copy; <?php echo $currentYear; ?> SkyWay Airlines. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="d-flex justify-content-center justify-content-md-end">
                        <div class="me-3">
                            <a href="<?php echo $baseUrl; ?>db/db_test.php" class="text-light text-decoration-none small">System Check</a>
                        </div>
                        <div class="me-3">
                            <a href="<?php echo $baseUrl; ?>sitemap.php" class="text-light text-decoration-none small">Sitemap</a>
                        </div>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div>
                            <a href="<?php echo $baseUrl; ?>auth/login.php?admin=true" class="text-light text-decoration-none small">Admin Login</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Browser Support Notice -->
            <div class="text-center mt-3">
                <small class="text-light">Best viewed in latest versions of Chrome, Firefox, Safari and Edge browsers.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="#" class="btn btn-primary back-to-top" role="button" style="position: fixed; bottom: 20px; right: 20px; display: none;">
    <i class="fas fa-arrow-up"></i>
</a>

<!-- Avoid loading Bootstrap JS again if it's already loaded in navbar -->
<script>
    if (typeof bootstrap === 'undefined') {
        // Load Bootstrap JS only if not already loaded
        var bootstrapScript = document.createElement('script');
        bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js';
        document.body.appendChild(bootstrapScript);
    }
</script>

<!-- Custom JS -->
<?php if (!isset($GLOBALS['main_js_loaded'])): ?>
<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
<?php $GLOBALS['main_js_loaded'] = true; endif; ?>

<script>
    // Back to top button functionality
    window.addEventListener('scroll', function() {
        const backToTop = document.querySelector('.back-to-top');
        if (backToTop) {
            if (window.scrollY > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        }
    });
</script>

<?php
// End and flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>
</body>
</html>
