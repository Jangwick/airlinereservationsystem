<?php
session_start();

// Include functions file for base URL
if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
} else {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

$baseUrl = getBaseUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-gavel me-2"></i> Terms & Conditions</h1>
                    <p class="lead">Please review our terms and conditions for using SkyWay Airlines services.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-md-end mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>" class="text-white">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Terms & Conditions</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Introduction -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-2x text-info"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading">Important Notice</h5>
                            <p class="mb-0">These Terms & Conditions constitute a legally binding agreement between you and SkyWay Airlines. By booking a flight, using our website, or using any of our services, you agree to comply with these terms.</p>
                        </div>
                    </div>
                </div>
                
                <p>Last Updated: <?php echo date('F d, Y'); ?></p>
                <p>Please read these terms carefully before using our services. If you do not agree with any part of these terms, you may not use our services.</p>
            </div>
        </div>
        
        <!-- Table of Contents -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h4 class="mb-0">Table of Contents</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ol>
                            <li><a href="#definitions">Definitions</a></li>
                            <li><a href="#booking-ticketing">Booking and Ticketing</a></li>
                            <li><a href="#fares-payments">Fares and Payments</a></li>
                            <li><a href="#reservations">Reservations and Seat Allocation</a></li>
                            <li><a href="#check-in">Check-in and Boarding</a></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <ol start="6">
                            <li><a href="#baggage">Baggage Regulations</a></li>
                            <li><a href="#refunds-changes">Refunds and Flight Changes</a></li>
                            <li><a href="#liability">Liability Limitations</a></li>
                            <li><a href="#privacy">Privacy and Data</a></li>
                            <li><a href="#other">Other Provisions</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Definitions -->
        <section id="definitions" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">1. Definitions</h2>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>In these Terms & Conditions, unless the context requires otherwise:</p>
                    <ul>
                        <li><strong>"Airline", "we", "our", "us"</strong> means SkyWay Airlines and its affiliates.</li>
                        <li><strong>"Passenger", "you", "your"</strong> refers to any person holding a ticket who is carried or is to be carried on an aircraft.</li>
                        <li><strong>"Ticket"</strong> means the document entitled "Passenger Ticket and Baggage Check" or the Electronic Ticket issued by or on behalf of the Airline.</li>
                        <li><strong>"E-Ticket"</strong> means the electronic entry in our systems which contains details of your flight(s).</li>
                        <li><strong>"Booking"</strong> means the reservation of a seat or seats for a flight or flights.</li>
                        <li><strong>"Website"</strong> means the SkyWay Airlines website located at www.skywayairlines.com.</li>
                        <li><strong>"Conditions of Carriage"</strong> means these terms and conditions.</li>
                        <li><strong>"Check-in Deadline"</strong> means the time limit specified by the airline by which you must have completed check-in formalities and received your boarding pass.</li>
                        <li><strong>"Force Majeure"</strong> means unusual and unforeseeable circumstances beyond our control, the consequences of which could not have been avoided even if all due care had been exercised.</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <!-- Section 2: Booking and Ticketing -->
        <section id="booking-ticketing" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">2. Booking and Ticketing</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">2.1 Booking Confirmation</h5>
                </div>
                <div class="card-body">
                    <p>A booking is confirmed only when:</p>
                    <ol>
                        <li>You have received a booking reference number and/or confirmation email.</li>
                        <li>Full payment for the ticket has been processed and accepted.</li>
                        <li>Passenger details provided match the passenger's travel documents.</li>
                    </ol>
                    <p>We reserve the right to cancel any booking that remains unpaid within the specified time limit.</p>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">2.2 Personal Information</h5>
                </div>
                <div class="card-body">
                    <p>You are responsible for providing accurate personal information when making a booking. This includes:</p>
                    <ul>
                        <li>Full name as it appears on your travel documents</li>
                        <li>Valid contact information (email and phone number)</li>
                        <li>Any special requirements or assistance needed</li>
                    </ul>
                    <p class="text-danger">Failure to provide accurate information may result in denied boarding without refund or compensation.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">2.3 Ticket Validity</h5>
                </div>
                <div class="card-body">
                    <p>Unless otherwise stated on the ticket, our tickets are valid for:</p>
                    <ul>
                        <li>One year from the date of issue, or</li>
                        <li>One year from the date of first travel if travel begins within one year from the date of issue.</li>
                    </ul>
                    <p>Tickets are non-transferable and can only be used by the passenger named on the ticket.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 3: Fares and Payments -->
        <section id="fares-payments" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">3. Fares and Payments</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">3.1 Fare Rules</h5>
                </div>
                <div class="card-body">
                    <p>All fares are subject to the following conditions:</p>
                    <ul>
                        <li>Fares are only guaranteed once payment has been completed and a ticket has been issued.</li>
                        <li>Fares may change without notice before booking is completed.</li>
                        <li>Different fare classes have different rules regarding changes, cancellations, and refunds.</li>
                        <li>Promotional fares may have additional restrictions.</li>
                        <li>Taxes, fees, and surcharges are subject to change due to governmental or regulatory requirements.</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">3.2 Payment Methods</h5>
                </div>
                <div class="card-body">
                    <p>We accept the following payment methods:</p>
                    <ul>
                        <li>Major credit and debit cards (Visa, MasterCard, American Express)</li>
                        <li>Online payment services (PayPal, GCash, Maya)</li>
                        <li>Bank transfers (for group bookings only)</li>
                    </ul>
                    <p>All payments must be made in the currency specified during the booking process. Additional fees may apply for certain payment methods.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">3.3 Price Errors</h5>
                </div>
                <div class="card-body">
                    <p>While we make every effort to ensure that all prices on our website are accurate, errors may occasionally occur.</p>
                    <p>If we discover a pricing error, we reserve the right to:</p>
                    <ul>
                        <li>Cancel the booking and provide a full refund</li>
                        <li>Contact you to offer the option to pay the correct price or cancel with a full refund</li>
                    </ul>
                    <p>This applies even after a booking confirmation has been sent.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 4: Reservations -->
        <section id="reservations" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">4. Reservations and Seat Allocation</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">4.1 Seat Reservations</h5>
                </div>
                <div class="card-body">
                    <p>Seat selection is available:</p>
                    <ul>
                        <li>During the booking process (fees may apply)</li>
                        <li>During online check-in (subject to availability)</li>
                        <li>At the airport check-in counter (subject to availability)</li>
                    </ul>
                    <p>We reserve the right to assign or reassign seats at any time, even after boarding has commenced. This may be necessary for operational, safety, or security reasons.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">4.2 Special Seating Requirements</h5>
                </div>
                <div class="card-body">
                    <p>Passengers who require special assistance or have specific seating needs should notify us at the time of booking or at least 48 hours before departure.</p>
                    <p>This includes:</p>
                    <ul>
                        <li>Passengers with reduced mobility</li>
                        <li>Passengers traveling with infants</li>
                        <li>Unaccompanied minors</li>
                        <li>Passengers requiring medical assistance</li>
                    </ul>
                    <p>We will make reasonable efforts to accommodate these requests but cannot guarantee availability in all cases.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 5: Check-in and Boarding -->
        <section id="check-in" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">5. Check-in and Boarding</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">5.1 Check-in Deadlines</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Flight Type</th>
                                    <th>Check-in Opens</th>
                                    <th>Check-in Closes</th>
                                    <th>Boarding Gate Closes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Domestic</td>
                                    <td>2 hours before departure</td>
                                    <td>45 minutes before departure</td>
                                    <td>20 minutes before departure</td>
                                </tr>
                                <tr>
                                    <td>International</td>
                                    <td>3 hours before departure</td>
                                    <td>60 minutes before departure</td>
                                    <td>30 minutes before departure</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i> Failure to check in or arrive at the boarding gate by the deadlines may result in denied boarding and cancellation of your reservation without refund.
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">5.2 Travel Documents</h5>
                </div>
                <div class="card-body">
                    <p>You are responsible for ensuring that you have all required travel documents, including but not limited to:</p>
                    <ul>
                        <li>Valid passport (with minimum 6 months validity from the date of return)</li>
                        <li>Visa(s) for your destination and transit countries if required</li>
                        <li>Health certificates or vaccination records if required</li>
                        <li>Any other documents required by the countries you are visiting</li>
                    </ul>
                    <p>We may deny boarding to passengers who fail to present the required travel documents, and no refund or compensation will be provided in such cases.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 6: Baggage -->
        <section id="baggage" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">6. Baggage Regulations</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">6.1 Baggage Allowance</h5>
                </div>
                <div class="card-body">
                    <p>Baggage allowances vary based on route, fare type, and cabin class. Detailed information can be found in our <a href="<?php echo $baseUrl; ?>pages/baggage.php">Baggage Information</a> page.</p>
                    <p>Excess baggage fees will apply for baggage exceeding the specified allowance. These fees must be paid at the airport before boarding.</p>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">6.2 Prohibited Items</h5>
                </div>
                <div class="card-body">
                    <p>For safety and security reasons, certain items are prohibited from being transported in either checked or cabin baggage. These include but are not limited to:</p>
                    <ul>
                        <li>Explosives, fireworks, and flammable substances</li>
                        <li>Gases and aerosols</li>
                        <li>Toxic or infectious substances</li>
                        <li>Corrosives</li>
                        <li>Firearms and weapons (unless properly declared and authorized)</li>
                        <li>Other dangerous items as defined by IATA regulations</li>
                    </ul>
                    <p>Additional restrictions may apply for cabin baggage, such as limits on liquids, gels, and sharp objects.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">6.3 Liability for Baggage</h5>
                </div>
                <div class="card-body">
                    <p>Our liability for damaged, delayed, or lost baggage is limited in accordance with applicable international conventions and regulations.</p>
                    <p>We strongly recommend that you:</p>
                    <ul>
                        <li>Obtain travel insurance that includes baggage coverage</li>
                        <li>Report any damaged or missing baggage immediately upon arrival</li>
                        <li>Do not pack valuable, fragile, or perishable items in your checked baggage</li>
                    </ul>
                    <p>Full details on baggage liability and claim procedures can be found in our Conditions of Carriage.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 7: Refunds and Changes -->
        <section id="refunds-changes" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">7. Refunds and Flight Changes</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">7.1 Voluntary Changes and Cancellations</h5>
                </div>
                <div class="card-body">
                    <p>Changes to your booking may be permitted depending on your fare type and the time remaining before departure. The following conditions apply:</p>
                    <ul>
                        <li>Change fees and fare difference may apply</li>
                        <li>Changes must be made before the scheduled departure time</li>
                        <li>Some promotional fares may not allow changes or cancellations</li>
                    </ul>
                    <p>Refund eligibility and associated fees depend on your fare type and the time of cancellation. Non-refundable tickets will not be refunded except as required by law.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">7.2 Schedule Changes and Cancellations by the Airline</h5>
                </div>
                <div class="card-body">
                    <p>In the event of a significant schedule change or flight cancellation by us, you will be offered the following options:</p>
                    <ol>
                        <li>Rebooking on the next available flight at no additional cost</li>
                        <li>Rebooking on an alternative date of your choice (fare difference may apply)</li>
                        <li>A refund of the unused portion of your ticket</li>
                    </ol>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Additional compensation may be available in certain circumstances, as required by applicable laws and regulations.
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Section 8: Liability -->
        <section id="liability" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">8. Liability Limitations</h2>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Our liability for death or personal injury, baggage loss or damage, and flight delays is governed by:</p>
                    <ul>
                        <li>The Montreal Convention or Warsaw Convention, as applicable</li>
                        <li>Local laws and regulations</li>
                        <li>Our Conditions of Carriage</li>
                    </ul>
                    
                    <p>We are not liable for any loss or damage that arises from compliance with laws, regulations, or government requirements, or from circumstances beyond our control (Force Majeure events), including but not limited to:</p>
                    <ul>
                        <li>Weather conditions</li>
                        <li>Air traffic control restrictions</li>
                        <li>Security concerns</li>
                        <li>Political instability or civil unrest</li>
                        <li>Natural disasters</li>
                    </ul>
                    
                    <div class="alert alert-warning mt-3">
                        <p class="mb-0"><strong>Important:</strong> The information provided in this section is a summary only. Full details regarding liability limitations can be found in our Conditions of Carriage, which are available upon request.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Section 9: Privacy -->
        <section id="privacy" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">9. Privacy and Data</h2>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <p>Personal information collected during the booking process and throughout your journey will be processed in accordance with our Privacy Policy.</p>
                    <p>By using our services, you consent to:</p>
                    <ul>
                        <li>Collection and processing of your personal information for booking, security, and customer service purposes</li>
                        <li>Sharing of your information with government authorities when required by law</li>
                        <li>Sharing of your information with third-party service providers (e.g., airports, ground handlers) to facilitate your journey</li>
                    </ul>
                    <p>You have the right to access, correct, or delete your personal information in accordance with applicable data protection laws.</p>
                    <p>For complete details, please refer to our <a href="#">Privacy Policy</a>.</p>
                </div>
            </div>
        </section>
        
        <!-- Section 10: Other Provisions -->
        <section id="other" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">10. Other Provisions</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">10.1 Amendment of Terms</h5>
                </div>
                <div class="card-body">
                    <p>We reserve the right to amend these Terms & Conditions at any time. The current version will be published on our website, and any changes will take effect immediately upon publication.</p>
                    <p>Your continued use of our services after such changes constitutes acceptance of the revised Terms & Conditions.</p>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">10.2 Governing Law</h5>
                </div>
                <div class="card-body">
                    <p>These Terms & Conditions shall be governed by and construed in accordance with the laws of the Philippines, without regard to its conflict of law provisions.</p>
                    <p>Any dispute arising out of or in connection with these Terms & Conditions shall be subject to the exclusive jurisdiction of the courts of Manila, Philippines.</p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">10.3 Contact Information</h5>
                </div>
                <div class="card-body">
                    <p>For questions or concerns regarding these Terms & Conditions, please contact us:</p>
                    <ul>
                        <li><strong>Email:</strong> legal@skywayairlines.com</li>
                        <li><strong>Phone:</strong> +63 (2) 8123 4567</li>
                        <li><strong>Address:</strong> 123 Airport Road, Metro Manila, Philippines</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <!-- Acceptance Statement -->
        <div class="alert alert-secondary mt-5">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="acceptTerms">
                <label class="form-check-label" for="acceptTerms">
                    I have read and agree to the Terms & Conditions of SkyWay Airlines.
                </label>
            </div>
        </div>
        
        <!-- Print Button -->
        <div class="text-center mt-4">
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print Terms & Conditions
            </button>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
