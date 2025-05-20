<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store current page as redirect target after login
    $_SESSION['redirect_after_login'] = 'booking/payment.php';
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

$user_id = $_SESSION['user_id'];

// Initialize flight and booking variables with defaults to prevent undefined variable errors
$booking = [
    'booking_id' => 0,
    'flight_id' => 0,
    'flight_number' => 'N/A',
    'airline' => 'N/A',
    'departure_city' => 'N/A',
    'arrival_city' => 'N/A',
    'departure_time' => date('Y-m-d H:i:s'),
    'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'price' => 0,
    'total_amount' => 0
];
$passenger_count = 1;
$flight_id = 0;
$passengers = 0;
$total_price = 0;
$booking_id = 0;

// Check if booking data exists in session
if (isset($_SESSION['booking_data'])) {
    // Extract necessary data from session
    $booking_data = $_SESSION['booking_data'];
    $flight_id = $booking_data['flight_id'];
    $passengers = $booking_data['passengers'];
    $passenger_count = $passengers; // Set passenger_count from session data
    $total_price = $booking_data['total_price'];
    $booking_id = $booking_data['booking_id'] ?? 0; // Will be 0 for new bookings
    
    // Get flight details to populate the booking array
    $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $flight = $result->fetch_assoc();
        
        // Populate booking array with flight details
        $booking['flight_id'] = $flight_id;
        $booking['flight_number'] = $flight['flight_number'];
        $booking['airline'] = $flight['airline'];
        $booking['departure_city'] = $flight['departure_city'];
        $booking['arrival_city'] = $flight['arrival_city'];
        $booking['departure_time'] = $flight['departure_time'];
        $booking['arrival_time'] = $flight['arrival_time'];
        $booking['price'] = $flight['price'];
        $booking['total_amount'] = $total_price;
    }
} 
// Check if we have a booking_id parameter (for completing payment later)
else if (isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    // Get booking details directly from database when booking_id is provided
    if ($booking_id > 0) {
        $stmt = $conn->prepare("SELECT b.*, 
                              f.price, 
                              (f.price * 0.85) as base_fare,
                              (f.price * 0.15) as taxes_fees,
                              f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                              f.departure_time, f.arrival_time 
                              FROM bookings b 
                              JOIN flights f ON b.flight_id = f.flight_id 
                              WHERE b.booking_id = ? AND b.user_id = ?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            header("Location: ../user/bookings.php");
            exit();
        }
        
        $booking = $result->fetch_assoc();
        
        // Calculate payment details
        $passengers = $booking['passengers'];
        $price_per_passenger = $booking['price'];
        $total_price = $booking['total_amount'];
        $base_fare = $booking['base_fare'];
        $taxes_fees = $booking['taxes_fees'];
    } 
    // For new bookings from session
    else if (isset($_SESSION['booking_data'])) {
        $booking_data = $_SESSION['booking_data'];
        
        // Get flight details
        $flight_id = $booking_data['flight_id'];
        $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            header("Location: ../flights/search.php");
            exit();
        }
        
        $flight = $result->fetch_assoc();
        
        // Set booking variables using session data
        $passengers = $booking_data['passengers'];
        $base_fare = $booking_data['base_fare'];
        $taxes_fees = $booking_data['taxes_fees'];
        $price_per_passenger = $booking_data['price_per_passenger'];
        $total_price = $booking_data['total_price'];
    }
} else {
    // No booking data and no booking_id parameter
    $_SESSION['error_message'] = "No booking information found.";
    header("Location: ../flights/search.php");
    exit();
}

