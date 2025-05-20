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
$flight_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate flight ID
if ($flight_id <= 0) {
    header("Location: flights.php");
    exit();
}

// Get flight details
$stmt = $conn->prepare("SELECT f.*, 
                     (f.total_seats - COALESCE((SELECT SUM(b.passengers) FROM bookings b WHERE b.flight_id = f.flight_id AND b.booking_status != 'cancelled'), 0)) AS available_seats
                     FROM flights f 
                     WHERE f.flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if flight exists
if ($result->num_rows === 0) {
    header("Location: flights.php");
    exit();
}

$flight = $result->fetch_assoc();

// Calculate flight duration
$departure = new DateTime($flight['departure_time']);
$arrival = new DateTime($flight['arrival_time']);
$interval = $departure->diff($arrival);
$duration = sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);

// Format dates and times
$departure_date = date('l, F j, Y', strtotime($flight['departure_time']));
$departure_time = date('h:i A', strtotime($flight['departure_time']));
$arrival_date = date('l, F j, Y', strtotime($flight['arrival_time']));
$arrival_time = date('h:i A', strtotime($flight['arrival_time']));

// Check if the flight is in the past
$is_past = new DateTime() > $departure;

// Default passenger count
$passenger_count = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
if ($passenger_count < 1) $passenger_count = 1;
if ($passenger_count > 9) $passenger_count = 9;

// Helper functions
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'scheduled': return 'success';
        case 'delayed': return 'warning';
        case 'cancelled': return 'danger';
        case 'boarding': return 'info';
        case 'departed': return 'primary';
        case 'arrived': return 'secondary';
        default: return 'success'; // Default to scheduled
    }
}

