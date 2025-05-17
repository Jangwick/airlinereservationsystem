<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Info - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .login-card {
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .info-box {
            background-color: #e9f5fe;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #0d6efd;
        }
        .warning {
            font-size: 0.9rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card mb-5">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Login Information</h4>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.png" alt="SkyWay Airlines Logo" class="img-fluid" style="max-height: 80px;">
                    <h5 class="mt-3">SkyWay Airlines Administration</h5>
                </div>
                
                <div class="info-box mb-4">
                    <h5 class="mb-3">Default Admin Credentials</h5>
                    <p class="mb-2"><strong>Username:</strong> <code>admin</code></p>
                    <p class="mb-2"><strong>Password:</strong> <code>admin123</code></p>
                    <p class="mt-3 mb-0 warning"><i class="fas fa-exclamation-triangle me-1"></i> Please change the default password after your first login for security reasons.</p>
                </div>
                
                <div class="mb-4">
                    <h5>Access Admin Dashboard</h5>
                    <p>To access the admin dashboard, please follow these steps:</p>
                    <ol>
                        <li>Go to the <a href="../auth/login.php">login page</a></li>
                        <li>Enter the admin credentials above</li>
                        <li>You will be automatically redirected to the admin dashboard</li>
                    </ol>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="../auth/login.php" class="btn btn-primary">Go to Login Page</a>
                    <a href="../index.php" class="btn btn-outline-secondary">Return to Homepage</a>
                </div>
            </div>
            <div class="card-footer text-center py-3">
                <small class="text-muted">For security assistance, please contact your system administrator.</small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
