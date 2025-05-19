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
header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');

// Create file pointer to php://output
$output = fopen('php://output', 'w');

// Set column headers
fputcsv($output, ['Log ID', 'Admin Username', 'Admin Name', 'Action', 'Details', 'Entity ID', 'IP Address', 'Date/Time']);

// Initialize filters
$admin_filter = isset($_GET['admin']) ? intval($_GET['admin']) : 0;
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '';

// Build the query
$query = "SELECT al.*, u.username, u.first_name, u.last_name
          FROM admin_logs al
          JOIN users u ON al.admin_id = u.user_id
          WHERE 1=1";
$params = [];
$types = "";

// Apply filters
if ($admin_filter > 0) {
    $query .= " AND al.admin_id = ?";
    $params[] = $admin_filter;
    $types .= "i";
}

if (!empty($action_filter)) {
    $query .= " AND al.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (al.details LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

// Apply timeframe filter if specified
if (!empty($timeframe)) {
    switch ($timeframe) {
        case 'today':
            $query .= " AND DATE(al.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $query .= " AND DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $query .= " AND YEARWEEK(al.created_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $query .= " AND YEAR(al.created_at) = YEAR(CURDATE()) AND MONTH(al.created_at) = MONTH(CURDATE())";
            break;
    }
}

// Add order by
$query .= " ORDER BY al.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();

// Output each row to CSV
while ($log = $logs_result->fetch_assoc()) {
    fputcsv($output, [
        $log['log_id'],
        $log['username'],
        $log['first_name'] . ' ' . $log['last_name'],
        $log['action'],
        $log['details'],
        $log['entity_id'],
        $log['ip_address'],
        $log['created_at']
    ]);
}

// Close file pointer
fclose($output);
exit;
