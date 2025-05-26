<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Check if flight_id is provided
if (!isset($_GET['flight_id']) || empty($_GET['flight_id'])) {
    header("Location: ../flights/search.php");
    exit();
}

$flight_id = intval($_GET['flight_id']);
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

// Validate passengers count
if ($passengers < 1) $passengers = 1;
if ($passengers > 9) $passengers = 9;

// Get flight details - FIXED query to include base_fare and taxes_fees calculation
$stmt = $conn->prepare("SELECT f.*, 
                       (f.price * 0.85) as base_fare,
                       (f.price * 0.15) as taxes_fees,
                       (f.total_seats - COALESCE((SELECT SUM(b.passengers) FROM bookings b 
                         WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0)) AS available_seats 
                       FROM flights f 
                       WHERE f.flight_id = ?");
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

// Check if flight is available for booking
if ($flight['status'] === 'cancelled' || $flight['status'] === 'completed') {
    $_SESSION['error_message'] = "This flight is not available for booking.";
    header("Location: ../flights/search.php");
    exit();
}

// Check if there are enough seats available
if ($flight['available_seats'] < $passengers) {
    $_SESSION['error_message'] = "Not enough seats available. Only " . $flight['available_seats'] . " seats left.";
    header("Location: ../flights/search.php");
    exit();
}

// Calculate total prices correctly
$base_fare = $flight['base_fare'];
$taxes_fees = $flight['taxes_fees'];
$price_per_passenger = $flight['price'];
$total_price = $price_per_passenger * $passengers;

// Initialize passenger data for form
$passenger_data = [];
for ($i = 0; $i < $passengers; $i++) {
    $passenger_data[] = [
        'title' => '',
        'first_name' => '',
        'last_name' => '',
        'date_of_birth' => '',
        'passport_number' => '',
        'nationality' => ''
    ];
}

// Process form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continue_booking'])) {
    // Validate passenger information
    for ($i = 0; $i < $passengers; $i++) {
        $passenger_data[$i]['title'] = $_POST['passenger_title'][$i] ?? '';
        $passenger_data[$i]['first_name'] = $_POST['passenger_first_name'][$i] ?? '';
        $passenger_data[$i]['last_name'] = $_POST['passenger_last_name'][$i] ?? '';
        $passenger_data[$i]['date_of_birth'] = $_POST['passenger_dob'][$i] ?? '';
        $passenger_data[$i]['passport_number'] = $_POST['passenger_passport'][$i] ?? '';
        $passenger_data[$i]['nationality'] = $_POST['passenger_nationality'][$i] ?? '';
        
        // Validate required fields
        if (empty($passenger_data[$i]['title'])) {
            $errors[] = "Title is required for Passenger " . ($i + 1);
        }
        if (empty($passenger_data[$i]['first_name'])) {
            $errors[] = "First name is required for Passenger " . ($i + 1);
        }
        if (empty($passenger_data[$i]['last_name'])) {
            $errors[] = "Last name is required for Passenger " . ($i + 1);
        }
    }
    
    // If no errors, proceed to checkout
    if (empty($errors)) {
        // Store flight and passenger data in session
        $_SESSION['booking_data'] = [
            'flight_id' => $flight_id,
            'passengers' => $passengers,
            'passenger_data' => $passenger_data,
            'base_fare' => $base_fare,
            'taxes_fees' => $taxes_fees,
            'price_per_passenger' => $price_per_passenger,
            'total_price' => $total_price,
            'selected_at' => date('Y-m-d H:i:s')
        ];
        
        // Redirect to review page
        header("Location: review_booking.php");
        exit();
    }
}

// Format flight times
$departure_time = date('h:i A', strtotime($flight['departure_time']));
$arrival_time = date('h:i A', strtotime($flight['arrival_time']));
$departure_date = date('l, F j, Y', strtotime($flight['departure_time']));

// Calculate flight duration
$dep = new DateTime($flight['departure_time']);
$arr = new DateTime($flight['arrival_time']);
$interval = $dep->diff($arr);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Flight - SkyWay Airlines</title>
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
                <div class="progress-step active">
                    <span>2</span>
                </div>
                <span class="progress-step-label">Passenger Info</span>
            </div>
            <div class="position-relative">
                <div class="progress-step">
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
                        <li class="breadcrumb-item active">Select Flight</li>
                    </ol>
                </nav>
                <h1 class="h3">Complete Your Booking</h1>
                <p class="text-muted">
                    Enter passenger details to continue with your booking.
                </p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h5>
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

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

                <!-- Passenger Details Form -->
                <form method="post" action="select_flight.php?flight_id=<?php echo $flight_id; ?>&passengers=<?php echo $passengers; ?>">
                    <?php for($i = 0; $i < $passengers; $i++): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Passenger <?php echo $i + 1; ?> Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label for="passenger_title_<?php echo $i; ?>" class="form-label">Title</label>
                                    <select class="form-select" id="passenger_title_<?php echo $i; ?>" name="passenger_title[]" required>
                                        <option value="" selected disabled>Select</option>
                                        <option value="Mr" <?php echo $passenger_data[$i]['title'] === 'Mr' ? 'selected' : ''; ?>>Mr</option>
                                        <option value="Mrs" <?php echo $passenger_data[$i]['title'] === 'Mrs' ? 'selected' : ''; ?>>Mrs</option>
                                        <option value="Ms" <?php echo $passenger_data[$i]['title'] === 'Ms' ? 'selected' : ''; ?>>Ms</option>
                                        <option value="Dr" <?php echo $passenger_data[$i]['title'] === 'Dr' ? 'selected' : ''; ?>>Dr</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="passenger_first_name_<?php echo $i; ?>" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="passenger_first_name_<?php echo $i; ?>" name="passenger_first_name[]" value="<?php echo htmlspecialchars($passenger_data[$i]['first_name']); ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="passenger_last_name_<?php echo $i; ?>" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="passenger_last_name_<?php echo $i; ?>" name="passenger_last_name[]" value="<?php echo htmlspecialchars($passenger_data[$i]['last_name']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="passenger_dob_<?php echo $i; ?>" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="passenger_dob_<?php echo $i; ?>" name="passenger_dob[]" value="<?php echo $passenger_data[$i]['date_of_birth']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="passenger_passport_<?php echo $i; ?>" class="form-label">Passport Number</label>
                                    <input type="text" class="form-control" id="passenger_passport_<?php echo $i; ?>" name="passenger_passport[]" value="<?php echo htmlspecialchars($passenger_data[$i]['passport_number']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="passenger_nationality_<?php echo $i; ?>" class="form-label">Nationality</label>
                                    <input type="text" class="form-control" id="passenger_nationality_<?php echo $i; ?>" name="passenger_nationality[]" value="<?php echo htmlspecialchars($passenger_data[$i]['nationality']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>

                    <div class="d-flex justify-content-between mb-4">
                        <a href="../flights/search_results.php?departure_city=<?php echo urlencode($flight['departure_city']); ?>&arrival_city=<?php echo urlencode($flight['arrival_city']); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Search Results
                        </a>
                        <button type="submit" name="continue_booking" class="btn btn-primary">
                            Continue to Review<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <!-- Price Summary -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Price Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <h3 class="text-primary mb-0">$<?php echo number_format($base_fare, 2); ?></h3>
                                <div class="text-muted small">base fare per passenger</div>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-<?php echo isset($flight['status']) ? getStatusClass($flight['status']) : 'success'; ?> mb-1">
                                    <?php echo isset($flight['status']) ? ucfirst($flight['status']) : 'Scheduled'; ?>
                                </div>
                                <div class="small">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                            </div>
                        </div>

                        <hr class="hr-dashed">

                        <div class="d-flex justify-content-between mb-2">
                            <span>Base Fare (per passenger)</span>
                            <span>$<?php echo number_format($base_fare, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxes & Fees (per passenger)</span>
                            <span>$<?php echo number_format($taxes_fees, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Price (per passenger)</span>
                            <span>$<?php echo number_format($price_per_passenger, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                        <div class="d-flex justify-content-between mb-2">ngers; ?></span>
                            <span>Passengers</span>
                            <span><?php echo $passengers; ?></span>
                        </div> class="d-flex justify-content-between fw-bold">
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">number_format($total_price, 2); ?></span>
                            <span>Total</span>
                            <span>$<?php echo number_format($total_price, 2); ?></span>
                        </div>
                    </div>
                </div>                <!-- Flight Information -->
border-0 mb-4">
                <!-- Flight Information -->>
                <div class="card shadow-sm border-0 mb-4"></h5>
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Information</h5>lass="card-body">
                    </div>
                    <div class="card-body">flex justify-content-between mb-2">
                        <div class="mb-3">>
                            <div class="d-flex justify-content-between mb-2">ars($flight['flight_number']); ?></span>
                                <span class="text-muted">Flight Number:</span>
                                <span class="fw-bold"><?php echo htmlspecialchars($flight['flight_number']); ?></span>lass="d-flex justify-content-between mb-2">
                            </div>
                            <div class="d-flex justify-content-between mb-2">['aircraft'] ?? 'Standard Aircraft'); ?></span>
                                <span class="text-muted">Aircraft:</span>
                                <span><?php echo htmlspecialchars($flight['aircraft'] ?? 'Standard Aircraft'); ?></span>lass="d-flex justify-content-between mb-2">
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Duration:</span>
                                <span><?php echo $duration; ?></span>lass="d-flex justify-content-between mb-2">
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Class:</span>
                                <span>Economy</span>
                            </div>lass="alert alert-info mb-0">
                        </div>-2"></i>
                        <div class="alert alert-info mb-0"> + 7kg carry-on
                            <i class="fas fa-info-circle me-2"></i>
                            Baggage allowance: 20kg checked baggage + 7kg carry-on
                        </div>
                    </div>
                </div>                <!-- Need Help Box -->
w-sm border-0">
                <!-- Need Help Box -->
                <div class="card shadow-sm border-0">a-headset me-2"></i>Need Help?</h5>
                    <div class="card-body">r need assistance with your booking, please contact our support team.</p>
                        <h5><i class="fas fa-headset me-2"></i>Need Help?</h5>
                        <p class="text-muted mb-3">If you have any questions or need assistance with your booking, please contact our support team.</p>fa-phone-alt me-2 text-primary"></i>
                        <div class="mb-2">
                            <i class="fas fa-phone-alt me-2 text-primary"></i>
                            <span>+63 (2) 8123 4567</span>
                        </div>i class="fas fa-envelope me-2 text-primary"></i>
                        <div>
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <span>support@skywayairlines.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flight search results -->
        <div class="container my-5">
            <!-- FAQs about booking process -->
            <div class="row mt-5">
                <div class="col-12">
                    <h3>Frequently Asked Questions</h3>
                    <div class="accordion mt-3" id="bookingFaqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="bookingHeading1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bookingCollapse1" aria-expanded="false" aria-controls="bookingCollapse1">
                                    How long are the displayed fares valid?
                                </button>
                            </h2>
                            <div id="bookingCollapse1" class="accordion-collapse collapse" aria-labelledby="bookingHeading1" data-bs-parent="#bookingFaqAccordion">
                                <div class="accordion-body">
                                    The displayed fares are only guaranteed at the time of purchase. Prices may change due to availability, taxes, or other factors until your booking is confirmed and paid for.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="bookingHeading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bookingCollapse2" aria-expanded="false" aria-controls="bookingCollapse2">
                                    What's included in the ticket price?
                                </button>
                            </h2>
                            <div id="bookingCollapse2" class="accordion-collapse collapse" aria-labelledby="bookingHeading2" data-bs-parent="#bookingFaqAccordion">
                                <div class="accordion-body">
                                    The basic ticket price includes the seat fare, all mandatory taxes, and fees. Baggage allowances vary by fare class. Additional services like seat selection, extra baggage, and meals may be available for purchase separately.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="bookingHeading3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bookingCollapse3" aria-expanded="false" aria-controls="bookingCollapse3">
                                    Can I change my flight after booking?
                                </button>
                            </h2>
                            <div id="bookingCollapse3" class="accordion-collapse collapse" aria-labelledby="bookingHeading3" data-bs-parent="#bookingFaqAccordion">
                                <div class="accordion-body">
                                    Yes, most tickets can be changed subject to fare rules and availability. Changes may incur a fee plus any fare difference. For details and to make changes, visit the "Manage Booking" section after purchasing your ticket.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="../pages/faq.php" class="btn btn-outline-primary">View Complete FAQ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>    <!-- Footer -->
../includes/footer.php'; ?>
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>    <!-- Bootstrap JS -->
cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
