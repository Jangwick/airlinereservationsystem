<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize variables
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$error = '';
$flight = null;

// Validate flight ID
if ($flight_id <= 0) {
    header("Location: ../flights/search.php");
    exit();
}

// Get flight information
$stmt = $conn->prepare("SELECT f.* FROM flights f 
                       WHERE f.flight_id = ? AND f.departure_time > NOW()");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Flight not found or no longer available for booking";
} else {
    $flight = $result->fetch_assoc();
    
    // Check available seats
    $seats_query = "SELECT available_seats FROM flights WHERE flight_id = ?";
    $seats_stmt = $conn->prepare($seats_query);
    $seats_stmt->bind_param("i", $flight_id);
    $seats_stmt->execute();
    $available_seats = $seats_stmt->get_result()->fetch_assoc()['available_seats'];
    
    if ($available_seats < $passengers) {
        $error = "Not enough seats available. Only {$available_seats} seats left on this flight.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    // Get form data
    $passenger_data = [];
    for ($i = 1; $i <= $passengers; $i++) {
        $passenger_data[] = [
            'title' => $_POST["title_$i"],
            'first_name' => $_POST["first_name_$i"],
            'last_name' => $_POST["last_name_$i"],
            'dob' => $_POST["dob_$i"],
            'passport' => $_POST["passport_$i"] ?? '',
            'phone' => $_POST["phone_$i"] ?? ''
        ];
    }
    
    // Calculate total price
    $total_price = $flight['price'] * $passengers;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if passenger_count column exists in bookings table
        $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'passenger_count'");
        $has_passenger_count = $column_check->num_rows > 0;
        
        // Insert booking record with different queries depending on column existence
        if ($has_passenger_count) {
            $booking_query = "INSERT INTO bookings (user_id, flight_id, booking_date, passenger_count, 
                         total_amount, booking_status, payment_status) 
                         VALUES (?, ?, NOW(), ?, ?, 'pending', 'pending')";
            $booking_stmt = $conn->prepare($booking_query);
            $booking_stmt->bind_param("iiid", $_SESSION['user_id'], $flight_id, $passengers, $total_price);
        } else {
            $booking_query = "INSERT INTO bookings (user_id, flight_id, booking_date, 
                         total_amount, booking_status, payment_status) 
                         VALUES (?, ?, NOW(), ?, 'pending', 'pending')";
            $booking_stmt = $conn->prepare($booking_query);
            $booking_stmt->bind_param("iid", $_SESSION['user_id'], $flight_id, $total_price);
        }
        
        $booking_stmt->execute();
        $booking_id = $conn->insert_id;
        
        // Check if passengers table exists, create if not
        $check_table = $conn->query("SHOW TABLES LIKE 'passengers'");
        if ($check_table->num_rows == 0) {
            $create_table = "CREATE TABLE passengers (
                passenger_id INT PRIMARY KEY AUTO_INCREMENT,
                booking_id INT,
                title VARCHAR(10),
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                date_of_birth DATE,
                passport_number VARCHAR(20),
                phone_number VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
            )";
            $conn->query($create_table);
        }
        
        // Insert passenger information
        $passenger_query = "INSERT INTO passengers (booking_id, title, first_name, last_name, 
                          date_of_birth, passport_number, phone_number) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $passenger_stmt = $conn->prepare($passenger_query);
        
        foreach ($passenger_data as $passenger) {
            $passenger_stmt->bind_param("issssss", 
                $booking_id, 
                $passenger['title'], 
                $passenger['first_name'], 
                $passenger['last_name'], 
                $passenger['dob'], 
                $passenger['passport'], 
                $passenger['phone']
            );
            $passenger_stmt->execute();
        }
        
        // Update available seats
        $update_seats = "UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?";
        $update_stmt = $conn->prepare($update_seats);
        $update_stmt->bind_param("ii", $passengers, $flight_id);
        $update_stmt->execute();
        
        // Try to create notification for admin (if notifications table exists)
        $check_notifications = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($check_notifications->num_rows > 0) {
            // Notifications table exists, proceed with creating notifications
            $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                                  VALUES (?, 'New Booking', 'A new booking has been created (ID: BK-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . ")', 'booking')";
            $admin_query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
            $admin_result = $conn->query($admin_query);
            if ($admin_result->num_rows > 0) {
                $admin_id = $admin_result->fetch_assoc()['user_id'];
                $notification_stmt = $conn->prepare($notification_query);
                $notification_stmt->bind_param("i", $admin_id);
                $notification_stmt->execute();
            }

            // Create notification for user
            $user_notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                                       VALUES (?, 'Booking Confirmation', 'Your booking has been created and is awaiting payment. Booking ID: BK-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "', 'booking')";
            $user_notification_stmt = $conn->prepare($user_notification_query);
            $user_notification_stmt->bind_param("i", $_SESSION['user_id']);
            $user_notification_stmt->execute();
        }
        
        // Log the booking activity for admin
        if (file_exists('../includes/admin_functions.php')) {
            require_once '../includes/admin_functions.php';
            if (function_exists('logAdminAction')) {
                logAdminAction('booking_created', $_SESSION['user_id'], "New booking created by user (ID: {$_SESSION['user_id']}), Booking ID: $booking_id");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to payment page
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error creating booking: " . $e->getMessage();
    }
}

