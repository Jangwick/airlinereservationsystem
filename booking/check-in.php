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
$message = '';
$message_type = '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// If user is checking in for a specific booking
if ($booking_id > 0) {
    // Get booking details
    $stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                          f.departure_time, f.arrival_time 
                          FROM bookings b 
                          JOIN flights f ON b.flight_id = f.flight_id 
                          WHERE b.booking_id = ? AND b.user_id = ? AND b.booking_status = 'confirmed'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "Booking not found or not eligible for check-in.";
        $message_type = "danger";
        $booking = null;
    } else {
        $booking = $result->fetch_assoc();
        
        // Check if check-in is allowed (between 48 hrs before and until departure)
        $now = new DateTime();
        $departure = new DateTime($booking['departure_time']);
        $diff = $now->diff($departure);
        
        if ($now > $departure) {
            $message = "Check-in is not available after departure.";
            $message_type = "danger";
        } elseif ($diff->days > 2) {
            $message = "Check-in opens 48 hours before departure.";
            $message_type = "warning";
        } else {
            // Check if already checked in
            if (isset($booking['check_in_status']) && $booking['check_in_status'] === 'completed') {
                $message = "You have already checked in for this flight.";
                $message_type = "info";
            }
        }
        
        // Get passenger details if available
        $passengers = [];
        $passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;
        
        if ($passengers_table_exists) {
            $stmt = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $passengers_result = $stmt->get_result();
            
            while ($passenger = $passengers_result->fetch_assoc()) {
                $passengers[] = $passenger;
            }
        }
    }
}

