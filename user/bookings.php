<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$user_id = $_SESSION['user_id'];

// Initialize filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_from = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filter_to = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Base query
$query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
          f.departure_time, f.arrival_time 
          FROM bookings b 
          JOIN flights f ON b.flight_id = f.flight_id 
          WHERE b.user_id = ?";

// Add filters
$params = [$user_id];
$types = "i";

if (!empty($filter_status)) {
    $query .= " AND b.booking_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_from)) {
    $query .= " AND DATE(f.departure_time) >= ?";
    $params[] = $filter_from;
    $types .= "s";
}

if (!empty($filter_to)) {
    $query .= " AND DATE(f.departure_time) <= ?";
    $params[] = $filter_to;
    $types .= "s";
}

// Add sorting
$query .= " ORDER BY f.departure_time ASC";

// Prepare and execute statement
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($booking = $result->fetch_assoc()) {
    $bookings[] = $booking;
}

// Get booking counts by status
$count_query = "SELECT booking_status, COUNT(*) as count FROM bookings WHERE user_id = ? GROUP BY booking_status";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();

$status_counts = [
    'confirmed' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'completed' => 0
];

while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['booking_status']] = $row['count'];
}

$total_bookings = array_sum($status_counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0">My Bookings</h1>
                <p class="text-muted">Manage all your bookings in one place</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="../flights/search.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Book New Flight
                </a>
            </div>
        </div>
        
        <!-- Booking Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase text-muted mb-2">Total Bookings</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $total_bookings; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase text-success mb-2">Upcoming</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $status_counts['confirmed']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase text-info mb-2">Completed</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $status_counts['completed']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase text-danger mb-2">Cancelled</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $status_counts['cancelled']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="get" action="bookings.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Booking Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $filter_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $filter_to; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-grid gap-2 d-md-flex w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                            <a href="bookings.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">All Bookings</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking Ref</th>
                                <th>Flight</th>
                                <th>Route</th>
                                <th>Departure</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold">BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    <div class="small text-muted"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></span>
                                        <span class="mx-2"><i class="fas fa-arrow-right text-muted"></i></span>
                                        <span class="fw-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                    <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                </td>
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
                                    
                                    // Show payment status
                                    if ($booking['payment_status'] == 'pending') {
                                        echo ' <span class="badge bg-warning text-dark">Unpaid</span>';
                                    } elseif ($booking['payment_status'] == 'refunded') {
                                        echo ' <span class="badge bg-info">Refunded</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        
                                        <?php if ($booking['booking_status'] == 'pending' && $booking['payment_status'] == 'pending'): ?>
                                        <a href="../booking/payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-credit-card"></i> Pay
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] == 'confirmed' && strtotime($booking['departure_time']) > time()): ?>
                                        <a href="../booking/check-in.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-check-circle"></i> Check-in
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] != 'cancelled' && strtotime($booking['departure_time']) > (time() + 86400)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal" data-booking-id="<?php echo $booking['booking_id']; ?>">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt text-muted fa-3x mb-3"></i>
                    <h5>No Bookings Found</h5>
                    <p class="text-muted">You haven't made any bookings yet.</p>
                    <a href="../flights/search.php" class="btn btn-primary mt-2">Book Your First Flight</a>
                </div>
                <?php endif; ?>
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
                <form id="cancelForm" action="../booking/cancel.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="cancel_booking_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Cancellation may be subject to fees according to our cancellation policy.
                        </div>
                        
                        <p>Are you sure you want to cancel this booking?</p>
                        
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Cancellation Reason</label>
                            <select class="form-select" id="cancel_reason" name="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="Change of plans">Change of plans</option>
                                <option value="Found better deal">Found better deal</option>
                                <option value="Schedule conflict">Schedule conflict</option>
                                <option value="Other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="other_reason_div" style="display: none;">
                            <label for="other_reason" class="form-label">Please specify</label>
                            <textarea class="form-control" id="other_reason" name="other_reason" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle cancel booking modal
        document.addEventListener('DOMContentLoaded', function() {
            // Set booking ID when modal is opened
            var cancelModal = document.getElementById('cancelModal');
            if (cancelModal) {
                cancelModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var bookingId = button.getAttribute('data-booking-id');
                    document.getElementById('cancel_booking_id').value = bookingId;
                });
            }
            
            // Show "other reason" textarea when "Other" is selected
            var cancelReason = document.getElementById('cancel_reason');
            if (cancelReason) {
                cancelReason.addEventListener('change', function() {
                    var otherReasonDiv = document.getElementById('other_reason_div');
                    if (this.value === 'Other') {
                        otherReasonDiv.style.display = 'block';
                    } else {
                        otherReasonDiv.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
