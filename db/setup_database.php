<?php
$server = "localhost:3307";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($server, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS airline_reservation_system";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("airline_reservation_system");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create flights table
$sql = "CREATE TABLE IF NOT EXISTS flights (
    flight_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(20) NOT NULL,
    airline VARCHAR(100) NOT NULL,
    departure_city VARCHAR(100) NOT NULL,
    arrival_city VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    duration VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT(11) NOT NULL,
    status ENUM('scheduled', 'delayed', 'cancelled', 'boarding', 'departed', 'arrived') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Flights table created successfully<br>";
} else {
    echo "Error creating flights table: " . $conn->error . "<br>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    flight_id INT(11) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    passengers INT(2) NOT NULL DEFAULT 1,
    seat_numbers VARCHAR(255) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'completed', 'refunded', 'failed') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Bookings table created successfully<br>";
} else {
    echo "Error creating bookings table: " . $conn->error . "<br>";
}

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    booking_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'gcash', 'maya', 'other') NOT NULL,
    payment_status ENUM('pending', 'completed', 'refunded', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Payments table created successfully<br>";
} else {
    echo "Error creating payments table: " . $conn->error . "<br>";
}

// Create tickets table
$sql = "CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    booking_id INT(11) NOT NULL,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    passenger_name VARCHAR(100) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    qr_code TEXT,
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Tickets table created successfully<br>";
} else {
    echo "Error creating tickets table: " . $conn->error . "<br>";
}

// Create promos table
$sql = "CREATE TABLE IF NOT EXISTS promos (
    promo_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    promo_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    discount_percent DECIMAL(5,2),
    discount_amount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Promos table created successfully<br>";
} else {
    echo "Error creating promos table: " . $conn->error . "<br>";
}

// Insert default admin account
$hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, email, password, first_name, last_name, role) 
        VALUES ('admin', 'admin@airlines.com', '$hashedPassword', 'System', 'Administrator', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "Default admin account created successfully<br>";
} else {
    if (strpos($conn->error, "Duplicate entry") !== false) {
        echo "Admin account already exists<br>";
    } else {
        echo "Error creating admin account: " . $conn->error . "<br>";
    }
}

// Insert sample flights data
$sample_flights = [
    [
        'flight_number' => 'SK101',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Manila',
        'arrival_city' => 'Tokyo',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 08:00')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 13:30')),
        'duration' => '5h 30m',
        'price' => 450.00,
        'available_seats' => 150,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK102',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Tokyo',
        'arrival_city' => 'Manila',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+3 days 14:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+3 days 18:00')),
        'duration' => '5h 30m',
        'price' => 480.00,
        'available_seats' => 145,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK103',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Manila',
        'arrival_city' => 'Singapore',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 10:15')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 14:00')),
        'duration' => '3h 45m',
        'price' => 320.00,
        'available_seats' => 180,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK104',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Singapore',
        'arrival_city' => 'Manila',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 15:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 19:15')),
        'duration' => '3h 45m',
        'price' => 340.00,
        'available_seats' => 175,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK105',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Manila',
        'arrival_city' => 'Dubai',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+3 days 23:15')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+4 days 05:45')),
        'duration' => '9h 30m',
        'price' => 550.00,
        'available_seats' => 160,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK106',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Dubai',
        'arrival_city' => 'Manila',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+5 days 02:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+5 days 17:00')),
        'duration' => '9h 30m',
        'price' => 580.00,
        'available_seats' => 155,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK107',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Manila',
        'arrival_city' => 'Hong Kong',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 07:45')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 10:15')),
        'duration' => '2h 30m',
        'price' => 280.00,
        'available_seats' => 185,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK108',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Hong Kong',
        'arrival_city' => 'Manila',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 17:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 20:00')),
        'duration' => '2h 30m',
        'price' => 295.00,
        'available_seats' => 180,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK109',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Manila',
        'arrival_city' => 'Seoul',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 09:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 14:45')),
        'duration' => '5h 15m',
        'price' => 420.00,
        'available_seats' => 170,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK110',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Seoul',
        'arrival_city' => 'Manila',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+3 days 16:00')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+3 days 21:15')),
        'duration' => '5h 15m',
        'price' => 440.00,
        'available_seats' => 165,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK201',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Cebu',
        'arrival_city' => 'Singapore',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+1 day 08:30')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+1 day 12:00')),
        'duration' => '3h 30m',
        'price' => 310.00,
        'available_seats' => 175,
        'status' => 'scheduled'
    ],
    [
        'flight_number' => 'SK202',
        'airline' => 'SkyWay Airlines',
        'departure_city' => 'Singapore',
        'arrival_city' => 'Cebu',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 13:15')),
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 16:45')),
        'duration' => '3h 30m',
        'price' => 325.00,
        'available_seats' => 170,
        'status' => 'scheduled'
    ]
];

// Insert sample flights
foreach ($sample_flights as $flight) {
    $sql = "INSERT INTO flights (flight_number, airline, departure_city, arrival_city, departure_time, arrival_time, duration, price, available_seats, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssis", 
        $flight['flight_number'], 
        $flight['airline'], 
        $flight['departure_city'], 
        $flight['arrival_city'], 
        $flight['departure_time'], 
        $flight['arrival_time'], 
        $flight['duration'], 
        $flight['price'], 
        $flight['available_seats'], 
        $flight['status']
    );
    
    if ($stmt->execute()) {
        echo "Flight {$flight['flight_number']} from {$flight['departure_city']} to {$flight['arrival_city']} added successfully<br>";
    } else {
        echo "Error adding flight {$flight['flight_number']}: " . $stmt->error . "<br>";
    }
}

// Insert sample promo codes
$sample_promos = [
    [
        'promo_code' => 'SUMMER30',
        'description' => 'Get 30% off on all flights to beach destinations. Valid from June to August.',
        'discount_percent' => 30.00,
        'discount_amount' => NULL,
        'start_date' => date('Y-m-d', strtotime('first day of June')),
        'end_date' => date('Y-m-d', strtotime('last day of August')),
        'status' => 'active'
    ],
    [
        'promo_code' => 'BIZ20',
        'description' => '20% discount on all Business Class bookings.',
        'discount_percent' => 20.00,
        'discount_amount' => NULL,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+3 months')),
        'status' => 'active'
    ],
    [
        'promo_code' => 'WELCOME50',
        'description' => '$50 off on your first booking with SkyWay Airlines.',
        'discount_percent' => NULL,
        'discount_amount' => 50.00,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+1 year')),
        'status' => 'active'
    ]
];

// Insert sample promos
foreach ($sample_promos as $promo) {
    $sql = "INSERT INTO promos (promo_code, description, discount_percent, discount_amount, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssddsss", 
        $promo['promo_code'], 
        $promo['description'], 
        $promo['discount_percent'], 
        $promo['discount_amount'], 
        $promo['start_date'], 
        $promo['end_date'], 
        $promo['status']
    );
    
    if ($stmt->execute()) {
        echo "Promo code {$promo['promo_code']} added successfully<br>";
    } else {
        echo "Error adding promo code {$promo['promo_code']}: " . $stmt->error . "<br>";
    }
}

$conn->close();
echo "Database setup completed!";
?>
