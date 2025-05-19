<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Handle status messages
$status_message = '';
$status_type = '';

if (isset($_SESSION['user_status'])) {
    $status_message = $_SESSION['user_status']['message'];
    $status_type = $_SESSION['user_status']['type'];
    unset($_SESSION['user_status']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin Dashboard</title>
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
                    <h1 class="h2">Add New User</h1>
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

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="user_actions.php" method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    <div class="invalid-feedback">Please enter a first name.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    <div class="invalid-feedback">Please enter a last name.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback">Please enter a username.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback">Please enter a password.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="role" class="form-label">User Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user">Regular User</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Account Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
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
                        var password = document.getElementById('password')
                        var confirm_password = document.getElementById('confirm_password')
                        
                        if (password.value !== confirm_password.value) {
                            confirm_password.setCustomValidity("Passwords don't match")
                            event.preventDefault()
                            event.stopPropagation()
                        } else {
                            confirm_password.setCustomValidity('')
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
