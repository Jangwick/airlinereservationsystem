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

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get upcoming bookings (limit to 5)
$stmt = $conn->prepare("SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
                     f.departure_time, f.arrival_time 
                     FROM bookings b 
                     JOIN flights f ON b.flight_id = f.flight_id 
                     WHERE b.user_id = ? AND b.booking_status != 'cancelled' AND f.departure_time > NOW() 
                     ORDER BY f.departure_time ASC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_result = $stmt->get_result();
$upcoming_bookings = [];
while ($booking = $upcoming_result->fetch_assoc()) {
    $upcoming_bookings[] = $booking;
}

// Count total bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking_count = $result->fetch_assoc()['total'];

// Count upcoming flights
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b 
                     JOIN flights f ON b.flight_id = f.flight_id 
                     WHERE b.user_id = ? AND b.booking_status != 'cancelled' AND f.departure_time > NOW()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_count = $result->fetch_assoc()['total'];

// Get upcoming flight (soonest one)
$next_flight = null;
if (count($upcoming_bookings) > 0) {
    $next_flight = $upcoming_bookings[0];
}

// Get recent searches from session if available
$recent_searches = isset($_SESSION['recent_searches']) ? $_SESSION['recent_searches'] : [];

// Try to get notifications if the table exists
$notifications = [];
$notification_count = 0;
$notifications_available = false;

try {
    // Check if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->num_rows > 0) {
        $notifications_available = true;
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notifications_result = $stmt->get_result();
        while ($notification = $notifications_result->fetch_assoc()) {
            $notifications[] = $notification;
        }
        $notification_count = count($notifications);
    }
} catch (Exception $e) {
    // Silently handle the exception - notifications aren't critical
    $notifications_available = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SkyWay Airlines</title>
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
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3 bg-primary text-white">
                    <div class="card-body py-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-6 fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                                <p class="lead opacity-75 mb-4">Manage your bookings, view your upcoming flights, and explore new destinations.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="../flights/search.php" class="btn btn-light">
                                        <i class="fas fa-search me-2"></i>Search Flights
                                    </a>
                                    <a href="bookings.php" class="btn btn-outline-light">
                                        <i class="fas fa-ticket-alt me-2"></i>View My Bookings
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 text-center d-none d-md-block">
                                <i class="fas fa-plane-departure fa-6x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats & Quick Access -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-ticket-alt fa-2x text-primary mb-3"></i>
                        <h5 class="fw-bold"><?php echo $booking_count; ?></h5>
                        <p class="text-muted">Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-plane-departure fa-2x text-primary mb-3"></i>
                        <h5 class="fw-bold"><?php echo $upcoming_count; ?></h5>
                        <p class="text-muted">Upcoming Flights</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <a href="profile.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-edit fa-2x text-primary mb-3"></i>
                            <h5 class="fw-bold">My Profile</h5>
                            <p class="text-muted">Update your details</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="../booking/check-in.php" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x text-primary mb-3"></i>
                            <h5 class="fw-bold">Web Check-In</h5>
                            <p class="text-muted">Check in for your flight</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Flights -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Upcoming Flights</h5>
                            <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($upcoming_bookings) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($upcoming_bookings as $booking): ?>
                                    <div class="list-group-item px-0">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center mb-2 mb-md-0">
                                                <div class="fw-bold text-primary"><?php echo date('M d', strtotime($booking['departure_time'])); ?></div>
                                                <div class="small"><?php echo date('Y', strtotime($booking['departure_time'])); ?></div>
                                            </div>
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <div class="fw-bold"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($booking['airline']); ?></div>
                                            </div>
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['departure_city']); ?></span>
                                                    <span class="mx-2"><i class="fas fa-arrow-right text-muted"></i></span>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($booking['arrival_city']); ?></span>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo date('h:i A', strtotime($booking['departure_time'])); ?> - <?php echo date('h:i A', strtotime($booking['arrival_time'])); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-md-end">
                                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-plane text-muted fa-3x mb-3"></i>
                                <h5>No Upcoming Flights</h5>
                                <p class="text-muted">You don't have any upcoming flights at the moment.</p>
                                <a href="../flights/search.php" class="btn btn-primary mt-2">Book a Flight</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- User Info & Quick Links -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">My Account</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-circle mb-3 mx-auto">
                                <span class="initials"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                            <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-pencil-alt me-1"></i>Edit Profile
                            </a>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="bookings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                <span><i class="fas fa-ticket-alt me-2 text-primary"></i> My Bookings</span>
                                <span class="badge bg-primary rounded-pill"><?php echo $booking_count; ?></span>
                            </a>
                            <a href="../booking/check-in.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                <span><i class="fas fa-check-circle me-2 text-primary"></i> Web Check-In</span>
                                <i class="fas fa-chevron-right text-muted small"></i>
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                <span><i class="fas fa-user me-2 text-primary"></i> My Profile</span>
                                <i class="fas fa-chevron-right text-muted small"></i>
                            </a>
                            <a href="../auth/logout.php" class="list-group-item list-group-item-action px-0 py-2 border-0">
                                <span class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($notifications_available && count($notifications) > 0): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Notifications</h5>
                            <?php if ($notification_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach($notifications as $notification): ?>
                                <a href="#" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Search & Featured Offers -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Quick Flight Search</h5>
                    </div>
                    <div class="card-body">
                        <form action="../flights/search.php" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="departure_city" class="form-label">From</label>
                                <input type="text" class="form-control" id="departure_city" name="departure_city" placeholder="City or Airport">
                            </div>
                            <div class="col-md-3">
                                <label for="arrival_city" class="form-label">To</label>
                                <input type="text" class="form-control" id="arrival_city" name="arrival_city" placeholder="City or Airport">
                            </div>
                            <div class="col-md-2">
                                <label for="departure_date" class="form-label">Departure Date</label>
                                <input type="date" class="form-control" id="departure_date" name="departure_date">
                            </div>
                            <div class="col-md-2">
                                <label for="passengers" class="form-label">Passengers</label>
                                <select class="form-select" id="passengers" name="passengers">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Search Flights</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
    .avatar-circle {
        width: 80px;
        height: 80px;
        background-color: #3b71ca;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .initials {
        font-size: 32px;
        color: white;
        font-weight: bold;
    }
    </style>
</body>
</html>
