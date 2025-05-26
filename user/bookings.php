<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';
// Include currency helper
require_once '../includes/currency_helper.php';

$user_id = $_SESSION['user_id'];

// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);

// Get booking status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Base query
$query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
          f.departure_time, f.arrival_time, f.status as flight_status
          FROM bookings b 
          JOIN flights f ON b.flight_id = f.flight_id 
          WHERE b.user_id = ?";

// Add status filter if specified
if (!empty($status_filter)) {
    $query .= " AND b.booking_status = ?";
}

// Complete the query with sorting
$query .= " ORDER BY f.departure_time ASC";

// Prepare and execute statement
$stmt = $conn->prepare($query);

if (!empty($status_filter)) {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
    // Add to bookings array
    $bookings[] = $row;
}

// Helper function to format status labels
function getStatusLabel($status) {
    switch ($status) {
        case 'confirmed':
            return '<span class="badge bg-success">Confirmed</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        case 'completed':
            return '<span class="badge bg-info">Completed</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

// Helper function to format flight status labels
function getFlightStatusLabel($status) {
    switch ($status) {
        case 'scheduled':
            return '<span class="badge bg-success">On Time</span>';
        case 'delayed':
            return '<span class="badge bg-warning text-dark">Delayed</span>';
        case 'boarding':
            return '<span class="badge bg-info">Boarding</span>';
        case 'departed':
            return '<span class="badge bg-primary">Departed</span>';
        case 'arrived':
            return '<span class="badge bg-secondary">Arrived</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-success">On Time</span>';
    }
}

// Count bookings by status
$stmt = $conn->prepare("SELECT booking_status, COUNT(*) as count FROM bookings WHERE user_id = ? GROUP BY booking_status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_result = $stmt->get_result();

$status_counts = [
    'all' => 0,
    'confirmed' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'completed' => 0
];

while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['booking_status']] = $row['count'];
    $status_counts['all'] += $row['count'];
}
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
            <div class="col-12">
                <h1 class="h3 mb-0">My Bookings</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Bookings</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Status Filter Cards -->
        <div class="row mb-4">
            <div class="col">
                <a href="bookings.php" class="text-decoration-none">
                    <div class="card shadow-sm border-<?php echo empty($status_filter) ? 'primary' : '0'; ?>">
                        <div class="card-body text-center">
                            <div class="h5 mb-0"><?php echo $status_counts['all']; ?></div>
                            <div class="small text-muted">All Bookings</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="bookings.php?status=confirmed" class="text-decoration-none">
                    <div class="card shadow-sm border-<?php echo $status_filter === 'confirmed' ? 'primary' : '0'; ?>">
                        <div class="card-body text-center">
                            <div class="h5 mb-0"><?php echo $status_counts['confirmed']; ?></div>
                            <div class="small text-muted">Confirmed</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="bookings.php?status=pending" class="text-decoration-none">
                    <div class="card shadow-sm border-<?php echo $status_filter === 'pending' ? 'primary' : '0'; ?>">
                        <div class="card-body text-center">
                            <div class="h5 mb-0"><?php echo $status_counts['pending']; ?></div>
                            <div class="small text-muted">Pending</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="bookings.php?status=completed" class="text-decoration-none">
                    <div class="card shadow-sm border-<?php echo $status_filter === 'completed' ? 'primary' : '0'; ?>">
                        <div class="card-body text-center">
                            <div class="h5 mb-0"><?php echo $status_counts['completed']; ?></div>
                            <div class="small text-muted">Completed</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="bookings.php?status=cancelled" class="text-decoration-none">
                    <div class="card shadow-sm border-<?php echo $status_filter === 'cancelled' ? 'primary' : '0'; ?>">
                        <div class="card-body text-center">
                            <div class="h5 mb-0"><?php echo $status_counts['cancelled']; ?></div>
                            <div class="small text-muted">Cancelled</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <?php if (count($bookings) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="list-group-item p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-5 mb-3 mb-md-0">
                                        <div class="d-flex">
                                            <div class="me-3 text-center">
                                                <div class="fs-4 fw-bold text-primary">
                                                    <?php echo date('d', strtotime($booking['departure_time'])); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo date('M', strtotime($booking['departure_time'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                <div class="d-flex align-items-center mt-2">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                                        <div class="small text-muted">
                                                            <?php echo date('h:i A', strtotime($booking['departure_time'])); ?>
                                                        </div>
                                                    </div>
                                                    <div class="mx-2">
                                                        <i class="fas fa-arrow-right text-muted"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                                        <div class="small text-muted">
                                                            <?php echo date('h:i A', strtotime($booking['arrival_time'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <div class="mb-1">
                                            <?php echo getStatusLabel($booking['booking_status']); ?>
                                            
                                            <?php if ($booking['flight_status']): ?>
                                                <?php echo getFlightStatusLabel($booking['flight_status']); ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['payment_status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Payment Pending</span>
                                            <?php elseif ($booking['payment_status'] === 'completed'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($booking['payment_status'] === 'refunded'): ?>
                                                <span class="badge bg-info">Refunded</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small mb-1">
                                            <span class="fw-bold">Booking ID:</span> 
                                            BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        <div class="small mb-1">
                                            <span class="fw-bold">Passengers:</span> 
                                            <?php echo $booking['passengers']; ?>
                                        </div>
                                        <div class="small">
                                            <span class="fw-bold">Amount:</span>
                                            <?php echo $currency_symbol . number_format($booking['total_amount'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary mb-2">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                        
                                        <?php if ($booking['booking_status'] === 'pending' && $booking['payment_status'] === 'pending'): ?>
                                            <a href="../booking/payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">
                                                <i class="fas fa-credit-card me-1"></i> Pay Now
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Check if online check-in is available (48 hours before departure and not checked-in yet)
                                        $now = new DateTime();
                                        $departure = new DateTime($booking['departure_time']);
                                        $diff = $now->diff($departure);
                                        $hours_until_departure = $diff->h + ($diff->days * 24);
                                        
                                        $check_in_available = $booking['booking_status'] === 'confirmed' && 
                                                              $booking['payment_status'] === 'completed' &&
                                                              $now < $departure && 
                                                              $hours_until_departure <= 48;
                                                              
                                        $is_checked_in = isset($booking['check_in_status']) && $booking['check_in_status'] === 'completed';
                                        
                                        if ($check_in_available && !$is_checked_in):
                                        ?>
                                            <a href="../booking/check-in.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-check-circle me-1"></i> Check-in
                                            </a>
                                        <?php elseif ($is_checked_in): ?>
                                            <button class="btn btn-outline-success" disabled>
                                                <i class="fas fa-check me-1"></i> Checked-in
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5>No Bookings Found</h5>
                        <p class="text-muted mb-4">You don't have any <?php echo !empty($status_filter) ? $status_filter : ''; ?> bookings yet.</p>
                        <a href="../flights/search.php" class="btn btn-primary">Book a Flight</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
