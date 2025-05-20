<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize variables
$search_flight = isset($_POST['search_flight']) ? $_POST['search_flight'] : '';
$departure_city = isset($_POST['departure_city']) ? $_POST['departure_city'] : '';
$arrival_city = isset($_POST['arrival_city']) ? $_POST['arrival_city'] : '';
$departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
$return_date = isset($_POST['return_date']) ? $_POST['return_date'] : '';
$passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
$flight_class = isset($_POST['flight_class']) ? $_POST['flight_class'] : 'economy';

// Validate and sanitize input
$departure_city = filter_var($departure_city, FILTER_SANITIZE_STRING);
$arrival_city = filter_var($arrival_city, FILTER_SANITIZE_STRING);
$departure_date = filter_var($departure_date, FILTER_SANITIZE_STRING);
$return_date = filter_var($return_date, FILTER_SANITIZE_STRING);
$passengers = filter_var($passengers, FILTER_VALIDATE_INT);
$flight_class = filter_var($flight_class, FILTER_SANITIZE_STRING);

// Redirect to search results if flight search form is submitted
if (!empty($search_flight)) {
    header("Location: search_results.php?query=" . urlencode($search_flight));
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if form is submitted for booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_flight') {
    // Get flight ID and other booking details
    $flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;
    $passenger_names = isset($_POST['passenger_names']) ? $_POST['passenger_names'] : [];
    $contact_email = isset($_POST['contact_email']) ? $_POST['contact_email'] : '';
    $contact_phone = isset($_POST['contact_phone']) ? $_POST['contact_phone'] : '';
    $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';

    // Validate flight ID
    if ($flight_id <= 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Invalid flight selected'
        ];
        header("Location: booking.php");
        exit();
    }

    // Validate passenger names
    $passenger_names = array_map('trim', $passenger_names);
    $passenger_names = array_filter($passenger_names); // Remove empty values
    $num_passengers = count($passenger_names);

    if ($num_passengers <= 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'At least one passenger name is required'
        ];
        header("Location: booking.php");
        exit();
    }

    // Calculate total price based on flight details and number of passengers
    $stmt = $conn->prepare("SELECT price, airline, flight_number, departure_city, arrival_city, departure_time, arrival_time FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $flight_result = $stmt->get_result();

    if ($flight_result->num_rows === 0) {
        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Flight not found'
        ];
        header("Location: booking.php");
        exit();
    }

    $flight = $flight_result->fetch_assoc();

    // Change this line:
    $base_fare = $flight['base_price'] * $num_passengers;

    // To this:
    $base_fare = $flight['price'] * $num_passengers;

    // Calculate total amount (you can add more complex pricing logic here)
    $total_amount = $base_fare; // + additional fees, taxes, etc.

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert booking into database
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, booking_date, total_amount, booking_status, payment_status) VALUES (?, ?, NOW(), ?, 'pending', 'pending')");
        $stmt->bind_param("iid", $user_id, $flight_id, $total_amount);
        $stmt->execute();
        $booking_id = $stmt->insert_id;

        // Insert passengers into database
        $stmt = $conn->prepare("INSERT INTO passengers (booking_id, first_name, last_name, email, phone, special_requests) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($passenger_names as $name) {
            $name_parts = explode(' ', $name, 2);
            $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

            $stmt->bind_param("isssss", $booking_id, $first_name, $last_name, $contact_email, $contact_phone, $special_requests);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['booking_status'] = [
            'type' => 'success',
            'message' => 'Booking successful! Your booking ID is BK-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT)
        ];

        // Send confirmation email (you can implement this function)
        // sendConfirmationEmail($booking_id);

        // Redirect to booking confirmation page
        header("Location: confirmation.php?id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        $_SESSION['booking_status'] = [
            'type' => 'danger',
            'message' => 'Error processing booking: ' . $e->getMessage()
        ];
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold">Book a Flight</h1>
            <p class="lead opacity-75">Fill in the details below to book your flight.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <!-- Flight Search Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Flight Search</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="booking.php" class="row g-3">
                        <div class="col-md-6">
                            <label for="departure_city" class="form-label">Departure City</label>
                            <input type="text" class="form-control" id="departure_city" name="departure_city" required>
                        </div>
                        <div class="col-md-6">
                            <label for="arrival_city" class="form-label">Arrival City</label>
                            <input type="text" class="form-control" id="arrival_city" name="arrival_city" required>
                        </div>
                        <div class="col-md-6">
                            <label for="departure_date" class="form-label">Departure Date</label>
                            <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="return_date" class="form-label">Return Date (optional)</label>
                            <input type="date" class="form-control" id="return_date" name="return_date">
                        </div>
                        <div class="col-md-6">
                            <label for="passengers" class="form-label">Passengers</label>
                            <input type="number" class="form-control" id="passengers" name="passengers" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="flight_class" class="form-label">Class</label>
                            <select class="form-select" id="flight_class" name="flight_class" required>
                                <option value="economy">Economy</option>
                                <option value="business">Business</option>
                                <option value="first">First Class</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search Flights
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Booking Form Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Booking Details</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="booking.php" class="row g-3">
                        <input type="hidden" name="action" value="book_flight">
                        <div class="col-md-6">
                            <label for="flight_id" class="form-label">Flight ID</label>
                            <input type="text" class="form-control" id="flight_id" name="flight_id" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label">Contact Phone</label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" required>
                        </div>
                        <div class="col-md-6">
                            <label for="passenger_names" class="form-label">Passenger Names</label>
                            <input type="text" class="form-control" id="passenger_names" name="passenger_names[]" placeholder="First Last" required>
                            <div class="invalid-feedback">
                                Please enter at least one passenger name.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plane-departure me-2"></i>Book Flight
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <!-- User Info Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">My Account</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mb-3 mx-auto">
                            <span class="initials"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-pencil-alt me-1"></i>Edit Profile
                        </a>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="bookings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span><i class="fas fa-ticket-alt me-2 text-primary"></i> My Bookings</span>
                            <span class="badge bg-primary rounded-pill"><?php echo $booking_count; ?></span>
                        </a>
                        <a href="../booking/check-in.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span><i class="fas fa-check-circle me-2 text-primary"></i> Web Check-In</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span><i class="fas fa-user me-2 text-primary"></i> My Profile</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="../auth/logout.php" class="list-group-item list-group-item-action px-0 py-2 border-0">
                            <span class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</span>
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

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #3b71ca;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.initials {
    font-size: 32px;
    color: white;
    font-weight: bold;
}
</style>
</body>
</html>