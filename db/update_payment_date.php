<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if the payment_date column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_date'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Add the column if it doesn't exist
if (!$column_exists) {
    $sql = "ALTER TABLE bookings ADD COLUMN payment_date DATETIME AFTER payment_status";
    
    if ($conn->query($sql) === TRUE) {
        // Try to backfill payment dates for completed payments
        $backfill_query = "UPDATE bookings SET payment_date = booking_date WHERE payment_status = 'completed' AND payment_date IS NULL";
        $conn->query($backfill_query);
        
        $success = true;
        $message = "The payment_date column has been added successfully to the bookings table.";
    } else {
        $success = false;
        $message = "Error adding column: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The payment_date column already exists in the bookings table.";
}

// Display result
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Payment Date</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <h1>Database Update</h1>
        <div class="alert alert-<?php echo ($success ? 'success' : 'danger'); ?>">
            <?php echo $message; ?>
        </div>
        <div class="mb-4">
            <p>The <code>payment_date</code> column allows the system to track when payments were made for bookings.</p>
            <p>This enables better payment reporting and tracking in your admin dashboard.</p>
        </div>
        <a href="../admin/manage_bookings.php" class="btn btn-primary">Return to Booking Management</a>
    </div>
</body>
</html>
