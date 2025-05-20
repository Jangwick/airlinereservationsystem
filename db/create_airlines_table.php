<?php
// Include database connection
require_once 'db_config.php';

// Check if the airlines table already exists
$table_check = $conn->query("SHOW TABLES LIKE 'airlines'");
if ($table_check->num_rows > 0) {
    echo "<p>Airlines table already exists.</p>";
} else {
    // SQL to create table
    $sql = "CREATE TABLE airlines (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(10) NULL,
        logo_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'airlines' created successfully</p>";
        
        // Now populate with some common airlines
        $airlines = [
            ['Philippine Airlines', 'PAL', 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/79/Philippine_Airlines_logo.svg/200px-Philippine_Airlines_logo.svg.png'],
            ['Cebu Pacific', 'CEB', 'https://upload.wikimedia.org/wikipedia/commons/9/99/Cebu_Pacific_Air_logo.svg'],
            ['AirAsia', 'AIR', 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/AirAsia_New_Logo.svg/200px-AirAsia_New_Logo.svg.png'],
            ['Singapore Airlines', 'SIA', 'https://upload.wikimedia.org/wikipedia/en/thumb/6/6b/Singapore_Airlines_Logo_2.svg/250px-Singapore_Airlines_Logo_2.svg.png'],
            ['Emirates', 'UAE', 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d0/Emirates_logo.svg/200px-Emirates_logo.svg.png'],
            ['Japan Airlines', 'JAL', 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/Japan_Airlines_logo_%282011%29.svg/200px-Japan_Airlines_logo_%282011%29.svg.png'],
            ['Korean Air', 'KAL', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Korean_Air_Logo.svg/200px-Korean_Air_Logo.svg.png']
        ];
        
        // Prepare insert statement
        $stmt = $conn->prepare("INSERT INTO airlines (name, code, logo_url) VALUES (?, ?, ?)");
        
        // Insert each airline
        foreach ($airlines as $airline) {
            $stmt->bind_param("sss", $airline[0], $airline[1], $airline[2]);
            $stmt->execute();
        }
        
        echo "<p>Added " . count($airlines) . " airlines to the database.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

echo "<p>Done! <a href='../flights/search.php'>Go to Flight Search</a></p>";
?>
