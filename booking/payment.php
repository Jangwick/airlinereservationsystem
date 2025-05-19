<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Get booking ID
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Validate booking ID and ownership
if ($booking_id <= 0) {
    header("Location: ../user/bookings.php");
    exit();
}

// Get booking information
$booking_query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                 f.departure_time, f.arrival_time, f.price 
                 FROM bookings b 
                 JOIN flights f ON b.flight_id = f.flight_id 
                 WHERE b.booking_id = ? AND b.user_id = ?";
$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

if ($booking_result->num_rows === 0) {
    header("Location: ../user/bookings.php");
    exit();
}

$booking = $booking_result->fetch_assoc();

// Add this line to provide a default passenger count if missing
$passenger_count = isset($booking['passenger_count']) ? $booking['passenger_count'] : 1;

// Check if booking is already paid
if ($booking['payment_status'] === 'completed') {
    header("Location: confirmation.php?booking_id=" . $booking_id);
    exit();
}

// Check if booking is cancelled
if ($booking['booking_status'] === 'cancelled') {
    header("Location: ../user/bookings.php");
    exit();
}

// Process payment
$payment_error = '';
$payment_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment information
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = $_POST['card_number'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Simple validation
    if (empty($payment_method) || empty($card_number) || empty($card_name) || empty($expiry_date) || empty($cvv)) {
        $payment_error = "All payment fields are required";
    } else {
        // In a real application, you would integrate with a payment gateway here
        // For this example, we'll simulate a successful payment
        
        // Check if payment_method and payment_date columns exist in the bookings table
        $payment_method_column_exists = false;
        $payment_date_column_exists = false;
        
        $columns_result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
        if ($columns_result && $columns_result->num_rows > 0) {
            $payment_method_column_exists = true;
        }
        
        $columns_result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_date'");
        if ($columns_result && $columns_result->num_rows > 0) {
            $payment_date_column_exists = true;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Build the update query based on which columns exist
            $update_fields = ["payment_status = 'completed'", "booking_status = 'confirmed'"];
            $params = [];
            $types = "";
            
            if ($payment_method_column_exists) {
                $update_fields[] = "payment_method = ?";
                $params[] = $payment_method;
                $types .= "s";
            }
            
            if ($payment_date_column_exists) {
                $update_fields[] = "payment_date = NOW()";
            }
            
            $update_query = "UPDATE bookings SET " . implode(", ", $update_fields) . " WHERE booking_id = ?";
            $params[] = $booking_id;
            $types .= "i";
            
            $update_stmt = $conn->prepare($update_query);
            if (!empty($params)) {
                $update_stmt->bind_param($types, ...$params);
            }
            $update_stmt->execute();
            
            // Create booking_history record if table exists
            $check_history_table = $conn->query("SHOW TABLES LIKE 'booking_history'");
            if ($check_history_table->num_rows > 0) {
                $history_query = "INSERT INTO booking_history (booking_id, status, status_change, notes, updated_by) 
                                 VALUES (?, 'confirmed', 'Payment completed', 'Payment processed via " . $payment_method . "', ?)";
                $history_stmt = $conn->prepare($history_query);
                $history_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
                $history_stmt->execute();
            }
            
            // Create notification for admin (if notifications table exists)
            $check_notifications = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($check_notifications->num_rows > 0) {
                $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                                      VALUES (?, 'Payment Received', 'Payment received for booking BK-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "', 'payment')";
                $admin_query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
                $admin_result = $conn->query($admin_query);
                if ($admin_result->num_rows > 0) {
                    $admin_id = $admin_result->fetch_assoc()['user_id'];
                    $notification_stmt = $conn->prepare($notification_query);
                    $notification_stmt->bind_param("i", $admin_id);
                    $notification_stmt->execute();
                }
                
                // Create notification for user
                $user_notification = "INSERT INTO notifications (user_id, title, message, type) 
                                     VALUES (?, 'Payment Confirmed', 'Your payment for booking BK-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . " has been received.', 'payment')";
                $user_stmt = $conn->prepare($user_notification);
                $user_stmt->bind_param("i", $_SESSION['user_id']);
                $user_stmt->execute();
            }
            
            // Log the payment for admin
            if (file_exists('../includes/admin_functions.php')) {
                require_once '../includes/admin_functions.php';
                if (function_exists('logAdminAction')) {
                    logAdminAction('payment_received', $_SESSION['user_id'], "Payment received for booking #$booking_id via $payment_method");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success flag
            $payment_success = true;
            
            // Redirect to confirmation page
            header("Location: confirmation.php?booking_id=" . $booking_id);
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $payment_error = "Error processing payment: " . $e->getMessage();
        }
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
                                        <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="cvv" name="cvv" placeholder="123" maxlength="4">
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
