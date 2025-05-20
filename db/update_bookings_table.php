<?php
session_start();

// Only administrators can run this script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<p>Access denied. Please log in as an administrator.</p>";
    exit;
}

// Include database connection
require_once 'db_config.php';

// Start a transaction to ensure database consistency
$conn->begin_transaction();

try {
    // Check if the price_per_passenger column exists
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'price_per_passenger'");
    $column_exists = $result->num_rows > 0;

    // Either add or modify the column based on its existence
    if ($column_exists) {
        // If the column exists, modify it
        $conn->query("ALTER TABLE bookings MODIFY COLUMN price_per_passenger DECIMAL(10,2) NULL COMMENT 'Price per passenger'");
    } else {
        // If the column doesn't exist, add it
        $conn->query("ALTER TABLE bookings ADD COLUMN price_per_passenger DECIMAL(10,2) NULL COMMENT 'Price per passenger'");
    }

    // Check if the base_fare and taxes_fees columns already exist
    $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'base_fare'");
    $base_fare_exists = ($column_check->num_rows > 0);

    $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'taxes_fees'");
    $taxes_fees_exists = ($column_check->num_rows > 0);

    $message = "";

    // Add base_fare column if it doesn't exist
    if (!$base_fare_exists) {
        $sql = "ALTER TABLE bookings ADD COLUMN base_fare DECIMAL(10,2) AFTER price_per_passenger";
        if ($conn->query($sql) === TRUE) {
            $message .= "<p>Successfully added base_fare column to bookings table.</p>";
        } else {
            $message .= "<p>Error adding base_fare column: " . $conn->error . "</p>";
        }
    }

    // Add taxes_fees column if it doesn't exist
    if (!$taxes_fees_exists) {
        $sql = "ALTER TABLE bookings ADD COLUMN taxes_fees DECIMAL(10,2) AFTER base_fare";
        if ($conn->query($sql) === TRUE) {
            $message .= "<p>Successfully added taxes_fees column to bookings table.</p>";
        } else {
            $message .= "<p>Error adding taxes_fees column: " . $conn->error . "</p>";
        }
    }

    // Update existing bookings to have base_fare and taxes_fees values if columns were added
    if (!$base_fare_exists || !$taxes_fees_exists) {
        $sql = "UPDATE bookings b 
                JOIN flights f ON b.flight_id = f.flight_id 
                SET b.base_fare = f.price * 0.85, 
                    b.taxes_fees = f.price * 0.15 
                WHERE b.base_fare IS NULL OR b.taxes_fees IS NULL";
        
        if ($conn->query($sql) === TRUE) {
            $message .= "<p>Successfully updated existing bookings with base_fare and taxes_fees values.</p>";
        } else {
            $message .= "<p>Error updating existing bookings: " . $conn->error . "</p>";
        }
    }

    // Update price_per_passenger for existing bookings
    $conn->query("UPDATE bookings SET price_per_passenger = CASE WHEN passengers > 0 THEN total_amount/passengers ELSE total_amount END 
                 WHERE price_per_passenger IS NULL");

    // Commit the transaction
    $conn->commit();
    $message = "<p>Bookings table updated successfully.</p>";

} catch (Exception $e) {
    // Roll back the transaction if any part fails
    $conn->rollback();
    $message = "<p>Error updating bookings table: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Bookings Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <h1 class="mb-4">Update Bookings Table Structure</h1>
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Operation Results</h5>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <div class="alert alert-info mt-3">
                    <h5>Why this update is important:</h5>
                    <p>This script ensures that the bookings table has the columns needed to store base fare and taxes information properly. This allows the application to display accurate price breakdowns for all bookings.</p>
                </div>
            </div>
            <div class="card-footer">
                <a href="../admin/dashboard.php" class="btn btn-primary">Return to Admin Dashboard</a>
                <a href="../admin/manage_bookings.php" class="btn btn-outline-secondary">Manage Bookings</a>
            </div>
        </div>
    </div>
</body>
</html>
