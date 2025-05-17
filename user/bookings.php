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
$success = '';
$error = '';

// Process booking cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = $_GET['cancel'];
    
    // Verify booking belongs to user
    $stmt = $conn->prepare("SELECT b.*, f.flight_id, f.available_seats, f.departure_time 
                          FROM bookings b 
                          JOIN flights f ON b.flight_id = f.flight_id 
                          WHERE b.booking_id = ? AND b.user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Check if flight departure is at least 24 hours away
        $departure_time = strtotime($booking['departure_time']);
        $current_time = time();
        $time_difference = $departure_time - $current_time;
        $hours_difference = $time_difference / (60 * 60);
        
        if ($hours_difference >= 24) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Update booking status
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
                // Update payment status
                $stmt = $conn->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
                // Update tickets status
                $stmt = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
                // Update available seats in flights
                $new_available_seats = $booking['available_seats'] + $booking['passengers'];
                $stmt = $conn->prepare("UPDATE flights SET available_seats = ? WHERE flight_id = ?");
                $stmt->bind_param("ii", $new_available_seats, $booking['flight_id']);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $success = "Booking cancelled successfully. A refund will be processed within 7-14 business days.";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = "Cancellation failed: " . $e->getMessage();
            }
        } else {
            $error = "Cancellation failed. Bookings can only be cancelled at least 24 hours before departure.";
        }
    } else {
        $error = "Invalid booking or booking not found.";
    }
}

// Get all user bookings
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                      f.departure_time, f.arrival_time, f.status as flight_status 
                      FROM bookings b 
                      JOIN flights f ON b.flight_id = f.flight_id 
                      WHERE b.user_id = ? 
                      ORDER BY b.booking_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="card-text text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                    </div>
                </div>
                
                <div class="list-group shadow-sm">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="bookings.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-ticket-alt me-2"></i> My Bookings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <a href="../flights/search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Search Flights
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">My Bookings</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link active" href="#all-bookings" data-bs-toggle="tab">All Bookings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#upcoming" data-bs-toggle="tab">Upcoming</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#completed" data-bs-toggle="tab">Completed</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#cancelled" data-bs-toggle="tab">Cancelled</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <div class="tab-pane active" id="all-bookings">
                                <?php if (count($bookings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="bookingsTable">
                                            <thead>
                                                <tr>
                                                    <th>Booking ID</th>
                                                    <th>Flight</th>
                                                    <th>Route</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                            <div class="text-muted small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($booking['departure_city']) . ' → ' . htmlspecialchars($booking['arrival_city']); ?></td>
                                                        <td>
                                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                                            <div class="text-muted small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                                                <span class="badge bg-success">Confirmed</span>
                                                            <?php elseif ($booking['booking_status'] == 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">Pending</span>
                                                            <?php elseif ($booking['booking_status'] == 'cancelled'): ?>
                                                                <span class="badge bg-danger">Cancelled</span>
                                                            <?php elseif ($booking['booking_status'] == 'completed'): ?>
                                                                <span class="badge bg-info">Completed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($booking['payment_status'] == 'completed'): ?>
                                                                <span class="badge bg-success">Paid</span>
                                                            <?php elseif ($booking['payment_status'] == 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">Pending</span>
                                                            <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                                                <span class="badge bg-info">Refunded</span>
                                                            <?php elseif ($booking['payment_status'] == 'failed'): ?>
                                                                <span class="badge bg-danger">Failed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                
                                                                <?php if ($booking['booking_status'] != 'cancelled' && strtotime($booking['departure_time']) > time() + (24 * 60 * 60)): ?>
                                                                    <a href="bookings.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                                                        <i class="fas fa-times"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                                        <h5>No Bookings Found</h5>
                                        <p class="text-muted">You haven't made any bookings yet. Start by searching for a flight.</p>
                                        <a href="../flights/search.php" class="btn btn-primary mt-3">Search Flights</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tab-pane" id="upcoming">
                                <?php
                                $upcoming_bookings = array_filter($bookings, function($booking) {
                                    return $booking['booking_status'] != 'cancelled' && strtotime($booking['departure_time']) > time();
                                });
                                ?>
                                
                                <?php if (count($upcoming_bookings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Booking ID</th>
                                                    <th>Flight</th>
                                                    <th>Route</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcoming_bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                            <div class="text-muted small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($booking['departure_city']) . ' → ' . htmlspecialchars($booking['arrival_city']); ?></td>
                                                        <td>
                                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                                            <div class="text-muted small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                                                <span class="badge bg-success">Confirmed</span>
                                                            <?php elseif ($booking['booking_status'] == 'pending'): ?>
                                                                <span class="badge bg-warning text-dark">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                
                                                                <?php if (strtotime($booking['departure_time']) > time() + (24 * 60 * 60)): ?>
                                                                    <a href="bookings.php?cancel=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                                                        <i class="fas fa-times"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-plane-departure fa-4x text-muted mb-3"></i>
                                        <h5>No Upcoming Flights</h5>
                                        <p class="text-muted">You don't have any upcoming flights. Book your next adventure now!</p>
                                        <a href="../flights/search.php" class="btn btn-primary mt-3">Search Flights</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tab-pane" id="completed">
                                <?php
                                $completed_bookings = array_filter($bookings, function($booking) {
                                    return ($booking['booking_status'] == 'completed' || strtotime($booking['arrival_time']) < time()) && $booking['booking_status'] != 'cancelled';
                                });
                                ?>
                                
                                <?php if (count($completed_bookings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Booking ID</th>
                                                    <th>Flight</th>
                                                    <th>Route</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($completed_bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                            <div class="text-muted small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($booking['departure_city']) . ' → ' . htmlspecialchars($booking['arrival_city']); ?></td>
                                                        <td>
                                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                                            <div class="text-muted small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                                        </td>
                                                        <td>
                                                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View Details
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-plane-arrival fa-4x text-muted mb-3"></i>
                                        <h5>No Completed Flights</h5>
                                        <p class="text-muted">You don't have any completed flights yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tab-pane" id="cancelled">
                                <?php
                                $cancelled_bookings = array_filter($bookings, function($booking) {
                                    return $booking['booking_status'] == 'cancelled';
                                });
                                ?>
                                
                                <?php if (count($cancelled_bookings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Booking ID</th>
                                                    <th>Flight</th>
                                                    <th>Route</th>
                                                    <th>Date</th>
                                                    <th>Payment Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cancelled_bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($booking['airline']); ?></div>
                                                            <div class="text-muted small"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($booking['departure_city']) . ' → ' . htmlspecialchars($booking['arrival_city']); ?></td>
                                                        <td>
                                                            <div><?php echo date('M d, Y', strtotime($booking['departure_time'])); ?></div>
                                                            <div class="text-muted small"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if ($booking['payment_status'] == 'refunded'): ?>
                                                                <span class="badge bg-info">Refunded</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Pending Refund</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View Details
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-ban fa-4x text-muted mb-3"></i>
                                        <h5>No Cancelled Bookings</h5>
                                        <p class="text-muted">You don't have any cancelled bookings.</p>
                                    </div>
                                <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#bookingsTable').DataTable({
                "order": [[3, "desc"]], // Sort by date (column 3) in descending order
                "language": {
                    "search": "Filter bookings:",
                    "lengthMenu": "Show _MENU_ bookings",
                    "info": "Showing _START_ to _END_ of _TOTAL_ bookings",
                    "emptyTable": "No bookings found"
                }
            });
            
            // Activate tab based on URL hash
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Change hash on tab change
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash;
            });
        });
    </script>
</body>
</html>
