<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Test database connection
if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Handle case where user doesn't exist in database
    $error_message = "User information could not be retrieved. Please contact support.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format";
        } else {
            // Check if email already exists (except for current user)
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email already in use by another account";
            } else {
                // Update user profile
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully";
                    
                    // Update session data
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error_message = "Error updating profile: " . $conn->error;
                }
            }
        }
    } else if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password
        if (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long";
        } else if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully";
                } else {
                    $error_message = "Error changing password: " . $conn->error;
                }
            } else {
                $error_message = "Current password is incorrect";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="card-text text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </div>
                
                <div class="list-group shadow-sm">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="bookings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-ticket-alt me-2"></i> My Bookings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <a href="../flights/search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Search Flights
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                    <i class="fas fa-user me-2"></i>Profile Information
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Profile Information Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                <h5 class="card-title mb-4">Personal Information</h5>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            <div class="invalid-feedback">Please enter your first name</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            <div class="invalid-feedback">Please enter your last name</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            <div class="invalid-feedback">Please enter a valid email</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="account_created" class="form-label">Account Created</label>
                                            <input type="text" class="form-control" id="account_created" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                        </div>
                                        <div class="col-md-12">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <h5 class="card-title mb-4">Change Password</h5>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <div class="invalid-feedback">Please enter your current password</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                            <div class="invalid-feedback">Password must be at least 6 characters long</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <div class="invalid-feedback">Passwords do not match</div>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </div>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5 class="card-title mb-4">Account Protection</h5>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notification_login" checked>
                                        <label class="form-check-label" for="notification_login">Email me when someone logs into my account</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notification_booking" checked>
                                        <label class="form-check-label" for="notification_booking">Email me when a new booking is made</label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="savePreferences">Save Preferences</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Travel Preferences</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="meal_preference" class="form-label">Meal Preference</label>
                                <select class="form-select" id="meal_preference">
                                    <option value="regular">Regular</option>
                                    <option value="vegetarian">Vegetarian</option>
                                    <option value="vegan">Vegan</option>
                                    <option value="halal">Halal</option>
                                    <option value="kosher">Kosher</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="seat_preference" class="form-label">Seat Preference</label>
                                <select class="form-select" id="seat_preference">
                                    <option value="window">Window</option>
                                    <option value="aisle">Aisle</option>
                                    <option value="middle">Middle</option>
                                    <option value="no_preference">No Preference</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="special_assistance" class="form-label">Special Assistance</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="wheelchair_assistance">
                                    <label class="form-check-label" for="wheelchair_assistance">Wheelchair Assistance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mobility_assistance">
                                    <label class="form-check-label" for="mobility_assistance">Mobility Assistance</label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="button" class="btn btn-outline-primary" id="saveTravelPreferences">Save Travel Preferences</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms to apply validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        // Additional validation for password match
                        if (form.querySelector('#confirm_password')) {
                            const newPassword = document.getElementById('new_password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;
                            
                            if (newPassword !== confirmPassword) {
                                document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
                                event.preventDefault();
                                event.stopPropagation();
                            } else {
                                document.getElementById('confirm_password').setCustomValidity('');
                            }
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Save preferences button (just for UI demonstration)
        document.getElementById('savePreferences').addEventListener('click', function() {
            alert('Preferences saved successfully!');
        });
        
        // Save travel preferences button (just for UI demonstration)
        document.getElementById('saveTravelPreferences').addEventListener('click', function() {
            alert('Travel preferences saved successfully!');
        });
    </script>
</body>
</html>
