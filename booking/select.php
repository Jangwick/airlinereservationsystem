<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the current URL in session to redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize variables
$error = '';
$flight = null;
$return_flight = null;
$selected_seats = array();

// Check if flight_id is provided
if (!isset($_GET['flight_id'])) {
    header("Location: ../flights/search.php");
    exit();
}

$flight_id = $_GET['flight_id'];
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$class = isset($_GET['class']) ? $_GET['class'] : 'economy';
$trip_type = isset($_GET['trip_type']) ? $_GET['trip_type'] : 'one_way';
$return_flight_id = isset($_GET['return_flight_id']) ? $_GET['return_flight_id'] : null;

// Get flight information
$stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $flight = $result->fetch_assoc();
} else {
    $error = "Flight not found";
}

// Get return flight information if applicable
if ($trip_type === 'round_trip' && $return_flight_id) {
    $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $return_flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $return_flight = $result->fetch_assoc();
    }
}

// Calculate total fare based on class
$price_multiplier = 1;
switch ($class) {
    case 'business':
        $price_multiplier = 2.5;
        break;
    case 'first':
        $price_multiplier = 4;
        break;
    default:
        $price_multiplier = 1;
}

$total_fare = $flight ? $flight['price'] * $price_multiplier * $passengers : 0;
if ($return_flight) {
    $total_fare += $return_flight['price'] * $price_multiplier * $passengers;
}

// Process form submission for seat selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seats'])) {
    $selected_seats = $_POST['seats'];
    
    // Validate seat selection
    if (count($selected_seats) != $passengers) {
        $error = "Please select exactly " . $passengers . " seat(s)";
    } else {
        // Store booking details in session
        $_SESSION['booking'] = [
            'flight_id' => $flight_id,
            'return_flight_id' => $return_flight_id,
            'passengers' => $passengers,
            'class' => $class,
            'seats' => $selected_seats,
            'return_seats' => isset($_POST['return_seats']) ? $_POST['return_seats'] : [],
            'total_fare' => $total_fare
        ];
        
        // Redirect to passenger details page
        header("Location: passenger_details.php");
        exit();
    }
}

// Get occupited seats (for demo purposes, we'll use random seats)
function getOccupiedSeats() {
    $occupied = array();
    $total_seats = 60;
    $random_count = rand(10, 30);
    
    for ($i = 0; $i < $random_count; $i++) {
        $row = rand(1, 10);
        $col = chr(rand(65, 70)); // A to F
        $occupied[] = $row . $col;
    }
    
    return $occupied;
}

