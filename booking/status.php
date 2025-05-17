<?php
session_start();

// Include functions file for base URL
require_once '../includes/functions.php';
$baseUrl = getBaseUrl();

// Initialize variables
$error = '';
$flight = null;

// Sample flight statuses for demonstration purposes
$status_map = [
    'scheduled' => ['class' => 'primary', 'description' => 'Flight is scheduled to depart as planned.'],
    'boarding' => ['class' => 'info', 'description' => 'Boarding is in progress. Please proceed to the gate.'],
    'departed' => ['class' => 'success', 'description' => 'Flight has departed and is en route to its destination.'],
    'arrived' => ['class' => 'success', 'description' => 'Flight has arrived at its destination.'],
    'delayed' => ['class' => 'warning', 'description' => 'Flight is delayed. Please check for updated departure time.'],
    'cancelled' => ['class' => 'danger', 'description' => 'Flight has been cancelled. Please contact customer service.']
];

// Process flight status lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flight_number = $_POST['flight_number'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (empty($flight_number)) {
        $error = 'Please enter a flight number';
    } else {
        // Include database connection
        require_once '../db/db_config.php';
        
        // Query to find flight
        $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_number = ?");
        $stmt->bind_param("s", $flight_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $flight = $result->fetch_assoc();
            
            // If date specified, check for flight on that date
            if (!empty($date) && date('Y-m-d', strtotime($flight['departure_time'])) != $date) {
                $error = 'No flight found with this number on the specified date';
                $flight = null;
            }
        } else {
            $error = 'No flight found with this number. Please check and try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Status - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Flight Status</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($flight): ?>
                            <!-- Flight status display -->
                            <div class="alert alert-<?php echo $status_map[$flight['status']]['class']; ?> mb-4">
                                <h5 class="alert-heading">Flight Status: <?php echo ucfirst($flight['status']); ?></h5>
                                <p><?php echo $status_map[$flight['status']]['description']; ?></p>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Flight Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5><?php echo htmlspecialchars($flight['airline']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?></h5>
                                            <div class="text-muted"><?php echo date('l, F j, Y', strtotime($flight['departure_time'])); ?></div>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $status_map[$flight['status']]['class']; ?>">
                                                <?php echo ucfirst(htmlspecialchars($flight['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <div class="text-muted">Departure</div>
                                                <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></div>
                                                <div><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                                <?php if ($flight['status'] == 'delayed'): ?>
                                                    <div class="mt-2">
                                                        <span class="badge bg-warning text-dark">
                                                            Delayed by 1h 30m
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <div class="text-muted">Duration</div>
                                                <?php
                                                $departure = new DateTime($flight['departure_time']);
                                                $arrival = new DateTime($flight['arrival_time']);
                                                $interval = $departure->diff($arrival);
                                                $duration = $interval->format('%h h %i m');
                                                ?>
                                                <div><i class="fas fa-clock me-1"></i> <?php echo $duration; ?></div>
                                                <div class="flight-path">
                                                    <div class="flight-dots"></div>
                                                    <div class="flight-dots right"></div>
                                                </div>
                                                <?php if (in_array($flight['status'], ['departed'])): ?>
                                                    <div class="text-muted mt-2">
                                                        <small>In Flight</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <div class="text-muted">Arrival</div>
                                                <div class="fs-4 fw-bold"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></div>
                                                <div><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                                <?php if ($flight['status'] == 'arrived'): ?>
                                                    <div class="mt-2">
                                                        <span class="badge bg-success">
                                                            Arrived at <?php echo date('H:i', strtotime('+5 minutes', strtotime($flight['arrival_time']))); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Terminal Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="terminal-info">
                                                <h6><i class="fas fa-plane-departure me-2"></i>Departure</h6>
                                                <p class="mb-1"><strong>Terminal:</strong> 
                                                    <?php echo rand(1, 3); ?>
                                                </p>
                                                <p class="mb-1"><strong>Gate:</strong> 
                                                    <?php echo chr(rand(65, 70)) . rand(1, 20); ?>
                                                </p>
                                                <p class="mb-0"><strong>Check-in Counter:</strong> 
                                                    <?php echo rand(10, 50); ?>-<?php echo rand(51, 60); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="terminal-info">
                                                <h6><i class="fas fa-plane-arrival me-2"></i>Arrival</h6>
                                                <p class="mb-1"><strong>Terminal:</strong> 
                                                    <?php echo rand(1, 3); ?>
                                                </p>
                                                <p class="mb-1"><strong>Baggage Claim:</strong> 
                                                    <?php echo rand(1, 10); ?>
                                                </p>
                                                <p class="mb-0"><strong>Arrival Status:</strong>
                                                    <?php if ($flight['status'] == 'arrived'): ?>
                                                        <span class="text-success">Landed</span>
                                                    <?php elseif ($flight['status'] == 'departed'): ?>
                                                        <span class="text-primary">In Air</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not Departed</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button class="btn btn-secondary me-2" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i> Print Details
                                </button>
                                <a href="<?php echo $baseUrl; ?>flights/search.php" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Search Flights
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Flight status lookup form -->
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                                <p class="text-muted mb-4">Check the current status of your flight using the flight number and date.</p>
                                
                                <div class="mb-3">
                                    <label for="flight_number" class="form-label">Flight Number</label>
                                    <input type="text" class="form-control" id="flight_number" name="flight_number" placeholder="e.g. SK101" required>
                                    <div class="form-text">Enter the flight number (e.g. SK101)</div>
                                    <div class="invalid-feedback">Please enter a flight number</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="date" class="form-label">Date (Optional)</label>
                                    <input type="date" class="form-control" id="date" name="date">
                                    <div class="form-text">Select the date of your flight (optional)</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Check Flight Status</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle text-primary me-2"></i>Flight Status Information</h5>
                        <p class="card-text">Flight status is updated regularly, but may be subject to change without prior notice. We recommend checking your flight status before heading to the airport.</p>
                        
                        <div class="mt-3">
                            <h6>Status Guide</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-primary me-2">Scheduled</span> Flight is scheduled to depart as planned.</li>
                                <li><span class="badge bg-info me-2">Boarding</span> Boarding is in progress. Please proceed to the gate.</li>
                                <li><span class="badge bg-success me-2">Departed</span> Flight has departed and is en route to its destination.</li>
                                <li><span class="badge bg-success me-2">Arrived</span> Flight has arrived at its destination.</li>
                                <li><span class="badge bg-warning text-dark me-2">Delayed</span> Flight is delayed. Please check for updated departure time.</li>
                                <li><span class="badge bg-danger me-2">Cancelled</span> Flight has been cancelled. Please contact customer service.</li>
                            </ul>
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
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms to apply validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
