<?php
session_start();

// Include functions file to avoid duplicate function declarations
require_once '../includes/functions.php';

// Get base URL using the function from functions.php
$baseUrl = getBaseUrl();

// Get flight ID from URL
$flight_id = isset($_GET['flight_id']) ? (int)$_GET['flight_id'] : 0;
$passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
$class = isset($_GET['class']) ? $_GET['class'] : 'economy';

// In a real application, you would fetch flight details from the database
// For this example, we'll use sample data
$flight = [
    'flight_id' => $flight_id,
    'flight_number' => 'SK10' . $flight_id,
    'airline' => 'SkyWay Airlines',
    'departure_city' => 'Manila',
    'arrival_city' => 'Tokyo',
    'departure_time' => '2023-07-15 08:00:00',
    'arrival_time' => '2023-07-15 13:30:00',
    'duration' => '5h 30m',
    'price' => 450.00,
    'available_seats' => 150,
];

// Calculate total cost
$base_price = $flight['price'];
$class_multiplier = 1;
if ($class == 'business') {
    $class_multiplier = 2.5;
} elseif ($class == 'first') {
    $class_multiplier = 4;
}
$price_per_passenger = $base_price * $class_multiplier;
$total_price = $price_per_passenger * $passengers;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Flight - SkyWay Airlines</title>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Book Your Flight</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="display-4 text-primary mb-3">
                                <i class="fas fa-plane"></i>
                            </div>
                            <h4 class="mb-0">Flight <?php echo htmlspecialchars($flight['flight_number']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($flight['airline']); ?></p>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="text-center me-4">
                                                <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                            </div>
                                            <div class="flex-grow-1 text-center">
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($flight['duration']); ?></p>
                                                <div class="flight-path">
                                                    <i class="fas fa-plane"></i>
                                                </div>
                                            </div>
                                            <div class="text-center ms-4">
                                                <p class="mb-0 fw-bold"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></p>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="mb-1"><i class="fas fa-calendar-alt me-2"></i>Date: <?php echo date('F j, Y', strtotime($flight['departure_time'])); ?></p>
                                            <p class="mb-1"><i class="fas fa-user-friends me-2"></i>Passengers: <?php echo $passengers; ?></p>
                                            <p class="mb-1"><i class="fas fa-crown me-2"></i>Class: <?php echo ucfirst($class); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Price Details</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td>Base Price</td>
                                            <td class="text-end">$<?php echo number_format($base_price, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo ucfirst($class); ?> Class</td>
                                            <td class="text-end">x<?php echo $class_multiplier; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Number of Passengers</td>
                                            <td class="text-end"><?php echo $passengers; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Taxes & Fees</td>
                                            <td class="text-end">Included</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">Total</td>
                                            <td class="text-end fw-bold">$<?php echo number_format($total_price, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <p>Please provide passenger details to complete your booking:</p>
                        </div>

                        <form action="../booking/confirmation.php" method="POST">
                            <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                            <input type="hidden" name="passengers" value="<?php echo $passengers; ?>">
                            <input type="hidden" name="class" value="<?php echo $class; ?>">
                            <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                            
                            <?php for ($i = 1; $i <= $passengers; $i++): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Passenger <?php echo $i; ?> Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="first_name_<?php echo $i; ?>" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name_<?php echo $i; ?>" name="passenger[<?php echo $i; ?>][first_name]" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name_<?php echo $i; ?>" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name_<?php echo $i; ?>" name="passenger[<?php echo $i; ?>][last_name]" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="dob_<?php echo $i; ?>" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" id="dob_<?php echo $i; ?>" name="passenger[<?php echo $i; ?>][dob]" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nationality_<?php echo $i; ?>" class="form-label">Nationality</label>
                                                <input type="text" class="form-control" id="nationality_<?php echo $i; ?>" name="passenger[<?php echo $i; ?>][nationality]" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Contact Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="contact_email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="contact_phone" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Proceed to Payment</button>
                                <a href="../flights/search.php" class="btn btn-outline-secondary">Back to Search</a>
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
</body>
</html>
