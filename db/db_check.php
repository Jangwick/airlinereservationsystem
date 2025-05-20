<?php
// Include database connection
require_once 'db_config.php';

// Check flights table structure
echo "<h2>Flights Table Structure</h2>";
$result = $conn->query("DESCRIBE flights");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing flights table: " . $conn->error;
}

// Check for flights count
$result = $conn->query("SELECT COUNT(*) as count FROM flights");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total flights in database: " . $row['count'] . "</p>";
} else {
    echo "Error counting flights: " . $conn->error;
}

// Display some sample flights
echo "<h2>Sample Flights</h2>";
$result = $conn->query("SELECT * FROM flights LIMIT 5");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr>";
        
        // Get column names
        $first_row = $result->fetch_assoc();
        foreach ($first_row as $key => $value) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        // Display first row
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td>" . (is_null($value) ? "NULL" : $value) . "</td>";
        }
        echo "</tr>";
        
        // Display remaining rows
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . (is_null($value) ? "NULL" : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No flights found</p>";
    }
} else {
    echo "Error fetching sample flights: " . $conn->error;
}
?>
