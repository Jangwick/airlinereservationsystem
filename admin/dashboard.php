<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Get dashboard statistics
// Total users
$sql_users = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total_users'];

// Total flights
$sql_flights = "SELECT COUNT(*) as total_flights FROM flights";
$result_flights = $conn->query($sql_flights);
$total_flights = $result_flights->fetch_assoc()['total_flights'];

// Total bookings
$sql_bookings = "SELECT COUNT(*) as total_bookings FROM bookings";
$result_bookings = $conn->query($sql_bookings);
$total_bookings = $result_bookings->fetch_assoc()['total_bookings'];

// Total revenue
$sql_revenue = "SELECT SUM(total_amount) as total_revenue FROM bookings WHERE payment_status = 'completed'";
$result_revenue = $conn->query($sql_revenue);
$total_revenue = $result_revenue->fetch_assoc()['total_revenue'];
$total_revenue = $total_revenue ? $total_revenue : 0;

// Recent bookings
$sql_recent_bookings = "SELECT b.booking_id, b.booking_date, b.total_amount, u.username, 
                        f.flight_number, f.departure_city, f.arrival_city, b.booking_status, b.payment_status 
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.user_id 
                        JOIN flights f ON b.flight_id = f.flight_id 
                        ORDER BY b.booking_date DESC LIMIT 5";
$result_recent_bookings = $conn->query($sql_recent_bookings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse">
                <div class="position-sticky">
                    <div class="text-center py-4">
                        <h2 class="text-white"><i class="fas fa-plane-departure me-2"></i>SkyWay</h2>
                        <p class="text-white-50">Admin Dashboard</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="flights.php">
                                <i class="fas fa-plane me-2"></i>Flights Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt me-2"></i>Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="promos.php">
                                <i class="fas fa-tags me-2"></i>Promo & Discounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cogs me-2"></i>System Settings
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Users</h6>
                                        <h1 class="display-4 stat-counter" data-target="<?php echo $total_users; ?>"><?php echo $total_users; ?></h1>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="users.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Flights</h6>
                                        <h1 class="display-4 stat-counter" data-target="<?php echo $total_flights; ?>"><?php echo $total_flights; ?></h1>
                                    </div>
                                    <i class="fas fa-plane-departure fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="flights.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Bookings</h6>
                                        <h1 class="display-4 stat-counter" data-target="<?php echo $total_bookings; ?>"><?php echo $total_bookings; ?></h1>
                                    </div>
                                    <i class="fas fa-ticket-alt fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="bookings.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-stat-card bg-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Revenue</h6>
                                        <h1 class="display-4">$<?php echo number_format($total_revenue, 2); ?></h1>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="reports.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Monthly Bookings & Revenue</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Booking Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bookingStatusChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Bookings</h5>
                        <a href="bookings.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>User</th>
                                        <th>Flight</th>
                                        <th>Route</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_recent_bookings->num_rows > 0): ?>
                                        <?php while ($booking = $result_recent_bookings->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $booking['booking_id']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['departure_city']) . ' → ' . htmlspecialchars($booking['arrival_city']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                                        <span class="badge bg-success">Confirmed</span>
                                                    <?php elseif ($booking['booking_status'] == 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($booking['booking_status'] == 'cancelled'): ?>
                                                        <span class="badge bg-danger">Cancelled</span>
                                                    <?php elseif ($booking['booking_status'] == 'completed'): ?>
                                                        <span class="badge bg-info">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($booking['payment_status'] == 'completed'): ?>
                                                        <span class="badge bg-success">Paid</span>
                                                    <?php elseif ($booking['payment_status'] == 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                                        <span class="badge bg-info">Refunded</span>
                                                    <?php elseif ($booking['payment_status'] == 'failed'): ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No bookings found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & System Status -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <a href="flights.php?action=add" class="btn btn-outline-primary w-100 py-3">
                                            <i class="fas fa-plus-circle mb-2 fa-2x"></i>
                                            <div>Add New Flight</div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="promos.php?action=add" class="btn btn-outline-success w-100 py-3">
                                            <i class="fas fa-tag mb-2 fa-2x"></i>
                                            <div>Create Promo</div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="reports.php?generate=sales" class="btn btn-outline-info w-100 py-3">
                                            <i class="fas fa-chart-line mb-2 fa-2x"></i>
                                            <div>Generate Report</div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="settings.php" class="btn btn-outline-secondary w-100 py-3">
                                            <i class="fas fa-cogs mb-2 fa-2x"></i>
                                            <div>System Settings</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">System Status</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-server text-primary me-2"></i> System Status
                                        </div>
                                        <span class="badge bg-success rounded-pill">Operational</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-database text-primary me-2"></i> Database
                                        </div>
                                        <span class="badge bg-success rounded-pill">Connected</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-credit-card text-primary me-2"></i> Payment Gateway
                                        </div>
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-envelope text-primary me-2"></i> Email Service
                                        </div>
                                        <span class="badge bg-success rounded-pill">Operational</span>
                                    </li>
                                </ul>
                                <div class="mt-3">
                                    <small class="text-muted">Last system check: <?php echo date('M d, Y H:i:s'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Revenue Chart
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Sample data - in real application, this would come from the database
        const bookingData = [65, 78, 90, 85, 92, 110, 120, 130, 115, 125, 140, 150];
        const revenueData = [15000, 18000, 21000, 20000, 22000, 25000, 28000, 30000, 27000, 29000, 32000, 35000];
        
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Bookings',
                        data: bookingData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'bookings-y-axis'
                    },
                    {
                        label: 'Revenue ($)',
                        data: revenueData,
                        type: 'line',
                        fill: false,
                        backgroundColor: 'rgba(255, 99, 132, 1)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        tension: 0.1,
                        yAxisID: 'revenue-y-axis'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    'bookings-y-axis': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Bookings'
                        }
                    },
                    'revenue-y-axis': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
        
        // Booking Status Chart
        const statusCtx = document.getElementById('bookingStatusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Confirmed', 'Pending', 'Cancelled', 'Completed'],
                datasets: [{
                    data: [65, 15, 10, 10],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
