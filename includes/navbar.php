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

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $baseUrl; ?>">
            <i class="fas fa-plane-departure me-2"></i>SkyWay Airlines
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
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
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo ($current_dir == 'admin') ? 'active' : ''; ?>" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_flights.php">Manage Flights</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_users.php">Manage Users</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_bookings.php">Manage Bookings</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/reports.php">Reports</a></li>
                    </ul>
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
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> Admin Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_flights.php">
                                    <i class="fas fa-plane me-1"></i> Manage Flights
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_users.php">
                                    <i class="fas fa-users me-1"></i> Manage Users
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/manage_bookings.php">
                                    <i class="fas fa-calendar-check me-1"></i> Manage Bookings
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> My Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/bookings.php">
                                    <i class="fas fa-ticket-alt me-1"></i> My Bookings
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>user/profile.php">
                                    <i class="fas fa-user-edit me-1"></i> My Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>auth/logout.php">
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
                        <a class="nav-link <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>user/register.php">
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
