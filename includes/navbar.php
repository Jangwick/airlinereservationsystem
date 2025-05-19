<?php
// Include functions.php if it exists, otherwise define getBaseUrl locally
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
} else if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

// Get base URL
$baseUrl = getBaseUrl();

// Set current page variable to highlight active navigation item
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="main-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?php echo isset($_SESSION['user_id']) ? $baseUrl . (($_SESSION['role'] === 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php') : $baseUrl; ?>">
            <i class="fas fa-plane-departure me-2"></i>SkyWay Airlines
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Navigation for non-logged in users -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <!-- Admin users only see minimal navigation in top bar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>">
                        <i class="fas fa-home me-1"></i> Site Home
                    </a>
                </li>
                <?php else: ?>
                <!-- Navigation for regular logged in users -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php' && $current_dir == 'user') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>user/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>user/bookings.php">
                        <i class="fas fa-ticket-alt me-1"></i> My Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>user/profile.php">
                        <i class="fas fa-user-edit me-1"></i> My Profile
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <!-- Don't show these navigation items to admin users -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_dir == 'flights' || strpos($current_page, 'flight') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>flights/search.php">
                        <i class="fas fa-plane me-1"></i> Flights
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($current_dir == 'booking') ? 'active' : ''; ?>" href="#" id="bookingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ticket-alt me-1"></i> Booking
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="bookingDropdown">
                        <li><a class="dropdown-item <?php echo ($current_page == 'manage.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>booking/manage.php">
                            <i class="fas fa-cogs me-1"></i> Manage Booking
                        </a></li>
                        <li><a class="dropdown-item <?php echo ($current_page == 'check-in.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>booking/check-in.php">
                            <i class="fas fa-check-circle me-1"></i> Web Check-In
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?php echo ($current_page == 'status.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>booking/status.php">
                            <i class="fas fa-info-circle me-1"></i> Flight Status
                        </a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($current_dir == 'pages') ? 'active' : ''; ?>" href="#" id="infoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-info-circle me-1"></i> Information
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="infoDropdown">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>pages/about.php">About Us</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>pages/contact.php">Contact</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>pages/faq.php">FAQ</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>pages/baggage.php">Baggage Information</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>pages/terms.php">Terms & Conditions</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $current_dir !== 'admin'): ?>
                <!-- Only show admin link when not in admin section -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $baseUrl; ?>admin/dashboard.php">
                        <i class="fas fa-cog me-1"></i> Admin Dashboard
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Account'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <!-- Admin user gets simplified dropdown options -->
                                <li><a class="dropdown-item direct-link" href="<?php echo $baseUrl; ?>admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> Admin Dashboard
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <!-- Regular user dropdown options -->
                                <li><a class="dropdown-item direct-link" href="<?php echo $baseUrl; ?>user/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> My Dashboard
                                </a></li>
                                <li><a class="dropdown-item direct-link" href="<?php echo $baseUrl; ?>user/bookings.php">
                                    <i class="fas fa-ticket-alt me-1"></i> My Bookings
                                </a></li>
                                <li><a class="dropdown-item direct-link" href="<?php echo $baseUrl; ?>user/profile.php">
                                    <i class="fas fa-user-edit me-1"></i> My Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger direct-link" href="<?php echo $baseUrl; ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>auth/register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<!-- Spacer to prevent content from hiding behind fixed navbar -->
<div style="padding-top: 60px;"></div>

<style>
/* Super-enhanced styles for navbar elements with very high z-index */
#main-navbar {
    position: fixed !important;
    top: 0 !important;
    right: 0 !important;
    left: 0 !important;
    z-index: 9999 !important; /* Extremely high z-index */
}

#main-navbar .dropdown-menu {
    z-index: 10000 !important; /* Even higher to ensure it's on top of everything */
}

/* Fix any potential pointer-events issues */
#main-navbar a, 
#main-navbar .dropdown-item,
#main-navbar .nav-link,
#main-navbar button {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10001 !important; /* Highest z-index for interactive elements */
}

/* Fix any absolute-positioned elements that might block clicks */
.dropdown-menu {
    position: absolute !important;
}

/* Fix potential overlay issues */
body {
    overflow-x: hidden;
}

/* Special fix for flight view pages */
.flight-content, 
.flight-details, 
.flight-card,
.flight-list {
    z-index: 1000 !important; /* Lower than navbar */
}
</style>

<script>
// Direct bypass approach for dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown toggles and add direct click handlers
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Find the associated dropdown menu
            var dropdownMenu = this.nextElementSibling;
            while (dropdownMenu && !dropdownMenu.classList.contains('dropdown-menu')) {
                dropdownMenu = dropdownMenu.nextElementSibling;
            }
            
            if (dropdownMenu) {
                // Toggle visibility directly
                if (dropdownMenu.style.display === 'block') {
                    dropdownMenu.style.display = 'none';
                } else {
                    // Hide all other dropdown menus first
                    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                        menu.style.display = 'none';
                    });
                    
                    // Show this dropdown menu
                    dropdownMenu.style.display = 'block';
                    
                    // Position the dropdown correctly if needed
                    dropdownMenu.style.position = 'absolute';
                    dropdownMenu.style.zIndex = '10000';
                }
            }
        });
    });
    
    // Improved direct click handlers for dropdown items - don't preventDefault
    document.querySelectorAll('.dropdown-menu a').forEach(function(item) {
        item.addEventListener('click', function(e) {
            // Don't prevent default or stop propagation
            // Just get the href and navigate
            var href = this.getAttribute('href');
            if (href) {
                console.log('Navigating to: ' + href);
                window.location.href = href;
            }
        });
    });
    
    // Special handling for user dashboard link
    var dashboardLinks = document.querySelectorAll('a[href*="dashboard.php"]');
    dashboardLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            console.log('Dashboard link clicked, navigating to: ' + href);
            window.location.href = href;
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                menu.style.display = 'none';
            });
        }
    });
});

// Hide all dropdowns when escape key is pressed
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            menu.style.display = 'none';
        });
    }
});

// Special handling for direct links that should bypass dropdown behavior
document.querySelectorAll('.direct-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        // Remove the modified click behavior for these specific links
        e.stopPropagation();
        var href = this.getAttribute('href');
        if (href) {
            window.location.href = href;
        }
    });
});
</script>