// Track payment errors
$payment_error = '';

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get payment details from form
    $card_name = $_POST['card_name'] ?? '';
    $card_number = $_POST['card_number'] ?? '';
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'credit_card';
    
    // Basic validation
    if (empty($card_name) || empty($card_number) || empty($card_expiry) || empty($card_cvv)) {
        $payment_error = "All payment fields are required.";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Check if payment_method column exists in bookings table
            $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
            $payment_method_exists = $column_check->num_rows > 0;
            
            // Check if updated_at column exists in bookings table
            $updated_at_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'updated_at'");
            $updated_at_exists = $updated_at_check->num_rows > 0;
            
            // Check if this is a payment for an existing booking
            if (isset($booking_id) && $booking_id > 0) {
                // Update existing booking
                if ($payment_method_exists && $updated_at_exists) {
                    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'completed', 
                                      booking_status = 'confirmed', 
                                      payment_method = ?,
                                      updated_at = NOW()
                                      WHERE booking_id = ? AND user_id = ?");
                    $stmt->bind_param("sii", $payment_method, $booking_id, $user_id);
                } elseif ($payment_method_exists) {
                    // If payment_method exists but updated_at doesn't
                    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'completed', 
                                      booking_status = 'confirmed', 
                                      payment_method = ?
                                      WHERE booking_id = ? AND user_id = ?");
                    $stmt->bind_param("sii", $payment_method, $booking_id, $user_id);
                } elseif ($updated_at_exists) {
                    // If updated_at exists but payment_method doesn't
                    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'completed', 
                                      booking_status = 'confirmed',
                                      updated_at = NOW() 
                                      WHERE booking_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $booking_id, $user_id);
                } else {
                    // If neither column exists
                    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'completed', 
                                      booking_status = 'confirmed' 
                                      WHERE booking_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $booking_id, $user_id);
                }
                
                $result = $stmt->execute();
                
                if (!$result) {
                    throw new Exception("Failed to update booking status: " . $conn->error);
                }
                
                // Get the existing booking ID for the confirmation
                $_SESSION['recent_booking_id'] = $booking_id;
                
            } else {
                // Insert new booking record - no need to use updated_at here since it's a new record
                $booking_date = date('Y-m-d H:i:s');
                $booking_status = 'confirmed';
                $payment_status = 'completed';
                
                if ($payment_method_exists) {
                    $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, booking_date, passengers, 
                                      total_amount, booking_status, payment_status, payment_method) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisidsss", $user_id, $flight_id, $booking_date, $passengers, 
                                  $total_price, $booking_status, $payment_status, $payment_method);
                } else {
                    $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, booking_date, passengers, 
                                      total_amount, booking_status, payment_status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisidss", $user_id, $flight_id, $booking_date, $passengers, 
                                  $total_price, $booking_status, $payment_status);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create booking: " . $stmt->error);
                }
                
                $booking_id = $conn->insert_id;
                
                // Store passenger details if available
                if (isset($_SESSION['booking_data']['passenger_data'])) {
                    $passenger_data = $_SESSION['booking_data']['passenger_data'];
                    
                    // Check if passengers table exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'passengers'");
                    
                    if ($table_check->num_rows > 0) {
                        // Check if nationality column exists in passengers table
                        $nationality_exists = $conn->query("SHOW COLUMNS FROM passengers LIKE 'nationality'")->num_rows > 0;
                        
                        foreach ($passenger_data as $passenger) {
                            $title = $passenger['title'] ?? '';
                            $first_name = $passenger['first_name'] ?? '';
                            $last_name = $passenger['last_name'] ?? '';
                            $dob = $passenger['date_of_birth'] ?? null;
                            $passport = $passenger['passport_number'] ?? '';
                            
                            if ($nationality_exists) {
                                // If nationality column exists, include it in the query
                                $nationality = $passenger['nationality'] ?? '';
                                $stmt = $conn->prepare("INSERT INTO passengers 
                                                     (booking_id, title, first_name, last_name, date_of_birth, passport_number, nationality) 
                                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssss", $booking_id, $title, $first_name, $last_name, $dob, $passport, $nationality);
                            } else {
                                // If nationality column doesn't exist, exclude it
                                $stmt = $conn->prepare("INSERT INTO passengers 
                                                     (booking_id, title, first_name, last_name, date_of_birth, passport_number) 
                                                     VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssss", $booking_id, $title, $first_name, $last_name, $dob, $passport);
                            }
                            $stmt->execute();
                        }
                    }
                }
                
                // Store the booking ID for the confirmation page
                $_SESSION['recent_booking_id'] = $booking_id;
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            $_SESSION['booking_success'] = true;
            $_SESSION['recent_booking_id'] = $booking_id;
            
            // Clear booking data from session
            unset($_SESSION['booking_data']);
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $booking_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Log the error
            error_log("Payment processing error: " . $e->getMessage());
            
            // Show error to user
            $payment_error = "Error processing payment: " . $e->getMessage();
        }
    }
}

