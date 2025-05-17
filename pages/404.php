<?php
session_start();

// Get the base URL for the application
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/airlinereservationsystem/';
}

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <style>
        .error-container {
            padding: 100px 0;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #0d6efd;
            line-height: 1;
        }
        .error-plane {
            animation: plane-animation 6s infinite linear;
            display: inline-block;
        }
        @keyframes plane-animation {
            0% {
                transform: translateX(-50px) translateY(0) rotate(0);
            }
            25% {
                transform: translateX(50px) translateY(-20px) rotate(10deg);
            }
            50% {
                transform: translateX(100px) translateY(0) rotate(0);
            }
            75% {
                transform: translateX(50px) translateY(20px) rotate(-10deg);
            }
            100% {
                transform: translateX(-50px) translateY(0) rotate(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>">
                <i class="fas fa-plane-departure me-2"></i>SkyWay Airlines
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>flights/search.php">Flights</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container error-container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="error-code">
                    4<span class="error-plane"><i class="fas fa-plane"></i></span>4
                </div>
                <h1 class="display-4 mb-4">Page Not Found</h1>
                <p class="lead mb-4">The page you're looking for has flown to an unknown destination or doesn't exist.</p>
                <div class="mb-5">
                    <a href="<?php echo $baseUrl; ?>" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-home me-2"></i>Return Home
                    </a>
                    <a href="<?php echo $baseUrl; ?>flights/search.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Search Flights
                    </a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Looking for something?</h5>
                        <p>Try checking the URL for errors or use the navigation menu to find what you're looking for.</p>
                        <p>If you're still having trouble, please <a href="<?php echo $baseUrl; ?>pages/contact.php">contact our support team</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
