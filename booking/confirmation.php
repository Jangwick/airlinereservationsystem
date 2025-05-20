<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Check if booking was successful or if booking_id was directly provided
if ((!isset($_SESSION['booking_success']) && !isset($_GET['booking_id'])) || 
    (!isset($_SESSION['booking_success']) && !isset($_SESSION['user_id']))) {
    header("Location: ../flights/search.php");
    exit();
}

// Get booking ID
$booking_id = intval($_GET['booking_id'] ?? $_SESSION['recent_booking_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

// Clear session flags
if (isset($_SESSION['booking_success'])) {
    unset($_SESSION['booking_success']);
}

// Double-check that the booking exists and belongs to this user
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                      f.departure_time, f.arrival_time 
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Try to find the booking without user_id check (for admin use)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                              f.departure_time, f.arrival_time 
                              FROM bookings b 
                              JOIN flights f ON b.flight_id = f.flight_id 
                              WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            header("Location: ../flights/search.php");
            exit();
        }
    } else {
        header("Location: ../flights/search.php");
        exit();
    }
}

$booking = $result->fetch_assoc();

// Debug info to check booking details
$debug_info = "Booking ID: $booking_id, User ID: $user_id";
error_log($debug_info);
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
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow border-0 mb-4">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h1 class="h3 mb-3">Booking Successful!</h1>
                        <p class="lead mb-4">Your flight has been booked successfully. A confirmation email has been sent to your registered email address.</p>
                        <div class="alert alert-light border mb-4">
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Booking Reference:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center">BK-<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Flight Number:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Route:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center"><?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Departure Date:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center"><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Departure Time:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 text-sm-end text-center"><strong>Amount Paid:</strong></div>
                                <div class="col-sm-6 text-sm-start text-center">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="../user/booking_details.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-info-circle me-2"></i>View Booking Details
                            </a>
                            <a href="../user/bookings.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>View All Bookings
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i> Return to Home
                    </a>
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
