<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if the passenger_count column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'passenger_count'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Add the column if it doesn't exist
if (!$column_exists) {
    $sql = "ALTER TABLE bookings ADD COLUMN passenger_count INT DEFAULT 1 AFTER booking_date";
    
    if ($conn->query($sql) === TRUE) {
        // Update existing records based on passenger data if the table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'passengers'");
        if ($check_table->num_rows > 0) {
            // Update passenger count for existing bookings
            $update_sql = "UPDATE bookings b SET b.passenger_count = 
                          (SELECT COUNT(*) FROM passengers p WHERE p.booking_id = b.booking_id)
                          WHERE EXISTS (SELECT 1 FROM passengers p WHERE p.booking_id = b.booking_id)";
            $conn->query($update_sql);
        }
        
        $success = true;
        $message = "The passenger_count column has been added successfully to the bookings table.";
    } else {
        $success = false;
        $message = "Error adding column: " . $conn->error;
    }
} else {
    $success = true;
    $message = "The passenger_count column already exists in the bookings table.";
}

// Display result
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='p-5'>
    <div class='container'>
        <h1>Database Update</h1>
        <div class='alert alert-" . ($success ? 'success' : 'danger') . "'>
            $message
        </div>
        <a href='../admin/manage_bookings.php' class='btn btn-primary'>Return to Booking Management</a>
    </div>
</body>
</html>";
?>
