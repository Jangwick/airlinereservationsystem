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

// Log the current user ID to debug the issue
error_log("DEBUG: Viewing bookings for User ID: {$user_id}");

// Get status parameter for filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Base query with proper joins to retrieve flight information
$query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
          f.departure_time, f.arrival_time, f.price 
          FROM bookings b 
          JOIN flights f ON b.flight_id = f.flight_id 
          WHERE b.user_id = ?";

// Add status filtering if specified
if (!empty($status_filter)) {
    $query .= " AND b.booking_status = ?";
}

// Order by departure time (newest first)
$query .= " ORDER BY f.departure_time DESC";

// Debug output
error_log("DEBUG: Bookings Query: " . $query . " with user_id: " . $user_id);

// Prepare and execute the statement
$stmt = $conn->prepare($query);

if (!empty($status_filter)) {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Check if any bookings were found
$bookings_found = $result->num_rows;
error_log("DEBUG: Number of bookings found: " . $bookings_found);

// Group bookings by status for easier display
$bookings = [
    'upcoming' => [],
    'completed' => [],
    'cancelled' => []
];

$now = new DateTime();

while ($booking = $result->fetch_assoc()) {
    $departure_time = new DateTime($booking['departure_time']);
    
    // Show all debugging data
    error_log("DEBUG: Processing booking ID: " . $booking['booking_id'] . 
              ", Status: " . $booking['booking_status'] . 
              ", Departure: " . $booking['departure_time']);
    
    // Categorize bookings
    if ($booking['booking_status'] == 'cancelled') {
        $bookings['cancelled'][] = $booking;
    } else if ($departure_time < $now || $booking['booking_status'] == 'completed') {
        $bookings['completed'][] = $booking;
    } else {
        $bookings['upcoming'][] = $booking;
    }
}

// Count bookings in each category
$upcoming_count = count($bookings['upcoming']);
$completed_count = count($bookings['completed']);
$cancelled_count = count($bookings['cancelled']);
$total_count = $upcoming_count + $completed_count + $cancelled_count;

// Success message (e.g., after cancellation)
$success_message = '';
if (isset($_SESSION['booking_message'])) {
    $success_message = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}

// Function to format duration
function formatDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $interval = $dep->diff($arr);
    return sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);
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
            <div class="col-md-8">
                <h1 class="h3 mb-0">My Bookings</h1>
                <p class="text-muted">View and manage all your flight bookings</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="../flights/search.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Book a New Flight
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Booking Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-primary mb-0"><?php echo $total_count; ?></h5>
                        <p class="text-muted mb-0 small">Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-success mb-0"><?php echo $upcoming_count; ?></h5>
                        <p class="text-muted mb-0 small">Upcoming Flights</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-info mb-0"><?php echo $completed_count; ?></h5>
                        <p class="text-muted mb-0 small">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-danger mb-0"><?php echo $cancelled_count; ?></h5>
                        <p class="text-muted mb-0 small">Cancelled</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo empty($status_filter) ? 'active' : ''; ?>" href="bookings.php">All Bookings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>" href="bookings.php?status=confirmed">Upcoming</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" href="bookings.php?status=completed">Completed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>" href="bookings.php?status=cancelled">Cancelled</a>
            </li>
        </ul>

        <?php if ($total_count == 0): ?>
        <div class="text-center py-5 my-4">
            <i class="fas fa-ticket-alt fa-4x text-muted mb-4"></i>
            <h4>No Bookings Found</h4>
            <p class="text-muted">You haven't made any bookings yet.</p>
            <a href="../flights/search.php" class="btn btn-primary mt-3">Book Your First Flight</a>
        </div>
        <?php else: ?>
            <!-- Upcoming Flights -->
            <?php if (count($bookings['upcoming']) > 0 && (empty($status_filter) || $status_filter === 'confirmed')): ?>
            <h4 class="mb-3">Upcoming Flights</h4>
            <?php foreach ($bookings['upcoming'] as $booking): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-success me-2">Confirmed</span>
                                <span class="text-muted">Booking Reference: BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            
                            <div class="row mb-3">
                                <!-- Airline Info -->
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="text-muted small">Airline</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                </div>
                                
                                <!-- Route Info -->
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <div class="text-muted small">Route</div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($booking['departure_city']); ?> → 
                                        <?php echo htmlspecialchars($booking['arrival_city']); ?>
                                    </div>
                                    <div class="small">
                                        Duration: <?php echo formatDuration($booking['departure_time'], $booking['arrival_time']); ?>
                                    </div>
                                </div>
                                
                                <!-- Date Info -->
                                <div class="col-md-4">
                                    <div class="text-muted small">Departure Date</div>
                                    <div class="fw-bold"><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                    <div class="small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill bg-light text-dark border me-2">
                                    <i class="fas fa-users me-1"></i> <?php echo $booking['passengers']; ?> Passenger(s)
                                </span>
                                
                                <?php if ($booking['payment_status'] === 'completed'): ?>
                                <span class="badge rounded-pill bg-success me-2">
                                    <i class="fas fa-check-circle me-1"></i> Paid
                                </span>
                                <?php elseif ($booking['payment_status'] === 'pending'): ?>
                                <span class="badge rounded-pill bg-warning text-dark me-2">
                                    <i class="fas fa-clock me-1"></i> Payment Pending
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <div class="h5 text-primary mb-3">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                            
                            <div class="d-flex flex-column gap-2">
                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-info-circle me-1"></i> View Details
                                </a>
                                
                                <?php
                                $departure = new DateTime($booking['departure_time']);
                                $now = new DateTime();
                                $interval = $now->diff($departure);
                                $days_until = $interval->days;
                                
                                // Check if departure is within 48 hours for check-in
                                $check_in_available = $days_until <= 2 && $now < $departure;
                                
                                if ($check_in_available): ?>
                                <a href="../booking/check-in.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-check-circle me-1"></i> Web Check-in
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Completed Flights -->
            <?php if (count($bookings['completed']) > 0 && (empty($status_filter) || $status_filter === 'completed')): ?>
            <h4 class="mb-3">Completed Flights</h4>
            <?php foreach ($bookings['completed'] as $booking): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-info me-2">Completed</span>
                                <span class="text-muted">Booking Reference: BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            
                            <div class="row mb-3">
                                <!-- Airline Info -->
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="text-muted small">Airline</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                </div>
                                
                                <!-- Route Info -->
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <div class="text-muted small">Route</div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($booking['departure_city']); ?> → 
                                        <?php echo htmlspecialchars($booking['arrival_city']); ?>
                                    </div>
                                    <div class="small">
                                        Duration: <?php echo formatDuration($booking['departure_time'], $booking['arrival_time']); ?>
                                    </div>
                                </div>
                                
                                <!-- Date Info -->
                                <div class="col-md-4">
                                    <div class="text-muted small">Departure Date</div>
                                    <div class="fw-bold"><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                    <div class="small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                </div>
                            </div>
                            
                            <div>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    <i class="fas fa-users me-1"></i> <?php echo $booking['passengers']; ?> Passenger(s)
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <div class="h5 text-primary mb-3">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                            
                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-info-circle me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Cancelled Flights -->
            <?php if (count($bookings['cancelled']) > 0 && (empty($status_filter) || $status_filter === 'cancelled')): ?>
            <h4 class="mb-3">Cancelled Bookings</h4>
            <?php foreach ($bookings['cancelled'] as $booking): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-danger me-2">Cancelled</span>
                                <span class="text-muted">Booking Reference: BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            
                            <div class="row mb-3">
                                <!-- Airline Info -->
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="text-muted small">Airline</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                </div>
                                
                                <!-- Route Info -->
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <div class="text-muted small">Route</div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($booking['departure_city']); ?> → 
                                        <?php echo htmlspecialchars($booking['arrival_city']); ?>
                                    </div>
                                    <div class="small">
                                        Scheduled: <?php echo date('M d, Y', strtotime($booking['departure_time'])); ?>
                                    </div>
                                </div>
                                
                                <!-- Payment Info -->
                                <div class="col-md-4">
                                    <div class="text-muted small">Payment Status</div>
                                    <div class="fw-bold">
                                        <?php 
                                        if ($booking['payment_status'] === 'refunded') {
                                            echo '<span class="text-info">Refunded</span>';
                                        } elseif ($booking['payment_status'] === 'completed') {
                                            echo '<span class="text-danger">Not Refunded</span>';
                                        } else {
                                            echo '<span class="text-secondary">Not Applicable</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <div class="text-muted mb-3">Cancelled on <?php echo date('M d, Y', strtotime($booking['updated_at'] ?? $booking['booking_date'])); ?></div>
                            
                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-info-circle me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
