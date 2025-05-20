<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Handle status messages
$status_message = '';
$status_type = '';

if (isset($_SESSION['booking_status'])) {
    $status_message = $_SESSION['booking_status']['message'];
    $status_type = $_SESSION['booking_status']['type'];
    unset($_SESSION['booking_status']);
}

// Initialize filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Base query - first check if passengers table exists
$passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;

if ($passengers_table_exists) {
    $query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
              f.departure_time, f.arrival_time, u.first_name, u.last_name, u.email,
              (SELECT COUNT(*) FROM passengers p WHERE p.booking_id = b.booking_id) as passenger_count
              FROM bookings b 
              JOIN flights f ON b.flight_id = f.flight_id 
              JOIN users u ON b.user_id = u.user_id 
              WHERE 1=1";
} else {
    // If passengers table doesn't exist, use default value of 1 for passenger_count
    $query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
              f.departure_time, f.arrival_time, u.first_name, u.last_name, u.email,
              1 as passenger_count
              FROM bookings b 
              JOIN flights f ON b.flight_id = f.flight_id 
              JOIN users u ON b.user_id = u.user_id 
              WHERE 1=1";
}

// Count query (for pagination) - no need to include passenger count here
$count_query = "SELECT COUNT(*) as total FROM bookings b 
               JOIN flights f ON b.flight_id = f.flight_id 
               JOIN users u ON b.user_id = u.user_id 
               WHERE 1=1";

$params = [];
$types = "";

// Add filters
if (!empty($filter_status)) {
    $query .= " AND b.booking_status = ?";
    $count_query .= " AND b.booking_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_payment_status)) {
    $query .= " AND b.payment_status = ?";
    $count_query .= " AND b.payment_status = ?";
    $params[] = $filter_payment_status;
    $types .= "s";
}

if (!empty($filter_airline)) {
    $query .= " AND f.airline = ?";
    $count_query .= " AND f.airline = ?";
    $params[] = $filter_airline;
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $query .= " AND DATE(f.departure_time) >= ?";
    $count_query .= " AND DATE(f.departure_time) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $query .= " AND DATE(f.departure_time) <= ?";
    $count_query .= " AND DATE(f.departure_time) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (b.booking_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
              OR u.email LIKE ? OR f.flight_number LIKE ? OR f.departure_city LIKE ? 
              OR f.arrival_city LIKE ?)";
    $count_query .= " AND (b.booking_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
                    OR u.email LIKE ? OR f.flight_number LIKE ? OR f.departure_city LIKE ? 
                    OR f.arrival_city LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, 
                                  $search_term, $search_term, $search_term]);
    $types .= "sssssss";
}

// Get total count for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_bookings_filtered = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings_filtered / $items_per_page);

// Add sorting and pagination
$query .= " ORDER BY b.booking_date DESC LIMIT ?, ?";
$offset_params = $params;
$offset_params[] = $offset;
$offset_params[] = $items_per_page;
$offset_types = $types . "ii";

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($offset_params)) {
    $stmt->bind_param($offset_types, ...$offset_params);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Get booking statistics
// Total bookings
$query_total = "SELECT COUNT(*) as count FROM bookings";
$total_bookings = $conn->query($query_total)->fetch_assoc()['count'];

// Status counts
$query_confirmed = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'";
$query_pending = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'";
$query_cancelled = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'cancelled'";
$query_completed = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'";
$query_today = "SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()";

$confirmed_bookings = $conn->query($query_confirmed)->fetch_assoc()['count'];
$pending_bookings = $conn->query($query_pending)->fetch_assoc()['count'];
$cancelled_bookings = $conn->query($query_cancelled)->fetch_assoc()['count'];
$completed_bookings = $conn->query($query_completed)->fetch_assoc()['count'];
$today_bookings = $conn->query($query_today)->fetch_assoc()['count'];

// Payment status counts
$query_paid = "SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'completed'";
$query_unpaid = "SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'pending'";
$query_refunded = "SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'refunded'";
$query_failed = "SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'failed'";

$paid_bookings = $conn->query($query_paid)->fetch_assoc()['count'];
$unpaid_bookings = $conn->query($query_unpaid)->fetch_assoc()['count'];
$refunded_bookings = $conn->query($query_refunded)->fetch_assoc()['count'];
$failed_bookings = $conn->query($query_failed)->fetch_assoc()['count'];

// Total revenue (sum of completed payments)
$query_revenue = "SELECT SUM(total_amount) as revenue FROM bookings WHERE payment_status = 'completed'";
$total_revenue = $conn->query($query_revenue)->fetch_assoc()['revenue'] ?? 0;

// Get distinct airlines for filter dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
}

