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
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate booking ID
if ($booking_id <= 0) {
    header("Location: bookings.php");
    exit();
}

// Get booking details
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                      f.departure_time, f.arrival_time, f.price,
                      (f.price * 0.85) as base_fare,
                      (f.price * 0.15) as taxes_fees
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Add this line to define passenger count with a default value
$passenger_count = isset($booking['passenger_count']) ? $booking['passenger_count'] : 1;

// Check if passengers table exists and get passenger information
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

// Check if booking history table exists and get history
$booking_history = [];
$history_table_exists = $conn->query("SHOW TABLES LIKE 'booking_history'")->num_rows > 0;

if ($history_table_exists) {
    $stmt = $conn->prepare("SELECT * FROM booking_history WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
    
    while ($history = $history_result->fetch_assoc()) {
        $booking_history[] = $history;
    }
}

// Calculate time difference
$departure = new DateTime($booking['departure_time']);
$arrival = new DateTime($booking['arrival_time']);
$interval = $departure->diff($arrival);
$duration = sprintf('%02d:%02d', $interval->h, $interval->i);

// Format dates
$departure_date = date('l, F j, Y', strtotime($booking['departure_time']));
$departure_time = date('h:i A', strtotime($booking['departure_time']));
$arrival_time = date('h:i A', strtotime($booking['arrival_time']));
$booking_date = date('F j, Y', strtotime($booking['booking_date']));

// Calculate time until departure
$now = new DateTime();
$time_until = $now->diff($departure);
$days_until = $time_until->days;
$hours_until = $time_until->h;
$minutes_until = $time_until->i;

// Check if departure is in the past
$is_past = $now > $departure;

// Get check-in status
$check_in_available = !$is_past && $days_until <= 2; // Allow check-in 48 hours before flight
$check_in_completed = isset($booking['check_in_status']) && $booking['check_in_status'] == 'completed';

// Check if cancellation is possible
$can_cancel = $booking['booking_status'] != 'cancelled' && $booking['booking_status'] != 'completed' && $days_until > 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -30px;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background-color: #3b71ca;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #3b71ca;
            z-index: 1;
        }
        
        .timeline::before {
            content: "";
            position: absolute;
            left: -23px;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #e0e0e0;
        }
        
        .boarding-pass {
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .boarding-pass-header {
            background-color: #3b71ca;
            color: white;
            padding: 15px;
            position: relative;
        }
        
        .boarding-pass-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: radial-gradient(circle, transparent, transparent 5px, #3b71ca 5px, #3b71ca 7px, transparent 7px);
            background-size: 20px 20px;
            background-position: 0 -10px;
        }
        
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
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 50%;
            color: #3b71ca;
            z-index: 2;
        }
        
        .boarding-pass-body {
            padding: 20px 15px 15px;
        }
        
        .boarding-pass-footer {
            border-top: 1px dashed #ddd;
            padding: 15px;
            background-color: #f8f9fa;
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
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                        <li class="breadcrumb-item active">Booking Details</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0">Booking Details</h1>
                <p class="text-muted">
                    Booking Reference: <span class="fw-bold">BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                    • Booked on <?php echo $booking_date; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex gap-2 justify-content-md-end">
                    <?php if (!$is_past && $booking['booking_status'] == 'confirmed'): ?>
                    <a href="../booking/check-in.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary<?php echo $check_in_completed ? ' disabled' : ''; ?>">
                        <?php echo $check_in_completed ? '<i class="fas fa-check me-1"></i> Checked-in' : '<i class="fas fa-check-circle me-1"></i> Web Check-in'; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($booking['booking_status'] == 'pending' && $booking['payment_status'] == 'pending'): ?>
                    <a href="../booking/payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                        <i class="fas fa-credit-card me-1"></i> Pay Now
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($booking['booking_status'] == 'confirmed' || $booking['booking_status'] == 'completed'): ?>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Status Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            <?php elseif ($booking['booking_status'] == 'pending'): ?>
                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
                                </div>
                            <?php elseif ($booking['booking_status'] == 'cancelled'): ?>
                                <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            <?php else: ?>
                                <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-check-double fa-2x text-info"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ms-4">
                            <h5 class="card-title mb-1">Booking Status: 
                                <?php
                                switch ($booking['booking_status']) {
                                    case 'confirmed':
                                        echo '<span class="text-success">Confirmed</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="text-warning">Pending</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="text-danger">Cancelled</span>';
                                        break;
                                    case 'completed':
                                        echo '<span class="text-info">Completed</span>';
                                        break;
                                }
                                ?>
                            </h5>
                            <p class="card-text mb-0">
                                <?php if ($booking['booking_status'] == 'pending'): ?>
                                    Please complete your payment to confirm this booking.
                                <?php elseif ($booking['booking_status'] == 'confirmed' && !$is_past): ?>
                                    Your booking is confirmed. Check-in opens 48 hours before departure.
                                <?php elseif ($booking['booking_status'] == 'confirmed' && $is_past): ?>
                                    Your flight has departed. We hope you enjoyed your journey.
                                <?php elseif ($booking['booking_status'] == 'cancelled'): ?>
                                    This booking has been cancelled.
                                <?php elseif ($booking['booking_status'] == 'completed'): ?>
                                    Your flight has been completed. Thank you for flying with us.
                                <?php endif; ?>
                            </p>
                            
                            <?php if ($booking['payment_status'] == 'pending'): ?>
                                <div class="mt-2">
                                    <span class="badge bg-warning text-dark">Payment Pending</span>
                                </div>
                            <?php elseif ($booking['payment_status'] == 'completed'): ?>
                                <div class="mt-2">
                                    <span class="badge bg-success">Payment Completed</span>
                                </div>
                            <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                <div class="mt-2">
                                    <span class="badge bg-info">Payment Refunded</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$is_past && $booking['booking_status'] == 'confirmed'): ?>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Flight Departure</h6>
                                <?php if ($days_until > 0 || $hours_until > 0 || $minutes_until > 0): ?>
                                <p class="text-muted mb-0 small">
                                    <?php
                                    $time_parts = [];
                                    if ($days_until > 0) $time_parts[] = $days_until . ' day' . ($days_until > 1 ? 's' : '');
                                    if ($hours_until > 0) $time_parts[] = $hours_until . ' hour' . ($hours_until > 1 ? 's' : '');
                                    if ($minutes_until > 0) $time_parts[] = $minutes_until . ' minute' . ($minutes_until > 1 ? 's' : '');
                                    echo implode(', ', $time_parts) . ' until departure';
                                    ?>
                                </p>
                                <?php else: ?>
                                <p class="text-danger mb-0 small">Flight departed</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex-shrink-0">
                                <?php if ($check_in_available): ?>
                                <a href="../booking/check-in.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-sm <?php echo $check_in_completed ? 'btn-success' : 'btn-outline-primary'; ?>">
                                    <?php echo $check_in_completed ? '<i class="fas fa-check me-1"></i> Checked In' : '<i class="fas fa-check-circle me-1"></i> Online Check-in'; ?>
                                </a>
                                <?php elseif (!$is_past): ?>
                                <span class="text-muted small">Check-in opens 48h before departure</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Flight Details Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($booking['airline']); ?>&background=0D6EFD&color=fff&size=40&bold=true&format=svg" 
                                     alt="<?php echo htmlspecialchars($booking['airline']); ?> Logo" 
                                     width="40" height="40" class="airline-logo">
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0"><?php echo htmlspecialchars($booking['airline']); ?></h6>
                                <div class="text-muted small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                            </div>
                        </div>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-5">
                                <div class="text-muted small">From</div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($booking['departure_city']); ?></h5>
                                <div>
                                    <span class="fw-bold"><?php echo $departure_time; ?></span>
                                    <span class="text-muted small"> • <?php echo $departure_date; ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-center d-flex flex-column justify-content-center">
                                <div class="small text-muted"><?php echo $duration; ?></div>
                                <div class="flight-path position-relative my-2">
                                    <div class="flight-path-line"></div>
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div class="small text-muted">Direct</div>
                            </div>
                            
                            <div class="col-md-5">
                                <div class="text-muted small">To</div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($booking['arrival_city']); ?></h5>
                                <div>
                                    <span class="fw-bold"><?php echo $arrival_time; ?></span>
                                    <span class="text-muted small"> • <?php echo date('l, F j, Y', strtotime($booking['arrival_time'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row row-cols-2 row-cols-md-4 g-3 border-top pt-3">
                            <div class="col">
                                <div class="text-muted small">Aircraft</div>
                                <div><?php echo isset($booking['aircraft']) ? htmlspecialchars($booking['aircraft']) : 'Standard Aircraft'; ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Flight Duration</div>
                                <div><?php echo $duration; ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Class</div>
                                <div>Economy</div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Fare Type</div>
                                <div>Standard</div>
                            </div>
                        </div>
                        
                        <?php if ($booking['booking_status'] != 'cancelled'): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>
                                    Please arrive at the airport at least 2 hours before the scheduled departure time.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Passenger Details -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Passenger Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($passengers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Passenger</th>
                                            <th>Date of Birth</th>
                                            <th>Passport</th>
                                            <th>Ticket Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($passengers as $passenger): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($passenger['title'] . ' ' . $passenger['first_name'] . ' ' . $passenger['last_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($passenger['date_of_birth'])); ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($passenger['passport_number']) ? htmlspecialchars($passenger['passport_number']) : 'N/A'; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($passenger['ticket_number']) && !empty($passenger['ticket_number'])): ?>
                                                    <?php echo htmlspecialchars($passenger['ticket_number']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not issued yet</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($booking['passenger_count'] > 0): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                This booking includes <?php echo $booking['passenger_count']; ?> passenger<?php echo $booking['passenger_count'] > 1 ? 's' : ''; ?>.
                                Detailed passenger information is not available in the system.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Passenger information is not available.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Payment Status</div>
                                <div>
                                    <?php
                                    if ($booking['payment_status'] == 'completed') {
                                        echo '<span class="text-success fw-bold">Paid</span>';
                                        if (isset($booking['payment_date'])) {
                                            echo ' on ' . date('M d, Y', strtotime($booking['payment_date']));
                                        }
                                    } elseif ($booking['payment_status'] == 'pending') {
                                        echo '<span class="text-warning fw-bold">Pending</span>';
                                    } elseif ($booking['payment_status'] == 'refunded') {
                                        echo '<span class="text-info fw-bold">Refunded</span>';
                                    } else {
                                        echo htmlspecialchars($booking['payment_status']);
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Payment Method</div>
                                <div>
                                    <?php 
                                    if (!empty($booking['payment_method'])) {
                                        echo ucfirst(str_replace('_', ' ', $booking['payment_method']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td>Base Fare (<?php echo $passenger_count; ?> passenger<?php echo $passenger_count > 1 ? 's' : ''; ?>)</td>
                                            <td class="text-end">$<?php echo number_format(($booking['base_fare'] ?? ($booking['price'] * 0.85)) * $passenger_count, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Taxes & Fees</td>
                                            <td class="text-end">$<?php echo number_format(($booking['taxes_fees'] ?? ($booking['price'] * 0.15)) * $passenger_count, 2); ?></td>
                                        </tr>
                                        <?php if ($booking['total_amount'] > ($booking['price'] * $passenger_count)): ?>
                                        <tr>
                                            <td>Additional Services</td>
                                            <td class="text-end">$<?php echo number_format($booking['total_amount'] - ($booking['price'] * $passenger_count), 2); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr class="fw-bold">
                                            <td>Total</td>
                                            <td class="text-end">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if ($booking['payment_status'] == 'pending'): ?>
                        <div class="text-center mt-3">
                            <a href="../booking/payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                                <i class="fas fa-credit-card me-2"></i> Complete Payment
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Action Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($can_cancel): ?>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                <i class="fas fa-times me-1"></i> Cancel Booking
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($check_in_available && !$check_in_completed): ?>
                            <a href="../booking/check-in.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-check-circle me-1"></i> Online Check-in
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['booking_status'] == 'confirmed' || $booking['booking_status'] == 'completed'): ?>
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Booking
                            </button>
                            
                            <a href="../booking/download_ticket.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Download E-Ticket
                            </a>
                            <?php endif; ?>
                            
                            <a href="#" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#contactModal">
                                <i class="fas fa-headset me-1"></i> Contact Support
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($booking['booking_status'] == 'confirmed' && $check_in_completed): ?>
                <!-- Boarding Pass Card -->
                <div class="card shadow-sm border-0 mb-4 boarding-pass">
                    <div class="boarding-pass-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Boarding Pass</h5>
                                <div class="small">Flight <?php echo htmlspecialchars($booking['flight_number']); ?></div>
                            </div>
                            <div>
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($booking['airline']); ?>&background=0D6EFD&color=fff&size=40&bold=true&format=svg" 
                                     alt="<?php echo htmlspecialchars($booking['airline']); ?> Logo" 
                                     width="40" height="40" class="airline-logo">
                            </div>
                        </div>
                    </div>
                    <div class="boarding-pass-body">
                        <div class="row mb-3">
                            <div class="col-5">
                                <div class="text-muted small">From</div>
                                <div class="h4 mb-0"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                <div class="h6 fw-bold mb-0"><?php echo $departure_time; ?></div>
                            </div>
                            <div class="col-2 text-center">
                                <div class="flight-path position-relative my-2">
                                    <div class="flight-path-line"></div>
                                    <i class="fas fa-plane"></i>
                                </div>
                            </div>
                            <div class="col-5 text-end">
                                <div class="text-muted small">To</div>
                                <div class="h4 mb-0"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                <div class="h6 fw-bold mb-0"><?php echo $arrival_time; ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="text-muted small">Date</div>
                                <div class="fw-bold"><?php echo date('d M Y', strtotime($booking['departure_time'])); ?></div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted small">Boarding Time</div>
                                <div class="fw-bold"><?php echo date('h:i A', strtotime($booking['departure_time']) - 1800); ?></div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted small">Gate</div>
                                <div class="fw-bold">
                                    <?php echo isset($booking['gate']) ? htmlspecialchars($booking['gate']) : 'TBA'; ?>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted small">Seat</div>
                                <div class="fw-bold">
                                    <?php echo isset($booking['seat_number']) ? htmlspecialchars($booking['seat_number']) : 'TBA'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=BK<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>" alt="Boarding Pass QR Code" class="img-fluid">
                        </div>
                    </div>
                    <div class="boarding-pass-footer text-center">
                        <div class="small mb-1">Passenger</div>
                        <div class="fw-bold mb-1">
                            <?php 
                            if (count($passengers) > 0) {
                                echo htmlspecialchars($passengers[0]['title'] . ' ' . $passengers[0]['first_name'] . ' ' . $passengers[0]['last_name']);
                            } else {
                                echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                            }
                            ?>
                        </div>
                        <div class="text-muted small">Booking Ref: BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Booking History Card -->
                <?php if (count($booking_history) > 0): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Booking History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach($booking_history as $history): ?>
                            <div class="timeline-item">
                                <div class="small text-muted"><?php echo date('M d, Y g:i A', strtotime($history['created_at'])); ?></div>
                                <div class="fw-bold"><?php echo ucfirst($history['status']); ?></div>
                                <div><?php echo htmlspecialchars($history['notes']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelBookingModalLabel">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../booking/cancel.php" method="post" id="cancelForm">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Cancellation may be subject to fees according to our cancellation policy.
                        </div>
                        
                        <p>Are you sure you want to cancel this booking?</p>
                        
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Cancellation Reason</label>
                            <select class="form-select" id="cancel_reason" name="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="Change of plans">Change of plans</option>
                                <option value="Found better deal">Found better deal</option>
                                <option value="Schedule conflict">Schedule conflict</option>
                                <option value="Other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="other_reason_div" style="display: none;">
                            <label for="other_reason" class="form-label">Please specify</label>
                            <textarea class="form-control" id="other_reason" name="other_reason" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="cancelForm" class="btn btn-danger">Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Support Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Contact Support</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="supportForm">
                        <input type="hidden" name="booking_ref" value="BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>">
                        
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label">Subject</label>
                            <select class="form-select" id="contact_subject" name="subject" required>
                                <option value="">Select subject...</option>
                                <option value="Change Request">Change Request</option>
                                <option value="Refund Inquiry">Refund Inquiry</option>
                                <option value="Special Assistance">Special Assistance</option>
                                <option value="Baggage Issue">Baggage Issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_message" class="form-label">Message</label>
                            <textarea class="form-control" id="contact_message" name="message" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">Contact Phone</label>
                            <input type="tel" class="form-control" id="contact_phone" name="phone">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitSupportBtn">Submit</button>
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
            // Cancel booking form handling
            const cancelForm = document.getElementById('cancelForm');
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    const cancelReason = document.getElementById('cancel_reason');
                    const otherReasonDiv = document.getElementById('other_reason_div');
                    const otherReason = document.getElementById('other_reason');
                    
                    // Validate form
                    if (cancelReason.value === '') {
                        e.preventDefault();
                        alert('Please select a cancellation reason');
                        return false;
                    }
                    
                    if (cancelReason.value === 'Other' && otherReason.value.trim() === '') {
                        e.preventDefault();
                        alert('Please specify the other reason for cancellation');
                        return false;
                    }
                    
                    // Confirm cancellation
                    if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Form is valid, allow submission to proceed
                    return true;
                });
            }
            
            // Show "other reason" textarea when "Other" is selected for cancellation
            var cancelReason = document.getElementById('cancel_reason');
            if (cancelReason) {
                cancelReason.addEventListener('change', function() {
                    var otherReasonDiv = document.getElementById('other_reason_div');
                    if (this.value === 'Other') {
                        otherReasonDiv.style.display = 'block';
                    } else {
                        otherReasonDiv.style.display = 'none';
                    }
                });
            }
            
            // Handle support form submission via AJAX
            const supportForm = document.getElementById('supportForm');
            const submitSupportBtn = document.getElementById('submitSupportBtn');
            
            if (submitSupportBtn && supportForm) {
                submitSupportBtn.addEventListener('click', function() {
                    // Validate form
                    const subject = document.getElementById('contact_subject');
                    const message = document.getElementById('contact_message');
                    
                    if (subject.value === '') {
                        alert('Please select a subject');
                        subject.focus();
                        return;
                    }
                    
                    if (message.value.trim() === '') {
                        alert('Please enter a message');
                        message.focus();
                        return;
                    }
                    
                    // Gather form data
                    const formData = new FormData(supportForm);
                    
                    // Create status indicator
                    const statusDiv = document.createElement('div');
                    statusDiv.className = 'alert alert-info mt-3';
                    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending your message...';
                    supportForm.appendChild(statusDiv);
                    
                    // Disable the submit button
                    submitSupportBtn.disabled = true;
                    
                    // In a real application, you would use fetch API to submit the form data
                    // For this demonstration, we'll simulate an AJAX request
                    setTimeout(function() {
                        // Simulate successful submission
                        statusDiv.className = 'alert alert-success mt-3';
                        statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i> Your message has been sent successfully. Our support team will contact you shortly.';
                        
                        // Reset form after a delay
                        setTimeout(function() {
                            supportForm.reset();
                            
                            // Close the modal
                            const contactModal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
                            contactModal.hide();
                            
                            // Remove status div and re-enable button
                            supportForm.removeChild(statusDiv);
                            submitSupportBtn.disabled = false;
                        }, 2000);
                    }, 1500);
                });
            }
            
            // Print functionality enhancement for better output
            const printBtn = document.querySelector('button[onclick="window.print()"]');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default onclick handler
                    
                    // Prepare page for printing
                    const originalTitle = document.title;
                    document.title = 'Booking #BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?> - SkyWay Airlines';
                    
                    // Add print-specific styles
                    const style = document.createElement('style');
                    style.textContent = `
                        @media print {
                            body * {
                                visibility: hidden;
                            }
                            .container, .container * {
                                visibility: visible;
                            }
                            .no-print, .no-print * {
                                display: none !important;
                            }
                            .card {
                                break-inside: avoid;
                                margin-bottom: 20px;
                            }
                            @page {
                                size: portrait;
                                margin: 0.5cm;
                            }
                        }
                    `;
                    document.head.appendChild(style);
                    
                    // Add class to elements that shouldn't be printed
                    document.querySelectorAll('.nav, .navbar, .footer, .dropdown-menu, button').forEach(el => {
                        if (!el.classList.contains('print-only')) {
                            el.classList.add('no-print');
                        }
                    });
                    
                    // Print the page
                    window.print();
                    
                    // Restore title
                    setTimeout(() => document.title = originalTitle, 100);
                });
            }
            
            // Check for successful check-in from query parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('checkin') === 'success') {
                // Show success message for check-in
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Check-in completed successfully! Your boarding pass is now available.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Insert at the top of the page content
                const container = document.querySelector('.container');
                container.insertBefore(alertDiv, container.firstChild);
                
                // Scroll to the alert
                window.scrollTo({top: 0, behavior: 'smooth'});
                
                // Remove the parameter from the URL
                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            }
        });
    </script>
</body>
</html>
