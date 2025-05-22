<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';
// Include currency helper
require_once '../includes/currency_helper.php';

// Check if flight_id is provided
$flight_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($flight_id <= 0) {
    header("Location: manage_flights.php");
    exit();
}

// Get flight details
$stmt = $conn->prepare("SELECT f.*,
                     (f.price * 0.85) as base_fare,
                     (f.price * 0.15) as taxes_fees,
                     (f.total_seats - COALESCE((SELECT SUM(b.passengers) FROM bookings b 
                      WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0)) AS available_seats,
                     COALESCE((SELECT COUNT(*) FROM bookings b WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0) AS booking_count,
                     COALESCE((SELECT SUM(b.total_amount) FROM bookings b WHERE b.flight_id = f.flight_id AND b.payment_status = 'completed'), 0) AS total_revenue
                     FROM flights f 
                     WHERE flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_flights.php");
    exit();
}

$flight = $result->fetch_assoc();

// Calculate flight duration
$departure = new DateTime($flight['departure_time']);
$arrival = new DateTime($flight['arrival_time']);
$interval = $departure->diff($arrival);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);

// Format dates
$departure_date = date('l, F j, Y', strtotime($flight['departure_time']));
$departure_time = date('h:i A', strtotime($flight['departure_time']));
$arrival_date = date('l, F j, Y', strtotime($flight['arrival_time']));
$arrival_time = date('h:i A', strtotime($flight['arrival_time']));

// Status badge classes
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'scheduled': return 'success';
        case 'delayed': return 'warning';
        case 'cancelled': return 'danger';
        case 'boarding': return 'info';
        case 'departed': return 'primary';
        case 'arrived': return 'secondary';
        default: return 'success';
    }
}

// Process form submission for price update
$price_update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $new_price = floatval($_POST['price']);
    
    if ($new_price > 0) {
        $update_stmt = $conn->prepare("UPDATE flights SET price = ? WHERE flight_id = ?");
        $update_stmt->bind_param("di", $new_price, $flight_id);
        
        if ($update_stmt->execute()) {
            $price_update_message = '<div class="alert alert-success">Flight price updated successfully!</div>';
            // Update the flight array with new price
            $flight['price'] = $new_price;
            $flight['base_fare'] = $new_price * 0.85;
            $flight['taxes_fees'] = $new_price * 0.15;
        } else {
            $price_update_message = '<div class="alert alert-danger">Error updating flight price.</div>';
        }
    } else {
        $price_update_message = '<div class="alert alert-danger">Please enter a valid price.</div>';
    }
}

// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Details - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
    </style>
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
                    <div>
                        <h1 class="h2 mb-0">Flight Details</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 small text-muted">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage_flights.php">Flights</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Flight Details</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="edit_flight.php?id=<?php echo $flight_id; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i> Edit Flight
                            </a>
                            <a href="view_passengers.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-users me-1"></i> View Passengers
                            </a>
                        </div>
                        <a href="manage_flights.php" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-arrow-left me-1"></i> Back to Flights
                        </a>
                    </div>
                </div>
                
                <?php echo $price_update_message; ?>
                
                <!-- Flight Overview -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Flight Overview</h5>
                                    <span class="badge bg-<?php echo getStatusClass($flight['status']); ?>">
                                        <?php echo ucfirst(strtolower($flight['status'] ?? 'Scheduled')); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="small text-muted">Flight Number</div>
                                        <div class="h5"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="small text-muted">Airline</div>
                                        <div class="h5"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-5">
                                        <div class="text-muted small">From</div>
                                        <h5><?php echo htmlspecialchars($flight['departure_city']); ?></h5>
                                        <div class="fw-bold"><?php echo $departure_time; ?></div>
                                        <div class="small text-muted"><?php echo $departure_date; ?></div>
                                    </div>
                                    
                                    <div class="col-md-2 text-center d-flex flex-column justify-content-center">
                                        <div class="small text-muted mb-2">Duration</div>
                                        <div class="fw-bold"><?php echo $duration; ?></div>
                                        <div class="flight-path position-relative my-2">
                                            <div class="flight-path-line"></div>
                                            <i class="fas fa-plane"></i>
                                        </div>
                                        <div class="small text-muted">Direct Flight</div>
                                    </div>
                                    
                                    <div class="col-md-5 text-md-end">
                                        <div class="text-muted small">To</div>
                                        <h5><?php echo htmlspecialchars($flight['arrival_city']); ?></h5>
                                        <div class="fw-bold"><?php echo $arrival_time; ?></div>
                                        <div class="small text-muted"><?php echo $arrival_date; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="small text-muted">Aircraft</div>
                                        <div><?php echo htmlspecialchars($flight['aircraft'] ?? 'Standard Aircraft'); ?></div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="small text-muted">Flight Type</div>
                                        <div><?php echo isset($flight['flight_type']) ? htmlspecialchars($flight['flight_type']) : 'Commercial'; ?></div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="small text-muted">Distance</div>
                                        <div>
                                            <?php 
                                            // Estimate distance based on flight duration
                                            $hours = $interval->h + ($interval->days * 24);
                                            $distance = round($hours * 800); // Average speed ~800km/hour
                                            echo number_format($distance) . ' km';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="small text-muted">Amenities</div>
                                        <div>Standard</div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-light mb-0">
                                    <strong>Flight ID:</strong> <?php echo $flight['flight_id']; ?><br>
                                    <strong>Created:</strong> <?php echo isset($flight['created_at']) ? date('M d, Y H:i', strtotime($flight['created_at'])) : 'N/A'; ?><br>
                                    <?php if (isset($flight['updated_at'])): ?>
                                    <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($flight['updated_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing & Capacity Information -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Pricing & Capacity</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="mb-4">
                                        <label for="price" class="form-label">Total Price per Passenger</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?php echo $currency_symbol; ?></span>
                                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo $flight['price']; ?>">
                                            <button type="submit" name="update_price" class="btn btn-primary">Update</button>
                                        </div>
                                        <div class="form-text">This is the total price including taxes and fees.</div>
                                    </div>
                                </form>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Base Fare</span>
                                        <span><?php echo $currency_symbol . number_format($flight['base_fare'], 2); ?></span>
                                    </div>
                                    <div class="form-text">85% of total price</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Taxes & Fees</span>
                                        <span><?php echo $currency_symbol . number_format($flight['taxes_fees'], 2); ?></span>
                                    </div>
                                    <div class="form-text">15% of total price</div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold">Seat Capacity</div>
                                        <span class="badge bg-primary"><?php echo $flight['total_seats']; ?> seats</span>
                                    </div>
                                    
                                    <?php 
                                        $booked = $flight['total_seats'] - $flight['available_seats'];
                                        $occupancy_percent = ($flight['total_seats'] > 0) ? ($booked / $flight['total_seats']) * 100 : 0;
                                    ?>
                                    
                                    <div class="progress mb-2" style="height: 10px;">
                                        <div class="progress-bar bg-<?php echo $occupancy_percent > 80 ? 'danger' : ($occupancy_percent > 50 ? 'warning' : 'success'); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo round($occupancy_percent); ?>%" 
                                             aria-valuenow="<?php echo round($occupancy_percent); ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Available: <?php echo $flight['available_seats']; ?></span>
                                        <span>Booked: <?php echo $booked; ?></span>
                                        <span><?php echo round($occupancy_percent); ?>% Full</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Booking Count</span>
                                        <span><?php echo $flight['booking_count']; ?> bookings</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Total Revenue</span>
                                        <span class="text-success"><?php echo $currency_symbol . number_format($flight['total_revenue'], 2); ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Potential Revenue</span>
                                        <span><?php echo $currency_symbol . number_format($flight['price'] * $flight['total_seats'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="edit_flight.php?id=<?php echo $flight_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Flight
                                    </a>
                                    <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                                        <i class="fas fa-plane-arrival me-1"></i> Update Status
                                    </a>
                                    <a href="view_passengers.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-users me-1"></i> View Passengers
                                    </a>
                                    <?php if ($flight['status'] !== 'cancelled' && $flight['status'] !== 'completed'): ?>
                                    <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                        <i class="fas fa-ban me-1"></i> Cancel Flight
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs for Additional Info -->
                <ul class="nav nav-tabs" id="flightTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="true">
                            Bookings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                            History
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content p-3 border border-top-0 bg-white mb-4">
                    <div class="tab-pane fade show active" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                        <h5>Recent Bookings</h5>
                        <p class="mb-3">View the most recent bookings for this flight.</p>
                        
                        <?php
                        // Get recent bookings
                        $stmt = $conn->prepare("SELECT b.*, u.first_name, u.last_name, u.email 
                                             FROM bookings b 
                                             JOIN users u ON b.user_id = u.user_id 
                                             WHERE b.flight_id = ? 
                                             ORDER BY b.booking_date DESC LIMIT 10");
                        $stmt->bind_param("i", $flight_id);
                        $stmt->execute();
                        $bookings_result = $stmt->get_result();
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Booking Date</th>
                                        <th>Status</th>
                                        <th>Passengers</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($booking['email']); ?></div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($booking['booking_status']) {
                                                case 'confirmed': $status_class = 'success'; break;
                                                case 'pending': $status_class = 'warning'; break;
                                                case 'cancelled': $status_class = 'danger'; break;
                                                case 'completed': $status_class = 'info'; break;
                                                default: $status_class = 'secondary';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $booking['passengers']; ?></td>
                                        <td><?php echo $currency_symbol . number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if ($bookings_result->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No bookings found for this flight</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-end mt-3">
                            <a href="manage_bookings.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-outline-primary btn-sm">
                                View All Bookings
                            </a>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <h5>Flight History</h5>
                        <p>Track changes and updates to this flight.</p>
                        
                        <!-- This is a placeholder. In a real implementation, you would fetch actual history data -->
                        <div class="timeline-vertical mt-4">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Flight Created</span>
                                        <span class="small text-muted"><?php echo isset($flight['created_at']) ? date('M d, Y H:i', strtotime($flight['created_at'])) : date('M d, Y'); ?></span>
                                    </div>
                                    <p class="mb-0 small">Initial flight details were added to the system.</p>
                                </div>
                            </div>
                            
                            <?php if (isset($flight['updated_at']) && $flight['created_at'] != $flight['updated_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Flight Updated</span>
                                        <span class="small text-muted"><?php echo date('M d, Y H:i', strtotime($flight['updated_at'])); ?></span>
                                    </div>
                                    <p class="mb-0 small">Flight details were modified.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Flight Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" action="flight_actions.php" method="post">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                        <input type="hidden" name="from_details" value="1">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Flight Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="scheduled" <?php echo $flight['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="delayed" <?php echo $flight['status'] == 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                <option value="boarding" <?php echo $flight['status'] == 'boarding' ? 'selected' : ''; ?>>Boarding</option>
                                <option value="departed" <?php echo $flight['status'] == 'departed' ? 'selected' : ''; ?>>Departed</option>
                                <option value="arrived" <?php echo $flight['status'] == 'arrived' ? 'selected' : ''; ?>>Arrived</option>
                                <option value="cancelled" <?php echo $flight['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="delayReasonGroup" style="display: none;">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notify_passengers" name="notify_passengers" value="1" checked>
                            <label class="form-check-label" for="notify_passengers">
                                Notify passengers via email
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('updateStatusForm').submit()">Update Status</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Flight Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelFlightForm" action="flight_actions.php" method="post">
                        <input type="hidden" name="action" value="cancel_flight">
                        <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Canceling this flight will affect all bookings. Passengers will be notified.
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Cancellation Reason</label>
                            <textarea class="form-control" id="cancel_reason" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="auto_refund" name="auto_refund" value="1" checked>
                            <label class="form-check-label" for="auto_refund">
                                Automatically process refunds for all bookings
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="cancel_notify_passengers" name="notify_passengers" value="1" checked>
                            <label class="form-check-label" for="cancel_notify_passengers">
                                Notify all passengers via email
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_cancel" class="form-label">Type "CANCEL" to confirm</label>
                            <input type="text" class="form-control" id="confirm_cancel" required pattern="CANCEL">
                            <div class="form-text">This action cannot be undone. Please type "CANCEL" (all caps) to confirm.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancel()">Cancel Flight</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Show/hide delay reason field based on selected status
        document.getElementById('status').addEventListener('change', function() {
            const delayReasonGroup = document.getElementById('delayReasonGroup');
            if (this.value === 'delayed' || this.value === 'cancelled') {
                delayReasonGroup.style.display = 'block';
            } else {
                delayReasonGroup.style.display = 'none';
            }
        });
        
        // Trigger the change event to set initial state
        document.getElementById('status').dispatchEvent(new Event('change'));
        
        // Confirm cancellation
        function confirmCancel() {
            const confirmation = document.getElementById('confirm_cancel').value;
            if (confirmation === 'CANCEL') {
                document.getElementById('cancelFlightForm').submit();
            } else {
                alert('Please type "CANCEL" to confirm.');
            }
        }
    </script>
    
    <style>
        .timeline-vertical {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-marker {
            position: absolute;
            top: 0;
            left: -30px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
        }
        
        .timeline-vertical:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -23px;
            width: 2px;
            background-color: #e0e0e0;
        }
    </style>
</body>
</html>
