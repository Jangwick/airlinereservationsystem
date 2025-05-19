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

// Handle status messages
$status_message = '';
$status_type = '';

if (isset($_SESSION['user_status'])) {
    $status_message = $_SESSION['user_status']['message'];
    $status_type = $_SESSION['user_status']['type'];
    unset($_SESSION['user_status']);
}

// Initialize search and filter variables
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

// Only apply status filter if the column exists
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
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get user statistics
$query_total_users = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$query_total_admins = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
$query_total_users_all = "SELECT COUNT(*) as count FROM users";

// Use specific query for active users if account_status exists
if ($account_status_exists) {
    $query_active_users = "SELECT COUNT(*) as count FROM users WHERE account_status = 'active'";
} else {
    // If account_status doesn't exist, consider all users active
    $query_active_users = "SELECT COUNT(*) as count FROM users";
}

$total_users = $conn->query($query_total_users)->fetch_assoc()['count'];
$total_admins = $conn->query($query_total_admins)->fetch_assoc()['count'];
$active_users = $conn->query($query_active_users)->fetch_assoc()['count'];
$all_users = $conn->query($query_total_users_all)->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
                    <h1 class="h2">User Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportUsers()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                        <a href="add_user.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Add New User
                        </a>
                    </div>
                </div>
                
                <!-- Status Messages -->
                <?php if (!empty($status_message)): ?>
                    <div class="alert alert-<?php echo $status_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $status_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$account_status_exists): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Notice:</strong> The account status feature is not fully configured. 
                        <a href="../db/update_users_table.php" class="alert-link">Click here</a> to add the required database column.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $all_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $active_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Regular Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Admin Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_admins; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Filter Users</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="manage_users.php" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search users..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="role">
                                    <option value="">All Roles</option>
                                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Regular User</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <?php if ($account_status_exists): ?>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-2">
                                <select class="form-select" name="sort">
                                    <option value="user_id" <?php echo $sort === 'user_id' ? 'selected' : ''; ?>>ID</option>
                                    <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                                    <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                                    <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                                    <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <select class="form-select" name="order">
                                    <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>ASC</option>
                                    <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>DESC</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">All Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <?php if ($account_status_exists): ?>
                                        <th>Status</th>
                                        <?php endif; ?>
                                        <th>Registration Date</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-warning text-dark">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <?php if ($account_status_exists): ?>
                                        <td>
                                            <?php 
                                            $status = isset($user['account_status']) ? $user['account_status'] : 'active';
                                            switch ($status) {
                                                case 'active':
                                                    echo '<span class="badge bg-success">Active</span>';
                                                    break;
                                                case 'inactive':
                                                    echo '<span class="badge bg-secondary">Inactive</span>';
                                                    break;
                                                case 'suspended':
                                                    echo '<span class="badge bg-danger">Suspended</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-success">Active</span>';
                                            }
                                            ?>
                                        </td>
                                        <?php endif; ?>
                                        
                                        <td>
                                            <?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <?php echo isset($user['last_login']) && $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user['user_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $user['user_id']; ?>">
                                                    <li><a class="dropdown-item" href="edit_user.php?id=<?php echo $user['user_id']; ?>">
                                                        <i class="fas fa-edit me-2"></i> Edit User
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="view_bookings.php?user_id=<?php echo $user['user_id']; ?>">
                                                        <i class="fas fa-ticket-alt me-2"></i> View Bookings
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    
                                                    <?php if ($account_status_exists): ?>
                                                    <?php $status = isset($user['account_status']) ? $user['account_status'] : 'active'; ?>
                                                    <?php if ($status !== 'suspended'): ?>
                                                    <li><a class="dropdown-item text-warning" href="#" onclick="changeStatus(<?php echo $user['user_id']; ?>, 'suspended')">
                                                        <i class="fas fa-ban me-2"></i> Suspend User
                                                    </a></li>
                                                    <?php else: ?>
                                                    <li><a class="dropdown-item text-success" href="#" onclick="changeStatus(<?php echo $user['user_id']; ?>, 'active')">
                                                        <i class="fas fa-check-circle me-2"></i> Activate User
                                                    </a></li>
                                                    <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                        <i class="fas fa-trash-alt me-2"></i> Delete User
                                                    </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="<?php echo $account_status_exists ? 9 : 8; ?>" class="text-center py-4">No users found matching your criteria</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <span id="usernameToDelete" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone. All user data, bookings, and history will be permanently deleted.</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteUserForm" action="user_actions.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" id="userIdToDelete" name="user_id" value="">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Change User Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm" action="user_actions.php" method="post">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" id="userIdForStatus" name="user_id" value="">
                        <input type="hidden" id="newStatus" name="status" value="">
                        
                        <div class="mb-3">
                            <label for="statusReason" class="form-label">Reason for status change (optional)</label>
                            <textarea class="form-control" id="statusReason" name="reason" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notifyUser" name="notify_user" value="1" checked>
                            <label class="form-check-label" for="notifyUser">Notify user by email</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('statusForm').submit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with disabled server-side processing
            // since we're already handling filtering through PHP
            $('#usersTable').DataTable({
                "paging": true,
                "searching": false,
                "ordering": false,
                "info": true,
                "lengthChange": false,
                "pageLength": 25,
            });
        });
        
        // Function to confirm user deletion
        function confirmDelete(userId, username) {
            document.getElementById('userIdToDelete').value = userId;
            document.getElementById('usernameToDelete').textContent = username;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // Function to change user status
        function changeStatus(userId, status) {
            document.getElementById('userIdForStatus').value = userId;
            document.getElementById('newStatus').value = status;
            
            // Update modal title based on status
            if (status === 'active') {
                document.getElementById('statusModalLabel').textContent = 'Activate User';
            } else if (status === 'suspended') {
                document.getElementById('statusModalLabel').textContent = 'Suspend User';
            } else {
                document.getElementById('statusModalLabel').textContent = 'Change User Status';
            }
            
            var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        }
        
        // Function to export users
        function exportUsers() {
            // Get current query parameters
            const queryParams = new URLSearchParams(window.location.search);
            // Redirect to export script with same parameters
            window.location.href = 'export_users.php?' + queryParams.toString();
        }
    </script>
</body>
</html>
