<?php
// Database credentials
$server = "localhost:3307";
$username = "root";
$password = "";
$database = "airline_reservation_system";

// Create connection
$conn = new mysqli($server, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

echo "<h2>Database Connection Test</h2>";
echo "<p style='color:green;'>Connection successful to MySQL server on $server</p>";
echo "<p>Database selected: $database</p>";

// Test querying the users table
$query = "SELECT COUNT(*) as user_count FROM users";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of users in the database: " . $row['user_count'] . "</p>";
} else {
    echo "<p style='color:red;'>Error querying users table: " . $conn->error . "</p>";
}

// Test querying the flights table
$query = "SELECT COUNT(*) as flight_count FROM flights";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of flights in the database: " . $row['flight_count'] . "</p>";
} else {
    echo "<p style='color:red;'>Error querying flights table: " . $conn->error . "</p>";
}

// Test querying the bookings table
$query = "SELECT COUNT(*) as booking_count FROM bookings";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of bookings in the database: " . $row['booking_count'] . "</p>";
} else {
    echo "<p style='color:red;'>Error querying bookings table: " . $conn->error . "</p>";
}

// Close connection
$conn->close();
echo "<p>Database connection closed.</p>";
echo "<p><a href='../index.php'>Return to homepage</a></p>";
?>
