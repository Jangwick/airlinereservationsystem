<?php
session_start();

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // In a real application, you would send the email here
        $success = "Thank you for your message! We will get back to you soon.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SkyWay Airlines</title>
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

    <!-- Contact Header -->
    <div class="container-fluid bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold">Contact Us</h1>
                    <p class="lead">Get in touch with our team for inquiries, support, or feedback.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Form & Info -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h2 class="mb-4">Send Us a Message</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="contact_form" value="1">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter your name</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                            <div class="invalid-feedback">Please enter a subject</div>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback">Please enter your message</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
                <div class="col-md-5 offset-md-1">
                    <h2 class="mb-4">Contact Information</h2>
                    <div class="mb-4">
                        <h5><i class="fas fa-map-marker-alt text-primary me-2"></i> Corporate Headquarters</h5>
                        <p>123 Airport Road<br>Metro Manila, Philippines</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="fas fa-phone-alt text-primary me-2"></i> Phone Numbers</h5>
                        <p>Customer Service: +63 (2) 8123 4567<br>
                        Reservations: +63 (2) 8123 4568<br>
                        Technical Support: +63 (2) 8123 4569</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="fas fa-envelope text-primary me-2"></i> Email</h5>
                        <p>General Inquiries: <a href="mailto:info@skywayairlines.com">info@skywayairlines.com</a><br>
                        Customer Support: <a href="mailto:support@skywayairlines.com">support@skywayairlines.com</a><br>
                        Careers: <a href="mailto:careers@skywayairlines.com">careers@skywayairlines.com</a></p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="fas fa-clock text-primary me-2"></i> Business Hours</h5>
                        <p>Monday - Friday: 8:00 AM - 8:00 PM<br>
                        Saturday: 9:00 AM - 5:00 PM<br>
                        Sunday: 10:00 AM - 4:00 PM</p>
                    </div>
                    <div class="social-links mt-4">
                        <a href="#" class="btn btn-outline-primary me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-primary me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-primary me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-primary"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Our Location</h2>
            <div class="row">
                <div class="col-12">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.802548413829!2d121.01212661535179!3d14.556504689828858!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c90264d0f82f%3A0xd2fa8e20940341ef!2sNinoy%20Aquino%20International%20Airport!5e0!3m2!1sen!2sph!4v1635142444117!5m2!1sen!2sph" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
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
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
