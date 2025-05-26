<?php
session_start();

// Check if database config exists and if we should suggest setup
$dbConfigExists = file_exists('db/db_config.php');
$dbSetupNeeded = false;

if ($dbConfigExists) {
    // Try to include database connection
    try {
        require_once 'db/db_config.php';
        // Check if we have tables
        if ($conn) {
            $result = $conn->query("SHOW TABLES");
            $dbSetupNeeded = ($result->num_rows == 0);
        } else {
            $dbSetupNeeded = true;
        }
    } catch (Exception $e) {
        $dbSetupNeeded = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyWay Airlines - Book Your Next Adventure</title>
    
    <!-- Performance optimization: Preload critical assets -->
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="assets/images/hero-bg.jpg" as="image" fetchpriority="high">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Critical CSS inline for faster rendering -->
    <style>
        /* Critical path CSS */
        .hero-section {
            background: url('assets/images/hero-bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            color: white;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .hero-section .container {
            position: relative;
            z-index: 1;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
    
    <!-- Non-critical CSS loaded asynchronously -->
    <link rel="stylesheet" href="assets/css/style.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="assets/css/performance.css" media="print" onload="this.media='all'">
    
    <!-- Font Awesome loaded with reduced priority -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Noscript fallback for CSS -->
    <noscript>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <?php if ($dbSetupNeeded): ?>
    <!-- Database Setup Banner - Only shown if database setup is needed -->
    <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="fas fa-database me-3 fa-2x"></i>
                <div>
                    <h4 class="alert-heading mb-1">Database Setup Required</h4>
                    <p class="mb-0">Your database needs to be configured before you can use the airline reservation system.</p>
                    <p class="mb-0 mt-2"><strong>Admin Login:</strong> Username: <code>admin</code> / Password: <code>admin123</code></p>
                </div>
                <div class="ms-auto">
                    <a href="db/create_database.php" class="btn btn-warning me-2">Setup Database</a>
                    <a href="auth/login.php" class="btn btn-success me-2">Admin Login</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold text-white">Discover the World with SkyWay Airlines</h1>
                    <p class="lead text-white">Book your flights easily, travel comfortably, and explore new destinations.</p>
                    <div class="mt-4">
                        <a href="#search-flights" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-search me-2"></i>Search Flights
                        </a>
                        <a href="user/register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Sign Up
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flight Search Box -->
    <section id="search-flights" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Search Flights</h3>
                            <form action="index.php" method="GET"> <!-- Changed to index.php until flights/search.php is created -->
                                <div class="row g-3">
                                    <!-- Trip Type -->
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="trip_type" id="one_way" value="one_way" checked>
                                            <label class="form-check-label" for="one_way">One Way</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="trip_type" id="round_trip" value="round_trip">
                                            <label class="form-check-label" for="round_trip">Round Trip</label>
                                        </div>
                                    </div>
                                    
                                    <!-- From -->
                                    <div class="col-md-6">
                                        <label for="departure_city" class="form-label">From</label>
                                        <select class="form-select" id="departure_city" name="departure_city" required>
                                            <option value="" selected disabled>Select departure city</option>
                                            <option value="Manila">Manila, Philippines</option>
                                            <option value="Cebu">Cebu, Philippines</option>
                                            <option value="Davao">Davao, Philippines</option>
                                            <option value="Singapore">Singapore</option>
                                            <option value="Tokyo">Tokyo, Japan</option>
                                            <option value="Seoul">Seoul, South Korea</option>
                                            <option value="Hong Kong">Hong Kong</option>
                                            <option value="Dubai">Dubai, UAE</option>
                                        </select>
                                    </div>
                                    
                                    <!-- To -->
                                    <div class="col-md-6">
                                        <label for="arrival_city" class="form-label">To</label>
                                        <select class="form-select" id="arrival_city" name="arrival_city" required>
                                            <option value="" selected disabled>Select arrival city</option>
                                            <option value="Manila">Manila, Philippines</option>
                                            <option value="Cebu">Cebu, Philippines</option>
                                            <option value="Davao">Davao, Philippines</option>
                                            <option value="Singapore">Singapore</option>
                                            <option value="Tokyo">Tokyo, Japan</option>
                                            <option value="Seoul">Seoul, South Korea</option>
                                            <option value="Hong Kong">Hong Kong</option>
                                            <option value="Dubai">Dubai, UAE</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Departure Date -->
                                    <div class="col-md-6">
                                        <label for="departure_date" class="form-label">Departure Date</label>
                                        <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                                    </div>
                                    
                                    <!-- Return Date -->
                                    <div class="col-md-6">
                                        <label for="return_date" class="form-label">Return Date</label>
                                        <input type="date" class="form-control" id="return_date" name="return_date" disabled>
                                    </div>
                                    
                                    <!-- Passengers -->
                                    <div class="col-md-6">
                                        <label for="passengers" class="form-label">Passengers</label>
                                        <select class="form-select" id="passengers" name="passengers">
                                            <option value="1">1 Passenger</option>
                                            <option value="2">2 Passengers</option>
                                            <option value="3">3 Passengers</option>
                                            <option value="4">4 Passengers</option>
                                            <option value="5">5 Passengers</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Class -->
                                    <div class="col-md-6">
                                        <label for="class" class="form-label">Class</label>
                                        <select class="form-select" id="class" name="class">
                                            <option value="economy">Economy</option>
                                            <option value="business">Business</option>
                                            <option value="first">First Class</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">Search Flights</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Destinations - Modified for lazy loading -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Popular Destinations</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card destination-card h-100">
                        <img data-src="assets/images/tokyo.jpg" class="card-img-top lazy" alt="Tokyo" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                        <div class="card-body">
                            <h5 class="card-title">Tokyo, Japan</h5>
                            <p class="card-text">Experience the unique blend of traditional culture and futuristic technology.</p>
                            <p class="text-primary fw-bold">From $450</p>
                            <a href="index.php?arrival_city=Tokyo" class="btn btn-outline-primary">Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card destination-card h-100">
                        <img data-src="assets/images/singapore.jpg" class="card-img-top lazy" alt="Singapore" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                        <div class="card-body">
                            <h5 class="card-title">Singapore</h5>
                            <p class="card-text">Discover the garden city with its stunning architecture and delicious food.</p>
                            <p class="text-primary fw-bold">From $320</p>
                            <a href="index.php?arrival_city=Singapore" class="btn btn-outline-primary">Explore</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card destination-card h-100">
                        <img data-src="assets/images/dubai.jpg" class="card-img-top lazy" alt="Dubai" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                        <div class="card-body">
                            <h5 class="card-title">Dubai, UAE</h5>
                            <p class="card-text">Marvel at the modern wonders and luxury experiences in the desert city.</p>
                            <p class="text-primary fw-bold">From $550</p>
                            <a href="index.php?arrival_city=Dubai" class="btn btn-outline-primary">Explore</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Special Offers -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Special Offers</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card special-offer-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4">
                                    <div class="offer-badge">
                                        <span>30%</span>
                                        <span>OFF</span>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <h4 class="card-title">Summer Sale</h4>
                                    <p class="card-text">Get 30% off on all flights to beach destinations.</p>
                                    <p><small class="text-muted">Use code: SUMMER30</small></p>
                                    <a href="index.php?promo=SUMMER30" class="btn btn-outline-primary">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card special-offer-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4">
                                    <div class="offer-badge bg-info">
                                        <span>20%</span>
                                        <span>OFF</span>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <h4 class="card-title">Business Class</h4>
                                    <p class="card-text">20% discount on all Business Class bookings.</p>
                                    <p><small class="text-muted">Use code: BIZ20</small></p>
                                    <a href="index.php?promo=BIZ20" class="btn btn-outline-primary">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose SkyWay Airlines</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="feature-box text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-plane-departure fa-3x text-primary"></i>
                        </div>
                        <h4>Extensive Network</h4>
                        <p>Connect to over 100 destinations worldwide with our growing network.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h4>Safe & Secure</h4>
                        <p>Your safety is our priority with rigorous maintenance standards.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-dollar-sign fa-3x text-primary"></i>
                        </div>
                        <h4>Best Value</h4>
                        <p>Competitive pricing with no compromise on service quality.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-headset fa-3x text-primary"></i>
                        </div>
                        <h4>24/7 Support</h4>
                        <p>Round-the-clock customer service to assist you anytime.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Destinations Section -->
    <section class="container my-5">
        <h2 class="text-center mb-5">Popular Destinations</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card destination-card h-100">
                    <img data-src="assets/images/tokyo.jpg" class="card-img-top lazy" alt="Tokyo" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                    <div class="card-body">
                        <h5 class="card-title">Tokyo, Japan</h5>
                        <p class="card-text">Experience the unique blend of traditional culture and futuristic technology.</p>
                        <p class="text-primary fw-bold">From $450</p>
                        <a href="index.php?arrival_city=Tokyo" class="btn btn-outline-primary">Explore</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card destination-card h-100">
                    <img data-src="assets/images/singapore.jpg" class="card-img-top lazy" alt="Singapore" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                    <div class="card-body">
                        <h5 class="card-title">Singapore</h5>
                        <p class="card-text">Discover the garden city with its stunning architecture and delicious food.</p>
                        <p class="text-primary fw-bold">From $320</p>
                        <a href="index.php?arrival_city=Singapore" class="btn btn-outline-primary">Explore</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card destination-card h-100">
                    <img data-src="assets/images/dubai.jpg" class="card-img-top lazy" alt="Dubai" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                    <div class="card-body">
                        <h5 class="card-title">Dubai, UAE</h5>
                        <p class="card-text">Marvel at the modern wonders and luxury experiences in the desert city.</p>
                        <p class="text-primary fw-bold">From $550</p>
                        <a href="index.php?arrival_city=Dubai" class="btn btn-outline-primary">Explore</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="display-6 fw-bold mb-3">Frequently Asked Questions</h2>
                    <p class="lead">Find answers to commonly asked questions about our services, bookings, and flights.</p>
                    <p class="mb-4">Our comprehensive FAQ section covers everything from booking procedures to baggage policies and flight changes.</p>
                    <a href="pages/faq.php" class="btn btn-primary">View All FAQs</a>
                </div>
                <div class="col-lg-7">
                    <div class="accordion" id="homeFaqAccordion">
                        <div class="accordion-item border-0 mb-2 shadow-sm">
                            <h2 class="accordion-header" id="homeHeading1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapse1" aria-expanded="false" aria-controls="homeCollapse1">
                                    How do I book a flight?
                            </button>
                            </h2>
                            <div id="homeCollapse1" class="accordion-collapse collapse" aria-labelledby="homeHeading1" data-bs-parent="#homeFaqAccordion">
                                <div class="accordion-body">
                                    You can book a flight through our website by using the search tool on our homepage. Enter your departure and arrival cities, dates, and number of passengers. Browse through the available options and select the flight that best suits your needs. Follow the booking process, provide passenger details, and make payment to confirm your reservation.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-2 shadow-sm">
                            <h2 class="accordion-header" id="homeHeading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapse2" aria-expanded="false" aria-controls="homeCollapse2">
                                    What is the baggage allowance?
                            </button>
                            </h2>
                            <div id="homeCollapse2" class="accordion-collapse collapse" aria-labelledby="homeHeading2" data-bs-parent="#homeFaqAccordion">
                                <div class="accordion-body">
                                    Baggage allowance varies depending on your ticket class and destination. Generally, economy class passengers are allowed one carry-on bag (max. 7kg) and one checked bag (max. 23kg). Business and First Class passengers are allowed additional or heavier baggage. Please check your specific flight details for accurate information.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-2 shadow-sm">
                            <h2 class="accordion-header" id="homeHeading3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#homeCollapse3" aria-expanded="false" aria-controls="homeCollapse3">
                                    How can I check in online?
                            </button>
                            </h2>
                            <div id="homeCollapse3" class="accordion-collapse collapse" aria-labelledby="homeHeading3" data-bs-parent="#homeFaqAccordion">
                                <div class="accordion-body">
                                    You can check in online through our website's "Web Check-in" section or through our mobile app. You'll need your booking reference number and the last name of the passenger. Online check-in opens 48 hours before flight departure and closes 1 hour before departure for domestic flights and 2 hours for international flights.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <h2 class="display-6 fw-bold mb-4">Subscribe to Our Newsletter</h2>
                <p class="lead mb-4">Get the latest updates, flight deals, and travel tips straight to your inbox.</p>
                <form action="subscribe.php" method="POST" class="rounded-3 overflow-hidden shadow">
                    <div class="input-group">
                        <input type="email" class="form-control border-0 py-3" placeholder="Enter your email address" aria-label="Email address" required>
                        <button class="btn btn-primary px-4" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <img src="assets/images/newsletter.jpg" class="img-fluid" alt="Newsletter Image">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Ensure Bootstrap JS loads first and isn't deferred -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dropdown fix script -->
    <script src="assets/js/dropdown-fix.js"></script>
    
    <!-- These scripts can be deferred -->
    <script defer src="assets/js/main.js"></script>
    <script defer src="assets/js/performance.js"></script>
    
    <!-- Inline critical JavaScript -->
    <script>
        // Critical JS functions
        // Toggle return date based on trip type
        document.addEventListener('DOMContentLoaded', function() {
            const tripTypeRadios = document.querySelectorAll('input[name="trip_type"]');
            const returnDateInput = document.getElementById('return_date');
            
            tripTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'round_trip') {
                        returnDateInput.removeAttribute('disabled');
                        returnDateInput.setAttribute('required', 'required');
                    } else {
                        returnDateInput.setAttribute('disabled', 'disabled');
                        returnDateInput.removeAttribute('required');
                    }
                });
            });
            
            // Prevent selecting departure city as arrival city
            const departureCitySelect = document.getElementById('departure_city');
            const arrivalCitySelect = document.getElementById('arrival_city');
            
            departureCitySelect.addEventListener('change', function() {
                const selectedDeparture = this.value;
                
                Array.from(arrivalCitySelect.options).forEach(option => {
                    if (option.value === selectedDeparture) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
            
            // Set minimum date for departure and return
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const formatDate = date => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            const departureDateInput = document.getElementById('departure_date');
            departureDateInput.min = formatDate(today);
            
            const returnDateInput = document.getElementById('return_date');
            returnDateInput.min = formatDate(tomorrow);
            
            departureDateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const nextDay = new Date(selectedDate);
                nextDay.setDate(nextDay.getDate() + 1);
                returnDateInput.min = formatDate(nextDay);
            });
        });
        
        // Add page transition handling
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.remove('page-transition');
        });
    </script>
</body>
</html>