// Get distinct payment statuses for filter dropdown
$payment_statuses_query = "SELECT DISTINCT payment_status FROM bookings WHERE payment_status IS NOT NULL";
$payment_statuses_result = $conn->query($payment_statuses_query);
$payment_statuses = [];
while ($row = $payment_statuses_result->fetch_assoc()) {
    $payment_statuses[] = $row['payment_status'];
}

// Get booking history - track status changes
function getBookingHistory($booking_id) {
    global $conn;
    
    // Check if booking_history table exists
    $history_check = $conn->query("SHOW TABLES LIKE 'booking_history'");
    
    if ($history_check->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM booking_history WHERE booking_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }
    
    return [];
}

// Function to get passenger details for a booking
function getBookingPassengers($booking_id) {
    global $conn, $passengers_table_exists;
    
    // Return empty array if the passengers table doesn't exist
    if (!$passengers_table_exists) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $passengers = [];
    while ($row = $result->fetch_assoc()) {
        $passengers[] = $row;
    }
    return $passengers;
}

// Get payment method statistics - first check if payment_method column exists
$payment_method_exists = false;
$columns_result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
if ($columns_result && $columns_result->num_rows > 0) {
    $payment_method_exists = true;
    $payment_methods_query = "SELECT payment_method, COUNT(*) as count FROM bookings 
                             WHERE payment_status = 'completed' 
                             GROUP BY payment_method ORDER BY count DESC";
    $payment_methods_result = $conn->query($payment_methods_query);
    $payment_methods = [];

    if ($payment_methods_result) {
        while ($row = $payment_methods_result->fetch_assoc()) {
            $payment_methods[$row['payment_method']] = $row['count'];
        }
    }
} else {
    // If payment_method column doesn't exist, create empty array
    $payment_methods = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin Dashboard</title>
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
                    <h1 class="h2">Booking Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportBookings()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="fas fa-chart-line me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
                <!-- Status Messages -->
                <?php if (!empty($status_message)): ?>
                    <div class="alert alert-<?php echo $status_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $status_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Confirmed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $confirmed_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $pending_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Cancelled</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $cancelled_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Completed</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $completed_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-double fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card border-left-secondary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Today</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $today_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Revenue Stats -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold">Payment Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 col-lg-3 text-center mb-3">
                                        <div class="h3 text-success mb-0"><?php echo $paid_bookings; ?></div>
                                        <div class="small text-muted">Completed</div>
                                    </div>
                                    <div class="col-6 col-lg-3 text-center mb-3">
                                        <div class="h3 text-warning mb-0"><?php echo $unpaid_bookings; ?></div>
                                        <div class="small text-muted">Pending</div>
                                    </div>
                                    <div class="col-6 col-lg-3 text-center mb-3">
                                        <div class="h3 text-info mb-0"><?php echo $refunded_bookings; ?></div>
                                        <div class="small text-muted">Refunded</div>
                                    </div>
                                    <div class="col-6 col-lg-3 text-center mb-3">
                                        <div class="h3 text-danger mb-0"><?php echo $failed_bookings; ?></div>
                                        <div class="small text-muted">Failed</div>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <div class="h4">Total Revenue</div>
                                    <div class="display-6 text-success fw-bold"><?php echo '$' . number_format($total_revenue, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold">Payment Methods</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Payment Method</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payment_methods as $method => $count): 
                                                $percentage = ($paid_bookings > 0) ? round(($count / $paid_bookings) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($method ?: 'Unknown'); ?></td>
                                                <td class="text-end"><?php echo $count; ?></td>
                                                <td class="text-end">
                                                    <div class="progress">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                            aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo $percentage; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($payment_methods)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No payment data available</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Filter Bookings</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="manage_bookings.php" class="row g-3">
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="payment_status" class="form-label">Payment</label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <option value="">All Payments</option>
                                    <?php foreach ($payment_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $filter_payment_status === $status ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="airline" class="form-label">Airline</label>
                                <select class="form-select" id="airline" name="airline">
                                    <option value="">All Airlines</option>
                                    <?php foreach ($airlines as $airline): ?>
                                        <option value="<?php echo htmlspecialchars($airline); ?>" <?php echo $filter_airline === $airline ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($airline); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>">
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by booking ID, customer name, email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                    <a href="manage_bookings.php" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Bookings Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Bookings</h5>
                            <span class="badge bg-primary"><?php echo $total_bookings_filtered; ?> bookings found</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover" id="bookingsTable">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
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
                                            <div><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($booking['email']); ?></div>
                                            <?php if ($passengers_table_exists && $booking['passenger_count'] > 1): ?>
                                                <div class="small badge bg-info"><?php echo $booking['passenger_count']; ?> passengers</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?></td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td>
                                            <?php switch ($booking['booking_status']) {
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
                                            } ?>
                                        </td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="booking_details.php?id=<?php echo $booking['booking_id']; ?>">
        <i class="fas fa-eye me-2"></i> View Details
    </a></li>
    <li><a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_status']; ?>', '<?php echo $booking['payment_status']; ?>')">
        <i class="fas fa-edit me-2"></i> Update Status
    </a></li>
    <li><hr class="dropdown-divider"></li>
    <?php if ($booking['booking_status'] !== 'cancelled' && $booking['booking_status'] !== 'completed'): ?>
    <li><a class="dropdown-item text-warning" href="#" onclick="confirmCancel(<?php echo $booking['booking_id']; ?>)">
        <i class="fas fa-ban me-2"></i> Cancel Booking
    </a></li>
    <?php endif; ?>
    <?php if ($booking['payment_status'] === 'completed' && $booking['booking_status'] !== 'refunded'): ?>
    <li><a class="dropdown-item text-info" href="#" onclick="processRefund(<?php echo $booking['booking_id']; ?>, <?php echo $booking['total_amount']; ?>)">
        <i class="fas fa-undo me-2"></i> Process Refund
    </a></li>
    <?php endif; ?>
    <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $booking['booking_id']; ?>, '<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>')">
        <i class="fas fa-trash-alt me-2"></i> Delete Booking
    </a></li>
</ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">No bookings found matching your criteria</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="p-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($filter_status); ?>&payment_status=<?php echo urlencode($filter_payment_status); ?>&airline=<?php echo urlencode($filter_airline); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&search=<?php echo urlencode($search); ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&payment_status=<?php echo urlencode($filter_payment_status); ?>&airline=<?php echo urlencode($filter_airline); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($filter_status); ?>&payment_status=<?php echo urlencode($filter_payment_status); ?>&airline=<?php echo urlencode($filter_airline); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm" action="booking_actions.php" method="post">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" id="booking_id" name="booking_id" value="">
                        <div class="mb-3">
                            <label for="booking_status" class="form-label">Booking Status</label>
                            <select class="form-select" id="booking_status" name="booking_status" required>
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notify_customer" name="notify_customer" value="1" checked>
                            <label class="form-check-label" for="notify_customer">
                                Notify customer via email
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('statusForm').submit()">Update Status</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelForm" action="booking_actions.php" method="post">
                        <input type="hidden" name="action" value="cancel_booking">
                        <input type="hidden" id="cancel_booking_id" name="booking_id" value="">
                        <p>Are you sure you want to cancel this booking? This will update the booking status to cancelled and release the seat inventory.</p>
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Cancellation Reason</label>
                            <textarea class="form-control" id="cancel_reason" name="reason" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="refund_amount" class="form-label">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="refund_amount" name="refund_amount" value="0.00">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="cancel_notify_customer" name="notify_customer" value="1" checked>
                            <label class="form-check-label" for="cancel_notify_customer">
                                Notify customer via email
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('cancelForm').submit()">Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Booking Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteForm" action="booking_actions.php" method="post">
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" id="delete_booking_id" name="booking_id" value="">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                        <p>Are you sure you want to permanently delete this booking from the system? All associated data, including tickets and payment records, will be removed.</p>
                        <div class="mb-3">
                            <label for="delete_confirmation" class="form-label">Type "DELETE" to confirm</label>
                            <input type="text" class="form-control" id="delete_confirmation" required pattern="DELETE">
                            <div class="form-text">This field is case sensitive.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitDeleteForm()">Delete Permanently</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Process Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="refundForm" action="booking_actions.php" method="post">
                        <input type="hidden" name="action" value="process_refund">
                        <input type="hidden" id="refund_booking_id" name="booking_id" value="">
                        <div class="mb-3">
                            <label for="refund_type" class="form-label">Refund Type</label>
                            <select class="form-select" id="refund_type" name="refund_type" required>
                                <option value="full">Full Refund</option>
                                <option value="partial">Partial Refund</option>
                            </select>
                        </div>
                        <div class="mb-3" id="partial_amount_group">
                            <label for="partial_amount" class="form-label">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="partial_amount" name="amount" value="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">Refund Reason</label>
                            <textarea class="form-control" id="refund_reason" name="reason" rows="3" required></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="refund_notify_customer" name="notify_customer" value="1" checked>
                            <label class="form-check-label" for="refund_notify_customer">
                                Notify customer via email
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('refundForm').submit()">Process Refund</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Generate Booking Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm" action="booking_reports.php" method="get" target="_blank">
                        <div class="mb-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="type" required>
                                <option value="daily">Daily Bookings</option>
                                <option value="monthly">Monthly Bookings</option>
                                <option value="airline">Bookings by Airline</option>
                                <option value="route">Bookings by Route</option>
                                <option value="status">Bookings by Status</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="report_date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="report_date_from" name="date_from" required value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="report_date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="report_date_to" name="date_to" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="report_format" class="form-label">Format</label>
                            <select class="form-select" id="report_format" name="format" required>
                                <option value="html">HTML</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('reportForm').submit()">Generate Report</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Payment Method Modal -->
    <div class="modal fade" id="updateSchemaModal" tabindex="-1" aria-labelledby="updateSchemaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateSchemaModalLabel">Add Payment Method Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Database Update Required</strong>
                        <p>This feature requires adding a 'payment_method' column to your bookings table.</p>
                        <p>Do you want to update the database schema now?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="../db/update_bookings_schema.php" class="btn btn-primary">Update Schema</a>
                </div>
            </div>
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
                "pageLength": 25,
                "ordering": true,
                "info": true,
                "searching": false
            });
            
            // Handle refund type change
            $('#refund_type').on('change', function() {
                if ($(this).val() === 'full') {
                    $('#partial_amount_group').hide();
                } else {
                    $('#partial_amount_group').show();
                }
            });
        });
        
        // Function to open status change modal
        function changeStatus(bookingId, currentBookingStatus, currentPaymentStatus) {
            document.getElementById('booking_id').value = bookingId;
            
            // Pre-select current statuses
            if (currentBookingStatus) {
                document.getElementById('booking_status').value = currentBookingStatus;
            }
            if (currentPaymentStatus) {
                document.getElementById('payment_status').value = currentPaymentStatus;
            }
            
            $('#statusModal').modal('show');
        }
        
        // Function to open cancel booking modal
        function confirmCancel(bookingId) {
            document.getElementById('cancel_booking_id').value = bookingId;
            $('#cancelModal').modal('show');
        }
        
        // Function to open delete booking modal
        function confirmDelete(bookingId, bookingIdFormatted) {
            document.getElementById('delete_booking_id').value = bookingId;
            $('#deleteModal').modal('show');
        }
        
        // Function to open refund modal
        function processRefund(bookingId, totalAmount) {
            document.getElementById('refund_booking_id').value = bookingId;
            document.getElementById('partial_amount').value = totalAmount.toFixed(2);
            $('#refundModal').modal('show');
        }
        
        // Function to validate and submit delete form
        function submitDeleteForm() {
            var input = document.getElementById('delete_confirmation');
            if (input.value === 'DELETE') {
                document.getElementById('deleteForm').submit();
            } else {
                input.setCustomValidity('Please type "DELETE" to confirm.');
                input.reportValidity();
            }
        }
        
        // Function to print report
        function printReport() {
            window.print();
        }
        
        // Function to export bookings
        function exportBookings() {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            // Redirect to export page with same parameters
            window.location.href = 'export_bookings.php?' + urlParams.toString();
        }
    </script>
</body>
</html>
