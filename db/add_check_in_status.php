<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if the check_in_status column exists
$column_exists = false;
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'check_in_status'");
if ($result->num_rows > 0) {
    $column_exists = true;
}

// Add the column if it doesn't exist
if (!$column_exists) {
    // Check if check_in_time column also exists, if not add both
    $check_in_time_exists = $conn->query("SHOW COLUMNS FROM bookings LIKE 'check_in_time'")->num_rows > 0;
    
    $sql = [];
    
    // Add check_in_status column
    $sql[] = "ALTER TABLE bookings ADD COLUMN check_in_status VARCHAR(20) DEFAULT NULL AFTER booking_status";
    
    // Add check_in_time column if it doesn't exist
    if (!$check_in_time_exists) {
        $sql[] = "ALTER TABLE bookings ADD COLUMN check_in_time DATETIME DEFAULT NULL AFTER check_in_status";
    }
    
    $success = true;
    $message = "The check_in_status and check_in_time columns have been added successfully to the bookings table.";
    
    // Execute each SQL statement
    foreach ($sql as $query) {
        if ($conn->query($query) !== TRUE) {
            $success = false;
            $message = "Error adding columns: " . $conn->error;
            break;
        }
    }
} else {
    $success = true;
    $message = "The check_in_status column already exists in the bookings table.";
}

// Display result
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Check-in Status</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='p-5'>
    <div class='container'>
        <h1>Database Update</h1>
        <div class='alert alert-" . ($success ? 'success' : 'danger') . "'>
            $message
        </div>
        <div class='mb-4'>
            <p>The <code>check_in_status</code> column allows the system to track whether passengers have checked in for their flights.</p>
            <p>This enables online check-in functionality and boarding pass generation.</p>
        </div>
        <a href='../admin/manage_bookings.php' class='btn btn-primary'>Return to Booking Management</a>
    </div>
</body>
</html>";
?>
