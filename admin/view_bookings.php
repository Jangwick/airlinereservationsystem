<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = $_GET['user_id'];

// Include database connection
require_once '../db/db_config.php';

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}

$user = $result->fetch_assoc();

// Get bookings for this user
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                     f.departure_time, f.arrival_time, f.status as flight_status
                     FROM bookings b 
                     JOIN flights f ON b.flight_id = f.flight_id 
                     WHERE b.user_id = ? 
                     ORDER BY b.booking_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}

// Count bookings by status
$booking_stats = [
    'total' => count($bookings),
    'confirmed' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'completed' => 0
];

foreach ($bookings as $booking) {
    if (isset($booking['booking_status'])) {
        $status = strtolower($booking['booking_status']);
        if (isset($booking_stats[$status])) {
            $booking_stats[$status]++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Bookings - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Bookings for <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportBookings()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                        <a href="edit_user.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-user-edit me-1"></i> Edit User
                        </a>
                        <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Users
                        </a>
                    </div>
                </div>

                <!-- User Info Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <div class="avatar-circle">
                                    <span class="initials"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                <p class="mb-0 text-muted">
                                    <?php echo htmlspecialchars($user['email']); ?> | 
                                    User ID: <?php echo $user['user_id']; ?> | 
                                    <?php echo $user['role'] === 'admin' ? '<span class="badge bg-warning text-dark">Administrator</span>' : '<span class="badge bg-info">Regular User</span>'; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="mb-0">
                                    <strong>Status:</strong>
                                    <?php 
                                    $status = isset($user['account_status']) ? $user['account_status'] : 'active';
                                    switch ($status) {
                                        case 'active':
                                            echo '<span class="badge bg-success">Active</span>';
                                            break;
                                        case 'inactive':
                                            echo '<span class="badge bg-secondary">Inactive</span>';
                                            break;
                                        case 'suspended':
                                            echo '<span class="badge bg-danger">Suspended</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-success">Active</span>';
                                    }
                                    ?>
                                </p>
                                <small class="text-muted">
                                    Registered: <?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $booking_stats['total']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Confirmed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $booking_stats['confirmed']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Completed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $booking_stats['completed']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="card border-left-danger h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Cancelled</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $booking_stats['cancelled']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ban fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">All Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="bookingsTable">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Flight</th>
                                        <th>Route</th>
                                        <th>Departure</th>
                                        <th>Booking Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td>
                                            <?php
                                            $booking_status = isset($booking['booking_status']) ? $booking['booking_status'] : '';
                                            switch ($booking_status) {
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
                                            <?php echo isset($booking['total_amount']) ? '$' . number_format($booking['total_amount'], 2) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">No bookings found for this user.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#bookingsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
            });
        });
        
        // Export bookings function
        function exportBookings() {
            window.location.href = 'export_bookings.php?user_id=<?php echo $user_id; ?>';
        }
    </script>
    
    <style>
    .avatar-circle {
        width: 50px;
        height: 50px;
        background-color: #3b71ca;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
    }
    
    .initials {
        font-size: 20px;
        color: white;
        font-weight: bold;
    }
    </style>
</body>
</html>
