<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Check if admin_logs table exists, create if it doesn't
$logs_table_exists = $conn->query("SHOW TABLES LIKE 'admin_logs'")->num_rows > 0;
if (!$logs_table_exists) {
    $create_table_sql = "CREATE TABLE admin_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        entity_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (admin_id),
        INDEX (action),
        INDEX (created_at)
    )";
    $conn->query($create_table_sql);
    
    // Add a sample log entry
    $admin_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn->query("INSERT INTO admin_logs (admin_id, action, details, ip_address) 
                VALUES ($admin_id, 'system_init', 'Activity logs system initialized', '$ip')");
}

// Initialize filters
$admin_filter = isset($_GET['admin']) ? intval($_GET['admin']) : 0;
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

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

// Count total logs for pagination
$count_query = str_replace("SELECT al.*, u.username, u.first_name, u.last_name", "SELECT COUNT(*) as count", $query);
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_logs = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $items_per_page);

// Get logs with pagination
$query .= " ORDER BY al.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $items_per_page;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();
$logs = [];
while ($log = $logs_result->fetch_assoc()) {
    $logs[] = $log;
}

// Get all distinct actions for filter dropdown
$actions = [];
$action_result = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
while ($row = $action_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Get all admins for filter dropdown
$admins = [];
$admin_result = $conn->query("SELECT DISTINCT u.user_id, u.username, u.first_name, u.last_name 
                             FROM admin_logs al
                             JOIN users u ON al.admin_id = u.user_id
                             ORDER BY u.username");
while ($row = $admin_result->fetch_assoc()) {
    $admins[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportLogs()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="timeframeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar me-1"></i> Timeframe
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="timeframeDropdown">
                            <li><a class="dropdown-item" href="?timeframe=today">Today</a></li>
                            <li><a class="dropdown-item" href="?timeframe=yesterday">Yesterday</a></li>
                            <li><a class="dropdown-item" href="?timeframe=week">This Week</a></li>
                            <li><a class="dropdown-item" href="?timeframe=month">This Month</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?">All Time</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Filter Logs</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="admin_logs.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="admin" class="form-label">Admin User</label>
                                <select class="form-select" id="admin" name="admin">
                                    <option value="0">All Admins</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['user_id']; ?>" <?php echo $admin_filter == $admin['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['username'] . ' (' . $admin['first_name'] . ' ' . $admin['last_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="action" class="form-label">Action Type</label>
                                <select class="form-select" id="action" name="action">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo $action; ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $action)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="admin_logs.php" class="btn btn-outline-secondary ms-2">Reset</a>
                                <?php if (!empty($admin_filter) || !empty($action_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                                    <span class="ms-3 text-muted">
                                        <i class="fas fa-filter me-1"></i> Filters applied
                                    </span>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Logs Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">System Activity Logs</h5>
                            <span class="badge bg-primary"><?php echo $total_logs; ?> logs found</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Date/Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['log_id']; ?></td>
                                        <td>
                                            <a href="?admin=<?php echo $log['admin_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="?action=<?php echo $log['action']; ?>" class="badge bg-secondary text-decoration-none">
                                                <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                            </a>
                                        </td>
                                        <td class="text-wrap"><?php echo htmlspecialchars($log['details']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <h5>No activity logs found</h5>
                                            <p class="text-muted">No logs match your search criteria.</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Activity log pagination" class="mt-4 mx-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($admin_filter) ? '&admin=' . $admin_filter : ''; ?><?php echo !empty($action_filter) ? '&action=' . $action_filter : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($admin_filter) ? '&admin=' . $admin_filter : ''; ?><?php echo !empty($action_filter) ? '&action=' . $action_filter : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($admin_filter) ? '&admin=' . $admin_filter : ''; ?><?php echo !empty($action_filter) ? '&action=' . $action_filter : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($admin_filter) ? '&admin=' . $admin_filter : ''; ?><?php echo !empty($action_filter) ? '&action=' . $action_filter : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($admin_filter) ? '&admin=' . $admin_filter : ''; ?><?php echo !empty($action_filter) ? '&action=' . $action_filter : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function exportLogs() {
            // Get all current query parameters
            const queryParams = new URLSearchParams(window.location.search);
            
            // Add export parameter
            queryParams.set('export', 'csv');
            
            // Redirect to export URL
            window.location.href = 'export_logs.php?' + queryParams.toString();
        }
    </script>
</body>
</html>
