<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once '../db/db_config.php';

// Get search term
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

// Validate search term
if (empty($term) || strlen($term) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Prepare search query - search by first_name, last_name, email
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone,
          (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) as booking_count
          FROM users u
          WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
          ORDER BY u.last_name, u.first_name
          LIMIT 10";

$search_term = '%' . $term . '%';
$stmt = $conn->prepare($query);
$stmt->bind_param('sss', $search_term, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'user_id' => $row['user_id'],
        'first_name' => htmlspecialchars($row['first_name']),
        'last_name' => htmlspecialchars($row['last_name']),
        'email' => htmlspecialchars($row['email']),
        'phone' => htmlspecialchars($row['phone'] ?? ''),
        'booking_count' => (int)$row['booking_count']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($users);
