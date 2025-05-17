<?php
session_start();

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- About Us Header -->
    <div class="container-fluid bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">About SkyWay Airlines</h1>
                    <p class="lead">Connecting the world with comfort, safety, and reliability since 2010.</p>
                </div>
                <div class="col-md-6 text-center">
                    <i class="fas fa-plane fa-4x mb-3 animate__animated animate__fadeInRight"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Our Story -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <img src="../assets/images/about-us.jpg" alt="About SkyWay Airlines" class="img-fluid rounded shadow-lg">
                </div>
                <div class="col-md-6">
                    <h2 class="mb-4">Our Story</h2>
                    <p>SkyWay Airlines was founded in 2010 with a vision to create a better flying experience for everyone. What started as a small regional airline with just 2 aircraft has now grown into a major carrier with a fleet of over 50 modern aircraft serving more than 100 destinations worldwide.</p>
                    <p>Our journey has been defined by our commitment to passenger comfort, operational excellence, and technological innovation. We've continuously evolved our services to meet the changing needs of travelers in the digital age.</p>
                    <p>Today, SkyWay Airlines is recognized as one of the leading carriers in the Asia-Pacific region, known for exceptional service quality and customer satisfaction.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-crosshairs fa-3x text-primary"></i>
                            </div>
                            <h3 class="card-title">Our Mission</h3>
                            <p class="card-text">To provide safe, reliable, and comfortable air transportation that connects people across the globe while delivering exceptional value to our customers, employees, and stakeholders.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-eye fa-3x text-primary"></i>
                            </div>
                            <h3 class="card-title">Our Vision</h3>
                            <p class="card-text">To be the most preferred airline in the Asia-Pacific region, recognized for our customer-centric approach, operational excellence, and commitment to sustainability.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Leadership Team</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card team-card h-100">
                        <img src="../assets/images/team-1.jpg" class="card-img-top" alt="CEO">
                        <div class="card-body text-center">
                            <h5 class="card-title">Maria Santos</h5>
                            <p class="text-muted">Chief Executive Officer</p>
                            <p class="card-text small">Maria has over 25 years of experience in the aviation industry and has been leading SkyWay Airlines since its inception.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card team-card h-100">
                        <img src="../assets/images/team-2.jpg" class="card-img-top" alt="COO">
                        <div class="card-body text-center">
                            <h5 class="card-title">Robert Chen</h5>
                            <p class="text-muted">Chief Operations Officer</p>
                            <p class="card-text small">Robert oversees all operational aspects of SkyWay Airlines, ensuring safety and efficiency.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card team-card h-100">
                        <img src="../assets/images/team-3.jpg" class="card-img-top" alt="CFO">
                        <div class="card-body text-center">
                            <h5 class="card-title">Sarah Johnson</h5>
                            <p class="text-muted">Chief Financial Officer</p>
                            <p class="card-text small">Sarah manages the financial strategy of SkyWay Airlines with a focus on sustainable growth.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card team-card h-100">
                        <img src="../assets/images/team-4.jpg" class="card-img-top" alt="CTO">
                        <div class="card-body text-center">
                            <h5 class="card-title">Miguel Reyes</h5>
                            <p class="text-muted">Chief Technology Officer</p>
                            <p class="card-text small">Miguel leads our digital transformation initiatives and technological innovations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
