<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = $_GET['id'];

// Include database connection
require_once '../db/db_config.php';

// Check if account_status column exists
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
$account_status_exists = ($column_check->num_rows > 0);

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}

$user = $result->fetch_assoc();

// If account_status doesn't exist in the database, set a default value
if (!isset($user['account_status']) || !$account_status_exists) {
    $user['account_status'] = 'active'; // Default value
}

// Handle status messages
$status_message = '';
$status_type = '';

if (isset($_SESSION['user_status'])) {
    $status_message = $_SESSION['user_status']['message'];
    $status_type = $_SESSION['user_status']['type'];
    unset($_SESSION['user_status']);
}

// Get user's booking stats
$booking_stats = [
    'total' => 0,
    'completed' => 0,
    'upcoming' => 0,
    'cancelled' => 0
];

// Check if bookings table exists
$table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($table_check->num_rows > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN b.booking_status != 'cancelled' AND b.booking_status != 'completed' AND f.departure_time > NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM bookings b
            LEFT JOIN flights f ON b.flight_id = f.flight_id
            WHERE b.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();

        if ($stats_result->num_rows > 0) {
            $booking_stats = $stats_result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Silently handle the exception - stats aren't critical
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h1 class="h2">Edit User</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Users
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

                <div class="row">
                    <!-- User Form -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <form action="user_actions.php" method="post" class="needs-validation" novalidate>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            <div class="invalid-feedback">Please enter a first name.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            <div class="invalid-feedback">Please enter a last name.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            <div class="invalid-feedback">Please enter a username.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">New Password <span class="text-muted">(Leave blank to keep current)</span></label>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                            <div class="invalid-feedback">Passwords do not match.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="role" class="form-label">User Role <span class="text-danger">*</span></label>
                                            <select class="form-select" id="role" name="role" required <?php echo ($user_id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Regular User</option>
                                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                            </select>
                                            <?php if ($user_id == $_SESSION['user_id']): ?>
                                                <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                                <div class="form-text text-muted">You cannot change your own role.</div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($account_status_exists): ?>
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Account Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="status" name="status" required <?php echo ($user_id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                <option value="active" <?php echo ($user['account_status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo ($user['account_status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="suspended" <?php echo ($user['account_status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                            </select>
                                            <?php if ($user_id == $_SESSION['user_id']): ?>
                                                <input type="hidden" name="status" value="<?php echo $user['account_status']; ?>">
                                                <div class="form-text text-muted">You cannot change your own status.</div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Info Card -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">User Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="avatar-circle mb-3 mx-auto">
                                        <span class="initials"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                                    </div>
                                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="text-muted mb-0">
                                        <?php echo $user['role'] === 'admin' ? '<span class="badge bg-warning text-dark">Administrator</span>' : '<span class="badge bg-info">Regular User</span>'; ?>
                                    </p>
                                </div>
                                
                                <hr>
                                
                                <div class="user-details">
                                    <p><strong>User ID:</strong> <?php echo $user['user_id']; ?></p>
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Registration Date:</strong> <?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                                    <p><strong>Last Login:</strong> <?php echo isset($user['last_login']) && $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                                    <?php if ($account_status_exists): ?>
                                    <p>
                                        <strong>Status:</strong> 
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
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="booking-stats">
                                    <h6>Booking Statistics</h6>
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="h3"><?php echo $booking_stats['total']; ?></div>
                                            <div class="small text-muted">Total Bookings</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="h3"><?php echo $booking_stats['upcoming']; ?></div>
                                            <div class="small text-muted">Upcoming Flights</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="h3"><?php echo $booking_stats['completed']; ?></div>
                                            <div class="small text-muted">Completed Flights</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="h3"><?php echo $booking_stats['cancelled']; ?></div>
                                            <div class="small text-muted">Cancelled Bookings</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="text-center">
                                    <a href="view_bookings.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-ticket-alt me-1"></i> View All Bookings
                                    </a>
                                    <?php if ($user_id != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="confirmDelete(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            <i class="fas fa-trash-alt me-1"></i> Delete User
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        // Check if passwords match
                        var newPassword = document.getElementById('new_password')
                        var confirmPassword = document.getElementById('confirm_password')
                        
                        if (newPassword.value !== '' || confirmPassword.value !== '') {
                            if (newPassword.value !== confirmPassword.value) {
                                confirmPassword.setCustomValidity("Passwords don't match")
                                event.preventDefault()
                                event.stopPropagation()
                            } else {
                                confirmPassword.setCustomValidity('')
                            }
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Function to confirm user deletion
        function confirmDelete(userId, username) {
            document.getElementById('userIdToDelete').value = userId;
            document.getElementById('usernameToDelete').textContent = username;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
    
    <style>
    .avatar-circle {
        width: 80px;
        height: 80px;
        background-color: #3b71ca;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .initials {
        font-size: 32px;
        color: white;
        font-weight: bold;
    }
    </style>
</body>
</html>