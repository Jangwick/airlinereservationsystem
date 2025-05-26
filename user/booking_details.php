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
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);

// Validate booking ID
if ($booking_id <= 0) {
    header("Location: bookings.php");
    exit();
}

// Get booking details - make sure it belongs to the current user
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, 
                      f.arrival_city, f.departure_time, f.arrival_time, f.status as flight_status 
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if booking exists and belongs to the current user
if ($result->num_rows === 0) {
    header("Location: bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Calculate flight duration
$departure = new DateTime($booking['departure_time']);
$arrival = new DateTime($booking['arrival_time']);
$interval = $departure->diff($arrival);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);

// Format dates
$departure_date = date('l, F j, Y', strtotime($booking['departure_time']));
$departure_time = date('h:i A', strtotime($booking['departure_time']));
$arrival_date = date('l, F j, Y', strtotime($booking['arrival_time']));
$arrival_time = date('h:i A', strtotime($booking['arrival_time']));

// Calculate days until flight
$now = new DateTime();
$days_until = $now->diff($departure)->days;
$is_past = $now > $departure;

// Check if check-in is available and not already checked-in
$check_in_available = !$is_past && $days_until <= 2; // Allow check-in 48 hours before flight
$check_in_completed = isset($booking['check_in_status']) && $booking['check_in_status'] == 'completed';

// Check if cancellation is possible
$can_cancel = $booking['booking_status'] != 'cancelled' && $booking['booking_status'] != 'completed' && $days_until > 1;

// Get passengers
$passengers = [];
try {
    // Try to get passengers from the database - this might fail if the table doesn't exist
    $stmt = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $passenger_result = $stmt->get_result();
    while ($passenger = $passenger_result->fetch_assoc()) {
        $passengers[] = $passenger;
    }
} catch (Exception $e) {
    // If passengers table doesn't exist, we'll use the booking count instead
    $passenger_count = $booking['passengers'] ?? 1;
    for ($i = 0; $i < $passenger_count; $i++) {
        $passengers[] = [
            'passenger_id' => $i,
            'first_name' => $i === 0 ? $_SESSION['first_name'] ?? 'Main' : 'Passenger',
            'last_name' => $i === 0 ? $_SESSION['last_name'] ?? 'Passenger' : ($i + 1),
            'seat_number' => 'Not assigned',
            'passenger_type' => 'Adult'
        ];
    }
}

// Get booking history
$booking_history = [];
try {
    $stmt = $conn->prepare("SELECT * FROM booking_history WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
    while ($history = $history_result->fetch_assoc()) {
        $booking_history[] = $history;
    }
} catch (Exception $e) {
    // Silently handle if booking_history table doesn't exist
}

// Get payment details
$payment_details = [];
try {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    if ($payment_result->num_rows > 0) {
        $payment_details = $payment_result->fetch_assoc();
    }
} catch (Exception $e) {
    // Silently handle if payments table doesn't exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -30px;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background-color: #3b71ca;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #3b71ca;
            z-index: 1;
        }
        
        .timeline::before {
            content: "";
            position: absolute;
            left: -22px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e0e0e0;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                        <li class="breadcrumb-item active">Booking Details</li>
                    </ol>
                </nav>
                <h1 class="h3">Booking #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></h1>
                <p class="text-muted">
                    <?php echo $departure_date; ?> · 
                    <?php echo htmlspecialchars($booking['departure_city']); ?> to 
                    <?php echo htmlspecialchars($booking['arrival_city']); ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end align-self-center">
                <div class="mb-2">
                    <?php 
                        $status_class = '';
                        switch($booking['booking_status']) {
                            case 'confirmed': $status_class = 'success'; break;
                            case 'pending': $status_class = 'warning'; break;
                            case 'cancelled': $status_class = 'danger'; break;
                            case 'completed': $status_class = 'info'; break;
                            default: $status_class = 'secondary';
                        }
                    ?>
                    <span class="badge bg-<?php echo $status_class; ?> fs-6"><?php echo ucfirst($booking['booking_status']); ?></span>
                </div>
                <div>
                    <?php if ($booking['booking_status'] === 'pending' && $booking['payment_status'] === 'pending'): ?>
                        <a href="../booking/payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                            <i class="fas fa-credit-card me-1"></i> Complete Payment
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($check_in_available && !$check_in_completed): ?>
                        <a href="../booking/check-in.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                            <i class="fas fa-check-circle me-1"></i> Check-In
                        </a>
                    <?php elseif ($check_in_completed): ?>
                        <a href="../booking/boarding-pass.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-file-alt me-1"></i> View Boarding Pass
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($can_cancel): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Flight Details Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-plane text-primary fa-lg"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                        <div class="text-muted"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <div class="small text-muted">Departure</div>
                                <div class="h5"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                <div class="fw-bold"><?php echo $departure_time; ?></div>
                                <div class="small text-muted"><?php echo $departure_date; ?></div>
                            </div>
                            
                            <div class="col-md-2 text-center">
                                <div class="small text-muted mb-2">Duration</div>
                                <div class="text-muted"><?php echo $duration; ?></div>
                                <div class="flight-path">
                                    <div class="flight-path-line"></div>
                                    <i class="fas fa-plane"></i>
                                </div>
                            </div>
                            
                            <div class="col-md-5 text-md-end">
                                <div class="small text-muted">Arrival</div>
                                <div class="h5"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                <div class="fw-bold"><?php echo $arrival_time; ?></div>
                                <div class="small text-muted"><?php echo $arrival_date; ?></div>
                            </div>
                        </div>
                        
                        <?php
                        // Get flight status details
                        $status_class = '';
                        $status_text = '';
                        
                        switch($booking['flight_status']) {
                            case 'scheduled':
                                $status_class = 'success';
                                $status_text = 'On Time';
                                break;
                            case 'delayed':
                                $status_class = 'warning';
                                $status_text = 'Delayed';
                                break;
                            case 'boarding':
                                $status_class = 'info';
                                $status_text = 'Boarding';
                                break;
                            case 'departed':
                                $status_class = 'primary';
                                $status_text = 'Departed';
                                break;
                            case 'arrived':
                                $status_class = 'secondary';
                                $status_text = 'Arrived';
                                break;
                            case 'cancelled':
                                $status_class = 'danger';
                                $status_text = 'Cancelled';
                                break;
                            default:
                                $status_class = 'success';
                                $status_text = 'On Time';
                        }
                        ?>
                        
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="small text-muted">Flight Status</div>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="small text-muted">Passengers</div>
                                <div><?php echo count($passengers); ?></div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="small text-muted">Class</div>
                                <div><?php echo isset($booking['class']) ? htmlspecialchars($booking['class']) : 'Economy'; ?></div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="small text-muted">Booking Date</div>
                                <div><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Passengers Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Passengers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($passengers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Passenger Type</th>
                                            <th>Seat</th>
                                            <?php if ($check_in_completed): ?>
                                            <th>Status</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($passengers as $passenger): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($passenger['passenger_type'] ?? 'Adult'); ?></td>
                                            <td>
                                                <?php if (isset($passenger['seat_number']) && $passenger['seat_number'] !== 'Not assigned'): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($passenger['seat_number']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($check_in_completed): ?>
                                            <td>
                                                <span class="badge bg-success">Checked In</span>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No passenger information available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Details Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="small text-muted">Payment Status</div>
                                <?php
                                $payment_badge_class = '';
                                switch($booking['payment_status']) {
                                    case 'completed': $payment_badge_class = 'success'; break;
                                    case 'pending': $payment_badge_class = 'warning'; break;
                                    case 'failed': $payment_badge_class = 'danger'; break;
                                    case 'refunded': $payment_badge_class = 'info'; break;
                                    default: $payment_badge_class = 'secondary';
                                }
                                ?>
                                <span class="badge bg-<?php echo $payment_badge_class; ?>"><?php echo ucfirst($booking['payment_status']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Payment Method</div>
                                <div><?php echo isset($payment_details['payment_method']) ? htmlspecialchars($payment_details['payment_method']) : 'Not specified'; ?></div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="fw-bold">Base Fare:</div>
                                    <div><?php echo $currency_symbol . number_format($booking['base_fare'] ?? $booking['total_amount'] * 0.85, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="fw-bold">Taxes & Fees:</div>
                                    <div><?php echo $currency_symbol . number_format($booking['taxes_fees'] ?? $booking['total_amount'] * 0.15, 2); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-bold">Total Amount:</div>
                                        <div class="h5 mb-0"><?php echo $currency_symbol . number_format($booking['total_amount'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($booking['payment_status'] === 'pending'): ?>
                            <div class="mt-3">
                                <a href="../booking/payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card me-1"></i> Complete Payment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Booking History -->
                <?php if (count($booking_history) > 0): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Booking History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach($booking_history as $index => $history): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong><?php echo htmlspecialchars($history['status']); ?></strong>
                                        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($history['created_at'])); ?></small>
                                    </div>
                                    <div class="text-muted">
                                        <?php echo htmlspecialchars($history['notes'] ?? 'No additional information'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Action Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($check_in_available && !$check_in_completed): ?>
                                <a href="../booking/check-in.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-check-circle me-1"></i> Check-In
                                </a>
                            <?php elseif ($check_in_completed): ?>
                                <a href="../booking/boarding-pass.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-file-alt me-1"></i> View Boarding Pass
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                <a href="#" class="btn btn-outline-primary">
                                    <i class="fas fa-suitcase me-1"></i> Add Baggage
                                </a>
                                <a href="#" class="btn btn-outline-primary">
                                    <i class="fas fa-chair me-1"></i> Select Seats
                                </a>
                            <?php endif; ?>
                            
                            <a href="mailto:support@skywayairlines.com?subject=Question about Booking #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-1"></i> Contact Support
                            </a>
                            
                            <?php if ($can_cancel): ?>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="fas fa-times me-1"></i> Cancel Booking
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Timeline Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Booking Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Booking Created</strong>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></small>
                                </div>
                                <div class="text-muted small">Your booking has been created successfully.</div>
                            </div>
                            
                            <?php if ($booking['payment_status'] === 'completed'): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Payment Completed</strong>
                                    <small class="text-muted"><?php echo isset($payment_details['created_at']) ? date('M d, Y', strtotime($payment_details['created_at'])) : date('M d, Y', strtotime($booking['booking_date'])); ?></small>
                                </div>
                                <div class="text-muted small">Your payment has been processed successfully.</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($check_in_completed): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Check-In Completed</strong>
                                    <small class="text-muted">Just Now</small>
                                </div>
                                <div class="text-muted small">You have successfully checked in for your flight.</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['booking_status'] === 'cancelled'): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Booking Cancelled</strong>
                                    <small class="text-muted">Just Now</small>
                                </div>
                                <div class="text-muted small">Your booking has been cancelled.</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Help & Support -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Help & Support</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Need assistance with your booking? Our customer support team is here to help.</p>
                        <ul class="list-unstyled mb-3">
                            <li class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i> Call: +1-800-123-4567
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i> Email: support@skywayairlines.com
                            </li>
                            <li>
                                <i class="fas fa-comment text-primary me-2"></i> Live Chat: Available 24/7
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="../pages/faq.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-question-circle me-2"></i> View FAQs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Booking Modal -->
    <?php if ($can_cancel): ?>
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelForm" action="booking_actions.php" method="post">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning:</strong> Cancellation may be subject to a fee based on our policy.
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Cancellation</label>
                            <select class="form-select" name="reason" id="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="Change of plans">Change of plans</option>
                                <option value="Found better flight">Found better flight</option>
                                <option value="Schedule conflict">Schedule conflict</option>
                                <option value="Medical emergency">Medical emergency</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments" class="form-label">Additional Comments</label>
                            <textarea class="form-control" name="comments" id="comments" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_cancel" id="confirm_cancel" required>
                            <label class="form-check-label" for="confirm_cancel">
                                I understand that this cancellation cannot be undone and may be subject to cancellation fees.
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('cancelForm').submit()">Confirm Cancellation</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
