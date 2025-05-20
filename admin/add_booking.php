<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    // Get form data
    $user_id = intval($_POST['user_id']);
    $flight_id = intval($_POST['flight_id']);
    $passengers = intval($_POST['passengers']);
    $booking_type = $_POST['booking_type'];
    $booking_status = $_POST['booking_status'];
    $payment_status = $_POST['payment_status'];
    $admin_notes = $_POST['admin_notes'];
    
    // Validate required fields
    if (empty($user_id) || empty($flight_id) || empty($passengers)) {
        $message = "Please fill all required fields";
        $message_type = "danger";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get flight details to calculate total amount
            $stmt = $conn->prepare("SELECT price FROM flights WHERE flight_id = ?");
            $stmt->bind_param("i", $flight_id);
            $stmt->execute();
            $flight_result = $stmt->get_result();
            
            if ($flight_result->num_rows === 0) {
                throw new Exception("Flight not found");
            }
            
            $flight = $flight_result->fetch_assoc();
            $total_amount = $flight['price'] * $passengers;
            
            // Apply discount/surcharge based on booking_type
            switch ($booking_type) {
                case 'priority':
                    $total_amount *= 1.15; // 15% surcharge
                    break;
                case 'group':
                    $total_amount *= 0.90; // 10% discount
                    break;
                case 'vip':
                    $total_amount *= 1.25; // 25% surcharge
                    break;
            }
            
            // Insert booking
            $stmt = $conn->prepare("INSERT INTO bookings 
                                  (user_id, flight_id, booking_date, passengers, total_amount, 
                                   booking_status, payment_status, admin_notes, created_by) 
                                  VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)");
            
            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("iiidsssi", $user_id, $flight_id, $passengers, $total_amount, 
                             $booking_status, $payment_status, $admin_notes, $admin_id);
            $stmt->execute();
            $booking_id = $conn->insert_id;
            
            // Log admin action
            $action = "Created new booking for user ID: $user_id, flight ID: $flight_id";
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, entity_id, details, created_at)
                                  VALUES (?, 'create_booking', ?, ?, NOW())");
            $stmt->bind_param("iis", $admin_id, $booking_id, $action);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $message = "Booking created successfully! Booking ID: $booking_id";
            $message_type = "success";
            
            // Redirect to booking details
            header("Location: booking_details.php?id=$booking_id&created=1");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $message = "Error creating booking: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get users for dropdown
$users_query = "SELECT user_id, first_name, last_name, email FROM users 
               WHERE role = 'user' ORDER BY last_name, first_name";
$users_result = $conn->query($users_query);
$users = [];
if ($users_result) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get available flights
$flights_query = "SELECT f.flight_id, f.flight_number, f.airline, 
                 f.departure_city, f.arrival_city, f.departure_time, 
                 f.price, f.total_seats
                 FROM flights f
                 WHERE f.departure_time > NOW()
                 AND f.status != 'cancelled'
                 ORDER BY f.departure_time ASC";