$occupied_seats = getOccupiedSeats();
$return_occupied_seats = getOccupiedSeats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - SkyWay Airlines</title>
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
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($flight): ?>
            <!-- Flight Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Flight Summary</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5><?php echo htmlspecialchars($flight['airline']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?></h5>
                                    <div class="text-muted"><?php echo date('l, F j, Y', strtotime($flight['departure_time'])); ?></div>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $flight['status'] === 'scheduled' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($flight['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted">Departure</div>
                                        <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></div>
                                        <div><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted">Duration</div>
                                        <div><i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($flight['duration']); ?></div>
                                        <div class="flight-route">
                                            <div class="flight-dots"></div>
                                            <div class="flight-dots right"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="text-muted">Arrival</div>
                                        <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></div>
                                        <div><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($return_flight): ?>
                            <div class="col-md-12">
                                <h5 class="border-top pt-3">Return Flight</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5><?php echo htmlspecialchars($return_flight['airline']); ?> - <?php echo htmlspecialchars($return_flight['flight_number']); ?></h5>
                                        <div class="text-muted"><?php echo date('l, F j, Y', strtotime($return_flight['departure_time'])); ?></div>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $return_flight['status'] === 'scheduled' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($return_flight['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="text-muted">Departure</div>
                                            <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($return_flight['departure_time'])); ?></div>
                                            <div><?php echo htmlspecialchars($return_flight['departure_city']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="text-muted">Duration</div>
                                            <div><i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($return_flight['duration']); ?></div>
                                            <div class="flight-route">
                                                <div class="flight-dots"></div>
                                                <div class="flight-dots right"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="text-muted">Arrival</div>
                                            <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($return_flight['arrival_time'])); ?></div>
                                            <div><?php echo htmlspecialchars($return_flight['arrival_city']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <p><strong>Passengers:</strong> <?php echo $passengers; ?></p>
                            <p><strong>Class:</strong> <?php echo ucfirst($class); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Price per passenger:</strong> $<?php echo number_format($flight['price'] * $price_multiplier, 2); ?></p>
                            <p class="fs-4 fw-bold text-primary">Total: $<?php echo number_format($total_fare, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seat Selection -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"]); ?>" method="post">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Select Your Seats</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Please select <?php echo $passengers; ?> seat(s) for your journey.
                        </div>
                        
                        <h5 class="mb-3">Outbound Flight: <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?></h5>
                        
                        <div class="seat-selection-container mb-4">
                            <div class="text-center mb-3">
                                <div class="plane-header">
                                    <i class="fas fa-plane fa-2x"></i>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-center mb-3">
                                <div class="seat-legend me-3">
                                    <div class="seat available d-inline-block me-1"></div> Available
                                </div>
                                <div class="seat-legend me-3">
                                    <div class="seat selected d-inline-block me-1"></div> Selected
                                </div>
                                <div class="seat-legend">
                                    <div class="seat occupied d-inline-block me-1"></div> Occupied
                                </div>
                            </div>
                            
                            <div class="seat-map">
                                <?php for ($row = 1; $row <= 10; $row++): ?>
                                    <?php for ($col = 'A'; $col <= 'F'; $col++): ?>
                                        <?php
                                        $seat_id = $row . $col;
                                        $seat_class = 'available';
                                        
                                        // Check if seat is occupied
                                        if (in_array($seat_id, $occupied_seats)) {
                                            $seat_class = 'occupied';
                                        }
                                        
                                        // Add aisle
                                        if ($col == 'C' || $col == 'D') {
                                            $margin_class = $col == 'C' ? 'me-3' : 'ms-3';
                                        } else {
                                            $margin_class = '';
                                        }
                                        ?>
                                        <div class="seat <?php echo $seat_class . ' ' . $margin_class; ?>" data-seat="<?php echo $seat_id; ?>">
                                            <?php echo $seat_id; ?>
                                            <?php if ($seat_class !== 'occupied'): ?>
                                                <input type="checkbox" name="seats[]" value="<?php echo $seat_id; ?>" class="d-none seat-checkbox" <?php echo (isset($_POST['seats']) && in_array($seat_id, $_POST['seats'])) ? 'checked' : ''; ?>>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <?php if ($return_flight): ?>
                            <h5 class="mb-3 mt-5">Return Flight: <?php echo htmlspecialchars($return_flight['departure_city']); ?> to <?php echo htmlspecialchars($return_flight['arrival_city']); ?></h5>
                            
                            <div class="seat-selection-container">
                                <div class="text-center mb-3">
                                    <div class="plane-header">
                                        <i class="fas fa-plane fa-2x"></i>
                                    </div>
                                </div>
                                
                                <div class="seat-map">
                                    <?php for ($row = 1; $row <= 10; $row++): ?>
                                        <?php for ($col = 'A'; $col <= 'F'; $col++): ?>
                                            <?php
                                            $seat_id = $row . $col;
                                            $seat_class = 'available';
                                            
                                            // Check if seat is occupied
                                            if (in_array($seat_id, $return_occupied_seats)) {
                                                $seat_class = 'occupied';
                                            }
                                            
                                            // Add aisle
                                            if ($col == 'C' || $col == 'D') {
                                                $margin_class = $col == 'C' ? 'me-3' : 'ms-3';
                                            } else {
                                                $margin_class = '';
                                            }
                                            ?>
                                            <div class="seat <?php echo $seat_class . ' ' . $margin_class; ?>" data-seat="<?php echo $seat_id; ?>">
                                                <?php echo $seat_id; ?>
                                                <?php if ($seat_class !== 'occupied'): ?>
                                                    <input type="checkbox" name="return_seats[]" value="<?php echo $seat_id; ?>" class="d-none return-seat-checkbox" <?php echo (isset($_POST['return_seats']) && in_array($seat_id, $_POST['return_seats'])) ? 'checked' : ''; ?>>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="../flights/search.php" class="btn btn-outline-secondary me-2">Back to Search</a>
                        <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> Flight not found. <a href="../flights/search.php" class="alert-link">Return to search</a>.
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Outbound flight seat selection
            const seats = document.querySelectorAll('.seat:not(.occupied)');
            const seatCheckboxes = document.querySelectorAll('.seat-checkbox');
            const passengerCount = <?php echo $passengers; ?>;
            let selectedCount = 0;
            
            // Count initially selected seats (if any)
            seatCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedCount++;
                    checkbox.parentElement.classList.add('selected');
                }
            });
            
            seats.forEach(seat => {
                seat.addEventListener('click', function() {
                    const checkbox = this.querySelector('.seat-checkbox');
                    
                    if (checkbox) {
                        if (checkbox.checked) {
                            // Unselect seat
                            checkbox.checked = false;
                            this.classList.remove('selected');
                            selectedCount--;
                        } else {
                            // Check if max seats already selected
                            if (selectedCount >= passengerCount) {
                                alert(`You can only select ${passengerCount} seat(s) for this flight.`);
                                return;
                            }
                            
                            // Select seat
                            checkbox.checked = true;
                            this.classList.add('selected');
                            selectedCount++;
                        }
                    }
                });
            });
            
            // Return flight seat selection (if applicable)
            const returnSeats = document.querySelectorAll('.return-seat-checkbox');
            if (returnSeats.length > 0) {
                const returnSeatElements = document.querySelectorAll('.seat:not(.occupied) input[name="return_seats[]"]').forEach(input => input.parentElement);
                let returnSelectedCount = 0;
                
                // Count initially selected return seats (if any)
                returnSeats.forEach(checkbox => {
                    if (checkbox.checked) {
                        returnSelectedCount++;
                        checkbox.parentElement.classList.add('selected');
                    }
                });
                
                document.querySelectorAll('.seat input[name="return_seats[]"]').forEach(input => {
                    const seatEl = input.parentElement;
                    
                    seatEl.addEventListener('click', function() {
                        if (input.checked) {
                            // Unselect seat
                            input.checked = false;
                            this.classList.remove('selected');
                            returnSelectedCount--;
                        } else {
                            // Check if max seats already selected
                            if (returnSelectedCount >= passengerCount) {
                                alert(`You can only select ${passengerCount} seat(s) for the return flight.`);
                                return;
                            }
                            
                            // Select seat
                            input.checked = true;
                            this.classList.add('selected');
                            returnSelectedCount++;
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
