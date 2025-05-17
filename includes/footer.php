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
                <p class="text-muted">Book your flights easily, travel comfortably, and explore new destinations with SkyWay Airlines.</p>
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
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>" class="text-decoration-none text-muted">Home</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>flights/search.php" class="text-decoration-none text-muted">Search Flights</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/manage.php" class="text-decoration-none text-muted">Manage Booking</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/check-in.php" class="text-decoration-none text-muted">Web Check-in</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>booking/status.php" class="text-decoration-none text-muted">Flight Status</a></li>
                </ul>
            </div>
            
            <!-- Information -->
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="text-uppercase mb-3">Information</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/about.php" class="text-decoration-none text-muted">About Us</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/contact.php" class="text-decoration-none text-muted">Contact Us</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/faq.php" class="text-decoration-none text-muted">FAQ</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/baggage.php" class="text-decoration-none text-muted">Baggage Information</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/terms.php" class="text-decoration-none text-muted">Terms & Conditions</a></li>
                    <li class="mb-2"><a href="<?php echo $baseUrl; ?>pages/privacy.php" class="text-decoration-none text-muted">Privacy Policy</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase mb-3">Contact Us</h6>
                <ul class="list-unstyled text-muted">
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
                    <p class="mb-0 text-muted small">&copy; <?php echo $currentYear; ?> SkyWay Airlines. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="d-flex justify-content-center justify-content-md-end">
                        <div class="me-3">
                            <a href="<?php echo $baseUrl; ?>db/db_test.php" class="text-muted text-decoration-none small">System Check</a>
                        </div>
                        <div class="me-3">
                            <a href="<?php echo $baseUrl; ?>sitemap.php" class="text-muted text-decoration-none small">Sitemap</a>
                        </div>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div>
                            <a href="<?php echo $baseUrl; ?>auth/login.php?admin=true" class="text-muted text-decoration-none small">Admin Login</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Browser Support Notice -->
            <div class="text-center mt-3">
                <small class="text-muted">Best viewed in latest versions of Chrome, Firefox, Safari and Edge browsers.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="#" class="btn btn-primary back-to-top" role="button" style="position: fixed; bottom: 20px; right: 20px; display: none;">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
    // Back to top button functionality
    window.addEventListener('scroll', function() {
        const backToTop = document.querySelector('.back-to-top');
        if (window.scrollY > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
</script>
