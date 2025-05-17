<?php
session_start();

// Include functions file for base URL
require_once '../includes/functions.php';
$baseUrl = getBaseUrl();

// Initialize variables
$error = '';
$booking = null;

// Process form submission for booking retrieval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_reference = $_POST['booking_reference'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    
    if (empty($booking_reference) || empty($last_name)) {
        $error = 'Please enter both booking reference and last name';
    } else {
        // Include database connection
        require_once '../db/db_config.php';
        
        // Query to find booking
        $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                                f.departure_time, f.arrival_time, f.status as flight_status, 
                                u.last_name
                                FROM bookings b 
                                JOIN flights f ON b.flight_id = f.flight_id 
                                JOIN users u ON b.user_id = u.user_id 
                                WHERE b.booking_id = ? AND u.last_name = ?");
        $stmt->bind_param("is", $booking_reference, $last_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            
            // Get tickets for this booking
            $stmt = $conn->prepare("SELECT * FROM tickets WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_reference);
            $stmt->execute();
            $tickets_result = $stmt->get_result();
            $tickets = [];
            
            while ($ticket = $tickets_result->fetch_assoc()) {
                $tickets[] = $ticket;
            }
            
            $booking['tickets'] = $tickets;
        } else {
            $error = 'No booking found with these details. Please check and try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Booking - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-search me-2"></i>Retrieve Your Booking</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking): ?>
                            <!-- Booking details display -->
                            <div class="booking-details">
                                <div class="alert alert-success">
                                    <h5 class="alert-heading">Booking Found!</h5>
                                    <p>Here are the details of your booking:</p>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Booking Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Booking Reference:</strong> #<?php echo $booking['booking_id']; ?></p>
                                                <p><strong>Booking Date:</strong> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></p>
                                                <p><strong>Status:</strong> 
                                                    <?php 
                                                    $status_class = 'bg-success';
                                                    if ($booking['booking_status'] == 'pending') $status_class = 'bg-warning text-dark';
                                                    if ($booking['booking_status'] == 'cancelled') $status_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['booking_status']); ?></span>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Passengers:</strong> <?php echo $booking['passengers']; ?></p>
                                                <p><strong>Amount:</strong> $<?php echo number_format($booking['total_amount'], 2); ?></p>
                                                <p><strong>Payment Status:</strong> 
                                                    <?php 
                                                    $payment_class = 'bg-success';
                                                    if ($booking['payment_status'] == 'pending') $payment_class = 'bg-warning text-dark';
                                                    if ($booking['payment_status'] == 'refunded') $payment_class = 'bg-info';
                                                    if ($booking['payment_status'] == 'failed') $payment_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $payment_class; ?>"><?php echo ucfirst($booking['payment_status']); ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Flight Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5><?php echo htmlspecialchars($booking['airline']); ?> - <?php echo htmlspecialchars($booking['flight_number']); ?></h5>
                                                <div class="text-muted"><?php echo date('l, F j, Y', strtotime($booking['departure_time'])); ?></div>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $booking['flight_status'] === 'scheduled' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($booking['flight_status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="text-muted">Departure</div>
                                                    <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></div>
                                                    <div><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="text-muted">Duration</div>
                                                    <?php
                                                    $departure = new DateTime($booking['departure_time']);
                                                    $arrival = new DateTime($booking['arrival_time']);
                                                    $interval = $departure->diff($arrival);
                                                    $duration = $interval->format('%h h %i m');
                                                    ?>
                                                    <div><i class="fas fa-clock me-1"></i> <?php echo $duration; ?></div>
                                                    <div class="flight-path">
                                                        <div class="flight-dots"></div>
                                                        <div class="flight-dots right"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="text-muted">Arrival</div>
                                                    <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></div>
                                                    <div><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($booking['tickets'])): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Tickets</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Ticket Number</th>
                                                        <th>Passenger Name</th>
                                                        <th>Seat</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($booking['tickets'] as $ticket): ?>
                                                    <tr>
                                                        <td><code><?php echo $ticket['ticket_number']; ?></code></td>
                                                        <td><?php echo htmlspecialchars($ticket['passenger_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($ticket['seat_number']); ?></td>
                                                        <td>
                                                            <?php if ($ticket['status'] == 'active'): ?>
                                                                <span class="badge bg-success">Active</span>
                                                            <?php elseif ($ticket['status'] == 'used'): ?>
                                                                <span class="badge bg-secondary">Used</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Cancelled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="ticket_view.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                                        <a href="change_flight.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-exchange-alt me-1"></i> Change Flight
                                        </a>
                                        <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger">
                                            <i class="fas fa-times-circle me-1"></i> Cancel Booking
                                        </a>
                                    <?php endif; ?>
                                    <a href="check-in.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-check-circle me-1"></i> Web Check-In
                                    </a>
                                    <button class="btn btn-secondary" onclick="window.print()">
                                        <i class="fas fa-print me-1"></i> Print Details
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Booking lookup form -->
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                                <p class="text-muted mb-4">Please enter your booking reference and last name to retrieve your booking details.</p>
                                
                                <div class="mb-3">
                                    <label for="booking_reference" class="form-label">Booking Reference</label>
                                    <input type="text" class="form-control" id="booking_reference" name="booking_reference" required>
                                    <div class="form-text">Enter the booking number from your confirmation email</div>
                                    <div class="invalid-feedback">Please enter your booking reference</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    <div class="form-text">Enter the last name used during booking</div>
                                    <div class="invalid-feedback">Please enter your last name</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Retrieve Booking</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle text-primary me-2"></i>Need Help?</h5>
                        <p class="card-text">If you're having trouble retrieving your booking, please contact our customer support at <a href="mailto:support@skywayairlines.com">support@skywayairlines.com</a> or call us at +63 (2) 8123 4567.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms to apply validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