function formatStatus($status) {
    return ucfirst(strtolower($status ?? 'scheduled')); // Use scheduled as default if null
}
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
        
        .feature-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
            color: #3b71ca;
            margin-bottom: 0.5rem;
        }
        
        .hr-dashed {
            border-top: 2px dashed #dee2e6;
        }
        
        .airline-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="flights.php">Flights</a></li>
                        <li class="breadcrumb-item active">Flight Details</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></h1>
                <p class="text-muted">
                    <?php echo htmlspecialchars($flight['departure_city']); ?> to 
                    <?php echo htmlspecialchars($flight['arrival_city']); ?> · 
                    <?php echo $departure_date; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end align-self-center">
                <?php if (!$is_past && ($flight['available_seats'] ?? 0) > 0): ?>
                    <a href="../booking/select_flight.php?flight_id=<?php echo $flight_id; ?>&passengers=<?php echo $passenger_count; ?>" class="btn btn-primary">
                        <i class="fas fa-ticket-alt me-2"></i>Book This Flight
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Flight Details Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($flight['airline']); ?>&background=0D6EFD&color=fff&size=80&bold=true&format=svg" 
                                 alt="<?php echo htmlspecialchars($flight['airline']); ?> Logo" 
                                 class="airline-logo me-3">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($flight['airline']); ?></h5>
                                <div class="text-muted">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                <div class="badge bg-<?php echo getStatusClass($flight['status']); ?> mt-1">
                                    <?php echo formatStatus($flight['status']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-5">
                            <div class="col-md-5">
                                <div class="text-muted small">Departure</div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($flight['departure_city']); ?></h5>
                                <div class="fw-bold"><?php echo $departure_time; ?></div>
                                <div class="text-muted"><?php echo $departure_date; ?></div>
                            </div>
                            
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
                                <div class="text-muted small text-center mb-1"><?php echo $duration; ?></div>
                                <div class="flight-path w-100">
                                    <div class="flight-path-line"></div>
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div class="text-muted small text-center mt-1">Direct</div>
                            </div>
                            
                            <div class="col-md-5 text-md-end">
                                <div class="text-muted small">Arrival</div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($flight['arrival_city']); ?></h5>
                                <div class="fw-bold"><?php echo $arrival_time; ?></div>
                                <div class="text-muted"><?php echo $arrival_date; ?></div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="row row-cols-2 row-cols-md-4 g-3">
                            <div class="col">
                                <div class="text-muted small">Flight Number</div>
                                <div><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Aircraft</div>
                                <div><?php echo isset($flight['aircraft']) ? htmlspecialchars($flight['aircraft']) : 'Standard Aircraft'; ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Duration</div>
                                <div><?php echo $duration; ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Distance</div>
                                <div><?php echo isset($flight['distance']) ? htmlspecialchars($flight['distance']) . ' km' : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Flight Services -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Services</h5>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-2 row-cols-md-4 text-center g-4">
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-wifi"></i>
                                </div>
                                <h6 class="mb-1">Wi-Fi</h6>
                                <p class="small text-muted mb-0">Available</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h6 class="mb-1">Meals</h6>
                                <p class="small text-muted mb-0">Complimentary</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-tv"></i>
                                </div>
                                <h6 class="mb-1">Entertainment</h6>
                                <p class="small text-muted mb-0">On-demand</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-plug"></i>
                                </div>
                                <h6 class="mb-1">Power</h6>
                                <p class="small text-muted mb-0">At every seat</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-suitcase"></i>
                                </div>
                                <h6 class="mb-1">Baggage</h6>
                                <p class="small text-muted mb-0">20kg included</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-couch"></i>
                                </div>
                                <h6 class="mb-1">Seat</h6>
                                <p class="small text-muted mb-0">Standard</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-wheelchair"></i>
                                </div>
                                <h6 class="mb-1">Assistance</h6>
                                <p class="small text-muted mb-0">Available</p>
                            </div>
                            <div class="col">
                                <div class="feature-icon">
                                    <i class="fas fa-baby"></i>
                                </div>
                                <h6 class="mb-1">Infant</h6>
                                <p class="small text-muted mb-0">Facilities</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fare Information -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Fare Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td>Base Fare (per passenger)</td>
                                        <td class="text-end">$<?php echo number_format($flight['price'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Taxes & Fees</td>
                                        <td class="text-end">Included</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Total (per passenger)</td>
                                        <td class="text-end">$<?php echo number_format($flight['price'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Fare conditions: Allows changes (fee may apply), non-refundable unless otherwise stated.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Price & Booking Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Price Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <h3 class="text-primary mb-0">$<?php echo number_format($flight['price'], 2); ?></h3>
                                <div class="text-muted small">per passenger</div>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-<?php echo getStatusClass($flight['status']); ?> mb-1">
                                    <?php echo formatStatus($flight['status']); ?>
                                </div>
                                <div class="small">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></div>
                            </div>
                        </div>
                        
                        <hr class="hr-dashed">
                        
                        <div class="mb-3">
                            <label for="passengers" class="form-label">Passengers</label>
                            <select id="passengers" class="form-select" onchange="updatePassengerCount(this.value)">
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $passenger_count == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Passenger<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Passengers</span>
                            <span id="passenger-count"><?php echo $passenger_count; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Base Fare</span>
                            <span>$<?php echo number_format($flight['price'], 2); ?> x <span id="passenger-multiplier"><?php echo $passenger_count; ?></span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxes & Fees</span>
                            <span>Included</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span id="total-price">$<?php echo number_format($flight['price'] * $passenger_count, 2); ?></span>
                        </div>
                        
                        <div class="mt-4">
                            <?php if (!$is_past): ?>
                                <?php if (($flight['available_seats'] ?? 0) > 0): ?>
                                    <a href="../booking/select_flight.php?flight_id=<?php echo $flight_id; ?>&passengers=<?php echo $passenger_count; ?>" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Now
                                    </a>
                                    <div class="text-muted small text-center">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?php echo ($flight['available_seats'] ?? 0); ?> seats available
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 mb-3" disabled>
                                        <i class="fas fa-ticket-alt me-2"></i>Fully Booked
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 mb-3" disabled>
                                    <i class="fas fa-clock me-2"></i>Past Flight
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Need Help Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">If you have questions about this flight or need assistance with booking, our support team is here to help.</p>
                        <div class="mb-3">
                            <i class="fas fa-phone-alt me-2 text-primary"></i>
                            <span>+63 (2) 8123 4567</span>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <span>support@skywayairlines.com</span>
                        </div>
                        <div>
                            <i class="fas fa-comment-alt me-2 text-primary"></i>
                            <span>Live Chat (9AM - 6PM)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Flight Policies -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Flight Policies</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-1"><i class="fas fa-exchange-alt me-2 text-primary"></i> Changes & Cancellations</h6>
                            <p class="small text-muted mb-0">Changes permitted for a fee. Cancellation fee may apply.</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="mb-1"><i class="fas fa-suitcase me-2 text-primary"></i> Baggage Information</h6>
                            <p class="small text-muted mb-0">Checked bag: 20kg included. Carry-on: 7kg allowed.</p>
                        </div>
                        <div>
                            <h6 class="mb-1"><i class="fas fa-check-circle me-2 text-primary"></i> Check-in</h6>
                            <p class="small text-muted mb-0">Web check-in opens 48 hours before departure.</p>
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
        // Update passenger count and total price
        function updatePassengerCount(count) {
            const basePrice = <?php echo $flight['price']; ?>;
            document.getElementById('passenger-count').textContent = count;
            document.getElementById('passenger-multiplier').textContent = count;
            document.getElementById('total-price').textContent = '$' + (basePrice * count).toFixed(2);
            
            // Update booking link
            const bookingLinks = document.querySelectorAll('a[href*="select_flight.php"]');
            bookingLinks.forEach(link => {
                const href = link.getAttribute('href');
                link.setAttribute('href', href.replace(/passengers=\d+/, 'passengers=' + count));
            });
        }
    </script>
</body>
</html>