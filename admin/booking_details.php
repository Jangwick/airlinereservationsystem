<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate booking ID
if ($booking_id <= 0) {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Invalid booking ID'
    ];
    header("Location: manage_bookings.php");
    exit();
}

// Get booking details with joined information
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                      f.departure_time, f.arrival_time, f.price, u.first_name, u.last_name, 
                      u.email, u.phone
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      JOIN users u ON b.user_id = u.user_id 
                      WHERE b.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['booking_status'] = [
        'type' => 'danger',
        'message' => 'Booking not found'
    ];
    header("Location: manage_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Format dates
$departure_date = date('l, F j, Y', strtotime($booking['departure_time']));
$departure_time = date('h:i A', strtotime($booking['departure_time']));
$arrival_time = date('h:i A', strtotime($booking['arrival_time']));
$booking_date = date('F j, Y', strtotime($booking['booking_date']));

// Get passenger count
$passenger_count = isset($booking['passenger_count']) ? $booking['passenger_count'] : 1;

// Check if passengers table exists and get passenger information
$passengers = [];
$passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;

if ($passengers_table_exists) {
    // Remove the problematic ORDER BY created_at clause
    $stmt = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $passengers_result = $stmt->get_result();
    
    while ($passenger = $passengers_result->fetch_assoc()) {
        $passengers[] = $passenger;
    }
}

// Calculate time difference
$departure = new DateTime($booking['departure_time']);
$arrival = new DateTime($booking['arrival_time']);
$interval = $departure->diff($arrival);
$duration = sprintf('%02d:%02d', $interval->h + ($interval->days * 24), $interval->i);

// Check if booking history table exists and get history
$booking_history = [];
$history_table_exists = $conn->query("SHOW TABLES LIKE 'booking_history'")->num_rows > 0;

if ($history_table_exists) {
    // Check if created_at column exists in booking_history table
    $columns_result = $conn->query("SHOW COLUMNS FROM booking_history LIKE 'created_at'");
    if ($columns_result && $columns_result->num_rows > 0) {
        // If created_at exists, use it for ordering
        $stmt = $conn->prepare("SELECT * FROM booking_history WHERE booking_id = ? ORDER BY created_at DESC");
    } else {
        // Otherwise, don't use an ORDER BY clause
        $stmt = $conn->prepare("SELECT * FROM booking_history WHERE booking_id = ?");
    }
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
    
    while ($history = $history_result->fetch_assoc()) {
        $booking_history[] = $history;
    }
}

// Check if payments table exists and get payment information
$payments = [];
$payments_table_exists = $conn->query("SHOW TABLES LIKE 'payments'")->num_rows > 0;

if ($payments_table_exists) {
    // Check if created_at column exists in payments table
    $columns_result = $conn->query("SHOW COLUMNS FROM payments LIKE 'created_at'");
    if ($columns_result && $columns_result->num_rows > 0) {
        // If created_at exists, use it for ordering
        $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
    } else {
        // Otherwise, don't use an ORDER BY clause
        $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
    }
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $payments_result = $stmt->get_result();
    
    while ($payment = $payments_result->fetch_assoc()) {
        $payments[] = $payment;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <h1 class="h2">Booking Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                        <a href="manage_bookings.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <!-- Booking ID and Status -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h3>Booking #BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                        <p class="text-muted">Booked on <?php echo $booking_date; ?></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-<?php 
                            echo $booking['booking_status'] == 'confirmed' ? 'success' : 
                                ($booking['booking_status'] == 'pending' ? 'warning' : 
                                    ($booking['booking_status'] == 'completed' ? 'info' : 'danger')); 
                        ?> fs-6 mb-2">
                            <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                        <span class="badge bg-<?php 
                            echo $booking['payment_status'] == 'completed' ? 'success' : 
                                ($booking['payment_status'] == 'pending' ? 'warning' : 
                                    ($booking['payment_status'] == 'refunded' ? 'info' : 'danger')); 
                        ?> fs-6 ms-2 mb-2">
                            <?php echo ucfirst($booking['payment_status']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary" onclick="changeStatus(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_status']; ?>', '<?php echo $booking['payment_status']; ?>')">
                                        <i class="fas fa-edit me-1"></i> Update Status
                                    </button>
                                    
                                    <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                    <button type="button" class="btn btn-warning" onclick="confirmCancel(<?php echo $booking['booking_id']; ?>)">
                                        <i class="fas fa-ban me-1"></i> Cancel Booking
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['payment_status'] == 'completed'): ?>
                                    <button type="button" class="btn btn-info" onclick="processRefund(<?php echo $booking['booking_id']; ?>, <?php echo $booking['total_amount']; ?>)">
                                        <i class="fas fa-undo me-1"></i> Process Refund
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $booking['booking_id']; ?>, '<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                    
                                    <?php if ($booking['booking_status'] == 'confirmed' || $booking['booking_status'] == 'completed'): ?>
                                    <a href="../booking/download_ticket.php?booking_id=<?php echo $booking['booking_id']; ?>&admin=true" class="btn btn-secondary">
                                        <i class="fas fa-download me-1"></i> Download Ticket
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="row">
                    <!-- Customer and Flight Information -->
                    <div class="col-lg-8">
                        <!-- Customer Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="text-muted small">Full Name</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="text-muted small">Email</div>
                                        <div><?php echo htmlspecialchars($booking['email']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="text-muted small">Phone</div>
                                        <div><?php echo htmlspecialchars($booking['phone'] ?? 'Not provided'); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="text-muted small">Booking Date</div>
                                        <div><?php echo $booking_date; ?></div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="view_customer.php?id=<?php echo $booking['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> View Customer Profile
                                    </a>
                                    <a href="mailto:<?php echo htmlspecialchars($booking['email']); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                        <i class="fas fa-envelope me-1"></i> Send Email
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Flight Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Flight Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light p-2 rounded-circle me-3">
                                                <i class="fas fa-plane text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7 mb-3 mb-md-0">
                                        <div class="row">
                                            <div class="col-5 text-center">
                                                <div class="fw-bold"><?php echo $departure_time; ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                            </div>
                                            <div class="col-2 text-center">
                                                <div class="small text-muted"><?php echo $duration; ?></div>
                                                <div class="position-relative">
                                                    <hr class="my-1">
                                                    <i class="fas fa-plane small position-absolute top-0 start-50 translate-middle"></i>
                                                </div>
                                            </div>
                                            <div class="col-5 text-center">
                                                <div class="fw-bold"><?php echo $arrival_time; ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-md-end">
                                        <a href="flight_details.php?id=<?php echo $booking['flight_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Flight
                                        </a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Departure Date</div>
                                        <div><?php echo $departure_date; ?></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Base Price</div>
                                        <div>$<?php echo number_format($booking['price'], 2); ?></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Passengers</div>
                                        <div><?php echo $passenger_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Passenger Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Passenger Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($passengers) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Date of Birth</th>
                                                <th>Passport/ID</th>
                                                <th>Ticket #</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($passengers as $passenger): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($passenger['title'] . ' ' . $passenger['first_name'] . ' ' . $passenger['last_name']); ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($passenger['date_of_birth'])); ?>
                                                </td>
                                                <td>
                                                    <?php echo !empty($passenger['passport_number']) ? htmlspecialchars($passenger['passport_number']) : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <?php echo !empty($passenger['ticket_number']) ? htmlspecialchars($passenger['ticket_number']) : 'Not issued'; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No detailed passenger information available. This booking is for <?php echo $passenger_count; ?> passenger<?php echo $passenger_count > 1 ? 's' : ''; ?>.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Payment Status</div>
                                        <div>
                                            <?php
                                            switch ($booking['payment_status']) {
                                                case 'completed':
                                                    echo '<span class="text-success fw-bold">Paid</span>';
                                                    break;
                                                case 'pending':
                                                    echo '<span class="text-warning fw-bold">Pending</span>';
                                                    break;
                                                case 'refunded':
                                                    echo '<span class="text-info fw-bold">Refunded</span>';
                                                    break;
                                                case 'failed':
                                                    echo '<span class="text-danger fw-bold">Failed</span>';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($booking['payment_status']);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Payment Method</div>
                                        <div>
                                            <?php echo isset($booking['payment_method']) ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'Not specified'; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="text-muted small">Total Amount</div>
                                        <div class="fw-bold">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (count($payments) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Transaction ID</th>
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($payment['status']) {
                                                        case 'completed':
                                                            echo '<span class="badge bg-success">Completed</span>';
                                                            break;
                                                        case 'pending':
                                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                            break;
                                                        case 'refunded':
                                                            echo '<span class="badge bg-info">Refunded</span>';
                                                            break;
                                                        case 'failed':
                                                            echo '<span class="badge bg-danger">Failed</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge bg-secondary">' . ucfirst($payment['status']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No detailed payment transaction history available.
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPaymentNote()">
                                        <i class="fas fa-plus me-1"></i> Add Payment Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Information -->
                    <div class="col-lg-4">
                        <!-- Admin Notes -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Admin Notes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($booking['admin_notes'])): ?>
                                <div class="p-3 bg-light rounded mb-3">
                                    <?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No admin notes for this booking.</p>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="addAdminNote()">
                                        <i class="fas fa-edit me-1"></i> Add/Edit Notes
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking History -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Booking History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($booking_history) > 0): ?>
                                <div class="timeline">
                                    <?php foreach ($booking_history as $history): ?>
                                    <div class="timeline-item">
                                        <div class="small text-muted"><?php echo date('M d, Y H:i', strtotime($history['created_at'])); ?></div>
                                        <div class="fw-bold"><?php echo ucfirst($history['status']); ?></div>
                                        <?php if (!empty($history['notes'])): ?>
                                        <div class="small"><?php echo htmlspecialchars($history['notes']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No booking history available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Contact Customer -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Contact Customer</h5>
                            </div>
                            <div class="card-body">
                                <form id="contactForm">
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="4" required></textarea>
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" onclick="contactCustomer()">
                                        <i class="fas fa-paper-plane me-1"></i> Send Message
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Include the necessary modals from manage_bookings.php -->
    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <!-- Modal content -->
    </div>
    
    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <!-- Modal content -->
    </div>
    
    <!-- Delete Booking Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <!-- Modal content -->
    </div>
    
    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
        <!-- Modal content -->
    </div>
    
    <!-- Admin Notes Modal -->
    <div class="modal fade" id="adminNotesModal" tabindex="-1" aria-labelledby="adminNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminNotesModalLabel">Update Admin Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="adminNotesForm" action="update_notes.php" method="post">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="5"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveAdminNotes()">Save Notes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add your JavaScript functions here (changeStatus, confirmCancel, etc.)
        
        function addAdminNote() {
            $('#adminNotesModal').modal('show');
        }
        
        function saveAdminNotes() {
            document.getElementById('adminNotesForm').submit();
        }
        
        function contactCustomer() {
            var subject = document.getElementById('subject').value;
            var message = document.getElementById('message').value;
            
            if (subject.trim() === '' || message.trim() === '') {
                alert('Please fill in all fields');
                return;
            }
            
            // Simulate sending email
            alert('Email sent to customer');
            document.getElementById('contactForm').reset();
        }
        
        function addPaymentNote() {
            // Implement payment note functionality
            alert('This feature is not yet implemented.');
        }
    </script>
</body>
</html>
