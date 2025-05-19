<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

// Check if passengers table exists
$passengers_table_exists = $conn->query("SHOW TABLES LIKE 'passengers'")->num_rows > 0;

if (!$passengers_table_exists) {
    $success = false;
    $message = "The passengers table doesn't exist yet. This column will be automatically added when the passengers table is created.";
} else {
    // Check if the seat_number column exists
    $column_exists = $conn->query("SHOW COLUMNS FROM passengers LIKE 'seat_number'")->num_rows > 0;
    
    if (!$column_exists) {
        // Add the column
        $sql = "ALTER TABLE passengers ADD COLUMN seat_number VARCHAR(10) DEFAULT NULL";
        
        if ($conn->query($sql) === TRUE) {
            $success = true;
            $message = "The seat_number column has been added successfully to the passengers table.";
        } else {
            $success = false;
            $message = "Error adding column: " . $conn->error;
        }
    } else {
        $success = true;
        $message = "The seat_number column already exists in the passengers table.";
    }
}

// Output result
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Seat Number</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <h1>Database Update</h1>
        <div class="alert alert-<?php echo ($success ? 'success' : 'danger'); ?>">
            <?php echo $message; ?>
        </div>
        <div class="mb-4">
            <p>The <code>seat_number</code> column allows the system to assign seat numbers to passengers during check-in.</p>
            <p>This enables seat selection and boarding pass generation for checked-in passengers.</p>
        </div>
        <a href="../admin/manage_bookings.php" class="btn btn-primary">Return to Booking Management</a>
    </div>
</body>
</html>
