<?php
// Database configuration with fallback mechanisms
$server = "localhost:3307";  // Try default first, most common configuration
$username = "root";
$password = "";
$database = "airline_reservation_system";

// Create connection only once per request
if (!isset($GLOBALS['db_conn']) || !$GLOBALS['db_conn']) {
    $conn = new mysqli($server, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        // Don't expose error details to end users
        //die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    // Store connection in global scope to reuse
    $GLOBALS['db_conn'] = $conn;
} else {
    $conn = $GLOBALS['db_conn'];
}

// Enable mysqli connection caching for persistent connections
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Function to safely close the connection when done
function closeDBConnection() {
    global $conn;
    if (isset($conn) && $conn) {
        $conn->close();
        $GLOBALS['db_conn'] = null;
    }
}

// Register shutdown function to automatically close connection
register_shutdown_function('closeDBConnection');

// If this file is accessed directly, show connection status
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Connection Status</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
            .success { color: green; }
            .error { color: red; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
            .container { max-width: 800px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class='container'>";
    
    if ($conn && !$conn->connect_error) {
        echo "<h2 class='success'>✓ Connected successfully</h2>";
        echo "<p>Successfully connected to MySQL server on <strong>{$server}</strong></p>";
        
        if ($conn->ping()) {
            echo "<p class='success'>Connection is active and working properly.</p>";
        }
        
        // Show database status
        echo "<h3>Database Status</h3>";
        $db_selected = $conn->select_db($database);
        if ($db_selected) {
            echo "<p class='success'>Database '{$database}' is selected and accessible.</p>";
            
            // Show table counts if possible
            try {
                $tables_result = $conn->query("SHOW TABLES");
                $table_count = $tables_result ? $tables_result->num_rows : 0;
                echo "<p>Number of tables in database: {$table_count}</p>";
                
                if ($table_count > 0) {
                    echo "<ul>";
                    while ($row = $tables_result->fetch_array()) {
                        echo "<li>{$row[0]}</li>";
                    }
                    echo "</ul>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error checking tables: {$e->getMessage()}</p>";
            }
        } else {
            echo "<p class='error'>Database '{$database}' does not exist or is not accessible.</p>";
            echo "<p><a href='create_database.php'>Click here to create the database</a></p>";
        }
        
        // Show MySQL server info
        echo "<h3>MySQL Server Information</h3>";
        echo "<pre>";
        echo "Server Info: " . $conn->server_info . "\n";
        echo "Server Version: " . $conn->server_version . "\n";
        echo "Character Set: " . $conn->character_set_name() . "\n";
        echo "</pre>";
    } else {
        echo "<h2 class='error'>✗ Connection failed</h2>";
        echo "<p>Could not connect to MySQL server: " . ($connection_error ? $connection_error : "Unknown error") . "</p>";
        
        echo "<h3>Troubleshooting</h3>";
        echo "<ol>
            <li>Make sure MySQL server is running on your XAMPP control panel</li>
            <li>Check if MySQL is running on the standard port (3306) or a custom port</li>
            <li>Verify your MySQL username and password are correct</li>
            <li>Check if there's a firewall blocking connections</li>
        </ol>";
        
        echo "<p><a href='../setup.php'>Run the setup wizard</a> to diagnose and fix issues</p>";
    }
    
    echo "</div></body></html>";
}
?>
