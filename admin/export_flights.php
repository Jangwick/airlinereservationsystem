<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="flight_export_' . date('Y-m-d') . '.csv"');

// Create file pointer to php://output
$output = fopen('php://output', 'w');

// Set column headers
fputcsv($output, [
    'Flight ID', 'Flight Number', 'Airline', 'Departure City', 'Departure Airport',
    'Arrival City', 'Arrival Airport', 'Departure Time', 'Arrival Time',
    'Status', 'Aircraft', 'Total Seats', 'Available Seats', 'Base Price'
]);

// Initialize filters from GET parameters
$filter_airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_origin = isset($_GET['origin']) ? $_GET['origin'] : '';
$filter_destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT * FROM flights WHERE 1=1";
$params = [];
$types = "";

// Add filters
if (!empty($filter_airline)) {
    $query .= " AND airline = ?";
    $params[] = $filter_airline;
    $types .= "s";
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_origin)) {
    $query .= " AND departure_city LIKE ?";
    $params[] = "%$filter_origin%";
    $types .= "s";
}

if (!empty($filter_destination)) {
    $query .= " AND arrival_city LIKE ?";
    $params[] = "%$filter_destination%";
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $query .= " AND DATE(departure_time) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $query .= " AND DATE(departure_time) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (flight_number LIKE ? OR airline LIKE ? OR departure_city LIKE ? OR arrival_city LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

// Add order by
$query .= " ORDER BY departure_time ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Output each row to CSV
while ($row = $result->fetch_assoc()) {
    // Format data as needed
    $row['departure_time'] = date('Y-m-d H:i:s', strtotime($row['departure_time']));
    $row['arrival_time'] = date('Y-m-d H:i:s', strtotime($row['arrival_time']));
    $row['base_price'] = number_format($row['base_price'], 2);
    
    fputcsv($output, [
        $row['flight_id'],
        $row['flight_number'],
        $row['airline'],
        $row['departure_city'],
        $row['departure_airport'],
        $row['arrival_city'],
        $row['arrival_airport'],
        $row['departure_time'],
        $row['arrival_time'],
        $row['status'],
        $row['aircraft'],
        $row['total_seats'],
        $row['available_seats'],
        $row['base_price']
    ]);
}

// Close file pointer
fclose($output);
exit;
?>
