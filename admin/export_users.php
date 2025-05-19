<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Check if account_status column exists
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
$account_status_exists = ($column_check->num_rows > 0);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

// Create file pointer to php://output
$output = fopen('php://output', 'w');

// Define columns for the CSV
if ($account_status_exists) {
    fputcsv($output, ['User ID', 'Username', 'Email', 'First Name', 'Last Name', 'Role', 'Status', 'Registration Date', 'Last Login']);
} else {
    fputcsv($output, ['User ID', 'Username', 'Email', 'First Name', 'Last Name', 'Role', 'Registration Date', 'Last Login']);
}

// Initialize filters from GET parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'user_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Base query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

// Add filters
if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter) && $account_status_exists) {
    if ($status_filter === 'active') {
        $query .= " AND account_status = 'active'";
    } elseif ($status_filter === 'inactive') {
        $query .= " AND account_status = 'inactive'";
    } elseif ($status_filter === 'suspended') {
        $query .= " AND account_status = 'suspended'";
    }
}

// Add sorting
$allowed_sort_columns = ['user_id', 'username', 'email', 'first_name', 'last_name', 'role', 'created_at', 'last_login'];
$allowed_order = ['ASC', 'DESC'];

if (in_array($sort, $allowed_sort_columns) && in_array($order, $allowed_order)) {
    $query .= " ORDER BY $sort $order";
} else {
    $query .= " ORDER BY user_id ASC";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Output each row to CSV
while ($user = $result->fetch_assoc()) {
    $registration_date = isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : 'N/A';
    $last_login = isset($user['last_login']) && $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never';
    
    if ($account_status_exists) {
        $status = isset($user['account_status']) ? ucfirst($user['account_status']) : 'Active';
        
        fputcsv($output, [
            $user['user_id'],
            $user['username'],
            $user['email'],
            $user['first_name'],
            $user['last_name'],
            ucfirst($user['role']),
            $status,
            $registration_date,
            $last_login
        ]);
    } else {
        fputcsv($output, [
            $user['user_id'],
            $user['username'],
            $user['email'],
            $user['first_name'],
            $user['last_name'],
            ucfirst($user['role']),
            $registration_date,
            $last_login
        ]);
    }
}

// Close file pointer
fclose($output);
exit;
