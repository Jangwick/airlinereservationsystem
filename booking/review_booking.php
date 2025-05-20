<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? $_SESSION['user_id'] : 0;

// Check if booking data exists in session
if (!isset($_SESSION['booking_data']) || empty($_SESSION['booking_data'])) {
    header("Location: ../flights/search.php");
    exit();
}

// Get booking data from session
$booking_data = $_SESSION['booking_data'];
$flight_id = $booking_data['flight_id'];
$passengers = $booking_data['passengers'];
$passenger_data = $booking_data['passenger_data'];
$base_fare = $booking_data['base_fare'];
$taxes_fees = $booking_data['taxes_fees'];
$price_per_passenger = $booking_data['price_per_passenger'];
$total_price = $booking_data['total_price'];

// Get flight details
$stmt = $conn->prepare("SELECT f.* FROM flights f WHERE f.flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Flight not found
    $_SESSION['error_message'] = "The selected flight was not found.";
    header("Location: ../flights/search.php");
    exit();
}

$flight = $result->fetch_assoc();

// Format flight times
$departure_time = date('h:i A', strtotime($flight['departure_time']));
$arrival_time = date('h:i A', strtotime($flight['arrival_time']));
$departure_date = date('l, F j, Y', strtotime($flight['departure_time']));

// Calculate flight duration
$dep = new DateTime($flight['departure_time']);
$arr = new DateTime($flight['arrival_time']);
$interval = $dep->diff($arr);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    // If user is not logged in, require login/registration
    if (!$logged_in) {
        // Store a flag that we're in the booking process
        $_SESSION['booking_in_progress'] = true;
        header("Location: ../auth/login.php?redirect=booking/review_booking.php");
        exit();
    }
    
    // Otherwise, proceed to payment
    header("Location: payment.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Booking - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .flight-path {
            position: relative;
            padding: 0 15px;
            margin: 15px 0;
        }
        
        .flight-path-line {
            position: absolute;
            top: 50%;
            left: 15px;
            right: 15px;
            height: 2px;
            background-color: #ddd;
            transform: translateY(-50%);
            z-index: 1;
        }
        
        .flight-path i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 5px;
            border-radius: 50%;
            color: #3b71ca;
            z-index: 2;
        }
        
        .booking-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .booking-progress::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress-step {
            position: relative;
            z-index: 2;
            background: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #3b71ca;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #3b71ca;
        }
        
        .progress-step.active {
            background: #3b71ca;
            color: white;
        }
        
        .progress-step.completed {
            background: #3b71ca;
            color: white;
        }
        
        .progress-step-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
        }
        
        .progress-step.active .progress-step-label {
            color: #3b71ca;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <!-- Booking Progress -->
        <div class="booking-progress mb-5">
            <div class="position-relative">
                <div class="progress-step completed">
                    <i class="fas fa-check"></i>
                </div>
                <span class="progress-step-label">Select Flight</span>
            </div>
            <div class="position-relative">
                <div class="progress-step completed">
                    <i class="fas fa-check"></i>
                </div>
                <span class="progress-step-label">Passenger Info</span>
            </div>
            <div class="position-relative">
                <div class="progress-step active">
                    <span>3</span>
                </div>
                <span class="progress-step-label">Review</span>
            </div>
            <div class="position-relative">
                <div class="progress-step">
                    <span>4</span>
                </div>
                <span class="progress-step-label">Payment</span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../flights/search.php">Search Flights</a></li>
                        <li class="breadcrumb-item"><a href="javascript:history.back()">Select Flight</a></li>
                        <li class="breadcrumb-item active">Review Booking</li>
                    </ol>
                </nav>
                <h1 class="h3">Review Your Booking</h1>
                <p class="text-muted">
                    Please review your booking details before proceeding to payment.
                </p>
            </div>
        </div>

        <form method="post" action="review_booking.php">
            <div class="row">
                <div class="col-md-8">
                    <!-- Flight Details Summary -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Flight Details</h5>
                        </div>
                        <div class="card-body">
                            <!-- Airline Info -->
                            <div class="d-flex align-items-center mb-4">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($flight['airline']); ?>&background=0D6EFD&color=fff&size=64&bold=true&format=svg" 
                                    alt="<?php echo htmlspecialchars($flight['airline']); ?> Logo" 
                                    width="50" height="50" class="me-3">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($flight['airline']); ?></h5>
                                    <div class="text-muted">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Flight Path -->
                            <div class="row">
                                <div class="col-md-5">
                                    <h6><?php echo htmlspecialchars($flight['departure_city']); ?></h6>
                                    <h5 class="fw-bold mb-0"><?php echo $departure_time; ?></h5>
                                    <div class="text-muted"><?php echo $departure_date; ?></div>
                                </div>
                                <div class="col-md-2 text-center py-3">
                                    <div class="flight-path position-relative">
                                        <div class="flight-path-line"></div>
                                        <i class="fas fa-plane"></i>
                                    </div>
                                    <div class="small mt-2"><?php echo $duration; ?></div>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <h6><?php echo htmlspecialchars($flight['arrival_city']); ?></h6>
                                    <h5 class="fw-bold mb-0"><?php echo $arrival_time; ?></h5>
                                    <div class="text-muted"><?php echo date('l, F j, Y', strtotime($flight['arrival_time'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passenger Details -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Passenger Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Date of Birth</th>
                                            <th scope="col">Passport</th>
                                            <th scope="col">Nationality</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($passenger_data as $index => $passenger): ?>
                                            <tr>
                                                <th scope="row"><?php echo $index + 1; ?></th>
                                                <td><?php echo htmlspecialchars($passenger['title'] . ' ' . $passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                                <td><?php echo !empty($passenger['date_of_birth']) ? date('M d, Y', strtotime($passenger['date_of_birth'])) : '-'; ?></td>
                                                <td><?php echo !empty($passenger['passport_number']) ? htmlspecialchars($passenger['passport_number']) : '-'; ?></td>
                                                <td><?php echo !empty($passenger['nationality']) ? htmlspecialchars($passenger['nationality']) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Login Alert for Non-logged in Users -->
                    <?php if (!$logged_in): ?>
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle me-2"></i>Almost there!</h5>
                        <p class="mb-2">You will need to log in or create an account to complete your booking.</p>
                        <p class="mb-0">We'll save your booking information so you can pick up right where you left off.</p>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-4">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Edit Passenger Details
                        </a>
                        <button type="submit" name="confirm_booking" class="btn btn-primary">
                            <?php echo $logged_in ? 'Proceed to Payment' : 'Continue to Login'; ?>
                            <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Price Summary -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Price Summary</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td>Base Fare (per passenger)</td>
                                        <td class="text-end">$<?php echo number_format($base_fare, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Taxes & Fees (per passenger)</td>
                                        <td class="text-end">$<?php echo number_format($taxes_fees, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Price per passenger</td>
                                        <td class="text-end">$<?php echo number_format($price_per_passenger, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Passengers</td>
                                        <td class="text-end"><?php echo $passengers; ?></td>
                                    </tr>
                                    <tr class="border-top">
                                        <th>Total price</th>
                                        <th class="text-end">$<?php echo number_format($total_price, 2); ?></th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Booking Summary -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Booking Summary</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Flight Number:</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Date:</span>
                                    <span><?php echo date('M d, Y', strtotime($flight['departure_time'])); ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Departure Time:</span>
                                    <span><?php echo $departure_time; ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Arrival Time:</span>
                                    <span><?php echo $arrival_time; ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Passengers:</span>
                                    <span><?php echo $passengers; ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-0">
                                    <span class="text-muted">Class:</span>
                                    <span>Economy</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Booking Policies -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Booking Policies</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6><i class="fas fa-exchange-alt me-2 text-primary"></i>Changes & Cancellations</h6>
                                <p class="small text-muted mb-0">Changes permitted for a fee. Cancellation fee applies.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-suitcase me-2 text-primary"></i>Baggage</h6>
                                <p class="small text-muted mb-0">20kg checked baggage + 7kg carry-on included per passenger.</p>
                            </div>
                            <div>
                                <h6><i class="fas fa-info-circle me-2 text-primary"></i>Important Information</h6>
                                <p class="small text-muted mb-0">By proceeding with payment, you agree to our <a href="../pages/terms.php">terms and conditions</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
