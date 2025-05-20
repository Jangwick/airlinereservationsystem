<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Get flight ID from URL
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

// Validate flight ID
if ($flight_id <= 0) {
    $_SESSION['flight_status'] = [
        'type' => 'danger',
        'message' => 'Invalid flight ID'
    ];
    header("Location: manage_flights.php");
    exit();
}

// Get flight details
$stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flight_status'] = [
        'type' => 'danger',
        'message' => 'Flight not found'
    ];
    header("Location: manage_flights.php");
    exit();
}

$flight = $result->fetch_assoc();

// Get passenger list - join bookings with users and passengers table if exists
$passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;

// Check if ticket_number column exists in the passengers table
$ticket_column_exists = false;
if ($passengers_table_exists) {
    $column_check = $conn->query("SHOW COLUMNS FROM passengers LIKE 'ticket_number'");
    $ticket_column_exists = ($column_check && $column_check->num_rows > 0);
    
    // If the column doesn't exist, create it
    if (!$ticket_column_exists) {
        try {
            $conn->query("ALTER TABLE passengers ADD COLUMN ticket_number VARCHAR(20) NULL AFTER passport_number");
            $ticket_column_exists = true;
            // Log this action
            error_log("Added ticket_number column to passengers table");
        } catch (Exception $e) {
            // Log error but continue
            error_log("Failed to add ticket_number column: " . $e->getMessage());
        }
    }
}

// Now we can use the standard query since we've added the column if it was missing
if ($passengers_table_exists) {
    // If passengers table exists, get detailed passenger info
    $query = "SELECT b.booking_id, b.booking_status, b.payment_status, 
              u.user_id, u.first_name AS user_first_name, u.last_name AS user_last_name, u.email, u.phone,
              p.passenger_id, p.first_name, p.last_name, p.date_of_birth, p.passport_number, 
              p.ticket_number
              FROM bookings b
              JOIN users u ON b.user_id = u.user_id
              JOIN passengers p ON b.booking_id = p.booking_id
              WHERE b.flight_id = ? AND b.booking_status != 'cancelled'
              ORDER BY b.booking_date DESC";
} else {
    // Otherwise, just get booking user info
    $query = "SELECT b.booking_id, b.booking_status, b.payment_status, b.passengers AS passenger_count,
              u.user_id, u.first_name, u.last_name, u.email, u.phone
              FROM bookings b
              JOIN users u ON b.user_id = u.user_id
              WHERE b.flight_id = ? AND b.booking_status != 'cancelled'
              ORDER BY b.booking_date DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$passengers_result = $stmt->get_result();
$passengers = [];

while ($row = $passengers_result->fetch_assoc()) {
    $passengers[] = $row;
}

// Calculate capacity statistics
$total_seats = $flight['total_seats'] ?? 0;
$booked_query = "SELECT SUM(passengers) as total_booked FROM bookings WHERE flight_id = ? AND booking_status != 'cancelled'";
$stmt = $conn->prepare($booked_query);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$booked_result = $stmt->get_result();
$booked_data = $booked_result->fetch_assoc();
$booked_seats = $booked_data['total_booked'] ?? 0;
$available_seats = max(0, $total_seats - $booked_seats);
$capacity_percent = ($total_seats > 0) ? min(100, round(($booked_seats / $total_seats) * 100)) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Passengers - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <h1 class="h2">Flight Passengers</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_flights.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Back to Flights
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printPassengerList()">
                            <i class="fas fa-print"></i> Print List
                        </button>
                    </div>
                </div>

                <!-- Flight Details Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title"><?php echo htmlspecialchars($flight['airline']); ?> Flight <?php echo htmlspecialchars($flight['flight_number']); ?></h5>
                                <p class="card-text">
                                    <strong>Route:</strong> <?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?><br>
                                    <strong>Departure:</strong> <?php echo date('M d, Y h:i A', strtotime($flight['departure_time'])); ?><br>
                                    <strong>Status:</strong> 
                                    <?php if ($flight['status'] == 'scheduled'): ?>
                                        <span class="badge bg-success">Scheduled</span>
                                    <?php elseif ($flight['status'] == 'delayed'): ?>
                                        <span class="badge bg-warning text-dark">Delayed</span>
                                    <?php elseif ($flight['status'] == 'cancelled'): ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php elseif ($flight['status'] == 'completed'): ?>
                                        <span class="badge bg-info">Completed</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Capacity</h6>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar bg-<?php echo $capacity_percent > 80 ? 'danger' : ($capacity_percent > 60 ? 'warning' : 'success'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $capacity_percent; ?>%" 
                                         aria-valuenow="<?php echo $capacity_percent; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <p class="card-text">
                                    <strong>Booked:</strong> <?php echo $booked_seats; ?> passengers<br>
                                    <strong>Available:</strong> <?php echo $available_seats; ?> seats<br>
                                    <strong>Total Capacity:</strong> <?php echo $total_seats; ?> seats
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Passengers Table -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Passenger List</h5>
                            <span class="badge bg-primary"><?php echo count($passengers); ?> passengers</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($passengers)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                <h5>No Passengers Found</h5>
                                <p class="text-muted">There are currently no passengers booked on this flight.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="passengersTable">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <?php if ($passengers_table_exists): ?>
                                                <th>Passenger Name</th>
                                                <th>Passport/ID</th>
                                                <th>Ticket Number</th>
                                            <?php else: ?>
                                                <th>Customer</th>
                                                <th>Passengers</th>
                                            <?php endif; ?>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($passengers as $passenger): ?>
                                        <tr>
                                            <td>
                                                <a href="booking_details.php?id=<?php echo $passenger['booking_id']; ?>">
                                                    BK-<?php echo str_pad($passenger['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                                                </a>
                                            </td>
                                            
                                            <?php if ($passengers_table_exists): ?>
                                                <td><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                                <td><?php echo !empty($passenger['passport_number']) ? htmlspecialchars($passenger['passport_number']) : 'Not provided'; ?></td>
                                                <td><?php 
    if ($passengers_table_exists && $ticket_column_exists) {
        echo !empty($passenger['ticket_number']) ? htmlspecialchars($passenger['ticket_number']) : 'Not issued';
    } else {
        echo 'N/A';
    }
?></td>
                                            <?php else: ?>
                                                <td><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></td>
                                                <td><?php echo $passenger['passenger_count']; ?></td>
                                            <?php endif; ?>
                                            
                                            <td>
                                                <div><?php echo htmlspecialchars($passenger['email']); ?></div>
                                                <div class="small text-muted"><?php echo !empty($passenger['phone']) ? htmlspecialchars($passenger['phone']) : 'No phone'; ?></div>
                                            </td>
                                            
                                            <td>
                                                <?php if ($passenger['booking_status'] == 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span>
                                                <?php elseif ($passenger['booking_status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($passenger['booking_status'] == 'completed'): ?>
                                                    <span class="badge bg-info">Completed</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($passenger['payment_status'] == 'pending'): ?>
                                                    <span class="badge bg-danger">Payment Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td>
                                                <a href="booking_details.php?id=<?php echo $passenger['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#passengersTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "info": true,
                "searching": true,
            });
        });
        
        // Print passenger list
        function printPassengerList() {
            window.print();
        }
    </script>
</body>
</html>
