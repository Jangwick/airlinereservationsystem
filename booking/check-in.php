<?php
session_start();

// Include functions file for base URL
require_once '../includes/functions.php';
$baseUrl = getBaseUrl();

// Initialize variables
$error = '';
$success = '';
$booking = null;
$booking_id = $_GET['booking_id'] ?? null;

// If booking ID provided in URL, try to retrieve it
if ($booking_id) {
    // Include database connection
    require_once '../db/db_config.php';
    
    // Query to find booking
    $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                          f.departure_time, f.arrival_time, f.status as flight_status
                          FROM bookings b 
                          JOIN flights f ON b.flight_id = f.flight_id 
                          WHERE b.booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Get tickets for this booking
        $stmt = $conn->prepare("SELECT * FROM tickets WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $tickets_result = $stmt->get_result();
        $tickets = [];
        
        while ($ticket = $tickets_result->fetch_assoc()) {
            $tickets[] = $ticket;
        }
        
        $booking['tickets'] = $tickets;
    } else {
        $error = 'No booking found with this ID.';
    }
}

// Process check-in form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_reference = $_POST['booking_reference'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    
    if (empty($booking_reference) || empty($last_name)) {
        $error = 'Please enter both booking reference and last name';
    } else {
        // Include database connection
        if (!isset($conn)) {
            require_once '../db/db_config.php';
        }
        
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
            
            // Check if flight is eligible for check-in (24h before departure)
            $departure_time = strtotime($booking['departure_time']);
            $current_time = time();
            $time_difference = $departure_time - $current_time;
            $hours_to_departure = $time_difference / 3600;
            
            if ($hours_to_departure > 24) {
                $error = 'Check-in is only available 24 hours before departure.';
            } elseif ($hours_to_departure < 0) {
                $error = 'This flight has already departed.';
            } else {
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
                
                // Process check-in (mark tickets as checked-in)
                if (isset($_POST['check_in']) && $_POST['check_in'] == 'yes') {
                    $stmt = $conn->prepare("UPDATE tickets SET status = 'active', checked_in = 1, checked_in_time = NOW() WHERE booking_id = ?");
                    $stmt->bind_param("i", $booking_reference);
                    
                    if ($stmt->execute()) {
                        $success = 'Check-in successful! Your boarding passes are ready.';
                        
                        // Refresh ticket data
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
                        $error = 'Check-in failed. Please try again.';
                    }
                }
            }
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
    <title>Web Check-In - SkyWay Airlines</title>
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
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Web Check-In</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking && empty($error)): ?>
                            <!-- Check-in details display -->
                            <div class="check-in-details">
                                <?php if (empty($success)): ?>
                                    <div class="alert alert-info">
                                        <h5 class="alert-heading">Booking Found!</h5>
                                        <p>You can now proceed with check-in for your flight.</p>
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
                                    
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                        <input type="hidden" name="booking_reference" value="<?php echo $booking['booking_id']; ?>">
                                        <input type="hidden" name="last_name" value="<?php echo $booking['last_name']; ?>">
                                        <input type="hidden" name="check_in" value="yes">
                                        
                                        <div class="alert alert-warning">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="terms" required>
                                                <label class="form-check-label" for="terms">
                                                    I confirm that I have read and agree to the <a href="../pages/terms.php" target="_blank">terms and conditions</a> for travel and that all passengers have valid identification.
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">Complete Check-In</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <!-- Boarding pass display after successful check-in -->
                                    <div class="boarding-passes">
                                        <h5 class="mb-4">Your Boarding Passes</h5>
                                        <?php foreach ($booking['tickets'] as $index => $ticket): ?>
                                            <div class="card boarding-pass mb-4">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0">Boarding Pass</h5>
                                                    <span class="badge bg-success">Checked-In</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Passenger</div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($ticket['passenger_name']); ?></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Flight</div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">From</div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">To</div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Date</div>
                                                                    <div class="fw-bold"><?php echo date('d M Y', strtotime($booking['departure_time'])); ?></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Time</div>
                                                                    <div class="fw-bold"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Seat</div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($ticket['seat_number']); ?></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Gate</div>
                                                                    <div class="fw-bold">TBA</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 text-center">
                                                            <div class="qr-code-container mb-2">
                                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $ticket['ticket_number']; ?>" alt="Boarding Pass QR Code" class="img-fluid">
                                                            </div>
                                                            <div class="small text-muted">Boarding Pass #<?php echo $ticket['ticket_number']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="small text-muted">Please arrive at the gate at least 30 minutes before departure</div>
                                                        <div>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                                                <i class="fas fa-print me-1"></i> Print
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Check-in lookup form -->
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                                <p class="text-muted mb-4">Please enter your booking reference and last name to check in for your flight.</p>
                                
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
                                    <button type="submit" class="btn btn-primary">Proceed to Check-In</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle text-primary me-2"></i>Web Check-In Information</h5>
                        <p class="card-text">Web check-in opens 24 hours before departure and closes 2 hours before departure. After checking in, please arrive at the airport at least 2 hours before your flight for domestic flights or 3 hours for international flights.</p>
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