$flights_result = $conn->query($flights_query);
$flights = [];
if ($flights_result) {
    while ($row = $flights_result->fetch_assoc()) {
        $flights[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Booking - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Create New Booking</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_bookings.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Bookings
                        </a>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Booking Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="add_booking.php" id="createBookingForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="user_id" class="form-label">Customer</label>
                                    <select class="form-select select2" id="user_id" name="user_id" required>
                                        <option value="">Select a customer</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="flight_id" class="form-label">Flight</label>
                                    <select class="form-select select2" id="flight_id" name="flight_id" required>
                                        <option value="">Select a flight</option>
                                        <?php foreach ($flights as $flight): ?>
                                            <option value="<?php echo $flight['flight_id']; ?>" data-price="<?php echo $flight['price']; ?>" data-seats="<?php echo $flight['total_seats']; ?>">
                                                <?php 
                                                    echo htmlspecialchars($flight['flight_number'] . ' - ' . 
                                                    $flight['departure_city'] . ' to ' . $flight['arrival_city'] . ' - ' .
                                                    date('M d, Y h:i A', strtotime($flight['departure_time'])) . 
                                                    ' - $' . number_format($flight['price'], 2));
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="passengers" class="form-label">Number of Passengers</label>
                                    <input type="number" class="form-control" id="passengers" name="passengers" min="1" value="1" required>
                                    <div class="form-text" id="availableSeats"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="booking_type" class="form-label">Booking Type</label>
                                    <select class="form-select" id="booking_type" name="booking_type" required>
                                        <option value="standard">Standard</option>
                                        <option value="priority">Priority (+15%)</option>
                                        <option value="group">Group (-10%)</option>
                                        <option value="vip">VIP (+25%)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="booking_status" class="form-label">Booking Status</label>
                                    <select class="form-select" id="booking_status" name="booking_status" required>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_notes" class="form-label">Admin Notes</label>
                                    <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Price Calculation</label>
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Base Price:</span>
                                                <span id="basePrice">$0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Passengers:</span>
                                                <span id="passengerCount">1</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2" id="adjustmentRow">
                                                <span id="adjustmentLabel">Adjustment:</span>
                                                <span id="adjustment">$0.00</span>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">Total Amount:</span>
                                                <span class="fw-bold" id="totalAmount">$0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back();">Cancel</button>
                                <button type="submit" name="create_booking" class="btn btn-primary">Create Booking</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        
        // Calculate price on change
        function calculatePrice() {
            const flightSelect = document.getElementById('flight_id');
            const passengers = parseInt(document.getElementById('passengers').value) || 1;
            const bookingType = document.getElementById('booking_type').value;
            
            if (flightSelect.selectedIndex > 0) {
                const option = flightSelect.options[flightSelect.selectedIndex];
                const basePrice = parseFloat(option.dataset.price) || 0;
                
                let totalAmount = basePrice * passengers;
                let adjustmentText = '';
                let adjustmentAmount = 0;
                
                // Apply discount/surcharge
                switch (bookingType) {
                    case 'priority':
                        adjustmentAmount = totalAmount * 0.15;
                        totalAmount *= 1.15;
                        adjustmentText = '15% Priority Surcharge:';
                        break;
                    case 'group':
                        adjustmentAmount = -totalAmount * 0.10;
                        totalAmount *= 0.90;
                        adjustmentText = '10% Group Discount:';
                        break;
                    case 'vip':
                        adjustmentAmount = totalAmount * 0.25;
                        totalAmount *= 1.25;
                        adjustmentText = '25% VIP Surcharge:';
                        break;
                    default:
                        adjustmentText = 'No Adjustment:';
                }
                
                // Update price display
                document.getElementById('basePrice').textContent = '$' + basePrice.toFixed(2);
                document.getElementById('passengerCount').textContent = passengers;
                document.getElementById('adjustmentLabel').textContent = adjustmentText;
                document.getElementById('adjustment').textContent = '$' + Math.abs(adjustmentAmount).toFixed(2);
                document.getElementById('totalAmount').textContent = '$' + totalAmount.toFixed(2);
                
                // Display adjustment row only if there's an adjustment
                document.getElementById('adjustmentRow').style.display = (bookingType === 'standard') ? 'none' : 'flex';
                
                // Check available seats
                const totalSeats = parseInt(option.dataset.seats) || 0;
                const availableSeatsElem = document.getElementById('availableSeats');
                
                if (totalSeats > 0) {
                    if (passengers > totalSeats) {
                        availableSeatsElem.textContent = `Warning: Only ${totalSeats} seats available on this flight`;
                        availableSeatsElem.className = 'form-text text-danger';
                    } else {
                        availableSeatsElem.textContent = `${totalSeats} seats available on this flight`;
                        availableSeatsElem.className = 'form-text text-success';
                    }
                } else {
                    availableSeatsElem.textContent = 'Seat information not available';
                    availableSeatsElem.className = 'form-text text-muted';
                }
            }
        }
        
        // Set up event listeners
        $('#flight_id, #passengers, #booking_type').on('change', calculatePrice);
        
        // Initial calculation
        calculatePrice();
    });
    </script>
</body>
</html>
