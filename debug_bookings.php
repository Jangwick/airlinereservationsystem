<?php
// This is a diagnostic tool to help debug booking issues
// IMPORTANT: Remove or protect this file in production environments!

session_start();

// Check for admin user or development environment
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin access required.");
}

require_once 'db/db_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Booking System Diagnostic</h1>";

// Check if we have a specific user to debug
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// Print session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check users table
echo "<h2>Users Table</h2>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<p>User table structure verified.</p>";
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            echo "<p>Found user: {$user['first_name']} {$user['last_name']} (ID: {$user['user_id']})</p>";
        } else {
            echo "<p>No user found with ID: {$user_id}</p>";
        }
    }
} else {
    echo "<p>Error: Could not verify users table.</p>";
}

// Check booking tables
echo "<h2>Booking Tables</h2>";

// Check bookings table
$result = $conn->query("DESCRIBE bookings");
if ($result) {
    echo "<p>Bookings table structure verified.</p>";
    
    // Count total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $row = $result->fetch_assoc();
    echo "<p>Total bookings in system: {$row['total']}</p>";
    
    // Get recent bookings if user_id provided
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_id DESC LIMIT 10");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
        
        echo "<h3>Recent Bookings for User ID: {$user_id}</h3>";
        if ($bookings_result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>Booking ID</th>
                    <th>Flight ID</th>
                    <th>Booking Date</th>
                    <th>Passengers</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                </tr>";
            
            while ($booking = $bookings_result->fetch_assoc()) {
                echo "<tr>
                    <td>{$booking['booking_id']}</td>
                    <td>{$booking['flight_id']}</td>
                    <td>{$booking['booking_date']}</td>
                    <td>{$booking['passengers']}</td>
                    <td>\${$booking['total_amount']}</td>
                    <td>{$booking['booking_status']}</td>
                    <td>{$booking['payment_status']}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No bookings found for this user.</p>";
        }
    }
} else {
    echo "<p>Error: Could not verify bookings table.</p>";
}

// Check flights table (to ensure proper join)
echo "<h2>Flights Table</h2>";
$result = $conn->query("DESCRIBE flights");
if ($result) {
    echo "<p>Flights table structure verified.</p>";
    
    // Count total flights
    $result = $conn->query("SELECT COUNT(*) as total FROM flights");
    $row = $result->fetch_assoc();
    echo "<p>Total flights in system: {$row['total']}</p>";
} else {
    echo "<p>Error: Could not verify flights table.</p>";
}

// Try sample join query to test if the issue is with table structure
echo "<h2>Testing Joining Functionality</h2>";
$query = "SELECT b.booking_id, f.flight_number FROM bookings b 
          JOIN flights f ON b.flight_id = f.flight_id LIMIT 5";

$result = $conn->query($query);
if ($result) {
    echo "<p>Join query executed successfully.</p>";
    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>Booking ID: {$row['booking_id']} - Flight Number: {$row['flight_number']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No joined records found.</p>";
    }
} else {
    echo "<p>Error: Join query failed - " . $conn->error . "</p>";
    echo "<p>Query attempted: {$query}</p>";
}

// Check for any potential issues with the database schema
echo "<h2>Database Integrity Checks</h2>";
$checks = [
    "Foreign key check" => "SELECT COUNT(*) as count FROM bookings b 
                           LEFT JOIN flights f ON b.flight_id = f.flight_id 
                           WHERE f.flight_id IS NULL",
    "User ID null check" => "SELECT COUNT(*) as count FROM bookings WHERE user_id IS NULL",
    "Flight ID null check" => "SELECT COUNT(*) as count FROM bookings WHERE flight_id IS NULL",
    "Invalid status check" => "SELECT COUNT(*) as count FROM bookings 
                              WHERE booking_status NOT IN ('confirmed', 'pending', 'cancelled', 'completed')"
];

foreach ($checks as $name => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>{$name}: {$row['count']} issues found</p>";
    } else {
        echo "<p>{$name}: Error executing check - " . $conn->error . "</p>";
    }
}

// Provide suggestions for fixing issues
echo "<h2>Troubleshooting Suggestions</h2>";
echo "<ol>
    <li>Check your payment.php file to ensure proper transaction handling</li>
    <li>Ensure proper user_id is being set when creating bookings</li>
    <li>Verify database schema is correct with proper relationships</li>
    <li>Check for JavaScript errors that might prevent form submission</li>
    <li>Add more debugging/logging to payment processing code</li>
</ol>";