// Get flight details from the database - make sure to include the price calculations
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

if ($result->num_rows > 0) {
    $flight = $result->fetch_assoc();
    
    // After getting the booking data, properly initialize the price variables
    if ($booking) {
        $base_fare = $booking['base_fare'] ?? ($booking['price'] * 0.85);
        $taxes_fees = $booking['taxes_fees'] ?? ($booking['price'] * 0.15);
        $price_per_passenger = $booking['price'];
        $total_amount = $booking['total_amount'];
        
        // If these values are still not set (perhaps booking doesn't have price info)
        if (!isset($base_fare) || !$base_fare) {
            $flight_price = $flight['price'] ?? 0;
            $base_fare = $flight_price * 0.85;
            $taxes_fees = $flight_price * 0.15;
            $price_per_passenger = $flight_price;
        }
    }
}

// Format flight times
$departure_time = date('h:i A', strtotime($booking['departure_time']));
$arrival_time = date('h:i A', strtotime($booking['arrival_time']));
$departure_date = date('l, F j, Y', strtotime($booking['departure_time']));
$arrival_date = date('l, F j, Y', strtotime($booking['arrival_time']));

// Calculate flight duration
$dep = new DateTime($booking['departure_time']);
$arr = new DateTime($booking['arrival_time']);
$interval = $dep->diff($arr);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);
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
    <style>
        .card-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .payment-method-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .payment-method-card.selected {
            border-color: #3b71ca;
            background-color: rgba(59, 113, 202, 0.05);
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
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../user/bookings.php">My Bookings</a></li>
                        <li class="breadcrumb-item active">Payment</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($payment_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $payment_error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" id="payment-form">
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="form-check payment-method-card p-3 text-center h-100" id="card-option">
                                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" value="credit_card" id="payment_credit_card" checked>
                                        <label class="form-check-label w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="payment_credit_card">
                                            <i class="fas fa-credit-card fa-2x mb-2 text-primary"></i>
                                            <span>Credit Card</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="form-check payment-method-card p-3 text-center h-100" id="paypal-option">
                                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" value="paypal" id="payment_paypal">
                                        <label class="form-check-label w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="payment_paypal">
                                            <i class="fab fa-paypal fa-2x mb-2 text-primary"></i>
                                            <span>PayPal</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="form-check payment-method-card p-3 text-center h-100" id="bank-option">
                                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" value="bank_transfer" id="payment_bank_transfer">
                                        <label class="form-check-label w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="payment_bank_transfer">
                                            <i class="fas fa-university fa-2x mb-2 text-primary"></i>
                                            <span>Bank Transfer</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <div class="form-check payment-method-card p-3 text-center h-100" id="wallet-option">
                                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" value="wallet" id="payment_wallet">
                                        <label class="form-check-label w-100 h-100 d-flex flex-column align-items-center justify-content-center" for="payment_wallet">
                                            <i class="fas fa-wallet fa-2x mb-2 text-primary"></i>
                                            <span>Digital Wallet</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="credit-card-form">
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control card-input" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                        <span class="input-group-text">
                                            <i class="fab fa-cc-visa me-1"></i>
                                            <i class="fab fa-cc-mastercard me-1"></i>
                                            <i class="fab fa-cc-amex"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="card_name" class="form-label">Cardholder Name</label>
                                    <input type="text" class="form-control" id="card_name" name="card_name" placeholder="Name on card">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiry_date" name="card_expiry" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="cvv" name="card_cvv" placeholder="123" maxlength="4">
                                            <span class="input-group-text">
                                                <i class="fas fa-question-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="3 or 4 digit security code on the back of your card"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="save_card" name="save_card" value="1">
                                    <label class="form-check-label" for="save_card">
                                        Save card for future payments
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info mt-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle fa-2x text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6>Test Payment Mode</h6>
                                        <p class="mb-0">This is a test payment environment. No real transactions will be processed.</p>
                                        <p class="mb-0">For testing, use any card number that follows the format, any future expiry date, and any 3 or 4 digit CVV.</p>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg mt-4">Pay $<?php echo number_format($booking['total_amount'], 2); ?></button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Secure Payment</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-lock text-success me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">Secure Payment</h6>
                                <p class="mb-0 text-muted">Your payment information is encrypted using secure SSL technology</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt text-success me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">Payment Protection</h6>
                                <p class="mb-0 text-muted">Your payment details are protected and never stored on our servers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 sticky-top" style="top: 80px;">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="booking-ref mb-3 pb-3 border-bottom">
                            <div class="small text-muted">Booking Reference</div>
                            <div class="h6">BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        
                        <div class="flight-details mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-plane-departure text-primary fa-lg"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="small text-muted">Flight</div>
                                    <div class="h6 mb-0"><?php echo htmlspecialchars($booking['airline'] . ' ' . $booking['flight_number']); ?></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <div class="h6 mb-0"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                    <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <div class="text-end">
                                    <div class="h6 mb-0"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                    <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['arrival_time'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="small text-muted">
                                <?php echo date('l, F j, Y', strtotime($booking['departure_time'])); ?>
                            </div>
                        </div>
                        
                        <div class="passenger-details mb-3 pb-3 border-bottom">
                            <div class="small text-muted">Passengers</div>
                            <div class="h6 mb-0"><?php echo $passenger_count; ?> passenger<?php echo $passenger_count > 1 ? 's' : ''; ?></div>
                        </div>
                        
                        <div class="price-breakdown">
                            <div class="d-flex justify-content-between mb-2">
                                <div>Base fare (<?php echo $passenger_count; ?> passenger<?php echo $passenger_count > 1 ? 's' : ''; ?>)</div>
                                <div>$<?php echo number_format($booking['price'] * $passenger_count, 2); ?></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <div>Taxes & fees</div>
                                <div>Included</div>
                            </div>
                            
                            <?php if ($booking['total_amount'] > ($booking['price'] * $passenger_count)): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <div>Additional services</div>
                                    <div>$<?php echo number_format($booking['total_amount'] - ($booking['price'] * $passenger_count), 2); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between pt-2 mt-2 border-top fw-bold">
                                <div>Total amount</div>
                                <div>$<?php echo number_format($booking['total_amount'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display payment summary -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Payment Summary</h5>
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
                                <tr class="border-top fw-bold">
                                    <td>Total amount to pay</td>
                                    <td class="text-end">$<?php echo number_format($total_price, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            
            // Format credit card number with spaces
            const cardNumberInput = document.getElementById('card_number');
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue;
            });
            
            // Format expiry date with slash
            const expiryInput = document.getElementById('expiry_date');
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
            
            // Payment method selection
            const paymentCards = document.querySelectorAll('.payment-method-card');
            const cardForm = document.getElementById('credit-card-form');
            
            paymentCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    paymentCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    
                    // Check the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Show/hide credit card form
                    if (radio.value === 'credit_card') {
                        cardForm.style.display = 'block';
                    } else {
                        cardForm.style.display = 'none';
                    }
                });
            });
            
            // Initialize with credit card selected
            document.getElementById('card-option').classList.add('selected');
        });
    </script>
</body>
</html>
