<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if booking data exists in session
if (!isset($_SESSION['booking'])) {
    header("Location: ../flights/search.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$booking = $_SESSION['booking'];
$passengers_count = $booking['passengers'];
$flight_id = $booking['flight_id'];
$return_flight_id = $booking['return_flight_id'];
$error = '';
$passenger_details = isset($_SESSION['passenger_details']) ? $_SESSION['passenger_details'] : [];

// Get flight information
$stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();
$flight = $result->fetch_assoc();

// Get return flight information if applicable
$return_flight = null;
if ($return_flight_id) {
    $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $return_flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $return_flight = $result->fetch_assoc();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passenger_details = [];
    
    for ($i = 0; $i < $passengers_count; $i++) {
        if (empty($_POST['first_name'][$i]) || empty($_POST['last_name'][$i]) || empty($_POST['nationality'][$i]) || empty($_POST['dob'][$i])) {
            $error = "Please fill in all required fields for all passengers";
            break;
        }
        
        $passenger_details[] = [
            'first_name' => $_POST['first_name'][$i],
            'last_name' => $_POST['last_name'][$i],
            'nationality' => $_POST['nationality'][$i],
            'dob' => $_POST['dob'][$i],
            'passport' => isset($_POST['passport'][$i]) ? $_POST['passport'][$i] : '',
            'special_requests' => isset($_POST['special_requests'][$i]) ? $_POST['special_requests'][$i] : ''
        ];
    }
    
    if (empty($error)) {
        // Store passenger details in session
        $_SESSION['passenger_details'] = $passenger_details;
        
        // Redirect to payment page
        header("Location: payment.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Details - SkyWay Airlines</title>
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
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Passenger Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Please enter details for all passengers. Fields marked with * are required.
                        </div>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                            <?php for ($i = 0; $i < $passengers_count; $i++): ?>
                                <div class="passenger-form mb-4">
                                    <h5 class="card-title border-bottom pb-2">
                                        <?php echo $i === 0 ? 'Primary Passenger' : 'Passenger ' . ($i + 1); ?>
                                    </h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="first_name_<?php echo $i; ?>" class="form-label">First Name *</label>
                                            <input type="text" class="form-control" id="first_name_<?php echo $i; ?>" name="first_name[]" value="<?php echo $i === 0 ? htmlspecialchars($user['first_name']) : (isset($passenger_details[$i]['first_name']) ? htmlspecialchars($passenger_details[$i]['first_name']) : ''); ?>" required>
                                            <div class="invalid-feedback">Please enter first name</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name_<?php echo $i; ?>" class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" id="last_name_<?php echo $i; ?>" name="last_name[]" value="<?php echo $i === 0 ? htmlspecialchars($user['last_name']) : (isset($passenger_details[$i]['last_name']) ? htmlspecialchars($passenger_details[$i]['last_name']) : ''); ?>" required>
                                            <div class="invalid-feedback">Please enter last name</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nationality_<?php echo $i; ?>" class="form-label">Nationality *</label>
                                            <select class="form-select" id="nationality_<?php echo $i; ?>" name="nationality[]" required>
                                                <option value="" selected disabled>Select nationality</option>
                                                <option value="Philippines" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'Philippines' ? 'selected' : ''; ?>>Philippines</option>
                                                <option value="United States" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'United States' ? 'selected' : ''; ?>>United States</option>
                                                <option value="Japan" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'Japan' ? 'selected' : ''; ?>>Japan</option>
                                                <option value="South Korea" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'South Korea' ? 'selected' : ''; ?>>South Korea</option>
                                                <option value="Singapore" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'Singapore' ? 'selected' : ''; ?>>Singapore</option>
                                                <option value="United Arab Emirates" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'United Arab Emirates' ? 'selected' : ''; ?>>United Arab Emirates</option>
                                                <option value="China" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'China' ? 'selected' : ''; ?>>China</option>
                                                <option value="Other" <?php echo isset($passenger_details[$i]['nationality']) && $passenger_details[$i]['nationality'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <div class="invalid-feedback">Please select nationality</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="dob_<?php echo $i; ?>" class="form-label">Date of Birth *</label>
                                            <input type="date" class="form-control" id="dob_<?php echo $i; ?>" name="dob[]" value="<?php echo isset($passenger_details[$i]['dob']) ? htmlspecialchars($passenger_details[$i]['dob']) : ''; ?>" required>
                                            <div class="invalid-feedback">Please enter date of birth</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="passport_<?php echo $i; ?>" class="form-label">Passport Number</label>
                                            <input type="text" class="form-control" id="passport_<?php echo $i; ?>" name="passport[]" value="<?php echo isset($passenger_details[$i]['passport']) ? htmlspecialchars($passenger_details[$i]['passport']) : ''; ?>">
                                            <small class="text-muted">Required for international flights</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="special_requests_<?php echo $i; ?>" class="form-label">Special Requests</label>
                                            <select class="form-select" id="special_requests_<?php echo $i; ?>" name="special_requests[]">
                                                <option value="" selected>None</option>
                                                <option value="Wheelchair" <?php echo isset($passenger_details[$i]['special_requests']) && $passenger_details[$i]['special_requests'] === 'Wheelchair' ? 'selected' : ''; ?>>Wheelchair</option>
                                                <option value="Vegetarian Meal" <?php echo isset($passenger_details[$i]['special_requests']) && $passenger_details[$i]['special_requests'] === 'Vegetarian Meal' ? 'selected' : ''; ?>>Vegetarian Meal</option>
                                                <option value="Special Assistance" <?php echo isset($passenger_details[$i]['special_requests']) && $passenger_details[$i]['special_requests'] === 'Special Assistance' ? 'selected' : ''; ?>>Special Assistance</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="select.php?flight_id=<?php echo $flight_id; ?>&passengers=<?php echo $passengers_count; ?>&class=<?php echo $booking['class']; ?>&trip_type=<?php echo $return_flight_id ? 'round_trip' : 'one_way'; ?><?php echo $return_flight_id ? '&return_flight_id=' . $return_flight_id : ''; ?>" class="btn btn-outline-secondary">Back to Seat Selection</a>
                                <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Outbound Flight</h6>
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                <span class="text-muted"><?php echo date('d M Y', strtotime($flight['departure_time'])); ?></span>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?>
                            </div>
                            <div class="text-muted">
                                <small><?php echo date('H:i', strtotime($flight['departure_time'])); ?> - <?php echo date('H:i', strtotime($flight['arrival_time'])); ?></small>
                            </div>
                        </div>
                        
                        <?php if ($return_flight): ?>
                            <div class="mb-3">
                                <h6>Return Flight</h6>
                                <div class="d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($return_flight['flight_number']); ?></span>
                                    <span class="text-muted"><?php echo date('d M Y', strtotime($return_flight['departure_time'])); ?></span>
                                </div>
                                <div>
                                    <?php echo htmlspecialchars($return_flight['departure_city']); ?> → <?php echo htmlspecialchars($return_flight['arrival_city']); ?>
                                </div>
                                <div class="text-muted">
                                    <small><?php echo date('H:i', strtotime($return_flight['departure_time'])); ?> - <?php echo date('H:i', strtotime($return_flight['arrival_time'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <h6>Selected Seats</h6>
                            <div>
                                <strong>Outbound:</strong> <?php echo implode(', ', $booking['seats']); ?>
                            </div>
                            <?php if ($return_flight && !empty($booking['return_seats'])): ?>
                                <div>
                                    <strong>Return:</strong> <?php echo implode(', ', $booking['return_seats']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Fare Details</h6>
                            <div class="d-flex justify-content-between">
                                <span>Passengers</span>
                                <span><?php echo $passengers_count; ?> x <?php echo ucfirst($booking['class']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Base Fare</span>
                                <span>$<?php echo number_format($booking['total_fare'] * 0.8, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Taxes & Fees</span>
                                <span>$<?php echo number_format($booking['total_fare'] * 0.2, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold border-top pt-3 mt-3">
                            <span>Total Amount</span>
                            <span class="text-primary">$<?php echo number_format($booking['total_fare'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle text-primary me-2"></i>Important Information</h6>
                        <ul class="small mb-0">
                            <li>Please ensure all passenger details match official travel documents.</li>
                            <li>A valid ID will be required at check-in.</li>
                            <li>For international flights, passengers must have a valid passport.</li>
                            <li>Check-in opens 24 hours before departure and closes 1 hour before departure.</li>
                        </ul>
                    </div>
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
        
        // Set max date for DOB to today
        const dobInputs = document.querySelectorAll('input[name="dob[]"]');
        const today = new Date().toISOString().split('T')[0];
        
        dobInputs.forEach(input => {
            input.setAttribute('max', today);
        });
    </script>
</body>
</html>
