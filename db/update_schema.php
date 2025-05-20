<?php
// Include database connection
require_once 'db_config.php';

// Check if user is authorized (only accessible by admin)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

// Add payment_method column to bookings table if it doesn't exist
$column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
if ($column_check->num_rows === 0) {
    $sql = "ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) AFTER payment_status";
    
    if ($conn->query($sql) === TRUE) {
        echo "Column payment_method added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column payment_method already exists";
}

echo "<br><br><a href='../admin/dashboard.php'>Return to Dashboard</a>";
?>
