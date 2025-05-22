<?php
// This file is included in all admin pages to display the sidebar navigation

// Set current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get base URL for links
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}
$baseUrl = getBaseUrl();
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="sidebar-heading px-3 py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted text-uppercase">Admin Panel</span>
        </div>
        
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage_flights.php' || $current_page == 'add_flight.php' || $current_page == 'edit_flight.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/manage_flights.php">
                    <i class="fas fa-plane me-2"></i>
                    Flights Management
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage_users.php' || $current_page == 'edit_user.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/manage_users.php">
                    <i class="fas fa-users me-2"></i>
                    User Management
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage_bookings.php' || $current_page == 'booking_details.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/manage_bookings.php">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Booking Management
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reports
                </a>
            </li>
        </ul>
        
        <!-- Flight Management Section -->
        <div class="position-sticky">
            <ul class="nav flex-column">
                <!-- ...existing flight management items... -->
                
                <!-- Add these new items in the Flight Management section -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'fix_flight_prices.php' ? 'active' : ''; ?>" href="fix_flight_prices.php">
                        <i class="fas fa-dollar-sign me-2"></i> Fix Flight Prices
                    </a>
                </li>
                
                <!-- Add Database Utilities section -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'database_utilities.php' ? 'active' : ''; ?>" href="../db/database_utilities.php">
                        <i class="fas fa-database me-2"></i> Database Utilities
                    </a>
                </li>
            </ul>
        </div>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>System</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'admin_logs.php' || $current_page == 'initialize_logs.php' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>admin/admin_logs.php">
                    <i class="fas fa-history me-2"></i>
                    Activity Logs
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $baseUrl; ?>" target="_blank">
                    <i class="fas fa-globe me-2"></i>
                    View Website
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo $baseUrl; ?>auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
