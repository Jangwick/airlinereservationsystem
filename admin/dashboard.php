<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Get admin info
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get system statistics
$query_total_users = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$query_total_bookings = "SELECT COUNT(*) as count FROM bookings";
$query_total_flights = "SELECT COUNT(*) as count FROM flights";
$query_upcoming_flights = "SELECT COUNT(*) as count FROM flights WHERE departure_time > NOW()";

$total_users = $conn->query($query_total_users)->fetch_assoc()['count'];
$total_bookings = $conn->query($query_total_bookings)->fetch_assoc()['count'];
$total_flights = $conn->query($query_total_flights)->fetch_assoc()['count'];
$upcoming_flights = $conn->query($query_upcoming_flights)->fetch_assoc()['count'];

// Get most recent bookings
$query_recent_bookings = "SELECT b.booking_id, b.user_id, b.booking_date, b.booking_status,
                         u.first_name, u.last_name, f.flight_number, f.departure_city, 
                         f.arrival_city, f.departure_time
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.user_id 
                         JOIN flights f ON b.flight_id = f.flight_id 
                         ORDER BY b.booking_date DESC LIMIT 5";
$recent_bookings_result = $conn->query($query_recent_bookings);
$recent_bookings = [];
if ($recent_bookings_result) {
    while ($booking = $recent_bookings_result->fetch_assoc()) {
        $recent_bookings[] = $booking;
    }
}
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Admin Header -->
                <div class="admin-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Flights</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_flights; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Upcoming Flights</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $upcoming_flights; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane-departure fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_flights.php" class="btn btn-primary btn-lg d-flex flex-column align-items-center justify-content-center w-100 h-100 py-3">
                                            <i class="fas fa-plane mb-2 fa-2x"></i>
                                            <span>Manage Flights</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_users.php" class="btn btn-success btn-lg d-flex flex-column align-items-center justify-content-center w-100 h-100 py-3">
                                            <i class="fas fa-users mb-2 fa-2x"></i>
                                            <span>Manage Users</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_bookings.php" class="btn btn-info btn-lg text-white d-flex flex-column align-items-center justify-content-center w-100 h-100 py-3">
                                            <i class="fas fa-ticket-alt mb-2 fa-2x"></i>
                                            <span>Manage Bookings</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="reports.php" class="btn btn-warning btn-lg d-flex flex-column align-items-center justify-content-center w-100 h-100 py-3">
                                            <i class="fas fa-chart-bar mb-2 fa-2x"></i>
                                            <span>View Reports</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">Recent Bookings</h6>
                                <a href="manage_bookings.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Reference</th>
                                                <th>Customer</th>
                                                <th>Flight</th>
                                                <th>Route</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td><strong>BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($booking['booking_status']) {
                                                        case 'confirmed':
                                                            echo '<span class="badge bg-success">Confirmed</span>';
                                                            break;
                                                        case 'pending':
                                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge bg-danger">Cancelled</span>';
                                                            break;
                                                        case 'completed':
                                                            echo '<span class="badge bg-info">Completed</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if (empty($recent_bookings)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">No recent bookings found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity Logs -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">Recent Admin Activity</h6>
                                <a href="admin_logs.php" class="btn btn-sm btn-primary">View All Logs</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Admin</th>
                                                <th>Action</th>
                                                <th>Details</th>
                                                <th>Date/Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Check if admin_logs table exists
                                            $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
                                            $logs_exist = $table_check->num_rows > 0;
                                            
                                            if ($logs_exist) {
                                                // Get recent activity logs
                                                $logs_query = "SELECT al.*, u.username, u.first_name, u.last_name 
                                                              FROM admin_logs al
                                                              JOIN users u ON al.admin_id = u.user_id
                                                              ORDER BY al.created_at DESC
                                                              LIMIT 5";
                                                $logs_result = $conn->query($logs_query);
                                                $recent_logs = [];
                                                
                                                if ($logs_result && $logs_result->num_rows > 0) {
                                                    while ($log = $logs_result->fetch_assoc()) {
                                                        $recent_logs[] = $log;
                                                    }
                                                }
                                                
                                                if (!empty($recent_logs)) {
                                                    foreach ($recent_logs as $log) {
                                                        echo '<tr>';
                                                        echo '<td>' . htmlspecialchars($log['username']) . '</td>';
                                                        echo '<td><span class="badge bg-secondary">' . ucwords(str_replace('_', ' ', $log['action'])) . '</span></td>';
                                                        echo '<td class="text-wrap">' . htmlspecialchars($log['details']) . '</td>';
                                                        echo '<td>' . date('M d, Y g:i A', strtotime($log['created_at'])) . '</td>';
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center py-3">No recent activity logs found.</td></tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="4" class="text-center py-3">Activity logs system not initialized yet.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function refreshStats() {
            window.location.reload();
        }
    </script>
</body>
</html>
