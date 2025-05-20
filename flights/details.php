<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Get flight ID
$flight_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate flight ID
if ($flight_id <= 0) {
    header("Location: list.php");
    exit();
}

// Get flight information
$stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ? AND departure_time > NOW()");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php");
    exit();
}

$flight = $result->fetch_assoc();

// Calculate flight duration
$departure = new DateTime($flight['departure_time']);
$arrival = new DateTime($flight['arrival_time']);
$interval = $departure->diff($arrival);
$duration_hours = $interval->h + ($interval->days * 24);
$duration = sprintf('%dh %dm', $duration_hours, $interval->i);

// Format dates
$departure_date = $departure->format('l, F j, Y');
$departure_time = $departure->format('h:i A');
$arrival_date = $arrival->format('l, F j, Y');
$arrival_time = $arrival->format('h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Details - SkyWay Airlines</title>
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
                        <li class="breadcrumb-item"><a href="list.php">Available Flights</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Flight Details</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($flight['airline']); ?> Flight <?php echo htmlspecialchars($flight['flight_number']); ?></h1>
                <p class="text-muted">
                    <?php echo htmlspecialchars($flight['departure_city']); ?> to 
                    <?php echo htmlspecialchars($flight['arrival_city']); ?> • 
                    <?php echo $departure_date; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="list.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Flights
                </a>
                <a href="../booking/book.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-primary">
                    <i class="fas fa-ticket-alt me-2"></i>Book Now
                </a>
            </div>
        </div>

        <!-- Flight Overview Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Flight Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Airline Info -->
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($flight['airline']); ?>&background=0D6EFD&color=fff&size=80&bold=true&format=svg" 
                             alt="<?php echo htmlspecialchars($flight['airline']); ?>" 
                             class="mb-2" width="80" height="80">
                        <h5 class="mb-1"><?php echo htmlspecialchars($flight['airline']); ?></h5>
                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                        <?php if (isset($flight['aircraft']) && !empty($flight['aircraft'])): ?>
                            <p class="small text-muted"><?php echo htmlspecialchars($flight['aircraft']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Flight Route -->
                    <div class="col-md-9">
                        <div class="row mb-4">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="text-muted small">DEPARTURE</div>
                                <h5 class="mb-0"><?php echo $departure_time; ?></h5>
                                <h6><?php echo $departure_date; ?></h6>
                                <div class="fw-bold"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                <div class="text-muted"><?php echo htmlspecialchars($flight['departure_airport']); ?></div>
                            </div>
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
                                <div class="text-muted small mb-2"><?php echo $duration; ?></div>
                                <div class="flight-path w-100 position-relative">
                                    <i class="fas fa-plane text-primary"></i>
                                </div>
                                <div class="text-muted small mt-2">Direct</div>
                            </div>
                            <div class="col-md-5">
                                <div class="text-muted small">ARRIVAL</div>
                                <h5 class="mb-0"><?php echo $arrival_time; ?></h5>
                                <h6><?php echo $arrival_date; ?></h6>
                                <div class="fw-bold"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                <div class="text-muted"><?php echo htmlspecialchars($flight['arrival_airport']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="row">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-3"><i class="fas fa-clock fa-2x"></i></div>
                            <div>
                                <div class="small text-muted">Flight Duration</div>
                                <div class="fw-bold"><?php echo $duration; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-3"><i class="fas fa-chair fa-2x"></i></div>
                            <div>
                                <div class="small text-muted">Available Seats</div>
                                <div class="fw-bold"><?php echo isset($flight['seats_available']) ? $flight['seats_available'] : 'Available'; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-3"><i class="fas fa-tag fa-2x"></i></div>
                            <div>
                                <div class="small text-muted">Starting Price</div>
                                <div class="fw-bold">$<?php echo number_format($flight['price'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Class Options -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Fare Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cabin Class</th>
                                        <th>Benefits</th>
                                        <th class="text-end">Price</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Economy</div>
                                            <div class="small text-muted">Standard seating</div>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled small mb-0">
                                                <li><i class="fas fa-check text-success me-2"></i>Standard seat</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Carry-on bag</li>
                                                <li><i class="fas fa-check text-success me-2"></i>In-flight meals</li>
                                            </ul>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold">$<?php echo number_format($flight['price'], 2); ?></div>
                                            <div class="small text-muted">per person</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="../booking/book.php?flight_id=<?php echo $flight_id; ?>&class=economy" class="btn btn-sm btn-primary">
                                                Select
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">Business</div>
                                            <div class="small text-muted">Premium seating</div>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled small mb-0">
                                                <li><i class="fas fa-check text-success me-2"></i>Everything in Economy</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Priority boarding</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Extra legroom</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Premium meals</li>
                                            </ul>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold">$<?php echo number_format($flight['price'] * 2.5, 2); ?></div>
                                            <div class="small text-muted">per person</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="../booking/book.php?flight_id=<?php echo $flight_id; ?>&class=business" class="btn btn-sm btn-primary">
                                                Select
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">First Class</div>
                                            <div class="small text-muted">Luxury experience</div>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled small mb-0">
                                                <li><i class="fas fa-check text-success me-2"></i>Everything in Business</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Lie-flat seats</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Premium amenities</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Dedicated service</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Lounge access</li>
                                            </ul>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold">$<?php echo number_format($flight['price'] * 4, 2); ?></div>
                                            <div class="small text-muted">per person</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="../booking/book.php?flight_id=<?php echo $flight_id; ?>&class=first" class="btn btn-sm btn-primary">
                                                Select
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Flight Status</span>
                                <span class="badge bg-success">Scheduled</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Flight Number</span>
                                <span class="fw-bold"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Aircraft</span>
                                <span><?php echo htmlspecialchars($flight['aircraft'] ?? 'Standard Aircraft'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Flight Distance</span>
                                <span>
                                    <?php
                                    // Estimate distance based on flight duration (5 miles per minute as a rough estimate)
                                    $duration_minutes = $interval->h * 60 + $interval->i;
                                    $estimated_miles = $duration_minutes * 5;
                                    echo number_format($estimated_miles) . ' miles';
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>In-flight Services</span>
                                <span>Available</span>
                            </li>
                        </ul>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Book early to secure the best prices. Fares may change based on availability.
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <a href="../booking/book.php?flight_id=<?php echo $flight_id; ?>" class="btn btn-primary">
                            <i class="fas fa-ticket-alt me-2"></i>Book This Flight
                        </a>
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
    .flight-path {
        position: relative;
        height: 2px;
        background-color: #e9ecef;
        margin: 15px 0;
    }
    
    .flight-path i {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(90deg);
        background-color: white;
        padding: 0 5px;
    }
    </style>
</body>
</html>