// Process check-in form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    
    // Validate booking ID
    if ($booking_id <= 0) {
        $message = "Invalid booking ID.";
        $message_type = "danger";
    } else {
        // Check if booking exists and belongs to user
        $stmt = $conn->prepare("SELECT b.*, f.departure_time FROM bookings b 
                              JOIN flights f ON b.flight_id = f.flight_id 
                              WHERE b.booking_id = ? AND b.user_id = ? AND b.booking_status = 'confirmed'");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = "Booking not found or not eligible for check-in.";
            $message_type = "danger";
        } else {
            $booking_data = $result->fetch_assoc();
            
            // Check if check-in is allowed
            $now = new DateTime();
            $departure = new DateTime($booking_data['departure_time']);
            $diff = $now->diff($departure);
            
            if ($now > $departure) {
                $message = "Check-in is not available after departure.";
                $message_type = "danger";
            } elseif ($diff->days > 2) {
                $message = "Check-in opens 48 hours before departure.";
                $message_type = "warning";
            } else {
                // Check if already checked in
                if (isset($booking_data['check_in_status']) && $booking_data['check_in_status'] === 'completed') {
                    $message = "You have already checked in for this flight.";
                    $message_type = "info";
                } else {
                    // Process check-in
                    try {
                        // Start transaction
                        $conn->begin_transaction();
                        
                        // Update booking with check-in status
                        $update_query = "UPDATE bookings SET check_in_status = 'completed', check_in_time = NOW() WHERE booking_id = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param("i", $booking_id);
                        
                        if (!$update_stmt->execute()) {
                            throw new Exception("Error updating check-in status.");
                        }
                        
                        // Assign random seats if the system has passenger details
                        $passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;
                        
                        if ($passengers_table_exists) {
                            // Get passengers for this booking
                            $stmt = $conn->prepare("SELECT passenger_id FROM passengers WHERE booking_id = ?");
                            $stmt->bind_param("i", $booking_id);
                            $stmt->execute();
                            $passengers_result = $stmt->get_result();
                            
                            // Define seat letters and start from row 10
                            $seat_letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                            $row_number = 10;
                            $seat_index = 0;
                            
                            while ($passenger = $passengers_result->fetch_assoc()) {
                                $seat_number = $row_number . $seat_letters[$seat_index];
                                
                                // Update passenger with seat number
                                $seat_query = "UPDATE passengers SET seat_number = ? WHERE passenger_id = ?";
                                $seat_stmt = $conn->prepare($seat_query);
                                $seat_stmt->bind_param("si", $seat_number, $passenger['passenger_id']);
                                $seat_stmt->execute();
                                
                                // Move to next seat
                                $seat_index++;
                                if ($seat_index >= count($seat_letters)) {
                                    $seat_index = 0;
                                    $row_number++;
                                }
                            }
                        }
                        
                        // Check if booking_history table exists
                        $history_check = $conn->query("SHOW TABLES LIKE 'booking_history'");
                        
                        if ($history_check->num_rows > 0) {
                            $history_query = "INSERT INTO booking_history (booking_id, status, status_change, notes, updated_by) 
                                           VALUES (?, 'checked-in', 'Web check-in completed', 'Online check-in via website', ?)";
                            $history_stmt = $conn->prepare($history_query);
                            $history_stmt->bind_param("ii", $booking_id, $user_id);
                            $history_stmt->execute();
                        }
                        
                        // Create a notification if the table exists
                        $notification_check = $conn->query("SHOW TABLES LIKE 'notifications'");
                        
                        if ($notification_check->num_rows > 0) {
                            $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                                               VALUES (?, 'Check-in Completed', 'You have successfully checked in for your flight. Your boarding pass is ready.', 'check-in')";
                            $notification_stmt = $conn->prepare($notification_query);
                            $notification_stmt->bind_param("i", $user_id);
                            $notification_stmt->execute();
                        }
                        
                        // Commit transaction
                        $conn->commit();
                        
                        $message = "Check-in completed successfully. Your boarding pass is now available.";
                        $message_type = "success";
                        
                        // Redirect to booking details page
                        header("Location: ../user/booking_details.php?id=$booking_id&checkin=success");
                        exit();
                    } catch (Exception $e) {
                        // Roll back transaction on error
                        $conn->rollback();
                        $message = "Error during check-in: " . $e->getMessage();
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}

// Get eligible bookings for check-in
$eligible_bookings = [];
$stmt = $conn->prepare("SELECT b.booking_id, b.check_in_status, f.flight_number, f.airline, f.departure_city, 
                      f.arrival_city, f.departure_time 
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.user_id = ? AND b.booking_status = 'confirmed' 
                      AND f.departure_time > NOW() 
                      AND f.departure_time < DATE_ADD(NOW(), INTERVAL 2 DAY)
                      ORDER BY f.departure_time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$eligible_result = $stmt->get_result();

while ($eligible = $eligible_result->fetch_assoc()) {
    $eligible_bookings[] = $eligible;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .check-in-steps .step {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        
        .check-in-steps .step:last-child {
            border-bottom: none;
        }
        
        .check-in-steps .step-number {
            width: 30px;
            height: 30px;
            background-color: #3b71ca;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
        }
        
        .boarding-pass {
            background-color: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .boarding-pass-header {
            background-color: #3b71ca;
            color: white;
            padding: 15px;
        }
        
        .boarding-pass-footer {
            background-color: #3b71ca;
            color: white;
            padding: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../user/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Web Check-In</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($booking) && $booking): ?>
            <!-- Check-in for specific booking -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Check-In for Flight <?php echo htmlspecialchars($booking['flight_number']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Flight Details</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="140">Airline:</td>
                                            <td><?php echo htmlspecialchars($booking['airline']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Flight Number:</td>
                                            <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Route:</td>
                                            <td><?php echo htmlspecialchars($booking['departure_city'] . ' → ' . $booking['arrival_city']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Departure:</td>
                                            <td><?php echo date('F j, Y - h:i A', strtotime($booking['departure_time'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Booking Reference:</td>
                                            <td>BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Passenger Information</h6>
                                    <?php if (count($passengers) > 0): ?>
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Seat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($passengers as $passenger): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($passenger['title'] . ' ' . $passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                                        <td><?php echo isset($passenger['seat_number']) ? $passenger['seat_number'] : 'Not assigned yet'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>Passenger details not available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($booking['check_in_status']) && $booking['check_in_status'] === 'completed'): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    You have already checked in for this flight. Your boarding pass is ready.
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="../user/booking_details.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                        View Boarding Pass
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="check-in-steps">
                                    <form action="check-in.php" method="post">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        
                                        <!-- Step 1: Confirm Flight Details -->
                                        <div class="step d-flex">
                                            <div class="step-number">1</div>
                                            <div class="flex-grow-1">
                                                <h6>Confirm Flight Details</h6>
                                                <p>Please verify that the flight information shown above is correct.</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-check text-success"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Step 2: Terms and Conditions -->
                                        <div class="step d-flex">
                                            <div class="step-number">2</div>
                                            <div class="flex-grow-1">
                                                <h6>Terms and Conditions</h6>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                                    <label class="form-check-label" for="terms">
                                                        I confirm that I have read and agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> of travel.
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="baggage" name="baggage" required>
                                                    <label class="form-check-label" for="baggage">
                                                        I confirm that my baggage complies with <a href="#" data-bs-toggle="modal" data-bs-target="#baggageModal">baggage policy</a>.
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Step 3: Review and Complete -->
                                        <div class="step d-flex">
                                            <div class="step-number">3</div>
                                            <div class="flex-grow-1">
                                                <h6>Complete Check-In</h6>
                                                <p>Click the button below to complete your check-in.</p>
                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-check-circle me-2"></i>Complete Check-In
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Check-in landing page -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-md-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-plane-departure fa-4x text-primary mb-3"></i>
                                <h2 class="card-title">Online Check-In</h2>
                                <p class="card-text">Check in online to save time at the airport. Online check-in opens 48 hours before departure.</p>
                            </div>
                            
                            <!-- Check-in Form -->
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <form action="check-in.php" method="get" class="mb-4">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label for="booking_ref" class="form-label">Booking Reference</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">BK-</span>
                                                    <input type="text" class="form-control" id="booking_ref" name="booking_id" placeholder="123456" required pattern="[0-9]+">
                                                </div>
                                                <div class="form-text">Enter the booking reference number without the "BK-" prefix</div>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search me-2"></i>Find Booking
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Eligible Bookings -->
                            <?php if (count($eligible_bookings) > 0): ?>
                                <div class="card mb-0 border-0 bg-light">
                                    <div class="card-body">
                                        <h5 class="mb-3">Your Eligible Flights</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Flight</th>
                                                        <th>Route</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($eligible_bookings as $eligible): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($eligible['flight_number']); ?></div>
                                                                <div class="small text-muted"><?php echo htmlspecialchars($eligible['airline']); ?></div>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($eligible['departure_city'] . ' → ' . $eligible['arrival_city']); ?></td>
                                                            <td>
                                                                <div><?php echo date('M d, Y', strtotime($eligible['departure_time'])); ?></div>
                                                                <div class="small text-muted"><?php echo date('h:i A', strtotime($eligible['departure_time'])); ?></div>
                                                            </td>
                                                            <td>
                                                                <?php if (isset($eligible['check_in_status']) && $eligible['check_in_status'] === 'completed'): ?>
                                                                    <span class="badge bg-success">Checked In</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning text-dark">Not Checked In</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (isset($eligible['check_in_status']) && $eligible['check_in_status'] === 'completed'): ?>
                                                                    <a href="../user/booking_details.php?id=<?php echo $eligible['booking_id']; ?>" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-ticket-alt me-1"></i> Boarding Pass
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="check-in.php?booking_id=<?php echo $eligible['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-check-circle me-1"></i> Check-In
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Check-in Information -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-clock fa-2x text-primary"></i>
                            </div>
                            <h5 class="text-center mb-3">Check-in Time</h5>
                            <p class="card-text">Online check-in opens 48 hours before departure and closes 1 hour before the flight.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-suitcase fa-2x text-primary"></i>
                            </div>
                            <h5 class="text-center mb-3">Baggage</h5>
                            <p class="card-text">Remember to check the baggage allowance for your flight before you travel.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-id-card fa-2x text-primary"></i>
                            </div>
                            <h5 class="text-center mb-3">Travel Documents</h5>
                            <p class="card-text">Don't forget to bring your ID or passport and printed boarding pass to the airport.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Check-in Terms</h6>
                    <p>By checking in online, you confirm that you will arrive at the airport in sufficient time for all pre-boarding procedures.</p>
                    <p>You must present a valid government-issued photo ID at security checkpoints and at the boarding gate.</p>
                    <p>Failure to arrive at the boarding gate on time may result in the loss of your seat without refund.</p>
                    
                    <h6>Baggage</h6>
                    <p>Your ticket includes one carry-on bag and one personal item. Additional bags may be subject to fees.</p>
                    <p>Oversize or overweight baggage may be rejected or subject to additional fees.</p>
                    
                    <h6>Health and Safety</h6>
                    <p>You confirm that you are fit to fly and do not have any conditions that would make you unsuitable for air travel.</p>
                    <p>You agree to comply with all health and safety measures required by the airline and destination country.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Baggage Policy Modal -->
    <div class="modal fade" id="baggageModal" tabindex="-1" aria-labelledby="baggageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="baggageModalLabel">Baggage Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Carry-On Baggage</h6>
                    <p>Each passenger is allowed one carry-on bag not exceeding 22 x 14 x 9 inches (56 x 36 x 23 cm) and one personal item.</p>
                    <p>Maximum weight for carry-on baggage is 22 lbs (10 kg).</p>
                    
                    <h6>Checked Baggage</h6>
                    <p>Standard economy tickets include one checked bag up to 50 lbs (23 kg).</p>
                    <p>Maximum dimensions for checked baggage: 62 linear inches (158 cm) total of length + width + height.</p>
                    <p>Additional or overweight bags will incur extra charges.</p>
                    
                    <h6>Prohibited Items</h6>
                    <p>Dangerous goods such as explosives, compressed gases, flammable liquids or solids, etc. are prohibited.</p>
                    <p>For a complete list of restricted items, please visit the airport security website.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
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
