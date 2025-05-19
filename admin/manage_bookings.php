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
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
          f.departure_time, f.arrival_time, u.first_name, u.last_name, u.email 
          FROM bookings b 
          JOIN flights f ON b.flight_id = f.flight_id 
          JOIN users u ON b.user_id = u.user_id 
          WHERE 1=1";
$params = [];
$types = "";

// Add filters
if (!empty($filter_status)) {
    $query .= " AND b.booking_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_airline)) {
    $query .= " AND f.airline = ?";
    $params[] = $filter_airline;
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $query .= " AND DATE(f.departure_time) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $query .= " AND DATE(f.departure_time) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (b.booking_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
              OR u.email LIKE ? OR f.flight_number LIKE ? OR f.departure_city LIKE ? 
              OR f.arrival_city LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, 
                                  $search_term, $search_term, $search_term]);
    $types .= "sssssss";
}

// Add sorting
$query .= " ORDER BY b.booking_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Get booking statistics
$query_total = "SELECT COUNT(*) as count FROM bookings";
$query_confirmed = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'";
$query_pending = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'";
$query_cancelled = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'cancelled'";
$query_completed = "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'";
$query_today = "SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()";

$total_bookings = $conn->query($query_total)->fetch_assoc()['count'];
$confirmed_bookings = $conn->query($query_confirmed)->fetch_assoc()['count'];
$pending_bookings = $conn->query($query_pending)->fetch_assoc()['count'];
$cancelled_bookings = $conn->query($query_cancelled)->fetch_assoc()['count'];
$completed_bookings = $conn->query($query_completed)->fetch_assoc()['count'];
$today_bookings = $conn->query($query_today)->fetch_assoc()['count'];

// Get distinct airlines for filter dropdown
$airlines_query = "SELECT DISTINCT airline FROM flights ORDER BY airline";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
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
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search" class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Booking ID, name, email..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">All Bookings</h5>
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
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?></td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
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
                                                    <li><a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-edit me-2"></i> Update Status
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php if ($booking['booking_status'] !== 'cancelled' && $booking['booking_status'] !== 'completed'): ?>
                                                    <li><a class="dropdown-item text-warning" href="#" onclick="confirmCancel(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-ban me-2"></i> Cancel Booking
                                                    </a></li>
                                                    <?php endif; ?>
                                                    <?php if ($booking['payment_status'] === 'completed' && $booking['booking_status'] !== 'refunded'): ?>
                                                    <li><a class="dropdown-item text-info" href="#" onclick="processRefund(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-undo me-2"></i> Process Refund
                                                    </a></li>
                                                    <?php endif; ?>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $booking['booking_id']; ?>)">
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
            
            // Initialize with correct display
            $('#refund_type').trigger('change');
        });
        
        // Function to open status change modal
        function changeStatus(bookingId) {
            document.getElementById('booking_id').value = bookingId;
            $('#statusModal').modal('show');
        }
        
        // Function to open cancel booking modal
        function confirmCancel(bookingId) {
            document.getElementById('cancel_booking_id').value = bookingId;
            $('#cancelModal').modal('show');
        }
        
        // Function to open delete booking modal
        function confirmDelete(bookingId) {
            document.getElementById('delete_booking_id').value = bookingId;
            $('#deleteModal').modal('show');
        }
        
        // Function to open refund modal
        function processRefund(bookingId) {
            document.getElementById('refund_booking_id').value = bookingId;
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
        
        // Function to export bookings
        function exportBookings() {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            // Redirect to export page with same parameters
            window.location.href = 'export_bookings.php?' + urlParams.toString();
        }
        
        // Function to print report
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
