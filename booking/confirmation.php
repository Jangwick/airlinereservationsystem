<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if booking was completed
if (!isset($_SESSION['completed_booking_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$booking_id = $_SESSION['completed_booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                      f.departure_time, f.arrival_time, f.duration 
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../user/dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

// Get tickets
$stmt = $conn->prepare("SELECT * FROM tickets WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets = [];
while ($ticket = $tickets_result->fetch_assoc()) {
    $tickets[] = $ticket;
}

// Get payment details
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$payment = $payment_result->fetch_assoc();

// Clear the completed booking ID from session
// We'll keep it for this page load, but clear it after to prevent refreshing issues
$completed_booking_id = $_SESSION['completed_booking_id'];
unset($_SESSION['completed_booking_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - SkyWay Airlines</title>
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
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>
                        <h2 class="mb-3">Booking Confirmed!</h2>
                        <p class="lead">Thank you for choosing SkyWay Airlines. Your booking has been confirmed and e-tickets have been generated.</p>
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="../user/bookings.php" class="btn btn-outline-primary">View All Bookings</a>
                            <a href="#tickets" class="btn btn-primary">View E-Tickets</a>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Booking Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Booking Reference:</strong> SKYWAY-<?php echo $booking_id; ?></p>
                                <p><strong>Booking Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($booking['booking_date'])); ?></p>
                                <p><strong>Booking Status:</strong> 
                                    <span class="badge bg-success">Confirmed</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                                <p><strong>Payment Status:</strong> 
                                    <span class="badge bg-success">Completed</span>
                                </p>
                                <p><strong>Transaction ID:</strong> <?php echo $payment['transaction_id']; ?></p>
                            </div>
                        </div>
                        
                        <div class="flight-details mb-4">
                            <h5 class="border-bottom pb-2 mb-3">Flight Information</h5>
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <img src="../assets/images/airlines/<?php echo strtolower(str_replace(' ', '-', $booking['airline'])); ?>.png" alt="<?php echo $booking['airline']; ?>" class="img-fluid" style="max-height: 50px;">
                                        <div class="mt-2">
                                            <strong><?php echo $booking['airline']; ?></strong>
                                        </div>
                                        <div class="text-muted small"><?php echo $booking['flight_number']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-center">
                                            <div class="fw-bold fs-4"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></div>
                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                            <div class="fw-bold"><?php echo $booking['departure_city']; ?></div>
                                        </div>
                                        <div class="text-center flex-grow-1 px-3">
                                            <div class="text-muted small"><?php echo $booking['duration']; ?></div>
                                            <div class="flight-route">
                                                <div class="flight-dots"></div>
                                                <div class="flight-dots right"></div>
                                            </div>
                                            <div>Direct Flight</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold fs-4"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></div>
                                            <div><?php echo date('M d, Y', strtotime($booking['arrival_time'])); ?></div>
                                            <div class="fw-bold"><?php echo $booking['arrival_city']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="passenger-details mb-4">
                            <h5 class="border-bottom pb-2 mb-3">Passenger Information</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Passenger Name</th>
                                            <th>Seat Number</th>
                                            <th>Ticket Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ticket['passenger_name']); ?></td>
                                                <td><?php echo htmlspecialchars($ticket['seat_number']); ?></td>
                                                <td><code><?php echo htmlspecialchars($ticket['ticket_number']); ?></code></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="payment-details mb-4">
                            <h5 class="border-bottom pb-2 mb-3">Payment Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Base Fare:</span>
                                        <span>$<?php echo number_format($booking['total_amount'] * 0.8, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Taxes & Fees:</span>
                                        <span>$<?php echo number_format($booking['total_amount'] * 0.2, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total Amount:</span>
                                        <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- E-Tickets Section -->
                <div id="tickets" class="mb-4">
                    <h3 class="mb-4">Your E-Tickets</h3>
                    
                    <?php foreach ($tickets as $index => $ticket): ?>
                        <div class="ticket-container">
                            <div class="ticket-header">
                                <div class="ticket-logo">
                                    <img src="../assets/images/logo.png" alt="SkyWay Airlines" class="img-fluid" style="max-height: 50px;">
                                </div>
                                <div class="ticket-qr">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo $ticket['ticket_number']; ?>" alt="QR Code">
                                    <div class="mt-1 small text-muted">Scan for verification</div>
                                </div>
                            </div>
                            
                            <div class="ticket-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Boarding Pass</h5>
                                        <p class="ticket-number mb-0">
                                            <strong>Ticket #:</strong> <?php echo $ticket['ticket_number']; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <div class="badge bg-success">Confirmed</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ticket-flight-info">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <img src="../assets/images/airlines/<?php echo strtolower(str_replace(' ', '-', $booking['airline'])); ?>.png" alt="<?php echo $booking['airline']; ?>" class="img-fluid" style="max-height: 40px;">
                                            <div class="mt-2">
                                                <strong><?php echo $booking['airline']; ?></strong>
                                            </div>
                                            <div class="text-muted small"><?php echo $booking['flight_number']; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="text-center">
                                                <div class="fw-bold fs-4"><?php echo date('H:i', strtotime($booking['departure_time'])); ?></div>
                                                <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                                <div class="fw-bold"><?php echo $booking['departure_city']; ?></div>
                                            </div>
                                            <div class="text-center flex-grow-1 px-3">
                                                <div class="text-muted small"><?php echo $booking['duration']; ?></div>
                                                <div class="flight-route">
                                                    <div class="flight-dots"></div>
                                                    <div class="flight-dots right"></div>
                                                </div>
                                                <div>Direct Flight</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="fw-bold fs-4"><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></div>
                                                <div><?php echo date('M d, Y', strtotime($booking['arrival_time'])); ?></div>
                                                <div class="fw-bold"><?php echo $booking['arrival_city']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ticket-passenger-info">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="mb-1"><strong>Passenger:</strong> <?php echo $ticket['passenger_name']; ?></p>
                                        <p class="mb-1"><strong>Seat:</strong> <?php echo $ticket['seat_number']; ?></p>
                                        <p class="mb-1"><strong>Class:</strong> 
                                            <?php 
                                            // Determine class from total amount (this is a simplification)
                                            $per_passenger = $booking['total_amount'] / $booking['passengers'];
                                            $class = 'Economy';
                                            if ($per_passenger > 1000) $class = 'First Class';
                                            else if ($per_passenger > 500) $class = 'Business';
                                            echo $class;
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <div class="boarding-time">
                                            <p class="mb-1"><strong>Boarding:</strong></p>
                                            <p class="mb-1"><?php 
                                                $boarding_time = strtotime($booking['departure_time']) - 1800; // 30 min before
                                                echo date('H:i', $boarding_time); 
                                            ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ticket-footer mt-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="small text-muted mb-0">Please arrive at the airport at least 2 hours before departure for domestic flights and 3 hours for international flights.</p>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <p class="small mb-0"><strong>Gate:</strong> TBA</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ticket-barcode mt-4">
                                <img src="https://barcodeapi.org/api/code128/<?php echo $ticket['ticket_number']; ?>" alt="Barcode" class="img-fluid" style="max-width: 80%;">
                            </div>
                            
                            <div class="ticket-actions mt-4">
                                <button class="btn btn-outline-primary me-2 print-ticket" data-ticket-id="<?php echo $ticket['ticket_id']; ?>">
                                    <i class="fas fa-print me-1"></i> Print Ticket
                                </button>
                                <a href="ticket_download.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-success me-2">
                                    <i class="fas fa-download me-1"></i> Download PDF
                                </a>
                                <a href="ticket_email.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-info">
                                    <i class="fas fa-envelope me-1"></i> Email Ticket
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center my-5">
                    <h4 class="mb-3">What's Next?</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle fa-3x text-success"></i>
                                    </div>
                                    <h5>Check-In Online</h5>
                                    <p>Check-in online 24 hours before your flight for a smooth experience.</p>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Online Check-In</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-suitcase fa-3x text-primary"></i>
                                    </div>
                                    <h5>Baggage Information</h5>
                                    <p>View baggage allowance and purchase additional baggage if needed.</p>
                                    <a href="#" class="btn btn-sm btn-outline-primary">Baggage Info</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-calendar-check fa-3x text-primary"></i>
                                    </div>
                                    <h5>Add to Calendar</h5>
                                    <p>Don't miss your flight! Add the flight details to your calendar.</p>
                                    <a href="#" class="btn btn-sm btn-outline-primary add-to-calendar" 
                                       data-title="Flight <?php echo $booking['flight_number']; ?> to <?php echo $booking['arrival_city']; ?>"
                                       data-start="<?php echo $booking['departure_time']; ?>"
                                       data-end="<?php echo $booking['arrival_time']; ?>"
                                       data-location="<?php echo $booking['departure_city']; ?> Airport"
                                       data-description="<?php echo $booking['airline']; ?> Flight <?php echo $booking['flight_number']; ?> from <?php echo $booking['departure_city']; ?> to <?php echo $booking['arrival_city']; ?>">
                                        Add to Calendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-4">
                    <a href="../index.php" class="btn btn-primary">Return to Home</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Print ticket functionality
        document.addEventListener('DOMContentLoaded', function() {
            const printButtons = document.querySelectorAll('.print-ticket');
            printButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const ticketId = this.getAttribute('data-ticket-id');
                    const ticketElement = this.closest('.ticket-container');
                    
                    // Create a new window for printing
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write('<html><head><title>Print Ticket</title>');
                    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">');
                    printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">');
                    printWindow.document.write('<link rel="stylesheet" href="../assets/css/style.css">');
                    printWindow.document.write('</head><body class="p-4">');
                    
                    // Add the ticket content
                    printWindow.document.write('<div class="ticket-container" style="max-width: 800px; margin: 0 auto;">');
                    printWindow.document.write(ticketElement.innerHTML);
                    printWindow.document.write('</div>');
                    
                    // Remove the action buttons for printing
                    printWindow.document.write('<script>document.querySelector(".ticket-actions").remove();</script>');
                    
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    
                    // Wait for resources to load, then print
                    printWindow.onload = function() {
                        printWindow.print();
                        // printWindow.close();
                    };
                });
            });
            
            // Add to calendar functionality
            const addToCalendarButtons = document.querySelectorAll('.add-to-calendar');
            addToCalendarButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const flightData = {
                        title: this.getAttribute('data-title'),
                        start: this.getAttribute('data-start'),
                        end: this.getAttribute('data-end'),
                        location: this.getAttribute('data-location'),
                        description: this.getAttribute('data-description')
                    };
                    
                    // Google Calendar link
                    const googleCalendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(flightData.title)}&dates=${formatDateForGoogle(flightData.start)}/${formatDateForGoogle(flightData.end)}&details=${encodeURIComponent(flightData.description)}&location=${encodeURIComponent(flightData.location)}&sf=true&output=xml`;
                    
                    window.open(googleCalendarUrl);
                });
            });
            
            function formatDateForGoogle(dateString) {
                const date = new Date(dateString);
                return date.toISOString().replace(/-|:|\.\d+/g, '');
            }
        });
    </script>
</body>
</html>
