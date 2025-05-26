<?php
session_start();

// Check if user is logged in and booking_id is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['booking_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';
// Include currency helper
require_once '../includes/currency_helper.php';

$booking_id = $_SESSION['booking_id'];
$user_id = $_SESSION['user_id'];

// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);

// Get booking details
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                        f.departure_time, f.arrival_time
                        FROM bookings b
                        JOIN flights f ON b.flight_id = f.flight_id
                        WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if booking exists
if ($result->num_rows === 0) {
    header("Location: ../user/dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

// Get passenger details if table exists
$passengers = [];
try {
    $stmt = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $passengers_result = $stmt->get_result();
    
    while ($passenger = $passengers_result->fetch_assoc()) {
        $passengers[] = $passenger;
    }
} catch (Exception $e) {
    // If passengers table doesn't exist, we'll just skip this
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Function to get ticket number
function generateTicketNumber($booking_id, $passenger_index) {
    $prefix = 'TKT';
    $date_part = date('ymd');
    $ticket_number = $prefix . $date_part . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . str_pad($passenger_index, 2, '0', STR_PAD_LEFT);
    return $ticket_number;
}

// Clear booking_id from session to prevent reloading issues
unset($_SESSION['booking_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confirmation-icon {
            font-size: 4rem;
            color: #28a745;
        }
        .booking-detail {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle confirmation-icon mb-4"></i>
                        <h2 class="mb-3">Payment Successful!</h2>
                        <p class="lead mb-1">Your booking has been confirmed.</p>
                        <p class="text-muted mb-4">Booking ID: <strong>BK-<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></strong></p>
                        <div class="alert alert-success mb-4" role="alert">
                            <i class="fas fa-envelope me-2"></i> A confirmation email has been sent to <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="../user/booking_details.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-info-circle me-1"></i> View Booking Details
                            </a>
                            <a href="../user/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-1"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="booking-detail">
                                    <span class="text-muted">Flight Number:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Airline:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['airline']); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Departure:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Arrival:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="booking-detail">
                                    <span class="text-muted">Departure Date:</span>
                                    <span class="fw-bold"><?php echo date('F j, Y', strtotime($booking['departure_time'])); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Departure Time:</span>
                                    <span class="fw-bold"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Arrival Date:</span>
                                    <span class="fw-bold"><?php echo date('F j, Y', strtotime($booking['arrival_time'])); ?></span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Arrival Time:</span>
                                    <span class="fw-bold"><?php echo date('h:i A', strtotime($booking['arrival_time'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Passenger(s)</h6>
                                <?php if (count($passengers) > 0): ?>
                                    <?php foreach ($passengers as $index => $passenger): ?>
                                        <div class="booking-detail">
                                            <span class="text-muted">Passenger <?php echo $index + 1; ?>:</span>
                                            <span class="fw-bold"><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></span>
                                            <?php if (isset($passenger['ticket_number'])): ?>
                                                <div><small class="text-muted">Ticket: <?php echo $passenger['ticket_number']; ?></small></div>
                                            <?php else: ?>
                                                <div><small class="text-muted">Ticket: <?php echo generateTicketNumber($booking_id, $index + 1); ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="booking-detail">
                                        <span class="text-muted">Passenger:</span>
                                        <span class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                        <div><small class="text-muted">Ticket: <?php echo generateTicketNumber($booking_id, 1); ?></small></div>
                                    </div>
                                    <?php for ($i = 1; $i < $booking['passengers']; $i++): ?>
                                        <div class="booking-detail">
                                            <span class="text-muted">Passenger <?php echo $i + 1; ?>:</span>
                                            <span class="fw-bold">Additional Passenger</span>
                                            <div><small class="text-muted">Ticket: <?php echo generateTicketNumber($booking_id, $i + 1); ?></small></div>
                                        </div>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Payment Details</h6>
                                <div class="booking-detail">
                                    <span class="text-muted">Payment Method:</span>
                                    <span class="fw-bold">
                                        <?php echo isset($booking['payment_method']) ? htmlspecialchars($booking['payment_method']) : 'Credit Card'; ?>
                                    </span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Payment Status:</span>
                                    <span class="badge bg-success">Completed</span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Transaction ID:</span>
                                    <span class="fw-bold">
                                        <?php echo isset($booking['transaction_id']) ? htmlspecialchars($booking['transaction_id']) : 'TXN' . time(); ?>
                                    </span>
                                </div>
                                <div class="booking-detail">
                                    <span class="text-muted">Amount Paid:</span>
                                    <span class="fw-bold"><?php echo $currency_symbol . number_format($booking['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Next Steps</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4 text-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-check-circle fa-2x text-primary"></i>
                                </div>
                                <h6>Booking Confirmed</h6>
                                <p class="small text-muted">Your booking has been confirmed and tickets have been issued.</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-plane-departure fa-2x text-primary"></i>
                                </div>
                                <h6>Check-in</h6>
                                <p class="small text-muted">Online check-in will be available 48 hours before your flight.</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-ticket-alt fa-2x text-primary"></i>
                                </div>
                                <h6>Get Boarding Pass</h6>
                                <p class="small text-muted">After check-in, download your boarding pass or collect it at the airport.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="col-lg-4">
                <?php 
                $category = 'booking';
                $baseUrl = '../';
                include '../includes/widgets/faq_widget.php';
                ?>
                
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p>If you have any questions about your booking, our customer service team is here to help.</p>
                        <div class="d-grid gap-2">
                            <a href="../pages/contact.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i> Contact Support
                            </a>
                            <a href="tel:+123456789" class="btn btn-outline-secondary">
                                <i class="fas fa-phone me-1"></i> Call Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
