<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if booking and passenger details exist in session
if (!isset($_SESSION['booking']) || !isset($_SESSION['passenger_details'])) {
    header("Location: ../flights/search.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$booking = $_SESSION['booking'];
$passenger_details = $_SESSION['passenger_details'];
$passengers_count = $booking['passengers'];
$flight_id = $booking['flight_id'];
$return_flight_id = $booking['return_flight_id'];
$total_fare = $booking['total_fare'];
$error = '';
$success = '';

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

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];
    $booking_status = 'confirmed';
    $payment_status = 'completed';
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, booking_date, passengers, seat_numbers, booking_status, payment_status, total_amount) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
        $seat_numbers = implode(',', $booking['seats']);
        $stmt->bind_param("iiisssd", $user_id, $flight_id, $passengers_count, $seat_numbers, $booking_status, $payment_status, $total_fare);
        $stmt->execute();
        $booking_id = $conn->insert_id;
        
        // Insert payment
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, payment_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("idsss", $booking_id, $total_fare, $payment_method, $payment_status, $transaction_id);
        $stmt->execute();
        
        // Insert tickets for each passenger
        foreach ($passenger_details as $index => $passenger) {
            $ticket_number = 'SKYW' . time() . rand(1000, 9999) . chr(65 + $index);
            $passenger_name = $passenger['first_name'] . ' ' . $passenger['last_name'];
            $seat_number = $booking['seats'][$index];
            $qr_code = 'QR' . $ticket_number; // In a real app, generate actual QR code
            
            $stmt = $conn->prepare("INSERT INTO tickets (booking_id, ticket_number, passenger_name, seat_number, qr_code, status, issued_date) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->bind_param("issss", $booking_id, $ticket_number, $passenger_name, $seat_number, $qr_code);
            $stmt->execute();
        }
        
        // Update available seats in flights table
        $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?");
        $stmt->bind_param("ii", $passengers_count, $flight_id);
        $stmt->execute();
        
        // Handle return flight if applicable
        if ($return_flight_id) {
            // Update available seats for return flight
            $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?");
            $stmt->bind_param("ii", $passengers_count, $return_flight_id);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Store booking ID in session for confirmation page
        $_SESSION['completed_booking_id'] = $booking_id;
        
        // Clear booking session data
        unset($_SESSION['booking']);
        unset($_SESSION['passenger_details']);
        
        // Redirect to confirmation page
        header("Location: confirmation.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Payment failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - SkyWay Airlines</title>
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
                        <h4 class="mb-0">Payment Details</h4>
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
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="payment-form" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <h5>Select Payment Method</h5>
                                <div class="row g-3 mb-3">
                                    <div class="col-6 col-md-3">
                                        <div class="form-check payment-method-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                            <label class="form-check-label payment-method-label" for="credit_card">
                                                <div class="text-center">
                                                    <i class="fab fa-cc-visa fa-2x text-primary"></i>
                                                    <div class="mt-2">Credit Card</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="form-check payment-method-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                                            <label class="form-check-label payment-method-label" for="debit_card">
                                                <div class="text-center">
                                                    <i class="fab fa-cc-mastercard fa-2x text-primary"></i>
                                                    <div class="mt-2">Debit Card</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="form-check payment-method-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="gcash">
                                            <label class="form-check-label payment-method-label" for="gcash">
                                                <div class="text-center">
                                                    <i class="fas fa-wallet fa-2x text-primary"></i>
                                                    <div class="mt-2">GCash</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="form-check payment-method-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="maya" value="maya">
                                            <label class="form-check-label payment-method-label" for="maya">
                                                <div class="text-center">
                                                    <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                                    <div class="mt-2">Maya</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Credit Card Payment Form (default) -->
                            <div id="credit-card-form" class="payment-form-container">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456" required>
                                            <span class="input-group-text">
                                                <i class="fab fa-cc-visa me-1"></i>
                                                <i class="fab fa-cc-mastercard me-1"></i>
                                                <i class="fab fa-cc-amex"></i>
                                            </span>
                                            <div class="invalid-feedback">Please enter a valid card number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiry_date" placeholder="MM/YY" required>
                                        <div class="invalid-feedback">Please enter expiry date</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" placeholder="123" required>
                                        <div class="invalid-feedback">Please enter CVV</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="card_holder" class="form-label">Card Holder Name</label>
                                        <input type="text" class="form-control" id="card_holder" placeholder="John Doe" required>
                                        <div class="invalid-feedback">Please enter card holder name</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- GCash Payment Form -->
                            <div id="gcash-form" class="payment-form-container d-none">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="gcash_number" class="form-label">GCash Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="text" class="form-control" id="gcash_number" placeholder="9123456789">
                                            <div class="invalid-feedback">Please enter a valid GCash number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> You will receive an OTP on your GCash app to complete the payment.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Maya Payment Form -->
                            <div id="maya-form" class="payment-form-container d-none">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="maya_number" class="form-label">Maya Account Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="text" class="form-control" id="maya_number" placeholder="9123456789">
                                            <div class="invalid-feedback">Please enter a valid Maya account number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> You will receive an OTP on your Maya app to complete the payment.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Debit Card Payment Form -->
                            <div id="debit-card-form" class="payment-form-container d-none">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="debit_card_number" class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="debit_card_number" placeholder="1234 5678 9012 3456">
                                            <span class="input-group-text">
                                                <i class="fab fa-cc-visa me-1"></i>
                                                <i class="fab fa-cc-mastercard"></i>
                                            </span>
                                            <div class="invalid-feedback">Please enter a valid card number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="debit_expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="debit_expiry_date" placeholder="MM/YY">
                                        <div class="invalid-feedback">Please enter expiry date</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="debit_cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="debit_cvv" placeholder="123">
                                        <div class="invalid-feedback">Please enter CVV</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="debit_card_holder" class="form-label">Card Holder Name</label>
                                        <input type="text" class="form-control" id="debit_card_holder" placeholder="John Doe">
                                        <div class="invalid-feedback">Please enter card holder name</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="terms_agree" required>
                                <label class="form-check-label" for="terms_agree">
                                    I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to the terms and conditions
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="passenger_details.php" class="btn btn-outline-secondary">Back to Passenger Details</a>
                                <button type="submit" class="btn btn-primary">Complete Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-shield-alt text-primary me-2"></i>Secure Payment</h5>
                        <p class="card-text">Your payment information is securely processed with industry-standard encryption. We do not store your complete card details.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fab fa-cc-visa fa-2x me-2 text-muted"></i>
                                <i class="fab fa-cc-mastercard fa-2x me-2 text-muted"></i>
                                <i class="fab fa-cc-amex fa-2x me-2 text-muted"></i>
                                <i class="fab fa-cc-paypal fa-2x text-muted"></i>
                            </div>
                            <div>
                                <i class="fas fa-lock fa-2x text-success"></i>
                            </div>
                        </div>
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
                            <h6>Passengers</h6>
                            <?php foreach ($passenger_details as $index => $passenger): ?>
                                <div><?php echo $index + 1; ?>. <?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></div>
                            <?php endforeach; ?>
                        </div>
                        
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
                                <span>$<?php echo number_format($total_fare * 0.8, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Taxes & Fees</span>
                                <span>$<?php echo number_format($total_fare * 0.2, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold border-top pt-3 mt-3">
                            <span>Total Amount</span>
                            <span class="text-primary">$<?php echo number_format($total_fare, 2); ?></span>
                        </div>
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
        })();
        
        // Toggle payment form based on selected payment method
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardForm = document.getElementById('credit-card-form');
            const debitCardForm = document.getElementById('debit-card-form');
            const gcashForm = document.getElementById('gcash-form');
            const mayaForm = document.getElementById('maya-form');
            
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    // Hide all forms
                    creditCardForm.classList.add('d-none');
                    debitCardForm.classList.add('d-none');
                    gcashForm.classList.add('d-none');
                    mayaForm.classList.add('d-none');
                    
                    // Show selected form
                    switch(this.value) {
                        case 'credit_card':
                            creditCardForm.classList.remove('d-none');
                            break;
                        case 'debit_card':
                            debitCardForm.classList.remove('d-none');
                            break;
                        case 'gcash':
                            gcashForm.classList.remove('d-none');
                            break;
                        case 'maya':
                            mayaForm.classList.remove('d-none');
                            break;
                    }
                });
            });
            
            // Format credit card number input
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 16) value = value.slice(0, 16);
                    
                    // Add spaces every 4 digits
                    let formattedValue = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    
                    e.target.value = formattedValue;
                });
            }
            
            // Format expiry date input
            const expiryDateInput = document.getElementById('expiry_date');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    
                    if (value.length > 2) {
                        value = value.slice(0, 2) + '/' + value.slice(2);
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Format CVV input
            const cvvInput = document.getElementById('cvv');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 3) value = value.slice(0, 3);
                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html>