// Get user information for autofill
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Flight - SkyWay Airlines</title>
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
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="../flights/search.php">Flights</a></li>
                        <li class="breadcrumb-item active">Book Flight</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
                <p class="mt-3">
                    <a href="../flights/search.php" class="btn btn-outline-danger btn-sm">Return to Flight Search</a>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Passenger Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" id="booking-form">
                                <?php for ($i = 1; $i <= $passengers; $i++): ?>
                                    <div class="passenger-form mb-4">
                                        <h6 class="border-bottom pb-2 mb-3">Passenger <?php echo $i; ?> <?php echo $i === 1 ? '(Lead Passenger)' : ''; ?></h6>
                                        
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label for="title_<?php echo $i; ?>" class="form-label">Title</label>
                                                <select class="form-select" id="title_<?php echo $i; ?>" name="title_<?php echo $i; ?>" required>
                                                    <option value="Mr">Mr</option>
                                                    <option value="Mrs">Mrs</option>
                                                    <option value="Ms">Ms</option>
                                                    <option value="Dr">Dr</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-5 mb-3">
                                                <label for="first_name_<?php echo $i; ?>" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name_<?php echo $i; ?>" name="first_name_<?php echo $i; ?>" 
                                                value="<?php echo $i === 1 ? htmlspecialchars($user['first_name']) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="col-md-5 mb-3">
                                                <label for="last_name_<?php echo $i; ?>" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name_<?php echo $i; ?>" name="last_name_<?php echo $i; ?>"
                                                value="<?php echo $i === 1 ? htmlspecialchars($user['last_name']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="dob_<?php echo $i; ?>" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" id="dob_<?php echo $i; ?>" name="dob_<?php echo $i; ?>" required>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="passport_<?php echo $i; ?>" class="form-label">Passport Number</label>
                                                <input type="text" class="form-control" id="passport_<?php echo $i; ?>" name="passport_<?php echo $i; ?>">
                                                <div class="form-text">Required for international flights</div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="phone_<?php echo $i; ?>" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone_<?php echo $i; ?>" name="phone_<?php echo $i; ?>"
                                                value="<?php echo $i === 1 ? htmlspecialchars($user['phone'] ?? '') : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <?php if ($i === 1): ?>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" id="same_as_account" checked>
                                                <label class="form-check-label" for="same_as_account">
                                                    Use my account information
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                                
                                <h6 class="border-bottom pb-2 mb-3">Contact Information</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contact_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3">Additional Services</h6>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="add_insurance" name="add_insurance" value="1">
                                            <label class="form-check-label" for="add_insurance">
                                                Add Travel Insurance ($15 per passenger)
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="priority_boarding" name="priority_boarding" value="1">
                                            <label class="form-check-label" for="priority_boarding">
                                                Priority Boarding ($10 per passenger)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="extra_baggage" name="extra_baggage" value="1">
                                            <label class="form-check-label" for="extra_baggage">
                                                Extra Baggage Allowance ($25 per passenger)
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="special_meal" name="special_meal" value="1">
                                            <label class="form-check-label" for="special_meal">
                                                Special Meal Request ($8 per passenger)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#policyModal">Privacy Policy</a>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg">Continue to Payment</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4 sticky-top" style="top: 80px;">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Flight Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <img src="../assets/images/airlines/<?php echo strtolower($flight['airline']); ?>.png" alt="Airline Logo" 
                                    class="airline-logo" onerror="this.src='../assets/images/airlines/default.png'" width="40">
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($flight['airline']); ?></h6>
                                    <div class="text-muted small"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                </div>
                            </div>
                            
                            <div class="flight-route mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="h5 mb-0"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                        <div class="text-muted"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></div>
                                        <div class="small text-muted"><?php echo date('M d, Y', strtotime($flight['departure_time'])); ?></div>
                                    </div>
                                    <div class="flight-path align-self-center">
                                        <i class="fas fa-plane"></i>
                                    </div>
                                    <div class="text-end">
                                        <div class="h5 mb-0"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                        <div class="text-muted"><?php echo date('h:i A', strtotime($flight['arrival_time'])); ?></div>
                                        <div class="small text-muted"><?php echo date('M d, Y', strtotime($flight['arrival_time'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flight-details mb-4">
                                <div class="row mb-2">
                                    <div class="col-5">Duration:</div>
                                    <div class="col-7 text-end">
                                        <?php
                                        $departure = new DateTime($flight['departure_time']);
                                        $arrival = new DateTime($flight['arrival_time']);
                                        $duration = $departure->diff($arrival);
                                        echo $duration->format('%h h %i min');
                                        ?>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5">Aircraft:</div>
                                    <div class="col-7 text-end"><?php echo htmlspecialchars($flight['aircraft'] ?? 'Standard'); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-5">Class:</div>
                                    <div class="col-7 text-end">Economy</div>
                                </div>
                            </div>
                            
                            <div class="price-summary">
                                <h6 class="border-bottom pb-2">Price Summary</h6>
                                <div class="row mb-2">
                                    <div class="col-8">Base fare (<?php echo $passengers; ?> passenger<?php echo $passengers > 1 ? 's' : ''; ?>)</div>
                                    <div class="col-4 text-end">$<?php echo number_format($flight['price'] * $passengers, 2); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-8">Taxes & fees</div>
                                    <div class="col-4 text-end">Included</div>
                                </div>
                                <div class="row mb-2 additional-services" style="display: none;">
                                    <div class="col-8">Additional services</div>
                                    <div class="col-4 text-end">$<span id="additional-price">0.00</span></div>
                                </div>
                                <div class="row fw-bold mt-2 pt-2 border-top">
                                    <div class="col-8">Total</div>
                                    <div class="col-4 text-end">$<span id="total-price"><?php echo number_format($flight['price'] * $passengers, 2); ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Booking and Payment</h6>
                    <p>By booking a flight with SkyWay Airlines, you agree to pay the full amount for your ticket. All bookings are subject to availability and confirmation.</p>
                    
                    <h6>2. Flight Changes and Cancellations</h6>
                    <p>Changes to your booking may incur fees. Cancellations less than 24 hours before departure are non-refundable.</p>
                    
                    <h6>3. Baggage Allowance</h6>
                    <p>Each passenger is allowed one carry-on bag and one checked bag. Additional baggage is subject to extra fees.</p>
                    
                    <h6>4. Check-In Requirements</h6>
                    <p>Passengers must check in at least 2 hours before domestic flights and 3 hours before international flights.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="policyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>We collect personal information to process your bookings and provide you with the services you request.</p>
                    <p>Your information is shared only with relevant third parties needed to complete your booking and provide travel services.</p>
                    <p>We implement security measures to protect your personal information and retain it only as long as necessary.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            // Handle "Use my account information" checkbox
            const sameAsAccountCheckbox = document.getElementById('same_as_account');
            if (sameAsAccountCheckbox) {
                sameAsAccountCheckbox.addEventListener('change', function() {
                    const firstName = document.getElementById('first_name_1');
                    const lastName = document.getElementById('last_name_1');
                    const phone = document.getElementById('phone_1');
                    
                    if (this.checked) {
                        firstName.value = '<?php echo addslashes($user['first_name']); ?>';
                        lastName.value = '<?php echo addslashes($user['last_name']); ?>';
                        lastName.value = '<?php echo addslashes($user['last_name']); ?>';
                        phone.value = '<?php echo addslashes($user['phone'] ?? ''); ?>';
                    } else {tName.value = '';
                        firstName.value = '';
                        lastName.value = '';
                        phone.value = '';
                    }
                });
            }
            // Calculate additional services price
            // Calculate additional services priceprice'] * $passengers; ?>;
            const basePrice = <?php echo $flight['price'] * $passengers; ?>;
            const insurance = document.getElementById('add_insurance');boarding');
            const priorityBoarding = document.getElementById('priority_boarding');
            const extraBaggage = document.getElementById('extra_baggage');
            const specialMeal = document.getElementById('special_meal');
            const passengers = <?php echo $passengers; ?>;d('additional-price');
            const additionalPrice = document.getElementById('additional-price');
            const totalPrice = document.getElementById('total-price');onal-services');
            const additionalServices = document.querySelector('.additional-services');
            function updatePrice() {
            function updatePrice() {
                let additional = 0;
                if (insurance && insurance.checked) {
                if (insurance && insurance.checked) {
                    additional += 15 * passengers;
                }
                if (priorityBoarding && priorityBoarding.checked) {
                if (priorityBoarding && priorityBoarding.checked) {
                    additional += 10 * passengers;
                }
                if (extraBaggage && extraBaggage.checked) {
                if (extraBaggage && extraBaggage.checked) {
                    additional += 25 * passengers;
                }
                if (specialMeal && specialMeal.checked) {
                if (specialMeal && specialMeal.checked) {
                    additional += 8 * passengers;
                }
                if (additional > 0) {
                if (additional > 0) {s.style.display = 'flex';
                    additionalServices.style.display = 'flex';oFixed(2);
                    additionalPrice.textContent = additional.toFixed(2);
                } else {tionalServices.style.display = 'none';
                    additionalServices.style.display = 'none';
                }
                totalPrice.textContent = (basePrice + additional).toFixed(2);
                totalPrice.textContent = (basePrice + additional).toFixed(2);
            }
            insurance?.addEventListener('change', updatePrice);
            insurance?.addEventListener('change', updatePrice);Price);
            priorityBoarding?.addEventListener('change', updatePrice);
            extraBaggage?.addEventListener('change', updatePrice);
            specialMeal?.addEventListener('change', updatePrice);
        });t>
    </script>
</body>
</html>