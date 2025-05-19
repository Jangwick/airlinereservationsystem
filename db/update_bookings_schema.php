<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Function to display message
function showMessage($type, $message) {
    $bgColor = $type === 'success' ? 'bg-success' : 'bg-danger';
    echo "<div class='alert $bgColor text-white'>$message</div>";
}

// Check if the payment_method column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Add the column if it doesn't exist
if (!$column_exists) {
    $sql = "ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) AFTER payment_status";
    
    if ($conn->query($sql) === TRUE) {
        $success = true;
        $message = "The payment_method column has been added successfully to the bookings table.";
        
        // Update existing records with default payment method
        $conn->query("UPDATE bookings SET payment_method = 'credit_card' WHERE payment_status = 'completed'");
        $conn->query("UPDATE bookings SET payment_method = NULL WHERE payment_status != 'completed'");
    } else {
        $success = false;
        $message = "Error adding column: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The payment_method column already exists in the bookings table.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <?php include '../admin/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Database Update</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../admin/manage_bookings.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Bookings
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Update Bookings Table</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <?php showMessage($success ? 'success' : 'danger', $message); ?>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h6>What was updated?</h6>
                            <p>
                                The <code>payment_method</code> column is needed to track which payment method customers used for their bookings, such as:
                            </p>
                            <ul>
                                <li><strong>Credit Card</strong></li>
                                <li><strong>PayPal</strong></li>
                                <li><strong>Bank Transfer</strong></li>
                                <li><strong>Wallet</strong></li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                This column enables payment method statistics and reporting in your admin dashboard.
                            </div>

                            <h6 class="mt-4">What next?</h6>
                            <p>You can now:</p>
                            <div class="d-flex gap-2 mt-3">
                                <a href="../admin/manage_bookings.php" class="btn btn-primary">
                                    <i class="fas fa-ticket-alt me-2"></i> Back to Bookings
                                </a>
                                <a href="../admin/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
