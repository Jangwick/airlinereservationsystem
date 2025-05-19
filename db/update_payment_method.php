<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

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
        
        // Set existing payment methods for completed payments
        $update_sql = "UPDATE bookings SET payment_method = 'credit_card' WHERE payment_status = 'completed' AND payment_method IS NULL";
        $conn->query($update_sql);
    } else {
        $success = false;
        $message = "Error adding column: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The payment_method column already exists in the bookings table.";
}

// Display result
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Payment Method</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='p-5'>
    <div class='container'>
        <h1>Database Update</h1>
        <div class='alert alert-" . ($success ? 'success' : 'danger') . "'>
            $message
        </div>
        <div class='mb-4'>
            <p>The <code>payment_method</code> column allows the system to track which payment method was used for each booking.</p>
            <p>This enables payment method statistics and reporting in your admin dashboard.</p>
        </div>
        <a href='../admin/manage_bookings.php' class='btn btn-primary'>Return to Booking Management</a>
    </div>
</body>
</html>";
?>
